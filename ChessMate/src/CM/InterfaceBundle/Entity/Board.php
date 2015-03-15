<?php
// src/CM/InterfaceBundle/Entity/Board.php
namespace CM\InterfaceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="board")
 */
 // @ORM\Entity(repositoryClass="CM\UserBundle\Repository\UserRepository")
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
    protected $unmoved;

    /**
     * The position of a pawn vulnerable to En passant
     * @ORM\Column(type="array", nullable=true)
     */
    protected $enPassantAvailable;

    public function __construct()
    {
        $this->setDefaults();
        //$this->pieces = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set default board & unmoved pieces
     *
     * @return array 
     */
    public function setDefaults()
    {
    	$this->setDefaultBoard();
    	$this->setDefaultUnmoved();
    	$this->enPassantAvailable = null;
    }

    /**
     * set default board
     *
     * @return array 
     */
    public function setDefaultBoard()
    {
        $this->board = array(
    		array('w_rook','w_knight','w_bishop','w_queen','w_king','w_bishop','w_knight','w_rook'),
    		array('w_pawn','w_pawn','w_pawn','w_pawn','w_pawn','w_pawn','w_pawn','w_pawn'),
    		array(false, false, false, false, false, false, false, false),
    		array(false, false, false, false, false, false, false, false),
    		array(false, false, false, false, false, false, false, false),
    		array(false, false, false, false, false, false, false, false),
    		array('b_pawn','b_pawn','b_pawn','b_pawn','b_pawn','b_pawn','b_pawn','b_pawn'),
    		array('b_rook','b_knight','b_bishop','b_queen','b_king','b_bishop','b_knight','b_rook')
    	);
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
     * Set unmoved
     *
     * @param array $unmoved
     * @return Board
     */
    public function setUnmoved(array $unmoved)
    {
        $this->unmoved = $unmoved;

        return $this;
    }

    /**
     * set default unmoved
     *
     * @return array 
     */
    public function setDefaultUnmoved()
    {
        $this->unmoved = array(
    			array(true, true, true, true, true, true, true, true),
    			array(true, true, true, true, true, true, true, true),
    			array(false, false, false, false, false, false, false, false),
    			array(false, false, false, false, false, false, false, false),
    			array(false, false, false, false, false, false, false, false),
    			array(false, false, false, false, false, false, false, false),
    			array(true, true, true, true, true, true, true, true),
    			array(true, true, true, true, true, true, true, true)
    	);
    }

    /**
     * Get unmoved
     *
     * @return array 
     */
    public function getUnmoved()
    {
        return $this->unmoved;
    }
    
    /**
     * Mark piece as moved
     * @param int $row
     * @param int $column
     */
    public function setPieceAsMoved($row, $column) {
    	$this->unmoved[$row][$column] = false;
    }
    
    /**
     * Check if piece is moved
     * @param int $row
     * @param int $column
     */
    public function getPieceIsMoved($row, $column) {
    	return !$this->unmoved[$row][$column];
    }

    /**
     * Update board
     *
     * @param array $board
     * @return Board
     */
    public function updateBoard(array $from, array $to)
    {
        $this->board[$to[0]][$to[1]] = $this->board[$from[0]][$from[1]];
        $this->board[$from[0]][$from[1]] = false;

        return $this;
    }

    /**
     * Set indices for a piece vulnerable to En passant
     *
     * @param array|null $pawnPosition The vulnerable pawn's position
     * @return Board
     */
    public function setEnPassantAvailable($pawnPosition)
    {
        $this->enPassantAvailable = $pawnPosition;

        return $this;
    }

    /**
     * Get indices for a piece vulnerable to En passant
     *
     * @return null if En passant is unavailable
     */
    public function getEnPassantAvailable()
    {
        return $this->enPassantAvailable;
    }
}
