<?php


class Ext_TC_Pdf_Table_Gui2_Data extends Ext_TC_Gui2_Data
{
    public static function getOrderby()
	{
		return array('name' => 'ASC');
	}
	
	public static function getDialog(Ext_Gui2 $oGui)
	{
		$oDialog = $oGui->createDialog($oGui->t('Positionstabelle editieren'), $oGui->t('Positionstabelle erstellen'));
		$oDialog->height = '650';
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Standardtabelle laden'), 'select', array(
				'db_column'			=> 'default_table',
		)));
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
				'db_column'			=> 'name',
				'required'			=> 1,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Platzhalter'), 'input', array(
				'db_column'			=> 'placeholder',
				'required'			=> 1,
				'default_value'		=> '',
		)));
		
		################## Start Standardeinstellungen ##################
		$oH3						= $oDialog->create('h4');
		$oH3->setElement($oGui->t('Standard-Einstellungen'));
		$oDialog->setElement($oH3);
		
		$sMessage = $oGui->t('Wenn keine Einstellungen für optionale Elemente vorgenommen werden, gelten die Einstellungen aus dem entsprechenden PDF Layout.');
		
		$oNotification = $oDialog->createNotification($oGui->t('Hinweis'), $sMessage, 'info', [
			'dismissible' => false
		]);
		
		$oDialog->setElement($oNotification);

		//Attribute 1: Schriftart
		$oFont	= new Ext_TC_System_Font();
		$aFonts = $oFont->getArrayList(true);
		$aFonts = Ext_TC_Util::addEmptyItem($aFonts);

		$oDialog->setElement($oDialog->createRow($oGui->t('Schriftart'), 'select', array(
				'db_column'			=> 'font_family',
				'db_alias'			=> 'attribute',
				'select_options'	=> $aFonts,
		)));
		
		//Attribute 2: Schriftgröße
		$oDialog->setElement($oDialog->createRow($oGui->t('Schriftgröße'), 'input', array(
				'db_column'			=> 'font_size',
				'db_alias'			=> 'attribute',
		)));
		
		//Attribute 3: Schriftfarbe
		$oDialog->setElement($oDialog->createRow($oGui->t('Schriftfarbe'), 'color', array(
				'db_column'			=> 'font_color',
				'db_alias'			=> 'attribute',
				'required'			=> 1,
		)));
		
		//Attribute 4: Schriftfarbe
		$oDialog->setElement($oDialog->createRow($oGui->t('Linienfarbe'), 'color', array(
				'db_column'			=> 'color',
				'db_alias'			=> 'attribute',
				'required'			=> 1,
		)));
		
		//Attribute 5: Nachkomastellen
		$oDialog->setElement($oDialog->createRow($oGui->t('Nachkommastellen'), 'input', array(
				'db_column'			=> 'decimal_places',
				'db_alias'			=> 'attribute',
				'required'			=> 1,
				'default_value'		=> '2',
		)));
		
		//Margins Multi-Row
		$oLabelRight	= self::_createMarginLabel($oGui->t('Rechts'));
		$oLabelLeft		= self::_createMarginLabel($oGui->t('Links'));
		$oLabelTop		= self::_createMarginLabel($oGui->t('Oben'));
		$oLabelBottom	= self::_createMarginLabel($oGui->t('Unten'));
		
		$oDialog->setElement($oDialog->createMultiRow($oGui->t('Innenabstand pro Zelle (mm)'), 
			array(
				'db_alias' => 'attribute',
				'items' => array(
					array(
						'db_column'		=> 'margin_right',
						'input'			=> 'input',
						'style'			=> 'width: 50px; margin-right:20px;',
						'label'			=> $oLabelRight,
						'required'		=> 1,
					),
					array(
						'db_column'		=> 'margin_left',
						'input'			=> 'input',
						'style'			=> 'width: 50px; margin-right:20px;',
						'label'			=> $oLabelLeft,
						'required'		=> 1,
					),
					array(
						'db_column'		=> 'margin_top',
						'input'			=> 'input',
						'style'			=> 'width: 50px; margin-right:20px;',
						'label'			=> $oLabelTop,
						'required'		=> 1,
					),
					array(
						'db_column'		=> 'margin_bottom',
						'input'			=> 'input',
						'style'			=> 'width: 50px; margin-right:20px;',
						'label'			=> $oLabelBottom,
						'required'		=> 1,
					),
				)
			)
		));
		
		//Attribute 10: Totale Breite
		$oDialog->setElement($oDialog->createRow($oGui->t('Totale Breite der Tabelle (mm)'), 'input', array(
				'db_column'			=> 'table_width',
				'db_alias'			=> 'attribute',
				'required'			=> 1,
				'default_value'		=> 210,
		)));
		
		################## Start Einstellungen Seitenumbruch ##################
		$oH3						= $oDialog->create('h4');
		$oH3->setElement($oGui->t('Einstellungen für den Seitenumbruch'));
		$oDialog->setElement($oH3);
		
		//Attribute 11: Zwischensumme
		$oDialog->setElement($oDialog->createRow($oGui->t('Zwischensumme'), 'checkbox', array(
				'db_column'			=> 'subtotal',
				'db_alias'			=> 'attribute',
		)));
		
		//Attribute 12: Übertrag
		$oDialog->setElement($oDialog->createRow($oGui->t('Übertrag'), 'checkbox', array(
				'db_column'			=> 'carry_over',
				'db_alias'			=> 'attribute',
		)));
		
		################## Vorschau der Tabelle ##################
		$oH3						= $oDialog->create('h4');
		$oH3->setElement($oGui->t('Vorschau der Tabelle'));
		$oDialog->setElement($oH3);
		
		$sMessage = $oGui->t('Bitte klicken Sie doppelt auf eine Zelle, um Einstellungen für diese Zelle zu ändern.');
		$oNotification = $oDialog->createNotification($oGui->t('Hinweis'), $sMessage, 'info');
		
		$oDialog->setElement($oNotification);
		
		//Sprache Dropdown
		$aLangs = (array)Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getLanguages', array(true));

		$oDivLanguage = $oDialog->create('div');
		$oDivLanguage->class = 'divToolbarPdfTableLanguage';
		
		$oSelectLanguage = $oDialog->create('select');
		$oSelectLanguage->name = 'table_language';
		$oSelectLanguage->class = 'txt';
		
		foreach($aLangs as $sLangCode => $sLangTitle)
		{
			$oOption = $oDialog->create('option');
			$oOption->value = $sLangCode;
			$oOption->setElement($sLangTitle);
			
			$oSelectLanguage->setElement($oOption);
		}
		
		$oDivLanguage->setElement($oSelectLanguage);
		
		$oDivAddColButton = $oDialog->create('div');
		$oDivAddColButton->class = 'guiBarElement guiBarLink';
		$oDivAddColIcon = $oDialog->create('div');
		$oDivAddColIcon->class = 'divToolbarIcon w16';
		$oDivAddColIcon->id = 'add_col_'.$oGui->hash;
		$oImgAddColIcon = $oDialog->create('img');
		$oImgAddColIcon->src = Ext_TC_Util::getIcon('add');
		$sTitleAddColIcon = $oGui->t('Neue Spalte hinzufügen');
		$oImgAddColIcon->alt = $sTitleAddColIcon;
		$oImgAddColIcon->title = $sTitleAddColIcon;
		$oDivAddColIcon->setElement($oImgAddColIcon);
		
		$oDivAddColLabel = $oDialog->create('div');
		$oDivAddColLabel->class = 'divToolbarLabel';
		$oDivAddColLabel->setElement($sTitleAddColIcon);
		
		$oDivAddColButton->setElement($oDivAddColIcon);
		$oDivAddColButton->setElement($oDivAddColLabel);
		
		$oDivCleaner = $oDialog->create('div');
		$oDivCleaner->class = 'divCleaner';
		
		$oDivActions = $oDialog->create('div');
		$oDivActions->class = 'divToolbar divToolbarPdfTable';

		$oDivActions->setElement($oDivLanguage);
		$oDivActions->setElement($oDivAddColButton);
		$oDivActions->setElement($oDivCleaner);
		
		$oDialog->setElement($oDivActions);
		
		$oDivPdfTable = $oDialog->create('div');
		$oDivPdfTable->id = 'pdf_table_' . $oGui->hash;
		$oDialog->setElement($oDivPdfTable);
		
		return $oDialog;
	}
	
	protected static function _createMarginLabel($sLabel)
	{
		$oLabel = new Ext_Gui2_Html_Label();
		$oLabel->style = 'margin-right:10px;';
		$oLabel->setElement($sLabel);
		
		return $oLabel;
	}

	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false)
	{
		$aData = parent::getEditDialogData($aSelectedIds, $aSaveData, $sAdditional);
		$oWDBasic = $this->_getWDBasicObject($aSelectedIds);

		foreach($aData as &$aElement) {

			// Default Platzhalter mit nächster ID (Unique) generieren
			if(
				$aElement['db_column'] === 'placeholder' &&
				$oWDBasic->id === 0
			) {
				$aElement['default_value'] = Ext_TC_Pdf_Table_WDBasic::getDefaultPlaceholderName();
				break;
			}

		}

		return $aData;
	}

	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true)
	{
		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		//@todo: DB-Abfrage, wenn nichts definiert ist, muss der Standard geladen werden
		$oTablePdf = $this->_initDefaultPdfTable();
		
		//In die Gui abspeichern
		$this->_oGui->setPdfTable($oTablePdf);
		
		$this->_oGui->save();
		
		//Html aus der Pdf-Tabelle generieren
		$oTable						= $this->_getPdfTableHtml($oTablePdf);

		$aData['pdf_table_html']	= $oTable->generateHtml();

		return $aData;
	}
	
	/**
	 *
	 * Standard Pdf-Tabelle falls nichts definiert ist
	 * 
	 * @return Ext_TC_Pdf_Table 
	 */
	protected function _initDefaultPdfTable()
	{
		$oTablePdf	= new Ext_TC_Pdf_Table();
		
		//Spalte Beschreibung
		$oCol = $oTablePdf->createCol();
		$oCol->setData('title', $this->t('Beschreibung'));
		$oCol->setData('width', 150);
		$oCol->setData('dynamic_width', true);
		$oCol->setData('position', 0);
		$oCol->setData('can_delete', false);
		
		//Spalte Betrag
		$oColAmount = $oTablePdf->createCol();
		$oColAmount->setData('title', $this->t('Betrag'));
		$oColAmount->setData('width', 40);
		$oColAmount->setData('position', 1);
		
		//Zeile Positionen
		$oRow = $oTablePdf->createRow();
		$oRow->setData('title', $this->t('Positionen'));
		
		//Zeilen & Spalten ins Table Objekt setzen
		$oTablePdf->addCol($oCol, 'description');
		$oTablePdf->addCol($oColAmount, 'amount');
		$oTablePdf->addRow($oRow, 'positions');
		
		//Zelle generieren
		$oCell = $oTablePdf->createCell();
		
		//Zele hinzufügen
		$oTablePdf->addCell('description', 'positions', $oCell);
		
		//Zelle generieren
		$oCell = $oTablePdf->createCell();
		
		//Zele hinzufügen
		$oTablePdf->addCell('amount', 'positions', $oCell);
		
		return $oTablePdf;
	}


	/**
	 *
	 * Pdf Tabellen Objekt ins Gui2_Html_Table konvertieren
	 * 
	 * @return Ext_Gui2_Html_Table
	 */
	protected function _getPdfTableHtml(Ext_TC_Pdf_Table $oTablePdf)
	{	
		$oTableHtml = new Ext_Gui2_Html_Table();
		$oTableHtml->class = 'pdfTable';
		$oTableHtml->cellpadding = '0';
		$oTableHtml->cellspacing = '0';

		$this->_createTableHead($oTablePdf, $oTableHtml);
		
		$this->_createTableRows($oTablePdf, $oTableHtml);

		return $oTableHtml;
	}
	
	/**
	 *
	 * Die ersten 3 Zeilen erstellen(Breite,Titel & Aktionen)
	 * 
	 * @param Ext_TC_Pdf_Table $oTablePdf
	 * @param Ext_Gui2_Html_Table $oTableHtml 
	 */
	protected function _createTableHead(Ext_TC_Pdf_Table $oTablePdf, Ext_Gui2_Html_Table $oTableHtml)
	{
		$aCols = (array)$oTablePdf->getCols();
		
		//Erste Zeile, Breitenangaben
		$oTr = new Ext_Gui2_Html_Table_tr();
		
		//Label erste Zeile
		$oTd = new Ext_Gui2_Html_Table_Tr_Td();
		$oTd->setElement($this->t('Breite'));
		$oTd->class = 'firstCol';
		$oTr->setElement($oTd);
		
		foreach($aCols as $sColKey => $oCol)
		{
			$oTd = new Ext_Gui2_Html_Table_Tr_Td();
			$oTd->class = 'widthCols';
			$oTd->id = $this->_oGui->hash.'_widthcol_'.$sColKey;
			
			$oInputWidth = new Ext_Gui2_Html_Input();
			
			if($oCol->hasData('dynamic_width') && $oCol->getData('dynamic_width') === true)
			{
				// Auf readonly setzen ohne disabled
				$oInputWidth->bReadOnly = true;
				$oInputWidth->bDisabledByReadonly = false;
			}
			else
			{
				$oInputWidth->value = $oCol->getData('width');
			}

			$oInputWidth->class .= 'txt colWidth';
			
			$oInputWidth->id = $this->_oGui->hash.'_widthcol_'.$sColKey.'_input';

			$oTd->setElement($oInputWidth);

			$oTr->setElement($oTd);
		}

		$oTableHtml->setElement($oTr);
		
		//Zweite Zeile, Aktionen
		$oTr = new Ext_Gui2_Html_Table_tr();
		
		//Label zweite Zeile
		$oTd = new Ext_Gui2_Html_Table_Tr_Td();
		$oTd->class = 'firstCol';
		$oTd->setElement($this->t('Aktionen'));
		$oTr->setElement($oTd);
		
		foreach($aCols as $sColKey => $oCol)
		{
			$oTd = new Ext_Gui2_Html_Table_Tr_Td();
			$oTd->id = $this->_oGui->hash.'_actions_'.$sColKey;
			$oTd->class = 'actionsRow';
			
			//Links verschieben Image
			$oImgLeft = new Ext_Gui2_Html_Image();
			$oImgLeft->src = Ext_TC_Util::getIcon('back');
			$oImgLeft->title = $this->t('Nach links verschieben');
			$oImgLeft->alt = $this->t('Nach links verschieben');
			$oImgLeft->class = 'left positionBack';
			$oImgLeft->id = $this->_oGui->hash.'_actions_'.$sColKey.'_positionBack';
			$oTd->setElement($oImgLeft);
			
			//Rechts verschieben Image
			$oImgRight = new Ext_Gui2_Html_Image();
			$oImgRight->src = Ext_TC_Util::getIcon('next');
			$oImgRight->title = $this->t('Nach rechts verschieben');
			$oImgRight->alt = $this->t('Nach rechts verschieben');
			$oImgRight->class = 'right positionNext';
			$oImgRight->id = $this->_oGui->hash.'_actions_'.$sColKey.'_positionNext';
			$oTd->setElement($oImgRight);
			
			//Manche Spalten dürfen nicht gelöscht werden(z.B. Beschreibung)
			$bCanDelete = true;
			
			if($oCol->hasData('can_delete') && $oCol->getData('can_delete') === false)
			{
				$bCanDelete = false;
			}
			
			//Löschen Image
			if($bCanDelete)
			{
				$oImgRight = new Ext_Gui2_Html_Image();
				$oImgRight->src = Ext_TC_Util::getIcon('delete');
				$oImgRight->title = $this->t('Löschen');
				$oImgRight->alt = $this->t('Löschen');
				$oImgRight->class = 'right';
				$oImgRight->id = $this->_oGui->hash.'_actions_'.$sColKey.'_delete';
				$oTd->setElement($oImgRight);	
			}
			
			//Editieren Image
			$oImgRight = new Ext_Gui2_Html_Image();
			$oImgRight->src = Ext_TC_Util::getIcon('edit');
			$oImgRight->title = $this->t('Editieren');
			$oImgRight->alt = $this->t('Editieren');
			$oImgRight->class = 'right';
			$oTd->setElement($oImgRight);
			
			//Hidden Feld Position
			$oInputHidden = new Ext_Gui2_Html_Input();
			$oInputHidden->type = 'hidden';
			$oInputHidden->id = $this->_oGui->hash.'_actions_'.$sColKey.'_position';
			$oInputHidden->value = $oCol->getData('position');
			$oTd->setElement($oInputHidden);
			
			//Hidden Feld Spaltenkey
			$oInputHidden = new Ext_Gui2_Html_Input();
			$oInputHidden->type = 'hidden';
			$oInputHidden->id = $this->_oGui->hash.'_actions_'.$sColKey.'_key';
			$oInputHidden->value = $sColKey;
			$oTd->setElement($oInputHidden);
			
			$oTr->setElement($oTd);
		}
		
		$oTableHtml->setElement($oTr);
		
		//Dritte Zeile, Aktionen
		$oTr = new Ext_Gui2_Html_Table_tr();
		
		//Label dritte Zeile
		$oTh = new Ext_Gui2_Html_Table_Tr_Th();
		$oTh->class = 'firstCol';
		$oTh->setElement('&nbsp;');
		$oTr->setElement($oTh);
		
		//Titel-Spalten
		foreach($aCols as $sColKey => $oCol)
		{
			$oTh = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->id = $this->_oGui->hash.'_titles_'.$sColKey;
			
			$oTh->setElement($oCol->getData('title'));
			
			$oTr->setElement($oTh);
		}
		
		$oTableHtml->setElement($oTr);
	}
	
	/**
	 *
	 * Alle definierten Rows in der PdfTable Klasse in die Html-Tabelle packen
	 * 
	 * @param Ext_TC_Pdf_Table $oTablePdf
	 * @param Ext_Gui2_Html_Table $oTableHtml 
	 */
	protected function _createTableRows(Ext_TC_Pdf_Table $oTablePdf, Ext_Gui2_Html_Table $oTableHtml)
	{
		$aRows = (array)$oTablePdf->getRows();
		
		foreach($aRows as $sRowkey => $oRow)
		{
			$oTr = new Ext_Gui2_Html_Table_tr();
			
			//Erste Spalte der Zeilen
			$oTd = new Ext_Gui2_Html_Table_Tr_Td();
			$oTd->setElement($oRow->getData('title'));
			$oTd->class = 'firstCol';
			$oTr->setElement($oTd);
			
			$aCells = $oRow->getCells();
			
			//Alle Spalten in die Zeilen einfügen
			foreach($aCells as $oCell)
			{
				$oTd = new Ext_Gui2_Html_Table_Tr_Td();
				$oTd->setElement('&nbsp;');
				
				$oTr->setElement($oTd);
			}
			
			$oTableHtml->setElement($oTr);
		}
	}
	
	/**
	 *
	 * @param array $_VARS
	 */
	public function switchAjaxRequest($_VARS)
	{
		if($_VARS['task'] == 'deleteCol')
		{
			//Spalte löschen
			
			/* @var $oPdfTable Ext_TC_Pdf_Table */
			$oTablePdf = $this->_oGui->getPdfTable();
			
			if(isset($_VARS['col']))
			{
				$sColKey = $_VARS['col'];

				$oTablePdf->removeCol($sColKey);
			}
			
			$aTransfer = $this->_switchAjaxRequest($_VARS);
			
			//Wird benötigt, nicht rausnehmen!
			$sIconKey = $_VARS['action'];
			
			$oDialog = $this->_getDialog($sIconKey);
			$aSelectedIds = (array)$_VARS['id'];
			$aTransfer['data']['id'] = $this->_getDialogId($oDialog, $aSelectedIds);


			//Html Nach dem löschen erneut generieren
			$oTableHtml = $this->_getPdfTableHtml($oTablePdf);
			
			$aTransfer['data']['pdf_table_html'] = $oTableHtml->generateHTML();
			
			$aTransfer['action'] = 'saveDialogCallback';
			
			echo json_encode($aTransfer);
		}
		else
		{
			parent::switchAjaxRequest($_VARS);
		}
	}
}