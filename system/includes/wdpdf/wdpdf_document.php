<?php


/*
 * -- webDynamics pdf classes --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Include from: /system/includes/wdpdf/wdpdf.php
 *
 * 
 */


class wdPDF_Document extends wdPDF {


	// ----- internal properties and get/set methods -----


	protected $_sAddressLine  = '';
	protected $_sCompany      = '';
	protected $_sFirstName    = '';
	protected $_sLastName     = '';
	protected $_sStreet       = '';
	protected $_sZIP          = '';
	protected $_sCity         = '';
	protected $_sCountry      = '';
	protected $_sSubject      = '';
	protected $_sHeadText     = '';
	protected $_sFootText     = '';
	protected $_aInvoiceItems = array(
		'show_vat_entries'            => true,                   // (boolean) show vat entries
		'pre_tax'                     => false,                  // (boolean) invoice items amount is pre tax
		'amount_after_tax'            => 0,                      // (float) sum of invoice items amount (after tax)
		'amount_pre_tax'              => 0,                      // (float) sum of invoice items amount (pre tax)
		'amount_per_vat'              => array(),                // (array) sum of invoice items amount per vat
		'list'                        => array(),                // (array) list of invoice items,
		'show_item_numbers'           => true,                   // (boolean) show invoice items number column
		'show_item_vat'               => true,                   // (boolean) show invoice items vat column
		'currency'                    => 'Euro',                 // (string) currency of the invoice items
		'value_separator'             => ',',                    // (string) separater for numeric values
		'value_precision'             => 2,                      // (int) precision of numeric values
		'internal_precision'          => 10,                     // (int) internal precision of numeric values
		'colname_number'              => 'Art-Nr.',              // (string) ...
		'colname_description'         => 'Beschreibung',         // (string) ...
		'colname_vat'                 => 'Mwst',                 // (string) ...
		'colname_quantity'            => 'Menge',                // (string) ...
		'colname_amount'              => 'Einzelpreis',          // (string) ...
		'colname_amount_total'        => 'Gesamtpreis',          // (string) ...
		'quantity_precision'          => 2,                      // (int) ...
		'vat_inclusive_text'          => 'inkl. %1$d %% Mwst',   // (string) ...
		'vat_added_text'              => 'zzgl. %1$d %% Mwst',   // (string) ...
		'amount_total_pre_tax_text'   => 'Gesamt (Netto)',       // (string) ...
		'amount_total_after_tax_text' => 'Gesamt (Brutto)',      // (string) ...
		'currency_info_text'          => 'Alle Preise in %1$s.', // (string) ...
		'show_currency_info_text'     => false,                  // (boolean) ...
		'show_short_currency'         => true,                   // (boolean) ...
		'short_currency'              => '€',                    // (string) ...
		'discount_amount'             => 0,                      // (float) ...
		'discount_text'               => 'Rabatt'                // (string) ...
	);


	public function __set($sName, $mValue) {

		// convert the passed arguments
		$sName = (string)$sName;

		// set the specified value
		switch ($sName) {

			// subject of the document
			case 'subject':
				$this->_sSubject = (string)$mValue;

			// call parent method
			default:
				parent::__set($sName, $mValue);

		}

		// we never get here ...

	}


	public function __get($sName) {

		// convert the passed arguments
		$sName = (string)$sName;

		// return the specified value
		switch ($sName) {

			// subject of the document
			case 'subject':
				$sReturn = (string)$this->_sSubject;
				return $sReturn;

			// total amount pre tax
			case 'amountTotalPreTax':
				$fReturn = (float)$this->_aInvoiceItems['amount_pre_tax'];
				return $fReturn;

			// total amount after tax
			case 'amountTotalAfterTax';
				$fReturn = (float)$this->_aInvoiceItems['amount_after_tax'];
				return $fReturn;

			// short currency name
			case 'shortCurrency':
				$sReturn = (string)$this->_aInvoiceItems['short_currency'];
				return $sReturn;

			// long currency name
			case 'longCurrency':
				$sReturn = (string)$this->_aInvoiceItems['currency'];
				return $sReturn;

			// list of invoice items
			case 'itemList':
				$aReturn = array();
				foreach ($this->_aInvoiceItems['list'] as $aItem) {
					$aReturn[] = array(
						'number'      => $aItem['number'],
						'description' => $aItem['description'],
						'vat'         => $aItem['vat'],
						'quantity'    => $aItem['quantity'],
						'amount'      => $aItem['amount']
					);
				}
				return $aReturn;

			// call parent method
			default:
				$mReturn = parent::__get($sName);
				return $mReturn;

		}

		// we never get here ...

	}


	// ----- constructor methods -----


	public function __construct(array $aConfig = array(), array $aData = array()) {
		parent::__construct($aConfig, $aData);
		$this->_processDocumentData($aData);
	}


	protected function _processDocumentData(array $aData) {
		if (array_key_exists('address_line', $aData)) {
			$this->_sAddressLine = $this->convertUTF8String($aData['address_line']);
		}
		if (array_key_exists('company', $aData)) {
			$this->_sCompany = $this->convertUTF8String($aData['company']);
		}
		if (array_key_exists('first_name', $aData)) {
			$this->_sFirstName = $this->convertUTF8String($aData['first_name']);
		}
		if (array_key_exists('last_name', $aData)) {
			$this->_sLastName = $this->convertUTF8String($aData['last_name']);
		}
		if (array_key_exists('street', $aData)) {
			$this->_sStreet = $this->convertUTF8String($aData['street']);
		}
		if (array_key_exists('zip', $aData)) {
			$this->_sZIP = $this->convertUTF8String($aData['zip']);
		}
		if (array_key_exists('city', $aData)) {
			$this->_sCity = $this->convertUTF8String($aData['city']);
		}
		if (array_key_exists('country', $aData)) {
			$this->_sCountry = $this->convertUTF8String($aData['country']);
		}
		if (array_key_exists('subject', $aData)) {
			$this->_sSubject = $this->convertUTF8String($aData['subject']);
		}
		if (array_key_exists('head_text', $aData)) {
			$this->_sHeadText = $this->convertUTF8String($aData['head_text']);
		}
		if (array_key_exists('foot_text', $aData)) {
			$this->_sFootText = $this->convertUTF8String($aData['foot_text']);
		}
		if (array_key_exists('invoice_items_pre_tax', $aData)) {
			$this->_aInvoiceItems['pre_tax'] = ($aData['invoice_items_pre_tax'] === true);
		}
		if (array_key_exists('invoice_short_currency', $aData)) {
			$this->_aInvoiceItems['short_currency'] = $this->convertUTF8String($aData['invoice_short_currency']);
		} else {
			$this->_aInvoiceItems['short_currency'] = $this->convertUTF8String($this->_aInvoiceItems['short_currency']);
		}
		if (array_key_exists('invoice_show_short_currency', $aData)) {
			$this->_aInvoiceItems['show_short_currency'] = ($aData['invoice_show_short_currency'] === true);
		}
		if (array_key_exists('invoice_currency_info_text', $aData)) {
			$this->_aInvoiceItems['currency_info_text'] = $this->convertUTF8String($aData['invoice_currency_info_text']);
		} else {
			$this->_aInvoiceItems['currency_info_text'] = $this->convertUTF8String($this->_aInvoiceItems['currency_info_text']);
		}
		if (array_key_exists('invoice_show_currency_info_text', $aData)) {
			$this->_aInvoiceItems['show_currency_info_text'] = ($aData['invoice_show_currency_info_text'] === true);
		}
/* ( these options should not be set in the configuration )
		if (array_key_exists('invoice_amount_total_pre_tax_text', $aData)) {
			$this->_aInvoiceItems['amount_total_pre_tax_text'] = $this->convertUTF8String($aData['invoice_amount_total_pre_tax_text']);
		} else {
			$this->_aInvoiceItems['amount_total_pre_tax_text'] = $this->convertUTF8String($this->_aInvoiceItems['amount_total_pre_tax_text']);
		}
		if (array_key_exists('invoice_amount_total_after_tax_text', $aData)) {
			$this->_aInvoiceItems['amount_total_after_tax_text'] = $this->convertUTF8String($aData['invoice_amount_total_after_tax_text']);
		} else {
			$this->_aInvoiceItems['amount_total_after_tax_text'] = $this->convertUTF8String($this->_aInvoiceItems['amount_total_after_tax_text']);
		}
*/
		if (array_key_exists('invoice_vat_inclusive_text', $aData)) {
			$this->_aInvoiceItems['vat_inclusive_text'] = $this->convertUTF8String($aData['invoice_vat_inclusive_text']);
		} else {
			$this->_aInvoiceItems['vat_inclusive_text'] = $this->convertUTF8String($this->_aInvoiceItems['vat_inclusive_text']);
		}
		if (array_key_exists('invoice_vat_added_text', $aData)) {
			$this->_aInvoiceItems['vat_added_text'] = $this->convertUTF8String($aData['invoice_vat_added_text']);
		} else {
			$this->_aInvoiceItems['vat_added_text'] = $this->convertUTF8String($this->_aInvoiceItems['vat_added_text']);
		}
		if (array_key_exists('invoice_colname_number', $aData)) {
			$this->_aInvoiceItems['colname_number'] = $this->convertUTF8String($aData['invoice_colname_number']);
		} else {
			$this->_aInvoiceItems['colname_number'] = $this->convertUTF8String($this->_aInvoiceItems['colname_number']);
		}
		if (array_key_exists('invoice_colname_description', $aData)) {
			$this->_aInvoiceItems['colname_description'] = $this->convertUTF8String($aData['invoice_colname_description']);
		} else {
			$this->_aInvoiceItems['colname_description'] = $this->convertUTF8String($this->_aInvoiceItems['colname_description']);
		}
		if (array_key_exists('invoice_colname_vat', $aData)) {
			$this->_aInvoiceItems['colname_vat'] = $this->convertUTF8String($aData['invoice_colname_vat']);
		} else {
			$this->_aInvoiceItems['colname_vat'] = $this->convertUTF8String($this->_aInvoiceItems['colname_vat']);
		}
		if (array_key_exists('invoice_colname_quantity', $aData)) {
			$this->_aInvoiceItems['colname_quantity'] = $this->convertUTF8String($aData['invoice_colname_quantity']);
		} else {
			$this->_aInvoiceItems['colname_quantity'] = $this->convertUTF8String($this->_aInvoiceItems['colname_quantity']);
		}
		if (array_key_exists('invoice_colname_amount', $aData)) {
			$this->_aInvoiceItems['colname_amount'] = $this->convertUTF8String($aData['invoice_colname_amount']);
		} else {
			$this->_aInvoiceItems['colname_amount'] = $this->convertUTF8String($this->_aInvoiceItems['colname_amount']);
		}
		if (array_key_exists('invoice_colname_amount_total', $aData)) {
			$this->_aInvoiceItems['colname_amount_total'] = $this->convertUTF8String($aData['invoice_colname_amount_total']);
		} else {
			$this->_aInvoiceItems['colname_amount_total'] = $this->convertUTF8String($this->_aInvoiceItems['colname_amount_total']);
		}
		if (array_key_exists('invoice_currency', $aData)) {
			$this->_aInvoiceItems['currency'] = $this->convertUTF8String($aData['invoice_currency']);
		} else {
			$this->_aInvoiceItems['currency'] = $this->convertUTF8String($this->_aInvoiceItems['currency']);
		}
		if (array_key_exists('invoice_value_separator', $aData)) {
			$this->_aInvoiceItems['value_separator'] = $this->convertUTF8String($aData['invoice_value_separator']);
		} else {
			$this->_aInvoiceItems['value_separator'] = $this->convertUTF8String($this->_aInvoiceItems['value_separator']);
		}
		if (array_key_exists('invoice_value_precision', $aData)) {
			$this->_aInvoiceItems['value_precision'] = intval($aData['invoice_value_precision']);
		}
		if (array_key_exists('invoice_internal_precision', $aData)) {
			$this->_aInvoiceItems['internal_precision'] = intval($aData['invoice_internal_precision']);
		}
		if (array_key_exists('invoice_quantity_precision', $aData)) {
			$this->_aInvoiceItems['quantity_precision'] = intval($aData['invoice_quantity_precision']);
		}
		if (array_key_exists('invoice_discount_amount', $aData)) {
			$this->_aInvoiceItems['discount_amount'] = floatval($aData['invoice_discount_amount']);
		}
		if (array_key_exists('invoice_discount_text', $aData)) {
			$this->_aInvoiceItems['discount_text'] = $this->convertUTF8String($aData['invoice_discount_text']);
		} else {
			$this->_aInvoiceItems['discount_text'] = $this->convertUTF8String($this->_aInvoiceItems['discount_text']);
		}
		if (array_key_exists('invoice_show_item_numbers', $aData)) {
			$this->_aInvoiceItems['show_item_numbers'] = ($aData['invoice_show_item_numbers'] === true);
		}
		if (array_key_exists('invoice_show_item_vat', $aData)) {
			$this->_aInvoiceItems['show_item_vat'] = ($aData['invoice_show_item_vat'] === true);
		}
		if (array_key_exists('invoice_items', $aData) && is_array($aData['invoice_items'])) {
			$this->_processInvoiceItems($aData['invoice_items']);
		}
		if (array_key_exists('block_items', $aData) && is_array($aData['block_items'])) {
			$this->_processBlockItems($aData['block_items']);
		}
		if (array_key_exists('variables', $aData) && is_array($aData['variables'])) {
			$this->addVariables($aData['variables']);
		}

	}

	protected function _processBlockItems($arrBlockItems) {
		$this->addDocumentItems($arrBlockItems);
	}

	protected function _processInvoiceItems(array $aItems) {
		// load required configuration values
		$iPrecision          = (int)$this->_aInvoiceItems['value_precision'];
		$iInternalPrecision  = (int)$this->_aInvoiceItems['internal_precision'];
		$iQuantityPrecision  = (int)$this->_aInvoiceItems['quantity_precision'];
		$bInvoiceItemsPreTax = (bool)$this->_aInvoiceItems['pre_tax'];
		// process invoice items
		foreach ($aItems as $aItem) {
			if (!is_array($aItem)) {
				continue;
			}
			$aItemData = array(
				'number'       => '',
				'description'  => '',
				'vat'          => 0,
				'quantity'     => 0,
				'amount'       => 0,
				'amount_total' => 0
			);
			if (array_key_exists('number', $aItem)) {
				$aItemData['number'] = $this->convertUTF8String($aItem['number']);
			}
			if (array_key_exists('description', $aItem)) {
				$aItemData['description'] = $this->convertUTF8String($aItem['description']);
			}
			if (array_key_exists('vat', $aItem)) {
				$aItemData['vat'] = intval($aItem['vat']);
			}
			if (array_key_exists('quantity', $aItem)) {
				$aItemData['quantity'] = bcadd(0, floatval($aItem['quantity']), $iQuantityPrecision);
			}
			if (array_key_exists('amount', $aItem)) {
				$aItemData['amount'] = bcadd(0, floatval($aItem['amount']), $iPrecision);
			}
			$aItemData['amount_total'] = bcmul(
				$aItemData['quantity'],
				$aItemData['amount'],
				$iInternalPrecision
			);
			if (!array_key_exists($aItemData['vat'], $this->_aInvoiceItems['amount_per_vat'])) {
				$this->_aInvoiceItems['amount_per_vat'][$aItemData['vat']] = 0.00;
			}
			$this->_aInvoiceItems['amount_per_vat'][$aItemData['vat']] = bcadd(
				$this->_aInvoiceItems['amount_per_vat'][$aItemData['vat']],
				$aItemData['amount_total'],
				$iInternalPrecision
			);
			$this->_aInvoiceItems['amount_after_tax'] = bcadd(
				$this->_aInvoiceItems['amount_after_tax'],
				$aItemData['amount_total'],
				$iInternalPrecision
			);
			$this->_aInvoiceItems['list'][] = $aItemData;
		}
		// calculate amount per vat
		// current state: array_sum($this->_aInvoiceItems['amount_per_vat']) = $this->_aInvoiceItems['amount_after_tax']
		foreach ($this->_aInvoiceItems['amount_per_vat'] as $iCurrentVatValue => $fCurrentVatAmount) {
			if ($iCurrentVatValue <= 0) {
				unset($this->_aInvoiceItems['amount_per_vat'][$iCurrentVatValue]);
				continue;
			}
			$fVatFactor = bcdiv($iCurrentVatValue, 100, $iInternalPrecision);
			$this->_aInvoiceItems['amount_per_vat'][$iCurrentVatValue] = bcmul(
				$fCurrentVatAmount,
				$fVatFactor,
				$iInternalPrecision
			);
			unset($fVatFactor);
		}
		// adjust/recalculate amount total if required
		$this->_aInvoiceItems['amount_pre_tax'] = $this->_aInvoiceItems['amount_after_tax'];
		if ($bInvoiceItemsPreTax === true) {
			foreach ($this->_aInvoiceItems['amount_per_vat'] as $iCurrentVatValue => $fCurrentVatAmount) {
				$this->_aInvoiceItems['amount_after_tax'] = bcadd(
					$this->_aInvoiceItems['amount_after_tax'],
					$fCurrentVatAmount,
					$iInternalPrecision
				);
			}
		} else {
			foreach ($this->_aInvoiceItems['amount_per_vat'] as $iCurrentVatValue => $fCurrentVatAmount) {
				$this->_aInvoiceItems['amount_pre_tax'] = bcsub(
					$this->_aInvoiceItems['amount_pre_tax'],
					$fCurrentVatAmount,
					$iInternalPrecision
				);
			}
		}
	}


	protected function _printInvoiceItemsTable() {
		// load required configuration values
		$bShowVatEntries          = (bool)$this->_aInvoiceItems['show_vat_entries'];
		$bInvoiceItemsPreTax      = (bool)$this->_aInvoiceItems['pre_tax'];
		$bShowItemNumbers         = (bool)$this->_aInvoiceItems['show_item_numbers'];
		$bShowItemVat             = (bool)$this->_aInvoiceItems['show_item_vat'];
		$iPrecision               = (int)$this->_aInvoiceItems['value_precision'];
		$iInternalPrecision       = (int)$this->_aInvoiceItems['internal_precision'];
		$sCurrency                = (string)$this->_aInvoiceItems['currency'];
		$sValueSeparator          = (string)$this->_aInvoiceItems['value_separator'];
		$fAmountTotalAfterTax     = (float)$this->_aInvoiceItems['amount_after_tax'];
		$fAmountTotalPreTax       = (float)$this->_aInvoiceItems['amount_pre_tax'];
		$aInvoiceItems            = (array)$this->_aInvoiceItems['list'];
		$aAmountPerVat            = (array)$this->_aInvoiceItems['amount_per_vat'];
		$sColnameNumber           = (string)$this->_aInvoiceItems['colname_number'];
		$sColnameDescription      = (string)$this->_aInvoiceItems['colname_description'];
		$sColnameVat              = (string)$this->_aInvoiceItems['colname_vat'];
		$sColnameQuantity         = (string)$this->_aInvoiceItems['colname_quantity'];
		$sColnameAmount           = (string)$this->_aInvoiceItems['colname_amount'];
		$sColnameAmountTotal      = (string)$this->_aInvoiceItems['colname_amount_total'];
		$iQuantityPrecision       = (int)$this->_aInvoiceItems['quantity_precision'];
		$sVatInclusiveText        = (string)$this->_aInvoiceItems['vat_inclusive_text'];
		$sVatAddedText            = (string)$this->_aInvoiceItems['vat_added_text'];
		$sTotalAmountPreTaxText   = (string)$this->_aInvoiceItems['amount_total_pre_tax_text'];
		$sTotalAmountAfterTaxText = (string)$this->_aInvoiceItems['amount_total_after_tax_text'];
		$sCurrencyInfoText        = (string)$this->_aInvoiceItems['currency_info_text'];
		$bShowCurrencyInfoText    = (bool)$this->_aInvoiceItems['show_currency_info_text'];
		$bShowShortCurrency       = (bool)$this->_aInvoiceItems['show_short_currency'];
		$sShortCurrency           = (string)$this->_aInvoiceItems['short_currency'];
		// generate short currency text
		$sShortCurrencyText = '';
		if ($bShowShortCurrency == true) {
			$sShortCurrencyText = ' '.$sShortCurrency;
		}
		// get pdf object
		$objFPDI = $this->_oFPDI;
		// inner margin left
		$iInnerMargin = 5;
		// outer margin left
		$iOuterMargin = 1;
		// header (column names, width and align)
		$aHeader      = array();
		$aColumnWidth = array();
		$aColumnAlign = array();
		if ($bShowItemNumbers == true) {
			$aHeader[]      = $sColnameNumber;
			$aColumnWidth[] = '20';
			$aColumnAlign[] = 'L';
		}
		$aHeader[]      = $sColnameDescription;
		$aColumnAlign[] = 'L';
		if ($bShowItemVat == true) {
			if ($bShowItemNumbers == true) {
				$aColumnWidth[] = '50';
			} else {
				$aColumnWidth[] = '70';
			}
			$aHeader[]      = $sColnameVat;
			$aColumnWidth[] = '20';
			$aColumnAlign[] = 'R';
		} else {
			if ($bShowItemNumbers == true) {
				$aColumnWidth[] = '70';
			} else {
				$aColumnWidth[] = '90';
			}
		}
		$aHeader[] = $sColnameQuantity;
		$aColumnWidth[] = '20';
		$aColumnAlign[] = 'R';
		$aHeader[] = $sColnameAmount;
		$aColumnWidth[] = '28';
		$aColumnAlign[] = 'R';
		$aHeader[] = $sColnameAmountTotal;
		$aColumnWidth[] = '28';
		$aColumnAlign[] = 'R';
		// general settings
		$objFPDI->SetDrawColor(0, 0, 0);
		// print header
		$objFPDI->SetFont('helvetica', '', 10);
		$objFPDI->Line(15 + $iOuterMargin, $objFPDI->GetY() + 10, 190, $objFPDI->GetY() + 10);
		$objFPDI->SetXY(20, $objFPDI->GetY() + 10);
		for ($i = 0; $i < count($aHeader); $i++) {
			$objFPDI->Cell($aColumnWidth[$i], 7, $aHeader[$i], 0, 0, $aColumnAlign[$i]);
		}
		$objFPDI->Ln();
		$objFPDI->Line(15 + $iOuterMargin, $objFPDI->GetY(), 190, $objFPDI->GetY());
		$objFPDI->SetXY(20, $objFPDI->GetY());
		// print invoice items
		$objFPDI->SetFont('helvetica', '', 10);
		foreach ($aInvoiceItems as $aItem) {
			// set start position
			$objFPDI->SetX(20);
			$i = 0;
			// column "number"
			if ($bShowItemNumbers == true) {
				$objFPDI->Cell(
					$aColumnWidth[$i],
					6,
					$aItem['number'],
					0,
					0,
					$aColumnAlign[$i]
				);
				$i++;
			}
			// column "description"
			$objFPDI->Cell(
				$aColumnWidth[$i],
				6,
				$aItem['description'],
				0,
				0,
				$aColumnAlign[$i]
			);
			$i++;
			// column "vat"
			if ($bShowItemVat == true) {
				$objFPDI->Cell(
					$aColumnWidth[$i],
					6,
					$aItem['vat'].' %',
					0,
					0,
					$aColumnAlign[$i]
				);
				$i++;
			}
			// column "quantity"
			$objFPDI->Cell(
				$aColumnWidth[$i],
				6,
				number_format($aItem['quantity'], $iQuantityPrecision, $sValueSeparator, ''),
				0,
				0,
				$aColumnAlign[$i]
			);
			$i++;
			// column "amount"
			$objFPDI->Cell(
				$aColumnWidth[$i],
				6,
				number_format($aItem['amount'], $iPrecision, $sValueSeparator, '').$sShortCurrencyText,
				0,
				0,
				$aColumnAlign[$i]
			);
			$i++;
			// column "amount_total"
			$objFPDI->Cell(
				$aColumnWidth[$i],
				6,
				number_format($aItem['amount_total'], $iPrecision, $sValueSeparator, '').$sShortCurrencyText,
				0,
				0,
				$aColumnAlign[$i]
			);
			$i++;
			// new line
			$objFPDI->SetY($objFPDI->GetY() + 5);
		}
		// line after invoice items
		$objFPDI->Line(15 + $iOuterMargin, $objFPDI->GetY()+1, 190, $objFPDI->GetY()+1);
		$objFPDI->SetXY(20, $objFPDI->GetY());
		// calculate colum width for the left column
		$iColumnWidth = 0;
		for ($i = 0; $i < count($aColumnWidth)-1; $i++) {
			$iColumnWidth += $aColumnWidth[$i];
		}
		// print total amount pre tax if required
		if ($bInvoiceItemsPreTax == true) {
			$objFPDI->SetFont('helvetica', '', 10);
			$objFPDI->SetY($objFPDI->GetY() + 1);
			$objFPDI->SetX(20);
			$objFPDI->Cell(
				$iColumnWidth,
				6,
				$sTotalAmountPreTaxText,
				0,
				0,
				'L'
			);
			$objFPDI->Cell(
				$aColumnWidth[count($aColumnWidth)-1],
				6,
				number_format($fAmountTotalPreTax, $iPrecision, $sValueSeparator, '').$sShortCurrencyText,
				0,
				0,
				$aColumnAlign[count($aColumnAlign)-1]
			);
			$objFPDI->SetY($objFPDI->GetY() + 5);
			$objFPDI->Line(15 + $iOuterMargin, $objFPDI->GetY()+1, 190, $objFPDI->GetY()+1);
			$objFPDI->SetXY(20, $objFPDI->GetY());
		}
		// print vat amounts if required
		if ($bShowVatEntries == true && count($aAmountPerVat) > 0) {
			$objFPDI->SetFont('helvetica', '', 10);
			foreach ($aAmountPerVat as $iCurrentVatValue => $fCurrentVatAmount) {
				$objFPDI->SetY($objFPDI->GetY() + 1);
				$objFPDI->SetX(20);
				if ($bInvoiceItemsPreTax == true) {
					$objFPDI->Cell(
						$iColumnWidth,
						6,
						sprintf($sVatAddedText, $iCurrentVatValue),
						0,
						0,
						'L'
					);
				} else {
					$objFPDI->Cell(
						$iColumnWidth,
						6,
						sprintf($sVatInclusiveText, $iCurrentVatValue),
						0,
						0,
						'L'
					);
				}
				$objFPDI->Cell(
					$aColumnWidth[count($aColumnWidth)-1],
					6,
					number_format($fCurrentVatAmount, $iPrecision, $sValueSeparator, '').$sShortCurrencyText,
					0,
					0,
					$aColumnAlign[count($aColumnAlign)-1]
				);
				$objFPDI->SetY($objFPDI->GetY() + 4);
			}
			$objFPDI->SetY($objFPDI->GetY() + 1);
			$objFPDI->Line(15 + $iOuterMargin, $objFPDI->GetY()+1, 190, $objFPDI->GetY()+1);
			$objFPDI->SetXY(20, $objFPDI->GetY());
		}
		// print total amount after tax
		$objFPDI->SetFont('helvetica', '', 10);
		$objFPDI->SetY($objFPDI->GetY() + 1);
		$objFPDI->SetX(20);
		$objFPDI->Cell(
			$iColumnWidth,
			6,
			$sTotalAmountAfterTaxText,
			0,
			0,
			'L'
		);
		$objFPDI->Cell(
			$aColumnWidth[count($aColumnWidth)-1],
			6,
			number_format($fAmountTotalAfterTax, $iPrecision, $sValueSeparator, '').$sShortCurrencyText,
			0,
			0,
			$aColumnAlign[count($aColumnAlign)-1]
		);
		// print line after amount after tax
		$objFPDI->SetY($objFPDI->GetY() + 5);
		$objFPDI->Line(15 + $iOuterMargin, $objFPDI->GetY()+1, 190, $objFPDI->GetY()+1);
		$objFPDI->SetXY(20, $objFPDI->GetY());
		// print currency info text in required
		if ($bShowCurrencyInfoText == true) {
			$objFPDI->SetFont('helvetica', '', 10);
			$objFPDI->SetY($objFPDI->GetY() + 1);
			$objFPDI->SetX(20);
			$objFPDI->Cell(
				array_sum($aColumnWidth),
				6,
				sprintf($sCurrencyInfoText, $sCurrency),
				0,
				0,
				'L'
			);
		}
	}


	// ----- document generation methods -----


	public function generateDocument() {
		$objFPDI = $this->_oFPDI;
		// address line
		$objFPDI->SetFont('helvetica', '', 8);
		$objFPDI->SetY(51);
		$objFPDI->Cell(0, 0, $this->_sAddressLine);
		// recipient
		$objFPDI->SetFont('helvetica', '', 10);
		$sRecipient  = $this->_sCompany."\n";
		$sRecipient .= $this->_sFirstName." ".$this->_sLastName."\n";
		$sRecipient .= $this->_sStreet."\n";
		$sRecipient .= $this->_sZIP." ".$this->_sCity."\n\n";
		$sRecipient .= $this->_sCountry;
		$objFPDI->SetY(54);
		$objFPDI->MultiCell(0, 4.5, $sRecipient, 0, 'L');
		// subject
		$objFPDI->SetFont('helvetica', 'B', 11);
		$objFPDI->SetY(95);
		$objFPDI->Cell(0, 0, $this->_sSubject, 0, 'L');
		// head text
		$objFPDI->SetFont('helvetica', '', 10);
		$objFPDI->SetY($objFPDI->GetY() + 8);
		$objFPDI->Write(5, $this->_sHeadText);
		// invoice items table if required
		if (count((array)$this->_aInvoiceItems['list']) > 0) {
			$this->_printInvoiceItemsTable();
		}
		// foot text
		$objFPDI->SetFont('helvetica', '', 10);
		$objFPDI->SetY($objFPDI->GetY() + 8);
		$objFPDI->Write(5, $this->_sFootText);
	}


}
