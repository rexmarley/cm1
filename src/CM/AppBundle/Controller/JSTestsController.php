<?php

namespace CM\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class JSTestsController extends Controller
{    
    public function testJSAction()
    {    	
        return $this->render('CMAppBundle:JSTests:tests.html.twig', array());
    }
}