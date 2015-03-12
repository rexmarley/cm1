<?php

namespace CM\InterfaceBundle\Factories;

use CM\InterfaceBundle\Entity\Game;
use CM\UserBundle\Entity\User;
use CM\InterfaceBundle\Entity\Board;
use CM\InterfaceBundle\Entity\BoardSquare;
use CM\InterfaceBundle\Entity\Pieces\Rook;
use CM\InterfaceBundle\Entity\Pieces\Pawn;
use CM\InterfaceBundle\Entity\Pieces\King;
use CM\InterfaceBundle\Entity\Pieces\Queen;
use CM\InterfaceBundle\Entity\Pieces\Bishop;
use CM\InterfaceBundle\Entity\Pieces\Knight;
/**
 * Chess game factory
 */
class GameFactory
{
    /**
     * Create new game of chess
     *
     * @param int $length the length of game
     * @param int $length the length of game
     * @param int $length the length of game
     *
     * @return Game
     */
    public function createNewGame($length, User $whitePlayer, User $blackPlayer)
    {
    	//create board
    	$board = $this->getNewBoard();
    	
        $game = new Game();
        $game->setLength($length);
        $game->addPlayer($whitePlayer);
        $game->addPlayer($blackPlayer);
        $game->setBoard($board);

        return $game;
    }
    
    private function getNewBoard() {
    	$board = new Board();
    	for ($i = 0; $i < 8; $i++) {
	    	for ($j = 0; $j < 8; $j++) {
	    		$square = new BoardSquare();
	    		$square->setBoard($board);
	    		$square->setRow($i);
	    		$square->setColumn($j);
		    	if ($i < 2 || $i > 5) {
		    		$piece = $this->getPiece($i, $j);
		    		$piece->setSquare($square);
	    			$square->setPiece($piece);
		    	}
	    		$board->addSquare($square);
	    	}    		
    	}
    	return $board;    	
    }
    
    private function getPiece($row, $column) {
    	$colour = 'w';
    	if ($row > 5) {
    		$colour = 'b';
    	}
    	if ($row == 1 || $row == 6) {
    		return new Pawn($colour);
    	}
    	$piece = null;
    	if ($column == 4) {
    		$piece = new King($colour);
    	} else if ($column == 3) {
    		$piece = new Queen($colour);
    	} else if ($column == 2 || $column == 5) {
    		$piece = new Bishop($colour);    		
    	} else if ($column == 1 || $column == 6) {
    		$piece = new Knight($colour);    		
    	} else {
    		$piece = new Rook($colour);
    	}
    	return $piece;    	
    }
}
