<?php

namespace CM\AppBundle\Helpers\Validation;

use CM\AppBundle\Entity\Game;

/**
 * Move validator
 */
abstract class ValidationHelper
{
    protected $game;
    protected $board;
    protected $enPassant = null;
    protected $pieceSwapped = false;
    protected $checkThreat = null;
	
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
    	$colour = $move['pColour'];
    	if (($this->board[$move['from'][0]][$move['from'][1]] != $colour.'_'.$move['pType'])
    		|| ($this->board[$move['to'][0]][$move['to'][1]] && $this->board[$move['to'][0]][$move['to'][1]][0] == $colour) ) {
    		return array('valid' => false);
    	}
    	//validate piece
    	$valid = $this->validatePiece($move);
    	if($valid) {
    		//move piece
    		if (!$this->pieceSwapped) {
    			$this->updateAbstractBoard($move['from'], $move['to']);
    		}
			//get opponent colour
			$opColour = $this->getOpponentColour($colour);
    		//if in check, invalidate move
    		if ($this->inCheck($opColour, $this->getKingSquare($colour))) {
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
     * Get array indices for given colour king's square
     * @param char $colour w/b
     * @return array [y,x]
     */
    protected function getKingSquare($colour) {    	
		$king = $colour.'_king';
		//get king's position
		for ($row = 0; $row < 8; $row++) {
			$col = array_search($king, $this->board[$row]);
			if ($col !== false) {
				return array($row, $col);
			}
		}    	
    }

	/**
	 * Check if king is in check
	 * @param char $opColour The threatening player
	 * @param array $kingSquare The threatened square
	 */
	protected function inCheck($opColour, $kingSquare) {
		//check in check
		return ($this->inCheckByPawn($opColour, $kingSquare) 
				|| $this->inCheckByKnight($opColour, $kingSquare)
				|| $this->inCheckOnXAxis($opColour, $kingSquare)
				|| $this->inCheckOnYAxis($opColour, $kingSquare)
				|| $this->inCheckOnDiagonal($opColour, $kingSquare));
	}

	/**
	 * Check if in check on diagonal
	 */
	protected function inCheckOnDiagonal($opColour, $kingSquare) {
		$row = $kingSquare[0];
		$col = $kingSquare[1];
		$blocks = [false,false,false,false];
		$bishop = $opColour.'_bishop';
		$queen = $opColour.'_queen';
		for ($i = 1; $i < 8; $i++) {
			$threats = [
				$this->getPieceAt($row+$i, $col-$i), 
				$this->getPieceAt($row+$i, $col+$i), 
				$this->getPieceAt($row-$i, $col-$i), 
				$this->getPieceAt($row-$i, $col+$i)
					
			];
			if (!$blocks[0] && ($threats[0] == $bishop || $threats[0] == $queen)) {
				$this->checkThreat = array($row+$i, $col-$i);
				return true;
			} 
			if (!$blocks[1] && ($threats[1] == $bishop || $threats[1] == $queen)) {
				$this->checkThreat = array($row+$i, $col+$i);
				return true;
			}
			if (!$blocks[2] && ($threats[2] == $bishop || $threats[2] == $queen)) {
				$this->checkThreat = array($row-$i, $col-$i);
				return true;
			}
			if (!$blocks[3] && ($threats[3] == $bishop || $threats[3] == $queen)) {
				$this->checkThreat = array($row-$i, $col+$i);
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
	protected function inCheckOnXAxis($opColour, $kingSquare) {
		$row = $kingSquare[0];
		$queen = $opColour.'_queen';
		$rook = $opColour.'_rook';
		//radiate out (for checkmates)
		for ($col = $kingSquare[1]-1; $col >= 0; $col--) {
			if ($this->board[$row][$col] == $rook || $this->board[$row][$col] == $queen) {
				if (!$this->xAxisBlocked($kingSquare[1], $col, $row)) {
					$this->checkThreat = array($row, $col);
					return true;
				}				
			}
		}
		for ($col = $kingSquare[1]+1; $col < 8; $col++) {
			if ($this->board[$row][$col] == $rook || $this->board[$row][$col] == $queen) {
				if (!$this->xAxisBlocked($kingSquare[1], $col, $row)) {
					$this->checkThreat = array($row, $col);
					return true;
				}				
			}
		}
		return false;
	}
	
	/**
	 * Check if in check on y-axis
	 */
	protected function inCheckOnYAxis($opColour, $kingSquare) {
		$col = $kingSquare[1];
		$queen = $opColour.'_queen';
		$rook = $opColour.'_rook';
		//radiate out
		for ($row = $kingSquare[0]-1; $row >= 0; $row--) {
			if ($this->board[$row][$col] == $rook || $this->board[$row][$col] == $queen) {
				if (!$this->yAxisBlocked($kingSquare[0], $row, $col)) {
					$this->checkThreat = array($row, $col);
					return true;
				}				
			}
		}
		for ($row = $kingSquare[0]+1; $row < 8; $row++) {
			if ($this->board[$row][$col] == $rook || $this->board[$row][$col] == $queen) {
				if (!$this->yAxisBlocked($kingSquare[0], $row, $col)) {
					$this->checkThreat = array($row, $col);
					return true;
				}				
			}
		}
		return false;
	}
	
	/**
	 * Check if king is in check by knight
	 */
	protected function inCheckByKnight($opColour, $kingSquare) {
		$x = $kingSquare[1];
		$y = $kingSquare[0];
		if ($this->pieceAt($y+2, $x-1, $opColour.'_knight')) {
			$this->checkThreat = array($y+2, $x-1);
			return true;			
		}
		if ($this->pieceAt($y+2, $x+1, $opColour.'_knight')) {
			$this->checkThreat = array($y+2, $x+1);
			return true;			
		}
		if ($this->pieceAt($y+1, $x-2, $opColour.'_knight')) {
			$this->checkThreat = array($y+1, $x-2);
			return true;			
		}
		if ($this->pieceAt($y+1, $x+2, $opColour.'_knight')) {
			$this->checkThreat = array($y+1, $x+2);
			return true;			
		}
		if ($this->pieceAt($y-1, $x-2, $opColour.'_knight')) {
			$this->checkThreat = array($y-1, $x-2);
			return true;			
		}
		if ($this->pieceAt($y-1, $x+2, $opColour.'_knight')) {
			$this->checkThreat = array($y-1, $x+2);
			return true;			
		}
		if ($this->pieceAt($y-2, $x-1, $opColour.'_knight')) {
			$this->checkThreat = array($y-2, $x-1);
			return true;			
		}
		if ($this->pieceAt($y-2, $x+1, $opColour.'_knight')) {
			$this->checkThreat = array($y-2, $x+1);
			return true;			
		}
		return false;
	}
	
	/**
	 * Check if king is in check by pawn
	 */
	protected function inCheckByPawn($opColour, $kingSquare) {
		$dir = 1;
		if ($opColour == 'w') {
			$dir = -1;
		}
		if ($this->pieceAt($kingSquare[0]+$dir, $kingSquare[1]-1, $opColour.'_pawn')) {
			$this->checkThreat = array($kingSquare[0]+$dir, $kingSquare[1]-1);
			return true;			
		}
		if ($this->pieceAt($kingSquare[0]+$dir, $kingSquare[1]+1, $opColour.'_pawn')) {
			$this->checkThreat = array($kingSquare[0]+$dir, $kingSquare[1]+1);
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
	 * @param from	x1
	 * @param to	x2
	 * @param row	y
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
	 * @param from		y1
	 * @param to		y2
	 * @param column	x
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
