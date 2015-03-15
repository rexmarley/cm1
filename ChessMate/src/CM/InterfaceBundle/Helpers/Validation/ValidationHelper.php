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
// 	private $castled = false;
// 	private $enPassantAvailable = false;
// 	private $enPassantPerformed = false;
    private $game;
    private $board;
    //private $unmoved;
	
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
    
    protected function setGlobals($game) {
    	$this->game = $game;
    	$this->board = $game->getBoard()->getBoard();
    	//$this->unmoved = $game->getBoard()->getUnmoved();    	
    }
		
	/**
	 * Validate knight movement
	 * @param from	[y,x]
	 * @param to	[y,x]
	 */
	private function validateKnight($from, $to) {
		if ((($to[0] - $from[0])*($to[0] - $from[0])) + (($to[1] - $from[1])*($to[1] - $from[1])) == 5) {
			return true;
		}
		return false;
	}
	
	/**
	 * Validate bishop movement
	 * @param from	[y,x]
	 * @param to	[y,x]
	 */
	private function validateBishop($from, $to) {
		if ($this->onDiagonal($from, $to) && !$this->diagonalBlocked($from[1], $from[0], $to[1], $to[0])) {
			return true;
		}
		return false;
	}
	
	/**
	 * Validate queen movement
	 * @param from	[y,x]
	 * @param to	[y,x]
	 */
	private function validateQueen($from, $to) {
		if (($from[0] == $to[0] && !$this->xAxisBlocked($from[1], $to[1], $from[0])) 
			|| ($from[1] == $to[1] && !$this->yAxisBlocked($from[0], $to[0], $from[1])) 
			|| ($this->onDiagonal($from, $to) && !$this->diagonalBlocked($from[1], $from[0], $to[1], $to[0]))) {
			return true;
		}	
		return false;
	}

	/**
	 * Validate king movement
	 * @param string $colour
	 * @param array  $from	[y,x]
	 * @param array  $to	[y,x]
	 */
	private function validateKing($colour, $from, $to) {
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
		        	//update abstract board
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
	 * Validate pawn movement
	 * @param string $colour
	 * @param array  $from	[y,x]
	 * @param array  $to	[y,x]
	 */
	private function validatePawn($colour, $from, $to) {
		$spaces = 1;
		if ($this->unmoved[$from[0]][$from[1]]) {
			//allow initial movement of 2 spaces
			$spaces = 2;
		}
		//allow moving forward
		$dir = $to[0] - $from[0];
		$move = abs($dir);
		if ($this->vacant($to[0], $to[1]) && $from[1] == $to[1] && $move <= $spaces) {
			if (($colour == 'w' && $to[0] > $from[0]) || ($colour == 'b' && $to[0] < $from[0])) {
				$this->checkApplyEnPassant($move, $to, $colour);
				return true;
			}
		} else if ($this->onDiagonal($from, $to) && (($colour == 'w' && $dir == 1) || $colour == 'b' && $dir == -1))  {
			if ($this->checkTakePiece($to, $colour)) {
				return true;
			} else if ($this->enPassantAvailable[0] == $from[0] && $this->enPassantAvailable[1] == $to[1]) {
				//perform En passant
				//allow revert if in check
				$epTaken = $this->board[$from[0]][$to[1]];
				$this->board[$from[0]][$to[1]] = false;
	        	//update abstract board
	    		$this->updateAbstractBoard($from, $to);
	    		if ($this->inCheck($colour)) {
					//revert ------------------------->not needed for server side
	            	$this->updateAbstractBoard($to, $from); 
					$this->board[$to[0]][$to[1]] = $epTaken;
					return false;				
				}
				$this->enPassantAvailable = false;
				$this->enPassantPerformed = true;
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if king is in check
	 */
	private function inCheck($colour) {
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
	private function inCheckOnDiagonal($colour, $kingSquare) {
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
	private function inCheckOnXAxis($colour, $kingSquare) {
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
	private function inCheckOnYAxis($colour, $kingSquare) {
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
	private function inCheckByKnight($colour, $kingSquare) {
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
	private function inCheckByPawn($colour, $kingSquare) {
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
	private function pieceAt($row, $column, $piece) {
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
	private function getPieceAt($row, $column) {
		if ($row > -1 && $row < 8 && $column > -1 && $column < 8) {
			return $this->board[$row][$column];
		}
		return false;
	}
	
	/**
	 * Get opponent's colour
	 */
	private function getOpponentColour($colour) {
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
	private function updateAbstractBoard($from, $to) {
		$this->board[$to[0]][$to[1]] = $this->board[$from[0]][$from[1]];
		$this->board[$from[0]][$from[1]] = false;
	}
	
	/**
	 * Check if x-axis squares are blocked
	 * @param from	y1
	 * @param to	y2
	 * @param row
	 */
	private function xAxisBlocked($from, $to, $row) {
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
	private function yAxisBlocked($from, $to, $column) {
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
	private function diagonalBlocked($fromX, $fromY, $toX, $toY) {
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
	private function onDiagonal($from, $to) {
		return abs($to[0] - $from[0]) == abs($to[1] - $from[1]);
	}
	
	/**
	 * Check if target square is unoccupied
	 */
	private function vacant($row, $column) {
		return $this->board[$row][$column] === false;
	}

	/**
	 * Check if target square is occupied by own piece
	 */
	private function occupiedByOwnPiece($row, $column, $colour) {
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
	private function occupiedByOtherPiece($row, $column, $colour) {
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
	private function checkTakePiece($square, $colour) {
		if ($this->occupiedByOtherPiece($square[0], $square[1], $colour)) {
			return true;
		}
		return false;
	}
		
	/**
	 * Check En passant has been performed
	 * @param moved the moved piece's start square
	 */
	private function checkEnPassantPerformed($moved) {
		if ($this->enPassantPerformed) {
			$this->enPassantPerformed = false;
			return true;
		}
		//check En passant time-out
		if ($this->enPassantAvailable != $moved) {
			$this->enPassantAvailable = false;
		}
		return false;		
	}
}
