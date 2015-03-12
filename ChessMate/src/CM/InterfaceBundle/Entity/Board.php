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
     * @ORM\OneToMany(targetEntity="BoardSquare", mappedBy="board")
     */
    private $squares;

    public function __construct()
    {
        $this->squares = new ArrayCollection();
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
     * Add square
     *
     * @param \CM\InterfaceBundle\Entity\BoardSquare $squares
     * @return Board
     */
    public function addSquare(\CM\InterfaceBundle\Entity\BoardSquare $square)
    {
        $this->squares[] = $square;

        return $this;
    }

    /**
     * Remove square
     *
     * @param \CM\InterfaceBundle\Entity\BoardSquare $squares
     */
    public function removeSquare(\CM\InterfaceBundle\Entity\BoardSquare $square)
    {
        $this->squares->removeElement($square);
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
