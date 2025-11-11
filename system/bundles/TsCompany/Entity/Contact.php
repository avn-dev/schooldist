<?php

namespace TsCompany\Entity;

use Communication\Interfaces\Model\CommunicationContact;
use Illuminate\Notifications\RoutesNotifications;
use Illuminate\Support\Collection;

/**
 * @property $id
 * @property $company_id
 * @property $email
 * @property $gender
 * @property $firstname
 * @property $lastname
 * @property $group
 * @property $phone
 * @property $mobile
 * @property $fax
 * @property $skype
 * @property $transfer
 * @property $accommodation
 * @property $reminder
 * @property $image
 * @property $master_contact
 * @property $user_id
 * @property $created
 * @property $changed
 * @property $active
 * @property $creator_id
 */
class Contact extends \Ext_Thebing_Basic implements CommunicationContact {
	use RoutesNotifications;

	protected $_sTable = 'ts_companies_contacts';

	protected $_sTableAlias = 'ts_ac';

	protected $_aFormat = array(
		'email'=> [
			'validate'=>'MAIL'
		],
		'lastname' => [
			'required' => true
		],
		'phone' => [
			'validate' => 'PHONE_ITU',
			'parameter_settings' => [
				'type' => 'method',
				'source' => 'getCompanyCountry'
			]
		],
		'fax' => [
			'validate' => 'PHONE_ITU',
			'parameter_settings' => [
				'type' => 'method',
				'source' => 'getCompanyCountry'
			]
		],
		'mobile' => [
			'validate' => 'PHONE_ITU',
			'parameter_settings' => [
				'type' => 'method',
				'source' => 'getCompanyCountry'
			]
		]
	);

	/**
	 * @param string $sName
	 * @return mixed|string
	 * @throws \ErrorException
	 */
	public function __get($sName){

		$sValue = '';

		switch($sName) {
			case 'name':
				return $this->getName();
			case 'name_description':
				// Erweiterter Name (erweiterbar)
				$oObject = $this->getParentObject();
				if($oObject instanceof AbstractCompany){
					$sValue = $oObject->ext_2 . ': ' . $this->name . ' (' . $this->email . ')';
				}
				break;
			default:
				$sValue = parent::__get($sName);
		}

		return $sValue;
	}

	public function save($bLog = true) {

		if ($this->master_contact == 1) {
			$this->resetMasterContact();
		}

		return parent::save($bLog);

	}

	public function getName() {

		$oFormat = new \Ext_Gui2_View_Format_Name();

		return $oFormat->formatByResult($this->getData());

	}

	public function resetMasterContact() {

		$iParentId = $this->company_id;

		if ($iParentId >= 0) {
			return;
		}

		$sSql = "
			UPDATE
				#table
			SET
				`master_contact` = 0
			WHERE
				`company_id` = :company_id
		";

		\DB::executePreparedQuery($sSql, array(
			'table' => $this->_sTable,
			'company_id' => $iParentId,
		));

	}

	/**
	 * @return null|Company
	 */
	public function getParentObject() {

		if($this->company_id > 0) {
			return Company::getInstance($this->company_id);
		}

		return null;
	}

	/**
	 * @return int|mixed|string
	 * @throws \Exception
	 */
	public function getLanguage() {

		$oObject = $this->getParentObject();
		$sLanguage = $oObject->getLanguage();

		return $sLanguage;
	}

	public function getCorrespondenceLanguages(): array {
		return [$this->getLanguage()];
	}

	public function getEmailAddresses() {
		if (!empty($this->email)) {
			return [$this->email];
		}

		return [];
	}

	public function routeNotificationFor($driver, $notification = null)
	{
		return match ($driver) {
			'mail' => $this->getCommunicationRoutes($driver)->first(),
			default => null,
		};
	}

	public function getCommunicationName(string $channel): string
	{
		return $this->getName();
	}

	public function getCommunicationRoutes(string $channel): ?Collection
	{
		return match ($channel) {
			'mail' => collect($this->getEmailAddresses())
				->map(fn ($email) => [$email, $this->getCommunicationName($channel)]),
			default => null,
		};
	}

	public function getCommunicationAdditionalRelations(): array
	{
		if (empty($parent = $this->getParentObject())) {
			return [];
		}

		return [
			$parent
		];
	}

	public function getCompanyCountry() {
		return $this->getParentObject()?->ext_6;
	}

}
