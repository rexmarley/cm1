<?php
// src/CM/InterfaceBundle/Entity/Pieces/Pawn.php

namespace CM\InterfaceBundle\Entity\Pieces;
use Doctrine\ORM\Mapping as ORM;

class Pawn extends AbstractPiece
{
	protected $enPassantAvailable;
		
	public function __construct($colour)
	{
		$this->type = 'pawn';
		$this->colour = $colour;
		$this->isMoved = false;
	}
}
