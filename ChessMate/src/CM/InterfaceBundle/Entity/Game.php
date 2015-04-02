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
     * @ORM\Column(type="array")
     */
    private $playersJoined;

    /**
     * @ORM\Column(type="array")
     */
    //protected $chatLog;
    
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
     * 2 = draw
     * @ORM\Column(type="integer", nullable=true)
     */
    private $victorIndex;

    /**
     * Game over message
     * @ORM\Column(type="string", nullable=true)
     */
    private $gameOverMessage;
    
    /**
     * Constructor
     */
    public function __construct($board, $length)
    {
        $this->players = new \Doctrine\Common\Collections\ArrayCollection();
        $this->board = $board;
        $this->playersJoined = array(false,false);
        $this->playerTimes = array($length, $length);
    	//set white as active
    	$this->setActivePlayerIndex(0);
        $this->lastMoveValidated = true;
        $this->chatLog = array();
    	//set last move by no-one to prevent superfluous check
        $this->lastMove = array('by'=> 2);
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
     * Set player has joined game
     *
     * @param int $player index
     * @param bool $joined
     * @return Game
     */
    public function setPlayerJoined($player, $joined)
    {
        $this->playersJoined[$player] = $joined;

        return $this;
    }

    /**
     * Check if player has joined
     * @param int $player index
     *
     * @return boolean
     */
    public function getPlayerJoined($player)
    {
        return $this->playersJoined[$player];
    }

    /**
     * Check if both players have joined
     *
     * @return boolean 
     */
    public function getJoined()
    {
        return ($this->playersJoined[0] && $this->playersJoined[1]);
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
        return 1 - $this->activePlayerIndex;        
    }

    /**
     * Switch active player
     * 
     * @return Game
     */
    public function switchActivePlayer()
    {
        $this->activePlayerIndex = 1 - $this->activePlayerIndex;

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
     * @param array $move[by<playerIndex>, from[y,x], to[y,x], newBoard, enPassantAvailable, newPiece]
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
     * Get index of player that made last move
     *
     * @return array 
     */
    public function getLastMoveBy()
    {
        return $this->lastMove['by'];
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
     * Check if new move is ready
     * @param int $player index
     * 
     * @return boolean
     */
    public function newMoveReady($player) {
    	return $this->getLastMoveBy() != $player && !$this->getLastMoveValidated();
    }
    
    /**
     * Check if game is over
     * @return boolean
     */
    public function over() {
    	return !is_null($this->victorIndex);
    }
    
    /**
     * Set game over message
     * 
     * @return Game
     */
    public function setGameOverMessage($message) {
    	$this->gameOverMessage = $message;
    	return $this;
    }
    
    /**
     * Get game over message
     * @param int $player index
     * 
     * @return string
     */
    public function getGameOverMessage($player) {
    	return $this->gameOverMessage;
    }

    /**
     * Set victor's index, if game over
     * @param int index
     * 
     * @return Game
     */
    public function setVictorIndex($index)
    {
        $this->victorIndex = $index;

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
}
