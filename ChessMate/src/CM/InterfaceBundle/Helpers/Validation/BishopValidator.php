<?php

namespace CM\InterfaceBundle\Helpers\Validation;

use CM\InterfaceBundle\Entity\Game;
use CM\UserBundle\Entity\User;
use CM\InterfaceBundle\Entity\Board;

/**
 * Bishop validator
 */
class BishopValidator extends ValidationHelper
{		
	/**
	 * Validate bishop movement
	 * @param array  $move
	 */
	protected function validatePiece($move) {
    	$from = $move['from'];
    	$to = $move['to'];
		if ($this->onDiagonal($from, $to) && !$this->diagonalBlocked($from[1], $from[0], $to[1], $to[0])) {
			return true;
		}
		return false;
	}
}
