<?php

/**
 * Verschollene Versionen finden und löschen, Payments retten #6239
 *
 * Dieser Check sucht anhand vorhanender Version Items fehlende Versionen in der Datenbank.
 * Die übrigen Daten werden dann gelöscht und bisherige Bezahlungen werden in Überbezahlungen umgewandelt.
 */
class Ext_Thebing_System_Checks_Documents_Versions_MissingVersions extends GlobalChecks {

	private $_aInquiries = array();
	private $_iFoundMissingVersions = 0;
	private $_aLostVersions = array();
	private $_aLog = array();

	public function getTitle() {
		return 'Check missing document versions and corresponding payments';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		Util::backupTable('kolumbus_inquiries_documents_versions_items');
		Util::backupTable('kolumbus_inquiries_payments_overpayment');
		Util::backupTable('kolumbus_inquiries_payments_items');

		DB::begin(__CLASS__);

		try {

			$this->fetchMissingVersions();
			$this->convertPaymentsToOverpayments();
			$this->removeVersions();
			$this->notify();

			DB::commit(__CLASS__);

		} catch(Exception $oException) {

			DB::rollback(__CLASS__);

			$this->logError($oException->getMessage());
			throw $oException;

		}

		Ext_Gui2_Index_Stack::executeCache();

		return true;

	}

	/**
	 * Alle Dokument-Versionen ermitteln, welche nicht in der Datenbank existieren
	 * Dies geschieht über die entsprechenden Version-Items
	 */
	private function fetchMissingVersions() {

		$iCounter = 1;
		
		// Alle Items ermitteln, welche zu einer Version gehören, die nicht existiert
		$sSql = "
			SELECT
				`kidvi`.*
			FROM
				`kolumbus_inquiries_documents_versions_items` `kidvi` LEFT JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kidvi`.`version_id`
			WHERE
				`kidvi`.`active` = 1 AND
				`kidv`.`id` IS NULL

		";

		$aResult = (array)DB::getQueryRows($sSql);

		// Items nach Version gruppieren
		$aMissingVersions = array();
		foreach($aResult as $aItem) {
			$aMissingVersions[$aItem['version_id']]['id'] = (int)$aItem['version_id'];
			$aMissingVersions[$aItem['version_id']]['created'] = new DateTime($aItem['created']);
			$aMissingVersions[$aItem['version_id']]['items'][] = $aItem;
		}

		// Inquiry pro Version ermitteln
		foreach($aMissingVersions as $aMissingVersion) {
			$oInquiry = null;

			// Versuchen, Inquiry über Items zu ermitteln
			foreach($aMissingVersion['items'] as $aItem) {
				switch($aItem['type']) {
					case 'course':
						$oService = Ext_TS_Inquiry_Journey_Course::getInstance($aItem['type_id']);
						$oInquiry = $oService->getJourney()->getInquiry();
						break;
					case 'accommodation':
						$oService = Ext_TS_Inquiry_Journey_Accommodation::getInstance($aItem['type_id']);
						$oInquiry = $oService->getJourney()->getInquiry();
						break;
					case 'transfer':

						if($aItem['type_id'] > 0) {
							// Type-ID direkt verwenden
							$iJourneyTransferId = $aItem['type_id'];
						} else {
							// In alten Items steht in item_id nichts drin, aber ggf. in additional_info
							$aJsonData = json_decode($aItem['additional_info'], true);
							if($aJsonData) {
								// Erstbeste Journey-Transfer-ID nehmen
								foreach(array('transfer_arrival_id', 'transfer_departure_id') as $sField) {
									if(!empty($aJsonData[$sField])) {
										$iJourneyTransferId = $aJsonData[$sField];
										break;
									}
								}
							}
						}

						if(!empty($iJourneyTransferId)) {
							$oService = Ext_TS_Inquiry_Journey_Transfer::getInstance($iJourneyTransferId);
							$oInquiry = $oService->getJourney()->getInquiry();
						}

						break;
				}

				// Inquiry wurde gefunden
				if($oInquiry instanceof Ext_TS_Inquiry) {
					break;
				}
			}

			// Versuchen, Inquiry über Bezahlbelege zu ermitteln
			// Es gibt hier eine 1:1-Tabelle zwischen Dokument und Payment
			if(
				$oInquiry === null ||
				$oInquiry->id == 0
			) {

				$sSql = "
					SELECT
						`kid`.`inquiry_id`
					FROM
						`kolumbus_inquiries_documents_versions_items` `kidvi` LEFT JOIN
						`kolumbus_inquiries_payments_items` `kipi` ON
							`kipi`.`item_id` = `kidvi`.`id` LEFT JOIN
						`kolumbus_inquiries_payments` `kip` ON
							`kip`.`id` = `kipi`.`payment_id` LEFT JOIN
						`kolumbus_inquiries_payments_documents` `kipd` ON
							`kipd`.`payment_id` = `kip`.`id` LEFT JOIN
						`kolumbus_inquiries_documents` `kid` ON
							`kid`.`id` = `kipd`.`document_id`
					WHERE
						`kidvi`.`version_id` = :version_id
				";

				$iInquiryId = (int)DB::getQueryOne($sSql, array('version_id' => $aMissingVersion['version_id']));
				$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
			}

			if(
				$oInquiry instanceof Ext_TS_Inquiry &&
				$oInquiry->id != 0
			) {
				// Fehlende Versionen unter entsprechende Inquiries gruppieren
				$this->_aInquiries[$oInquiry->id]['missing_versions'][] = $aMissingVersion;
				$this->_aInquiries[$oInquiry->id]['inquiry'] = $oInquiry;
				$this->_aInquiries[$oInquiry->id]['overpayment_before'] = $this->getInquiryOverpayAmount($oInquiry);
				$this->_aInquiries[$oInquiry->id]['payments'] = array();
				$this->_iFoundMissingVersions++;
			} else {
				// Versionen, die verloren bleiben (es konnte keine dazugehörige Inquiry ermittelt werden)
				$this->_aLostVersions[] = $aMissingVersion;
			}
			
			if($iCounter % 100 == 0) {
				WDBasic::clearAllInstances();
			}
			
			$iCounter++;
			
		}
	}

	/**
	 * Payment-Items der verlorenen Versionen in Overpayment umwandeln
	 *
	 * @throws RuntimeException
	 */
	private function convertPaymentsToOverpayments() {

		$oLog = Log::getLogger('Documents_Versions_MissingVersions');
		
		$iCounter = 1;
		
		$aInvoiceTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

		foreach($this->_aInquiries as $iInquiryId => &$aData) {

			/** @var Ext_TS_Inquiry $oInquiry */
			$oInquiry = $aData['inquiry'];

			// Aktuellstes Dokument ermitteln, welches eine Zahlung besitzt
			$sSql = "
				SELECT
					`kid`.`id`
				FROM
					`kolumbus_inquiries_documents` `kid` INNER JOIN
					`kolumbus_inquiries_documents_versions_items` `kidvi` ON
						`kidvi`.`version_id` = `kid`.`latest_version` AND
						`kidvi`.`active` = 1 INNER JOIN
					`kolumbus_inquiries_payments_items` `kipi` ON
						`kipi`.`item_id` = `kidvi`.`id` AND
						`kipi`.`active` = 1 INNER JOIN
					`kolumbus_inquiries_payments` `kip` ON
						`kip`.`id` = `kipi`.`payment_id` AND
						`kip`.`active` = 1
				WHERE
					`kid`.`inquiry_id` = :inquiry_id AND
					`kid`.`type` IN ( :types ) AND
					`kid`.`active` = 1
				ORDER BY
					`kip`.`id` DESC
				LIMIT 1
			";

			$iDocumentId = (int)DB::getQueryOne($sSql, array(
				'inquiry_id' => $iInquiryId,
				'types' => $aInvoiceTypes
			));

			foreach($aData['missing_versions'] as $aMissingVersion) {

				$aPayments = $this->searchPaymentsOfVersion($aMissingVersion);

				if(
					$iDocumentId === 0 &&
					count($aPayments) > 0
				) {
					// Overpayments müssen laut Datenbankstruktur eine Document-ID haben
					$oLog->addError('No document with payment found for inquiry '.$iInquiryId.'!', array());
					continue;
				}

				foreach($aPayments as $oPayment) {

					// Überbezahlung zu dieser Zahlung suchen
					$oOverPayment = Ext_Thebing_Inquiry_Payment_Overpayment::getRepository()->findOneBy(array(
						'payment_id' => $oPayment->id,
						'inquiry_document_id' => $iDocumentId
					));

					if($oOverPayment === null) {
						$oOverPayment = new Ext_Thebing_Inquiry_Payment_Overpayment();
						$oOverPayment->payment_id = $oPayment->id;
						$oOverPayment->inquiry_document_id = $iDocumentId;

						$oFirstItem = $oPayment->getFirstItem();
						$oOverPayment->currency_inquiry = $oFirstItem->currency_inquiry;
						$oOverPayment->currency_school = $oFirstItem->currency_school;

						$this->logInfo('Created overpayment (payment_id: '.$oPayment->id.'; inquiry_document_id: '.$iDocumentId.')');
					} else {
						$this->logInfo('Found overpayment (id: '.$oOverPayment->id.'; payment_id: '.$oPayment->id.'; inquiry_document_id: '.$iDocumentId.')', $oOverPayment->getData());
					}

					$oOverPayment->amount_inquiry += $oPayment->amount_inquiry;
					$oOverPayment->amount_school += $oPayment->amount_school;

					$oOverPayment->validate(true);
					$oOverPayment->save();

					// Payment-Items löschen
					foreach($oPayment->getItems() as $oItem) {
						$mSuccess = $oItem->delete();
						if($mSuccess !== true) {
							$oLog->addError('Error while deleting payment item '.$oItem->id, array());
							continue;
						}

						$this->logInfo('Deleted payment item '.$oItem->id.' (payment: '.$oPayment->id.')', $oItem->getData());
					}
				}

				$aData['payments'] = array_merge($aData['payments'], $aPayments);
			}

			// Beträge müssen neu berechnet und abgespeichert werden
			$oInquiry->calculatePayedAmount();

			// Auch im Index aktualisieren
			Ext_Gui2_Index_Stack::add('ts_inquiry', $iInquiryId, 0);
			
			if($iCounter % 100 == 0) {
				WDBasic::clearAllInstances();
			}
			
			$iCounter++;
		}
	}

	/**
	 * Benachrichtigen (E-Mail)
	 */
	private function notify() {

		$sHtml = '<html><head><style type="text/css">th, td { border: 1px solid #808080; vertical-align: top; }</style></head><body>';

		$sHtml .= '<h2>Found lost document versions ('.$this->_iFoundMissingVersions.')</h2>';

		if(count($this->_aInquiries) > 0) {
			$sHtml .= '<table cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">';
			$sHtml .= '
				<tr>
					<th>Inquiry-ID</th>
					<th>School</th>
					<th>Customer number</th>
					<th>Missing versions (ID) / Date created</th>
					<th>Found payments of lost versions / Date created</th>
					<th>Original overpayment amount</th>
					<th>Added overpayment amount</th>
					<th>New overpayment amount</th>
				</tr>';
		}

		foreach($this->_aInquiries as $aData) {

			/** @var Ext_TS_Inquiry $oInquiry */
			$oInquiry = $aData['inquiry'];
			$aJourneys = $oInquiry->getJourneys();
			$iSchoolId = reset($aJourneys)->school_id;
			$oSchool = new Ext_Thebing_School($iSchoolId, null, true);

			$sGroup = '';
			if($oInquiry->hasGroup()) {
				$sGroup = '*';
			}

			$sHtml .= '<tr>';
			$sHtml .= '<td>'.$oInquiry->id.' '.$sGroup.'</td>';
			$sHtml .= '<td>'.$oSchool->getName().'</td>';
			$sHtml .= '<td>'.$oInquiry->getCustomer()->getCustomerNumber().'</td>';

			// Versionen
			$sHtml .= '<td>'.join('<br>', array_map(function($aMissingVersion) {
				return $aMissingVersion['id'].' / '.Ext_Thebing_Format::LocalDateTime($aMissingVersion['created']);
			}, $aData['missing_versions'])).'</td>';

			// Payments
			$sHtml .= '<td>'.join('<br>', array_map(function($oPayment) {
				$aPaymentData = $oPayment->getData();
				return $oPayment->id.' / '.Ext_Thebing_Format::LocalDateTime(new DateTime($aPaymentData['created']));
			}, $aData['payments'])).'</td>';

			$mOverPayment = $this->getInquiryOverpayAmount($oInquiry);
			$mOverPaymentDiff = $mOverPayment - $aData['overpayment_before'];

			if($mOverPaymentDiff == 0) {
				$mOverPayment = $mOverPaymentDiff = '---';
			}

			$sHtml .= '<td>'.$aData['overpayment_before'].'</td>';
			$sHtml .= '<td>'.$mOverPaymentDiff.'</td>';
			$sHtml .= '<td>'.$mOverPayment.'</td>';
		}

		$sHtml .= '</table>';
		$sHtml .= '</body>';

		$sHtml .= '<h2>Lost lost document versions ('.count($this->_aLostVersions).')</h2>';
		$sHtml .= '<ul>';
		foreach($this->_aLostVersions as $aLostVersion) {
			$aPayments = $this->searchPaymentsOfVersion($aLostVersion);
			$fPaymentAmount = 0;

			foreach($aPayments as $oPayment) {
				$fPaymentAmount += $oPayment->getAmount();
				$this->logInfo('Lost payment found: '.$oPayment->id.' (amount: '.$oPayment->getAmount().')');
			}

			$sHtml .= '<li>ID '.$aLostVersion['id'].' (created: '.Ext_Thebing_Format::LocalDateTime($aLostVersion['created']).', payments: '.count($aPayments).' ('.$fPaymentAmount.'))</li>';
		}
		$sHtml .= '</ul>';

		$sHtml .= '<h2>Log</h2>';
		$sHtml .= '<pre>'.print_r($this->_aLog, true).'</pre>';

		$sHtml .= '</html>';

		$this->logInfo('HTML Report', $sHtml);

		$oSchool = Ext_Thebing_Client::getFirstSchool();
		$oMail = new WDMail();
		$oMail->subject = 'TS Checks Report – '.$oSchool->getName().' ('.get_class($this).')';
		$oMail->html = $sHtml;
		$oMail->send('support@thebing.com');
	}

	/**
	 * Alle verlorenen Versionen entfernen
	 */
	private function removeVersions() {

		foreach($this->_aInquiries as $aData) {
			foreach($aData['missing_versions'] as $aMissingVersion) {
				$this->removeVersion($aMissingVersion);
			}
		}

		foreach($this->_aLostVersions as $aLostVersion) {
			$this->removeVersion($aLostVersion);
		}
	}

	/**
	 * Alle Bestandteile der Version löschen
	 *
	 * @param array $aVersion
	 * @throws RuntimeException
	 */
	private function removeVersion($aVersion) {

		// Items löschen
		foreach($aVersion['items'] as $aItem) {
			$oItem = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($aItem['id']);
			$mSuccess = $oItem->delete();
			if($mSuccess !== true) {
				throw new RuntimeException('Error while deleting version item '.$oItem->id.' ('.$aVersion['id'].')');
			}

			$this->logInfo('Deleted version item '.$oItem->id.' ('.$aVersion['id'].')');
		}

		// Preis-Indizes der Version löschen
		$aPriceIndexes = Ext_Thebing_Inquiry_Document_Version_Price::getByVersion($aVersion['id']);
		foreach($aPriceIndexes as $oPriceIndex) {
			$mSuccess = $oPriceIndex->delete();
			if($mSuccess !== true) {
				throw new RuntimeException('Error while deleting priceindex '.$oPriceIndex->id.' ('.$aVersion['id'].')');
			}
		}
	}

	/**
	 * Alle Payments der verlorenen Version suchen
	 *
	 * @param array $aVersion
	 * @return Ext_Thebing_Inquiry_Payment[]
	 */
	private function searchPaymentsOfVersion($aVersion) {
		$aReturn = array();

		$sSql = "
			SELECT
				`kip`.`id`
			FROM
				`kolumbus_inquiries_payments` `kip` INNER JOIN
				`kolumbus_inquiries_payments_items` `kipi` ON
					`kipi`.`payment_id` = `kip`.`id` AND
					`kipi`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`id` = `kipi`.`item_id`
			WHERE
				`kip`.`active` = 1 AND
				`kidvi`.`version_id` = :version_id
			GROUP BY
				`kip`.`id`
		";

		$aResult = (array)DB::getQueryCol($sSql, array(
			'version_id' => $aVersion['id'])
		);

		foreach($aResult as $iPaymentId) {
			$aReturn[] = Ext_Thebing_Inquiry_Payment::getInstance($iPaymentId);
		}

		return $aReturn;
	}

	/**
	 * Overpayment-Amount der Inquiry holen, dabei statischen Cache löschen
	 *
	 * @param Ext_TS_Inquiry $oInquiry
	 * @return float
	 */
	private function getInquiryOverpayAmount(Ext_TS_Inquiry $oInquiry) {
		$fAmount = 0;

		$aDocuments = (array)$oInquiry->getDocuments('invoice', true, true);

		foreach($aDocuments as $oDocument) {

			// Statischen Cache löschen
			$oProperty = new ReflectionProperty($oDocument, 'aOverpayAmount');
			$oProperty->setAccessible(true);
			$oProperty->setValue($oDocument, array());

			$fAmount += $oDocument->getOverpayAmount();
		}

		return $fAmount;
	}

	public function logInfo($sMessage, $mOptional = array()) {
		parent::logInfo($sMessage, $mOptional);

		if(empty($mOptional)) {
			$this->_aLog[] = $sMessage;
		} else {
			$this->_aLog[] = array($sMessage, $mOptional);
		}
	}

}