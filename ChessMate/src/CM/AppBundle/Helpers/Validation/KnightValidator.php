<?php

namespace CM\AppBundle\Helpers\Validation;

use CM\AppBundle\Entity\Game;
use CM\UserBundle\Entity\User;
use CM\AppBundle\Entity\Board;

/**
 * Knight validator
 */
class KnightValidator extends ValidationHelper
{		
	/**
	 * Validate knight movement
	 * @param array  $move
	 */
	protected function validatePiece($move) {
    	$from = $move['from'];
    	$to = $move['to'];
		if ((($to[0] - $from[0])*($to[0] - $from[0])) + (($to[1] - $from[1])*($to[1] - $from[1])) == 5) {
			return true;
		}
		return false;
	}
}
