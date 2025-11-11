<?php


class Ext_Thebing_Gui2_Format_GrossPdf extends Ext_Gui2_View_Format_Abstract {

	protected $_aDescriptions = array();
	
	public function __construct(){
		$this->_aDescriptions = array(
			'gross_pdf'				=> Ext_Thebing_L10N::t('Brutto Pdf', '', 'Thebing » Invoice » Inbox'),
			'gross_pdf_required'	=> Ext_Thebing_L10N::t('Brutto Pdf benötigt', '', 'Thebing » Invoice » Inbox'),
			'gross_pdf_sent'		=> Ext_Thebing_L10N::t('Brutto Pdf gesendet', '', 'Thebing » Invoice » Inbox'),
			'gross_pdf_not_sent'	=> Ext_Thebing_L10N::t('Brutto Pdf nicht gesendet', '', 'Thebing » Invoice » Inbox')
		);
	}
	
	/**
	 * @todo Label der Dokumente zentral holen!
	 * @param <type> $mValue
	 * @param <type> $oColumn
	 * @param <type> $aResultData
	 * @return <type>
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(
			isset($aResultData['id']) && 
			$aResultData['id'] > 0 
		) {

			list($aResultData['kidv_gross_sent'], $aResultData['kidv_gross_path']) = explode("|", $aResultData['kidv_gross']);

			## Letztes PDF ermitteln
				$sPdfPath = $aResultData['kidv_gross_path'];
				
				if($sPdfPath == ''){
					$sOnClick = '';
					$sIcon = Ext_Thebing_Util::getIcon('pdf_inactive');
					$sStyle = 'cursor: default';
				}else{
					$sOnClick = 'onclick="window.open(\'/storage/download'.$sPdfPath.'\'); return false"';
					$sIcon = Ext_Thebing_Util::getIcon('pdf');
					$sStyle = 'cursor: pointer';
				}
			##
			
			$iMailCount = $aResultData['kidv_gross_sent'];

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
				if($aResultData['pdf_gross'] == 1 && $iMailCount > 0){
					return '<img style="' . $sStyle . '" ' . $sOnClick . ' src="' . $sIcon . '" alt="'.$this->_aDescriptions['gross_pdf'].'" title="'.$this->_aDescriptions['gross_pdf'].'"/>';
				} else { 
					return '<img style="' . $sStyle . '" ' . $sOnClick . ' src="' . $sIcon . '" alt="'.$this->_aDescriptions['gross_pdf_required'].'" title="'.$this->_aDescriptions['gross_pdf_required'].'"/>';
				} 
			} else {
				if($iMailCount > 0){
					// Verschickt
					return '<img style="' . $sStyle . '" ' . $sOnClick . ' src="' . $sIcon . '" alt="'.$this->_aDescriptions['gross_pdf_sent'].'" title="'.$this->_aDescriptions['gross_pdf_sent'].'"/>';
				} else {
					// Noch nicht verschickt
					return '<img style="' . $sStyle . '" ' . $sOnClick . ' src="' . $sIcon . '" alt="'.$this->_aDescriptions['gross_pdf_not_sent'].'" title="'.$this->_aDescriptions['gross_pdf_not_sent'].'"/>';
				}
			}
			
		} else {
			return '';
		}
		
	}
	
	public function align(&$oColumn = null){
		return 'center';
	}

}
