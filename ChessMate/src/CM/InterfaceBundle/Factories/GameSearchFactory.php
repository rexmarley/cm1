<?php

namespace CM\InterfaceBundle\Factories;

use CM\InterfaceBundle\Entity\GameSearch;
use CM\UserBundle\Entity\User;

/**
 * Chess game search factory
 */
class GameSearchFactory
{
	/**
	 * Create new game search
	 * @param User $player
	 * @param int $duration
	 * @param int $skill
	 * @return \CM\InterfaceBundle\Entity\GameSearch
	 */
    public function createNewSearch(User $player, $duration = null, $skill = null)
    {  	
    	if (is_null($skill)) {
    		//match any
    		$minRank = 0;
    		$maxRank = 3000;
    	} else if ($skill == 1) {
    		$playerRank = $player->getRating();
			//best match
			$minRank = $playerRank - 100;
			$maxRank = $playerRank + 100;
		} else if ($skill == 2) {
			//lesser skill
			$maxRank = $playerRank;
    		$minRank = 0;
		} else {
			//greater skill
			$minRank = $playerRank;
    		$maxRank = 3000;		
		}
        $search = new GameSearch($duration, $minRank, $maxRank);
        $search->setPlayer1($player);

        return $search;
    }
}
