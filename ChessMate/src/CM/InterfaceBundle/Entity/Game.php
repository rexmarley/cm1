<?php
// src/CM/InterfaceBundle/Entity/Game.php

namespace CM\InterfaceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="game")
 * @ORM\Entity(repositoryClass="CM\InterfaceBundle\Repository\GameRepository")
 */
class Game
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Board", cascade={"persist"})
     */
    private $board;
    
    /**
     * Players
     *
     * @ORM\ManyToMany(targetEntity="CM\UserBundle\Entity\User", inversedBy="currentGames", cascade={"persist"})
     * @ORM\JoinTable(name="game_players",
     * joinColumns={@ORM\JoinColumn(name="game_id", referencedColumnName="id")},
     * inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")})
     */
    private $players;

    /**
     * The index of the active player
     * 0 = white
     * 1 = black
     * @ORM\Column(type="integer")
     */
    private $activePlayerIndex;
    
    /**
     * @ORM\Column(type="integer")
     */
    private $length;
    
    /**
     * Has the game got both players
     * 
     * @ORM\Column(type="boolean")
     */
    private $joined;
    
    /**
     * @ORM\Column(type="boolean")
     */
    private $p1Chatty;
    
    /**
     * @ORM\Column(type="boolean")
     */
    private $p2Chatty;
    
    private $whiteTimeLeft;
    private $blackTimeLeft;
    
    /**
     * Constructor
     */
    public function __construct($board)
    {
        $this->players = new \Doctrine\Common\Collections\ArrayCollection();
        $this->board = $board;
        $this->joined = false;
    	//set white as active
    	$this->setActivePlayerIndex(0);
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
     * Set length
     *
     * @param integer $length
     * @return Game
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Get length
     *
     * @return integer 
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set game as joined
     *
     * @param boolean $joined
     * @return Game
     */
    public function setJoined($joined)
    {
        $this->joined = $joined;

        return $this;
    }

    /**
     * Check if game has both players
     *
     * @return boolean 
     */
    public function getJoined()
    {
        return $this->joined;
    }

    /**
     * Set board
     *
     * @param \CM\InterfaceBundle\Entity\Board $board
     * @return Game
     */
    public function setBoard(\CM\InterfaceBundle\Entity\Board $board = null)
    {
        $this->board = $board;

        return $this;
    }

    /**
     * Get board
     *
     * @return \CM\InterfaceBundle\Entity\Board 
     */
    public function getBoard()
    {
        return $this->board;
    }

    /**
     * Set whitePlayer
     *
     * @param \CM\UserBundle\Entity\User $whitePlayer
     * @return Game
     */
    public function setWhitePlayer(\CM\UserBundle\Entity\User $whitePlayer = null)
    {
        $this->players->set(0, $whitePlayer);

        return $this;
    }

    /**
     * Get whitePlayer
     *
     * @return \CM\UserBundle\Entity\User 
     */
    public function getWhitePlayer()
    {
        return $this->players->get(0);
    }

    /**
     * Set blackPlayer
     *
     * @param \CM\UserBundle\Entity\User $blackPlayer
     * @return Game
     */
    public function setBlackPlayer(\CM\UserBundle\Entity\User $blackPlayer)
    {
       $this->players->set(1, $blackPlayer);

        return $this;
    }

    /**
     * Get blackPlayer
     *
     * @return \CM\UserBundle\Entity\User 
     */
    public function getBlackPlayer()
    {
        return $this->players->get(1);
    }

    /**
     * Set active player index
     * @param int index
     * 
     * @return Game
     */
    public function setActivePlayerIndex($index)
    {
        if ($index == 0 || $index == 1) {
        	$this->activePlayerIndex = $index;
        }

        return $this;
    }
    

    /**
     * Get active player index
     *
     * @return \CM\UserBundle\Entity\User 
     */
    public function getActivePlayerIndex()
    {
        return $this->activePlayerIndex;
    }

    /**
     * Switch active player
     * 
     * @return Game
     */
    public function switchActivePlayer()
    {
        if ($this->activePlayerIndex == 0) {
        	$this->activePlayerIndex = 1;
        } else {
        	$this->activePlayerIndex = 0;
        }

        return $this;
    }    

    /**
     * Get active player
     *
     * @return \CM\UserBundle\Entity\User 
     */
    public function getActivePlayer()
    {
        return $this->players->get($this->activePlayerIndex);
    }

    /**
     * Add players
     *
     * @param \CM\UserBundle\Entity\User $players
     * @return Game
     */
    public function addPlayer(\CM\UserBundle\Entity\User $player)
    {
        $this->players[] = $player;

        return $this;
    }

    /**
     * Remove players
     *
     * @param \CM\UserBundle\Entity\User $players
     */
    public function removePlayer(\CM\UserBundle\Entity\User $player)
    {
        $this->players->removeElement($player);
    }

    /**
     * Get players
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPlayers()
    {
        return $this->players;
    }

    /**
     * Enable/disable chat for player 1
     *
     * @param boolean $chatty
     * @return Game
     */
    public function setP1Chatty($chatty)
    {
        $this->p1Chatty = $chatty;

        return $this;
    }

    /**
     * Check if player 1 has chat enabled
     *
     * @return boolean 
     */
    public function getP1Chatty()
    {
        return $this->p1Chatty;
    }

    /**
     * Enable/disable chat for player 2
     *
     * @param boolean $chatty
     * @return Game
     */
    public function setP2Chatty($chatty)
    {
        $this->p2Chatty = $chatty;

        return $this;
    }

    /**
     * Check if player 2 has chat enabled
     *
     * @return boolean 
     */
    public function getP2Chatty()
    {
        return $this->p2Chatty;
    }
}
