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
    	$board = new Board();
    	
        $game = new Game($board);
        $game->setLength($length);
        $game->addPlayer($whitePlayer);
        $game->addPlayer($blackPlayer);

        return $game;
    }
}
