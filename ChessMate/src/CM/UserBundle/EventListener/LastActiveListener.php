<?php
namespace CM\UserBundle\EventListener;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Model\UserInterface;

class LastActiveListener
{
    protected $securityContext;
    protected $userManager;

    public function __construct(SecurityContext $securityContext, UserManagerInterface  $userManager)
    {
        $this->securityContext = $securityContext;
        $this->userManager = $userManager;
    }

    /**
    * Update user on each request
    */
    public function onCoreController(FilterControllerEvent $event)
    {
        // listen for MASTER_REQUESTs
        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }

        // Check if request is from user
        if ($this->securityContext->getToken()) {
            $user = $this->securityContext->getToken()->getUser();
            //update last active time (every five mins) 
            if ($user instanceof UserInterface && !$user->isOnline()) {
                $user->setLastActiveTime(new \DateTime());
                $this->userManager->updateUser($user);
            }
        }
    }
}