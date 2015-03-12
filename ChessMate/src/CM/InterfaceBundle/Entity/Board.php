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
     * Add squares
     *
     * @param \CM\InterfaceBundle\Entity\BoardSquare $squares
     * @return Board
     */
    public function addSquare(\CM\InterfaceBundle\Entity\BoardSquare $squares)
    {
        $this->squares[] = $squares;

        return $this;
    }

    /**
     * Remove squares
     *
     * @param \CM\InterfaceBundle\Entity\BoardSquare $squares
     */
    public function removeSquare(\CM\InterfaceBundle\Entity\BoardSquare $squares)
    {
        $this->squares->removeElement($squares);
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
