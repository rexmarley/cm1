<?php
// src/CM/InterfaceBundle/Entity/Pieces/AbstractPiece.php

namespace CM\InterfaceBundle\Entity\Pieces;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\BooleanType;
//use CM\InterfaceBundle\Entity\BoardSquare;

/**
 * @ORM\Entity
 * @ORM\Table(name="piece")
 */
 // @ORM\Entity(repositoryClass="CM\UserBundle\Repository\UserRepository")
abstract class AbstractPiece
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * The piece type
     *
     * @ORM\Column(type="string")
     */
    protected $type;
    
    /**
     * The piece colour
     *
     * @ORM\Column(type="string")
     */
    protected $colour;
    
     /**
      * @ORM\OneToOne(targetEntity="CM\InterfaceBundle\Entity\BoardSquare", inversedBy="piece")
      */
    protected $square;

    /**
     * Has the piece been moved
     *
     * @ORM\Column(type="boolean")
     */
    protected $isMoved = false;

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
     * Set piece as moved
     * 
     * @param Bool $moved
     *
     * @return Bool
     */
    public function setIsMoved($moved) {
    	$this->isMoved = $moved;
    	return $moved;
    }
    
    /**
     * Check if piece has been moved
     *
     * @return Bool
     */
    public function getIsMoved() {
    	return $this->isMoved;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return AbstractPiece
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set colour
     *
     * @param string $colour
     * @return AbstractPiece
     */
    public function setColour($colour)
    {
        $this->colour = $colour;

        return $this;
    }

    /**
     * Get colour
     *
     * @return string 
     */
    public function getColour()
    {
        return $this->colour;
    }

    /**
     * Set current square on board
     *
     * @param \CM\InterfaceBundle\Entity\BoardSquare $square
     * @return AbstractPiece
     */
    public function setSquare(\CM\InterfaceBundle\Entity\BoardSquare $square = null)
    {
        $this->square = $square;

        return $this;
    }

    /**
     * Get current square on board
     *
     * @return \CM\InterfaceBundle\Entity\BoardSquare 
     */
    public function getSquare()
    {
        return $this->square;
    }
}
