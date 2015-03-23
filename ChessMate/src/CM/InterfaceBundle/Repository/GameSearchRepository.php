<?php

namespace CM\InterfaceBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CM\UserBundle\Entity\User;

/**
 * GameSearchRepository
 */
class GameSearchRepository extends EntityRepository
{
	/**
	 * Find matching search
	 * @param User $player
	 * @param int $length
	 * @param int $minRank
	 * @param int $maxRank
	 *
	 * @return array
	 */
	public function findGameSearch(User $player, $length, $minRank, $maxRank)
	{
		//get search parameters
		$playerRank = $player->getRating();
		$queryParams = array('playerID' => $player->getId(), 
							'playerRank' => $playerRank,
							'minRank' => $minRank,
							'maxRank' => $maxRank );
		$lengthTerm = '';
		if (!is_null($length)) {
			$queryParams['length'] = $length;
			$lengthTerm .= ' AND (gs.length = :length OR gs.length IS NULL)';
		}
		//find game
		$search = $this->getEntityManager()
		->createQuery(
				'SELECT gs 
				FROM CMInterfaceBundle:GameSearch gs
			    JOIN gs.searcher p
				WHERE p.id != :playerID
				AND gs.matched = 0
				AND gs.cancelled = 0
				AND :playerRank >= gs.minRank
				AND :playerRank <= gs.maxRank
				AND p.rating >= :minRank 
				AND p.rating <= :maxRank
				'.$lengthTerm
		)
		->setMaxResults(1)
		->setParameters($queryParams)
		->getResult();
		
		return $search;
	}
	
// 	/**
// 	 * Find matching search
// 	 * @param User $player
// 	 * @param int $length
// 	 * @param int $skill
// 	 *
// 	 * @return array
// 	 */
// 	public function findGameSearch(User $player, $length = null, $skill = null)
// 	{
// 		//get search parameters
// 		$playerRank = $player->getRating();
// 		$queryParams = array('playerID' => $player->getId(), 'playerRank' => $playerRank);
// 		$constraints = '';
// 		if (!is_null($length) && !is_null($skill)) {
// 			//get additional constraints
// 			if ($skill == 1) {
// 				//best match
// 				$queryParams['minRank'] = $playerRank - 100;
// 				$queryParams['maxRank'] = $playerRank + 100;
// 				$constraints = ' AND p.rating >= :minRank AND p.rating <= :maxRank';
// 			} else {
// 				if ($skill == 2) {
// 					//lesser skill
// 					$op = ' <= ';
// 				} else {
// 					//greater skill
// 					$op = ' >= ';			
// 				}
// 				$constraints = ' AND p.rating'.$op.':playerRank';
// 			}
// 			$queryParams['length'] = $length;
// 			$constraints .= ' AND (gs.length = :length OR gs.length IS NULL)';
// 		}
// 		//find game
// 		$search = $this->getEntityManager()
// 		->createQuery(
// 				'SELECT gs 
// 				FROM CMInterfaceBundle:GameSearch gs
// 			    JOIN gs.searcher p
// 				WHERE p.id != :playerID
// 				AND gs.matched = 0
// 				AND gs.cancelled = 0
// 				AND :playerRank >= gs.minRank
// 				AND :playerRank <= gs.maxRank
// 				'.$constraints
// 		)
// 		->setMaxResults(1)
// 		->setParameters($queryParams)
// 		->getResult();
		
// 		return $search;
// 	}
}