<?php

/**
 * Class Ext_Thebing_AgencyRepository
 */
class Ext_Thebing_AgencyRepository extends \WDBasic_Repository {

	/**
	 * Prüft ob die Agentur mit dem gesuchten Namen existiert.
	 *
	 * @param string $sName
	 * @return bool
	 */
	public function hasAgencyWithName($sName) {

		$oAgency = $this->findAgencyByName($sName);

		if($oAgency !== null) {
			return true;
		}

		return false;

	}

	/**
	 * Sucht eine Agentur mit dem gesuchten Name.
	 *
	 * @param string $sName
	 * @return Ext_Thebing_Agency|null
	 */
	public function findAgencyByName($sName) {

		$oAgency = Ext_Thebing_Agency::query()
			->where('ext_1', $sName)
			->first()
		;

		return $oAgency;
	}

	/**
	 * Prüft ob die angegebene Agentur mehr Mitarbeiter als den angegebenen hat.
	 * Wenn ein Kontakt bei Hubspot gelöscht wird, muss er hier ebenfalls gelöscht werden.
	 * Da in Hubspot aber der Agenturname direkt bei den Kontakten gepflegt wird muss geprüft werden,
	 * ob der zu löschende Mitarbeiter der letzte Mitarbeiter der Agentur ist, wenn ja dann muss die Agentur ebenfalls
	 * gelöscht werden, weil sie dementsprechend auch nicht mehr bei Hubspot existiert.
	 *
	 * @param Ext_Thebing_Agency $oAgency
	 * @param Ext_Thebing_Agency_Contact $oContact
	 * @return bool
	 */
	public function hasOtherContacts(Ext_Thebing_Agency $oAgency, Ext_Thebing_Agency_Contact $oContact) {

		$oDb = DB::getDefaultConnection();

		$sSql = "
			SELECT
				COUNT(`ts_ac`.`id`)
			FROM
				`ts_companies_contacts` as `ts_ac`
			WHERE
				`ts_ac`.`company_id` = :company_id AND
				`ts_ac`.`id` != :contact_id
		";

		$aResult = $oDb->preparedQueryData($sSql, [
			'company_id' => (int)$oAgency->id,
			'contact_id' => (int)$oContact->id,
		]);

		if(!empty($aResult)) {
			return true;
		}

		return false;

	}

	/**
	 * Gibt eine Agentur zurück anhand des Aktivierungscodes
	 *
	 * @param string $sCode
	 *
	 * @return null|Ext_Thebing_Agency
	 */
	public function getAgencyByActivationCode($sCode) {

		$oDb = DB::getDefaultConnection();

		$sSql = "
			SELECT
				`agency_id`
			FROM
				`ts_agencies_activation_codes`
			WHERE
				`activation_code` = :sActivationCode
		";

		$aResult = $oDb->preparedQueryData($sSql, [
			'sActivationCode' => $sCode,
		]);

		if (empty($aResult)) {
			return null;
		}

		$aRow = reset($aResult);
		return Ext_Thebing_Agency::getInstance($aRow['agency_id']);

	}

	public function getContactByActivationCode($sCode) {

        $oDb = DB::getDefaultConnection();

        $sSql = "
			SELECT
				`contact_id`
			FROM
				`ts_agencies_activation_codes`
			WHERE
				`activation_code` = :activation_code
		";

        $iContactId = $oDb->queryOne($sSql, [
            'activation_code' => $sCode,
        ]);

        if (empty($iContactId)) {
            return null;
        }

        return Ext_Thebing_Agency_Contact::getInstance($iContactId);
    }

	/**
	 * Prüft, ob für diese Agentur Buchungen existieren
	 *
	 * @param Ext_Thebing_Agency $oAgency
	 *
	 * @return bool
	 */
    public function hasInquiries(Ext_Thebing_Agency $oAgency) {

		$sSql = "
			SELECT
				`id`
			FROM
				`ts_inquiries`
			WHERE
				`agency_id` = :agency_id AND
				`active` = 1 AND 
			    `canceled` = 0
			LIMIT 
				1
		";

		$aInquiries = (array)DB::getPreparedQueryData($sSql, ['agency_id' => $oAgency->id]);

		if(!empty($aInquiries)) {
			return true;
		}

		return false;
	}

}
