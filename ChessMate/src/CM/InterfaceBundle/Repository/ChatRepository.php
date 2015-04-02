<?php

namespace CM\InterfaceBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CM\UserBundle\Entity\User;
use CM\InterfaceBundle\Entity\Game;

/**
 * ChatRepository
 */
class ChatRepository extends EntityRepository
{
	/**
	 * Find chat messages for player & game
	 * @param User $player
	 * @param Game $game
	 * @param int $lastSeen
	 * 
	 * @return array
	 */
	public function findGamePlayerChat(User $user, Game $game, $lastSeen = 0)
	{
		$msgs = $this->getEntityManager()
		->createQuery(
				'SELECT m FROM CMInterfaceBundle:ChatMessage m
				WHERE m.id > :lastSeen
				AND m.user = :user
				AND m.game = :game'
		)
		->setParameter('lastSeen', $lastSeen)
		->setParameter('user', $user)
		->setParameter('game', $user)
		->getArrayResult();
		
		return $msgs;
	}
}