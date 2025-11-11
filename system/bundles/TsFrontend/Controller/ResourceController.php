<?php

namespace TsFrontend\Controller;

use Core\Controller\Vendor\ResourceAbstractController;

class ResourceController extends ResourceAbstractController {

	protected $_sAccessRight = null;

	protected $_sInterface = 'frontend';

	protected $sPath = 'system/bundles/TsFrontend/Resources/assets/';

}