<?php
/* 
 *
 * 
 * Scheckzahlungen  ausgelesen und innerhalb einer Tabelle anzeigen:
 * (dabei werden Lehrer, Unterkünfte , Transfer  , manuelle Zahlungen sowie
 * Schülerrefunds[nur Ausgaben] berücksichtigt
 * Anschliessend kann nach vorherigen Druckvorgang der akutelle Status
 * gesetzt werden. 
 *
 * @created 25.01.11
 *
 * @property $id 	
 * @property $changed 	
 * @property $created 	
 * @property $active 	
 * @property $creator_id 	
 * @property $user_id 	
 * @property $cheque_number 	
 * @property $numberrange_id 	
 * @property $print_user_id 	
 * @property $print_user_created 	
 * @property $print_success 	
 * @property $type 	
 * @property $type_id 	
 * @property $school_id
 */
class Ext_Thebing_Accounting_Cheque extends  Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_cheque_payment';
	protected $_sTableAlias			= 'kcp';

	protected function getItemQueries($sItem, $iId=false) { 

		switch($sItem) {
			case 'accommodation':
				$sSql = " 
				SELECT
					kcp.id,
					kap.user_id,
					kap.created as created,
					kap.amount as amount,
					kap.payment_currency_id as currency_id,
					IF(db4.ext_68 != '', db4.ext_68, db4.ext_33) as recipient,
					'' as check_payment_amount_number_nocurrency,
					kap.comment as comment,
					kap.accommodation_id as source_id,
					kap.transaction_id,
					'accommodation' as type,
					kap.id as type_id,
					ts_ij.school_id as school_id,
					kap.user_id as creator_id,
					kcp.cheque_number,
					kcp.print_user_id as print_user,
					kcp.print_user_created as print_created,
					kcp.print_success as print_success,
				    `kap`.`inquiry_id`
				 FROM
					`kolumbus_accommodations_payments` `kap` JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`kap`.`inquiry_id` = `ts_ij`.`inquiry_id` AND
						`ts_ij`.`active` = 1 INNER JOIN
					`kolumbus_payment_method` kpm ON
						kap.method_id = kpm.id INNER JOIN
					`customer_db_4` db4 ON
						kap.accommodation_id = db4.id LEFT JOIN
					`kolumbus_cheque_payment` kcp ON
					(
						 kcp.type = 'accommodation'
							AND
						kcp.type_id = kap.id
					)

				 WHERE
					`kap`.`active` = 1 AND
					kpm.type = '".Ext_Thebing_Admin_Payment::TYPE_CHEQUE."' ";
				$sTableAlias = "kap";
			break;

			case 'teacher':
				$sSql = "
				SELECT
					kcp.id,
					ktp.user_id,
					ktp.created as created,
					ktp.amount as amount,
					ktp.payment_currency_id as currency_id,
					IF(kt.account_holder != '', kt.account_holder, concat(kt.lastname, ', ', kt.firstname)) as recipient,
					'' as check_payment_amount_number_nocurrency,
					ktp.comment as comment,
					ktp.teacher_id as source_id,
					ktp.transaction_id,
					'teacher' as type,
					ktp.id as type_id,
					kcp.school_id as school_id,
					ktp.user_id as creator_id,
					kcp.cheque_number,
					kcp.print_user_id as print_user,
					kcp.print_user_created as print_created,
					kcp.print_success as print_success,
				    '' as inquiry_id
				 FROM 
					`ts_teachers_payments` ktp INNER JOIN
					`kolumbus_payment_method` kpm ON
						ktp.method_id = kpm.id INNER JOIN
					`ts_teachers` kt ON
						ktp.teacher_id = kt.id LEFT JOIN
					`kolumbus_cheque_payment` kcp ON
					(
						 kcp.type = 'teacher' AND
						kcp.type_id = ktp.id
					)
				 WHERE
					ktp.active = 1 AND
					kpm.type = '".Ext_Thebing_Admin_Payment::TYPE_CHEQUE."' ";
				$sTableAlias = "ktp";
			break;

			case 'transfer':
				$sSql = "
				SELECT
					kcp.id,
					ktrp.user_id,
					ktrp.created as created,
					ktrp.amount as amount,
					ktrp.payment_currency_id as currency_id,
					IF(kit.provider_type = 'accommodation', IF(db4.ext_68 != '', db4.ext_68, db4.ext_33), IF(kc.bank_account_holder != '', kc.bank_account_holder, kc.name)) as recipient,
					'' as check_payment_amount_number_nocurrency,
					ktrp.comment as comment,
					ktrp.inquiry_transfer_id as source_id,
					ktrp.transaction_id,
					'transfer' as type,
					ktrp.id as type_id,
					`ts_ij`.`school_id` as school_id,
					kit.user_id as creator_id,
					kcp.cheque_number,
					kcp.print_user_id as print_user,
					kcp.print_user_created as print_created,
					kcp.print_success as print_success,
       				`ts_ij`.`inquiry_id` as inquiry_id
				 FROM 
					`kolumbus_transfers_payments` ktrp JOIN
					`kolumbus_payment_method` kpm ON
						ktrp.method_id = kpm.id JOIN
					`ts_inquiries_journeys_transfers` kit ON
						ktrp.inquiry_transfer_id = kit.id JOIN 
					`ts_inquiries_journeys` `ts_ij` ON
						kit.journey_id = `ts_ij`.`id` LEFT JOIN
					`kolumbus_companies` kc ON
						kit.provider_id = kc.id AND
						kit.provider_type = 'provider' LEFT JOIN
					`customer_db_4` `db4` ON
						kit.provider_id = db4.id AND
						kit.provider_type = 'accommodation' LEFT JOIN
					`kolumbus_cheque_payment` kcp ON
						 kcp.type = 'transfer' AND
						kcp.type_id = ktrp.id
				 WHERE
					ktrp.active = 1 AND
					kpm.type = '".Ext_Thebing_Admin_Payment::TYPE_CHEQUE."' AND
					kit.provider_id != 0 AND
					kit.provider_type != ''";
				$sTableAlias = "ktrp";
			break;

			case 'refund':
				$sSql = "

				SELECT
					kcp.id,
					kip.editor_id,
					kip.created as created,					
					if(kip.amount_inquiry < 0, kip.amount_inquiry * (-1) , kip.amount_inquiry )  as amount,
					ts_i.currency_id as currency_id,
					CONCAT(tc_c.lastname, ',', tc_c.firstname) as recipient,
					'' as check_payment_amount_number_nocurrency,

					kip.comment as comment,
					'inquiry_transfer' as source_id,
					'transaction_id',
					'refund' as type,
					kip.id as type_id,
					ts_ij.school_id as school_id,
					kip.editor_id as creator_id,
					kcp.cheque_number,
					kcp.print_user_id as print_user,
					kcp.print_user_created as print_created,
					kcp.print_success as print_success,
				   	'' as inquiry_id
				FROM
					`kolumbus_inquiries_payments` kip INNER JOIN
					`kolumbus_inquiries_payments_documents` kipd ON
						kip.id = kipd.payment_id INNER JOIN
					`kolumbus_inquiries_documents` kid ON
						kipd.document_id = kid.id INNER JOIN
					`ts_inquiries` `ts_i` ON
					    `kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
						`kid`.`entity_id` = `ts_i`.`id` INNER JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
						`ts_ij`.`active` = 1 INNER JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
						`ts_i_to_c`.`type` = 'traveller' INNER JOIN
					`tc_contacts` `tc_c` ON
						`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
						`tc_c`.`active` = 1 INNER JOIN
					`kolumbus_payment_method` kpm ON
						kip.method_id = kpm.id LEFT JOIN
					`kolumbus_cheque_payment` kcp ON
						kcp.type = 'refund' AND
						kcp.type_id = kip.id
				WHERE
					kpm.type = '".Ext_Thebing_Admin_Payment::TYPE_CHEQUE."' AND
					kip.type_id = 3 AND
					kip.active = 1";
				$sTableAlias = "kip";

				break;

		}

		if($iId) {
			$sSql .= " AND `".$sTableAlias."`.`id` = ".(int)$iId;
		}

		return $sSql;
	}

	/**
	 *  Es sollen folgende Ausgabe-Informationen (nur für Scheckausgaben)
	 *  generiert werden
	 *
	 *  1) Lehrer
	 *  2) Unterkünfte
	 *  3) Tranfers 
	 *  4) Manuelle Zahlungen (Ausgaben) jeglicher Art
	 *  5) Schüler - Refunds (z.B Rückerstattung)
	 *
	 *  
	 */
	public function getListQueryData($oGui = NULL){


		$aQueryData = array();
		$sFormat = $this->_formatSelect();
		$aQueryData['data'] = array();


		$sAliasString = '';
		$sTableAlias = '';
		if(!empty($this->_sTableAlias)) {
			$sAliasString .= '`'.$this->_sTableAlias.'`.';
			$sTableAlias .= '`'.$this->_sTableAlias.'`';
		}

		$aQueryData['sql'] = "
				SELECT
					* {FORMAT}
				FROM
					`{TABLE}` ".$sTableAlias."
			";

		if(array_key_exists('active', $this->_aData)) {
			$aQueryData['sql'] .= " WHERE ".$sAliasString."`active` = 1 ";
		}

		if(array_key_exists('id', $this->_aData)) {
			$aQueryData['sql'] .= "ORDER BY ".$sAliasString."`id` ASC ";
		}

		
		$sQuery = "

			SELECT * FROM (

				(
				".$this->getItemQueries('accommodation')."
                 )

				  UNION

				 (
				  ".$this->getItemQueries('teacher')."
				) 

				 UNION

				 (
				 ".$this->getItemQueries('transfer')."
				 )

				UNION

				(
				".$this->getItemQueries('refund')."
				)


				 ) as result " ;

		$aQueryData['sql'] = $sQuery;


		//$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		//$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']); // ?
		
		return $aQueryData;
	}


	/**
	 *  liefert Zahlungsinformationen (Typ und id)
	 * @return <array>
	 */
	public function getAdditionalInfo(){

		$aInfo = array();

		if(
			$this->type &&
			$this->type_id
		) {
			$sSql = $this->getItemQueries($this->type, $this->type_id);

			$aInfo = DB::getQueryRow($sSql);
		}
		return $aInfo;
	}

}
