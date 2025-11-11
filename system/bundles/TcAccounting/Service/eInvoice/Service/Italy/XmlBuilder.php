<?php

namespace TcAccounting\Service\eInvoice\Service\Italy;

use TcAccounting\Service\eInvoice\Interfaces;
use TcAccounting\Service\eInvoice\Service\File;
use TcAccounting\Service\eInvoice\Service\Italy\Format\Date as DateFormat;
use TcAccounting\Service\eInvoice\Service\Italy\Format\Amount as AmountFormat;

class XmlBuilder implements Interfaces\Builder {
	
	/**
	 * Generiert anhand der übergebenen Struktur ein XML
	 * 
	 * @param \TcAccounting\Service\eInvoice\Interfaces\Structure $oStructure
	 * @param \TcAccounting\Service\eInvoice\Service\File $oFile
	 * @return TcAccounting\Service\eInvoice\Service\File
	 */
	public function build(Interfaces\Structure $oStructure, File $oFile) : File {
		/* @var $oStructure \TcAccounting\Service\eInvoice\Italy\XmlStructure */

		$oXml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
			. '<?xml-stylesheet type="text/xsl" href="fatturapa_v1.2.xsl"?>'
			.'<Root/>'
		);
		
		// Header
		
		$oHeader = $oXml->addChild('FatturaElettronicaHeader');
		
		$this->buildHeaderNodes($oHeader, $oStructure);
		
		// Body
		
		$oBody = $oXml->addChild('FatturaElettronicaBody');
		
		$this->buildBodyNodes($oBody, $oStructure);
				
		// Platzhalter ersetzen		
		// wegen dem v1: hatte ich Probleme wegen Namespacing - deshalb <Root> als Platzhalter
		
		$sXml = str_replace(
			['<Root>', '</Root>'], 
			['<v1:FatturaElettronica xmlns:v1="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" versione="FPR12">', '</v1:FatturaElettronica>']
		, $oXml->asXML());
		
		// Saubere XML Struktur
		
		$oDOM = new \DOMDocument();		
		$oDOM->preserveWhiteSpace = false;
		$oDOM->formatOutput = true;

		$oDOM->loadXML($sXml);

		// XML speichern
		
		$oFile->store($this->buildFileName($oStructure, $oFile->getIndex()), $oDOM->saveXML());
		
		return $oFile;
	}

	/**
	 * Alle Headerinformationen in das XML schreiben
	 * 
	 * @param \SimpleXMLElement $oHeader
	 * @param \TcAccounting\Service\eInvoice\Service\Italy\XmlStructure $oStructure
	 */
	private function buildHeaderNodes(\SimpleXMLElement $oHeader, XmlStructure $oStructure) {
	
		$oTransmitter = $oStructure->getTransmitter();
		$oInvoiceContact = $oStructure->getInvoiceContact();
		$oInvoiceAddress = $oStructure->getInvoiceAddress();

		if(strlen($oInvoiceContact->codex) == 6) {
			$sTrasmissione = 'FPA12';
		} else if(strlen($oInvoiceContact->codex) == 7) {
			$sTrasmissione = 'FPR12';
		}
		
		$aHeader = [
			'DatiTrasmissione' => [
				'IdTrasmittente' => [ 
					'IdPaese' => $oTransmitter->address_country,
					'IdCodice' => $oTransmitter->transmission_tax_number,
				],
				'ProgressivoInvio' => 1,
				'FormatoTrasmissione' => $sTrasmissione,
				'CodiceDestinatario' => $oInvoiceContact->codex,
				'ContattiTrasmittente' => [
					'Telefono' => $oTransmitter->phone_number
				]
			],
			'CedentePrestatore' => [ // Daten des Rechnungsversenders
				'DatiAnagrafici' => [
					'IdFiscaleIVA' => [
						'IdPaese' => $oTransmitter->address_country,
						'IdCodice' => $oTransmitter->transmission_tax_number,
					],
					'CodiceFiscale' => $oTransmitter->transmission_tax_number,
					'Anagrafica' => [
						'Denominazione' => $oTransmitter->transmission_designation
					],
					'RegimeFiscale' => $oTransmitter->transmission_tax_system
				],
				'Sede' => [
					'Indirizzo' => $oTransmitter->address_street,
					'CAP' => $oTransmitter->address_zip,
					'Comune' => $oTransmitter->address_city,
					'Provincia' => $oTransmitter->address_state,
					'Nazione' => $oTransmitter->address_country,
				]
				// IscrizioneREA s.u.
			],
			'CessionarioCommittente' => [ // Daten des Kunden
				'DatiAnagrafici' => [], // s.u.
				'Sede' => [
					'Indirizzo' => $oInvoiceAddress->address,
					'CAP' => ($oInvoiceAddress->country_iso === 'IT' || strlen($oInvoiceAddress->zip) === 5) ? $oInvoiceAddress->zip : '00000',
					'Comune' => $oInvoiceAddress->city,
					'Provincia' => (empty($oInvoiceAddress->state) ? 'EE' : $oInvoiceAddress->state),
					'Nazione' => $oInvoiceAddress->country_iso,
				]
			]
		];

		// Darf nicht leer sein
		if(empty($aHeader['CedentePrestatore']['Sede']['Provincia'])) {
			unset($aHeader['CedentePrestatore']['Sede']['Provincia']);
		}
		if(empty($aHeader['CessionarioCommittente']['Sede']['Provincia'])) {
			unset($aHeader['CessionarioCommittente']['Sede']['Provincia']);
		}
		
		// Extra nodes
		
		$aExtraNodes = $oStructure->getExtraNodes();
		
		if(!empty($aExtraNodes)) {
			$aHeader['CessionarioCommittente']['Extra'] = [];
			foreach($aExtraNodes as $sNode => $sExtraValue) {
				$aHeader['CessionarioCommittente']['Extra'][$sNode] = $sExtraValue;
			}
		}
				
		// IscrizioneREA
		
		if(
			!empty($oTransmitter->transmission_register_office) &&	
			!empty($oTransmitter->transmission_register_number) &&	
			!empty($oTransmitter->transmission_capital) &&	
			!empty($oTransmitter->transmission_partner) &&	
			!empty($oTransmitter->transmission_liquidation)
		) {
			$aHeader['CedentePrestatore']['IscrizioneREA'] = [
				'Ufficio' => $oTransmitter->transmission_register_office,
				'NumeroREA' => $oTransmitter->transmission_register_number,
				'CapitaleSociale' => number_format($oTransmitter->transmission_capital, 2, '.', ''),
				'SocioUnico' => $oTransmitter->transmission_partner,
				'StatoLiquidazione' => $oTransmitter->transmission_liquidation,
			];
		}
		
		// DatiAnagrafici - Kundendaten
		// Remove country code if entry has one
		/*if (
			!empty($oInvoiceContact->steuer) &&
			str_starts_with($oInvoiceContact->steuer, $oInvoiceAddress->country_iso)
		) {
			$oInvoiceContact->steuer = substr($oInvoiceContact->steuer, 2);
		}*/

		if(
			!empty($oInvoiceContact->mwst) ||
			!empty($oInvoiceContact->steuer)
		) {
			// tax identification code (steuer) or tax number (mwst) provided
			if(!empty($oInvoiceContact->mwst)) {
				$aHeader['CessionarioCommittente']['DatiAnagrafici']['IdFiscaleIVA'] = [
					'IdPaese' => $oInvoiceAddress->country_iso,
					'IdCodice' => $oInvoiceContact->mwst,
				];
			} else {
				$aHeader['CessionarioCommittente']['DatiAnagrafici'] = [
					'CodiceFiscale' => $oInvoiceContact->steuer,
				];
			}

		} elseif (empty($oInvoiceAddress->company)) {
			// No company booking either, tax id code will be created from name
			$aHeader['CessionarioCommittente']['DatiAnagrafici']['IdFiscaleIVA'] = [
				'IdPaese' => $oInvoiceAddress->country_iso,
				'IdCodice' => \Illuminate\Support\Str::limit(\Illuminate\Support\Str::upper(\Illuminate\Support\Str::transliterate( $oInvoiceContact->lastname . $oInvoiceContact->firstname)), 16, '')
			];

		} else {
			// If you landed here, then the company tax identification code (steuer) or tax number (mwst) of the company was not entered, but should have
			$aHeader['CessionarioCommittente']['DatiAnagrafici']['IdFiscaleIVA'] = [
				'IdPaese' => $oInvoiceAddress->country_iso,
				'IdCodice' => '000000000'
			];
		}

		if(!empty($oInvoiceAddress->company)) {
			$aHeader['CessionarioCommittente']['DatiAnagrafici']['Anagrafica']['Denominazione'] = $oInvoiceAddress->company;
		} else {
			$aHeader['CessionarioCommittente']['DatiAnagrafici']['Anagrafica']['Nome'] = $oInvoiceContact->firstname;
			$aHeader['CessionarioCommittente']['DatiAnagrafici']['Anagrafica']['Cognome'] = $oInvoiceContact->lastname;
		}
					
		// Aus dem Array eine XML-Struktur generieren
				
		$this->buildXmlFromArray($oHeader, $aHeader);

	}
	
	/**
	 * Alle Bodyformationen in das XML schreiben
	 * 
	 * @param \SimpleXMLElement $oBody
	 * @param \TcAccounting\Service\eInvoice\Service\Italy\XmlStructure $oStructure
	 */
	private function buildBodyNodes(\SimpleXMLElement $oBody, XmlStructure $oStructure) {
		
		$oTransmitter = $oStructure->getTransmitter();
		$oDocument = $oStructure->getDocument();
		
		// Spezielle Formatklassen für das XML um das vorgegebene Format einzuhalten
		$oAmountFormat = new AmountFormat();
		$oDateFormat = new DateFormat();
		
		// Bei Storno und Gutschrift muss alles negiert werden		
		$bNegate = ($oDocument->isCancellation() || $oDocument->isCreditNote());
		
		$fAmount = $oDocument->getAmount();
		if($bNegate) {
			$fAmount = ($fAmount * -1);
		}
		
		$aBody = [
			'DatiGenerali' => [
				'DatiGeneraliDocumento' => [
					'TipoDocumento' => $oDocument->getType(),
					'Divisa' => $oDocument->getCurrency(),
					'Data' => $oDateFormat->format($oDocument->getDate()),
					'Numero' => $oDocument->getNumber(),
					'ImportoTotaleDocumento' => $oAmountFormat->format($fAmount),
					#'Causale' => ''
				]
			],
			'DatiBeniServizi' => [] // Items
		];
		
		// Rechnungspositionen
		
		$aLineItems = $oDocument->getItems();
		
		foreach($aLineItems as $iPosition => $oLineItem) {
			
			$aItem = [
				'NumeroLinea' => ($iPosition + 1),
				'CodiceArticolo' => [
					'CodiceTipo' => 'ID-TYPE',
					'CodiceValore' => $oLineItem->getItemType(),
				],
				
			];
			
			$fAmount = $oLineItem->getAmount();
			$fDiscountAmount = $oLineItem->getDiscountAmount();
			if($bNegate) {
				$fAmount = ($fAmount * -1);
				$fDiscountAmount = ($fDiscountAmount * -1);
			}
			
			if($oLineItem->getType() === DTO\LineItem::TYPE_DISCOUNT) {
				$aItem['TipoCessionePrestazione'] = 'SC';
			} 
			
			$aItem['Descrizione'] = $oLineItem->getDescription();
			$aItem['Quantita'] = $oAmountFormat->format($oLineItem->getQuantity());
   
			if($oLineItem->getType() === DTO\LineItem::TYPE_DISCOUNT) {
				$aItem['PrezzoUnitario'] = $oAmountFormat->format($fAmount);
				$aItem['PrezzoTotale'] = $oAmountFormat->format($fAmount);
			} else {
				
				if(!empty($oLineItem->getFrom())) {
					$aItem['DataInizioPeriodo'] = $oLineItem->getFrom();
				}
				
				if(!empty($oLineItem->getUntil())) {
					$aItem['DataInizioPeriodo'] = $oLineItem->getUntil();
				}
				
				$aItem['PrezzoUnitario'] = $oAmountFormat->format($fAmount);
				$aItem['PrezzoTotale'] = $oAmountFormat->format($fAmount);
				
				if($oLineItem->getType() === DTO\LineItem::TYPE_STANDARD_DISCOUNT) {
					$aItem['ScontoMaggiorazione'] = [
						'Typo' => 'SC',
						'Percentuale' => $oAmountFormat->format($oLineItem->getDiscountPercent()),
						'Importo' => $oAmountFormat->format($fDiscountAmount),						
					];
				}				
			}

			if ($oLineItem->getVatId()) {
				$aItem['AliquotaIVA'] = $oLineItem->getVatRate();
			} else {
				$aItem['AliquotaIVA'] = '0.00';
			}

			if ($oLineItem->getNatura()) {
				$aItem['Natura'] = $oLineItem->getNatura();
			}
			
			$aBody['DatiBeniServizi'][] = ['DettaglioLinee' => $aItem];
		}
		
		// Steuerinformationen
		
		$aVatItems = $oDocument->getVatItems();
		
		foreach($aVatItems as $aVat) {
			
			$fAmount = $aVat['amount'];
			if($bNegate) {
				$fAmount = ($fAmount * -1);
			}
			
			$aVat = [
				'Natura' => $aVat['natura'],
				'ImponibileImporto' => $oAmountFormat->format($fAmount),
				'Imposta' => '0.00',
				'RiferimentoNormativo' => $aVat['note'],
				'AliquotaIVA' => $oAmountFormat->format($aVat['rate'])
			];
			
			$aBody['DatiBeniServizi'][] = ['DatiRiepilogo' => $aVat];
		}
		
		// Paymentterms
		
		if(!$oDocument->isCreditNote() && !$oDocument->isCancellation()) {
			
			$aPaymentterms = $oDocument->getPaymentterms();
			
			$aBody['DatiPagamento'] = [
				[ 'CondizioniPagamento' => (count($aPaymentterms) === 1) ? 'TP02' : 'TP01']
			];
			
			foreach($aPaymentterms as $aPaymentterm) {
				$aBody['DatiPagamento'][] = [
					'DettaglioPagamento' => [
						'ModalitaPagamento' => 'MP05',
						'DataScadenzaPagamento' => $oDateFormat->format($aPaymentterm['due_date']),
						'ImportoPagamento' => $oAmountFormat->format($aPaymentterm['amount']),
						'IstitutoFinanziario' => $oTransmitter->transmission_bank_name,
						'IBAN' => $oTransmitter->transmission_iban
					]
 				];
			}
		}
		
		// PDF
		
		$aFileData = $oDocument->getFileData();
		
		if(!empty($aFileData)) {
			
			$sBase64File = base64_encode(file_get_contents($aFileData['file_path']));

			$aBody['Allegati'] = [
				'NomeAttachment' => $aFileData['name'],
				'FormatoAttachment' => 'PDF',
				'DescrizioneAttachment' => $aFileData['description'],
				'Attachment' => $sBase64File,
			];
		}
		
		// Aus dem Array eine XML-Struktur generieren
		
		$this->buildXmlFromArray($oBody, $aBody);
	}
	
	/**
	 * Generiert aus dem übergebenen Array eine XML-Struktur
	 * 
	 * @param \SimpleXMLElement $oRootElement
	 * @param array $aArray
	 */
	private function buildXmlFromArray(\SimpleXMLElement $oRootElement, array $aArray) {
		
		foreach($aArray as $sNodeName => $mNodeValue) {
			
			if(is_numeric($sNodeName)) {
				// Für Nodes mit demselben Namen in derselben Ebene
				$this->buildXmlFromArray($oRootElement, $mNodeValue);				
			} else {			
				if(is_array($mNodeValue)) {
					// rekursiver Aufruf
					$oSubRootElement = $oRootElement->addChild($sNodeName);
					$this->buildXmlFromArray($oSubRootElement, $mNodeValue);
				} else {
					$oRootElement->addChild($sNodeName, $mNodeValue);
				}
			}
			
		}
		
	}
	
	/**
	 * Generiert den Dateinamen für das XML
	 * 
	 * @param \TcAccounting\Service\eInvoice\Service\Italy\XmlStructure $oStructure
	 * @param int $iCount
	 * @return string
	 */
	private function buildFileName(XmlStructure $oStructure, int $iCount) : string {
		
		$oTransmitter = $oStructure->getTransmitter();
		
		$sFileName = strtoupper($oTransmitter->address_country).
				$oTransmitter->transmission_tax_number.'_'.
				$this->fillUp($iCount);
		
		return strtoupper(\Util::getCleanFilename($sFileName)).'.xml';
	}
	
	/**
	 * Füllt die übergebene Zahl mit Nullen auf (wichtig für Dateinamen)
	 * 
	 * @param int $iCount
	 * @return string
	 */
	private function fillUp(int $iCount) : string {
		return sprintf("%05d", $iCount);
	}
}