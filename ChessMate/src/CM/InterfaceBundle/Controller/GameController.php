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
     * Login as inactive guest account
     * Create's a new account if none are available
     * 
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
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
	
    /**
     * Index action
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function startAction()
    {
	    $user = $this->getUser();	
    	$games = $user->getCurrentGames();
    	
        return $this->render('CMInterfaceBundle:Game:index.html.twig', array('games' => $games, 'player' => 'x'));
    }
    
	/**
	 * Create search
	 * 
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
    public function newSearchAction(Request $request)
    {
    	$user = $this->getUser();
	    $em = $this->getDoctrine()->getManager();
    	//get game variables - null if match any
	    $duration = $request->request->get('duration');
	    $skill = $request->request->get('skill');
	    //create new search
    	$search = $this->get('game_search_factory')->createNewSearch($user, $duration, $skill);
	    $em->persist($search);
	    //save changes
		$em->flush();

    	return new JsonResponse(array('searchID' => $search->getId()));
    }
    
	/**
	 * Find/create new game
	 * 
	 * @param int $searchID
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
    public function matchSearchAction($searchID)
    {
	    $em = $this->getDoctrine()->getManager();
	    $search = $em->getRepository('CMInterfaceBundle:GameSearch')->find($searchID);
	    if ($search) {
	    	$user = $this->getUser();
	    	if ($search->getSearcher() != $user) {
		    	throw new AccessDeniedException('This is not your search!');    		
	    	}
	     	//if user's search not matched by opponent
		    if (!$search->getMatched()) {
		    	//find match
		     	$length = $search->getLength();
		     	$minRank = $search->getMinRank();
		     	$maxRank = $search->getMaxRank();
		     	$repo = $em->getRepository('CMInterfaceBundle:GameSearch');
		 	    $match = $repo->findGameSearch($user, $length, $minRank, $maxRank);
			    if (!$match) {
			    	sleep(1);
			    	//recheck
		 	    	$match = $repo->findGameSearch($user, $length, $minRank, $maxRank);
			    	$em->refresh($search);
			    }
		 	    //if not cancelled
			    if ($search) {
			    	//recheck for match
				    if ($match || $search->getMatched()) {
					    if ($match) {
					    	$match = $match[0];
					    	//set opponent's search as matched & add game
					    	$match->setMatched(true);
			    	    	$game = $this->get('game_factory')->createNewGame($length, $user, $match->getSearcher());
			    		    $em->persist($game);
			    		    $match->setGame($game);
				    		$em->flush();
			    		    //$search->setGame($game);
					    	//get game id
					    	$gameID = $game->getId();
					    } else {
					    	//search matched by opponent
					    	//get game id
					    	$gameID = $search->getGame()->getId();
					    }
					    
		    		    //delete own search
		    		    $em->remove($search);
				    	//save
				    	$em->flush();
			    	    //return link to game
		    			return new JsonResponse(array('matched' => true,
		    											'gameURL' => $this->generateUrl('cm_interface_play', 
		    																				array('gameID' => $gameID))));
				    }
				    //report back for retry
					return new JsonResponse(array('matched' => false));
			    } else {
			    	//else ajax aborted 
					return new JsonResponse(array('cancelled' => true));
			    }
	    	}
	    	//get game id
		    $gameID = $search->getGame()->getId();
	    	//delete search
	    	$em->remove($search);
	    	//save
	    	$em->flush();
	    	//return link to game
    		return new JsonResponse(array('matched' => true,
    									'gameURL' => $this->generateUrl('cm_interface_play', array('gameID' => $gameID))));
	    }
	    //only reachable if ajax aborted
		return new JsonResponse(array('cancelled' => true));
    }
    
    public function cancelSearchAction($searchID) {
    	if ($searchID != 0) {
		    $em = $this->getDoctrine()->getManager();
		    $search = $em->getRepository('CMInterfaceBundle:GameSearch')->find($searchID);
	    	$user = $this->getUser();
	    	if ($search->getSearcher() != $user) {
		    	throw new AccessDeniedException('This is not your search!');    		
	    	}
		    //remove search
 		    $em->remove($search);
 		    $em->flush();
		    $cancelled = true;		
    	} else {
    		$cancelled = false;
    	}
	    
    	return new JsonResponse(array('cancelled' => $cancelled));
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
	    //get player colour
    	$colour = $this->getPlayerColour($game, $user);
    	//get opponent
    	if ($colour == 'w') {
    		$op = 1;
    	} else {
    		$op = 0;    		
    	}
    	$opponent = $game->getPlayers()->get($op);

	    return $this->render('CMInterfaceBundle:Game:index.html.twig', 
	    		array('game' => $game, 'player' => $colour, 'opponent' => $opponent));
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
			$colour = 'x';
			//get default pieces
		    $pieces = $this->get('html_helper')->getUnicodePieces();
		} else {
		    $user = $this->getUser();	
	    	$em = $this->getDoctrine()->getManager();
	    	$game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
		    //authenticate user/game
		    $this->checkGameValidity($game, $user);
		    //get player colour
	    	$colour = $this->getPlayerColour($game, $user);
	    	//get game pieces
    		$pieces = $this->get('html_helper')->getUnicodePieces($game->getBoard()->getBoard());
		}

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
	
    public function toggleChatAction($player)
    {
    	return $this->render('CMInterfaceBundle:Game:index.html.twig', array());    	
    }
}
