<?php

namespace CM\InterfaceBundle\Helpers;

use CM\InterfaceBundle\Entity\Game;
use CM\UserBundle\Entity\User;
use CM\InterfaceBundle\Entity\Board;
/**
 * Chess game factory
 */
class ValidationHelper
{

	/**
	 * Validate chess move
	 *
	 * @param array $from
	 * @param array $to
	 * @param string $type
	 * @param string $colour
	 * @param Game $game
	 *
	 * @return Game
	 */
    public static function validateMove(array $from, array $to, $type, $colour, Game $game)
    {
    	//allow abstract validation
    	$board = $game->getBoard()->getBoard();
    	//include redundant middle board to avoid resolving indices
    	$unmoved = $game->getBoard()->getUnmoved();
    	//temp
    	//return array('valid' => false, 'board' => $board, 'unmoved' => $unmoved);
    	//check piece type/colour matches origin
    	if ($board[$from[0]][$from[1]] != $colour.'_'.$type) {
    		return array('valid' => false, 'board' => $board, 'unmoved' => $unmoved);
    	}
    	//check target square is not occupied by own piece
    	if ($board[$to[0]][$to[1]] && $board[$to[0]][$to[1]][0] == $colour) {
    		return array('valid' => false, 'board' => $board, 'unmoved' => $unmoved);
    	}
    	return array('valid' => true, 'board' => $board);//don't need board/unmoved
    	 
    }
    
    private function validatePawn()
    {
    	 
    }
}
