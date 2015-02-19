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
		$user = $userManager->findUserBy(array('registered' => 0));
		if (!$user) {
			$name = "Guest 00" . rand(1,1000);
			$user = $userManager->createUser();
			$user->setUsername($name);		
			$user->setEmail("");
			$user->setPassword("");
			$user->setRegistered(false);
			$userManager->updateUser($user);
		} else {
			$name = $user->getUsername();
		}
		//set login token
		$token = new UsernamePasswordToken($user, $user->getPassword(), "main", $user->getRoles());
		$this->get("security.context")->setToken($token);		
		// fire login
		$event = new InteractiveLoginEvent($this->get("request"), $token);
		$this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
    	
        return $this->render('CMInterfaceBundle:Default:index.html.twig', array('name' => $name));
    }
}
