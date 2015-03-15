<?php

namespace CM\InterfaceBundle\Helpers\Validation;

use CM\InterfaceBundle\Entity\Game;
use CM\UserBundle\Entity\User;
use CM\InterfaceBundle\Entity\Board;
/**
 * Pawn validator
 */
class PawnValidator extends ValidationHelper
{		
	/**
	 * Validate pawn movement
	 * @param array  $move
	 */
	private function validatePiece($move) {
    	$from = $move['from'];
    	$to = $move['to'];
    	$colour = $move['pColour'];
		$spaces = 1;
		if ($this->unmoved[$from[0]][$from[1]]) {
			//allow initial movement of 2 spaces
			$spaces = 2;
		}
		$valid = false;
		//allow moving forward
		$dir = $to[0] - $from[0];
		$moved = abs($dir);
		if ($this->vacant($to[0], $to[1]) && $from[1] == $to[1] && $moved <= $spaces) {
			if (($colour == 'w' && $to[0] > $from[0]) || ($colour == 'b' && $to[0] < $from[0])) {
				$this->checkApplyEnPassant($moved, $to, $colour);
				$valid = true;
			}
		} else if ($this->onDiagonal($from, $to) && (($colour == 'w' && $dir == 1) || $colour == 'b' && $dir == -1))  {
    		$enPassantAvailable = $this->board->getEnPassantAvailable();
    		if (checkTakePiece($to, $colour)) {
				//allow diagonal take
				$valid = true;    			
    		}
			//check/perform En passant
			else if ($enPassantAvailable[0] == $from[0] && $enPassantAvailable[1] == $to[1]) {
				//take pawn
				$this->board[$from[0]][$to[1]] = false;
				$this->board->setEnPassantAvailable(null);
				$valid = true;    
			}
		}
		if ($valid) {
			//check for pawn reaching opposing end
			if (($colour == 'w' && $to[0] == 7) || ($colour == 'b' && $to[0] == 0)) {
				$this->board[$to[0]][$to[1]] = $move['newPiece'];
			}
		}
		return $valid;
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
}
