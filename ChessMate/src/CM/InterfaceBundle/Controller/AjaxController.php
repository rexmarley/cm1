<?php

namespace CM\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
	Symfony\Component\HttpFoundation\Request,
	Symfony\Component\HttpFoundation\Response,
	Symfony\Component\HttpFoundation\JsonResponse;

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
    	$valid = $this->validateMove($from, $to, $type, $colour);
    	
    	//$pieceType = $request->request->get('pieceType');
    	//$colour = $request->request->get('colour');
    	//$fromSquare = $request->request->get('fromSquare');
    	//$toSquare = $request->request->get('toSquare');
    	//check move
    	//$success = $this->checkMove($pieceType, $colour, $fromSquare, $toSquare);
    	//set response
    	//$response = array("success" => $success, "from" => $fromSquare, "to" => $toSquare);
    	//return JSON response
    	//--return new Response(json_encode($response));
    	return new JsonResponse(array('valid' => $valid['valid'], 'checkMate' => false, 'board' => $valid['board']));
    }
    
    private function validateMove(array $from, array $to, $type, $colour)
    {
    	//TODO: mysql PK=composite(userID1,userID2)
    	//TODO: service
    	//allow abstract validation
    	$board = array(
    			array('w_rook','w_knight','w_bishop','w_queen','w_king','w_bishop','w_knight','w_rook'),
    			array('w_pawn','w_pawn','w_pawn','w_pawn','w_pawn','w_pawn','w_pawn','w_pawn'),
    			array(false, false, false, false, false, false, false, false),
    			array(false, false, false, false, false, false, false, false),
    			array(false, false, false, false, false, false, false, false),
    			array(false, false, false, false, false, false, false, false),
    			array('b_pawn','b_pawn','b_pawn','b_pawn','b_pawn','b_pawn','b_pawn','b_pawn'),
    			array('b_rook','b_knight','b_bishop','b_queen','b_king','b_bishop','b_knight','b_rook')
    	);
    	//include redundant middle board to avoid resolving indices
    	$unmoved = array(
    			array(true, true, true, true, true, true, true, true),
    			array(true, true, true, true, true, true, true, true),
    			array(false, false, false, false, false, false, false, false),
    			array(false, false, false, false, false, false, false, false),
    			array(false, false, false, false, false, false, false, false),
    			array(false, false, false, false, false, false, false, false),
    			array(true, true, true, true, true, true, true, true),
    			array(true, true, true, true, true, true, true, true)
    	);    	
    	//check piece type/colour matches origin
//     	echo '<pre>';
//     	var_dump($from[0]);
//     	var_dump($from[1]);
//     	var_dump($board[$from[0]][$from[1]]);
//     	echo '</pre>';
		//temp
    	return array('valid' => false, 'board' => $board, 'unmoved' => $unmoved);
    	if ($board[$from[0]][$from[1]] != $colour.'_'.$type) {
    		return array('valid' => false, 'board' => $board, 'unmoved' => $unmoved);
    	}
    	return array('valid' => true, 'board' => $board);//don't need board/unmoved
    	
    }
    
    private function validatePawn()
    {
    	
    }
}
