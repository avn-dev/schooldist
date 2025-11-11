<?php

namespace Admin\Http\Controller;

class ImgBuilderController extends \MVC_Abstract_Controller {
	
	protected $_sInterface = 'frontend';
	protected $_sAccessRight = null;
	
	public function execute() {
		
		$sSet = $this->_oRequest->get('s');
		$aContent = $this->_oRequest->input('c');

		ini_set('memory_limit', '1G');

		$aInfo = array(0=>0);
		$aInfo[1] = (int)$sSet;
		foreach((array)$aContent as $mData) {
			$aInfo[] = $mData;
		}

		$oImgBuilder = new \imgBuilder();

		$oImgBuilder->buildImage($aInfo, 1);
		die();

	}
	
}
