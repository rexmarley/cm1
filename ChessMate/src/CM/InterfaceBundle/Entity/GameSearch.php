<?php
// src/CM/InterfaceBundle/Entity/GameSearch.php

namespace CM\InterfaceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use CM\UserBundle\Entity\User;
use CM\InterfaceBundle\Entity\Game;

/**
 * @ORM\Entity
 * @ORM\Table(name="game_search")
 * @ORM\Entity(repositoryClass="CM\InterfaceBundle\Repository\GameSearchRepository")
 */
class GameSearch
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $minRank;

    /**
     * @ORM\Column(type="integer")
     */
    private $maxRank;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $length;
    
    /**
     * Search initiator
     *
     * @ORM\OneToOne(targetEntity="CM\UserBundle\Entity\User")
     */
    private $player1;
    
    /**
     * @ORM\Column(type="boolean")
     */
    private $matched;

    /**
     * @ORM\OneToOne(targetEntity="Game")
	 * @ORM\JoinColumn(name="game_id", referencedColumnName="id", nullable=true)
     */
    private $game;
    
    /**
     * Constructor
     */
    public function __construct($length, $minRank, $maxRank)
    {
    	$this->length = $length;
    	$this->minRank = $minRank;
    	$this->maxRank = $maxRank;
    	$this->matched = false;
    	$this->game = null;
    }

    /**
     * Set minimum rank
     *
     * @param integer $minRank
     * @return GameSearch
     */
    public function setMinRank($minRank)
    {
        $this->minRank = $minRank;

        return $this;
    }

    /**
     * Get minimum rank
     *
     * @return integer 
     */
    public function getMinRank()
    {
        return $this->minRank;
    }

    /**
     * Set maximum rank
     *
     * @param integer $maxRank
     * @return GameSearch
     */
    public function setMaxRank($maxRank)
    {
        $this->maxRank = $maxRank;

        return $this;
    }

    /**
     * Get maximum rank
     *
     * @return integer 
     */
    public function getMaxRank()
    {
        return $this->maxRank;
    }

    /**
     * Set length
     *
     * @param integer $length
     * @return GameSearch
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
     * Set search initiator
     *
     * @param User $player1
     * @return GameSearch
     */
    public function setPlayer1(User $player1)
    {
        $this->player1 = $player1;

        return $this;
    }

    /**
     * Get search initiator
     *
     * @return User 
     */
    public function getPlayer1()
    {
        return $this->player1;
    }

    /**
     * Set search matched
     *
     * @param boolean $matched
     * @return GameSearch
     */
    public function setMatched($matched)
    {
    	$this->matched = $matched;
    
    	return $this;
    }
    
    /**
     * Check if search is matched
     *
     * @return boolean
     */
    public function getMatched()
    {
    	return $this->matched;
    }

    /**
     * Set game
     *
     * @param Game $game
     * @return GameSearch
     */
    public function setGame(Game $game)
    {
    	$this->game = $game;
    
    	return $this;
    }
    
    /**
     * Get game
     *
     * @return Game
     */
    public function getGame()
    {
    	return $this->game;
    }
}