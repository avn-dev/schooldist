<?php

class Ext_TC_Upload_Gui2_Data extends Ext_TC_Gui2_Data
{
	public static $sL10NDescription = 'Thebing Core » Templates » Uploads';

	public static function getCategories()
	{
		$aCategories = array();
		$aCategories['pdf_background'] = L10N::t('PDF-Hintergründe', self::$sL10NDescription);
		//$aCategories['pdf_attachments']	= L10N::t('PDF-Anhänge', self::$sL10NDescription);
		$aCategories['signatures'] = L10N::t('Signaturen', self::$sL10NDescription);
		$aCategories['communication'] = L10N::t('Anhänge für Kommunikation', self::$sL10NDescription);
		$aCategories['images'] = L10N::t('Bilder', self::$sL10NDescription);
		return $aCategories;
	}

	/*
	 * Liefert die Kategorien zu denen dateien hochgeladen werden können
	 */

	public static function getNewDialog(Ext_Thebing_Gui2 $oGui)
	{
		$aCategories = Factory::executeStatic(Ext_TC_Upload_Gui2_Data::class, 'getCategories');
		$aCategories = Ext_Gui2_Util::addLabelItem($aCategories, $oGui->t('Kategorie'), '');

		// Dialog NEU
		$oDialog = $oGui->createDialog(L10N::t('Upload "{description}" editieren', $oGui->gui_description), L10N::t('Neuer Upload', $oGui->gui_description));

		$oDialog->setElement($oDialog->createRow(L10N::t('Bezeichnung', $oGui->gui_description), 'input', array('db_alias' => '', 'db_column' => 'description', 'required' => true)));
		$oDialog->setElement($oDialog->createRow(L10N::t('Kategorie', $oGui->gui_description), 'select', array('db_alias' => '', 'db_column' => 'category', 'select_options' => $aCategories, 'required' => true)));

		self::attachDialogDefaultFields($oGui, $oDialog);

		return $oDialog;
	}

	public static function getEditDialog(Ext_Thebing_Gui2 $oGui)
	{
		// Dialog editieren
		$oDialog = $oGui->createDialog(L10N::t('Upload "{description}" editieren', $oGui->gui_description), L10N::t('Neuer Upload', $oGui->gui_description));

		$oDialog->setElement($oDialog->createRow(L10N::t('Bezeichnung', $oGui->gui_description), 'input', array('db_alias' => '', 'db_column' => 'description', 'required' => true)));

		self::attachDialogDefaultFields($oGui, $oDialog);

		return $oDialog;
	}

	private static function attachDialogDefaultFields(Ext_Gui2 $gui2, Ext_Gui2_Dialog $dialog)
	{
		$dialog->height = 650;

		$languages = Factory::executeStatic('Ext_TC_Object', 'getLanguages', [true]);
		$subObjects = Factory::executeStatic('Ext_TC_Object', 'getSubObjects', [true]);
		$subObjectLabel = Factory::executeStatic('Ext_TC_Object', 'getSubObjectLabel');

		$filePath = Ext_TC_Upload_Gui2_Data::getUploadPath(false);

		$dialog->setElement($dialog->createRow($subObjectLabel, 'select', array('db_alias' => '', 'db_column' => 'objects', 'select_options' => $subObjects, 'multiple' => 5, 'jquery_multiple' => 1, 'searchable' => 1)));
		$dialog->setElement($dialog->createRow(L10N::t('Sprachen', $gui2->gui_description), 'select', array('db_alias' => '', 'db_column' => 'languages', 'select_options' => $languages, 'multiple' => 5, 'jquery_multiple' => 1, 'searchable' => 1)));

		$upload = new Ext_Gui2_Dialog_Upload($gui2, $gui2->t('Upload'), $dialog, 'filename', '', $filePath);
		$upload->bAddColumnData2Filename = 0;
		$dialog->setElement($upload);
	}

	/**
	 * Gibt das Uploadverzeichnis zurück
	 * @return string
	 */
	public static function getUploadPath($bDocumentRoot = false)
	{
		$sDirectory = Factory::executeStatic('Ext_TC_Util', 'getSecureDirectory', array($bDocumentRoot));
		$sDirectory .= 'uploads/';

		return $sDirectory;
	}

	public static function getOrderBy()
	{
		return ['tc_u.description' => 'ASC'];
	}

	protected function _getErrorMessage($mError, $sField = '', $sLabel = '', $sAction = null, $sAdditional = null)
	{
		$sMessage = '';

		if (empty($sLabel)) {
			$sLabel = $sField;
		}

		if (is_array($mError)) {
			switch ($mError['message']) {
				case 'NO_PDF_DATA':
					$sMessage = 'Nur PDF-Dateien sind erlaubt.';
					$sMessage = L10N::t($sMessage, $this->_oGui->gui_description);
					break;
				case 'NO_IMG_DATA':
					$sMessage = 'Nur Bilddateien mit den Endungen ' . implode(', ', Ext_TC_Upload::getFileExtensions('image')) . ' sind erlaubt.';
					$sMessage = L10N::t($sMessage, $this->_oGui->gui_description);
					break;
				case 'NO_FILE_DATA':
					$sMessage = 'Nur Dateien mit den Endungen ' . implode(', ', Ext_TC_Upload::getFileExtensions('file')) . ' sind erlaubt.';
					$sMessage = L10N::t($sMessage, $this->_oGui->gui_description);
					break;
			}

			$sMessage = sprintf($sMessage, $mError['item']);

		} else {
			$sMessage = parent::_getErrorMessage($mError, $sField, $sLabel);
		}

		return $sMessage;

	}
}