<?php

class Ext_Thebing_Gui2_Format_School_Tuition_DivDraggable extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @var string
	 */
	protected $sView;

	/**
	 * @param string $sView
	 */
	public function __construct($sView) {
		$this->sView = $sView;
	}

	/**
	 * @inheritdoc
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sName = $this->formatName($oColumn, $aResultData);


		// Kein DIV, wenn Schüler Urlaub hat
		if(
			!($aResultData['state'] & Ext_TS_Inquiry_TuitionIndex::STATE_VACATION) &&
			!($aResultData['state_course'] & Ext_TS_Inquiry_TuitionIndex::STATE_VACATION) &&		
			//Beispiel: wir haben den Montag als Wochentag ausgewählt in der Klassenplanung,
			//und der Kurs fängt aber erst ab Dienstag an, dann soll der Schüler nicht zuweisbar sein
			//aber trotzdem angezeigt werden, siehe T-2874
			$aResultData['between_course_date'] == 1
		) {
			$oDiv = new Ext_Gui2_Html_Div();
			$oDiv->id = 'inquiry_'.$this->sView.'_'.$aResultData['inquiry_course_id'].'_'.$aResultData['program_service_id'].'_'.$aResultData['id'];
			$oDiv->class = 'student';
			$oDiv->setElement($sName);

			return $oDiv->generateHTML();
		} else {
			return $sName;
		}
		
	}

	/**
	 * @inheritdoc
	 */
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$aReturn = array();
		$aReturn['content'] = nl2br($aResultData['comment']);
		$aReturn['name'] = $this->formatName($oColumn, $aResultData);
		$aReturn['tooltip'] = true;

		return $aReturn;
		
	}

	/**
	 * @param Ext_Gui2_Head $oColumn
	 * @param array $aResultData
	 * @return string
	 */
	private function formatName(Ext_Gui2_Head $oColumn, array $aResultData) {

		if($oColumn->select_column === 'customer_name') {
			return Ext_Thebing_Gui2_Format_CustomerName::manually_format($aResultData['lastname'], $aResultData['firstname']);
		} elseif($oColumn->select_column === 'firstname') {
			return $aResultData['firstname'];
		} elseif($oColumn->select_column === 'lastname') {
			return $aResultData['lastname'];
		} else {
			throw new RuntimeException('Invalid column');
		}

	}

}
