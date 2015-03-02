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
    	//TODO: all in js and just Ajax move if valid!!!!!!!!!!!!!!
    	$pieceType = $request->request->get('pieceType');
    	$colour = $request->request->get('colour');
    	$fromSquare = $request->request->get('fromSquare');
    	$toSquare = $request->request->get('toSquare');
    	//check move
    	$success = $this->checkMove($pieceType, $colour, $fromSquare, $toSquare);
    	//set response
    	$response = array("success" => $success, "from" => $fromSquare, "to" => $toSquare);
    	//return JSON response
    	//--return new Response(json_encode($response));
    	return new JsonResponse($response);
    }
    
    public function checkMove($pieceType, $colour, $fromSquare, $toSquare) {
    	//TODO: all in js and just Ajax move if valid!!!!!!!!!!!!!!
    	$valid = false;
		$from = explode('_', $fromSquare);
		$fLetter = $from[0];
		$fNumber = $from[1];
		$to = explode('_', $toSquare);
		$tLetter = $to[0];
		$tNumber = $to[1];
		//set positioning of letters TODO: something better (ASCII ?)
		$pos = array('a' => 1,'b' => 2,'c' => 3,'d' => 4,'e' => 5,'f' => 6,'g' => 7,'h' => 8);
    	if ($pieceType == 'pawn') {
			if ($fNumber == 2 || $fNumber == 7) {
				//allow initial movement of 2 spaces
				//no need to worry about moving extra space: if direction is different end of board is reached
				$colour *= 2;
			}
			//if ($colour == 'white') {
			if ($tNumber > $fNumber && $fLetter == $tLetter && $tNumber - $colour <= $fNumber) {
				$valid = true;
			}
			//}
    	} elseif ($pieceType == 'rook') {
			if ($fLetter == $tLetter || $fNumber == $tNumber) {
				$valid = true;
			}		
		} elseif ($pieceType == 'knight') {
			if ((($tNumber - $fNumber)*($tNumber - $fNumber)) + (($pos[$tLetter] - $pos[$fLetter])*($pos[$tLetter] - $pos[$fLetter])) == 5) {
				$valid = true;
			}		
		} elseif ($pieceType == 'bishop') {
			if ($tNumber - $fNumber == $pos[$tLetter] - $pos[$fLetter]) {
				$valid = true;
			}
		} elseif ($pieceType == 'queen') {
			if ($tNumber - $fNumber <= 1 && $pos[$tLetter] - $pos[$fLetter] <= 1) {
				$valid = true;
			}		
		} elseif ($pieceType == 'king') {
			if ($tNumber - $fNumber <= 1 && $pos[$tLetter] - $pos[$fLetter] <= 1) {
				$valid = true;
			}		
		} 

    	return $valid;
    } 
}
