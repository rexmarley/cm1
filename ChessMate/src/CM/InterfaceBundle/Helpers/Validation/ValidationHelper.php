<?php

namespace CM\InterfaceBundle\Helpers\Validation;

use CM\InterfaceBundle\Entity\Game;
use CM\UserBundle\Entity\User;
use CM\InterfaceBundle\Entity\Board;

/**
 * Move validator
 */
abstract class ValidationHelper
{
    protected $game;
    protected $board;
    protected $enPassant = null;
    protected $pieceSwapped = false;
	
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
    		if (!$this->pieceSwapped) {
    			$this->updateAbstractBoard($move['from'], $move['to']);
    		}
    		//if in check, invalidate move
    		if ($this->inCheck($move['pColour'])) {
    			return array('valid' => false);
    		}
    		//mark piece as moved
    		$this->game->getBoard()->setPieceAsMoved($move['from'][0], $move['from'][1]);
    		//add/remove En passant
    		$this->game->getBoard()->setEnPassantAvailable($this->enPassant);
    		//flag pawn as swapped/reset
    		$this->game->getBoard()->setPawnSwapped($this->pieceSwapped);
    		return array('valid' => true, 'board' => $this->board);
    	}

    	return array('valid' => false);
    }
    
    /**
     * Overridden function
     * @return boolean
     */
    protected function validatePiece($move) {
    	return false; 
    }
    
    protected function setGlobals($game) {
    	$this->game = $game;
    	$this->board = $game->getBoard()->getBoard();
    	//$this->unmoved = $game->getBoard()->getUnmoved();    	
    }

	/**
	 * Check if king is in check
	 */
	protected function inCheck($colour) {
		$king = $colour.'_king';
		//get opponent colour
		$colour = $this->getOpponentColour($colour);
		//get king's position
		$kingSquare = 0;
		for ($row = 0; $row < 8; $row++) {
			$col = array_search($king, $this->board[$row]);
			if ($col !== false) {
				$kingSquare = [$row, $col];
				break;
			}
		}
		//check in check
		if ($this->inCheckByPawn($colour, $kingSquare)) {
			return true;
		} else if ($this->inCheckByKnight($colour, $kingSquare)) {
			return true;
		} else if ($this->inCheckOnXAxis($colour, $kingSquare)) {
			return true;
		} else if ($this->inCheckOnYAxis($colour, $kingSquare)) {
			return true;
		} else if ($this->inCheckOnDiagonal($colour, $kingSquare)) {
			return true;
		}
		
		return false;
	}

	/**
	 * Check if in check on diagonal
	 */
	protected function inCheckOnDiagonal($colour, $kingSquare) {
		$row = $kingSquare[0];
		$col = $kingSquare[1];
		$blocks = [false,false,false,false];
		for ($i = 1; $i < 8; $i++) {
			$threats = [
				$this->getPieceAt($row+$i, $col-$i), 
				$this->getPieceAt($row+$i, $col+$i), 
				$this->getPieceAt($row-$i, $col-$i), 
				$this->getPieceAt($row-$i, $col+$i)
					
			];
			if ((!$blocks[0] && ($threats[0] == $colour.'_bishop' || $threats[0] == $colour.'_queen'))
				|| (!$blocks[1] && ($threats[1] == $colour.'_bishop' || $threats[1] == $colour.'_queen'))
				|| (!$blocks[2] && ($threats[2] == $colour.'_bishop' || $threats[2] == $colour.'_queen'))
				|| (!$blocks[3] && ($threats[3] == $colour.'_bishop' || $threats[3] == $colour.'_queen'))
				) {
				return true;
			}
			//get blocking pieces
			for ($j = 0; $j < 4; $j++) {
				if (!$blocks[$j]) {
					$blocks[$j] = $threats[$j];					
				}
			}
		}
		return false;
	}

	/**
	 * Check if in check on x-axis
	 */
	protected function inCheckOnXAxis($colour, $kingSquare) {
		$row = $kingSquare[0];
		for ($col = 0; $col < 8; $col++) {
			if ($this->board[$row][$col] == $colour.'_rook' || $this->board[$row][$col] == $colour.'_queen') {
				if (($col + 1) == $kingSquare[1] || ($col - 1) == $kingSquare[1] || !$this->xAxisBlocked($kingSquare[1], $col, $row)) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Check if in check on y-axis
	 */
	protected function inCheckOnYAxis($colour, $kingSquare) {
		$col = $kingSquare[1];
		for ($row = 0; $row < 8; $row++) {
			if ($this->board[$row][$col] == $colour.'_rook' || $this->board[$row][$col] == $colour.'_queen') {
				if (($row + 1) == $kingSquare[0] || ($row - 1) == $kingSquare[0] || !$this->yAxisBlocked($kingSquare[0], $row, $col)) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Check if king is in check by knight
	 */
	protected function inCheckByKnight($colour, $kingSquare) {
		if ($this->pieceAt($kingSquare[0]+2, $kingSquare[1]-1, $colour.'_knight')
			|| $this->pieceAt($kingSquare[0]+2, $kingSquare[1]+1, $colour.'_knight')
			|| $this->pieceAt($kingSquare[0]+1, $kingSquare[1]-2, $colour.'_knight')
			|| $this->pieceAt($kingSquare[0]+1, $kingSquare[1]+2, $colour.'_knight')
			|| $this->pieceAt($kingSquare[0]-1, $kingSquare[1]-2, $colour.'_knight')
			|| $this->pieceAt($kingSquare[0]-1, $kingSquare[1]+2, $colour.'_knight')
			|| $this->pieceAt($kingSquare[0]-2, $kingSquare[1]-1, $colour.'_knight')
			|| $this->pieceAt($kingSquare[0]-2, $kingSquare[1]+1, $colour.'_knight')) {
			return true;
		}
		return false;
	}
	
	/**
	 * Check if king is in check by pawn
	 */
	protected function inCheckByPawn($colour, $kingSquare) {
		$dir = 1;
		if ($colour == 'w') {
			$dir = -1;
		}
		if ($this->pieceAt($kingSquare[0]+$dir, $kingSquare[1]-1, $colour.'_pawn')
			|| $this->pieceAt($kingSquare[0]+$dir, $kingSquare[1]+1, $colour.'_pawn')) {
			return true;
		}
		return false;
	}

	/**
	 * Check given piece is at given square
	 */
	protected function pieceAt($row, $column, $piece) {
		if ($row > -1 && $row < 8 && $column > -1 && $column < 8) {
			if ($this->board[$row][$column] == $piece) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Get piece/false at given square
	 */
	protected function getPieceAt($row, $column) {
		if ($row > -1 && $row < 8 && $column > -1 && $column < 8) {
			return $this->board[$row][$column];
		}
		return false;
	}
	
	/**
	 * Get opponent's colour
	 */
	protected function getOpponentColour($colour) {
		if ($colour == 'w') {
			$colour = 'b';
		} else {
			$colour = 'w';			
		}
		return $colour;
	}
	
	/**
	 * Update abstract board,
	 * handles taking automatically
	 * @param from	[y,x]
	 * @param to	[y,x]
	 */
	protected function updateAbstractBoard($from, $to) {
		$this->board[$to[0]][$to[1]] = $this->board[$from[0]][$from[1]];
		$this->board[$from[0]][$from[1]] = false;
	}
	
	/**
	 * Check if x-axis squares are blocked
	 * @param from	y1
	 * @param to	y2
	 * @param row
	 */
	protected function xAxisBlocked($from, $to, $row) {
		//get x-axis direction
		$range = abs($to - $from);
		$x = ($to - $from) / $range;
		//check squares inbetween are empty
		for ($i = 1; $i < $range; $i++) {
			if($this->board[$row][$from + ($i*$x)]) {
				return true;
			}
		}
	
		return false;
	}
	
	/**
	 * Check if y-axis squares are blocked
	 * @param from	x1
	 * @param to	x2
	 * @param column
	 */
	protected function yAxisBlocked($from, $to, $column) {
		//get y-axis direction
		$range = abs($to - $from);
		$y = ($to - $from) / $range;
		//check squares inbetween are empty
		for ($i = 1; $i < $range; $i++) {
			if($this->board[$from + ($i*$y)][$column]) {
				return true;
			}
		}
	
		return false;
	}

	/**
	 * Check if diagonal squares are blocked
	 */
	protected function diagonalBlocked($fromX, $fromY, $toX, $toY) {
		$range = abs($fromX - $toX);
		//get x-axis direction
		$xDir = ($toX - $fromX) / $range;
		//get y-axis direction
		$yDir = ($toY - $fromY) / $range;
		//check squares inbetween are empty
		for ($i = 1; $i < $range; $i++) {
			if($this->board[$fromY + ($i*$yDir)][$fromX + ($i*$xDir)]) {
				return true;
			}
		}
	
		return false;
	}
	
	/**
	 * check if target square is diagonal with source
	 * @param from	[y,x]
	 * @param to	[y,x]
	 * 
	 * @return Boolean
	 */
	protected function onDiagonal($from, $to) {
		return abs($to[0] - $from[0]) == abs($to[1] - $from[1]);
	}
	
	/**
	 * Check if target square is unoccupied
	 */
	protected function vacant($row, $column) {
		return $this->board[$row][$column] === false;
	}

	/**
	 * Check if target square is occupied by own piece
	 */
	protected function occupiedByOwnPiece($row, $column, $colour) {
		if ($row > -1 && $row < 8 && $column > -1 && $column < 8) {
			if (!$this->vacant($row, $column) && $this->board[$row][$column][0] == $colour) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Check if target square is occupied by other piece
	 */
	protected function occupiedByOtherPiece($row, $column, $colour) {
		if ($row > -1 && $row < 8 && $column > -1 && $column < 8) {
			if (!$this->vacant($row, $column) && $this->board[$row][$column][0] != $colour) {
				return true;
			}
		}
		
		return false;
	}
		
	/**
	 * Check for takeable piece
	 */
	protected function checkTakePiece($square, $colour) {
		if ($this->occupiedByOtherPiece($square[0], $square[1], $colour)) {
			return true;
		}
		return false;
	}
}
