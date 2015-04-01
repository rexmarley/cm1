<?php

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
     * @ORM\Column(type="array")
     */
    protected $chatLog;
    
    /**
     * @ORM\Column(type="array")
     */
    private $chattyPlayers;
    
    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $lastMoveTime;

    /**
     * @ORM\Column(type="array")
     */
    protected $lastMove;
    
    /**
     * @ORM\Column(type="boolean")
     */
    private $lastMoveValidated;
    
    /**
     * @ORM\Column(type="array")
     */
    private $playerTimes;

    /**
     * Cheater's index; null if none
     * 0 = white
     * 1 = black
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cheaterIndex;

    /**
     * Victor's index; null if none
     * 0 = white
     * 1 = black
     * @ORM\Column(type="integer", nullable=true)
     */
    private $victorIndex;
    
    /**
     * Constructor
     */
    public function __construct($board, $length)
    {
        $this->players = new \Doctrine\Common\Collections\ArrayCollection();
        $this->board = $board;
        $this->p1Joined = false;
        $this->p2Joined = false;
        $this->playerTimes = array($length, $length);
    	//set white as active
    	$this->setActivePlayerIndex(0);
        $this->lastMove = array();
        $this->lastMoveValidated = true;
        $this->chatLog = array();
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
     * Get inactive player index
     *
     * @return \CM\UserBundle\Entity\User 
     */
    public function getInactivePlayerIndex()
    {
        return $this->activePlayerIndex - ($this->activePlayerIndex * 2) + 1;        
    }

    /**
     * Switch active player
     * 
     * @return Game
     */
    public function switchActivePlayer()
    {
        $this->activePlayerIndex = $this->activePlayerIndex - ($this->activePlayerIndex * 2) + 1;

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

    /**
     * Set chat log
     *
     * @param array $log
     * @return Game
     */
    public function setChatLog($log)
    {
        $this->chatLog = $log;

        return $this;
    }

    /**
     * Get chat log
     *
     * @return array 
     */
    public function getChatLog()
    {
        return $this->chatLog;
    }

    /**
     * Add chat item
     *
     * @param string $item
     * @return Game
     */
    public function addChatItem($item)
    {
        $this->chatLog[] = $item;

        return $this;
    }

    /**
     * Enable/disable chat for player
     *
     * @param int $player index
     * @param bool $chatty
     * @return Game
     */
    public function setPlayerIsChatty($player, $chatty)
    {
        $this->chattyPlayers[$player] = $chatty;

        return $this;
    }

    /**
     * Check if player has chat enabled
     *
     * @return bool 
     */
    public function getPlayerIsChatty($player)
    {
        return $this->chattyPlayers[$player];
    }
    
    /**
     * Toggle chat for player
     * @param int $player
     */
    public function togglePlayerIsChatty($player) {
    	if ($this->chattyPlayers[$player]) {
    		$this->chattyPlayers[$player] = false;
    	} else {
    		$this->chattyPlayers[$player] = true;
    	}
    }

    /**
     * Set last move, for validation
     *
     * @param array $move[from[y,x], to[y,x], newBoard, enPassantAvailable, newPiece]
     * 
     * @return Game
     */
    public function setLastMove(array $move)
    {
        $this->lastMove = $move;

        return $this;
    }

    /**
     * Get last move, for validation
     *
     * @return array 
     */
    public function getLastMove()
    {
        return $this->lastMove;
    }

    /**
     * Set last move is validated
     *
     * @param boolean $validated
     * @return Game
     */
    public function setLastMoveValidated($validated)
    {
       $this->lastMoveValidated = $validated;

        return $this;
    }

    /**
     * Check if last move is validated
     *
     * @return boolean 
     */
    public function getLastMoveValidated()
    {
        return $this->lastMoveValidated;
    }

    /**
     * Set cheater's index, if move not valid
     * @param int index
     * 
     * @return Game
     */
    public function setCheaterIndex($index)
    {
        if ($index == 0 || $index == 1) {
        	$this->cheaterIndex = $index;
        }

        return $this;
    }
    
    /**
     * Get cheater's index
     *
     * @return \CM\UserBundle\Entity\User 
     */
    public function getCheaterIndex()
    {
        return $this->cheaterIndex;
    }

    /**
     * Set victor's index, if game over
     * @param int index
     * 
     * @return Game
     */
    public function setVictorIndex($index)
    {
        if ($index == 0 || $index == 1) {
        	$this->victorIndex = $index;
        }

        return $this;
    }
    
    /**
     * Get victor's index
     *
     * @return \CM\UserBundle\Entity\User 
     */
    public function getVictorIndex()
    {
        return $this->victorIndex;
    }
}
