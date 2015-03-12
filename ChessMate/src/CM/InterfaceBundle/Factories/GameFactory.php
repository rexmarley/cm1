<?php

namespace CM\InterfaceBundle\Factory;

use CM\InterfaceBundle\Entity\Game;
use CM\UserBundle\Entity\User;
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
        $game = new Game();

        return $game;
    }
}
