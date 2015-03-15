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
	//private $castled = false;
	//private $enPassantAvailable = null;
	
	/**
	 * Validate chess move
	 *
	 * @param array $move [from, to, pieceType, pieceColour]
	 * @param Game $game The game
	 *
	 * @return Game
	 */
    public function validateMove(array $move, Game $game)
    {
    	$this->setGlobals($game);
    	//check piece type/colour matches origin
    	//and target square is not occupied by own piece
    	if (($this->board[$move['from'][0]][$move['from'][1]] != $move['pColour'].'_'.$move['pType'])
    		|| ($this->board[$move['to'][0]][$move['to'][1]] && $this->board[$move['to'][0]][$move['to'][1]][0] == $move['pColour']) ) {
    		return array('valid' => false);
    	}    	
    	//validate piece
    	$valid = $this->validatePiece($move);
    	if($valid) {
    		//move piece
    		$this->updateAbstractBoard($move['from'], $move['to']);
    		//if in check, invalidate move
    		if ($this->inCheck($move['pColour'])) {
    			return array('valid' => false);
    		}
    		//mark piece as moved
    		$this->board->setPieceAsMoved($move['from'][0], $move['from'][1]);
    		//remove any lingering En passant
    		$this->board->setEnPassantAvailable(null);
    		return array('valid' => true, 'board' => $this->board); //return $game?
    	}

    	return array('valid' => false);
    }
		
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
		//allow moving forward
		$dir = $to[0] - $from[0];
		$moved = abs($dir);   			
    	//check for pawn reaching opposing end
    	if (($colour == 'w' && $to[0] == 7) || ($colour == 'b' && $to[0] == 0)) {
    		$this->board[$to[0]][$to[1]] = $move['newPiece'];
    	}
		if ($this->vacant($to[0], $to[1]) && $from[1] == $to[1] && $moved <= $spaces) {
			if (($colour == 'w' && $to[0] > $from[0]) || ($colour == 'b' && $to[0] < $from[0])) {
				$this->checkApplyEnPassant($moved, $to, $colour);
				return true;
			}
		} else if ($this->onDiagonal($from, $to) && (($colour == 'w' && $dir == 1) || $colour == 'b' && $dir == -1))  {
			//check/perform En passant
    		$enPassantAvailable = $this->board->getEnPassantAvailable();
			if ($enPassantAvailable[0] == $from[0] && $enPassantAvailable[1] == $to[1]) {
				//take pawn
				$this->board[$from[0]][$to[1]] = false;
				$this->board->setEnPassantAvailable(null);
			}
			//allow diagonal take
			return true;
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
