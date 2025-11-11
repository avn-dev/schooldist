<?php

class Ext_TS_Inquiry_Document_Gui2_Data extends Ext_Thebing_Document_Gui2_List {

	/**
	 * Liefert das Nummernformat
	 * @global array $_VARS
	 * @return array
	 */
	public function getNumberFormat() {
		global $_VARS;

		// In der All-schools Ansicht muss das Nummernformat der Schule der Buchung genommen werden #5902
		if(Ext_Thebing_System::isAllSchools()) {

			$iWDBasic = reset($_VARS['parent_gui_id']);
		
			$oWDBasic = Ext_TS_Inquiry::getInstance($iWDBasic);
			$oSchool = $oWDBasic->getSchool();
			$iNumberFormat = $oSchool->number_format;

			$aData = Ext_Thebing_Util::getNumberFormatData($iNumberFormat);			
		} else {
			$aData = parent::getNumberFormat();
		}
		
		return $aData;

	}

	/**
	 * @see \Ext_Thebing_Document::getDialog()
	 *
	 * @param Ext_Thebing_Inquiry_Document $oWDBasic
	 * @throws Exception
	 */
	public function setForeignKey(&$oWDBasic) {

		// Offensichtlich besteht trotz Index-GUI/Elasticsearch eine Verbindung zur DB
		if ($this->_oGui->foreign_key === 'inquiry_id') {
			$oInquiry = $this->getSelectedObject();
			$oWDBasic->entity = get_class($oInquiry);
			$oWDBasic->entity_id = $oInquiry->id;
			return;
		}

		parent::setForeignKey($oWDBasic);

	}


}
