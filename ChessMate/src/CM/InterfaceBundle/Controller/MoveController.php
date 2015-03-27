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
// 	/**
// 	 * Moves are validated client-side
// 	 * If valid, move is validated server-side, to prevent tampering
// 	 * 
// 	 * @param Request $request
// 	 * @return \Symfony\Component\HttpFoundation\JsonResponse
// 	 */ 
//     public function validateMoveAction(Request $request)
//     {
//     	//find game
//     	$em = $this->getDoctrine()->getManager();
//     	$gameID = $request->request->get('gameID');
//     	$game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
//     	$user = $this->getUser();
//     	//make sure valid user for game & turn
// 	    $player = $game->getPlayers()->indexOf($user);
//     	if ($player === false) {
// 	    	throw new AccessDeniedException('You are not part of this game!');
//     	} else if ($game->getActivePlayerIndex() != $player) {
//     		throw new AccessDeniedException('It is not your turn!');
//     	}
//     	//check player has time left
//     	$moveTime = time() - $game->getLastMoveTime();
//     	$timeLeft = $game->getPlayerTime($player) - $moveTime;
//     	if ($timeLeft < $moveTime) {
//     		throw new AccessDeniedException('You are out of time!');    		
//     	}    	
//     	//get move details
//     	$move = array(
//     			'from' => $request->request->get('from'),
//     			'to' => $request->request->get('to'),
//     			'pColour' => $request->request->get('colour'),
//     			'pType' => $request->request->get('type'),
//     			'newPiece' => $request->request->get('newPiece')
//     	);
//     	//make sure right colour moved
//     	if ($move['pColour'] == 'w' and $player == 1 || $move['pColour'] == 'b' and $player == 0) {
//     		throw new AccessDeniedException('That is not your piece!');    		
//     	}
//     	//get piece validator 
//     	$validator = $this->get($move['pType'].'_validator');
//     	//validate move
//     	$valid = $validator->validateMove($move, $game);
//     	if ($valid['valid']) {
//     		//update game state
//     		$board = $game->getBoard(); 
//     		$board->setBoard($valid['board']);
//     		//set last move for retrieval by opponent
//     		$board->setLastMoveFrom($move['from']);
//     		$board->setLastMoveTo($move['to']);
//     		$game->setBoard($board);
// 			$game->switchActivePlayer();
// 	    	//update player's time
// 	    	$game->setLastMoveTime(time());
// 	    	$game->setPlayerTime($player, $timeLeft - $moveTime);
// 	    	//save
//     		$em->flush();
//     	}
// 	    //return valid/invalid 	
//     	return new JsonResponse(array('valid' => $valid['valid'], 'gameID' => $gameID));
//     }
    
	/**
	 * Set last move for retrieval/validation by opponent
	 * If validity is not confirmed by opponent, server-side validation is conducted in subsequent call
	 * 
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */ 
    public function sendMoveAction(Request $request)
    {
    	//find game
    	$em = $this->getDoctrine()->getManager();
    	$gameID = $request->request->get('gameID');
    	$game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
    	$user = $this->getUser();
    	//make sure valid user for game & turn
	    $player = $game->getPlayers()->indexOf($user);
    	if ($player === false) {
	    	throw new AccessDeniedException('You are not part of this game!');
    	} else if ($game->getActivePlayerIndex() != $player) {
    		throw new AccessDeniedException('It is not your turn!');
    	}
    	//check player has time left
    	$moveTime = time() - $game->getLastMoveTime();
    	$timeLeft = $game->getPlayerTime($player) - $moveTime;
    	if ($timeLeft < $moveTime) {
    		throw new AccessDeniedException('You are out of time!');    		
    	}    	
    	//get move details
    	$move = array(
    			'from' => $request->request->get('from'),
    			'to' => $request->request->get('to'),
    			'newBoard' => $request->request->get('board'),
    			'enPassant' => $request->request->get('enPassant'),
    			'newPiece' => $request->request->get('newPiece')
    	);
	    //set move for validation by opponent
	    $game->getBoard()->setLastMove($move); //TODO: store in Game?
		$game->switchActivePlayer();
	    $em->flush();
	    
    	return new JsonResponse(array('sent' => true));
    }
    
    /**
     * Get opponent's move for validation
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    function getMoveAction(Request $request) {
    	//find game
    	$em = $this->getDoctrine()->getManager();
    	$gameID = $request->request->get('gameID');
    	$game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
    	$user = $this->getUser();
	    $player = $game->getPlayers()->indexOf($user);
	    //check if move made
	    if ($game->getActivePlayerIndex() == $player) {
	    	//get opponent's move
	    	$move = $game->getBoard()->getLastMove();
			//return opponent's valid move
		    return new JsonResponse(
		    	array('moved' => true,
	    				'checkMate' => false, // ?client-side/request check? 
		    			'from' => $move['from'], 
		    			'to' => $move['to'],
		    			'swapped' => $move['newPiece'],
	    				'enPassant' => $move['enPassant'],
	    				'newBoard' => $move['newBoard']
		    	));	    	
	    }
	    return new JsonResponse(array('moved' => false));	
    }
    
//     /**
//      * Get opponent's move
//      * @param Request $request
//      * @return \Symfony\Component\HttpFoundation\JsonResponse
//      */
//     function getMoveAction(Request $request) {
//     	//find game
//     	$em = $this->getDoctrine()->getManager();
//     	$gameID = $request->request->get('gameID');
//     	$game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
//     	$user = $this->getUser();
// 	    $player = $game->getPlayers()->indexOf($user);
// 	    //check if move made
// 	    if ($game->getActivePlayerIndex() == $player) {
// 	    	//get opponent's move
// 	    	$board = $game->getBoard();
// 			//return opponent's valid move
// 		    return new JsonResponse(
// 		    	array('moved' => true,
// 	    				'checkMate' => false,
// 	    				'enPassant' => $board->getEnPassantAvailable(), 
// 		    			'pieceSwapped' => $board->getPawnSwapped(),
// 		    			'board' => $board->getBoard(), 
// 		    			'from' => $board->getLastMoveFrom(), 
// 		    			'to' => $board->getLastMoveTo()
// 		    	));	    	
// 	    }
// 	    return new JsonResponse(array('moved' => false));	
//     }
    
    /**
     * Save move if validated by opponent
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    function saveMoveAction($gameID) {
    	$user = $this->getUser();
    	//find game
    	$em = $this->getDoctrine()->getManager();
    	$game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
    	//make sure valid user for game
    	//& only allow save of opponents move
	    $player = $game->getPlayers()->indexOf($user);
    	if ($player === false) {
	    	throw new AccessDeniedException('You are not part of this game!');
    	} else if ($game->getActivePlayerIndex() != $player) {
    		throw new AccessDeniedException('You may not save your own move!');
    	}
    	//save move
    	//get opponent's valid move
    	$move = $game->getBoard()->getLastMove();
    	$move['validated'] = true; //not used?
    	//update game state
    	$board = $game->getBoard();
    	$board->setBoard($move['newBoard']);
    	$board->setEnPassantAvailable($move['enPassant']);
    	//mark piece as moved
    	$board->setPieceAsMoved($move['from'][0], $move['from'][1]);
    	$game->setBoard($board);
    	
	    return new JsonResponse(array('saved' => true));
    }
	/**
	 * Moves are validated client-side
	 * If valid consensus differs between players, the cheat is exposed
	 * 
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */ 
    public function findCheatAction($gameID)
    {
    	//find game
    	$em = $this->getDoctrine()->getManager();
    	$game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
    	$user = $this->getUser();
    	//make sure valid user for game - probably not needed
	    $player = $game->getPlayers()->indexOf($user);
    	if ($player === false) {
	    	throw new AccessDeniedException('You are not part of this game!');
    	}
    	$board = $game->getBoard(); 
    	//get attempted move
    	$attempted = $board->getLastMove();
    	$absBoard = $board->getBoard();
    	$from = $attempted['from'];
    	$to = $attempted['to'];
    	$piece = explode("_", $absBoard[$from][$to]);
    	//get move details
    	$move = array(
    			'from' => $from,
    			'to' => $to,
    			'pColour' => $piece[0],
    			'pType' => $piece[1],
    			'newPiece' => $attempted['newPiece']
    	);
    	//make sure right colour moved
    	if ($move['pColour'] == 'w' and $player == 0 || $move['pColour'] == 'b' and $player == 1) {
    		throw new AccessDeniedException('Not opponent\'s piece!');    		
    	}
    	//get piece validator 
    	$validator = $this->get($move['pType'].'_validator');
    	//validate move
    	$valid = $validator->validateMove($move, $game);
    	if ($valid['valid']) {
    		//update game state
    		$board->setBoard($valid['board']);
    		//set last move for retrieval by opponent
    		$board->setLastMoveFrom($move['from']);
    		$board->setLastMoveTo($move['to']);
    		$game->setBoard($board);
			$game->switchActivePlayer();
	    	//update player's time
	    	$game->setLastMoveTime(time());
	    	$game->setPlayerTime($player, $timeLeft - $moveTime);
	    	//save
    		$em->flush();
    	}
	    //return valid/invalid 	
    	return new JsonResponse(array('valid' => $valid['valid'], 'gameID' => $gameID));
    }
}
