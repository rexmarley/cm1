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
     * @param User $whitePlayer player 1
     * @param User $blackPlayer player 2
     *
     * @return Game
     */
    public function createNewGame($length, User $whitePlayer, User $blackPlayer)
    {
    	//create board
    	$board = new Board();    	
        $game = new Game($board, $length);
        
        $game->addPlayer($whitePlayer);
        $game->setP1Chatty($whitePlayer->getChatty());
        $game->addPlayer($blackPlayer);
       	$game->setP2Chatty($blackPlayer->getChatty());

        return $game;
    }
}
