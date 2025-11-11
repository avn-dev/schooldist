<?php

namespace TsBitrix\Controller;

use Illuminate\Validation\Rule;
use Core\Factory\ValidatorFactory;
use TsApi\DTO\ApiField;
use TsApi\Exceptions\ApiError;

class RequestController extends \TsApi\Controller\EnquiryController {

	/**
	 * POST-Request: Enquiry erstellen
	 *
	 * @return \Illuminate\Http\Response|\Response
	 */
	public function call() {

		$this->oLogger->addInfo('Bitrix API', [$this->_oRequest, $_SERVER]);

		try {

			if(!$this->checkToken('ts_api_bitrix')) {
				throw new ApiError('Invalid token');
			}

//			if(!$this->_oRequest->isJson()) {
//				throw new ApiError('No valid JSON body');
//			}

//			$aInput = $this->_oRequest->json()->all();

			
			return $this->sendResponse(200, 'Yes');

		} catch(\Exception $e) {
			return $this->handleException($e);
		}

	}

}
