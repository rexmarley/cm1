<?php
// src/CM/UserBundle/Entity/User.php

namespace CM\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\Common\Collections\ArrayCollection;
use CM\InterfaceBundle\Entity\BoardSquare;

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
     * @ORM\ManyToMany(targetEntity="CM\InterfaceBundle\Entity\Game", mappedBy="players")
     */
    protected $currentGames;
    
    /**
     * @ORM\OneToMany(targetEntity="CM\InterfaceBundle\Entity\BoardSquare", mappedBy="board")
     */
    private $squares;
    
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
     * Add currentGames
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
     * Remove currentGames
     *
     * @param \CM\InterfaceBundle\Entity\Game $currentGames
     */
    public function removeCurrentGame(\CM\InterfaceBundle\Entity\Game $currentGame)
    {
        $this->currentGames->removeElement($currentGame);
    }

    /**
     * Get currentGames
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCurrentGames()
    {
        return $this->currentGames;
    }

    /**
     * Add squares
     *
     * @param \CM\UserBundle\Entity\BoardSquare $squares
     * @return User
     */
    public function addSquare(BoardSquare $squares)
    {
        $this->squares[] = $squares;

        return $this;
    }

    /**
     * Remove squares
     *
     * @param \CM\UserBundle\Entity\BoardSquare $squares
     */
    public function removeSquare(BoardSquare $squares)
    {
        $this->squares->removeElement($squares);
    }

    /**
     * Get squares
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSquares()
    {
        return $this->squares;
    }
}
