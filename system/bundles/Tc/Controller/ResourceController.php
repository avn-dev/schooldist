<?php

namespace Tc\Controller;

use Core\Controller\Vendor\ResourceAbstractController;

class ResourceController extends ResourceAbstractController
{
	protected $_sInterface = 'backend';

	protected $sPath = 'system/bundles/Tc/Resources/assets/';
}