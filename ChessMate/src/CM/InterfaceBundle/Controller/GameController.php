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
use CM\InterfaceBundle\Entity\ChatMessage;

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
    	
        return $this->render('CMInterfaceBundle:Game:main.html.twig', array('games' => $games, 'player' => 'x'));
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
	    $gameID = false;
	    if ($search) {
	    	$user = $this->getUser();
	    	if ($search->getSearcher() != $user) {
		    	throw new AccessDeniedException('This is not your search!');    		
	    	}
	     	//if user's search matched by opponent
		    if ($search->getMatched()) {
		    	//get game id
		    	while (!$search->getGame()) {
		    		usleep(500000);
		    		$em->refresh($search);
		    	}
		    	$gameID = $search->getGame()->getId();
		    } else {
		    	//find match
		     	$length = $search->getLength();
		     	$minRank = $search->getMinRank();
		     	$maxRank = $search->getMaxRank();
		     	$repo = $em->getRepository('CMInterfaceBundle:GameSearch');
		 	    $match = $repo->findGameSearch($user, $length, $minRank, $maxRank);
		 	    if ($match) {
					$match = $match[0];
			    	//set searches as matched
			    	$match->setMatched(true);
			    	$search->setMatched(true);
			    	$em->flush();
			    	//create game if earlier searcher 
					if ($search->getId() < $match->getId()) {
				    	//if length is not specified - use opponents settings
				    	if (!$length) {
				    		$length = $match->getLength();
				    		//if neither is specified - default 10 mins. each
					    	if (!$length) {
					    		$length = 600;
					    	}
				    	}
		    	    	$game = $this->get('game_factory')->createNewGame($length, $user, $match->getSearcher());
		    	    	$em->persist($game);
		    		    $match->setGame($game);
		    	    	$em->flush();
		    		    //delete own search
		    		    $em->remove($search);
				    	$em->flush();
				    	//get game id
				    	$gameID = $game->getId();
				    	//create log files
				    	//$this->get('file_service')->createLogFiles($gameID);
					}		 	    	
		 	    }
		    }		    
		    if ($gameID) {	
	    	    //return link to game
    			return new JsonResponse(array('matched' => true,
    											'gameURL' => $this->generateUrl('cm_interface_play', 
    																				array('gameID' => $gameID))));
		    }
			//else, report back for retry
			return new JsonResponse(array('matched' => false));
		}
	    //only reachable if search cancelled & ajax aborted
		return new JsonResponse(array('cancelled' => true));
    }
    
    /**
     * Cancel search
     * @param int $searchID
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function cancelSearchAction($searchID) {
    	$cancelled = false;
    	if ($searchID != 0) {
		    $em = $this->getDoctrine()->getManager();
		    $search = $em->getRepository('CMInterfaceBundle:GameSearch')->find($searchID);
		    if ($search) {
		    	$user = $this->getUser();
		    	if ($search->getSearcher() != $user) {
			    	throw new AccessDeniedException('This is not your search!');    		
		    	}
			    //remove search
	 		    $em->remove($search);
	 		    $em->flush();
			    $cancelled = true;
		    }
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
    	//get time left
    	$p1Time = $game->getPlayerTime(0);
    	$p2Time = $game->getPlayerTime(1);
    	//get opponent
    	if ($colour == 'w') {
    		$op = 1;
    		$userTime = $this->getMinutesTimeString($p1Time);
    		$opTime = $this->getMinutesTimeString($p2Time);
    		$pChatty = $game->getPlayerIsChatty(0);
    		$opChatty = $game->getPlayerIsChatty(1);
    	} else {
    		$op = 0;
    		$userTime = $this->getMinutesTimeString($p2Time);
    		$opTime = $this->getMinutesTimeString($p1Time);
    		$pChatty = $game->getPlayerIsChatty(1);
    		$opChatty = $game->getPlayerIsChatty(0);
    	}
    	$opponent = $game->getPlayers()->get($op);
	    //get taken pieces
    	$taken = $this->get('html_helper')->getUnicodeTakenPieces($game->getBoard()->getTaken());

	    return $this->render('CMInterfaceBundle:Game:main.html.twig', 
	    		array('game' => $game,
	    			'player' => $colour, 
	    			'opponent' => $opponent,
	    			'pChatty' => $pChatty,
	    			'opChatty' => $opChatty,
	    			'userTime' => $userTime,
	    			'opTime' => $opTime,
	    			'taken' => $taken)
	    		);
    }
    
    private function getMinutesTimeString($seconds) {
    	$s = $seconds % 60;
    	$minutes = ($seconds - $s) / 60;
    	if ($s < 10) {
    		$s .= '0';
    	}
    	
    	return $minutes.':'.$s;
    }

    /**
     * Join game
     * @param int $gameID
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function joinGameAction($gameID) {
	    $user = $this->getUser();	
    	$em = $this->getDoctrine()->getManager();
    	$game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
	    //authenticate user/game
	    $this->checkGameValidity($game, $user);
	    //join game
	    $game->setPlayerJoined($game->getPlayers()->indexOf($user), true);	    
    	$em->flush();
	    
    	return new JsonResponse(array('joined' => $this->checkJoined($user, $game, $em)));    	
    }
    
    /**
     * Check opponent has joined the game
     * @param User $user
     * @param Game $game
     * @param EntityManager $em
     * @return boolean
     */
    private function checkJoined($user, $game, $em) {
	    //wait for oppponent to join
	    $waited = 0;
	    $em->refresh($game);
	    while (!$game->getJoined() && $waited < 15) {
			sleep(1);
	    	$em->refresh($game);
	    	$waited++;
	    }
	    $joined = $game->getJoined();
	    if ($joined) {
	    	//add to user
	    	$user->addCurrentGame($game);
	    	//set time
	    	$game->setLastMoveTime(time());
	    } else {
	    	//cancel game
	    	$em->remove($game);
	    }
    	//remove searches
    	$em->getRepository('CMInterfaceBundle:GameSearch')->removeGameSearches($game);
	    $em->flush();
	    
    	return $joined;    	
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
	
    /**
     * Resign game
     * @param int $gameID
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function resignAction($gameID)
    {
	    $user = $this->getUser();	
    	$em = $this->getDoctrine()->getManager();
	    $game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
	    //authenticate user/game
	    $this->checkGameValidity($game, $user);
    	//cancel game
    	//$game->setInProgress(false);    	
    	
    	return $this->redirect($this->generateUrl('cm_interface_start', array()));	
    }
	
    /**
     * Offer draw
     * @param int $gameID
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function offerDrawAction($gameID)
    {
    	return $this->render('CMInterfaceBundle:Game:main.html.twig', array());    	
    }
	
    /**
     * Toggle chat
     * @param int $gameID
     * @param int $player
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toggleChatAction($gameID, $player)
    {
	    $user = $this->getUser();
    	
    	$em = $this->getDoctrine()->getManager();
	    $game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
    	if ($player == 'w') {
    		$pIndex = 0;
    	} else {
    		$pIndex = 1;
    	}
    	if ($game->getPlayers()->get($pIndex) != $user) {
    		throw new AccessDeniedException('You may not toggle your opponent\'s chat!');
    	}
    	$game->togglePlayerIsChatty($pIndex);
    	$user->toggleChatty();
    	$em->flush();
    	return new JsonResponse();  
    }
    
    /**
     * Add chat item
     * @param unknown $gameID
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function sendChatAction($gameID, Request $request) {
    	$msg = $request->request->get('msg');
    	$user = $this->getUser();
    	$em = $this->getDoctrine()->getManager();
	    $game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
	    $this->checkGameValidity($game, $user);
	    $chat = new ChatMessage($game, $user, $msg);
	    $em->persist($chat);
    	$em->flush();
    	
    	return new JsonResponse();    	
    }
    
    /**
     * General listener
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listenAction(Request $request) {
    	$content = json_decode($request->getContent());    	
	    $user = $this->getUser();
    	
    	$this->get('session')->save();
    	
    	$em = $this->getDoctrine()->getManager();
	    $gameID = $content->gameID;
	    $game = $em->getRepository('CMInterfaceBundle:Game')->find($gameID);
    	$pIndex = $game->getPlayers()->indexOf($user);
    	$opIndex = 1 - $pIndex;
    	$opponent = $game->getPlayers()->get($opIndex);
	    //wait for game over/new move/draw offered/chat max 20 secs.
	    $waited = 0;
	    $drawOffered = false;
	    $lastSeen = $content->lastChat;
	    if ($game->getPlayerIsChatty($pIndex)) {
	    	$chatMsgs = $em->getRepository('CMInterfaceBundle:ChatMessage')->findGamePlayerChat($opponent, $game, $lastSeen);
	    } else {
	    	$chatMsgs = array();
	    }
	    while (!$game->over() && !$game->newMoveReady($pIndex) && count($chatMsgs) == 0 && !$drawOffered && $waited < 25) {
	    	sleep(1);
	    	$em->refresh($game);
	    	if ($game->getPlayerIsChatty($pIndex)) {
	    		$chatMsgs = $em->getRepository('CMInterfaceBundle:ChatMessage')->findGamePlayerChat($opponent, $game, $lastSeen);
	    	}
	    	$waited++;
	    }
	    $gameOver = $game->over();
	    //allow chat even if game is over
	    $lastSeen += count($chatMsgs);
	    
    	$opChatty = $content->opChatty;
    	$chatToggled = false;
    	if ($game->getPlayerIsChatty($opIndex) != $opChatty) {
    		//chat toggled
    		$chatToggled = true;
    		$opponent = $game->getPlayers()->get($opIndex);
    		if ($opChatty) {
    			$chatMsgs[] = '<br><span class="red">'.$opponent->getUsername().' has disabled chat.</span>';    			
    		} else {
    			$chatMsgs[] = '<br><span class="green">'.$opponent->getUsername().' has enabled chat.</span>';    			
    		}
    	}
    	$chat = array('msgs' => $chatMsgs, 'toggled' => $chatToggled, 'lastSeen' => $lastSeen);
	    //check if game is over
	    if ($gameOver) {
	    	$message = $game->getGameOverMessage($pIndex);
	    	return new JsonResponse(array('change' => true, 'gameOver' => true, 'overMsg' => $message, 'chat' => $chat));
	    } else if ($game->newMoveReady($pIndex)) { 	
			//return opponent's move for validation
			$response = $this->getNewMoveResponse($game->getLastMove());
			$response['chat'] = $chat;
		    return new JsonResponse($response);
	    } else if (count($chatMsgs) > 0) { 	
	    	return new JsonResponse(array('change' => true, 'chat' => $chat));
	    }
    	return new JsonResponse(array('change' => false));   
    }

    /**
     * Get last move details formatted for validation
     * 
     * @param array $move
     * @return array
     */
    private function getNewMoveResponse(array $move) {
    	return array(
    			'change' => true,
    			'gameOver' => false,
		    	'moved' => true,
		    	'from' => $move['from'], 
		    	'to' => $move['to'],
		    	'swapped' => $move['newPiece'],
	    		'enPassant' => $move['enPassant'],
	    		'newBoard' => $move['newBoard']
		    );
    }
}
