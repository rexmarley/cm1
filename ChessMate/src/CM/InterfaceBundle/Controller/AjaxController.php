<?php

namespace CM\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
	Symfony\Component\HttpFoundation\Request,
	Symfony\Component\HttpFoundation\Response,
	Symfony\Component\HttpFoundation\JsonResponse;
use CM\InterfaceBundle\Entity\Game;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AjaxController extends Controller
{   
	/**
	 * Moves are validated client-side
	 * If valid, move is validated server-side, to prevent tampering
	 * 
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */ 
    public function validateMoveAction(Request $request)
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
    	//get move details
    	$move = array(
    			'from' => $request->request->get('from'),
    			'to' => $request->request->get('to'),
    			'pColour' => $request->request->get('colour'),
    			'pType' => $request->request->get('type'),
    			'newPiece' => $request->request->get('newPiece')
    	);
    	//get piece validator 
    	$validator = $this->get($move['pType'].'_validator');
    	//validate move
    	$valid = $validator->validateMove($move, $game);
    	$board = $game->getBoard();
    	if ($valid['valid']) {
    		//$game->getBoard()->updateBoard($move['from'], $move['to']);
    		$board->setBoard($valid['board']);
    		$game->setBoard($board);
    		//get opponent
    		$opponent = $game->getPlayers()->get(0);
    		if ($user == $opponent) {
    			$opponent = $game->getPlayers()->get(1);
    		}
    		$game->switchActivePlayer();
    		$em->flush();
    		//wait for opponents move - 5 mins. max
		    set_time_limit(300);
		    while ($game->getActivePlayerIndex() != $player) {
		    	//wait 1 second between checks
		    	sleep(1);
		    	$em->refresh($game);
		    }
		    //return opponent's valid move    	
	    	return new JsonResponse(
	    		array('valid' => true, 'checkMate' => false, 'board' => $board->getBoard(), 'from' => $move['from'], 'to' => $move['to'])
	    	);
		    
    	}
    	
    	return new JsonResponse(array('valid' => false));
    }
}
