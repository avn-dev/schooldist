<?php

namespace Office\Controller;

include_once(\Util::getDocumentRoot()."system/extensions/office/office.inc.php");
include_once(\Util::getDocumentRoot()."system/extensions/office/office.dao.inc.php");

class ApiController extends \MVC_Abstract_Controller {
	
	protected $_sAccessRight = null;
	
	/**
	 * https://fidelo.com/system/extensions/office/office.api.php?task=payment&date=1549580400&name=ESCUELA%20DE%20IDIOMAS%20NERJA%20S.L&reference=NOTPROVIDED&text=ESCUELA%20DE%20IDIOMAS%20NERJA%20S.L%20DIFFERENCE%20INVOICE%20DEC2018%20AND%20JANUARY%202019%20EINERJA%20NOTPROVIDED&amount=73.2&account=819308800&transaction=["de","37040048","","819308800","","DE33370400480819308800","FIDELO SOFTWARE GMBH","COBADEFFXXX","de","","","BSABESBBXXX","","ES7400810548970001019105","","ES7400810548970001019105","ESCUELA DE IDIOMAS NERJA S.","L","BSABESBBXXX","0","0","0","2019\/02\/08","2019\/02\/08","732\/10","EUR","","","0","0","TRF","","97261","166","SEPA-CT HABEN EINZELBUCHUNG","97261","","DIFFERENCE INVOICE DEC2018 AND JANUARY 2019 EINERJA","","","","","","","","","","","","","","","","","","","","unknown","0","0","","","","unknown","unknown","unknown","unknown","","","","","","","","","","","","","","0","","","","","unknown","","","","NOTPROVIDED"]
	 */
	public function request() {

		$objOffice = new \classExtension_Office;
        $aConfigData = $objOffice->getConfigData();
        $objOfficeDao = new \classExtensionDao_Office($aConfigData);
		
		$aTransfer = [
			'invoices_found' => [],
			'payments' => []
		];

		if($this->_oRequest->get('task') == 'payment') {

			\Log::getLogger('office', 'api')->addInfo('Payment', (array)$this->_oRequest->getAll());
			
			$aInfo = array();

			$iDate = (int)$this->_oRequest->get('date');
			$sText = $this->_oRequest->get('text');
			$sName = $this->_oRequest->get('name');
			$sReference = $this->_oRequest->get('reference');
			$sTransaction = $this->_oRequest->get('transaction');
			$fPayment = $fAmount = (float)$this->_oRequest->get('amount');

			// Prüfen, ob Zahlung bereits verarbeitet wurde
			$bProcessed = $objOfficeDao->checkAccountActivity($iDate, $fAmount, $sText);

			$aInfo[] = array(
				"Incoming payment" => $fAmount,
				"Message" => $sText	
			);

			// Zahlung wurde bereits erfasst
			if($bProcessed === true) {

				$aInfo[] = array(
					"Already processed"
				);

				$aTransfer['status'] = 'Already processed';
				
			} else {

				preg_match_all('/[0-9]+/i', $sText, $aMatch);

				$aInfo[] = array(
					"Numbers matched" => $aMatch	
				);

				$fTotal = 0;
				$aInvoices = array();

				$aDocuments	= (array)$objOfficeDao->getInvoicesByNumbers($aMatch[0]);

				$sPaymentText = strtoupper($sText);

				$iMatchedCustomerId = null;
				
				foreach($aDocuments as $aDocument) {

					$aDocument['matched'] = false;
					$sCleanedText = strtoupper(preg_replace("/[^A-Za-z0-9]/", '', $sText));
					$sCleanedCustomerMatchcode = strtoupper(preg_replace("/[^A-Za-z0-9]/", '', $aDocument['customer_matchcode']));
					$sCleanedCustomerName = strtoupper(preg_replace("/[^A-Za-z0-9]/", '', $aDocument['customer_name']));
					
					$aInfo[] = array(
						"Matching" => [
							$sCleanedText,
							$sCleanedCustomerMatchcode,
							$sCleanedCustomerName,
							$aDocument['customer_number']
						]	
					);
					
					if(
						mb_stripos($sCleanedText, $sCleanedCustomerMatchcode) !== false ||
						mb_stripos($sCleanedText, $sCleanedCustomerName) !== false ||
						mb_stripos($sCleanedText, $aDocument['customer_number']) !== false
					) {

						$aDocument['matched'] = true;
						$aInfo[] = array(
							"Invoice matched" => $aDocument	
						);

						$iMatchedCustomerId = $aDocument['customer_id'];
						
					}

					if(
						$aDocument['state'] == 'released' ||
						$aDocument['state'] == 'reminded'
					) {
						$fTotal = bcadd($fTotal, $aDocument['price'], 2);
					}
					$aInvoices[] = $aDocument;
					
					$aTransfer['invoices_found'][] = $aDocument['number'];
					
				}

				$aTransfer['invoices_total'] = $fTotal;
				
				$aInfo[] = array(
					"Total sum of invoices" => $fTotal,
					"Invoices found" => $aInvoices
				);

				$bTotalMatched = false;
				if($fTotal == $fAmount) {

					$aInfo[] = array(
						"invoice total matches payment"
					);

					$bTotalMatched = true;

				}

				// Alle offenen Rechnungen holen
				if(!empty($iMatchedCustomerId)) {
					
					$aDueInvoices = (array)$objOfficeDao->getInvoicesByCustomer($iMatchedCustomerId);
					
					$aInfo[] = [
						'due_invoices' => $aDueInvoices
					];
					
					$aInvoices = [];
					foreach($aDueInvoices as $aDocument) {
						$aDocument['matched'] = true;
						$aInvoices[] = $aDocument;
					}
					
				}
				
				$iInvoice = 0;
				$iCountInvoices = count($aInvoices);
				foreach((array)$aInvoices as $aInvoice) {

					$iInvoice++;

					if(
						(
							(
								$bTotalMatched &&
								$aInvoice['state'] == 'released'	
							) ||
							$aInvoice['matched'] === true
						) &&
						$fPayment > 0
					) {

						$aCurrentPayments = $objOfficeDao->getPayments($aInvoice['id']);
						$fOutstanding = $aInvoice['price'] - $aCurrentPayments['sum'];

						// Wenn der gezahlte Betrag größer ist als der offene und es nicht die letzte Rechnung ist, dann nur den offenen Betrag
						if(
							$fPayment > $fOutstanding
						) {
							$fInsertPayment = $fOutstanding;
						} else {
							$fInsertPayment = $fPayment;
						}
						$fOutstanding -= $fInsertPayment;
						$fPayment -= $fInsertPayment;

						$sDate = date('Y-m-d', $iDate);

						$aInfo[] = array(
							"Payments found" => $aCurrentPayments,
							"Compare date" => $sDate,
							"Compare amount" => $fInsertPayment				
						);

						// Prüfen, ob diese Zahlung schon eingetragen wurde
						foreach($aCurrentPayments['payments'] as $aCurrentPayment) {

							if(
								$aCurrentPayment['date'] == $sDate &&
								$aCurrentPayment['amount'] == $fInsertPayment
							) {
								$aInfo[] = array(
									"Paymend already saved" => $aCurrentPayment
								);
								continue 2;
							}

						}

						$aDocument = $aInvoice;

						$objOfficeDao->savePayment($aInvoice['id'], $fInsertPayment, 0, true, $sText);

						$aInfo[] = array(
							"Paymend saved" => $fInsertPayment,
							"Invoice number" => $aInvoice['number'],
							"Invoice amount" => $aInvoice['price'],
							"Invoice outstanding" => $fOutstanding
						);

						$aTransfer['payments'][] = [
							'amount' => $fInsertPayment,
							'invoice' => $aInvoice['number']
						];
						
						#$aTransfer[] = $aInvoice;

					} else {
						$aInfo[] = array(
							"Skip invoice" => $aInvoice	
						);
					}

				}

				$aInfo['fPayment'] = $fPayment;

				// Wenn Zahlung nicht zugeordnet werden konnte
				if($fPayment != 0) {

					$sMessage = "<html><body><style>html,body,p,th,td {font-family:sans-serif;}</style><p>Die folgende Zahlung konnte automatisch nicht vollständig zugeordnet werden. Bitte nehmen Sie die Zuordnung manuell vor:</p>";
					$sMessage .= "<table>";
					$sMessage .= "<tr><td>Verwendungszweck:</td><td>".$sText.'</td></tr>';
					$sMessage .= "<tr><td>Komplettbetrag:</td><td style='text-align: right;'>".number_format($fAmount, 2, ",", ".")." &euro;</td></tr>";
					$sMessage .= "<tr><th style='text-align: left;'>Nicht zugewiesen:</th><th style='text-align: right;'>".number_format($fPayment, 2, ",", ".")." &euro;</th></tr>";
					$sMessage .= "</table></body></html>";

					$sEmail = $objOfficeDao->office_accounts_notification_email;

					$aInfo["sEmail"] = $sEmail;
					$aInfo["sMessage"] = $sMessage;

					if(!empty($sEmail)) {

						$oWDMail = new \Office\Service\Email();
						$oWDMail->setSubject(\System::d('project_name')." - Office API");
						$oWDMail->setHtml($sMessage);
						$oWDMail->send([$sEmail]);

					}
					
					$aTransfer['status'] = 'Payment not fully assigned';
					
				}

				$objOfficeDao->addAccountActivity($iDate, $fAmount, $sText, $sName, $sReference, $sTransaction);

				$oMail = new \Office\Service\Email();
				$oMail->setSubject("Office API");
				$oMail->setText(print_r($aInfo, 1)."\n\n".print_r($_VARS, 1));
				$oMail->send([\System::d('error_email')]);

			}

			if(\System::d('debugmode') == 2) {
				__out($aInfo);
			}

		} elseif(
			$this->_oRequest->exists('auftrag') &&
			$this->_oRequest->exists('statustext')
		) {

			$aCode1 = json_decode($this->_oRequest->get('code'));
			$sCode = stripslashes($this->_oRequest->get('code'));
			$aCode = json_decode($sCode);

			$oMail = new \Office\Service\Email();
			$oMail->setSubject("Office API Request");
			$oMail->setText(print_r($aCode1, 1).print_r($aCode, 1).print_r($_VARS, 1).print_r($_SERVER, 1));
			$oMail->send([\System::d('error_email')]);

		}

		echo json_encode($aTransfer);
		die();
	}
	
}
