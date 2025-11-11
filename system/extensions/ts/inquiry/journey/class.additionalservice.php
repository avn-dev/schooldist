<?php

use TsRegistrationForm\Interfaces\RegistrationInquiryService;

/**
 * @property string $id
 * @property string $changed
 * @property string $created
 * @property string $active
 * @property string $creator_id
 * @property string $journey_id
 * @property string $additionalservice_id
 * @property string $relation
 * @property string $relation_id
 * @property string $visible
 * @property string $comment
 */
class Ext_TS_Inquiry_Journey_Additionalservice extends Ext_TS_Inquiry_Journey_Service implements RegistrationInquiryService {

	protected $_sTable = 'ts_inquiries_journeys_additionalservices';

	protected $_sTableAlias = 'ts_ijas';

	protected $_aJoinedObjects = [
		'journey' => [
			'class' => Ext_TS_Inquiry_Journey::class,
			'type' => 'parent',
			'check_active' => true,
			'key' => 'journey_id'
		],
		'additionalservice' => [
			'class' => Ext_Thebing_School_Additionalcost::class,
			'type' => 'parent',
			'check_active' => true,
			'key' => 'additionalservice_id'
		]
	];

	protected $_aJoinTables = array(
//		'additionalservice_travellers' => [
//			'table' => 'ts_inquiries_journeys_additionalservices_to_travellers',
//			'primary_key_field' => 'journey_additionalservice_id',
//			'foreign_key_field' => 'contact_id',
//			'autoload' => false
//		]
	);

	/**
	 * @var string
	 */
	protected $_sEditorIdColumn = 'editor_id';

	public function __get($name) {
		
		if($name === 'sInfoTemplateType') {
			switch($this->relation) {
				case 'course':
					return 'additional_course';
				case 'accommodation':
					return 'additional_accommodation';
				default:
					throw new \DomainException('Invalid type');
//					return 'additional_general';
			}
		}

		return parent::__get($name);		
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return 'additionalservice';
	}

	/**
	 * @return Ext_Thebing_School_Additionalcost
	 */
	public function getAdditionalservice() {
		return $this->getJoinedObject('additionalservice');
	}

//	/**
//	 * @return string
//	 */
//	public function getNameForEditData() {
//
//		$sInterfaceLanguage = Ext_Thebing_School::fetchInterfaceLanguage();
//
//		$sName = $this->getJoinedObject('additionalservice')->getName($sInterfaceLanguage);
//
//		return $sName;
//	}

	/**
	 * @param null $oActivity
	 * @param string $sMode
	 * @return bool|string
	 * @throws Exception
	 */
	public function checkForChange($oActivity = null, $sMode = 'complete') {

		if($this->id <= 0) {
			return 'new';
		}

		if($this->active == 0) {
			return 'delete';
		}

		if($oActivity == null) {
			$aOriginalData = $this->getOriginalData();
		} else {
			$aOriginalData = $oActivity->getData();
		}

		if($sMode === 'complete') {

			if(
				(int)$this->additionalservice_id !== (int)$aOriginalData['additionalservice_id'] ||
				(string)$this->relation !== (string)$aOriginalData['relation'] ||
				(int)$this->relation_id !== (int)$aOriginalData['relation_id'] ||
				$this->visible != $aOriginalData['visible']
			) {
				return 'edit';
			}

		}

		return false;
	}

	public function isEmpty() {

		if($this->additionalservice_id <= 0) {
			return true;
		}

		return false;

	}

	protected function assignLineItemDescriptionVariables(\Core\Service\Templating $oSmarty, \Tc\Service\Language\Frontend $oLanguage) {
		
		$oAdditionalservice = $this->getAdditionalservice();
		$sName = $oAdditionalservice->getName($oLanguage->getLanguage());

		$oSmarty->assign('name', $sName);
		
	}

	public function getRegistrationFormData(): array {

		// Wird nicht benutzt, sondern läuft wg. generellen Gebühren komplett individuell
		return [
//			'additionalservice' => !empty($this->additionalservice_id) ? (int)$this->additionalservice_id : null
		];

	}

	/**
	 * {@inheritdoc}
	 */
	public function validatePayment() {
		return array();
	}

}
