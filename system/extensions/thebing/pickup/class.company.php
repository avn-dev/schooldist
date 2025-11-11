<?php

/**
 * @property $id
 * @property $created 	
 * @property $changed 	
 * @property $valid_until 	
 * @property $user_id 	
 * @property $idClient 	
 * @property $idSchool 	
 * @property $active 	
 * @property $creator_id 	
 * @property $title 	
 * @property $firstname 	
 * @property $lastname 	
 * @property $name 	
 * @property $street 	
 * @property $city 	
 * @property $plz 	
 * @property $state 	
 * @property $country 	
 * @property $country_iso 	
 * @property $tel 	
 * @property $fax 	
 * @property $handy 	
 * @property $email 	
 * @property $from_airports 	
 * @property $to_airports 	
 * @property $from_all_accommodations 	
 * @property $to_all_accommodations 	
 * @property $from_accommodations 	
 * @property $to_accommodations 	
 * @property $from_railways 	
 * @property $to_railways 	
 * @property $payment 	
 * @property $bank_account_holder 	
 * @property $bank_account_number 	
 * @property $bank_code 	
 * @property $bank_name 	
 * @property $bank_address
 */
class Ext_Thebing_Pickup_Company extends Ext_Thebing_Basic implements \TsPrivacy\Interfaces\Entity, \Communication\Interfaces\Model\CommunicationContact {

	use Ext_TS_Transfer_Location_Trait;

	// Tabellenname
	protected $_sTable = 'kolumbus_companies';

	protected $_sTableAlias = 'kco';

	protected $_aFormat = array(
		'email'	=> array(
			'validate'	=> 'MAIL'
		)
	);

	protected $_aJoinedObjects = array(
		'journey_transfers' => array(
			'class'				=> 'Ext_TS_Inquiry_Journey_Transfer',
			'key'				=> 'provider_id',
			'type'				=> 'child',
			'static_key_fields' => array('provider_type' => 'provider'),
			'check_active'		=> true,
			'on_delete' => 'no_purge'
		),
		'drivers' => [
			'class' => 'Ext_Thebing_Pickup_Company_Driver',
			'key' => 'companie_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		]
	);

	protected $_aFlexibleFieldsConfig = [
		'transfer_providers' => []
	];

    public function __get($sField) {

		Ext_Gui2_Index_Registry::set($this);
		
		if(
			$sField == 'from_airports' ||
			$sField == 'to_airports' ||
			$sField == 'from_accommodations' ||
			$sField == 'to_accommodations'
		) {
			$mValue = json_decode($this->_aData[$sField]);
            $mValue = (array)$mValue;
		} elseif($sField === 'zip') {
			$mValue = $this->plz;
		} elseif($sField === 'gender') {
			$mValue = $this->title;
		} elseif($sField === 'phone') {
			$mValue = $this->tel;
		} elseif($sField === 'mobile_phone') {
			$mValue = $this->handy;
		} else {
			$mValue = parent::__get($sField);
		}

		return $mValue;

	}

	public function __set($sField, $mValue) {

		if(
			$sField == 'from_airports' ||
			$sField == 'to_airports' ||
			$sField == 'from_accommodations' ||
			$sField == 'to_accommodations'
		) {
			$this->_aData[$sField] = (string)json_encode($mValue);
		} else {
			parent::__set($sField, $mValue);
		}

	}

	public function getSchool(){
		if($this->idSchool > 0){
			$iSchool = $this->idSchool;
		}else{
			$iSchool = \Core\Handler\SessionHandler::getInstance()->get('sid');
		}
		return Ext_Thebing_School::getInstance($iSchool);

	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->getName();
	}

	/**
	 * Gibt die Kommunikationssprache in der mit Providern kommuniziert
	 * werden soll (Standardschulsprache)
	 * @return <string>
	 */
	public function getLanguage() {
		$oSchool = $this->getSchool();
		$sLanguage = $oSchool->getLanguage();
		return $sLanguage;
	}

	public function save($bLog = true) {

		if($this->from_all_accommodations || $this->to_all_accommodations)
		{
			$oSchool = Ext_Thebing_School::getSchoolFromSession();

			$aAccTransfers = (array)$oSchool->getTransferLocations();

			// Select all accommodations
			if($this->from_all_accommodations)
			{
				$this->from_accommodations = array_keys($aAccTransfers);
			}

			// Select all accommodations
			if($this->to_all_accommodations)
			{
				$this->to_accommodations = array_keys($aAccTransfers);
			}
		}

		$mReturn = parent::save($bLog);
		
		WDCache::delete('transfer_provider');

		return $mReturn;

	}

	/**
	 * @inheritdoc
	 */
	public function purge($bAnonymize = false) {

		if(DB::getLastTransactionPoint() === null) {
			throw new RuntimeException(__METHOD__.': Not in a transaction!');
		}

		if(!$bAnonymize) {
			$this->enablePurgeDelete();
		}

		/** @var Ext_Thebing_Pickup_Company_Driver[] $aDrivers */
		$aDrivers = $this->getJoinedObjectChilds('drivers', false);
		foreach($aDrivers as $oDriver) {
			$oDriver->enablePurgeDelete();
			$oDriver->delete();
		}

		if(!$bAnonymize) {
			$this->delete();
		} else {

			$this->name = 'Anonym '.ucfirst(strtolower(Util::generateRandomString(8, ['no_numbers' => true])));
			$this->title = '';
			$this->firstname = '';
			$this->lastname = '';
			$this->tel = '';
			$this->handy = '';
			$this->fax = '';
			$this->email = '';
			$this->street = '';

			$this->bank_account_holder = '';
			$this->bank_account_number = '';
			$this->bank_code = '';
			$this->bank_name = '';
			$this->bank_address = '';

			$this->anonymized = 1;
			$this->save();

		}

	}

	/**
	 * @inheritdoc
	 */
	public static function getPurgeLabel() {
		return L10N::t('Transferanbieter', \TsPrivacy\Service\Notification::TRANSLATION_PATH);
	}

	/**
	 * @inheritdoc
	 */
	public static function getPurgeSettings() {
		$oClient = Ext_Thebing_Client::getFirstClient();
		return [
			'action' => $oClient->privacy_provider_action,
			'quantity' => $oClient->privacy_provider_quantity,
			'unit' => $oClient->privacy_provider_unit,
			'basedon' => 'valid_until'
		];
	}
	
	public function getInquiryJourneyTransfers($mUseDate = false) {
		$aJourneyTransfers = $this->getJoinedObjectChilds('journey_transfers', true);

		if($mUseDate !== false) {
			
			if(
				WDDate::isDate($mUseDate, WDDate::DB_DATE) &&
				$mUseDate != '0000-00-00'
			) {			
				
				$aTemp = $aJourneyTransfers;
				foreach($aTemp as $iKey => $oJourneyTransfer) {
					
					if(
						!WDDate::isDate($oJourneyTransfer->transfer_date, WDDate::DB_DATE) ||
						$oJourneyTransfer->transfer_date == '0000-00-00'
					) {
						unset($aJourneyTransfers[$iKey]);
						continue;
					}
					
					if($mUseDate != '0000-00-00') {
						
						$oTransferDate = new DateTime($oJourneyTransfer->transfer_date);	
						$oValidUntil = new DateTime($mUseDate);
						
						if($oTransferDate < $oValidUntil) {
							unset($aJourneyTransfers[$iKey]);
						}
					}
				}
				
			}
		}
				
		return $aJourneyTransfers;
	}

	/**
	 * @param bool $bEmptyItem
	 * @return array
	 */
	public static function getSelectOptions($bEmptyItem = true) {
		$oSelf = new static();
		$aList = $oSelf->getArrayList();
		$aReturn = array();

		if(!Ext_Thebing_System::isAllSchools()) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		}

		foreach($aList as $aTransferName) {
			if(!isset($oSchool) || $oSchool->id == $aTransferName['idSchool']) {
				$aReturn[$aTransferName['id']] = $aTransferName['name'];
			}
		}

		if($bEmptyItem) {
			$aReturn = Ext_TC_Util::addEmptyItem($aReturn);
			asort($aReturn);
		}

		return $aReturn;

	}

	public function getCommunicationName(string $channel): string
	{
		return $this->getName();
	}

	public function getCommunicationRoutes(string $channel): ?\Illuminate\Support\Collection
	{
		return match ($channel) {
			'mail' => (!empty($this->email)) ? collect([[$this->email, $this->getName()]]) : null,
			'sms' => (!empty($this->handy)) ? collect([[$this->handy, $this->getName()]]) : null,
			default => null,
		};
	}

	public function getCorrespondenceLanguages(): array
	{
		return [
			$this->getLanguage()
		];
	}
}