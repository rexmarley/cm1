<?php

namespace Acme\HelloBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use CM\UserBundle\Entity\User;

class LoadData extends AbstractFixture implements OrderedFixtureInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function load(ObjectManager $manager)
	{
		//create users
		$user1 = new User();
		$user1->setUsername('Rex');
		$user1->setPassword('pass');
		$user1->setRegistered(true);	
		$user1->setEmail('me@here.com');
		$user1->setLastActiveTime(new \DateTime());

		$manager->persist($user1);
		$manager->flush();
		
        //$this->addReference('user1', $user1);
		
		//create pieces
	}

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
