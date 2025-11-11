<?php

/**
 * Beschreibt einen Special-Block
 */
class Ext_Thebing_Inquiry_Special_Position extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_inquiries_positions_specials';
	
	protected $_sTableAlias = 'kips';

	/**
	 * Liefert das zugehörige Special
	 *
	 * @return Ext_Thebing_School_Special|null
	 * @throws Exception
	 */
	public function getSpecial() {
		$oSpecial = Ext_Thebing_School_Special::getInstance($this->special_id);

		// Prüfen ob Special noch gültig ist und nicht gelöscth wurde zwischen Buchungseingang und Rechnungs-erstellung
		if($oSpecial->active == 1) {
			return $oSpecial;
		} else {
			return null;
		}
	}

	public function getTypeObject() {
		
		$object = null;
		
		switch($this->type) {
			case 'accommodation':
				$object = \Ext_TS_Inquiry_Journey_Accommodation::getInstance($this->type_id);
				break;
			case 'course':
				$object = \Ext_TS_Inquiry_Journey_Course::getInstance($this->type_id);
				break;
			case 'transfer':
				$object = \Ext_TS_Inquiry_Journey_Transfer::getInstance($this->type_id);
				break;
			case 'additional_course':
				$object = \Ext_Thebing_School_Additionalcost::getInstance($this->type_id);
				$object->type = \Ext_Thebing_School_Additionalcost::TYPE_COURSE;
				break;
			case 'additional_accommodation':
				$object = \Ext_Thebing_School_Additionalcost::getInstance($this->type_id);
				$object->type = \Ext_Thebing_School_Additionalcost::TYPE_ACCOMMODATION;
				break;
			default:
				break;			
		}
		
		return $object;
	}
	
	/**
	 * Liefert den zugehörigen Block
	 *
	 * @return Ext_Thebing_Special_Block_Block
	 */
	public function getBlock() {
		return Ext_Thebing_Special_Block_Block::getInstance($this->special_block_id);
	}

	// ===================================================================================
	// Statische Funktionen
	// ===================================================================================

	/**
	 * Sucht eine oder mehrere Inquiry Special Positions object zu einer Buchungsposition
	 *
	 * @param string $sType
	 * @param int $iTypeId
	 * @param Ext_TS_Inquiry_Abstract $oInquiry
	 * @param bool $bIgnoreUsed
	 * @return Ext_Thebing_Inquiry_Special_Position[]
	 */
	public static function search($sType, $iTypeId, Ext_TS_Inquiry_Abstract $oInquiry, $bIgnoreUsed = false){

		$inquirySpecials = $oInquiry->getSpecials();

		$matchingSpecials = [];

		/** @var \Ts\Model\Special\InquirySpecial $specialPosition */
		foreach($inquirySpecials as $inquirySpecial) {

			if(
				empty($inquirySpecial->object) ||
				(
					$inquirySpecial->getType() == $sType &&
					(
						(
							is_numeric($iTypeId) &&
							$inquirySpecial->object->id == $iTypeId
						) || 
						(
							// !is_numeric($iTypeId) && entfernt, da spl_object_hash numeric sein kann
							spl_object_hash($inquirySpecial->object) == $iTypeId
						)	
					)
				)
			) {
				$matchingSpecials[] = $inquirySpecial;
			}
			
		}

		return $matchingSpecials;
	}

	/**
	 * Markiert einen Eintrag, dass er als Special benutzt worden ist
	 *
	 * @param Ext_TS_Inquiry_Abstract $oInquiry
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @throws UnexpectedValueException
	 */
	public static function markPosition(Ext_TS_Inquiry_Abstract $oInquiry, $oItem) {

		if($oItem instanceof Ext_Thebing_Inquiry_Document_Version_Item) {
			
			$aRelationTable = $oInquiry->getSpecialPositionRelationTableData();
			$oRelationObject = $oInquiry->getSpecialRelationObject();

			$aSql = array(
				'inquiry_id' => (int)$oRelationObject->id,
				'type' => '',
				'type_id' => 0,
				'relation_table' => $aRelationTable['table'],
				'relation_column' => $aRelationTable['primary_key_field']
			);

			// Specials beziehen sich immer auf den Journey Service, daher wird das Parent-Item benötigt
			$oParentItem = $oItem->getParentItem();
			if($oParentItem instanceof Ext_Thebing_Inquiry_Document_Version_Item) {

				$aSql['type'] = $oParentItem->type;
				$aSql['type_id'] = (int)$oParentItem->type_id;

				$sSql = "
					UPDATE
						`kolumbus_inquiries_positions_specials` `kips` INNER JOIN
						#relation_table `spr` ON
							`spr`.`special_position_id` = `kips`.`id`
					SET
						`kips`.`used` = 1
					WHERE
						`spr`.#relation_column = :inquiry_id AND
						`kips`.`type` = :type AND
						`kips`.`type_id` = :type_id AND
						`kips`.`active` = 1
				";

				DB::executePreparedQuery($sSql, $aSql);
			}
			
			// Generelle Specials auch als benutzt markieren
			$sSql = "
				UPDATE
					`kolumbus_inquiries_positions_specials` `kips` INNER JOIN
					#relation_table `spr` ON
						`spr`.`special_position_id` = `kips`.`id`
				SET
					`kips`.`used` = 1
				WHERE
					`spr`.#relation_column = :inquiry_id AND
					`kips`.`type_id` = 0 AND
					`kips`.`type` = '' AND
					`kips`.`active` = 1
			";

			DB::executePreparedQuery($sSql, $aSql);

			// Benutzte Special Blöcke auslesen
			$sSql = "
				SELECT
						`id`
				FROM
					`kolumbus_inquiries_positions_specials` `kips` INNER JOIN
					#relation_table `spr` ON
						`spr`.`special_position_id` = `kips`.`id`
				WHERE
					`spr`.#relation_column = :inquiry_id AND
					`kips`.`active` = 1 AND (
						(
							`kips`.`type` = :type AND
							`kips`.`type_id` = :type_id
						) OR (
							`kips`.`type` = '' AND
							`kips`.`type_id` = 0
						)
					)
			";

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			foreach((array)$aResult as $aData) {
				// Neue Zwischentabelle befüllen
				$sSql = "
					REPLACE INTO
						`kolumbus_inquiries_documents_versions_items_specials`
					SET
						`item_id` = :item_id,
						`special_block_id` = :special_block_id
				";

				$aSql['item_id'] = (int)$oItem->id;
				$aSql['special_block_id'] = (int)$aData['id'];

				DB::executePreparedQuery($sSql, $aSql);
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getListQueryData($oGui = null) {
		global $_VARS;

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		
		$iSpecialId = (int)$_VARS['parent_gui_id'][0];
		
		$aQueryData = array();
		$aQueryData['data'] = array();

		$sInvoiceWithoutProformaTypes = Ext_Thebing_Inquiry_Document_Search::getTypeDataAsString('invoice_without_proforma');

		$aQueryData['sql'] = "SELECT
								`cdb1`.`lastname`,
								`cdb1`.`firstname`,
								`tc_c_n`.`number`									`customerNumber`,
								GROUP_CONCAT(DISTINCT `kid`.`document_number`) document_number,
								`ki`.`currency_id`									`currency_id`,
								`ts_i_j`.`school_id`								`school_id`,
								(
									## Neueste Version
									SELECT
										`kidv_2`.`id`
									FROM
										`kolumbus_inquiries_documents_versions` `kidv_2`
									WHERE
										`kidv_2`.`document_id` = `kid`.`id` AND
										`kidv_2`.`active` = 1
									ORDER BY
										`kidv_2`.`version` DESC
									LIMIT 1
								) `version_id`,
								(
									## Neueste Version
									SELECT
										`kidv_2`.`date`
									FROM
										`kolumbus_inquiries_documents_versions` `kidv_2`
									WHERE
										`kidv_2`.`document_id` = `kid`.`id` AND
										`kidv_2`.`active` = 1
									ORDER BY
										`kidv_2`.`version` DESC
									LIMIT 1
								) `date`,
								kips.id special_position_id,
								kid.id,
								ts_spc.code
								
							FROM
								`kolumbus_inquiries_positions_specials` `kips` INNER JOIN
								`ts_inquiries_to_special_positions` `ts_i_to_sp` ON
									`ts_i_to_sp`.`special_position_id` = `kips`.`id` INNER JOIN
								`kolumbus_inquiries_documents_versions_items_specials` `kidvis` ON
									`kidvis`.`special_block_id` = `kips`.`id` INNER JOIN
								`kolumbus_inquiries_documents_versions_items` `kidvi` ON
									`kidvi`.`id` = `kidvis`.`item_id` AND
									`kidvi`.`active` = 1 INNER JOIN
								`kolumbus_inquiries_documents_versions` `kidv` ON
									`kidv`.`id` = `kidvi`.`version_id` AND
									`kidv`.`active` = 1 INNER JOIN
								`kolumbus_inquiries_documents` `kid` ON
									`kid`.`id` = `kidv`.`document_id` AND
									`kid`.`active` = 1 /*AND (
										-- Rechnung immer der Proforma vorziehen
										`ki`.`has_invoice` = 0 OR
										`kid`.`type` IN (".$sInvoiceWithoutProformaTypes.")
									)*/ INNER JOIN
								`ts_inquiries` `ki` ON
									`ki`.`id` = `ts_i_to_sp`.`inquiry_id` AND
									`ki`.`active` = 1 INNER JOIN
								`ts_inquiries_journeys` `ts_i_j` ON
									`ts_i_j`.`inquiry_id` = `ki`.`id` AND
									`ts_i_j`.`active` = 1 AND
									`ts_i_j`.`school_id` = :school_id INNER JOIN
								`ts_inquiries_to_contacts` `ts_i_to_c` ON
									`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
									`ts_i_to_c`.`type` = 'traveller' INNER JOIN
								`tc_contacts` `cdb1` ON
									`cdb1`.`id` = `ts_i_to_c`.`contact_id` AND
									`cdb1`.`active` = 1 INNER JOIN
								`tc_contacts_numbers` `tc_c_n` ON
									`tc_c_n`.`contact_id` = `cdb1`.`id` LEFT JOIN	
								`ts_specials_codes_usages` `ts_spcu` ON
									`ts_spcu`.`inquiry_id` = `ki`.`id` LEFT JOIN					
								`ts_specials_codes` `ts_spc` ON
									`ts_spc`.`id` = `ts_spcu`.`code_id`
							WHERE
								`kips`.`used` = 1
							GROUP BY
								`kid`.`entity`,
								`kid`.`entity_id`
			";

		$aQueryData['data']['school_id'] = (int)$oSchool->id;
		$aQueryData['data']['special_id'] = (int)$iSpecialId;

		return $aQueryData;
	}

}