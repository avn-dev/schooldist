<?php

class Ext_Thebing_Gui2_Icon_Document_Visible extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {
        global $_VARS;

        $_VARS['parent_gui_id'] = (array)$_VARS['parent_gui_id'];
        $iInquiryId = (int)reset($_VARS['parent_gui_id']);

		if($this->_oGui->hasParent()) {
			$oParentGui = $this->_oGui->getParent();
			$sEncodeOption = $oParentGui->getOption('decode_inquiry_id_additional_documents');
			if($sEncodeOption) {
				$iInquiryId = (int)$oParentGui->decodeId($iInquiryId, $sEncodeOption);
			}
		}

        $oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
		$oDocument = Ext_Thebing_Inquiry_Document::getInstance((int)reset($aSelectedIds));

		$creditnote = null;
		$creditnoteSubagency = null;
		if (
			str_contains($oElement->action, 'creditnote') ||
			str_contains($oElement->id, 'creditnote') // Um label_group anzuzeigen
		) {
			$creditnote = $oDocument->getCreditNote();
			$creditnoteSubagency = $oDocument->getCreditNoteSubagency();
		}

		if($oElement->action == 'diff_agency') {

            if(
	            !$oInquiry->hasAgency()
            ) {
                return 0;
            }

        } else if(
            (
                $oElement->action === 'creditnote_new' || 
                $oElement->action === 'creditnote_edit' ||
                $oElement->action === 'creditnote_refresh' ||
                $oElement->id === 'creditnote'
            ) &&
            (
                !$oInquiry->hasAgency() ||
				$oDocument->isNetto()
            )
        ) {
			return 0;
        } else if(
            (
                $oElement->action === 'creditnote_subagency_new' || 
                $oElement->action === 'creditnote_subagency_edit' ||
                $oElement->action === 'creditnote_subagency_refresh' ||
                $oElement->id === 'creditnote_subagency'
            ) &&
            (
                !$oInquiry->hasAgency() ||
				!$oInquiry->hasSubAgency()
            )
        ) {
			return 0;
        } elseif(
			$oElement->id == 'diff_documents' &&
			$oInquiry->hasGroup()
		) {
			return 1; #2464
		} elseif(
			(
				$oElement->action == 'diff_customer' ||
				$oElement->action == 'diff_customer_plus_credit'
			) &&
			$oInquiry->hasGroup()
		) {
			return 1; #2464
		} else if (
			(
				$oElement->action === 'finalize' ||
				$oElement->id === 'draft'
			)
			&&
			(
				!$oDocument ||
				$oDocument->getId() == 0 ||
				!$oDocument->isDraft()
			)
		) {
			return 0;
		} else if (
			(
				$oElement->action === 'finalize_creditnote' ||
				$oElement->id === 'creditnote_draft'
			)
			&&
			(
				!$creditnote?->isDraft() &&
				!$creditnoteSubagency?->isDraft()
			)
		) {
			return 0;
		}

		return parent::getStatus($aSelectedIds, $aRowData, $oElement);
	}

}