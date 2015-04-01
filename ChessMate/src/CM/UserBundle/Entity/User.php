<?php
// src/CM/UserBundle/Entity/User.php

namespace CM\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\Common\Collections\ArrayCollection;
use CM\InterfaceBundle\Entity\GameSearch;

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
     * @ORM\ManyToMany(targetEntity="CM\InterfaceBundle\Entity\Game", inversedBy="players")
     */
    protected $currentGames;
    
    /**
     * Is the user register or a guest
     * 
     * @ORM\Column(type="boolean")
     */
    protected $registered;
    
    /**
     * Glicko2 rating
     * 
     * @ORM\Column(type="integer")
     */
    protected $rating;
    
    /**
     * Time of last activity
     *
     * @var \Datetime
     * @ORM\Column(name="last_active_time", type="datetime")
     */
    protected $lastActiveTime;
    
    /**
     * Is chat enabled (default true, but maintains last value if user changes)
     * 
     * @ORM\Column(type="boolean")
     */
    protected $chatty;

    public function __construct()
    {
        parent::__construct();
        $this->currentGames = new ArrayCollection();
        $this->rating = 1500;
        $this->chatty = true;
    }
    
    /**
     * Set user as registered or guest
     *
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
     * Set user rating
     */
    public function setRating($rating) {
    	$this->rating = $rating;
    }
    
    /**
     * Get user rating
     *
     * @return Integer
     */
    public function getRating() {
    	return $this->rating;
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
    public function getIsOnline()
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

    /**
     * Add game to user
     *
     * @param \CM\InterfaceBundle\Entity\Game $currentGames
     * @return User
     */
    public function addCurrentGame(\CM\InterfaceBundle\Entity\Game $currentGame)
    {
        $this->currentGames[] = $currentGame;

        return $this;
    }

    /**
     * Remove game
     *
     * @param \CM\InterfaceBundle\Entity\Game $currentGames
     */
    public function removeCurrentGame(\CM\InterfaceBundle\Entity\Game $currentGame)
    {
        $this->currentGames->removeElement($currentGame);
    }

    /**
     * Get current games
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCurrentGames()
    {
        return $this->currentGames;
    }
    
    /**
     * Enable/Disable chat
     * @param Bool $chatty
     */
    public function setChatty($chatty) {
    	$this->chatty = $chatty;
    }
    
    /**
     * Check if user has chat enabled
     *
     * @return Bool
     */
    public function getChatty() {
    	return $this->chatty;
    }
    
    /**
     * Toggle chat for player
     * @param int $player
     */
    public function toggleChatty() {
    	if ($this->chatty) {
    		$this->chatty = false;
    	} else {
    		$this->chatty = true;
    	}
    }
}
