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
    	//var_dump($request->getContent()); 
    	$pieceType = $request->request->get('pieceType');
    	$fromSquare = $request->request->get('fromSquare');
    	$toSquare = $request->request->get('toSquare');
    	//check move
    	$success = $this->checkMove($pieceType, $fromSquare, $toSquare);
    	//set response
    	$response = array("success" => $success, "pt" => $pieceType);
    	//return JSON response
    	//--return new Response(json_encode($response));
    	return new JsonResponse($response);
    }
    
    public function checkMove($pieceType, $fromSquare, $toSquare) {
    	//temp - move to service?
    	$valid = false;
    	if ($pieceType == 'pawn') {
    		$valid = true;
    	}
    	return $valid;
    } 
}
