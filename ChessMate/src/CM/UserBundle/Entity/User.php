<?php
// src/CM/UserBundle/Entity/User.php

namespace CM\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="cm_user")
 * @ORM\Entity(repositoryClass="CM\UserBundle\Repository\UserRepository")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;    

    /**
     * Current games
     *
     * @var ArrayCollection $currentGames
     *
     * @ORM\ManyToOne(targetEntity="CM\InterfaceBundle\Entity\Game")
     */
    
    /**
     * Is the user register or a guest
     * 
     * @ORM\Column(type="boolean")
     */
    protected $registered;
    
    /**
     * Time of last activity
     *
     * @var \Datetime
     * @ORM\Column(name="last_active_time", type="datetime")
     */
    protected $lastActiveTime;

    public function __construct()
    {
        parent::__construct();
        $this->currentGames = new ArrayCollection();
    }
    
    /**
     * set user as registered or guest
     *
     * @return Bool
     */
    public function setRegistered($reg) {
    	$this->registered = $reg;
    }
    
    /**
     * Check if user is registered or guest
     *
     * @return Bool
     */
    public function getRegistered() {
    	return $this->registered;
    }
    
    /**
     * Set time of last activity
     * 
     * @param \Datetime $activeTime
     */
    public function setLastActiveTime($activeTime)
    {
    	$this->lastActiveTime = $activeTime;
    }
    
    /**
     * Get time of last activity
     * 
     * @return \Datetime
     */
    public function getLastActiveTime()
    {
    	return $this->lastActiveTime;
    }
    
    /**
     * Check if user is online/active
     * 
     * @return Bool
     */
    public function isOnline()
    {    
    	return $this->getLastActiveTime() > new \DateTime('5 minutes ago');
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
}
