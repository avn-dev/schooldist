<?php

namespace TsAccounting\Service\eInvoice\Italy;

use TcAccounting\Service\eInvoice\Service\Italy\XmlStructure;
use TcAccounting\Service\eInvoice\Service\Italy\DTO\Transmitter;
use TcAccounting\Service\eInvoice\Service\Italy\DTO\InvoiceContact;
use TcAccounting\Service\eInvoice\Service\Italy\DTO\Document;
use TcAccounting\Service\eInvoice\Service\Italy\DTO\LineItem;
use TcAccounting\Service\eInvoice\Service\Italy\DTO\Address;
use TsAccounting\Service\eInvoice\Italy\Exceptions\BuildException;

class XmlStructureBuilder {
	
	/**
	 * Sammelt alle nötigen Informationen für die XML-Generierung
	 * 
	 * @param \Ext_Thebing_Inquiry_Document $oInquiryDocument
	 * @return \TcAccounting\Service\eInvoice\Service\Italy\XmlStructure
	 */
	public function build(\Ext_Thebing_Inquiry_Document $oInquiryDocument) : XmlStructure {
		
		$oFinalExport = \TcAccounting\Service\eInvoice\Entity\File::getRepository()
				->findOneBy(['document_id' => $oInquiryDocument->getId(), 'type' => 'xml_it']);
		
		if(!is_null($oFinalExport)) {
			throw (new BuildException('final_export_exists'))->bindParameter($oInquiryDocument->document_number);
		}
		
		// @todo document type prüfen
		
		$oStructure = new XmlStructure();
		
		$oVersion = $oInquiryDocument->getLastVersion();
				
		// Header
		
		$this->readHeaderAttributes($oInquiryDocument, $oVersion, $oStructure);
				
		// Body
		
		$this->readBodyAttributes($oInquiryDocument, $oVersion, $oStructure);
		
		return $oStructure;
	}
	
	/**
	 * Sammelt alle Informationen für den Header des XMLs
	 * - Rechnungssteller
	 * - Rechnungsempfänger
	 * 
	 * @param \Ext_Thebing_Inquiry_Document $oInquiryDocument
	 * @param \Ext_Thebing_Inquiry_Document_Version $oVersion
	 * @param \TcAccounting\Service\eInvoice\Service\Italy\XmlStructure $oStructure
	 * @throws \TsAccounting\Service\eInvoice\Italy\Exceptions\BuildException
	 */
	private function readHeaderAttributes(\Ext_Thebing_Inquiry_Document $oInquiryDocument, \Ext_Thebing_Inquiry_Document_Version $oVersion, XmlStructure $oStructure) : void {
		
		$oInquiry = $oInquiryDocument->getInquiry();
		$oSchool = $oInquiry->getSchool();
		$oInbox = $oInquiry->getInbox();
		
		$oAccountingCompany = \TsAccounting\Entity\Company::searchByCombination($oSchool, $oInbox);

		if(is_null($oAccountingCompany)) {
			throw (new BuildException('no_company'))->bindParameter($oSchool->getName());
		}
		
		// Rechnungsadresse
		
		$aAddress = $oVersion->getAddress();
			
		$oLanguageObject = new \Tc\Service\Language\Frontend('it');

		if(!empty($aAddress)) {
			$aAddressData = (new \Ext_Thebing_Document_Address($oInquiry))
					->getAddressData($aAddress, $oLanguageObject);
		
			$oInvoiceAddress = new Address();
			$oInvoiceAddress->company = $aAddressData['document_company'];
			$oInvoiceAddress->address = $aAddressData['document_address'];
			$oInvoiceAddress->zip = $aAddressData['document_zip'];
			$oInvoiceAddress->city = $aAddressData['document_city'];
			$oInvoiceAddress->state = $aAddressData['document_state'];
			$oInvoiceAddress->country_iso = $aAddressData['document_country_iso'];

			$oStructure->setInvoiceAddress($oInvoiceAddress);
		}
		
		// Rechnungskontakt
		
		$oInvoiceContact = new InvoiceContact();
		$oInvoiceContact->firstname = $aAddressData['document_firstname'];
		$oInvoiceContact->lastname = $aAddressData['document_surname'];
		$oInvoiceContact->steuer = $aAddressData['document_tax_code'] ?? '';
		$oInvoiceContact->mwst = $aAddressData['document_vat_number'] ?? '';
		$oInvoiceContact->hasAgency = $oInquiry->hasAgency();

		if (!empty($aAddressData['document_recipient_code'])) {
			$oInvoiceContact->codex = $aAddressData['document_recipient_code'];
		} elseif (!empty($oInvoiceAddress) && $oInvoiceAddress->country_iso == 'IT') {
			$oInvoiceContact->codex = '0000000';
		} else {
			$oInvoiceContact->codex = 'XXXXXXX';
		}

		$oStructure->setInvoiceContact($oInvoiceContact);
		
		// Rechnungssteller
		
		if(empty($oAccountingCompany->address)) {
			throw (new BuildException('company_data_missing'))->bindParameter($oAccountingCompany->getName());
		}
		
		$oTransmitter = new Transmitter();
		$oTransmitter->phone_number = $oAccountingCompany->phone_number;
		$oTransmitter->address_street = $oAccountingCompany->address;
		$oTransmitter->address_zip = $oAccountingCompany->address_zip;
		$oTransmitter->address_city = $oAccountingCompany->address_city;
		$oTransmitter->address_state = $oAccountingCompany->address_state;		
		$oTransmitter->address_country = strtoupper($oAccountingCompany->address_country);
		$oTransmitter->transmission_tax_number = $oAccountingCompany->transmission_tax_number;
		$oTransmitter->transmission_tax_system = $oAccountingCompany->transmission_tax_system;
		$oTransmitter->transmission_bank_name = $oAccountingCompany->transmission_bank_name;
		$oTransmitter->transmission_iban = $oAccountingCompany->transmission_iban;
		$oTransmitter->transmission_designation = $oAccountingCompany->transmission_designation;
		$oTransmitter->transmission_register_office = $oAccountingCompany->transmission_register_office;
		$oTransmitter->transmission_register_number = $oAccountingCompany->transmission_register_number;
		$oTransmitter->transmission_capital = $oAccountingCompany->transmission_capital;
		$oTransmitter->transmission_partner = $oAccountingCompany->transmission_partner;
		$oTransmitter->transmission_liquidation = $oAccountingCompany->transmission_liquidation;
		$oStructure->setTransmitter($oTransmitter);
		
	}
	
	/**
	 * Sammelt alle Informationen für den Body der XML-Datei
	 * - Rechnungspositionen
	 * - PDF-Datei
	 * 
	 * @param \Ext_Thebing_Inquiry_Document $oInquiryDocument
	 * @param \Ext_Thebing_Inquiry_Document_Version $oVersion
	 * @param \TcAccounting\Service\eInvoice\Service\Italy\XmlStructure $oStructure
	 * @return void
	 */
	private function readBodyAttributes(\Ext_Thebing_Inquiry_Document $oInquiryDocument, \Ext_Thebing_Inquiry_Document_Version $oVersion, XmlStructure $oStructure) : void {
		
		$oInquiry = $oInquiryDocument->getInquiry();

		$oSchool = $oInquiry->getSchool();
		$oInbox = $oInquiry->getInbox();

		$oAccountingCompany = \TsAccounting\Entity\Company::searchByCombination($oSchool, $oInbox);

		$sType = $oInquiryDocument->type;
		if($oInquiryDocument->type === 'storno') {
			$sType = Document::CANCELLATION;
		} else if($oInquiryDocument->is_credit) {
			$sType = Document::CREDITNOTE;
		}
		
		$oDocument = new Document($sType, $oInquiryDocument->document_number, $oInquiryDocument->getCurrency()->getIso(), new \DateTime($oVersion->date));
		$oDocument->setAmount($oInquiryDocument->getAmount(true, true, $oInquiryDocument->type));
		$oDocument->setFile($oVersion->getPath(true), $oInquiryDocument->document_number);

		if ($oInquiry->hasGroup()) {
			$aDocumentItems = $oVersion->getGroupItems(null, false, false, true, true);
		} else {
			$aDocumentItems = $oVersion->getItemObjects(true);
		}

		foreach($aDocumentItems as $oItem) {
			$itemAsArray = [];
			if (is_array($oItem)) {
				$itemAsArray = $oItem;
				$oItem = \Ext_Thebing_Inquiry_Document_Version_Item::createFromArray($oItem);
			}
			$sItemType = (strpos($oItem->type, 'additional') !== false)
				? 'additional'
				: $oItem->type;

			if($sType == 'specials') {
				$sType = LineItem::TYPE_DISCOUNT;
			} else if ($oItem->amount_discount > 0) {
				$sType = LineItem::TYPE_STANDARD_DISCOUNT;
			} else {
				$sType = LineItem::TYPE_STANDARD;
			}

			$iItemTypeId = ($oItem->type_object_id > 0)
				? $oItem->type_object_id
				: $oItem->type_id;

			$oLineItem = new LineItem($sType, $sItemType, $iItemTypeId, $oItem->description);
			$oLineItem->setAmount($oItem->getAmount($sType, false, false));
			$oLineItem->setDiscountAmount($oItem->getDiscountAmount());
			$oLineItem->setDiscountPercent($oItem->amount_discount);
			// natura hängt jetzt primär von der Position ab. Falls nichts angegeben,
			// dann default_natura.
			if (!empty($oItem->tax_category)) {
				$natura = \Ext_TC_Vat::getInstance($oItem->tax_category)?->getShort();
				$oLineItem->setVat($oItem->tax_category, $oItem->tax);
				$oLineItem->setNatura($natura);
			} else {
				$oItem->tax_category = 0;
				if ($oAccountingCompany->default_natura) {
					$oLineItem->setNatura($oAccountingCompany->default_natura);
				}
			}

			if($oItem->index_from !== '0000-00-00') {
				$oLineItem->setFrom($oItem->index_from);
			}

			if($oItem->index_until !== '0000-00-00') {
				$oLineItem->setUntil($oItem->index_until);
			}

			$oLineItem->setQuantity($itemAsArray['count'] ? : 1);

			$oDocument->addItem($oLineItem);
		}
		
		$aPaymentterms = array_values($oVersion->getPaymentTerms());

		if ($oInquiry->hasGroup()) {
			$oVersion->calculateBackPrepayAmount($aPaymentterms);
		}
		
		foreach($aPaymentterms as $oPaymentterm) {
			$oDocument->addPaymentTerm(new \DateTime($oPaymentterm->date), $oPaymentterm->amount);
		}
				
		$oStructure->setDocument($oDocument);
		
	}
	
}

