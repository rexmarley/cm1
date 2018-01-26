<?php
namespace CM\UserBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Model\UserInterface;

class LastActiveListener
{
    protected $securityToken;
    protected $userManager;

    public function __construct(UserManagerInterface  $userManager)
    {
        $this->userManager = $userManager;
        $this->securityToken = $this->get("security.token_storage")->getToken();	
    }

    /**
    * Update user on each request
    */
    public function onCoreController(FilterControllerEvent $event)
    {
        // only listen for MASTER_REQUESTs
        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }

        // Check if request is from user
        if ($this->securityToken) {
            $user = $this->securityToken->getUser();
            //update last active time (every three mins) 
            if ($user instanceof UserInterface && !$user->getIsOnline()) {
                $user->setLastActiveTime(new \DateTime());
                $this->userManager->updateUser($user);
            }
        }
    }
}