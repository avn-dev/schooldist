<?php

namespace TsApi\Controller;

use TsApi\Exceptions\ApiError;
use Illuminate\Http\Request;
use Tc\Traits\Http\ErrorResponse;

abstract class AbstractController extends \Illuminate\Routing\Controller {

	use ErrorResponse;
	
	/**
	 * @var \Monolog\Logger
	 */
	protected $oLogger;

	protected $handlerClass;

	/**
	 * @todo Hier wird direkt \TsApi\Handler\Enquiry::formatValidationErrors aufgerufen was in der Abstrakten-Klasse falsch ist.
	 * @param \Exception $oException
	 * @return \Illuminate\Http\Response|\Response
	 */
	protected function handleException(Request $request, \Throwable $oException) {

//		if(\System::d('debugmode')) {
//			__pout($oException->getMessage());
//			__pout($oException->getTraceAsString(),1);
//		}
		
		$this->oLogger = \Log::getLogger('frontend');

		if($oException instanceof ApiError) {

			if(!empty($oException->getValidator())) {
				$aErrors = $this->handlerClass::formatValidationErrors($oException->getValidator());

				$this->oLogger->addInfo('API Error: '.$oException->getMessage(), ['errors' => $aErrors, 'request' => $request->all()]);
				return $this->sendResponse(400, 'Validation Error', [
					'errors' => $this->handlerClass::formatValidationErrors($oException->getValidator())
				]);
			}

			$this->oLogger->addInfo('API Error: '.$oException->getMessage(), ['request' => $request->all()]);
			return $this->sendResponse(400, $oException->getMessage());

		} else {
			$this->oLogger->addError('API Error: '.$oException->getMessage(), ['trace' => $oException->getTraceAsString(), 'request' => $request->all()]);
			return $this->sendResponse(500, 'Internal Error');
		}

	}

	/**
	 * @param $iStatus
	 * @param $sMessage
	 * @param array $aData
	 * @return \Illuminate\Http\Response|\Response
	 */
	protected function sendResponse($iStatus, $sMessage, array $aData = []) {

		$aData['status'] = $iStatus;
		$aData['message'] = $sMessage;

		return response($aData, $iStatus);

	}

	/**
	 * GET-Request: Enquiry über ID abrufen
	 *
	 * @param int $id
	 * @return \Illuminate\Http\Response|\Response
	 */
	public function show(Request $request, $id) {

		try {

			\System::setInterfaceLanguage('en');
			
			/** @var \Ext_TS_Inquiry $oEnquiry */
			$oEnquiry = \Ext_TS_Inquiry::getRepository()->findOneBy(['id' => $id]);
			if($oEnquiry === null) {
				throw new ApiError('Invalid entry given: ID '.$id);
			}

			$oSchool = $oEnquiry->getSchool();
			
			$oEnquiryHandler = new $this->handlerClass($oSchool);

			$aObject = $oEnquiryHandler->getObjectData($oEnquiry);

			return response($aObject);

		} catch(\Exception $e) {
			return $this->handleException($request, $e);
		}

	}

	/**
	 * POST-Request: Enquiry erstellen
	 *
	 * @return \Illuminate\Http\Response|\Response
	 */
	public function store(Request $request) {

		try {

			\System::setInterfaceLanguage('en');
			
			if(!$request->isJson()) {
				throw new ApiError('No valid JSON body');
			}

			$aInput = $request->json()->all();

			$aSchoolIds = array_keys(\Ext_Thebing_System::getClient()->getSchools(true));
			if(
				empty($aInput['school_id']) ||
				!in_array($aInput['school_id'], $aSchoolIds)
			) {
				throw new ApiError('No valid school id');
			}

			$oSchool = \Ext_Thebing_School::getInstance($aInput['school_id']);
			$aInput = \Illuminate\Support\Arr::except($aInput, 'school_id');

			$oEnquiryHandler = new $this->handlerClass($oSchool);

			$oValidator = $oEnquiryHandler->createValidator($aInput);

			if($oValidator->fails()) {
				throw new ApiError('Validation failed', $oValidator);
			}

			$oEnquiry = $oEnquiryHandler->buildInquiry();

			if (isset($aInput['courses'])) {
				foreach ($aInput['courses'] as $courseInput) {
					$oEnquiryHandler->buildCourse($courseInput);
				}
			}

			if (isset($aInput['accommodations'])) {
				foreach ($aInput['accommodations'] as $accommodationInput) {
					$oEnquiryHandler->buildAccommodation($accommodationInput);
				}
			}

			if (isset($aInput['transfers'])) {
				foreach ($aInput['transfers'] as $transferInput) {
					$oEnquiryHandler->buildTransfer($transferInput);
				}
			}

			if (isset($aInput['insurances'])) {
				foreach ($aInput['insurances'] as $insuranceInput) {
					$oEnquiryHandler->buildInsurance();
				}
			}

			if (isset($aInput['activities'])) {
				foreach ($aInput['activities'] as $activityInput) {
					$oEnquiryHandler->buildActivity();
				}
			}
			
			$oEnquiryHandler->setObjectData($oEnquiry, $aInput);

			return $this->sendResponse(200, 'Entry successfully created', ['id' => $oEnquiry->id]);

		} catch(\Throwable $e) {
			return $this->handleException($request, $e);
		}

	}

	/**
	 * PATCH-Request: Enquiry aktualisieren
	 *
	 * @param int $id
	 * @return \Illuminate\Http\Response|\Response
	 */
	public function update(Request $request, $id) {

		try {

			\System::setInterfaceLanguage('en');
			
			if(!$request->isJson()) {
				throw new ApiError('No valid JSON body');
			}

			$aInput = $request->json()->all();

			/** @var \Ext_TS_Inquiry $oEnquiry */
			$oEnquiry = \Ext_TS_Inquiry::getRepository()->findOneBy(['id' => $id]);
			if($oEnquiry === null) {
				throw new ApiError('Invalid enquiry given: ID '.$id);
			}

			$oSchool = $oEnquiry->getSchool();
			
			$oEnquiryHandler = new $this->handlerClass($oSchool, true);

			$oValidator = $oEnquiryHandler->createValidator($aInput);

			if($oValidator->fails()) {
				throw new ApiError('Validation failed', $oValidator);
			}

			// Damit auch umgewandelte Anfragen per API wie Anfragen behandelbar sind
			$customer = $oEnquiry->getCustomer();
			$customer->bCheckGender = false;
			
			$oEnquiryHandler->setObjectData($oEnquiry, $aInput);

			return $this->sendResponse(200, 'Entry successfully updated', ['id' => $oEnquiry->id]);

		} catch(\Throwable $e) {
			return $this->handleException($request, $e);
		}

	}

	public function search(Request $request) {
		
		try {

			\System::setInterfaceLanguage('en');
			
			if(!$request->isJson()) {
				throw new ApiError('No valid JSON body');
			}

			$aInput = $request->json()->all();

			$aSchoolIds = array_keys(\Ext_Thebing_System::getClient()->getSchools(true));
			if(
				empty($aInput['school_id']) ||
				!in_array($aInput['school_id'], $aSchoolIds)
			) {
				throw new ApiError('No valid school id');
			}

			if(\Util::checkEmailMx($aInput['email']) === false) {
				throw new ApiError('No valid email address');
			}
			
			$aEnquiries = [];

			$oSchool = \Ext_Thebing_School::getInstance($aInput['school_id']);
			
			$oEnquiryHandler = new $this->handlerClass($oSchool);
			
			$sResults = $oEnquiryHandler->searchEnquiriesByMail($aInput['email']);

			if(
				!empty($sResults) && 
				$sResults['total'] > 0
			) {
				foreach($sResults['hits'] as $aResult) {
					$oEnquiry = \Ext_TS_Inquiry::getInstance($aResult['_id']);
					if(
						$oEnquiry->exist() &&
						$oEnquiry->isActive()
					) {
						$aEnquiries[] = $oEnquiryHandler->getObjectData($oEnquiry);
					}
				}
			}

			// TODO Welchen Sinn macht diese Verschachtelung?
			return response(['enquiries' => $aEnquiries]);

		} catch(\Throwable $e) {
			return $this->handleException($request, $e);
		}

	}
	
}
