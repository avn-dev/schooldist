<?php


class Ext_TS_Document_Release_Gui2_Format_DocNumber extends Ext_Gui2_View_Format_ToolTip
{
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$oDocument			= Ext_Thebing_Inquiry_Document::getInstance($aResultData['id']);

		if (
			$oColumn->db_column == 'cn_number' ||
			$oColumn->db_column == 'cn_subagency_number'
		) {
			$creditNote = $oColumn->db_column == 'cn_number' ? $oDocument->getCreditNote() : $oDocument->getCreditNoteSubAgency();
			if (
				$creditNote &&
				$creditNote->isDraft()
			) {
				$mValue = \L10N::t('Entwurf', Ext_Thebing_Document::$sL10NDescription);
			}

			return parent::format($mValue, $oColumn, $aResultData);
		}

		$oParentDocument	= $oDocument->getParentDocument();

		// Entwürfe haben keine Nummer
		if ($oDocument->isDraft()) {
			$mValue = \L10N::t('Entwurf', Ext_Thebing_Document::$sL10NDescription);
		}

		if(
			$oParentDocument &&
			$oParentDocument->document_number
		)
		{
			$sDocNumber	= $oParentDocument->document_number;
			
			if(!$oParentDocument->isReleased())
			{
				$sBadFontColor	= Ext_Thebing_Util::getColor('red_font');
				
				$sDocNumber		= '<label style="color:'.$sBadFontColor.'; font-style:italic;">'.$sDocNumber.'</label>';
			}
			
			$mValue .= ' (' . $sDocNumber . ') ';
		}
		
		$mValue = parent::format($mValue, $oColumn, $aResultData);
		
		return $mValue;
	}
}