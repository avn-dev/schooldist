<?php

namespace TsApi\Handler;

use ElasticaAdapter\Facade\Elastica;
use Illuminate\Validation\Rule;
use Core\Factory\ValidatorFactory;
use TsApi\DTO\ApiField;
use TsApi\DTO\ContactNumberField;
use TsApi\DTO\InquiryNumberField;
use Illuminate\Support\Arr;

abstract class AbstractHandler {
	
	/**
	 * @var \Ext_Thebing_School
	 */
	protected $oSchool;
	
	/**
	 * @var array
	 */
	protected $aMapping;
	
	protected $type;
	protected $typeString;
	protected array $flexFieldsUsage = [];

	// inquiry->getObjectByAlias does not handle all types of objects.
	// Adding all the getters will be far more work, also because they can have multiple instances.
	// Because we already have all of them here, we save the references here.
	public array $objectsByAlias = [];

	public function __construct(\Ext_Thebing_School $oSchool, bool $bUpdate = false, array $mappingModifications = []) {

		$this->oSchool = $oSchool;
		$this->aMapping = $this->getMapping($bUpdate);
		foreach ($this->aMapping as &$oApiField) {
			if (isset($mappingModifications[$oApiField->sField]['validation'])) {
				$oApiField->aValidation = $mappingModifications[$oApiField->sField]['validation'];
			}
		}
	}
	
	/**
	 * @param array $aInput
	 * @return \Illuminate\Validation\Validator
	 */
	public function createValidator(array $aInput) {

		$oValidator = (new ValidatorFactory())->make($aInput, $this->getValidatorRules($this->aMapping), $this->getValidatorMessages());

		$oValidator->after(function() use($oValidator, $aInput) {
			$aInput = Arr::dot($aInput);
			// Überprüfen, ob Felder im Request Body sind, die nicht in der API existieren
			foreach($aInput as $sField => $mValue) {
				// Don't convert custom fields
				if (!str_starts_with($sField, 'custom_fields')) {
					$sField = implode(".", array_map(function ($v) {
						if ($v !== '' && is_numeric($v)) {
							return '*';
						}
						return $v;
					}, explode(".", $sField)));
				}
				if(!isset($this->aMapping[$sField])) {
					$oValidator->errors()->add($sField, 'invalid_field');
				}
			}
		});

		return $oValidator;

	}
	

	/**
	 * @param bool $bUpdate
	 * @return ApiField[]
	 */
	protected function getMapping(bool $bUpdate = false) {

		$oClient =\Ext_Thebing_Client::getFirstClient();
		$aAgencies = $oClient->getAgencies(true);
		$inboxes = array_keys($oClient->getInboxList(true));

		$oValidateReferrer = Rule::in(array_keys(\Ext_TS_Referrer::getReferrers(true, $this->oSchool->id)));
		$oValidateStudentStatus = Rule::in(array_keys(\Ext_Thebing_Marketing_Studentstatus::getList(true, $this->oSchool->id)));
		$oValidateNationality = Rule::in(array_keys(\Ext_Thebing_Nationality::getNationalities(true)));
		$oValidateLanguage = Rule::in(array_keys(\Ext_Thebing_Data::getLanguageSkills(true, false, false)));
		$oValidateCorrespondingLanguage = Rule::in(array_keys(\Ext_Thebing_Data::getCorrespondenceLanguages(true)));
		$oValidateAgencies = Rule::in(array_keys($aAgencies));
		$oValidateInbox = Rule::in($inboxes);

		$activityRepository = \TsActivities\Entity\Activity::getRepository();
		$activities = $activityRepository->getActivitiesBySchool($this->oSchool)->pluck('id');
		$insurances = \Ext_Thebing_Insurance::query()->pluck('id');
		$locations = array_keys(\Ext_TS_Transfer_Location::getLocations($this->oSchool));
		$locationTypes = \Ext_TS_Transfer_Location::getLocationTypes();
		$courseLanguages = \Ext_Thebing_Tuition_LevelGroup::query()->pluck('id');
		$accommodationMealCombinations = array_filter($this->oSchool->getAccommodationMealCombinations(), function($item) {
			return count($item) && count(reset($item));
		});

		$cRequired = function() use($bUpdate) {
			return $bUpdate ? '' : 'required';
		};

		$aMapping = [
			new ApiField('ts_i', 'referrer_id', [$oValidateReferrer], 'referer_id'),
			new ApiField('ts_i', 'student_status_id', [$oValidateStudentStatus], 'status_id'),
			new ApiField('ts_i', 'agency_id', [$oValidateAgencies], 'agency_id'),
			new ApiField('ts_i', 'inbox', [$oValidateInbox], 'inbox'),
			new ApiField('tc_cd', \Ext_TS_Contact::DETAIL_COMMENT),
			new ApiField('ts_i', 'comment_course_category', null, 'enquiry_course_category'),
			new ApiField('tc_c', 'firstname', []),
			new ApiField('tc_c', 'lastname', [$cRequired()]),
			new ApiField('tc_c', 'gender', ['in:0,1,2,3']),
			new ApiField('tc_c', 'birthday', ['date_format:Y-m-d']),
			new ApiField('tc_c', 'nationality', [$oValidateNationality]),
			new ApiField('tc_c', 'language', [$oValidateLanguage]),
			new ApiField('tc_c', 'corresponding_language', [$cRequired(), $oValidateCorrespondingLanguage]),
			new ApiField('tc_c', 'phone_private', ['phone_itu'], 'detail_phone_private'),
			new ApiField('tc_c', 'email', [$cRequired(), 'email_mx']),
			new ApiField('tc_ac', 'address', []),
			new ApiField('tc_ac', 'zip', []),
			new ApiField('tc_ac', 'city', []),
			new ApiField('tc_ac', 'country_iso', []),
			new ContactNumberField('tc_cn', 'contact_number', []), # Numberrange-ID beachten/eintragen
			new InquiryNumberField('ts_i', 'booking_number', [], 'number'),
			new ApiField('tc_ac', 'state', []),
			new ApiField('tc_c', 'mothertongue', [], 'language'),
			new ApiField('tc_c', 'detail_'.\Ext_TC_Contact_Detail::TYPE_PHONE_MOBILE, []),
			new ApiField('tc_c_e', 'emergency_firstname', [], 'firstname'),
			new ApiField('tc_c_e', 'emergency_lastname', [], 'lastname'),
			new ApiField('tc_c_e', 'emergency_phone', [], 'detail_'.\Ext_TC_Contact_Detail::TYPE_PHONE_PRIVATE),
			new ApiField('tc_c_e', 'emergency_email', [], 'email'),
			new ApiField('ts_i', 'confirmed', ['date_format:Y-m-d']),
			
			new ApiField('tc_bc', 'billing_firstname', [], 'firstname'),
			new ApiField('tc_bc', 'billing_lastname', [], 'lastname'),
			new ApiField('tc_bc', 'billing_phone', [], 'detail_'.\Ext_TC_Contact_Detail::TYPE_PHONE_PRIVATE),
			new ApiField('tc_bc', 'billing_email', [], 'email'),
			new ApiField('tc_a_b', 'billing_address', [], 'address'),
			new ApiField('tc_a_b', 'billing_zip', [], 'zip'),
			new ApiField('tc_a_b', 'billing_city', [], 'city'),
			new ApiField('tc_a_b', 'billing_country', [], 'country_iso'),
			
			new ApiField('ts_ijv', 'passport_number', [], 'passport_number'),
			
			new ApiField('ts_i_m_d', 'matching_allergies', [], 'acc_allergies'),
			
			new ApiField('ts_ijc', 'courses.*.course_id', ['required', 'integer', Rule::in(collect($this->oSchool->getCourses())->pluck('id'))], 'course_id'),
			new ApiField('ts_ijc', 'courses.*.language_id', ['integer', 'required', Rule::in($courseLanguages)],'courselanguage_id'),
			new ApiField('ts_ijc', 'courses.*.level_id', ['integer', 'required', Rule::in(array_keys($this->oSchool->getCourseLevelList()))], 'level_id'),
			new ApiField('ts_ijc', 'courses.*.weeks', ['integer', 'required'], 'weeks'),
			new ApiField('ts_ijc', 'courses.*.from', ['date_format:Y-m-d', 'required'], 'from'),
			new ApiField('ts_ijc', 'courses.*.until', ['date_format:Y-m-d', 'required'], 'until'),
			new ApiField('ts_ijcl', 'courses.*.lessons', ['numeric'], 'units'),

			new ApiField('ts_ija', 'accommodations.*.accommodation_id', ['required', 'integer', Rule::in(array_keys($accommodationMealCombinations))], 'accommodation_id', true),
			new ApiField('ts_ija', 'accommodations.*.weeks', ['integer', 'required'], 'weeks'),
			new ApiField('ts_ija', 'accommodations.*.from', ['date_format:Y-m-d', 'required'], 'from'),
			new ApiField('ts_ija', 'accommodations.*.until', ['date_format:Y-m-d', 'required'], 'until'),

			new ApiField('ts_ijt', 'transfers.*.type', [Rule::in([\Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE, \Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL, \Ext_TS_Inquiry_Journey_Transfer::TYPE_ADDITIONAL]), 'required'], null, true),
			new ApiField('ts_ijt', 'transfers.*.pickup_type', [Rule::in($locationTypes), 'required'], null, true),
			new ApiField('ts_ijt', 'transfers.*.pickup_location', ['required', Rule::in($locations)], null, true),
			new ApiField('ts_ijt', 'transfers.*.date', ['date_format:Y-m-d', 'required'], 'transfer_date'),
			new ApiField('ts_ijt', 'transfers.*.time', ['date_format:H:i:s', 'required'], 'transfer_time'),
			new ApiField('ts_ijt', 'transfers.*.dropoff_type', ['required', Rule::in($locationTypes)], null, true),
			new ApiField('ts_ijt', 'transfers.*.dropoff_location', ['integer', 'required', Rule::in($locations)], null, true),

			new ApiField('ts_iji', 'insurances.*.insurance_id', ['required', 'integer', Rule::in($insurances)], 'insurance_id'),
			new ApiField('ts_iji', 'insurances.*.weeks', ['integer', 'required'], 'weeks'),
			new ApiField('ts_iji', 'insurances.*.from', ['date_format:Y-m-d', 'required'], 'from'),
			new ApiField('ts_iji', 'insurances.*.until', ['date_format:Y-m-d', 'required'], 'until'),

			new ApiField('ts_ijact', 'activities.*.activity_id', ['required', 'integer', Rule::in($activities)], 'activity_id'),
			new ApiField('ts_ijact', 'activities.*.weeks', ['integer', 'required'], 'weeks'),
			new ApiField('ts_ijact', 'activities.*.from', ['date_format:Y-m-d', 'required'], 'from'),
			new ApiField('ts_ijact', 'activities.*.until', ['date_format:Y-m-d', 'required'], 'until'),

		];

		$aFields = Inquiry::fetchFlexFields();
		foreach($aFields as $oField) {

			if($oField->type == \Ext_TC_Flexibility::TYPE_HEADLINE) {
				continue;
			}

			$aRules = [];
			if($oField->required) {
				$aRules[] = $cRequired();
			}

			switch($oField->type) {
				case \Ext_TC_Flexibility::TYPE_TEXT:
				case \Ext_TC_Flexibility::TYPE_TEXTAREA:
				case \Ext_TC_Flexibility::TYPE_HTML:
					$aRules[] = 'strlen:65535'; // MySQL Text
					break;
				case \Ext_TC_Flexibility::TYPE_CHECKBOX:
					$aRules[] = Rule::in([0, 1]);
					break;
				case \Ext_TC_Flexibility::TYPE_DATE:
					$aRules[] = 'date_format:Y-m-d';
					break;
				case \Ext_TC_Flexibility::TYPE_SELECT:
				case \Ext_TC_Flexibility::TYPE_MULTISELECT:
					$aRules[] = Rule::in(array_column($oField->getJoinedObjectChilds('options'), 'id'));
					break;
				case \Ext_TC_Flexibility::TYPE_YESNO:
					$aRules[] = 'in:yes,no';
					break;
			}

			// IDs verwenden, da diese unveränderlich sind und somit nicht implementierte APIs stören
			$aMapping[] = new ApiField('flex', 'custom_fields.'.$oField->id, $aRules);

		}

		$aReturn = [];
		foreach($aMapping as $oApiField) {
			$aReturn[$oApiField->sField] = $oApiField;
		}

		return $aReturn;

	}

	public function getObjectData(\Ext_TS_Inquiry $oEnquiry) {

		$aObject = ['id' => $oEnquiry->id];

		$aClearFields = [
			'referrer_id',
			'status_id',
			'birthday',
			'nationality',
			'language',
		];

		foreach($this->aMapping as $oApiField) {

			// TODO Hier müsste ein Mapping eingeführt werden (null-Werte, Spezialfall Checkbox usw.)
			// Ignore fields with aliases that will not work with $oEnquiry->getObjectByAlias()
			if (in_array($oApiField->sAlias, ['flex', 'ts_ijc', 'ts_ija', 'ts_ijact', 'ts_iji', 'ts_ijt', 'ts_ijcl'])) {
				continue;
			}

			// There is already field language, that has same source. Can we remove the whole ApiField or is it needed for imports? This here stops it only from displaying
			if (in_array($oApiField->sField, ['mothertongue'])) {
				continue;
			}

			$oObject = $oEnquiry->getObjectByAlias($oApiField->sAlias, $oApiField->sColumn);
			$aObject[$oApiField->sField] = $oObject->{$oApiField->sColumn};

			// Leere Werte löschen
			if(
				in_array($oApiField->sField, $aClearFields) &&
				empty($aObject[$oApiField->sField]) ||
				$aObject[$oApiField->sField] === '0000-00-00'
			) {
				$aObject[$oApiField->sField] = '';
			}

		}

		return $aObject;
	}

	private function setApiFieldValues() {

	}

	public function setObjectData(\Ext_TS_Inquiry $oEnquiry, array $aInput, array $ignoreAliases = []): void {

		$aFlexData = [];
		$aInput = Arr::dot($aInput);
		foreach($aInput as $sField => $sValue) {
			$arrayKeys = explode('.', $sField);
			// Replace .0. with *
			// Don't convert custom fields
			if (!str_starts_with($sField, 'custom_fields')) {
				$sField = implode(".", array_map(function ($v) {
					if ($v !== '' && is_numeric($v)) {
						return '*';
					}
					return $v;
				}, $arrayKeys));
			}
			$oApiField = $this->aMapping[$sField];
			// Felder für diese Objekte werden nicht übernommen
			if (in_array($oApiField->sAlias, $ignoreAliases)) {
				continue;
			}
			if ($oApiField->sAlias === 'flex') {
				$iFieldId = $arrayKeys[1];
				$aFlexData[$iFieldId] = $sValue;
			} else {
				$objectsByAlias = $this->objectsByAlias;
				if (isset($this->objectsByAlias[$oApiField->sAlias]) && is_array($this->objectsByAlias[$oApiField->sAlias])) {
					$objectsByAlias = [$oApiField->sAlias => $this->objectsByAlias[$oApiField->sAlias][$arrayKeys[count($arrayKeys) - 2]]];
				}
				$oApiField->setValue($oEnquiry, $sValue, $objectsByAlias);
			}

		}

		// Because setObjectData sets some of the attributes, adjust course data after
		if (isset($this->objectsByAlias['ts_ijc'])) {
			foreach ($this->objectsByAlias['ts_ijc'] as $course) {
				$course->adjustData();
			}
		}

		// Update transfer attributes
		if (isset($this->objectsByAlias['ts_ijt'])) {
			foreach ($this->objectsByAlias['ts_ijt'] as $key => $transfer) {
				$transfer->setLocationByMergedString('start', $aInput['transfers.'.$key.'.pickup_type'] . "_" . $aInput['transfers.'.$key.'.pickup_location']);
				$transfer->setLocationByMergedString('end', $aInput['transfers.'.$key.'.dropoff_type'] . "_" . $aInput['transfers.'.$key.'.dropoff_location']);
			}
		}

		$mValidate = $oEnquiry->validate();

		if($mValidate !== true) {
			throw new \RuntimeException('API Error: Enquiry validation failed!');
		}

		$mSave = $oEnquiry->save();
		if(!$mSave instanceof \Ext_TS_Inquiry) {
			throw new \RuntimeException('API Error: Enquiry save failed!');
		}

		\Ext_TC_Flexibility::saveData($aFlexData, $oEnquiry->id);

		\Ext_Gui2_Index_Stack::save(true);

	}

	/**
	 * @param \TsApi\DTO\ApiField[] $aMapping
	 * @return array
	 */
	protected function getValidatorRules(array $aMapping) {

		$aRules = [];

		foreach($aMapping as $oField) {
			if(!empty($oField->aValidation)) {
				$aRules[$oField->sField] = $oField->aValidation;
			}

		}

		return $aRules;

	}

	/**
	 * Validator-Messages überschreiben, da die Standard-Messages in einer API nicht sinnvoll klingen
	 *
	 * @return array
	 */
	public static function getValidatorMessages() {

		return [
			'required' => 'Field is required: :attribute',
			'in' => 'Field :attribute has invalid value: :input, valid values: :values',
			'date_format' => 'Field :attribute has invalid date format: :input',
			'email_mx' => 'Field :attribute has invalid e-mail: :input',
			'strlen' => 'Field :attribute has invalid length',
			'phone_itu' => 'Field :attribute has invalid ITU telephone number: :input',
			'invalid_field' => 'Field does not exist: :attribute'
		];

	}

	/**
	 * Laravel Validator-Rules auf eigene Codes ummappen für Error-Rückgabe
	 *
	 * @return array
	 */
	public static function getValidatorRuleMapping() {

		return [
			'Required' => 'REQUIRED_FIELD_MISSING',
			'In' => 'INVALID_VALUE',
			'DateFormat' => 'INVALID_DATE',
			'EmailMx' => 'INVALID_EMAIL',
			'Strlen' => 'INVALID_VALUE',
			'PhoneItu' => 'INVALID_PHONE'
		];

	}

	/**
	 * @todo Das muss in eine abstrakte Klasse weil es allgemein ist und nicht nur für Anfragen
	 * @param \Illuminate\Validation\Validator $oValidator
	 * @return array
	 */
	static public function formatValidationErrors(\Illuminate\Validation\Validator $oValidator) {

		$aRuleCodes = self::getValidatorRuleMapping();
		$aMessages = $oValidator->errors()->getMessages();
		$aCustomMessages = self::getValidatorMessages();

		// Rules und Messages gibt es leider nicht zusammen
		$aErrors = [];
		foreach($oValidator->failed() as $sField => $aFailedRules) {
			$i = 0;
			foreach(array_keys($aFailedRules) as $sRule) {
				if(isset($aRuleCodes[$sRule])) {
					$sRule = $aRuleCodes[$sRule];
				}
				$aErrors[] = [
					'field' => $sField,
					'code' => strtoupper($sRule),
					'message' => $aMessages[$sField][$i]
				];
				unset($aMessages[$sField][$i]);
				$i++;
			}
		}

		// Manuelle Fehler
		foreach($aMessages as $sField => $aFieldMessages) {
			foreach($aFieldMessages as $sFieldMessage) {
				$sCode = strtoupper($sFieldMessage);
				if(isset($aCustomMessages[$sFieldMessage])) {
					$sFieldMessage = str_replace(':attribute', $sField, $aCustomMessages[$sFieldMessage]);
				}
				$aErrors[] = [
					'field' => $sField,
					'code' => $sCode,
					'message' => $sFieldMessage
				];
			}
		}

		return $aErrors;
	}

	public function searchEnquiriesByMail($sEmail) {

		$oSearch = new Elastica(Elastica::buildIndexName('ts_inquiry'));
		$oSearch->setLimit(20);

		$oTerm = new \Elastica\Query\Term();
		$oTerm->setTerm('type', $this->typeString);
		$oSearch->addMustQuery($oTerm);

		$oTerm = new \Elastica\Query\Term();
		$oTerm->setTerm('email_original', $sEmail);
		$oSearch->addMustQuery($oTerm);

		return $oSearch->search();

	}

	abstract public function buildInquiry(): \Ext_TS_Inquiry;

}
