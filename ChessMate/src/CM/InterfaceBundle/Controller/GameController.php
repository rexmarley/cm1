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
	/**
	 * Find/Create new game
	 * 
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
    public function newGameAction(Request $request)
    {
    	$user = $this->getUser();
	    $em = $this->getDoctrine()->getManager();
    	//get game variables - null if match any
	    $duration = $request->request->get('duration');
	    $skill = $request->request->get('skill');
    	//find match
	    $search = $em->getRepository('CMInterfaceBundle:GameSearch')->findGameSearch($user, $duration, $skill);
    	//create search/game
	    if ($search) {
			$search = $search[0];
	    	$search->setMatched(true);
	    	$em->flush();
	    	//set search initiator as white
	    	$white = $search->getPlayer1();
	    	//get game length
	    	$length = $search->getLength();
	    	if (is_null($length)) {
		    	if (!is_null($duration)) {
		    		$length = $duration;
		    	} else {
		    		//any length - set default
		    		$length = 600;
		    	}	    		
	    	}
	    	//create game
    		$game = $this->get('game_factory')->createNewGame($length, $white, $user);
	    	$em->persist($game);
	    	$search->setGame($game);
			$em->flush();
    		//add to players' current games
    		$white->addCurrentGame($game);
    		$user->addCurrentGame($game);
	    	//get id
		    $gameID = $game->getId();
	    } else {
	    	//create new search
    		$search = $this->get('game_search_factory')->createNewSearch($user, $duration, $skill);
	    	$em->persist($search);
	    	$em->flush();
		    //wait for matches
		    //extend time-limit to 5 mins. - user has option to cancel
		    set_time_limit(300);
		    $em->refresh($search);
		    while (is_null($search->getGame())) {
		    	//wait 1 second between checks
		    	sleep(1);
		    	$em->refresh($search);
		    }
		    //get game id
		    $gameID = $search->getGame()->getId();
		    //delete search
		    $em->remove($search);
	    }
	    //save changes
		$em->flush();

    	return new JsonResponse(array('gameURL' => $this->generateUrl('cm_interface_play', array('gameID' => $gameID))));
    }
	
    /**
     * Start  game
     * 
     * @param int $gameID
     * @return \Symfony\Component\HttpFoundation\Response
     */
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
	 * Show embedded board view
	 * 
	 * @param int|string $gameID
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
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
			//$user->setRating(1000);
			$user->setRating(1410);
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
