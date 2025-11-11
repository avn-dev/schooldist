<?

class Ext_Thebing_Gui2_Format_Email extends Ext_Gui2_View_Format_Abstract {

	public $bFormatAsLink = true;

	public function __construct($bFormatAsLink = true){
		$this->bFormatAsLink = $bFormatAsLink;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(
			isset($mValue) &&
			!empty($mValue) &&
			strpos($mValue, '@noemail.thebing.com') === false
		) {
			// Als Link formatieren
			if($this->bFormatAsLink){
				$mValue = '<a href="mailto:' . $mValue . '">' . $mValue . '</a>';
			}
			
		} else {
			$mValue = '';
		}

		return $mValue;

	}

}
