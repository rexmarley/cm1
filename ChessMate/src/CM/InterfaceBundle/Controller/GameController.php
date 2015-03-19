<?php

namespace CM\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use CM\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use CM\InterfaceBundle\Entity\Game;
use Symfony\Component\HttpFoundation\JsonResponse;

class GameController extends Controller
{    
	public function showBoardAction($gameID = null) {
		if (is_null($gameID)) {  
			$gameID = 'x';
			$colour = 'w';
		} else {
		    $user = $this->getUser();	
	    	$em = $this->getDoctrine()->getManager();
	    	$game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
		    //authenticate user/game
		    $this->checkGameValidity($game, $user);
		    //get player colour
	    	$colour = $this->getPlayerColour($game, $user);
			//get white/black specific board
		}
    	//get game pieces
	    $pieces = $this->getHTMLPieces($colour);

        return $this->render('CMInterfaceBundle:Game:board.html.twig', 
        		array('gameID' => $gameID, 'pieces' => $pieces, 'player' => $colour));	
	}
	
    public function newGameAction(Request $request)
    {
    	$user = $this->getUser();
	    $em = $this->getDoctrine()->getManager();
    	//get game variables
    	$opponent = $request->request->get('opponent');
	    $duration = $request->request->get('duration') * 60; //TODO: mins. or secs.?
    	if ($opponent == 'any') {
    		//find any new game
	    	$game = $em->getRepository('CMInterfaceBundle:Game')->findAnyNewGame($user);
    	} else {
	    	$skill = $request->request->get('skill');
    		//find match
	    	$game = $em->getRepository('CMInterfaceBundle:Game')->findMatchingNewGame($user, $duration, $skill);
    	}
    	//create/join game
	    if ($game) {
			$game = $game[0];
		    $gameID = $game->getId();
	    	$playing = $game->getPlayers()->indexOf($user);
	    	if ($playing === false) {
		   		$game->setBlackPlayer($user);
		   		$game->setInProgress(true);
	    		$em->flush();
	    	}
	    } else {
	    	//create new game
    		$game = $this->get('game_factory')->createNewGame($duration, $user);
	    	$em->persist($game);
	    	$em->flush();
		    $gameID = $game->getId();
		    //wait for other player to join
		    //extend time-limit to 5 mins. - user has option to cancel
		    set_time_limit(300);
		    while ($game->getInProgress() == false) {
		    	//wait 1 second between checks
		    	sleep(1);
		    	$em->refresh($game);
		    }
	    }

    	return new JsonResponse(array('gameURL' => $this->generateUrl('cm_interface_play', array('gameID' => $gameID))));
    }
	
    public function playAction($gameID)
    {
	    $user = $this->getUser();	
    	$em = $this->getDoctrine()->getManager();
	    $game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
	    //authenticate user/game
	    $this->checkGameValidity($game, $user);

	    return $this->render('CMInterfaceBundle:Game:index.html.twig', array('game' => $game));
    }
    
    /**
     * Get player's colour
     * User validity must already be checked
     * 
     * @param Game $game
     * @param User $user
     * 
     * @return string
     */
    private function getPlayerColour($game, $user) {
    	if ($game->getPlayers()->get(0) == $user) {
    		return 'w';
    	}
    	return 'b';  	
    }
    
    /**
     * Check game exists and is valid for user
     * 
     * @param Game $game
     * @param User $user
     * @throws AccessDeniedException
     */
    private function checkGameValidity($game, $user)
    {
	    if ($game) {
	    	//make sure valid user for game
	    	if (!$game->getPlayers()->contains($user)) {
	    		throw new AccessDeniedException('You are not part of this game!');
	    	}
	    } else {
	    	throw $this->createNotFoundException('Game not found!');
	    }    	
    }
	
    public function resignAction($gameID)
    {
	    $user = $this->getUser();	
    	$em = $this->getDoctrine()->getManager();
	    $game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
	    //authenticate user/game
	    $this->checkGameValidity($game, $user);
    	//cancel game
    	$game->setInProgress(false);    	
    	
    	return $this->redirect($this->generateUrl('cm_interface_start', array()));	
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
    
    private function getHTMLPieces($for = 'w') {
    	//TODO: move to helper/service & switch white/black depending on player
    	if ($for == 'w') {
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
    	} else {
	    	$pieces = array(
	    			array('id' => 'w_rook_1', 'img' => '&#9814;'),
	    			array('id' => 'w_knight_1', 'img' => '&#9816;'),
	    			array('id' => 'w_bishop_1', 'img' => '&#9815;'),
	    			array('id' => 'w_king', 'img' => '&#9812;'),
	    			array('id' => 'w_queen', 'img' => '&#9813;'),
	    			array('id' => 'w_bishop_2', 'img' => '&#9815;'),
	    			array('id' => 'w_knight_2', 'img' => '&#9816;'),
	    			array('id' => 'w_rook_2', 'img' => '&#9814;'),
	    			array('id' => 'w_pawn_1', 'img' => '&#9817;'),
	    			array('id' => 'w_pawn_2', 'img' => '&#9817;'),
	    			array('id' => 'w_pawn_3', 'img' => '&#9817;'),
	    			array('id' => 'w_pawn_4', 'img' => '&#9817;'),
	    			array('id' => 'w_pawn_5', 'img' => '&#9817;'),
	    			array('id' => 'w_pawn_6', 'img' => '&#9817;'),
	    			array('id' => 'w_pawn_7', 'img' => '&#9817;'),
	    			array('id' => 'w_pawn_8', 'img' => '&#9817;'),
	    			array('id' => 'b_pawn_1', 'img' => '&#9823;'),
	    			array('id' => 'b_pawn_2', 'img' => '&#9823;'),
	    			array('id' => 'b_pawn_3', 'img' => '&#9823;'),
	    			array('id' => 'b_pawn_4', 'img' => '&#9823;'),
	    			array('id' => 'b_pawn_5', 'img' => '&#9823;'),
	    			array('id' => 'b_pawn_6', 'img' => '&#9823;'),
	    			array('id' => 'b_pawn_7', 'img' => '&#9823;'),
	    			array('id' => 'b_pawn_8', 'img' => '&#9823;'),
	    			array('id' => 'b_rook_1', 'img' => '&#9820;'),
	    			array('id' => 'b_knight_1', 'img' => '&#9822;'),
	    			array('id' => 'b_bishop_1', 'img' => '&#9821;'),
	    			array('id' => 'b_king', 'img' => '&#9818;'),
	    			array('id' => 'b_queen', 'img' => '&#9819;'),
	    			array('id' => 'b_bishop_2', 'img' => '&#9821;'),
	    			array('id' => 'b_knight_2', 'img' => '&#9822;'),
	    			array('id' => 'b_rook_2', 'img' => '&#9820;')
	    	);    		
    	}
    	
    	return $pieces;
    }
     
    public function guestAction()
    {
    	//check user is not already logged in
    	if (!$this->getUser()) {
	     	//get inactive guest account
	     	$userManager = $this->get('fos_user.user_manager');
			$em = $this->getDoctrine()->getManager();
			$user = $em->getRepository('CMUserBundle:User')->findInactiveGuest();
			if (!$user) {
				//create new guest accounts as needed
				$id = $em->createQuery('SELECT MAX(u.id) FROM CMUserBundle:User u')->getSingleScalarResult() + 1;
				$name = "Guest0".$id;
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
			//give guest average rating
			$user->setRating(1000);
			$userManager->updateUser($user);
			//set login token
			$token = new UsernamePasswordToken($user, $user->getPassword(), "main", $user->getRoles());
			$this->get("security.context")->setToken($token);		
			// fire login
			$event = new InteractiveLoginEvent($this->get("request"), $token);
			$this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
    	}
    	
    	return $this->redirect($this->generateUrl('cm_interface_start', array()));
    }
}
