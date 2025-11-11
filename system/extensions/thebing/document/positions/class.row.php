<?php


class Ext_Thebing_Document_Positions_Row {

	public $aPosition = array();
	public $aPositionColumns = array();
	public $aTaxCategories = array();
	public $sDocumentType;
	public $oDocument;
	public $bDetailView = false;

	/**
	 * 0: Readonly (komplett)
	 * 1: Editierbar
	 * 2: Readonly (nur Beschreibung editierbar)
	 * 3: Disabled
	 *
	 * @var int
	 */
	public $iEditable = 1;

	public $bDiscount = false;
	public $iDefaultTaxCategory = 0;
	public bool $taxCategoryEditable = false;
	public $iSchoolId = null;
	public $bPositionTable = false;
	public $iTemplateId = 0;
	public $bLast = false;
	public $bPositionsEditable = true;
	public $bCommissionEditable = false;
	protected $_aStyles = array();

	public function __construct() {
		$this->_aStyles = self::getStyles();
	}

	public static function getStyles() {

		$aStyles = array();

		$aStyles['new'] = array('color'=>Ext_Thebing_Util::getColor('bad'), 'label'=>L10N::t('Neue Position', Ext_Thebing_Document::$sL10NDescription)); // rot
		$aStyles['edit'] = array('color'=>Ext_Thebing_Util::getColor('neutral'), 'label'=>L10N::t('Veränderte Position', Ext_Thebing_Document::$sL10NDescription)); // gelb
		$aStyles['delete'] = array('color'=>Ext_Thebing_Util::getColor('storno'), 'label'=>L10N::t('Gelöschte Position', Ext_Thebing_Document::$sL10NDescription)); // gelb
		$aStyles['old'] = array('color'=>Ext_Thebing_Util::getColor('good'), 'label'=>L10N::t('Unveränderte Position', Ext_Thebing_Document::$sL10NDescription)); // grün

		return $aStyles;

	}

	/**
	 * Write dialog positions
	 */
	public function generateHtml(&$aTotalAmounts, &$aTaxAmounts) {

		$bDisplayOnly = false;

		$iTemplateId = $this->iTemplateId;

		$oTemplate = Ext_Thebing_Pdf_Template::getInstance((int)$iTemplateId);

		$sTemplateType = $oTemplate->type;

		$aItem = $this->aPosition;

		$iTotalItemsCount = is_array($aItem['items']) ? count($aItem['items']) : 0;

		if($iTotalItemsCount > 0) {
			$bDisplayOnly = true;
		}

		// Diff Positionen dürfen nicht editierbar sein
		$sReadonly = '';
		$sReadonlyClass = '';
		if(
			$this->iEditable == 0 || 
			$this->iEditable == 2
		) {
			$sReadonly = ' readonly="readonly" ';
			$sReadonlyClass = ' readonly ';
		} elseif($this->iEditable == 3) {
			$sReadonly = ' disabled="disabled" ';
			$sReadonlyClass = ' readonly ';
		}

		$sOriginalReadonly = $sReadonly;

		$sClass = ' displayContainer';
		$sStyle = '';

		if(
			!$aItem['status']
		) {
			$aItem['status'] = 'old'; 
		}

		$sStyle = 'background:'.$this->_aStyles[$aItem['status']]['color'].'; ';

		// Wenn die PDF Checkbox NICHT angeklickt ist
		if(
			$aItem['onPdf'] != 1 &&
			$aItem['position_key'] != 'XXX'
		) {
			$sClass .= ' readonly';
		}

		if($aItem['invisible'] === true) {
			$sStyle .= ' display: none;';
		}

		$sRequired = 'required';

		if($aItem['type'] == 'extraPosition') {
			$sRequired = '';
		}

		if(
			empty($aItem['position_key']) ||
			is_numeric($aItem['position_key'])
		) {
			$iPositionId = (int)$aItem['position_key'];
			if(
				$iPositionId <= 0 &&
				!$bDisplayOnly
			) {
				$iPositionId = $this->iLastNewPositionId;
				$this->iLastNewPositionId++;
			}
		} else {
			$iPositionId = $aItem['position_key'];
		}

		// Wenn count nicht angegeben, Einzelposition
		if(!isset($aItem['count_all'])) {
			$aItem['count_all'] = 1;
		}
		if(!isset($aItem['count'])) {
			$aItem['count'] = 1;
		}
		
		// Anzahl aller Subitems (gruppen)
		$iCountAll =  (int)$aItem['count_all'];
							
		// Discount Zeile nur anzeigen, wenn Discount gewählt ist

		if($this->bDiscount) {

			if($aItem['amount_discount'] == 0) {
				$sStyle .= 'display: none;';				
			}

			$sClass .= ' discount';

		}

		if($this->bPositionTable) {
			$sClass .= ' position';
		}
		
		$sPositionHtml = '<tr id="position_row_' . $iPositionId . '"';

		// Aktuell fürs Debuggen
		if($aItem['status'] !== 'new') {
			$sPositionHtml .= ' data-id="'.$aItem['id'].'"';
		}

		if($sClass) {
			$sPositionHtml .= ' class="'.$sClass.'" ';
		}

		// Provision bei Discount umrechnen in Haupttabelle
		if(
			$aItem['amount_provision'] != 0 &&
			$aItem['amount_discount'] != 0
		) {
			$aItem['amount_provision'] = $aItem['amount_provision'] * (1 - ($aItem['amount_discount'] / 100)); 
			$aItem['amount_net'] = $aItem['amount'] - $aItem['amount_provision'];
		}
		
		$sPositionHtml .= ' style="'.$sStyle.'">';
		$bHiddenFields	= true;
		$iCounterCols	= 0;

		foreach($this->aPositionColumns as $sField => $this->aPositionColumn) {

			$sValue = $aItem[$sField];

			// Zahl formatieren
			if($this->aPositionColumn['format'] == 'number') {
				$sValue = Ext_Thebing_Format::Number($sValue, null, $this->iSchoolId);
			}

			$sL10N = $this->aPositionColumn['label'];

			$sName = '';
			if(!$bDisplayOnly) {
				// Wenn unterpunkt => gruppen => name erweitern
				$sName = 'position['.$iPositionId.']['.$sField.']';

			} else {
				$bHiddenFields = false;
			}

			$sMain = 'sub';
			if($sName == '') {
				$sMain = 'main';
			}

			$sPositionHtml .= '<td ';
			switch($sField) {
				case 'sortable':
					$sPositionHtml .= 'id="sortable_'.$iPositionId.'">';
					
					
					$sPositionHtml .= '<div class="jqueryIcon jqueryIconSortable sort_handle" title="'.L10N::t('Position verschieben', Ext_Thebing_Document::$sL10NDescription).'"></div>';
					break;
				case 'count':
					$sPositionHtml .= 'id="count_'.$iPositionId.'" class="click pointer'.$sClass.'" style="text-align: right;">';
					$sPositionHtml .= $sValue.' / '.$iCountAll;
					break;
				case 'initalcost':
					$sOnClick = '';
					$sCheckboxStyle = '';
					if($sReadonly != ""){
						$sOnClick = 'return false;';
						$sCheckboxStyle = 'display:none;';
					}

					$sPositionHtml .= ' style="text-align:center;">';

					if(!$this->bDiscount) {
						$sPositionHtml .= '<input name="'.$sName.'" id="initalcost_'.$iPositionId.'" onclick="'.$sOnClick.'" style="'.$sCheckboxStyle.'" class="click initalcost initalcost_' . $iCount . '_' . $sMain . ' ' . $sReadonlyClass . '" type="checkbox" value="1" ' . $sReadonly;
						if($sValue == 1) {
							$sPositionHtml .= ' checked="checked" ';
						}
						$sPositionHtml .= '/>';
						// Wenn readonly eine disabled checkbox anzeigen da wir die eig. nicht disablen dürfen da
						// das value dann nicht mitgeschickt wird!
						if($sReadonly != "") {
							$sPositionHtml .= '<input type="checkbox" disabled="disabled" readonly="readonly" ';
							if($sValue == 1) {
								$sPositionHtml .= 'checked="checked" ';
							}
							$sPositionHtml .= '/>';
						}
					}

					break;
				case 'onPdf':

				$sInputType = 'checkbox';
				 $sOnClick = '';
					$sCheckboxStyle = '';
					if($sReadonly != "") {
						$sOnClick = 'return false;';
						#$sCheckboxStyle = 'display:none;';
						$sInputType = 'hidden';
					}

					/**
					 * Bei unterschiedlichen Status der GruppenItems darf Checkbox nicht benutzbar sein
					 * Allerdings nur in Haupttabelle und nicht in der Positionstabelle
					 */
					$sReadonlyClassTemp = '';
					$sOnClickTemp = '';
					if(
						$this->bPositionTable === false &&
						(
							$iCountAll > 1 ||
							$iCountAll == 0		// Einzelrechnung
						) &&
						(int)$aItem['count'] != $iCountAll
					){
						$sReadonlyClassTemp = ' readonly ';
						#$sCheckboxStyle = 'display:none;';
						$sOnClickTemp = 'return false;';
						$sInputType = 'hidden';
						// Flag mitschicken, das onPDF nicht mit geupdatet werden darf
					}

					$sPositionHtml .= ' style="text-align:center;">';

					if(!$this->bDiscount) {

						if($sInputType=='hidden'){
							$mCheckBoxValue = $sValue;
						}else{
							$mCheckBoxValue = 1;
						}

						$sPositionHtml .= '<input name="'.$sName.'" id="onpdf_'.$iPositionId.'" onclick="'.$sOnClick.' ' . $sOnClickTemp . '" class="click onPdf onPdf'.$sClass.'" type="'.$sInputType.'" value="'.$mCheckBoxValue.'" ';

						if($sValue == 1 && $sInputType=='checkbox') {
							$sPositionHtml .= 'checked="checked" ';
						}
						$sPositionHtml .= '/>';
						// Wenn readonly eine disabled checkbox anzeigen da wir die eig. nicht disablen dürfen da
						// das value dann nicht mitgeschickt wird!
						if(
							$sReadonly != "" ||
							$sReadonlyClassTemp
						){
							$sPositionHtml .= '<input type="checkbox" disabled="disabled" readonly="readonly" ';
							if($sValue == 1) {
								$sPositionHtml .= 'checked="checked" ';
							}
							$sPositionHtml .= '/>';
						}
					}

					$sName = str_replace('[onPdf]', '', $sName);
					$sPositionHtml .= '<input id="type_'.$iPositionId.'" name="'.$sName.'[type]" type="hidden" value="'.\Util::convertHtmlEntities($aItem['type']).'" />';
					$sPositionHtml .= '<input id="type_id_'.$iPositionId.'" name="'.$sName.'[type_id]" type="hidden" value="'.\Util::convertHtmlEntities($aItem['type_id']).'" />';
					$sPositionHtml .= '<input id="parent_id_'.$iPositionId.'" name="'.$sName.'[parent_id]" type="hidden" value="'.\Util::convertHtmlEntities($aItem['parent_id']).'" />';
					$sPositionHtml .= '<input id="parent_type_'.$iPositionId.'" name="'.$sName.'[parent_type]" type="hidden" value="'.\Util::convertHtmlEntities($aItem['parent_type']).'" />';
					$sPositionHtml .= '<input id="parent_booking_id_'.$iPositionId.'" name="'.$sName.'[parent_booking_id]" type="hidden" value="'.\Util::convertHtmlEntities($aItem['parent_booking_id']).'" />';
					$sPositionHtml .= '<input id="position_'.$iPositionId.'" name="'.$sName.'[position]" type="hidden" value="'.\Util::convertHtmlEntities((int)$aItem['position']).'" />';
					$sPositionHtml .= '<input id="status_'.$iPositionId.'" name="'.$sName.'[status]" type="hidden" value="'.\Util::convertHtmlEntities($aItem['status']).'" />';
					$sPositionHtml .= '<input id="additional_'.$iPositionId.'" name="'.$sName.'[additional]" type="hidden" value="'.\Util::convertHtmlEntities(Util::convertMixed($aItem['additional_info'])).'" />';
					$sPositionHtml .= '<input id="index_from_'.$iPositionId.'" name="'.$sName.'[index_from]" type="hidden" value="'.($aItem['index_from'] ?? $aItem['from']).'" />';
					$sPositionHtml .= '<input id="index_until_'.$iPositionId.'" name="'.$sName.'[index_until]" type="hidden" value="'.($aItem['index_until'] ?? $aItem['until']).'" />';
					$sPositionHtml .= '<input id="type_object_id_'.$iPositionId.'" name="'.$sName.'[type_object_id]" type="hidden" value="'.\Util::convertHtmlEntities($aItem['type_object_id']).'" />';
					$sPositionHtml .= '<input id="type_parent_object_id_'.$iPositionId.'" name="'.$sName.'[type_parent_object_id]" type="hidden" value="'.\Util::convertHtmlEntities($aItem['type_parent_object_id']).'" />';
					
					if(!empty($sReadonlyClassTemp)) {
						$sPositionHtml .= '<input id="check_on_pdf_'.$iPositionId.'" name="'.$sName.'[check_on_pdf]" type="hidden" value="'.$mCheckBoxValue.'" />';
					}

					break;

				case 'label':

					$sPositionHtml .= ' id="label_'.$iPositionId.'">';
					$sPositionHtml .= $sValue;

					break;
				case 'description':

					$sPositionHtml .= '>';

					if(
						$this->iEditable == 2 &&
						$sTemplateType != 'document_loa' 
					) {
						$aTemp = array($sReadonly, $sReadonlyClass);
						$sReadonly = '';
						$sReadonlyClass = '';
					}

					$sPositionHtml .= '<textarea name="'.$sName.'" id="description_'.$iPositionId.'" class="description keyup txt form-control input-sm '.$sRequired.' '.$sReadonlyClass.''.$sClass.'" ' . $sReadonly . ' >'.$sValue.'</textarea>';

					// Zusätzliche Informationen pro Item
					if ($this->bDetailView && !$this->bDiscount) {

						$oDialog = new Ext_Gui2_Dialog();
						$oDialog->bSmallLabels = true;
						$oFormat = new Ext_Thebing_Gui2_Format_Date(false, $this->iSchoolId);
						$sName2 = str_replace('[description]', '', $sName);
						$bEditable = $this->iEditable == 1 && Ext_Thebing_Access::hasRight('thebing_invoice_document_refresh_always');

						$oFakeItem = new Ext_Thebing_Inquiry_Document_Version_Item();
						$oFakeItem->type = $aItem['type'];
						$oFakeItem->type_id = $aItem['type_id'];
						$oFakeItem->type_object_id = $aItem['type_object_id'];

						$sPositionHtml .= '<div class="container_row_details">';

						$sPositionHtml .= $oDialog->createRow(L10N::t('Typ', Ext_Thebing_Document::$sL10NDescription), 'input', [
							'value' => $oFakeItem->getTypeName(),
							'inputdiv_class' => 'input-group-sm'
						])->generateHTML(true);

						$oService = $oFakeItem->getService();
						if ($oService !== null) {
							$sLabelBooking = L10N::t('Buchung', Ext_Thebing_Document::$sL10NDescription);
							$sLabelService = L10N::t('Leistung', Ext_Thebing_Document::$sL10NDescription);
							$sPositionHtml .= $oDialog->createRow(L10N::t('Leistung', Ext_Thebing_Document::$sL10NDescription), 'input', [
								'value' => sprintf('%s (%s %d / %s %d)', $oService->getName(), $sLabelBooking, $oFakeItem->type_id, $sLabelService, $oFakeItem->type_object_id),
								'inputdiv_class' => 'input-group-sm'
							])->generateHTML(true);
						}

						$sPositionHtml .= $oDialog->createRow(L10N::t('Leistungszeitraum: Von', Ext_Thebing_Document::$sL10NDescription), 'calendar', [
							'name' => $sName2.'[index_from]',
							'value' => $oFormat->formatByValue($aItem['index_from'] ?? $aItem['from']),
							'calendar_row_class' => 'input-group-sm'
						])->generateHTML(!$bEditable);

						$sPositionHtml .= $oDialog->createRow(L10N::t('Leistungszeitraum: Bis', Ext_Thebing_Document::$sL10NDescription), 'calendar', [
							'name' => $sName2.'[index_until]',
							'value' => $oFormat->formatByValue($aItem['index_until'] ?? $aItem['until']),
							'calendar_row_class' => 'input-group-sm'
						])->generateHTML(!$bEditable);

						$sPositionHtml .= $oDialog->createRow(L10N::t('Zusätzliche Daten', Ext_Thebing_Document::$sL10NDescription), 'textarea', [
							'value' => Util::convertMixed($aItem['additional_info']),
							'inputdiv_class' => 'input-group-sm'
						])->generateHTML(true);

						\System::wd()->executeHook('ts_document_position_detail_form', $sPositionHtml, $aItem, $this, $oDialog, $sName2);
						
						$sPositionHtml .= '</div>';

					}

					if(
						$this->iEditable == 2  &&
						$sTemplateType != 'document_loa' 
					) {
						$sReadonly = $aTemp[0];
						$sReadonlyClass = $aTemp[1];
					}

					break;
				case 'amount':

					// Bei Gruppenrechnungen darf der Preis nicht über die Haupttabelle veränderbar sein, wenn die Anzahl
					// aktiver Gruppenmitglieder pro Position von der gesamtanzahl abweicht s.o.
					if(
						!empty($sReadonlyClassTemp )
					){
						$sReadonlyClass = $sReadonlyClassTemp;
					}
					
					if(
						$this->bDiscount ||
						$this->aPositionColumn['readonly']
					) {
						$sPositionHtml .= ' id="amount_'.$iPositionId.'" class="amount">';
						$sPositionHtml .= $sValue;
					} else {
						$sPositionHtml .= '>';
						$sPositionHtml .= '<input id="amount_'.$iPositionId.'" name="'.$sName.'" type="text" style="width:100%" class="keyup txt form-control input-sm amount'.$sReadonlyClass.''.$sClass.' info" value="'.$sValue.'"  ' . $sReadonly .' />';
					}

					break;
				case 'amount_discount':

					if($this->bDiscount) {
						$sPositionHtml .= '>';
					} else {
						if($this->aPositionColumn['readonly']) {
							$sPositionHtml .= ' class="amount" id="amount_discount_'.$iPositionId.'">';
							$sPositionHtml .= $sValue;
						} else {
							$sPositionHtml .= '>';
							$sPositionHtml .= '<input id="amount_discount_'.$iPositionId.'" name="'.$sName.'" type="text" style="width:100%" class="keyup txt form-control input-sm amount '.$sReadonlyClass.''.$sClass.'" value="'.$sValue.'" ' . $sReadonly .' />';
						}
					}
					
					break;

				case 'amount_discount_amount':

					$iAmount = $aItem['amount'] * ($aItem['amount_discount'] / 100);
					$sAmount = Ext_Thebing_Format::Number($iAmount, null, $this->iSchoolId);

					$sPositionHtml .= ' class="amount" id="amount_discount_amount_'.$iPositionId.'">';

					if(!$this->bDiscount) {
						$sPositionHtml .= $sAmount;
					}

					break;
				case 'amount_after_discount':

					if($this->bDiscount) {
						$sPositionHtml .= '>';
					} else {
						$sPositionHtml .= ' class="amount" id="amount_after_discount_'.$iPositionId.'">';

						$iAmountAfterDiscount = $aItem['amount'] - ($aItem['amount'] * ($aItem['amount_discount'] / 100));
						$sAmount = Ext_Thebing_Format::Number($iAmountAfterDiscount, null, $this->iSchoolId);

						$sPositionHtml .= $sAmount;
					}

					break;
				case 'amount_provision':

					$sTmpReadonly = $sReadonly;
					$sTmpReadonlyClass = $sReadonlyClass;

					// Wenn aktiviert, kann Provision immer bearbeitet werden
					if($this->bCommissionEditable) {
						$sTmpReadonly = $sTmpReadonlyClass = '';
					}

					if($this->bDiscount) {
						$sPositionHtml .= '>';
					} else {
						$sPositionHtml .= ' class="amount">';

						$sReloadIcon = '';
						$iWidthSub = 0;
						
						if(
							$aItem['type'] !== 'storno' && (
								$this->bPositionsEditable === true ||
								$this->bCommissionEditable === true
							)
						) {
							$sReloadIcon = '<span class="input-group-btn"><button id="amount_provision_reload_'.$iPositionId.'" type="button" class="btn btn-default btn-flat click" title="'.L10N::t('Provision neu berechnen', Ext_Thebing_Document::$sL10NDescription).'"><i class="fa fa-refresh"></i></button></span>';
							$iWidthSub = 19;
						}

						$sPositionHtml .= '<div class="input-group input-group-sm">';

						$sPositionHtml .= '<input id="amount_provision_'.$iPositionId.'" name="'.$sName.'" type="text" class="keyup txt form-control input-sm amount '.$sTmpReadonlyClass.''.$sClass.'" value="'.$sValue.'" ' . $sTmpReadonly .' />';

						$sPositionHtml .= $sReloadIcon;

						$sPositionHtml .= '</div>';

					}

					break;
				case 'amount_total':
				case 'amount_total_net':
				case 'amount_total_gross':

					$sPositionHtml .= ' class="amount_td amount" id="'.$sField.'_'.$iPositionId.'">';

					if(!$this->bDiscount) {

						$iAmount = $aItem['amount'];

						if(isset($this->aPositionColumns['amount_discount'])) {
							$iAmount = $iAmount - ($iAmount * ($aItem['amount_discount'] / 100));
						}

						if(isset($this->aPositionColumns['amount_provision'])) {
							$iAmount = $iAmount - $aItem['amount_provision'];
						}

						$sAmount = Ext_Thebing_Format::Number($iAmount, null, $this->iSchoolId);

						$sPositionHtml .= $sAmount;

					}

					break;

				case 'tax_category':

					if($sReadonly != ""){
						$sReadonly = ' disabled="disabled" ';
					}

					$sTmpReadonly = $sReadonly;
					$sTmpReadonlyClass = $sReadonlyClass;

					if (!$this->taxCategoryEditable) {
						// No rights to change tax category
						$sTmpReadonly = ' disabled="disabled" ';
					} else if ($this->bCommissionEditable) {
						// Wenn aktiviert, kann Provision immer bearbeitet werden
						$sTmpReadonly = $sTmpReadonlyClass = '';
					}
					
					if($sValue == 0) {
						$sValue = $this->iDefaultTaxCategory;
					}
					
					$sPositionHtml .= '>';
					$sPositionHtml .= '<input name="'.$sName.'" type="hidden" value="'.$sValue.'" />';
					$sPositionHtml .= '<select id="tax_category_'.$iPositionId.'" name="'.$sName.'" class="change txt form-control input-sm '.$sTmpReadonlyClass.''.$sClass.'" ' . $sTmpReadonly .' >';
					foreach((array)$this->aTaxCategories as $iKey => $sTax) {
						$sSelected = '';
						if($sValue == $iKey) {
							$sSelected = 'selected="selected"';
						}
						$sPositionHtml .= '<option value="' . $iKey . '" ' . $sSelected . '>' . $sTax . '</option>';
					}
					$sPositionHtml .= '</select>';
					break;
				case 'actions':
					$iDeleteId = 'delete_'.$iPositionId;
					$iEditId = 'edit_'.$iPositionId;
					$sClass = 'click pointer';
					$sStyle = '';
					
					if(!empty($sReadonly)){
						$sStyle = 'opacity: 0.5;';
						$iDeleteId = $iEditId = $sClass = '';
					}
					
					$sPositionHtml .= '
						>
							<i class="fa fa-minus-circle '.$sClass.'" style="'.$sStyle.'" id="'.$iDeleteId.'" title="'.L10N::t('Eintrag löschen', Ext_Thebing_Document::$sL10NDescription).'"></i> 
							<i class="fa fa-edit '.$sClass.'" style="'.$sStyle.'" id="'.$iEditId.'" title="'.L10N::t('Details bearbeiten', Ext_Thebing_Document::$sL10NDescription).'"></i>
						';

					break;
				default:
					$sPositionHtml .= '>';
					$sPositionHtml .= $sValue;
					break;
			}

			if(
				$this->aPositionColumn['sum'] === true &&
				$aItem['calculate'] == 1					// Damit nicht zu berechnende Positionen nicht in die Summenzeile eingerechnet werden. (aufgefallen bei Additional Docs)
			) {
				$aTotalAmounts[$sField] += $aItem[$sField];
			}

			$sPositionHtml .= '</td>';

			$iCounterCols++;
		}

		$sPositionHtml .= '</tr>';

		//Checkbox "all"
		if(
			//Checkbox "all" nur im kleinen Positionseditierdialog anzeigen
			$this->bPositionTable === true &&
			//discount Zeile ignorieren
			!$this->bDiscount &&
			//nur nach der letzten
			$this->bLast
		){
			$iColspan = $iCounterCols - 1;

			$sChecked = '';
			if($aItem['count']==$aItem['count_all'])
			{
				$sChecked = 'checked="checked"';
			}

			$sPositionHtml .= '<tr>';
			$sPositionHtml .= '<td style="text-align:center;">';
			$sPositionHtml .= '<input type="checkbox" id="multiple_checkbox_'.$iPositionId.'" class="change" '.$sChecked.'>';
			$sPositionHtml .= '</td>';
			$sPositionHtml .= '<td colspan="'.$iColspan.'">';
			$sPositionHtml .= '</td>';
			$sPositionHtml .= '</tr>';
		}

		// Auf default zurücksetzen
		$this->bDiscount = false;

		return $sPositionHtml;

	}

}
