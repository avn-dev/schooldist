<?php

/**
 * @property int
 * @property int $group_id
 * @property timestamp $changed
 * @property timestamp $created
 * @property int $accommodation_id
 * @property int $roomtype_id
 * @property int $meal_id
 * @property int $weeks
 * @property date $from
 * @property date $until
 * @property int $calculate
 * @property int $visible
 * @property int $active
 * @property int $creator_id
 * @property time $from_time
 * @property time $until_time
 * @property string $type
 */
class Ext_Thebing_Inquiry_Group_Accommodation extends Ext_Thebing_Inquiry_Group_Service {

	const JOIN_ADDITIONALSERVICES = 'additionalservices';

	protected $_sTable = 'kolumbus_groups_accommodations';

	protected $_aFormat = array(
		'group_id' => array(
			'required'=>true,
			'validate'=>'INT_POSITIVE',
			'not_changeable' => true
		),
		'from_time' => array(
			'format' => 'TIME',
			'validate' => 'TIME'
		),
		'until_time' => array(
			'format' => 'TIME',
			'validate' => 'TIME'
		),
		'weeks' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
		),
		'accommodation_id' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
		),
		'roomtype_id' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
		),
		'meal_id' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
		)
	);

	protected $_aJoinTables = [
		self::JOIN_ADDITIONALSERVICES => [
			'table'	=> 'ts_groups_additionalservices',
			'primary_key_field'	=> 'relation_id',
			'static_key_fields' => ['relation' => 'accommodation'],
			'class'	=> 'Ext_Thebing_School_Additionalcost',
			'autoload' => false
		]
	];

	protected $_aJoinedObjects = array(
        'journey_accommodations' => array(
			'class'					=> 'Ext_TS_Inquiry_Journey_Accommodation',
			'key'					=> 'groups_accommodation_id',
			'check_active'			=> true,
			'type'					=> 'child'
        )
    );

	/**
	 * Liefert alle Payments die diese Unterkunft beeinflussen
	 *
	 * @param string $sFilterFrom
	 * @param string $sFilterUntil
	 * @param array $aNewAccommodationData
	 * @return array
	 */
	public function checkPaymentStatus($sFilterFrom, $sFilterUntil, $aNewAccommodationData) {
		
		/** @var Ext_TS_Inquiry_Journey_Accommodation[] $aJourneyAccommodations */
		$aJourneyAccommodations = (array)$this->getJoinedObjectChilds('journey_accommodations');
		$aPayments = [];

		foreach($aJourneyAccommodations as $oJourneyAccommodation) {
			$aPayments += $oJourneyAccommodation->checkPaymentStatus($sFilterFrom, $sFilterUntil, $aNewAccommodationData);
		}

		return $aPayments;

	}

	/**
	 * @return Ext_Thebing_Accommodation_Category
	 */
	public function getAccommodationCategory() {
		return Ext_Thebing_Accommodation_Category::getInstance($this->accommodation_id);
	}

	/**
	 * @return Ext_Thebing_Accommodation_Roomtype
	 */
	public function getRoomType() {
		return Ext_Thebing_Accommodation_Roomtype::getInstance($this->roomtype_id);
	}

	/**
	 * @return Ext_Thebing_Accommodation_Meal
	 */
	public function getMealType() {
		return Ext_Thebing_Accommodation_Meal::getInstance($this->meal_id);
	}

	/**
	 * Info-String dieser Unterkunftsbuchung
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getInfoString($sLanguage) {

		$aReturn = [];
		$oCategory = $this->getAccommodationCategory();
		$oRoomType = $this->getRoomType();
		$oMealType = $this->getMealType();

		foreach([$oCategory, $oRoomType, $oMealType] as $oObject) {
			if($oObject->exist()) {
				$sName = $oObject->getShortName($sLanguage);
				if(!empty($sName)) {
					$aReturn[] = $oObject->getShortName($sLanguage);
				}
			}
		}

		return join(' / ', $aReturn);

	}
	
}