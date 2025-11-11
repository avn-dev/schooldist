<?php

namespace TcFrontend\Controller;

use Core\Controller\Vendor\ResourceAbstractController;

class ResourceController extends ResourceAbstractController {

	protected $_sAccessRight = null;

	protected $_sInterface = 'frontend';

	protected $sPath = 'system/bundles/TcFrontend/Resources/assets/';

}