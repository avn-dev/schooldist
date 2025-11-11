<?php

namespace Ts\Service\Import;

use Tc\Exception\Import\ImportRowException;
use Tc\Service\Import\ErrorPointer;
use TsApi\Exceptions\ApiError;

class Enquiry extends AbstractImport {
	
	protected $sEntity = 'Ext_TS_Inquiry';
	protected $apiHandler = \TsApi\Handler\Enquiry::class;

	public function getFields() {

		$oSchool = \Ext_Thebing_School::getSchoolFromSession();

		$aReferer = $oSchool->getRefererList();
		$aStatus = $oSchool->getCustomerStatusList();
		$oClient =\Ext_Thebing_Client::getFirstClient();
		$aAgencies = $oClient->getAgencies(true);

		$aFields = [];
		$aFields[] = ['field'=> 'Vorname', 'target' => 'firstname'];
		$aFields[] = ['field'=> 'Nachname', 'target' => 'lastname'];
		$aFields[] = ['field'=> 'E-Mail', 'target' => 'email'];
		$aFields[] = ['field'=> 'Korrespondenzsprache (en, en_US, fr, fr_FR,...)', 'target' => 'corresponding_language', 'default'=>$oSchool->getLanguage()];
		$aFields[] = ['field'=> 'Nationalität (ISO 3166-1 alpha-2, uppercase)', 'target' => 'nationality'];
		$aFields[] = [
			'field' => 'Geburtsdatum',
			'target' => 'birthday',
			'special' => 'date',
			'additional' => $this->sExcelDateFormat,
		];
		$aFields[] = ['field'=> 'Geschlecht (m = männlich, f = weiblich, x = divers)', 'target' => 'gender', 'special'=>'gender'];
		$aFields[] = ['field'=> 'Telefon (valid ITU telephone number, like: +49 221 123456789)', 'target' => 'phone_private'];

		$aFields[] = ['field'=> 'Adresse', 'target' => 'address'];
		$aFields[] = ['field'=> 'PLZ', 'target' => 'zip'];
		$aFields[] = ['field'=> 'Ort', 'target' => 'city'];
		$aFields[] = ['field'=> 'Land (ISO 3166-1 alpha-2, uppercase)', 'target' => 'country_iso'];

		$aFields[] = ['field'=> 'Kommentar', 'target' => 'comment'];
		$aFields[] = ['field'=> 'Kurs-Kommentar', 'target' => 'comment_course_category'];
		$aFields[] = ['field'=> 'Referrer', 'target' => 'referrer_id', 'special'=>'array', 'additional'=>array_flip($aReferer)];
		$aFields[] = ['field'=> 'Schüler-Status', 'target' => 'student_status_id', 'special'=>'array', 'additional'=>array_flip($aStatus)];
		$aFields[] = ['field'=> 'Agentur', 'target' => 'agency_id', 'special'=>'array', 'additional'=>array_flip($aAgencies)];

		$aFields[] = ['field'=> 'Kundennummer', 'target' => 'contact_number'];
		$aFields[] = ['field'=> 'Buchungsnummer', 'target' => 'booking_number'];
		$aFields[] = ['field'=> 'Bundesland', 'target' => 'state'];
		$aFields[] = ['field'=> 'Muttersprache (ISO 639-1, lowercase)', 'target' => 'mothertongue'];
		$aFields[] = ['field'=> 'Telefon Mobil', 'target' => 'detail_'.\Ext_TC_Contact_Detail::TYPE_PHONE_MOBILE];
		$aFields[] = ['field'=> 'Notfallkontakt - Vorname', 'target' => 'emergency_firstname'];
		$aFields[] = ['field'=> 'Notfallkontakt - Nachname', 'target' => 'emergency_lastname'];
		$aFields[] = ['field'=> 'Notfallkontakt - Telefon', 'target' => 'emergency_phone'];
		$aFields[] = ['field'=> 'Notfallkontakt - E-Mail', 'target' => 'emergency_email'];

		return $aFields;
	}
	
	protected function getBackupTables() {
		
		$aTables = [
			'ts_inquiries',
			'tc_contacts',
			'tc_addresses'
		];

		return $aTables;
	}
	
	protected function getCheckItemFields(array $aPreparedData) {

	}

	/**
	 * @see \Ext_TS_Enquiry_Gui2_Icon_Visible
	 */
	protected function processItem(array &$aItem, int $iItem, array $aAdditionalWorksheetData=null) {

		$oSchool = \Ext_Thebing_School::getSchoolFromSession();
		if (!$oSchool->exist()) {
			throw new \RuntimeException('No school');
		}

		$oEnquiryHandler = new ($this->apiHandler)($oSchool);

		try {

			$aData = [];
			\Ext_Thebing_Import::processItems($this->aFields, $aItem, $aData);

			if(
				!empty($aData['phone_private']) &&
				!empty($aData['country_iso'])
			) {
				$oWDValidator = new \WDValidate();
				$aData['phone_private'] = $oWDValidator->formatPhonenumber($aData['phone_private'], $aData['country_iso']);
			}

			$oValidator = $oEnquiryHandler->createValidator($aData);

			if($oValidator->fails()) {
				throw new ApiError('Validation failed', $oValidator);
			}

			$indexAgencyField = array_search('agency_id', array_column($this->aFields, 'target'));

			if(
				!empty($aItem[$indexAgencyField]) &&
				empty($aData['agency_id'])
			) {
				throw new \Exception(\L10N::t('Agentur nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH));
			}

			/*
			 * @todo update_existing muss implementiert werden! Welche Kriterien? (Typ: Anfrage, E-Mail)
			 */

			$oInquiry = $oEnquiryHandler->buildInquiry();

			$oEnquiryHandler->setObjectData($oInquiry, $aData);

			$this->aReport['insert']++;

			return $oInquiry->id;
			
		} catch(\Exception $e) {

			$this->handleProcessItemError($iItem,  $e);
	
		}
		
	}
	
	public function getFlexibleFields() {

		$aFlexFields['Main'] = \Ext_TC_Flexibility::getFields('student_record_general', false, ['enquiry', 'enquiry_booking']);

		return $aFlexFields;
	}

	protected function handleProcessItemError($item, \Exception $e) {
		if($e instanceof ApiError) {
			$this->aErrors[$item] = ($this->apiHandler)::formatValidationErrors($e->getValidator());
		} else if ($e instanceof ImportRowException && $e->hasPointer()) {
			$this->aErrors[$item] = [['message' => $e->getMessage(), 'pointer' => $e->getPointer()]];
		} else {
			$this->aErrors[$item] = [['message' => $e->getMessage(), 'pointer' => new ErrorPointer("", $item)]];
		}

		$this->aReport['error']++;

		if(empty($this->aSettings['skip_errors'])) {
			throw new \Exception('Terminate import');
		}
	}
	
}
