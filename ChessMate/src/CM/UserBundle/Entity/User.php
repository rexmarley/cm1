<?php
// src/Acme/UserBundle/Entity/User.php

namespace CM\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\BooleanType;

/**
 * @ORM\Entity
 * @ORM\Table(name="cm_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="boolean")
     */
    protected $registered;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }
    
    public function setRegistered($reg) {
    	$this->registered = $reg;
    }
    
    public function getRegistered() {
    	return $this->registered;
    }
}