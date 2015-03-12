<?php
// src/CM/InterfaceBundle/Entity/BoardSquare.php

namespace CM\InterfaceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="board_square")
 */
 // @ORM\Entity(repositoryClass="CM\UserBundle\Repository\UserRepository")
class BoardSquare
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * The game board
     *
     * @ORM\ManyToOne(targetEntity="Board", inversedBy="squares")
     */
    protected $board;
    
    /**
     * The square's row
     * 
     * @ORM\Column(type="integer")
     */
    protected $row;
    
    /**
     * The square's column
     * 
     * @ORM\Column(type="integer")
     */
    protected $column;
    
    /**
     * The occupying piece, if any
     *
     * @ORM\OneToOne(targetEntity="CM\InterfaceBundle\Entity\Pieces\AbstractPiece", mappedBy="square")
     */
    protected $piece;
    
    /**
     * Set row position on board
     * 
     * @param Integer $row
     */
    public function setRow($row)
    {
    	$this->row = $row;
    }
    
    /**
     * Get row position on board
     * 
     * @return \Integer
     */
    public function getRow()
    {
    	return $this->row;
    }
    
    /**
     * Set column position on board
     * 
     * @param Integer $column
     */
    public function setColumn($column)
    {
    	$this->column = $column;
    }
    
    /**
     * Get column position on board
     * 
     * @return \Datetime
     */
    public function getColumn()
    {
    	return $this->column;
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
     * @param \CM\InterfaceBundle\Entity\Board $board
     * @return BoardSquare
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
     * Set piece
     *
     * @param \CM\InterfaceBundle\Entity\Pieces\AbstractPiece $piece
     * @return BoardSquare
     */
    public function setPiece(\CM\InterfaceBundle\Entity\Pieces\AbstractPiece $piece = null)
    {
        $this->piece = $piece;

        return $this;
    }

    /**
     * Get piece
     *
     * @return \CM\InterfaceBundle\Entity\Pieces\AbstractPiece 
     */
    public function getPiece()
    {
        return $this->piece;
    }
}
