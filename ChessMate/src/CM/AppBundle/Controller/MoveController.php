<?php

namespace CM\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
	Symfony\Component\HttpFoundation\Request,
	Symfony\Component\HttpFoundation\Response,
	Symfony\Component\HttpFoundation\JsonResponse;
use CM\AppBundle\Entity\Game;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MoveController extends Controller
{    
	/**
	 * Set last move for retrieval/validation by opponent
	 * If validity is not confirmed by opponent, server-side validation is conducted in subsequent call
	 * 
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */ 
    public function sendMoveAction(Request $request)
    {
    	$content = json_decode($request->getContent());
    	//find game
    	$em = $this->getDoctrine()->getManager();
    	$game = $em->getRepository('CMAppBundle:Game')->find($content->gameID);
    	if ($game->over()) {
    		return new JsonResponse(array('sent' => false));    		
    	}
    	$user = $this->getUser();
    	//make sure valid user for game & turn
	    $player = $game->getPlayers()->indexOf($user);
	    //check for attempted hacks that don't affect gameplay
    	if ($player === false) {
	    	throw new AccessDeniedException('You are not part of this game!');
    	} else if ($game->getActivePlayerIndex() != $player) {
	    	throw new AccessDeniedException('It is not your turn!');
    	} else if (!$game->getLastMoveValidated()) {
    		//attempt to make move without validating previous
	    	throw new AccessDeniedException('Stop messing about!');
    	}
    	//check player has time left
    	$moveTime = time() - $game->getLastMoveTime();
    	$timeLeft = $game->getPlayerTime($player) - $moveTime;
    	if ($timeLeft < $moveTime) {
    		$game->setGameOver($game->getInactivePlayerIndex(), 'Game Aborted: '.$user->getUsername().' has cheated.');
			$em->flush();
    	} else {
	    	$game->setLastMoveTime(time()+3); //TODO
	    	//get game over - if opponent disagrees, validate server-side 
	    	$gameOver = $content->gameOver;
	    	//get move details
	    	$move = array(
	    			'by' => $player,
	    			'from' => $content->from,
	    			'to' => $content->to,
	    			'castling' => (array) $content->castling,
	    			//'newBoard' => $content->board,
	    			'newFEN' => $content->fen,
	    			'enPassant' => $content->enPassant,
	    			'newPiece' => $content->newPiece,
	    			'gameOver' => $gameOver
	    	);
		    //save move for validation by opponent
		    $game->setLastMove($move);
			//mark move as unvalidated
			$game->setLastMoveValidated(false);
			$em->flush();
			if ($gameOver) {
				//get new ratings for display; don't update until verified
				if ($gameOver == 3) {
					//checkmate
			    	$wResult = 1;
			    	$lResult = 0;
				} else {
					//draw
			    	$wResult = 0.5;
			    	$lResult = 0.5;					
				}
				$opponent = $game->getPlayers()->get(1 - $player);
	    		$user->updateRating(array(array('opRating' => $opponent->getRating(), 'opRD' => $opponent->getDeviation(), 'result' => $wResult)));
	    		$opponent->updateRating(array(array('opRating' => $user->getRating(), 'opRD' => $user->getDeviation(), 'result' => $lResult)));
	    		//don't flush
    			return new JsonResponse(array('gameOver' => true, 'pRating' => $user->getRating(), 'opRating' => $opponent->getRating()));
			}
    	}

    	return new JsonResponse(array('gameOver' => false));
    }
    
    /**
     * Save move if validated by opponent]
     * @param int $gameID
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function saveMoveAction($gameID, Request $request) {
    	$content = json_decode($request->getContent());
    	$gameOver = $content->gameOver;
    	$user = $this->getUser();
    	//find game
    	$em = $this->getDoctrine()->getManager();
    	$game = $em->getRepository('CMAppBundle:Game')->find($gameID);
    	//switch active player
		$game->switchActivePlayer();
    	//make sure valid user for game
    	//& only allow save of opponent's move
	    $player = $game->getPlayers()->indexOf($user);
    	if ($player === false) {
	    	throw new AccessDeniedException('You are not part of this game!');
    	} else if ($game->getActivePlayerIndex() != $player) {
    		throw new AccessDeniedException('You may not save your own move!');
    	}
    	//get opponent's validated move
    	$move = $game->getLastMove();
		//mark move as validated
		$game->setLastMoveValidated(true);
    	//save move
    	$this->saveMove($game, $move, $em);
    	//check for game over
    	$em->refresh($game);
		$gameOverHelper = $this->get('game_fin_helper');
		$gameOver = $gameOverHelper->checkGameOver($game, $gameOver, $move['gameOver'], $player, $em);

	    return new JsonResponse(array('saved' => true, 'gameOver' => $gameOver));
    }
    
    /**
     * Save move
     * @param Game $game
     * @param array $move
     * @param unknown $em
     */
    private function saveMove(Game $game, array $move, $em) {
    	//update game state
    	$board = $game->getBoard();
    	$taken = $this->get('fen_helper')->getPieceFromFEN($board->getFEN(), $move['to'][0], $move['to'][1]);
    	if (!is_numeric($taken)) {
    		$board->addTaken($taken);
    	}
    	$board->setFEN($move['newFEN']);
    	$board->setEnPassant($move['enPassant']);
    	$board->setCastling($move['castling']);
    	$game->setBoard($board);
    	$em->flush();    	
    }
    
	/**
	 * Moves are validated client-side
	 * If consensus on validity differs between players, the cheat is exposed
	 * 
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */ 
    public function findCheatAction($gameID)
    {
    	$user = $this->getUser();
    	//find game
    	$em = $this->getDoctrine()->getManager();
    	$game = $em->getRepository('CMAppBundle:Game')->find($gameID);
    	//get user
	    $player = $game->getPlayers()->indexOf($user);
    	//get player that made move
    	$mover = $game->getActivePlayerIndex();
    	//get player that questioned validity
    	$shaker = $game->getInactivePlayerIndex();
	    //check for attempted hacks that don't affect gameplay
    	if ($player === false) {
	    	throw new AccessDeniedException('You are not part of this game!');
    	} else if ($game->getLastMoveValidated()) {
	    	throw new AccessDeniedException('Move already validated!');
    	} else if ($player == $mover) {
	    	throw new AccessDeniedException('Stop messing about');
    	} else if ($game->over()) {
	    	throw new AccessDeniedException('The game is over');		
    	}
    	$board = $game->getBoard();
    	$fenHelper = $this->get('fen_helper');
    	$absBoard = $fenHelper->getBoardFromFEN($board->getFEN());
    	//get attempted move
    	$attempted = $game->getLastMove();
    	//$absBoard = $board->getBoard();
    	$from = $attempted['from'];
    	$to = $attempted['to'];
    	//check piece exists at from square
    	$piece = $absBoard[$from[0]][$from[1]];
    	if (!$piece) {
    		//cheat = player that made move
    		$game->setGameOver($shaker, 'Game Aborted: '.$game->getPlayers()->get($mover)->getUsername().' has cheated.');
    	} else {
	    	//get move details
	    	$move = array(
	    			'from' => $from,
	    			'to' => $to,
	    			'piece' => $piece,
	    			'castling' => $attempted['castling'],
	    			'colour' => $this->getPieceColour($piece),
	    			'enPassant' => $attempted['enPassant'],
	    			'newPiece' => $attempted['newPiece'],
	    			//'newBoard' => $attempted['newBoard']
	    			'newBoard' => $fenHelper->getBoardFromFEN($attempted['newFEN'])
	    	);
	    	//make sure right colour moved
	    	if ($move['colour'] == 'w' and $mover == 1 || $move['colour'] == 'b' and $mover == 0) {
    			$game->setGameOver($shaker, 'Game Aborted: '.$game->getPlayers()->get($mover)->getUsername().' has cheated.');
	    	} else {
		    	//get piece validator 
		    	$validator = $this->get(strtolower($move['piece']).'_validator');
		    	//validate move
		    	$valid = $validator->validateMove($move, $game, $absBoard);
		    	if ($valid['valid']) {
		    		//cheater = active player i.e. player that questioned validity
    				$game->setGameOver($mover, 'Game Aborted: '.$game->getPlayers()->get($shaker)->getUsername().' has cheated.');
	    			//save validated move
	    			$this->saveMove($game, $attempted, $em);
		    	} else {
	    			//cheat = inactive player i.e. player that made move
    				$game->setGameOver($shaker, 'Game Aborted: '.$game->getPlayers()->get($mover)->getUsername().' has cheated.');
		    	}
	    	}
    	}
		//mark move as validated
		$game->setLastMoveValidated(true);
    	$em->flush();
   	 	return new JsonResponse(array('cheat' => true));
    }

	/**
	 * Get piece colour
	 * @param char $piece
	 * @return char
	 */
	private function getPieceColour($piece) {
		if (strtoupper($piece) == $piece) {
			return 'w';
		}
		return 'b';
	}
}
