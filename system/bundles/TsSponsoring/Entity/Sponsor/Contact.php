<?php

namespace TsSponsoring\Entity\Sponsor;

use \TsSponsoring\Entity\Sponsor;

class Contact extends \Ext_TS_Contact {

	protected $_sPlaceholderClass = \TsSponsoring\Entity\Placeholder\Sponsor\Contact::class;

	public function __construct(int $iDataID = 0, string $sTable = null) {

		parent::__construct($iDataID, $sTable);

		$this->_aJoinTables['sponsors'] = [
			'table' => 'ts_sponsors_to_contacts',
			'foreign_key_field' => 'sponsor_id',
			'primary_key_field' => 'contact_id',
			'class' => Sponsor::class
		];

	}

	/**
	 * @return Sponsor
	 */
	public function getSponsor() {

		return \Illuminate\Support\Arr::first($this->getJoinTableObjects('sponsors'));

	}

	/**
	 * @return string
	 */
	public function getCommunicationLabel() {

		return $this->getSponsor()->abbreviation.': '.$this->getName().' ('.$this->email.')';

	}

	public function getCommunicationAdditionalRelations(): array
	{
		return [
			$this->getSponsor()
		];
	}
}
