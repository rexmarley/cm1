<?php

namespace CM\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *  php app/console doctrine:generate:entities UserBundle
 */
class UserRepository extends EntityRepository
{
	public function findInactiveGuest()
	{
		$guest = $this->getEntityManager()
		->createQuery(
				'SELECT u FROM CMUserBundle:User u
				WHERE u.registered = 0
				AND u.lastActiveTime < :active'
		)
		->setMaxResults(1)
		->setParameter('active', new \DateTime('5 minutes ago'))
		->getResult();
		
		return $guest;
	}
}
