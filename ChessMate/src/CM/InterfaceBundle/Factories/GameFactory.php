<?php

namespace CM\InterfaceBundle\Factories;

use CM\InterfaceBundle\Entity\Game;
use CM\UserBundle\Entity\User;
use CM\InterfaceBundle\Entity\Board;
/**
 * Chess game factory
 */
class GameFactory
{
    /**
     * Create new game of chess
     *
     * @param int $length the length of game
     * @param int $whitePlayer player 1
     * @param int $blackPlayer player 2
     *
     * @return Game
     */
    public function createNewGame($length, User $whitePlayer, User $blackPlayer = null)
    {
    	//create board
    	$board = new Board();    	
        $game = new Game($board);
        
        $game->setLength($length);
        $game->addPlayer($whitePlayer);
        if (!is_null($blackPlayer)) {
        	$game->addPlayer($blackPlayer);
        	$game->setInProgress(true);
        }

        return $game;
    }
}
