<?php

namespace TsPrivacy\Service;

class EntityCheck {

	/**
	 * @var array
	 */
	private $aEntityClasses = [
//		'Ext_TS_Enquiry' => 'enquiry',
		'Ext_TS_Inquiry' => 'inquiry',
		'Ext_Thebing_Accommodation' => 'provider',
		'Ext_Thebing_Teacher' => 'provider',
		'Ext_Thebing_Pickup_Company' => 'provider'
	];

	/**
	 * @return array
	 */
	public function getEntityClasses() {
		return $this->aEntityClasses;
	}

	/**
	 * Entity überprüfen
	 *
	 * @param string $sEntity
	 * @param int $iEntityId Konkreten Eintrag einzeln überprüfen, ansonsten alles was zutrifft
	 * @param bool $bPrepare
	 * @return array
	 */
	public function checkEntity($sEntity, $iEntityId = null, $bPrepare = false) {

		$bCheckContact = false;
		switch($sEntity) {
//			case 'Ext_TS_Enquiry':
//				$bCheckContact = true;
//				$aResult = $this->runEnquiryQuery($iEntityId, $bPrepare);
//				break;
			case 'Ext_TS_Inquiry':
				$bCheckContact = true;
				$aResult = $this->runInquiryQuery($iEntityId, $bPrepare);
				break;
			case 'Ext_Thebing_Accommodation':
			case 'Ext_Thebing_Teacher':
			case 'Ext_Thebing_Pickup_Company';
				$aResult = $this->runProviderQuery($sEntity, $iEntityId, $bPrepare);
				break;
			default:
				throw new \InvalidArgumentException('Unknown entity');
		}

		if($bCheckContact) {
			foreach($aResult as $iKey => $aRow) {
				$mCheckContact = $this->checkInquiryContact($sEntity, $aRow['contact_id']);
				if($mCheckContact === false) {
					unset($aResult[$iKey]);
				} else {
					$aResult[$iKey] = array_merge($aResult[$iKey], $mCheckContact);
				}
			}
		}

		return $aResult;

	}

	/**
	 * Kontakt kann durch Umwandeln, Kundenerkennung und Verknüpfung diverse Verknüpfungen haben
	 *
	 * @TODO Eigene ID (je nach Entität) ausschließen
	 *
	 * @param int $iContactId
	 * @return array|false
	 */
	private function checkInquiryContact($sEntity, $iContactId) {

		$sEntityClass = $sEntity; /** @var \TsPrivacy\Interfaces\Entity $sEntityClass */
		$aSettings = $sEntityClass::getPurgeSettings();

		// Wenn eine ältere Buchung bereits anonymisiert ist, würde ohne die Abfrage diese immer wieder als aktiv zutreffen,
		// da runInquiryQuery bereits anonymized = 0 abfragt
		$sWhereInquiry = $sWhereEnquiry = "";
		if($aSettings['action'] === 'anonymize') {
			$sWhereInquiry = " AND `ts_i`.`anonymized` = 0 ";
			$sWhereEnquiry = " AND `ts_e`.`anonymized` = 0 ";
		}

		$sSql = "
			SELECT
				GROUP_CONCAT(DISTINCT `ts_i`.`id`) `inquiry_ids`,
				/*GROUP_CONCAT(DISTINCT `ts_e`.`id`) `enquiry_ids`,*/
				IF(
					`tc_cn`.`number` IS NULL,
					CONCAT(`tc_c`.`lastname`, ', ', `tc_c`.`firstname`),
					`tc_cn`.`number`
				) `label`
			FROM
				`tc_contacts` `tc_c` LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`contact_id` = `tc_c`.`id` AND
					`ts_itc`.`type` = 'traveller' LEFT JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_itc`.`inquiry_id` 
					{$sWhereInquiry} /*LEFT JOIN
				`ts_enquiries_to_contacts` `ts_etc` ON
					`ts_etc`.`contact_id` = `tc_c`.`id` AND
					`ts_etc`.`type` = 'booker' LEFT JOIN
				`ts_enquiries` `ts_e` ON
					`ts_e`.`id` = `ts_etc`.`enquiry_id`
					{$sWhereEnquiry}*/
			WHERE
				`tc_c`.`id` = :contact_id
			GROUP BY
				`tc_c`.`id`
		";

		$aRow = (array)\DB::getQueryRow($sSql, [
			'contact_id' => $iContactId
		]);

		// Wenn Enquiry Inquiries hat, aber Inquiry keine Privacy-Einstellungen hat, ist das immer false
		if(!empty($aRow['inquiry_ids'])) {
			$aInquiryIds = explode(',', $aRow['inquiry_ids']);
			foreach($aInquiryIds as $iInquiryId) {
				if(empty($this->runInquiryQuery($iInquiryId))) {
					// empty: Bedingung nicht zugetroffen auf Inquiry, daher noch gültig
					return false;
				}
			}
		}

//		if(!empty($aRow['enquiry_ids'])) {
//			$aEnquiryIds = explode(',', $aRow['enquiry_ids']);
//			foreach($aEnquiryIds as $iEnquiryId) {
//				if(empty($this->runEnquiryQuery($iEnquiryId))) {
//					// empty: Bedingung nicht zugetroffen auf Enquiry, daher noch gültig
//					return false;
//				}
//			}
//		}

		return $aRow;

	}

	/**
	 * @param int $iEnquiryId
	 * @param bool $bPrepare
	 * @return array
	 */
	/*private function runEnquiryQuery($iEnquiryId = null, $bPrepare = false) {

		$aSettings = \Ext_TS_Enquiry::getPurgeSettings();

		if(!in_array($aSettings['action'], ['anonymize', 'delete'])) {
			return [];
		}

		$sWhere = "";

		$dDate = $this->createDate($aSettings, $bPrepare);
		if($dDate === null) {
			return [];
		}

		if($aSettings['basedon'] === 'created_date') {
			$sSelect = ", `ts_e`.`created` `date` ";
			$sWhere = " AND `ts_e`.`created` < :date ";
		} elseif($aSettings['basedon'] === 'document_date') {
			$sSelect = ", IF(MAX(`kidv`.`date`) > `ts_e`.`created`, MAX(`kidv`.`date`), `ts_e`.`created`) `date` ";
			$sHaving = " `date` < :date ";
		} else {
			throw new \RuntimeException('Unknown based on! '.$aSettings['basedon']);
		}

		if($aSettings['action'] === 'anonymize') {
			$sWhere .= " AND `ts_e`.`anonymized` = 0 ";
		}

		if($iEnquiryId !== null) {
			$sWhere .= " AND `ts_e`.`id` = :enquiry_id ";
		}

		$sSql = "
			SELECT
				`ts_e`.`id`,
				`ts_etc`.`contact_id`
				{$sSelect}
			FROM
				`ts_enquiries` `ts_e` LEFT JOIN
				`ts_enquiries_to_documents` `ts_etd` ON
					`ts_etd`.`enquiry_id` = `ts_e`.`id` LEFT JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `ts_etd`.`document_id` LEFT JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kid`.`latest_version` LEFT JOIN
				`ts_enquiries_to_contacts` `ts_etc` ON
					`ts_etc`.`enquiry_id` = `ts_e`.`id` AND
					`ts_etc`.`type` = 'booker'
			WHERE
				1
				{$sWhere}
			GROUP BY
				`ts_e`.`id`
		";

		if(!empty($sHaving)) {
			$sSql .= " HAVING ".$sHaving;
		}

		return (array)\DB::getQueryRows($sSql, [
			'date' => $dDate->format('Y-m-d'),
			'enquiry_id' => $iEnquiryId
		]);

	}*/

	/**
	 * @param int $iInquiryId
	 * @param bool $bPrepare
	 * @return array
	 */
	private function runInquiryQuery($iInquiryId = null, $bPrepare = false) {

		$aSettings = \Ext_TS_Inquiry::getPurgeSettings();

		if(!in_array($aSettings['action'], ['anonymize', 'delete'])) {
			return [];
		}

		$dDate = $this->createDate($aSettings, $bPrepare);
		if($dDate === null) {
			return [];
		}

		$sWhere = "";

		if($aSettings['basedon'] === 'created_date') {
			$sSelect = ", `ts_i`.`created` `date` ";
			$sWhere = " AND `ts_i`.`created` < :date ";
		} elseif($aSettings['basedon'] === 'document_date') {
			$sSelect = ", IF(MAX(`kidv`.`date`) > `ts_i`.`created`, MAX(`kidv`.`date`), `ts_i`.`created`) `date` ";
			$sHaving = " `date` < :date ";
		} elseif($aSettings['basedon'] === 'service_until_date') {
			$sSelect = ", `ts_i`.`service_until` `date` ";
			$sWhere = " AND `ts_i`.`service_until` != '0000-00-00' AND `ts_i`.`service_until` < :date ";
		} else {
			throw new \RuntimeException('Unknown based on! '.$aSettings['basedon']);
		}

		if($aSettings['action'] === 'anonymize') {
			$sWhere .= " AND `ts_i`.`anonymized` = 0 ";
		}

		if($iInquiryId !== null) {
			$sWhere .= " AND `ts_i`.`id` = :inquiry_id ";
		}

		$sSql = "
			SELECT
				`ts_i`.`id`,
				`ts_itc`.`contact_id`
				{$sSelect}
			FROM
				`ts_inquiries` `ts_i` LEFT JOIN
				`kolumbus_inquiries_documents` `kid` ON
				    `kid`.`entity` = '".\Ext_TS_Inquiry::class."' AND
					`kid`.`entity_id` = `ts_i`.`id` LEFT JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kid`.`latest_version` LEFT JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller'
			WHERE
				1
				{$sWhere}
			GROUP BY
				`ts_i`.`id`
		";

		if(!empty($sHaving)) {
			$sSql .= " HAVING ".$sHaving;
		}

		return (array)\DB::getQueryRows($sSql, [
			'date' => $dDate->format('Y-m-d'),
			'inquiry_id' => $iInquiryId
		]);

	}

	/**
	 * @param string $sEntity
	 * @param int  $iEntityId
	 * @param bool $bPrepare
	 * @return array
	 */
	private function runProviderQuery($sEntity, $iEntityId = null, $bPrepare = false) {

		$sEntityClass = $sEntity; /** @var \TsPrivacy\Interfaces\Entity $sEntityClass */
		$aSettings = $sEntityClass::getPurgeSettings();

		if(!in_array($aSettings['action'], ['anonymize', 'delete'])) {
			return [];
		}

		$dDate = $this->createDate($aSettings, $bPrepare);
		if($dDate === null) {
			return [];
		}

		/** @var \WDBasic $oEntity */
		$oEntity = new $sEntity();
		$sTable = $oEntity->getTableName();

		$sSelect = ", CONCAT(`lastname`, ', ', `firstname`) `label` ";
		if($sEntity === 'Ext_Thebing_Accommodation') {
			$sSelect = ", `ext_33` `label` ";
		}

		$sWhere = "";
		if($aSettings['action'] === 'anonymize') {
			$sWhere .= " AND `anonymized` = 0 ";
		}

		$sSql = "
			SELECT
				`id`,
				IF(`valid_until` != '0000-00-00', `valid_until`, `changed`) `date`
				{$sSelect}
			FROM
				`{$sTable}`
			WHERE
				(
					(
						`valid_until` != '0000-00-00' AND
						`valid_until` < :date
					) OR (
						`active` = 0 AND
						`changed` < :date
					)
				)
				{$sWhere}
		";

		if($iEntityId !== null) {
			$sSql .= " AND `id` = :entity_id ";
		}

		return (array)\DB::getQueryRows($sSql, [
			'date' => $dDate->format('Y-m-d'),
			'entity_id' => $iEntityId
		]);

	}

	/**
	 * @param string $sType
	 * @param bool $bPrepare
	 * @return \DateTime|null
	 */
	private function createDate(array $aSettings, $bPrepare = false) {

		$sUnit = $aSettings['unit'];
		$iQuantity = (int)$aSettings['quantity'];
		if(empty($iQuantity)) {
			return null;
		}

		$aUnitMapping = [
			'weeks' => 'W',
			'months' => 'M',
			'years' => 'Y'
		];

		if(!isset($aUnitMapping[$sUnit])) {
			throw new \RuntimeException('Invalid unit: '.$sUnit);
		}

		$sInterval = 'P'.$iQuantity.$aUnitMapping[$sUnit];
		$oInterval = new \DateInterval($sInterval);

		$dDate = new \DateTime();
		$dDate->sub($oInterval);

		if($bPrepare) {
			$dDate->add(new \DateInterval('P1W'));
		}

		return $dDate;

	}

}
