<?php

namespace CM\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="board")
 */
class Board
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="array")
     */
    protected $board;

     /**
      * @ORM\Column(type="array")
      */
     protected $castling;
    
    /**
     * Has pawn been swapped
     * 
     * @ORM\Column(type="boolean")
     */
    private $pawnSwapped;

    /**
     * A position vulnerable to En passant
     * @ORM\Column(type="array", nullable=true)
     */
    protected $enPassant;

    /**
     * @ORM\Column(type="array")
     */
    protected $takenPieces;

    public function __construct()
    {
        $this->setDefaults();
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
     * Set default board & unmoved pieces
     *
     * @return array 
     */
    public function setDefaults()
    {
    	$this->setDefaultBoard();
    	$this->castling = array('KQ','kq');
    	$this->setDefaultTaken();
    	$this->enPassant = null;
    	$this->pawnSwapped = false;
    }

    /**
     * set default board
     *
     * @return array 
     */
    private function setDefaultBoard()
    {
        $this->board = array(
				array('R','N','B','Q','K','B','N','R'),
	    		array('P','P','P','P','P','P','P','P'),
	    		array(false, false, false, false, false, false, false, false),
	    		array(false, false, false, false, false, false, false, false),
	    		array(false, false, false, false, false, false, false, false),
	    		array(false, false, false, false, false, false, false, false),
	    		array('p','p','p','p','p','p','p','p'),
	    		array('r','n','b','q','k','b','n','r')
    	);
    }
    
    private function setDefaultTaken() {
    	$this->takenPieces = array(
    			'P' => 0, 'R' => 0, 'N' => 0, 'B' => 0, 'Q' => 0,
    			'p' => 0, 'r' => 0, 'n' => 0, 'b' => 0, 'q' => 0
    	);    	
    }

    /**
     * Set board
     *
     * @param array $board
     * @return Board
     */
    public function setBoard(array $board)
    {
        $this->board = $board;

        return $this;
    }

    /**
     * Get board
     *
     * @return array 
     */
    public function getBoard()
    {
        return $this->board;
    }

    /**
     * Set castling options for player
     *
     * @param string $castling
     * @param int $pIndex
     * @return Board
     */
    public function setPlayerCastling($castling, $pIndex)
    {
        $this->castling[$pIndex] = $castling;

        return $this;
    }

    /**
     * Get castling options for player
     *
     * @return array 
     */
    public function getPlayerCastling($pIndex)
    {
        return $this->castling[$pIndex];
    }

    /**
     * Set castling options for both players
     *
     * @param array $castling
     * @return Board
     */
    public function setCastling($castling)
    {
        $this->castling[0] = $castling['w'];
        $this->castling[1] = $castling['b'];

        return $this;
    }
    
    public function getCastling() {
    	return array('w' => $this->castling[0], 'b' => $this->castling[1]);
    }

    /**
     * Set taken pieces
     *
     * @param array $taken
     * @return Board
     */
    public function setTaken(array $taken)
    {
        $this->takenPieces = $taken;

        return $this;
    }

    /**
     * Get taken pieces
     *
     * @return array 
     */
    public function getTaken()
    {
        return $this->takenPieces;
    }

    /**
     * Add taken piece
     *
     * @param string $taken
     * @return Board
     */
    public function addTaken($taken)
    {
        $this->takenPieces[$taken]++;

        return $this;
    }

    /**
     * Update board
     *
     * @param array $from	[y,x]
     * @param array $to		[y,x]
     * @return Board
     */
    public function updateBoard(array $from, array $to)
    {
    	if ($this->board[$to[0]][$to[1]]) {
    		//keep track of taken pieces for reloads
    		$this->taken[] = $this->board[$to[0]][$to[1]];
    	}
    	//move piece
        $this->board[$to[0]][$to[1]] = $this->board[$from[0]][$from[1]];
        $this->board[$from[0]][$from[1]] = false;

        return $this;
    }

    /**
     * Set Piece
     *
     * @param array $square [y,x]
     * @param string $piece
     * @return Board
     */
    public function setPiece(array $square, $piece)
    {
        $this->board[$square[0]][$square[1]] = $piece;

        return $this;
    }

    /**
     * Flag piece as swapped
     *
     * @param boolean $pawnSwapped
     * @return Game
     */
    public function setPawnSwapped($swapped)
    {
        $this->pawnSwapped = $swapped;

        return $this;
    }

    /**
     * Check if piece has swapped
     *
     * @return boolean 
     */
    public function getPawnSwapped()
    {
        return $this->pawnSwapped;
    }

    /**
     * Set indices for a piece vulnerable to En passant
     *
     * @param array|null $position 1 row behind vulnerable pawn
     * @return Board
     */
    public function setEnPassant($position)
    {
        $this->enPassant = $position;

        return $this;
    }

    /**
     * Get indices vulnerable to En passant
     *
     * @return null if En passant is unavailable
     */
    public function getEnPassant()
    {
        return $this->enPassant;
    }
}
