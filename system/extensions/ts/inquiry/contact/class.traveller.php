<?php

use Core\Traits\WdBasic\MetableTrait;

/**
 * @method static \Ext_TS_Inquiry_Contact_TravellerRepository getRepository()
 */
class Ext_TS_Inquiry_Contact_Traveller extends Ext_TS_Inquiry_Contact_Abstract {
	use MetableTrait;

	protected $_sPlaceholderClass = 'Ext_TS_Inquiry_Contact_Traveller_Placeholder';
	
	/**
	 * @return string
	 */
	protected function _getType()
	{
		return 'traveller';
	}
	
	/**
	 * Der Index sortiert zuerst immer die Groß und dann die Kleinbuchstaben, darum indizieren wir
	 * in dieser Methode den Namen der Kontaktperson in Kleinbuchstaben, damit das sortieren unabhängig
	 * von der Klein-Großbbuchstaben funktioniert
	 * 
	 * @return string 
	 */
	public function getNameStrToLower()
	{
		$sName = $this->getName();
		
		$sName = strtolower($sName);
		
		return $sName;
	}

	public function getComment(){
		$sComment = (string)$this->getDetail('comment');
		return $sComment;
	}

	/**
	 * @param bool $bThrowExceptions
	 * @return array|bool
	 */
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if($mValidate === true) {
			$mValidate = array();
		}

		$sAlias = $this->_sTableAlias;

		if($this->bCheckGender) {

			$iGender = (int)$this->gender;
			$aGenders = (array)Ext_TC_Util::getGenders(false);

			if(!array_key_exists($iGender, $aGenders)) {
				$mValidate[$sAlias.'.gender'][] = 'NO_GENDER';
			}

		}

		$sFirstName	= trim($this->firstname);
		$sLastName = trim($this->lastname);

		if($this->bCheckGender) {
			if(strlen($sFirstName) < 1) {
				$mValidate[$sAlias.'.firstname'][] = 'EMPTY';
			}
			if(strlen($sLastName) < 1) {
				$mValidate[$sAlias.'.lastname'][] = 'EMPTY';
			}
		} else {
			if(
				strlen($sFirstName) < 1 &&
				strlen($sLastName) < 1
			) {
				$mValidate[$sAlias.'.lastname'][] = 'FIRSTNAME_AND_LASTNAME_EMPTY';
			}
		}

		if(empty($mValidate)) {
			$mValidate = true;
		}

		return $mValidate;
	}

	public function isLeader() {

		$sSQL = "
			SELECT
				`value`
			FROM
				`ts_journeys_travellers_detail`
			WHERE
				`traveller_id` = :traveller_id AND
				`type` = 'guide'
			LIMIT
				1
		";

		return DB::getQueryOne($sSQL, ['traveller_id' => $this->id]);
	}
	
}
