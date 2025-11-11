<?php

class Ext_Thebing_Inquiry_Document_Version_History {
	
	/**
	 * @var Ext_Thebing_Inquiry_Document_Version
	 */
	protected $_oVersion;
	
	protected $_sDescription;
	protected $_oSchool;
	protected $_iCurrency;
	protected $_sHistoryType;
	protected $_bLastDocumentView;
	
	public function __construct(Ext_Thebing_Inquiry_Document_Version $oVersion) {
		$this->_oVersion = $oVersion;
	}

	public function setL10NDescription($sDescription) {
		$this->_sDescription = $sDescription;
	}
	
	public function setSchool($oSchool) {
		$this->_oSchool = $oSchool;
	}
	
	public function setCurrency($iCurrency) {
		$this->_iCurrency = $iCurrency;
	}
	
	public function setHistoryType($sHistoryType) {
		$this->_sHistoryType = $sHistoryType;
	}

	public function setLastDocumentView($bLastDocumentView) {
		$this->_bLastDocumentView = $bLastDocumentView;
	}

	protected function _getCacheKey() {
		$sCacheKey = 'Ext_Thebing_Inquiry_Document_Version_History_'.$this->_oVersion->id;
		$sCacheKey .= '_'.$this->_sDescription;
		$sCacheKey .= '_'.$this->_oSchool->id;
		$sCacheKey .= '_'.$this->_iCurrency;
		$sCacheKey .= '_'.$this->_sHistoryType;
		$sCacheKey .= '_'.$this->_bLastDocumentView;
		$sCacheKey .= '_'.$this->_oVersion->getDocument()->active;
		return $sCacheKey;
	}
	
	/**
	 * Generiert einen Historyeintrag für eine DokumentenVersion
	 * Schreibt Inhalt für eine Woche in den Cache
	 * @param string $sNumber
	 * @return string
	 */
	public function generate($sNumber) {

		$sCacheKey = $this->_getCacheKey();
		$sHistoryHtml = null;

		// Nur cachen, wenn Debug-Modus aus ist
		if(System::d('debugmode') == 0) {
			$sHistoryHtml = WDCache::get($sCacheKey);
		}

		if($sHistoryHtml === null) {

			$aExclusive = $this->_oSchool->getTaxExclusive();

			$oDocument = $this->_oVersion->getDocument();

			$sType			= $oDocument->getLabel();
			$sDocumentType	= $oDocument->type;
			$oInquiry		= $oDocument->getInquiry();

			if($oInquiry->group_id <= 0) {
				$iAmount		= $this->_oVersion->getAmount();
			} else {
				$iAmount		= $this->_oVersion->getGroupAmount();
			}

			$iTax			= (int)$this->_oVersion->tax;

			$iDate			= $this->_oVersion->created;

			$sDate			= Ext_Thebing_Format::LocalDate($iDate, $this->_oSchool->id).' '.Ext_Thebing_Format::LocalTime($iDate, $this->_oSchool->id);

			switch($sDocumentType) {
				case 'creditnote':
				case 'proforma_creditnote':
					$fAmount = $iAmount;// - $oVersion->getAmount(true, true, 'netto');
					$mAmount = Ext_Thebing_Format::Number($fAmount, $this->_iCurrency, $this->_oSchool->id);
					break;
				default:
					$mAmount = Ext_Thebing_Format::Number($iAmount, $this->_iCurrency, $this->_oSchool->id);
					break;
			}

			$sComment		= $this->_oVersion->comment;
			$oVersUser		= Ext_Thebing_User::getInstance($this->_oVersion->creator_id);
			$sUser			= $oVersUser->name;
			$sPdfPath		= $this->_oVersion->path;

			$oPdfFormat = new Ext_Thebing_Gui2_Format_Pdf(null);
			$sActions = $oPdfFormat->format($sPdfPath);

			// Nur anzeigen wenn auch vorhanden
//			if(
//				$sPdfPath != '' &&
//				is_file(Util::getPathWithRoot('storage'.$sPdfPath))
//			) {
//				$sButtonPdfInvoice = '<img title="'.L10N::t('PDF öffnen', $this->_sDescription).'" alt="'.L10N::t('PDF öffnen', $this->_sDescription).'" src="'.Ext_Thebing_Util::getIcon('pdf').'" style="cursor:pointer;" onclick="window.open(\'/storage/download'.$sPdfPath.'\'); return false"/>';
//			} else {
//				// PDF nicht vorhanden -> neu generieren
//				$sButtonPdfInvoice = '<img title="'.L10N::t('PDF fehlt', $this->_sDescription).'" alt="'.L10N::t('PDF fehlt', $this->_sDescription).'" src="'.Ext_Thebing_Util::getIcon('pdf_inactive').'" style="cursor:pointer;" onclick="alert(\''.L10N::t('PDF fehlt bitte neu generieren', $this->_sDescription).'\'); return false"/> ';
//			}
//
//			$sActions = $sButtonPdfInvoice;

			// Setzen, damit die Items nicht neu berechnet werden
			$this->_oVersion->bDoNotBuildNewItems = true;

			if($oInquiry->group_id <= 0) {
				$aItems = $this->_oVersion->getItems(null, false, false, true, true);
			} else {
				$aItems = $this->_oVersion->getGroupItems(null, false, false, true, false, true);
			}

			## START Steuern müssen hier manuell hinzugefügt werden da wir auch 'generals' array benötigen
			if($oInquiry->id > 0) {
				$aDataTax = Ext_TS_Vat::addTaxRows($aItems, $oInquiry, System::getInterfaceLanguage());
				$aItems = $aDataTax['items'];
				$aGeneral = $aDataTax['general'];
			} else {
				$aItems = array();
				$aGeneral = array();
			}
			## ENDE

			$sStyle = "";

			if($oDocument->active == 0) {
				$sStyle  = "background:".Ext_Thebing_Util::getColor('storno').";";					
			}

			// Gesamtzeile
			$aTotal = array();
			$aTotal['b'] = 0;
			$aTotal['p'] = 0;
			$aTotal['n'] = 0;

			// IDs für Firebug
			$sIds = 'data-document-id="'.$oDocument->id.'" data-version-id="'.$this->_oVersion->id.'"';

			switch($this->_sHistoryType)
			{
				case 'insurance':
				{
					$sHistoryHtml	.= '
						<tr '.$sIds.' style="'.$sStyle.'">
							<td>'.$this->_oVersion->version.'</td>
							<td class="alignRight">'.$sType.'</td>
							<td>'.$sDate.'</td>
							<td>'.$sComment.'</td>
							<td>'.$sUser.'</td>
							<td class="tdActionImg">'.$sActions.'</td>
						</tr>
					';

					break;
				}
				case 'additional_document':
				{
					$sHistoryHtml	.= '
						<tr '.$sIds.' style="'.$sStyle.'">
							<td>'.Ext_Thebing_Document::getTemplateTitle($this->_oVersion->template_id).'</td>
							<td>'.$sNumber.'</td>
							<td>'.$this->_oVersion->version.'</td>
							<td>'.$sDate.'</td>
							<td>'.$sComment.'</td>
							<td>'.$sUser.'</td>
							<td class="tdActionImg">'.$sActions.'</td>
						</tr>
					';

					break;
				}
				default:
				{
					$sHistoryHtml	.= '
						<tr '.$sIds.' style="'.$sStyle.'cursor:pointer;">
							<td onclick="this.up(\'tr\').next(\'tr\').toggle();">'.$sNumber.'</td>
							<td class="alignRight" onclick="this.up(\'tr\').next(\'tr\').toggle();">'.$this->_oVersion->version.'</td>
							<td onclick="this.up(\'tr\').next(\'tr\').toggle();">'.$sType.'</td>
							<td onclick="this.up(\'tr\').next(\'tr\').toggle();">'.$sDate.'</td>
							<td onclick="this.up(\'tr\').next(\'tr\').toggle();" class="amount">'.$mAmount.'</td>
							<td onclick="this.up(\'tr\').next(\'tr\').toggle();">'.$sComment.'</td>
							<td onclick="this.up(\'tr\').next(\'tr\').toggle();">'.$sUser.'</td>
							<td class="tdActionImg">'.$sActions.'</td>
						</tr>
					';

					break; 
				}
			}

			if(
				$this->_sHistoryType != 'insurance' &&
				$this->_sHistoryType != 'additional_document'
			) {

				$sHistoryHtml	.= '
						<tr style="display:none;" >
							<td colspan="8" style="background: #f7f7f7; padding: 0px;">

								<table class="table table_document_history_items">
									<tr>
										<th>'.L10N::t('Beschreibung', $this->_sDescription).'</th>
										<th style="width: 100px">'.L10N::t('Brutto', $this->_sDescription).'</th>';

				$bShowCreditnote = false;
				$bShowNet = false;

				if($sDocumentType !== 'storno') {
					if(strpos($sDocumentType, 'netto') !== false) {
						$bShowNet = true;
					} elseif(strpos($sDocumentType, 'creditnote') !== false) {
						$bShowCreditnote = true;
					}				
				} else {
					if($this->_bLastDocumentView == 'net') {
						$bShowNet = true;
					} elseif($this->_bLastDocumentView == 'creditnote') {
						$bShowCreditnote = true;
					}
				}

				if($bShowNet) {
					$sHistoryHtml	.= '
										<th style="width: 100px">'.L10N::t('Provision', $this->_sDescription).'</th>
										<th style="width: 100px">'.L10N::t('Netto', $this->_sDescription).'</th>
											';
				} elseif($bShowCreditnote) {
					$sHistoryHtml	.= '
										<th style="width: 100px">'.L10N::t('Netto', $this->_sDescription).'</th>
										<th style="width: 100px">'.L10N::t('Provision', $this->_sDescription).'</th>
											';
				}

				// Steuerspalte.
				if($iTax > 0) {
					if($iTax == 1) {
						$sVatLbl = L10N::t('inkl. VAT', $this->_sDescription);
					}
					if($iTax == 2) {
						$sVatLbl = L10N::t('zzgl. VAT', $this->_sDescription);
					}
					$sHistoryHtml .= '<th style="width: 140px">' . $sVatLbl . '</th>';
				}
				$sHistoryHtml	.= '</tr>';

				foreach((array)$aItems as $aItem) {
					// Nur Spalten anzeigen die auf dem PDF sind
					if($aItem['onPdf'] == 1) {
						// Gesamtzeilen erhöhen
						$aTotal['b'] += $aItem['amount'];
						$aTotal['p'] += $aItem['amount_provision'];
						$aTotal['n'] += $aItem['amount_net'];

						$sHistoryHtml	.= '
												<tr data-item-id="'.$aItem['id'].'">
													<td>'.$aItem['description'].'</td>
													<td class="amount_td">'.Ext_Thebing_Format::Number($aItem['amount'], $this->_iCurrency, $this->_oSchool->id).'</td>';

						$fVatAmount = 0;

						if($bShowCreditnote) {

							$sHistoryHtml		.= '
													<td class="amount_td">'.Ext_Thebing_Format::Number($aItem['amount_net'], $this->_iCurrency, $this->_oSchool->id).'</td>
													';

							//wurde eingebaut wegen #1736
							$fAmountProvision = round($aItem['amount'],2) - round($aItem['amount_net'],2);

							$sHistoryHtml		.= '
													<td class="amount_td">'.Ext_Thebing_Format::Number($fAmountProvision, $this->_iCurrency, $this->_oSchool->id).'</td>
													';
							$fVatAmount			= $aItem['amount_commission_vat'];

						} elseif($bShowNet) {
							$sHistoryHtml		.= '
													<td class="amount_td">'.Ext_Thebing_Format::Number($aItem['amount_provision'], $this->_iCurrency, $this->_oSchool->id).'</td>
													';
							$sHistoryHtml		.= '
													<td class="amount_td">'.Ext_Thebing_Format::Number($aItem['amount_net'], $this->_iCurrency, $this->_oSchool->id).'</td>
													';

							$fVatAmount			= $aItem['amount_net_vat'];
						} else {

							$fVatAmount			= $aItem['amount_vat'];
						}

						if($iTax > 0) {
							$sVat = '';
							if($aItem['tax_category'] > 0) {
								if(in_array(0, $aExclusive)) {
									// % Anzeige
									$sVat = ' (' . $aItem['tax'] . '%)';
								}
								if(in_array(1, $aExclusive)) {
									// Währung Anzeige
									$sVat = Ext_Thebing_Format::Number($fVatAmount, $this->_iCurrency, $this->_oSchool->id) . $sVat;
									//$sVat = $sVat;
								}
							}

							// Prüfen ob sich der Steuersatz verändert hat
							$sStyle = '';

							$sHistoryHtml .= '<td class="amount_td" style="' . $sStyle . '">' . $sVat . '</td>';
						}

						$sHistoryHtml	.= '	</tr>';
					}
				}

				## START Total Zeile
				$sHistoryHtml .= '<tr>';
				$sHistoryHtml .= '<td class="line"> '.L10N::t('Gesamt', $this->_sDescription).' </td>';
				$sHistoryHtml .= '<td class="line amount_td"> '.Ext_Thebing_Format::Number($aTotal['b'], $this->_iCurrency, $this->_oSchool->id).' </td>';
				if($bShowCreditnote) {
					$sHistoryHtml .= '<td class="line amount_td">'.Ext_Thebing_Format::Number($aTotal['n'], $this->_iCurrency, $this->_oSchool->id).'</td>';
					$sHistoryHtml .= '<td class="line amount_td">'.Ext_Thebing_Format::Number($aTotal['p'], $this->_iCurrency, $this->_oSchool->id).'</td>';
				}
				if($bShowNet) {
					$sHistoryHtml .= '<td class="line amount_td">'.Ext_Thebing_Format::Number($aTotal['p'], $this->_iCurrency, $this->_oSchool->id).'</td>';
					$sHistoryHtml .= '<td class="line amount_td">'.Ext_Thebing_Format::Number($aTotal['n'], $this->_iCurrency, $this->_oSchool->id).'</td>';
				}
				// Steuern
				if($iTax > 0) {
					$sHistoryHtml .= '<td class="line amount_td"></td>';
				}
				$sHistoryHtml .= '</tr>';
				## ENDE

				if($iTax > 0) {
					## START Steuer Zeilen
					// Gesamt zzgl. Externe Steuern
					$aTotal['v'] = 0;

					foreach((array)$aGeneral as $iVat => $aDataGeneral) {
						$sHistoryHtml .= '<tr>';
						if($bShowCreditnote) {
							$sHistoryHtml .= '<td> ' . $aDataGeneral['description'] . ' (' . Ext_Thebing_Format::Number($aDataGeneral['amount_commission_vat'], $this->_iCurrency, $this->_oSchool->id) . ')</td>';
							$sHistoryHtml .= '<td class="amount_td"></td>';
							$sHistoryHtml .= '<td class="amount_td"></td>';
							$sHistoryHtml .= '<td class="amount_td">'.Ext_Thebing_Format::Number($aDataGeneral['amountProv'], $this->_iCurrency, $this->_oSchool->id).'</td>';
							if($iTax == 2) {
								$aTotal['v'] += $aDataGeneral['amountProv'];
							}
						} elseif($bShowNet) {
							$sHistoryHtml .= '<td> ' . $aDataGeneral['description'] . ' (' . Ext_Thebing_Format::Number($aDataGeneral['amount_net_vat'], $this->_iCurrency, $this->_oSchool->id) . ')</td>';
							$sHistoryHtml .= '<td class="amount_td"></td>';
							$sHistoryHtml .= '<td class="amount_td"></td>';
							$sHistoryHtml .= '<td class="amount_td">'.Ext_Thebing_Format::Number($aDataGeneral['amountNet'], $this->_iCurrency, $this->_oSchool->id).'</td>';
							if($iTax == 2) {
								$aTotal['v'] += $aDataGeneral['amountNet'];
							}
						} else {
							$sHistoryHtml .= '<td> ' . $aDataGeneral['description'] . ' (' . Ext_Thebing_Format::Number($aDataGeneral['amount_vat'], $this->_iCurrency, $this->_oSchool->id) . ')</td>';
							$sHistoryHtml .= '<td class="amount_td">'.Ext_Thebing_Format::Number($aDataGeneral['amount'], $this->_iCurrency, $this->_oSchool->id).'</td>';
							if($iTax == 2) {
								$aTotal['v'] += $aDataGeneral['amount'];
							}
						}
						// Steuern
						$sHistoryHtml .= '<td></td>';

						$sHistoryHtml .= '</tr>';
					}
					## ENDE

					## TOTAL Zeile + Vat
					$sHistoryHtml .= '<tr>';
					$sHistoryHtml .= '<td class="line">'.L10N::t('Gesamt', $this->_sDescription).'</td>';
					if($bShowCreditnote) {
						$sHistoryHtml .= '<td class="line amount_td"></td>';
						$sHistoryHtml .= '<td class="line amount_td"></td>';
						$sHistoryHtml .= '<td class="line amount_td">'.Ext_Thebing_Format::Number($aTotal['p'] + $aTotal['v'], $this->_iCurrency, $this->_oSchool->id).'</td>';
					} elseif($bShowNet) {
						$sHistoryHtml .= '<td class="line amount_td"></td>';
						$sHistoryHtml .= '<td class="line amount_td"></td>';
						$sHistoryHtml .= '<td class="line amount_td">'.Ext_Thebing_Format::Number($aTotal['n'] + $aTotal['v'], $this->_iCurrency, $this->_oSchool->id).'</td>';
					} else {
						$sHistoryHtml .= '<td class="line amount_td"> '.Ext_Thebing_Format::Number($aTotal['b'] + $aTotal['v'], $this->_iCurrency, $this->_oSchool->id).' </td>';
					}
					// Steuern
					$sHistoryHtml .= '<td class="line amount_td"></td>';

					$sHistoryHtml .= '</tr>';
					## ENDE
				}

				$sHistoryHtml	.= '
								</table>

							</td>
						</tr>';
			}

			WDCache::set($sCacheKey, (30*24*60*60), $sHistoryHtml);

		}

		return $sHistoryHtml;
	}
	
}
