<?php


class Ext_Thebing_Gui2_Format_LoaPdf extends Ext_Gui2_View_Format_Abstract {

	protected $_aDescriptions = array();

	public function __construct() {
		$this->_aDescriptions = array(
			'loa_pdf'			=> Ext_Thebing_L10N::t('LOA Pdf', '', 'Thebing » Invoice » Inbox'),
			'loa_pdf_required'	=> Ext_Thebing_L10N::t('LOA Pdf benötigt', '', 'Thebing » Invoice » Inbox'),
			'loa_pdf_sent'		=> Ext_Thebing_L10N::t('LOA Pdf gesendet', '', 'Thebing » Invoice » Inbox'),
			'loa_pdf_not_sent'	=> Ext_Thebing_L10N::t('LOA Pdf nicht gesendet', '', 'Thebing » Invoice » Inbox')
		);
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(
			isset($aResultData['id']) && 
			isset($aResultData['id']) > 0 
		) {

			list($aResultData['kidv_loa_sent'], $aResultData['kidv_loa_path']) = explode("|", $aResultData['kidv_loa']);

			## Letztes PDF ermitteln
				$sPdfPath = $aResultData['kidv_loa_path'];

				if($sPdfPath == ''){
					$sOnClick = '';
					$sIcon = 'page_grey_acrobat.png';
					$sStyle = 'cursor: default';
				}else{
					$sOnClick = 'onclick="window.open(\'/storage/download'.$sPdfPath.'\'); return false"';
					$sIcon = 'page_white_acrobat.png';
					$sStyle = 'cursor: pointer';
				}
			##

			$iMailCount = $aResultData['kidv_loa_sent'];

			if(
				empty($iMailCount) ||
				$iMailCount == '0000-00-00 00:00:00'
			) {
				$iMailCount = 0;
			} else {
				$iMailCount = 1;
			}

			if(
				isset($aResultData['agency_id']) &&
				$aResultData['agency_id'] > 0
			) {
					
				if($aResultData['pdf_loa'] == 1 && $iMailCount > 0) {
					return '<img style="' . $sStyle . '" '  . $sOnClick . ' src="/media/' . $sIcon . '" alt="'.$this->_aDescriptions['loa_pdf'].'" title="'.$this->_aDescriptions['loa_pdf'].'" />';
				} else if($aResultData['pdf_loa'] == 1){
					return '<img style="' . $sStyle . '" ' . $sOnClick . ' src="/media/' . $sIcon . '" alt="'.$this->_aDescriptions['loa_pdf_required'].'" title="'.$this->_aDescriptions['loa_pdf_required'].'" />';
				}
			} else {

				if($iMailCount > 0){
					return '<img style="' . $sStyle . '" ' . $sOnClick . ' src="/media/' . $sIcon . '" alt="'.$this->_aDescriptions['loa_pdf_sent'].'" title="'.$this->_aDescriptions['loa_pdf_sent'].'" />';
				} else {
					return '<img style="' . $sStyle . '" ' . $sOnClick . ' src="/media/' . $sIcon . '" alt="'.$this->_aDescriptions['loa_pdf_not_sent'].'" title="'.$this->_aDescriptions['loa_pdf_not_sent'].'"/>';
				}

			}
			
		}else{
			return '';
		}
	}

	public function align(&$oColumn = null){
		return 'center';
	}
}