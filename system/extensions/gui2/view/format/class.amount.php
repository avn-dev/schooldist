<?
class Ext_Gui2_View_Format_Amount extends Ext_Gui2_View_Format_Float {

	protected $sCurrency	= '€';

	// liefert den wert
	public function get($mValue, &$oColumn = null, &$aResultData = null){
		return $mValue;
	}

	public function format($fAmount, &$oColumn = null, &$aResultData = null){
		$mAmount = parent::format($fAmount, $oColumn, $aResultData);
		
		$sCurrency = $this->sCurrency;

		// Währung anhängen falls vorhanden
		if($sCurrency != ''){
			if($sCurrency == '$'){
				$mAmount = $sCurrency.' '.$mAmount;
			} else {
				$mAmount .= ' '.$sCurrency;
			}
		}

		return $mAmount;

	}

}
