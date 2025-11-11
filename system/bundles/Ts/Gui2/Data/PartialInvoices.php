<?php

namespace Ts\Gui2\Data;

class PartialInvoices extends \Ext_Thebing_Gui2_Data
{
	
	public static function getOrderby()
	{
		return ['date'=>'ASC'];
	}
	
	public function confirmGenerateInvoice($aVars)
	{
		
		foreach($aVars['id'] as $iPartialInvoiceId) {
			
			$oPartialInvoice = \Ts\Entity\Inquiry\PartialInvoice::getInstance($iPartialInvoiceId);
			$bIsFirst = $oPartialInvoice->isNext();
			
			if(!$bIsFirst) {
				$aTransfer = array(
					'action' => 'showError',
					'error' => array(
						$this->t('Es ist ein Fehler aufgetreten'),
						$this->t('Von einer Buchung darf jeweils nur die erste noch nicht generierte Teilrechnung gewählt werden.')
					)
				);
				return $aTransfer;
			} elseif($oPartialInvoice->converted !== null) {
				$aTransfer = array(
					'action' => 'showError',
					'error' => array(
						$this->t('Es ist ein Fehler aufgetreten'),
						$this->t('Es können nur noch nicht erstellte Teilrechnungen generiert werden.')
					)
				);
				return $aTransfer;
			}
			
			$oInquiry = $oPartialInvoice->getJoinedObject('inquiry');

			$aTemplates = \Ext_Thebing_Pdf_Template_Search::s('document_invoice_customer', $oInquiry->getLanguage(), $oInquiry->getSchool()->id, $oInquiry->getInbox()->id);
			$oTemplate = reset($aTemplates);

//			$oForm = new \Ext_Thebing_Form;
//			$oForm->schools = [$oInquiry->getSchool()->id];
//			$oForm->languages = [$oInquiry->getLanguage()];
//			$oForm->default_language = $oInquiry->getLanguage();
//			$oForm->{'school_settings_'.$oInquiry->getSchool()->id.'_tpl_id'} = $oTemplate->id;
//			
//#getForm()->getSchoolSetting($school, 'tpl_id')
//			$oCombination = new \Ext_TS_Frontend_Combination;
//			$oCombination->usage = 'registration_v3';
//			$oCombination->items_school = $oInquiry->getSchool()->id;
//
//			$oCombinationGenerator = new \TsRegistrationForm\Generator\CombinationGenerator($oCombination, new \Core\Service\Templating());
//			$oCombinationGenerator->setForm($oForm);
//			$oCombinationGenerator->initCombination(new \MVC_Request, $oInquiry->getLanguage());
//			
//			$oInquiryHelper = new \TsRegistrationForm\Helper\BuildInquiryHelper($oCombinationGenerator);
//			
//			$aData = [
//				'document_type' => 'brutto'
//			];
//			
//			$oInbox = $oInquiry->getInbox();
//			\Ext_TS_NumberRange::setInbox($oInbox);
//
//			$oSchool = $oInquiry->getSchool();
//			$oNumberrange = \Ext_Thebing_Inquiry_Document_Numberrange::getObject($oInquiry->getTypeForNumberrange('brutto', $oTemplate->type), (bool)$iIsCredit, $oSchool->id);
//			
//			$oInquiryHelper->createDocument($oInquiry, $oNumberrange, $aData);
			
			$oPartialInvoice->converted = time();
			$oPartialInvoice->save();
			
		}
			
		$aTransfer = [
			'action' => 'showSuccessAndReloadTable',
			'data' => [
				'id'=>'SUCCESS'
			],
			'success_title' => $this->t('Rechnung wird generiert'),
			'message' => [$this->t('Die Rechnung wird im Hintergrund generiert und steht Ihnen in Kürze zur Verfügung')]
		];

		return $aTransfer;
	}
	
	static public function getListWhere()
	{
		$oSchool = \Ext_Thebing_School::getSchoolFromSession();

		$aWhere['ts_ij.school_id'] = (int)$oSchool->id;

		return $aWhere;
	}
	
	static public function getPartialInvoiceFilterOptions(\Ext_Gui2 $gui2)
	{
		return [
			'no' => $gui2->t('Nicht erstellt'),
			'yes' => $gui2->t('Erstellt')
		];
	}
	
}
