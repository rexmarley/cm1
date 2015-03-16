<?php

namespace CM\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
	Symfony\Component\HttpFoundation\Request,
	Symfony\Component\HttpFoundation\Response,
	Symfony\Component\HttpFoundation\JsonResponse;
use CM\InterfaceBundle\Entity\Game;

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
    	//make sure valid user for game TODO: must also be player's turn!
    	$user = $this->getUser();
    	if (!$game->getPlayers()->contains($user)) {
    		return new JsonResponse(array('valid' => false, 'checkMate' => false, 'board' => false));
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
    		$em->flush();
    	}
    	
    	return new JsonResponse(array('valid' => $valid['valid'], 'checkMate' => false, 'board' => $board->getBoard())); //or game?
    }
}
