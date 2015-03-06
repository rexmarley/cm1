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
}
