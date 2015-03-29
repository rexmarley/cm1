<?php

namespace CM\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
	Symfony\Component\HttpFoundation\Request,
	Symfony\Component\HttpFoundation\Response,
	Symfony\Component\HttpFoundation\JsonResponse;
use CM\InterfaceBundle\Entity\Game;
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
    	$game = $em->getRepository('CMInterfaceBundle:Game')->find($content->gameID);
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
    		//throw new AccessDeniedException('You are out of time!');
	    	$game->setVictorIndex($game->getInactivePlayerIndex());    		
    	}
    	$game->setLastMoveTime(time());
    	//get move details
    	$move = array(
    			'from' => $content->from,
    			'to' => $content->to,
    			'newBoard' => $content->board,
    			'enPassant' => $content->enPassant,
    			'newPiece' => $content->newPiece
    	);
	    //save move for validation by opponent
	    $game->setLastMove($move);
		//mark move as unvalidated
		$game->setLastMoveValidated(false);
    	//switch active player
		$game->switchActivePlayer();
	    $em->flush();

    	return new JsonResponse(array('sent' => true));
    }
    
    /**
     * Get opponent's move; validity is checked on receipt
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getMoveAction(Request $request) {
    	//find game
    	$em = $this->getDoctrine()->getManager();
    	$gameID = $request->request->get('gameID');
    	$game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
    	$user = $this->getUser();
	    $player = $game->getPlayers()->indexOf($user);
	    if ($game->getLastMoveValidated()) {
	    	$cheat = $game->getCheaterIndex();
	    	if (!is_null($cheat)) {
	    		//player/opponent cheated - report back
	    		if ($cheat == $player) {
	    			$message = 'Game Aborted: Why cheat at chess?';
	    		} else {
	    			$message = 'Game Aborted: Opponent has cheated!';	    			
	    		}
	   	 		return new JsonResponse(array('moved' => true, 'cheat' => $message));	    		
	    	}
	    //check if move made	    	
	    } else if ($game->getActivePlayerIndex() == $player) {
	    	//get opponent's move
	    	$move = $game->getLastMove();	    	
			//return opponent's unvalidated move
		    return new JsonResponse(
		    	array('moved' => true,
	    				'checkMate' => false, // ?client-side/request check? 
		    			'from' => $move['from'], 
		    			'to' => $move['to'],
		    			'swapped' => $move['newPiece'],
	    				'enPassant' => $move['enPassant'],
	    				'newBoard' => $move['newBoard']
		    	));	    	
	    } else if (time() - $game->getLastMoveTime() > 30) {
	    	//move not validated by opponent within 30 secs. - assume foul play/game abandoned
	    	$game->setVictorIndex($player);
	    }
	    return new JsonResponse(array('moved' => false));	
    }
    
    /**
     * Save move if validated by opponent
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function saveMoveAction($gameID) {
    	$user = $this->getUser();
    	//find game
    	$em = $this->getDoctrine()->getManager();
    	$game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
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
    	
	    return new JsonResponse(array('saved' => true));
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
    	$board->setBoard($move['newBoard']);
    	$board->setEnPassantAvailable($move['enPassant']);
    	//mark piece as moved
    	$board->setPieceAsMoved($move['from'][0], $move['from'][1]);
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
    	$game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
    	//get user
	    $player = $game->getPlayers()->indexOf($user);
    	//get player that made move
    	$mover = $game->getInactivePlayerIndex();
    	//get player that questioned validity
    	$shaker = $game->getActivePlayerIndex();
	    //check for attempted hacks that don't affect gameplay
    	if ($player === false) {
	    	throw new AccessDeniedException('You are not part of this game!');
    	} else if ($game->getLastMoveValidated()) {
	    	throw new AccessDeniedException('Move already validated!');
    	} else if ($player == $mover) {
	    	throw new AccessDeniedException('Stop messing about');
    	}
    	$cheatMessage = 'Game Aborted: ';
    	//get attempted move
    	$attempted = $game->getLastMove();
    	$board = $game->getBoard(); 
    	$absBoard = $board->getBoard();
    	$from = $attempted['from'];
    	$to = $attempted['to'];
    	//check piece exists at from square
    	$piece = $absBoard[$from[0]][$from[1]];
    	if (!$piece) {
    		//cheat = inactive player i.e. player that made move
    		$game->setCheaterIndex($mover);
    		$cheatMessage .= 'Opponent has cheated!';
    	} else {
	    	$piece = explode("_", $piece);
	    	//get move details
	    	$move = array(
	    			'from' => $from,
	    			'to' => $to,
	    			'pColour' => $piece[0],
	    			'pType' => $piece[1],
	    			'newPiece' => $attempted['newPiece']
	    	);
	    	//make sure right colour moved
	    	if ($move['pColour'] == 'w' and $mover == 1 || $move['pColour'] == 'b' and $mover == 0) {
    			$game->setCheaterIndex($mover);
    			$cheatMessage .= 'Opponent has cheated!';
	    	} else {
		    	//get piece validator 
		    	$validator = $this->get($move['pType'].'_validator');
		    	//validate move
		    	$valid = $validator->validateMove($move, $game);
		    	if ($valid['valid']) {
		    		//cheater = active player i.e. player that questioned validity
	    			$game->setCheaterIndex($shaker);
    				$cheatMessage .= 'Why cheat at chess?';
	    			//save validated move
	    			$this->saveMove($game, $attempted, $em);
		    	} else {
	    			//cheat = inactive player i.e. player that made move
	    			$game->setCheaterIndex($mover);
    				$cheatMessage .= 'Opponent has cheated!';
		    	}
	    	}
    	}
		//mark move as validated
		$game->setLastMoveValidated(true);
    	$em->flush();
	    //return cheater 	
   	 	return new JsonResponse(array('cheat' => $cheatMessage));
    }
}
