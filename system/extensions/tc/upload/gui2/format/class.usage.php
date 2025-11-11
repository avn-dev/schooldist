<?

class Ext_TC_Upload_Gui2_Format_Usage extends Ext_TC_Gui2_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		$aBack = array();

		$oUpload = \Factory::getInstance(Ext_TC_Upload::class, $mValue);

		// Verwendungszweck
		$aUsage = $oUpload->getUsage();

		foreach((array)$aUsage as $aReason){
			$aBack[] = sprintf('%s: %s', L10N::t($aReason['reason']), $aReason['name']);
		}

		return implode('<br/>', $aBack);
	}

}
