<?php

/**
 * Dieser Check konvertiert die Einträge aus kolumbus_inquiries_payments_creditnotes um,
 * und schreibt diese CN-Payments in die kolumbus_inquiries_payments plus Aufteilung des Betrags
 * pro Dokumentenposition der Creditnote.
 *
 * https://redmine.thebing.com/redmine/issues/5976
 */
class Ext_TS_System_Checks_Payment_ConvertCreditnotes extends GlobalChecks {

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Convert old creditnotes';
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return 'Convert old creditnotes into new database table';
	}

	/**
	 * @return bool
	 */
	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		/*
		 * Wenn die Tabelle 'kolumbus_inquiries_payments_creditnotes' nicht
		 * mehr existiert, ist der Check schonmal ausgeführt worden.
		 */
		if(!Util::checkTableExists('kolumbus_inquiries_payments_creditnotes')) {
			return true;
		}

		$aBackup[] = Util::backupTable('kolumbus_inquiries_payments');
		$aBackup[] = Util::backupTable('kolumbus_inquiries_payments_items');
		$aBackup[] = Util::backupTable('kolumbus_inquiries_payments_creditnotes');
		$aBackup[] = Util::backupTable('kolumbus_inquiries_documents_creditnote');
		$aBackup[] = Util::backupTable('ts_inquiries_payments_to_creditnote_payments');
		$aBackup[] = Util::backupTable('ts_documents_to_inquiries_payments');

		if(in_array(false, $aBackup)) {
			return false;
		}

		try {

			DB::begin(__CLASS__);

			/*
			 * Es werden alle Creditnote Auszahlungen gesucht, welche zu einem
			 * Payment verknüpft sind welches nicht mehr aktiv ist. Dieses Ergebnis
			 * wird per E-Mail an support@thebing.com gesendet.
			 */
			$sSql = "
				SELECT
					CONCAT(`tc_c`.`lastname`, `tc_c`.`firstname`) `customer_name`,
					`tc_cn`.`number` `customer_number`,
					`kip`.`amount_inquiry` `payment_amount`,
					`kid`.`document_number` `creditnote_document_number`,
					`cdb2`.`ext_1` `school_name`
				FROM
					`kolumbus_inquiries_payments_creditnotes` `kipc` INNER JOIN
					`kolumbus_inquiries_payments` `kip` ON
						`kip`.`id` = `kipc`.`payment_id` AND
						`kip`.`active` = 0 INNER JOIN
					`kolumbus_inquiries_documents` `kid` ON
						`kid`.`id` = `kipc`.`document_id` AND
						`kid`.`active` = 1 INNER JOIN
					`ts_inquiries` `ts_i` ON
						`ts_i`.`id` = `kid`.`inquiry_id` AND
						`ts_i`.`active` = 1 INNER JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
						`ts_ij`.`active` = 1 INNER JOIN
					`customer_db_2` `cdb2` ON
						`cdb2`.`id` = `ts_ij`.`school_id` AND
						`cdb2`.`active` = 1 INNER JOIN
					`ts_inquiries_to_contacts` `ts_itc` ON
						`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
						`ts_itc`.`type` = 'traveller' INNER JOIN
					`tc_contacts` `tc_c` ON
						`tc_c`.`id` = `ts_itc`.`contact_id` AND
						`tc_c`.`active` = 1 INNER JOIN
					`tc_contacts_numbers` `tc_cn` ON
						`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
					`kolumbus_agencies` `ka` ON
						`ka`.`id` = `ts_i`.`agency_id`
				WHERE
					`kipc`.`active` = 1
				ORDER BY
					`school_name`
			";

			$aSchools = array();
			$aOutputRows = array();

			$aRows = DB::getPreparedQueryData($sSql, array());

			foreach($aRows as $aRow) {
				if(!isset($aSchools[$aRow['school_name']])) {
					$aSchools[$aRow['school_name']] = true;
					$aOutputRows[] = "";
					$aOutputRows[] = '<b>'.$aRow['school_name'].'</b>';
				}
				$aOutputRows[] = "Student: ".$aRow['customer_name']." (".$aRow['customer_number'].") => Credit Note: ".$aRow['creditnote_document_number']. " => Amount: ".$aRow['payment_amount'];
			}

			if(!empty($aOutputRows)) {

				$sMessage = "Conversion Credit Note: ".$_SERVER['HTTP_HOST'];
				$sMessageBody = implode('<br />', $aOutputRows);

				if(Ext_Thebing_Util::isDevSystem()) {
					echo $sMessage.'<br />';
					echo $sMessageBody;
				}

				$oMail = new WDMail();
				$oMail->subject = $sMessage;
				$oMail->html = $sMessageBody;
				$oMail->send('marketing@thebing.com');

			}

			/*
			 * Alle alten Creditnote Einträge müssen in die neue Struktur konvertiert werden.
			 *
			 * Es müssen folgende Datensätze geschrieben werden:
			 * 1. kolumbus_inquiries_payments (type_id = 4)
			 * 2. ts_documents_to_inquiries_payments
			 * 3. ts_inquiries_payments_to_creditnote_payments
			 * 4. kolumbus_inquiries_payments_items
			 */
			$sSql = "
				SELECT
					`kipc`.*,
					`kipc`.`id` `kipc_id`,
					`kip`.`creator_id`,
					`kip`.`user_id`,
					`kip`.`inquiry_id`,
					`kip`.`method_id`,
					`kip`.`sender`,
					`kip`.`receiver`,
					`kip`.`grouping_id`,
					`kip`.`date`,
					`kip`.`comment`
				FROM
					`kolumbus_inquiries_payments_creditnotes` `kipc` INNER JOIN
					`kolumbus_inquiries_payments` `kip` ON
						`kip`.`id` = `kipc`.`payment_id` AND
						`kip`.`active` = 1
				WHERE
					`kipc`.`active` = 1
			";

			$aCreditnoteRows = DB::getPreparedQueryData($sSql, array());

			foreach($aCreditnoteRows as $aCreditnoteRow) {

				$sSender = $aCreditnoteRow['sender'];
				$sReceiver = $aCreditnoteRow['receiver'];

				if(
					empty($sSender) &&
					empty($sReceiver)
				) {
					$sSender = 'school';
					$sReceiver = 'agency';
				}

				// 1. kolumbus_inquiries_payments (type_id = 4)
				$aData = array(
					'changed' => $aCreditnoteRow['changed'],
					'creator_id' => $aCreditnoteRow['creator_id'],
					'user_id' => $aCreditnoteRow['user_id'],
					'inquiry_id' => $aCreditnoteRow['inquiry_id'],
					'method_id' => $aCreditnoteRow['method_id'],
					'sender' => $sSender,
					'receiver' => $sReceiver,
					'grouping_id' => $aCreditnoteRow['grouping_id'],
					'date' => $aCreditnoteRow['date'],
					'comment' => $aCreditnoteRow['comment'],
					'type_id' => 4,
					'amount_inquiry' => $aCreditnoteRow['amount'] * -1,
					'amount_school' => $aCreditnoteRow['amount_school'] * -1
				);

				$iCreditnotePaymentId = DB::insertData('kolumbus_inquiries_payments', $aData);

				$this->logInfo('Created kolumbus_inquiries_payments '.$iCreditnotePaymentId.' (from '.$aCreditnoteRow['kipc_id'].')', $aData);

				// 2. ts_documents_to_inquiries_payments
				$sSql = "
					REPLACE INTO
						`ts_documents_to_inquiries_payments`
					SET
						`document_id` = :document_id,
						`payment_id` = :payment_id
				";

				$aSql = array(
					'document_id' => $aCreditnoteRow['document_id'],
					'payment_id' => $iCreditnotePaymentId
				);

				DB::executePreparedQuery($sSql, $aSql);

				$this->logInfo('Create ts_documents_to_inquiries_payments', $aSql);

				/*
				 * 3. ts_inquiries_payments_to_creditnote_payments
				 *
				 * In die Tabelle 'ts_inquiries_payments_to_creditnote_payments' dürfen nur Creditnotes eingetragen werden,
				 * welche mit einer Agenturzahlung verrechnet wurden. Der alte Bezahldialog (Provisionen ausbezahlen)
				 * hatte keinen Sender und Receiver gesetzt. Daher sind alle Einträge mit einem Sender und Receiver eine
				 * Verrechnung mit einer Agenturzahlung.
				 */
				if(
					$aCreditnoteRow['sender'] != '' &&
					$aCreditnoteRow['receiver'] != ''
				) {

					$aData = array(
						'payment_id' => $aCreditnoteRow['payment_id'],
						'creditnote_payment_id' => $iCreditnotePaymentId
					);

					DB::insertData('ts_inquiries_payments_to_creditnote_payments', $aData);

					$this->logInfo('Create ts_inquiries_payments_to_creditnote_payments', $aData);

				}

				/*
				 * 4. kolumbus_inquiries_payments_items
				 *
				 * Der alte Creditnote Betrag muss auf die Items aufgeteilt werden. Pro Item muss ein ein
				 * Datensatz in der 'kolumbus_inquiries_payments_items' Tabelle angelegt werden. Da es allerdings zwei Amount-Spalten
				 * gibt (InquiryAmount und SchoolAmount) und diese unterschiedliche Währungen haben kann, wird der SchoolAmount
				 * mithilfe eines Faktors berechnet.
				 */
				$sSql = "
					SELECT
						`kidvi`.`id`,
						`kidvi`.`position`,
						`kidvi`.`amount_provision`,
						`ts_i`.`currency_id` `inquiry_currency`,
						`cdb2`.`currency` `school_currency`
					FROM
						`kolumbus_inquiries_documents_versions_items` `kidvi` INNER JOIN
						`kolumbus_inquiries_documents` `kid` ON
							`kid`.`id` = :id AND
							`kid`.`latest_version` = `kidvi`.`version_id` AND
							`kid`.`active` = 1 INNER JOIN
						`ts_inquiries` `ts_i` ON
							`ts_i`.`id` = `kid`.`inquiry_id` AND
							`ts_i`.`active` = 1 INNER JOIN
						`ts_inquiries_journeys` `ts_ij` ON
							`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
							`ts_ij`.`active` = 1 INNER JOIN
						`customer_db_2` `cdb2` ON
							`cdb2`.`id` = `ts_ij`.`school_id`
					WHERE
						`kidvi`.`active` = 1 AND
						`kidvi`.`onPdf` = 1 AND
						`kidvi`.`calculate` = 1
					ORDER BY
						`kidvi`.`position`
				";

				$aCreditnoteItemRows = DB::getPreparedQueryData($sSql, array(
					'id' => $aCreditnoteRow['document_id']
				));

				$fAmountCN = $aCreditnoteRow['amount'];
				$fAmountSchool = $aCreditnoteRow['amount_school'];

				$fFactor = 1;
				if($fAmountCN > 0) {
					$fFactor = $fAmountSchool / $fAmountCN;
				}

				// Minus-Beträge müssen beim Verteilen als erstes kommen
				usort($aCreditnoteItemRows, function($aItemA, $aItemB) {
					return bccomp($aItemA['amount_provision'], $aItemB['amount_provision'], 4);
				});

				foreach($aCreditnoteItemRows as $aCreditnoteItemRow) {

					$oVersionItem = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($aCreditnoteItemRow['id']);

					$fOpenAmount = round($aCreditnoteItemRow['amount_provision'], 2) + round($oVersionItem->getPayedAmount($aCreditnoteItemRow['inquiry_currency']), 2);

					if(Ext_Thebing_Util::compareFloat($fOpenAmount, 0) === 0) {
						continue;
					} elseif($fAmountCN >= $fOpenAmount) {
						// Kann voll bezahlt werden
						$fAmountInquiry = $fOpenAmount;
						// Noch zu verteilen
						$fAmountCN = bcsub($fAmountCN, $fOpenAmount, 2);
					} elseif(
						$fAmountCN < $fOpenAmount &&
						$fAmountCN > 0
					) {
						// Betrag reicht nur noch für dieses Item
						$fAmountInquiry = $fAmountCN;
						$fAmountCN = 0;
					} else {
						break;
					}

					$aData = array(
						'changed' => $aCreditnoteRow['changed'],
						'creator_id' => $aCreditnoteRow['creator_id'],
						'payment_id' => $iCreditnotePaymentId,
						'item_id' => $aCreditnoteItemRow['id'],
						'amount_inquiry' => $fAmountInquiry * -1,
						'amount_school' => round($fFactor * $fAmountInquiry, 2) * -1,
						'currency_inquiry' => $aCreditnoteItemRow['inquiry_currency'],
						'currency_school' => $aCreditnoteItemRow['school_currency']
					);

					DB::insertData('kolumbus_inquiries_payments_items', $aData);

					$this->logInfo('Create kolumbus_inquiries_payments_items', $aData);

				}

				WDBasic::clearAllInstances();

			}

			/*
			 * Löscht Payments, welche durch den alten
			 * Bezahldialog (Provision ausbezahlen) angelegt wurden. Da durch den Check neue Payments angelegt werden,
			 * können die alten Einträge gelöscht werden.
			 */
			$sSql = "
				DELETE FROM
					`kolumbus_inquiries_payments`
				WHERE
					`type_id` = 3 AND
					`sender` = '' AND
					`receiver` = '' AND
					`id` NOT IN (
							SELECT
								`payment_id`
							FROM
								`kolumbus_inquiries_payments_items`
						UNION
							SELECT
								`payment_id`
							FROM
								`kolumbus_inquiries_payments_overpayment`

					)
			";

			DB::executeQuery($sSql);

			$this->logInfo('Delete old kolumbus_inquiries_payments records');

			/*
			 * Da alle alten Creditnotes konvertiert wurden, kann
			 * die alte Tabelle ebenfalls gelöscht werden.
			 */
			DB::executeQuery("DROP TABLE kolumbus_inquiries_payments_creditnotes");

			$this->logInfo('Delete table kolumbus_inquiries_payments_creditnotes');

			/*
			 * Diese Tabelle scheint eine alte Tabelle zu sein und kann
			 * somit gelöscht werden. Daten wurden in ts_documents_to_documents übernommen.
			 */
			DB::executeQuery("DROP TABLE kolumbus_inquiries_documents_creditnote");

			$this->logInfo('Delete table kolumbus_inquiries_documents_creditnote');

			DB::commit(__CLASS__);

		} catch(Exception $ex) {

			DB::rollback(__CLASS__);

		}

		return true;
	}

}