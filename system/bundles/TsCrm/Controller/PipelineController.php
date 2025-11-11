<?php

namespace TsCrm\Controller;

class PipelineController extends \MVC_Abstract_Controller {
	
	public function main() {
		
		$viewData = [];
		
		$oGui = \TsCrm\Gui2\Data\Pipeline::createGui();
		$viewData['oGui'] = $oGui;
		
		return response()->view('pipeline/pipeline', $viewData);
	}
	
}
