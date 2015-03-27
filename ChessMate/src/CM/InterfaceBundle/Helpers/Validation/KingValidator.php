<?php

namespace CM\InterfaceBundle\Helpers\Validation;

use CM\InterfaceBundle\Entity\Game;
use CM\UserBundle\Entity\User;
use CM\InterfaceBundle\Entity\Board;
/**
 * King validator
 */
class KingValidator extends ValidationHelper
{
	/**
	 * Validate king movement
	 * @param array $move
	 */
	protected function validatePiece($move) {
    	$from = $move['from'];
    	$to = $move['to'];
    	$colour = $move['pColour'];
		if (abs($to[1] - $from[1]) <= 1 && abs($to[0] - $from[0]) <= 1) {
			return true;
		} else if (!$this->game->getBoard()->getPieceIsMoved($from[0], $from[1]) && $to[0] == $from[0] && !$this->inCheck($colour)) {
			//handle castling
			if ($to[1] == 2 || $to[1] == 6) {
				$rookFromCol = 0;
				$start = 1;
				$end = 4;
				$rookToCol = 3;
				if ($to[1] == 6) {
					$rookFromCol = 7;
					$start = 5;
					$end = 7;
					$rookToCol = 5;
				}
				//check castle is unmoved
				if (!$this->game->getBoard()->getPieceIsMoved($from[0], $rookFromCol)) {
					//check intermittent points are vacant
					for ($i = $start; $i < $end; $i++) {
						if (!$this->vacant($from[0], $i)) {
							return false;
						}
						// if in check at intermittent points, return false
						$nextSpace = [$from[0], $i];
			    		$this->updateAbstractBoard($from, $nextSpace);
			    		if ($this->inCheck($colour)) {
							//put king back in place
				    		$this->updateAbstractBoard($nextSpace, $from);
			    			return false;
			    		}
						//put king back in place
			    		$this->updateAbstractBoard($nextSpace, $from);
					}
		        	//move rook
		    		$this->updateAbstractBoard([$from[0], $rookFromCol], [$to[0], $rookToCol]);
					return true;
				}
			}
		}
		return false;
	}
}
