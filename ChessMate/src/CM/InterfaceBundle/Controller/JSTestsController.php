<?php

namespace CM\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class JSTestsController extends Controller
{    
    public function testJSAction()
    {    	
        return $this->render('CMInterfaceBundle:JSTests:testBoard.html.twig', array());
    }
}