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
    	
        return $this->render('CMInterfaceBundle:Default:index.html.twig', array('name' => $name));
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
