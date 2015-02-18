<?php

namespace CM\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CMUserBundle extends Bundle
{
	public function getParent() {
		return 'FOSUserBundle';
	}

}
