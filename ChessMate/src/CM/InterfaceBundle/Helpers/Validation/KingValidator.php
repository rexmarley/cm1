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
	 * @param array  $move
	 */
	private function validatePiece($move) {
    	$from = $move['from'];
    	$to = $move['to'];
    	$colour = $move['pColour'];
		if (abs($to[1] - $from[1]) <= 1 && abs($to[0] - $from[0]) <= 1) {
			return true;
		} else if ($this->unmoved[$from[0]][$from[1]] && $to[0] == $from[0] && !inCheck($colour)) {
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
				if ($this->unmoved[$from[0]][$rookFromCol]) {
					//check intermittent points are vacant
					for ($i = $start; $i < $end; $i++) {
						if (!vacant($from[0], $i)) {
							return false;
						}
						// if in check at intermittent points, return false
						$nextSpace = [$from[0], $i];
			    		$this->updateAbstractBoard($from, $nextSpace);
			    		if (inCheck($colour)) {
							//put king back in place
				    		$this->updateAbstractBoard($nextSpace, $from);
			    			return false;
			    		}
						//put king back in place
			    		$this->updateAbstractBoard($nextSpace, $from);
					}
		        	//move rook
		    		$this->updateAbstractBoard([$from[0], $rookFromCol], [$to[0], $rookToCol]);
		    		//set rook as moved - not actually necessary
					//unmoved[from[0]][rookFromCol] = false;
					//flag castled - prevent recheck of inCheck()
					$this->castled = true;
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Mark a piece as vulnerable to En passant
	 */
	private function checkApplyEnPassant($move, $to, $colour) {
		if ($move == 2) {
			//get opponent's colour
			$colour = $this->getOpponentColour($colour);
			//look left/right - TODO: out of bounds?
			if ($this->board[$to[0]][$to[1]-1] == $colour.'_pawn' || $this->board[$to[0]][$to[1]+1] == $colour.'_pawn') {
				$this->board->setEnPassantAvailable($to);
			}
		}
	}
		
// 	/**
// 	 * Check En passant has been performed
// 	 * @param moved the moved piece's start square
// 	 */
// 	private function checkEnPassantPerformed($moved) {
// 		if ($this->enPassantPerformed) {
// 			$this->enPassantPerformed = false;
// 			return true;
// 		}
// 		//check En passant time-out
// 		if ($this->enPassantAvailable != $moved) {
// 			$this->enPassantAvailable = false;
// 		}
// 		return false;		
// 	}
}
