<?php

use Communication\Interfaces\Model\CommunicationContact;

/**
 * @property string $company
 * @property string $homepage
 * @property string $zip
 * @property string $city
 * @property string $country
 * @property string $phone1
 * @property string $phone2
 * @property string $fax
 * @property string $email
 * @property string $skype_id
 * @property string $contact
 */
class Ext_Thebing_Insurances_Provider extends Ext_Thebing_Basic implements CommunicationContact {

	protected $_sTable = 'kolumbus_insurance_providers';
	protected $_sTableAlias = 'kinsp';

	/**
	 * See parent
	 */
	protected $_aFormat = array(
		'email'	=> array(
			'validate'	=> 'MAIL'
		)
	);

	/**
	 * See parent
	 */
	protected $_aJoinTables = array(
		'insurances'	=> array(
			'table'				=> 'kolumbus_insurances',
			'primary_key_field'	=> 'provider_id',
			'check_active'		=> true,
			'delete_check'		=> true,
			'join_operator'		=> 'LEFT OUTER JOIN'
		)
	);

	/**
	 * Gibt ein Array mit allen relevanten Empfängern zurück
	 * @return array
	 */
	public function getRecipients() {
		$aRecipients = array();

		$sRecipient = $this->company.' ('.$this->email.')';

		$aRecipients[$this->email] = $sRecipient;
		return $aRecipients;
	}

	public function getCommunicationName(string $channel): string
	{
		return $this->company;
	}

	public function getCommunicationRoutes(string $channel): ?\Illuminate\Support\Collection
	{
		return match ($channel) {
			'mail' => (!empty($this->email)) ? collect([[$this->email, $this->company]]) : null,
			default => null,
		};
	}

	public function getCorrespondenceLanguages(): array
	{
		$school = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		return $school->getCorrespondenceLanguages();
	}
}