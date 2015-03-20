<?php

namespace CM\InterfaceBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CM\UserBundle\Entity\User;

/**
 * GameRepository
 */
class GameRepository extends EntityRepository
{
	/**
	 * Find any new game that user is not already part of
	 * @param User $user
	 * 
	 * @return array
	 */
	public function findAnyNewGame(User $user)
	{
		$game = $this->getEntityManager()
		->createQuery(
				'SELECT g FROM CMInterfaceBundle:Game g
				WHERE g.inProgress = 0
				AND :user NOT MEMBER OF g.players'
		)
		->setMaxResults(1)
		->setParameter('user', $user)
		->getResult();
		
		return $game;
	}
	
	/**
	 * Find new game, matching search terms
	 * @param User $user
	 * @param int $length
	 * @param int $skill
	 *
	 * @return array
	 */
	public function findMatchingNewGame(User $user, $length, $skill)
	{
		$userRank = $user->getRating();
		$rankQuery = '';
		if ($skill == 1) {
			//best match
			$queryParams = (array('minRank' => ($userRank - 100), 'maxRank' => ($userRank + 100)));
			$rankQuery = ' WHERE p.rating > :minRank AND p.rating < :maxRank ';
		} else {
			$queryParams = (array('userRank' => $userRank));
			if ($skill == 2) {
				//lesser skill
				$op = ' <= ';
			} else {
				//greater skill
				$op = ' >= ';			
			}
			$rankQuery = ' WHERE p.rating'.$op.':userRank ';
		}		
		$queryParams['user'] = $user;
		$queryParams['length'] = $length;
		//find game
		$game = $this->getEntityManager()
		->createQuery(
				'SELECT g 
				FROM CMInterfaceBundle:Game g
			    LEFT JOIN g.players p
				'.$rankQuery.'
				AND g.inProgress = 0
				AND :user NOT MEMBER OF g.players
				AND g.length = :length'
		)
		->setMaxResults(1)
		->setParameters($queryParams)//;
		//echo $game->getSQL(); exit;
		->getResult();
		
		return $game;
	}
}