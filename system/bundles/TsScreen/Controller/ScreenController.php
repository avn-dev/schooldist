<?php

namespace TsScreen\Controller;

class ScreenController extends \MVC_Abstract_Controller {
	
	protected $_sInterface = 'frontend';
	protected $_sAccessRight = null;
	
	public function show($sKey) {
		
		$screen = \TsScreen\Entity\Screen::getRepository()->findOneBy(['key'=>$sKey]);
		
		if($screen === null) {
			abort(404);
		}

		$aTransfer = [
			'sKey' => $screen->key,
			'sName' => $screen->name,
			'sColor' => $screen->color,
			'sCss' => $screen->css,
			'sLogo' => $screen->logo,
			'sTicker' => $this->getTickerText($screen)
		];
		
		return response()->view('screen', $aTransfer);
	}
	
	private function getTickerText($screen) {
		
		$ticker = \TsScreen\Entity\Schedule::getRepository()->getValidElement($screen, 'ticker');

		$tickerString = null;
		if(!empty($ticker)) {
			$tickerString = $ticker->content;
		}
		
		return $tickerString;
	}
	
	private function getData(\TsScreen\Entity\Schedule $schedule=null) {
		
		if($schedule === null) {
			return '';
		}

		try {
		
			$className = '\TsScreen\Service\Elements\\'.ucfirst($schedule->type);
			$elementService = new $className($schedule);

			return $elementService->generate();
			
		} catch(\Throwable $e) {
			
			$log = \Log::getLogger('frontend', 'ts_screens');
			$log->addError('Exception', [$e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace()]);
		
			return ['error'=>$e->getMessage()];
		
		}
		
	}
	
	public function update(\MVC_Request $request, $sKey) {

		$compareChecksum = $request->get('checksum');
		
		$screen = \TsScreen\Entity\Screen::getRepository()->findOneBy(['key'=>$sKey]);

		$possibleElements = [
			'schedule',
			'roomplan',
			'editor'
		];
		
		foreach($possibleElements as $possibleElement) {
			
			$element = \TsScreen\Entity\Schedule::getRepository()->getValidElement($screen, $possibleElement);
			
			if(!empty($element)) {
				break;
			}
			
		} 

		$aTransfer = [
			'sKey' => $screen->key,
			'sTicker' => $this->getTickerText($screen),
			'sElement' => $element->type
		];
		
		if($element) {
			$data = $this->getData($element);
			$checksum = crc32(json_encode($data));

			if($checksum != $compareChecksum) {
				$aTransfer['iChecksum'] = $checksum;
				$aTransfer['oData'] = $data;
			}
		}
		
		return response()->json($aTransfer);
	}

}
