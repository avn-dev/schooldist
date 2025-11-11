<?php

namespace OpenBanking\Http\Controllers;

use Core\Controller\Vendor\ResourceAbstractController;

class ResourceController extends ResourceAbstractController
{
	protected $_sInterface = 'backend';

	protected $sPath = 'system/bundles/OpenBanking/Resources/assets/';
}