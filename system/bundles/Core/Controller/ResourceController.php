<?php

namespace Core\Controller;

use Core\Controller\Vendor\ResourceAbstractController;

class ResourceController extends ResourceAbstractController {

	/*
	 * Diese Resourcen werden auch auf der Loginseite verwendet, müssen also public sein.
	 */
	protected $_sAccessRight = null;
	
	protected $sPath = 'system/bundles/Core/Resources/assets/';

}
