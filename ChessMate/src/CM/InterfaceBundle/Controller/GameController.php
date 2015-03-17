<?php

namespace CM\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use CM\UserBundle\Entity\User;

class GameController extends Controller
{    
	public function showBoardAction($gameID = null) {
		if (is_null($gameID)) {  
			$gameID = 'x';
		} else {
			//get white/black specific board
		}
    	$pieces = $this->getHTMLPieces();
        return $this->render('CMInterfaceBundle:Game:board.html.twig', 
        		array('gameID' => $gameID, 'pieces' => $pieces));	
	}
	
    public function newGameAction()
    {
    	return $this->render('CMInterfaceBundle:Game:index.html.twig', array());
    }
	
    public function resignAction()
    {
    	return $this->render('CMInterfaceBundle:Game:index.html.twig', array());    	
    }
	
    public function offerDrawAction()
    {
    	return $this->render('CMInterfaceBundle:Game:index.html.twig', array());    	
    }
	
    public function toggleChatAction()
    {
    	return $this->render('CMInterfaceBundle:Game:index.html.twig', array());    	
    }
	
    public function startAction()
    {
    	$pieces = $this->getHTMLPieces();
    	
        return $this->render('CMInterfaceBundle:Game:index.html.twig', array('pieces' => $pieces));
    }
	
    public function playAction($gameID = null)
    {    	
    	$user = $this->getUser();
    	if ($user) {
    		//$name = $user->getUsername();
    	}
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	//TODO: find matching game or get computer opponent 
    	$user2 = $em->getRepository('CMUserBundle:User')->findOneBy(array('username' => 'Rex2'));
    	if (is_null($gameID)) {
    		$game = $this->get('game_factory')->createNewGame(600, $user, $user2);
    		$em->persist($game);
    		$em->flush();
    	} else {
    		$game = $em->getRepository('CMUserBundle:User')->findOne($gameID);
    	}
    			    	
    	$pieces = $this->getHTMLPieces();
    	
        return $this->render('CMInterfaceBundle:Game:index.html.twig', array('pieces' => $pieces, 'game' => $game));
    }
    
    private function getHTMLPieces() {
    	//TODO: move to helper/service & switch white/black depending on player
    	$pieces = array(
    			array('id' => 'b_rook_1', 'img' => '&#9820;'),
    			array('id' => 'b_knight_1', 'img' => '&#9822;'),
    			array('id' => 'b_bishop_1', 'img' => '&#9821;'),
    			array('id' => 'b_queen', 'img' => '&#9819;'),
    			array('id' => 'b_king', 'img' => '&#9818;'),
    			array('id' => 'b_bishop_2', 'img' => '&#9821;'),
    			array('id' => 'b_knight_2', 'img' => '&#9822;'),
    			array('id' => 'b_rook_2', 'img' => '&#9820;'),
    			array('id' => 'b_pawn_1', 'img' => '&#9823;'),
    			array('id' => 'b_pawn_2', 'img' => '&#9823;'),
    			array('id' => 'b_pawn_3', 'img' => '&#9823;'),
    			array('id' => 'b_pawn_4', 'img' => '&#9823;'),
    			array('id' => 'b_pawn_5', 'img' => '&#9823;'),
    			array('id' => 'b_pawn_6', 'img' => '&#9823;'),
    			array('id' => 'b_pawn_7', 'img' => '&#9823;'),
    			array('id' => 'b_pawn_8', 'img' => '&#9823;'),
    			array('id' => 'w_pawn_1', 'img' => '&#9817;'),
    			array('id' => 'w_pawn_2', 'img' => '&#9817;'),
    			array('id' => 'w_pawn_3', 'img' => '&#9817;'),
    			array('id' => 'w_pawn_4', 'img' => '&#9817;'),
    			array('id' => 'w_pawn_5', 'img' => '&#9817;'),
    			array('id' => 'w_pawn_6', 'img' => '&#9817;'),
    			array('id' => 'w_pawn_7', 'img' => '&#9817;'),
    			array('id' => 'w_pawn_8', 'img' => '&#9817;'),
    			array('id' => 'w_rook_1', 'img' => '&#9814;'),
    			array('id' => 'w_knight_1', 'img' => '&#9816;'),
    			array('id' => 'w_bishop_1', 'img' => '&#9815;'),
    			array('id' => 'w_queen', 'img' => '&#9813;'),
    			array('id' => 'w_king', 'img' => '&#9812;'),
    			array('id' => 'w_bishop_2', 'img' => '&#9815;'),
    			array('id' => 'w_knight_2', 'img' => '&#9816;'),
    			array('id' => 'w_rook_2', 'img' => '&#9814;')
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
    	
        return $this->render('CMInterfaceBundle:Game:index.html.twig', array('name' => $name));
    }
}
