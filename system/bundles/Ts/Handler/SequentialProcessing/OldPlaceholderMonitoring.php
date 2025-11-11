<?php

namespace Ts\Handler\SequentialProcessing;

use \Core\Handler\SequentialProcessing\TypeHandler;

class OldPlaceholderMonitoring extends TypeHandler {

	/**
	 * @var string
	 */
	protected $sLogFile;

	public function __construct() {
		$this->sLogFile = \Util::getDocumentRoot().'storage/logs/placeholder.log';
	}

	/**
	 * @inheritdoc
	 */
	public function execute($oObject) {
//		global $_VARS;

		$aAllPlaceholders = [];

		/** @var \Ext_Thebing_Placeholder $oObject */
		foreach($oObject::getMonitoringPlaceholders() as $sClass => $aPlaceholders) {
			foreach($aPlaceholders as $mUsedPlaceholders) {
				if(is_array($mUsedPlaceholders)) {
					foreach($mUsedPlaceholders as $sPlaceholder => $aPlaceholders) {
						foreach($aPlaceholders as $aPlaceholder) {
							$sPlaceholderTmp = $sPlaceholder;
							if(!empty($aPlaceholder['modifier'])) {
								$sPlaceholderTmp .= '|'.$aPlaceholder['modifier'].':'.$aPlaceholder['parameter'];
							}
							$aAllPlaceholders[$sClass][] = $sPlaceholderTmp;
						}
					}
				} else {
					// Loops
					$aAllPlaceholders[$sClass][] = $mUsedPlaceholders;
				}
			}
		}

		$aAllPlaceholders = array_map(function(array $aPlaceholder) {
			return array_values(array_unique($aPlaceholder));
		}, $aAllPlaceholders);

		// Nur beim Dokumentendialog gefüllt
//		$iTemplateId = null;
//		if(isset($_VARS['template_id'])) {
//			$iTemplateId = $_VARS['template_id'];
//		}

		// TODO PP wird mit root ausgeführt, dann gehört die Datei bis zur Abholung auch root
		$rFile = fopen($this->sLogFile, 'a');
		@chmod($this->sLogFile, 0777);

		$aData = [
			'timestamp' => gmdate('Y-m-d H:i:s'),
			'licence' => \System::d('license'),
			'version' => \System::d('version'),
//			'template_id' => $iTemplateId,
			'placeholders' => $aAllPlaceholders
		];

		$sJson = json_encode($aData);
		$sJson = str_replace("\n", '', $sJson); // Falls Regex doch mal \n haben sollte

		fwrite($rFile, $sJson."\n");
		fclose($rFile);

	}

	/**
	 * @inheritdoc
	 */
	public function check($oObject) {
		return $oObject instanceof \Ext_Thebing_Placeholder;
	}

}
