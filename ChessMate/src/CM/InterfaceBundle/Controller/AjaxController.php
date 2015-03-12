<?php

namespace CM\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
	Symfony\Component\HttpFoundation\Request,
	Symfony\Component\HttpFoundation\Response,
	Symfony\Component\HttpFoundation\JsonResponse;
use CM\InterfaceBundle\Entity\Game;

class AjaxController extends Controller
{    
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
    	
    	$valid = $this->validateMove($from, $to, $type, $colour, $game);
    	
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
    
    private function validateMove(array $from, array $to, $type, $colour, Game $game)
    {
    	//TODO: mysql PK=composite(userID1,userID2)
    	//TODO: service
    	//allow abstract validation
    	$board = $game->getBoard()->getBoard();
    	//include redundant middle board to avoid resolving indices
    	$unmoved = $game->getBoard()->getUnmoved();
		//temp
    	//return array('valid' => false, 'board' => $board, 'unmoved' => $unmoved);
    	//check piece type/colour matches origin
    	if ($board[$from[0]][$from[1]] != $colour.'_'.$type) {
    		return array('valid' => false, 'board' => $board, 'unmoved' => $unmoved);
    	}
    	echo $colour.'**********';
    	echo $board[$to[0]][$to[1]][0].'**********';
    	//check target square is not occupied by own piece
    	if ($board[$to[0]][$to[1]] && $board[$to[0]][$to[1]][0] == $colour) {
    		return array('valid' => false, 'board' => $board, 'unmoved' => $unmoved);
    	}
    	return array('valid' => true, 'board' => $board);//don't need board/unmoved
    	
    }
    
    private function validatePawn()
    {
    	
    }
}
