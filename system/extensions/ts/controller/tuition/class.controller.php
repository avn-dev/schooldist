<?php

use Core\Handler\CookieHandler;

class Ext_TS_Controller_Tuition_Controller extends MVC_Abstract_Controller
{
	protected $_sL10NPart;

	public function lastAllocation() {	
		
		global $session_data;
		
		\System::setInterface('backend');
		Ext_TC_System::setInterfaceLanguage(CookieHandler::get('systemlanguage'));
		
		Ext_TC_System::setLocale();
		
		$this->_sL10NPart = 'Thebing » Tuition » Planification';
		
		$aValues = $this->_oRequest->input('values');

		$oDate = new WDDate($aValues['week']);

		$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance((int)$aValues['inquiry_course_id']);
		$oCourse = Ext_Thebing_Tuition_Course::getInstance((int)$aValues['course_id']);

		// Nur bei der Status/Kurs Spalte sollen die
		// Kurse berücksichtigt werden
		if($aValues['column'] === 'state_course') {
			$aAllocationForInquiry	= Ext_Thebing_School_Tuition_Allocation::getAllocationByInquiryCourse($oDate->get(WDDate::DB_DATE), $oInquiryCourse, $oCourse, true, 10);
		} else {
			$aAllocationForInquiry	= Ext_Thebing_School_Tuition_Allocation::getAllocationByInquiry($oDate->get(WDDate::DB_DATE), $oInquiryCourse->getInquiry(), true, 10);
		}

		if(!empty($aAllocationForInquiry))
		{
			$sHtml = '<table class="table table-condensed">';

			$sHtml .= '<tr>';
			$sHtml .= '<th>';
			$sHtml .= $this->t('Woche');
			$sHtml .= '</th>';
			$sHtml .= '<th>';
			$sHtml .= $this->t('Kurs');
			$sHtml .= '</th>';
			$sHtml .= '<th>';
			$sHtml .= $this->t('Klasse');
			$sHtml .= '</th>';
			$sHtml .= '<th>';
			$sHtml .= $this->t('Klassenzimmer');
			$sHtml .= '</th>';
			$sHtml .= '<th>';
			$sHtml .= $this->t('Tage');
			$sHtml .= '</th>';
			$sHtml .= '<th>';
			$sHtml .= $this->t('Uhrzeit');
			$sHtml .= '</th>';
			$sHtml .= '<th>';
			$sHtml .= $this->t('Lehrer');
			$sHtml .= '</th>';
			$sHtml .= '<th>';
			$sHtml .= $this->t('Level');
			$sHtml .= '</th>';
			$sHtml .= '</tr>';

			foreach($aAllocationForInquiry as $aData)
			{
				$dWeek = $aData['block_week'];

				$oDate->set($dWeek, WDDate::DB_DATE);
				$sWeek = $oDate->get(WDDate::WEEK);

				$oDate->set($aData['block_from'], WDDate::TIMES);
				$sFrom = $oDate->get('H:I');
				$oDate->set($aData['block_until'], WDDate::TIMES);
				$sUntil = $oDate->get('H:I');
				
				$aDays = Ext_Thebing_Util::getDays('%a');
				$oDaysFormat = new Ext_Thebing_Gui2_Format_Multiselect($aDays, ', ', 'string', ',');
				$sDays = $oDaysFormat->format($aData['days']);
				
				$sFromUntil	= $sFrom . ' - ' . $sUntil;		

				$sProgress = Ext_Thebing_Tuition_Progress::getProgress($aData['inquiry_course_id'], 'current', 'name_short', $dWeek, $aData['courselanguage_id']);
				
				$sHtml .= '<tr>';
				$sHtml .= '<td>';
				$sHtml .= $this->t('Woche') . $sWeek;
				$sHtml .= '</td>';
				$sHtml .= '<td>';
				$sHtml .= $aData['course_short'];
				$sHtml .= '</td>';
				$sHtml .= '<td>';
				$sHtml .= $aData['class_name'];
				$sHtml .= '</td>';
				$sHtml .= '<td>';
				$sHtml .= $aData['classroom'];
				$sHtml .= '</td>';
				$sHtml .= '<td>';
				$sHtml .= $sDays;
				$sHtml .= '</td>';
				$sHtml .= '<td>';
				$sHtml .= $sFromUntil;
				$sHtml .= '</td>';
				$sHtml .= '<td>';
				$sHtml .= $aData['teacher_name'];
				$sHtml .= '</td>';
				$sHtml .= '<td>';
				$sHtml .= $sProgress;
				$sHtml .= '</td>';
				$sHtml .= '</tr>';
			}
			$sHtml .= '</table>';	
		} else {
			$sHtml = '<label>'.$this->t('Keine Zuweisungen gefunden.').'</label>';
		}

		$sHtml .= '<hr />';
		$sHtml .= '<strong>'.$this->t('Legende').'</strong>';

		$sLanguage = \Ext_TC_System::getInterfaceLanguage();;

		$oLanguage = new Tc\Service\Language\Backend($sLanguage);
		$oLanguage->setPath($this->_sL10NPart);

		$oState = new \TsTuition\Helper\State(\TsTuition\Helper\State::KEY_STRING, $oLanguage);
		$aStatesLegend = $oState->getOptions();

		$sHtml .= '<ul>';

		foreach($aStatesLegend as $sKey => $sDescription) {
			$sHtml .= '<li>'.$sKey.' - '.$sDescription.'</li>';
		}

		$sHtml .= '</ul>';
		
		$this->set('tooltip', $sHtml);
		$this->set('tooltip_id', $this->_oRequest->get('tooltip_id'));
		$this->set('action', 'loadTooltipContent');
	}

	/**
	 * @param $sText
	 * @return string
	 */
	public function t($sText) {
		$sText = L10N::t($sText, $this->_sL10NPart);		
		return $sText;
	}

}
