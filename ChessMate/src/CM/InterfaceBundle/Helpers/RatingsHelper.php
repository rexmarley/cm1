<?php

namespace CM\InterfaceBundle\Helpers;

use CM\UserBundle\Entity\User;

/**
 * Calculate Glicko based ratings
 * @reference http://www.glicko.net/glicko/glicko.pdf
 */
class RatingsHelper
{
	/**
	 * Constant governing uncertainty in rating - 
	 * 60 mths without playing is assumed to make any rating as unreliable as that of an unrated player.
	 * Average games in a month is estimated at 50
	 * 
 	 * Solve:
 	 *  startDeviation = sqrt((averageDeviation*averageDeviation) + (constant*constant*50*60))
 	 *  => 350 = sqrt((50*50)+(c*c*50*60)) 
	 * 
	 * @var int
	 */
	private static $c = 6.32;
	
	/**
	 * Average period length
	 * @var int
	 */
	private static $periodMins = 806;
	
	/**
	 * Adjust rating deviation at start of each game
	 * @param User $player
	 * @return int
	 */
	public function getStartRD(User $player) {
		$pp = time() - $player->getLastPlayedTime();
		$t = round(($pp / $this->periodMins) - 1);
		$oldRD = $player->getRD();
		$rd = min(sqrt(($oldRD*$oldRD)+($this->c*$this->c*$t)), 350);

		return $rd;
	}
	
	/**
	 * Update player's rating & deviation
	 * 
	 * @param User $player
	 * @param array $matches [opRating, opRD, result]
	 * @return User
	 */
	public function updatePlayer(User $player, array $matches) {
		$dSq = $this->getDSq($matches, $player->getRating());
		$player->setRating($this->getNewRating($player, $matches, $dSq));
		$player->setDeviation($this->getNewDeviation($player->getDeviation(), $dSq));
		
		return $player;
	}
	
	/**
	 * Calculate new rating based on results of all games in ratings period
	 * @param User $player
	 * @param array $matches
	 * @param double $dSq
	 * 
	 * @return int
	 */
	private function getNewRating(User $player, array $matches, $dSq) {
		$rd = $player->getDeviation();
		$oldRating = $player->getRating();
		$t1 = $this->getQ()/((1/$rd/$rd)+(1/$dSq));
		$sum = 0;
		foreach ($matches as $match) {
			$sum += $this->getG($match['opRD'])*($match['result'] - $this->getE($oldRating, $match['opRating'], $match['opRD']));
		}
		return round($oldRating + ($t1 * $sum));
	}
	
	/**
	 * Calculate new ratings deviation
	 * For use at start of new rating period
	 * @param double $oldRD
	 * @param double $dSq
	 * 
	 * @return double
	 */
	private function getNewDeviation($oldRD, $dSq) {
		//set minimum RD threshold of 30
		return max(round(sqrt(((1/$oldRD/$oldRD)+(1/$dSq))**-1), 1), 30);
	}
	
	/**
	 * Get q term of equations
	 * @return double
	 */
	private function getQ() {
		return log(10)/400;
	}
	
	/**
	 * Get g term of equations
	 * @return double
	 */
	private function getG($RD) {
		$q = $this->getQ();
		return 1/sqrt(1+(3*$q*$q*$RD*$RD/M_PI/M_PI));
	}

	/**
	 * Get E term of equations
	 * @return double
	 */
	private function getE($pRating, $opRating, $opRD) {
		$pow = -$this->getG($opRD)*($pRating-$opRating)/400;
		return 1/(1+(10**$pow));
	}
	
	/**
	 * Get d squared as a summation of all matches within set ratings period
	 * Implemented on a game by game basis in this instance
	 */
	private function getDSq($matches, $pRating) {
		$sum = 0;
		foreach ($matches as $match) {
			$sum += $this->getMatchDifference($pRating, $match['opRating'], $match['opRD']);
		}
		$q = $this->getQ();
		return ($q*$q*$sum)**-1;
	}
	
	/**
	 * Get difference for single match
	 * @param int $pRating
	 * @param int $opRating
	 * @param double $opRD
	 * 
	 * @return double
	 */
	private function getMatchDifference($pRating, $opRating, $opRD) {
		return $this->getG($opRD)*$this->getG($opRD)*$this->getE($pRating, $opRating, $opRD)*(1-$this->getE($pRating, $opRating, $opRD));
	}
}