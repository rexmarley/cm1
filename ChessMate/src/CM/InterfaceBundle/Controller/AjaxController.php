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
	 * If successful, moves are validated server-side, to prevent tampering
	 * 
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */ 
    public function validateMoveAction(Request $request)
    {
    	//TODO: all in js but must also be checked server side to prevent tampering
    	//--> and another thing; no point checking check mate client-side
    	$from = $request->request->get('from');
    	$to = $request->request->get('to');
    	$type = $request->request->get('type');
    	$colour = $request->request->get('colour');    	
    	$gameID = $request->request->get('gameID');
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	$game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);

    	//make sure valid user for game
    	$user = $this->getUser();
    	if (!$game->getPlayers()->contains($user)) {
    		return new JsonResponse(array('valid' => false, 'checkMate' => false, 'board' => false));
    	}
    	
    	$validator = $this->get('move_validator');
    	
    	$valid = $validator->validateMove($from, $to, $type, $colour, $game);
    	
    	if ($valid['valid']) {
    		//$game->setBoard($game->getBoard()->setBoard($valid['board']));
    		$game->getBoard()->updateBoard($from, $to);
    		$em->flush();
//     		echo '<pre>';
//     		var_dump($game->getBoard()->getBoard());
//     		echo '</pre>';
    	}
    	
    	return new JsonResponse(array('valid' => $valid['valid'], 'checkMate' => false, 'board' => $valid['board']));
    }
}
