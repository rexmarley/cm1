<?php

namespace CM\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class DefaultController extends Controller
{    
    public function playAction()
    {    	
    	$user = $this->getUser();
    	if ($user) {
    		$name = $user->getUsername();
    	}
    	
    	$pieces = $this->getPieces();
    	
        return $this->render('CMInterfaceBundle:Default:index.html.twig', array('name' => $name, 'pieces' => $pieces));
    }
    
    private function getPieces() {
    	$pieces = array(
    			array('id' => 'b_rook_1', 'img' => '&#9820;', 'colour' => 'b', 'type' => 'rook'),
    			array('id' => 'b_knight_1', 'img' => '&#9822;', 'colour' => 'b', 'type' => 'knight'),
    			array('id' => 'b_bishop_1', 'img' => '&#9821;', 'colour' => 'b', 'type' => 'bishop'),
    			array('id' => 'b_queen', 'img' => '&#9819;', 'colour' => 'b', 'type' => 'queen'),
    			array('id' => 'b_king', 'img' => '&#9818;', 'colour' => 'b', 'type' => 'king'),
    			array('id' => 'b_bishop_2', 'img' => '&#9821;', 'colour' => 'b', 'type' => 'bishop'),
    			array('id' => 'b_knight_2', 'img' => '&#9822;', 'colour' => 'b', 'type' => 'knight'),
    			array('id' => 'b_rook_2', 'img' => '&#9820;', 'colour' => 'b', 'type' => 'rook'),
    			array('id' => 'b_pawn_1', 'img' => '&#9823;', 'colour' => 'b', 'type' => 'pawn'),
    			array('id' => 'b_pawn_2', 'img' => '&#9823;', 'colour' => 'b', 'type' => 'pawn'),
    			array('id' => 'b_pawn_3', 'img' => '&#9823;', 'colour' => 'b', 'type' => 'pawn'),
    			array('id' => 'b_pawn_4', 'img' => '&#9823;', 'colour' => 'b', 'type' => 'pawn'),
    			array('id' => 'b_pawn_5', 'img' => '&#9823;', 'colour' => 'b', 'type' => 'pawn'),
    			array('id' => 'b_pawn_6', 'img' => '&#9823;', 'colour' => 'b', 'type' => 'pawn'),
    			array('id' => 'b_pawn_7', 'img' => '&#9823;', 'colour' => 'b', 'type' => 'pawn'),
    			array('id' => 'b_pawn_8', 'img' => '&#9823;', 'colour' => 'b', 'type' => 'pawn'),
    			array('id' => 'w_pawn_1', 'img' => '&#9817;', 'colour' => 'w', 'type' => 'pawn'),
    			array('id' => 'w_pawn_2', 'img' => '&#9817;', 'colour' => 'w', 'type' => 'pawn'),
    			array('id' => 'w_pawn_3', 'img' => '&#9817;', 'colour' => 'w', 'type' => 'pawn'),
    			array('id' => 'w_pawn_4', 'img' => '&#9817;', 'colour' => 'w', 'type' => 'pawn'),
    			array('id' => 'w_pawn_5', 'img' => '&#9817;', 'colour' => 'w', 'type' => 'pawn'),
    			array('id' => 'w_pawn_6', 'img' => '&#9817;', 'colour' => 'w', 'type' => 'pawn'),
    			array('id' => 'w_pawn_7', 'img' => '&#9817;', 'colour' => 'w', 'type' => 'pawn'),
    			array('id' => 'w_pawn_8', 'img' => '&#9817;', 'colour' => 'w', 'type' => 'pawn'),
    			array('id' => 'w_rook_1', 'img' => '&#9814;', 'colour' => 'w', 'type' => 'rook'),
    			array('id' => 'w_knight_1', 'img' => '&#9816;', 'colour' => 'w', 'type' => 'knight'),
    			array('id' => 'w_bishop_1', 'img' => '&#9815;', 'colour' => 'w', 'type' => 'bishop'),
    			array('id' => 'w_queen', 'img' => '&#9813;', 'colour' => 'w', 'type' => 'queen'),
    			array('id' => 'w_king', 'img' => '&#9812;', 'colour' => 'w', 'type' => 'king'),
    			array('id' => 'w_bishop_2', 'img' => '&#9815;', 'colour' => 'w', 'type' => 'bishop'),
    			array('id' => 'w_knight_2', 'img' => '&#9816;', 'colour' => 'w', 'type' => 'knight'),
    			array('id' => 'w_rook_2', 'img' => '&#9814;', 'colour' => 'w', 'type' => 'rook')
    	);
    	
    	return $pieces;
    }
     
    public function guestPlayAction()
    {    	
     	$userManager = $this->get('fos_user.user_manager');	
     	//get inactive guest account
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository('CMUserBundle:User')->findInactiveGuest();
		if (!$user) {
			$name = "Guest 00" . rand(1,1000);
			$user = $userManager->createUser();
			$user->setUsername($name);		
			$user->setEmail($name);
			$user->setPassword("");
			$user->setRegistered(false);
			$user->setLastActiveTime(new \DateTime());
		} else {
			$user = $user[0];
			$name = $user->getUsername();
			$user->setLastActiveTime(new \DateTime());
		}
		$userManager->updateUser($user);
		//set login token
		$token = new UsernamePasswordToken($user, $user->getPassword(), "main", $user->getRoles());
		$this->get("security.context")->setToken($token);		
		// fire login
		$event = new InteractiveLoginEvent($this->get("request"), $token);
		$this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
    	
        return $this->render('CMInterfaceBundle:Default:index.html.twig', array('name' => $name));
    }
}
