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
     * Has player 1 joined game
     * 
     * @ORM\Column(type="boolean")
     */
    private $p1Joined;
    
    /**
     * Has player 2 joined game
     * 
     * @ORM\Column(type="boolean")
     */
    private $p2Joined;
    
    /**
     * @ORM\Column(type="boolean")
     */
    private $p1Chatty;
    
    /**
     * @ORM\Column(type="boolean")
     */
    private $p2Chatty;
    
    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $lastMoveTime;
    
//     /**
//      * @ORM\Column(type="integer")
//      */
//     private $p1Time;
    
//     /**
//      * @ORM\Column(type="integer")
//      */
//     private $p2Time;
    
    /**
     * @ORM\Column(type="array")
     */
    private $playerTimes;
    
    /**
     * Constructor
     */
    public function __construct($board, $length)
    {
        $this->players = new \Doctrine\Common\Collections\ArrayCollection();
        $this->board = $board;
        $this->p1Joined = false;
        $this->p2Joined = false;
//         $this->p1Time = $length;
//         $this->p2Time = $length;
        $this->playerTimes = array($length, $length);
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
     * Set player 1 as joined
     *
     * @param boolean $joined
     * @return Game
     */
    public function setP1Joined($joined)
    {
        $this->p1Joined = $joined;

        return $this;
    }

    /**
     * Check if player 1 has joined
     *
     * @return boolean 
     */
    public function getP1Joined()
    {
        return $this->p1Joined;
    }

    /**
     * Set player 2 as joined
     *
     * @param boolean $joined
     * @return Game
     */
    public function setP2Joined($joined)
    {
        $this->p2Joined = $joined;

        return $this;
    }

    /**
     * Check if player 2 has joined
     *
     * @return boolean 
     */
    public function getP2Joined()
    {
        return $this->p2Joined;
    }

    /**
     * Check if both players have joined
     *
     * @return boolean 
     */
    public function getJoined()
    {
        return ($this->p1Joined && $this->p2Joined);
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
     * Set last move time
     *
     * @param integer $time
     * @return Game
     */
    public function setLastMoveTime($time)
    {
        $this->lastMoveTime = $time;

        return $this;
    }

    /**
     * Get last move time
     *
     * @return integer 
     */
    public function getLastMoveTime()
    {
        return $this->lastMoveTime;
    }

    /**
     * Set time left for player
     *
     * @param int $player index
     * @param int $timeLeft seconds
     * @return Game
     */
    public function setPlayerTime($player, $timeLeft)
    {
        $this->playerTimes[$player] = $timeLeft;

        return $this;
    }

    /**
     * Get time left for player
     *
     * @return int 
     */
    public function getPlayerTime($player)
    {
        return $this->playerTimes[$player];
    }

//     /**
//      * Set time left for player 1
//      *
//      * @param int $timeLeft
//      * @return Game
//      */
//     public function setP1Time($timeLeft)
//     {
//         $this->p1Time = $timeLeft;

//         return $this;
//     }

//     /**
//      * Get time left for player 1
//      *
//      * @return int 
//      */
//     public function getP1Time()
//     {
//         return $this->p1Time;
//     }

//     /**
//      * Set time left for player 2
//      *
//      * @param int $timeLeft
//      * @return Game
//      */
//     public function setP2Time($timeLeft)
//     {
//         $this->p2Time = $timeLeft;

//         return $this;
//     }

//     /**
//      * Get time left for player 2
//      *
//      * @return int 
//      */
//     public function getP2Time()
//     {
//         return $this->p2Time;
//     }

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
