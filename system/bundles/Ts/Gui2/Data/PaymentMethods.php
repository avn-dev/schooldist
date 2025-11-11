<?php

namespace Ts\Gui2\Data;

class PaymentMethods extends \Ext_Thebing_Gui2_Data
{
	
	static public function getOrderby()
	{
		return ['kpm.name' => 'ASC'];
	}
	
	static public function getDialog(\Ext_Thebing_Gui2 $oGui)
	{
		$aSchools = \Ext_Thebing_Client::getSchoolList(true);
		$oClient = \Ext_Thebing_Client::getFirstClient();
		$aPaymentTypes = [
			\Ext_Thebing_Admin_Payment::TYPE_CHEQUE => $oGui->t('Scheck'),
			\Ext_Thebing_Admin_Payment::TYPE_CLEARING => $oGui->t('Verrechnung')
		];
		foreach ((new \TsFrontend\Factory\PaymentFactory())->getOptions() as $sKey => $sLabel) {
			$aPaymentTypes[\Ext_Thebing_Admin_Payment::TYPE_PROVIDER_PREFIX.$sKey] = $sLabel;
		}
		$aPaymentTypes = \Ext_Thebing_Util::addEmptyItem($aPaymentTypes);

		$oDialog = $oGui->createDialog(
				$oGui->t('Zahlmethode editieren').' - {name}', 
				$oGui->t('Neue Zahlmethode anlegen')
		);

		$oDialog->width = 900;
		$oDialog->height = 650;

		$oDialog->setElement($oDialog->createRow($oGui->t('Bezeichnung'),	 
				'input', array(
					'db_alias' => 'kpm', 
					'db_column'=>'name',
					'required' => 1)));

		// nur wenn es methoden gibt und 1 wegen leereintrag
		if(count($aPaymentTypes) > 1){
		$oDialog->setElement($oDialog->createRow($oGui->t('Zahlungsmethode'), 
				'select' , array(
					'db_column' => 'type', 
					'db_alias' => 'kpm', 
					'select_options' => $aPaymentTypes) ));
		}

		//$oDialog->setElement($oDialog->createRow(L10N::t('Währung', $oGui->gui_description),	 
		//'select', array('db_alias' => '', 'db_column'=>'currency_id', 'required' => 1, 'select_options' => $aCurrencies)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Schulen'), 'select', [
			'db_alias' => 'kpm',
			'db_column' => 'schools',
			'multiple' => 5,
			'select_options' => $aSchools,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'required' => 1,
		]));

		//*****************************			QUITTUNGEN-AREA			****************************************************//

		//TODO:
		//die zu generierenden Quittungen sollen zusätzlich an die 3 Zahlarten (vor Abreise, vor Ort, refund) angeknüpft

		//Quittungen

		if($oClient->inquiry_payments_receipt > 0) { // Quittungen bei Zahlungen erforderlich

			$aPaymentKinds = \Ext_Thebing_Inquiry_Payment::getTypeOptions();

			$aCustomerReceiptTemplates = \Ext_Thebing_Pdf_Template_Search::s('document_invoice_customer_receipt', false, false, null, true);
			$aAgencyReceiptTemplates = \Ext_Thebing_Pdf_Template_Search::s('document_invoice_agency_receipt', false, false, null, true);
			$aCreditNoteReceiptTemplates = \Ext_Thebing_Pdf_Template_Search::s('document_creditnote_receipt', false, false, null, true);

			$oH2 = $oDialog->create('h2');
			$oH2->setElement($oGui->t("PDF-Vorlagen"));
			$oDialog->setElement($oH2);

			foreach($aPaymentKinds as $iKey => $sValue) {

				/* Refund CreditNote wird
				 * gesondert behandelt */
				if(
					$iKey == 4 ||
					$iKey == 5
				) {
					continue;
				}

				$oH3 = $oDialog->create('h4');
				$oH3->setElement($sValue);

				$oDialog->setElement($oH3);

				$oDialog->setElement(
					$oDialog->createRow(
						$oGui->t('Vorlage für Kundenbeleg'),
						'select',
						array(
							'db_column' => 'reciept_template_customer_'.$iKey,
							'multiple' => 5,
							'jquery_multiple' => 1,
							'select_options' => $aCustomerReceiptTemplates
						)
					)
				);

				$oDialog->setElement(
					$oDialog->createRow(
						$oGui->t('Vorlage für Agenturbeleg'),
						'select',
						array(
							'name_suffix' => '['.$iKey.']',
							'db_column' => 'reciept_template_agency_'.$iKey,
							'multiple' => 5,
							'jquery_multiple' => 1,
							'select_options' => $aAgencyReceiptTemplates
						)
					)
				);

			}

			/* Refund und Refund CreditNote haben noch
			 * die Möglichkeit eine Vorlage für den Agenturgutschriftbeleg
			 * einzustellen. */

			unset(
				$aPaymentKinds[1],
				$aPaymentKinds[2],
				$aPaymentKinds[3]
			);

			foreach($aPaymentKinds as $iKey => $sValue) {

				if(
					$iKey == 4 ||
					$iKey == 5
				) {

					$oDialog->setElement(
						$oDialog->create('h4')->setElement($aPaymentKinds[$iKey])
					);

				}

				$oDialog->setElement(
					$oDialog->createRow(
						$oGui->t('Vorlage für Agenturgutschriftbeleg'),
						'select',
						array(
							'name_suffix' => '['.$iKey.']',
							'db_column' => 'reciept_template_creditnote_'.$iKey,
							'multiple' => 5,
							'jquery_multiple' => 1,
							'select_options' => $aCreditNoteReceiptTemplates
						)
					)
				);

			}

		} 
				
		return $oDialog;
	}
		
}
