<?php

namespace CM\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{    
    public function playAction()
    {    	
    	$user = $this->getUser();
    	if ($user) {
    		$name = $user->getUsername();
    	} else {
    		$name = 'Guest';
    	}
    	
        return $this->render('CMInterfaceBundle:Default:index.html.twig', array('name' => $name));
    }
}
