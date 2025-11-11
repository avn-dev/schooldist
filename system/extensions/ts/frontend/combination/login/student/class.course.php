<?php


class Ext_TS_Frontend_Combination_Login_Student_Course extends Ext_TS_Frontend_Combination_Login_Student_Abstract
{
	protected function _setData()
	{
		$oInquiry		= $this->_getInquiry();
		$iInquiryId		= (int)$oInquiry->id;

		if(
			$iInquiryId <= 0
		)
		{
			$this->_showLogin();
		}

		if($this->_getParam('week'))
		{
			$iWeek = $this->_getParam('week');
		}
		else
		{
			$iWeek = 0;
		}

		$aWeek	= Ext_Thebing_Util::getWeekTimestamps($iWeek);
		$iStart	= $aWeek['start'];
		$oDate	= new WDDate($iStart, WDDate::TIMESTAMP);
		$dWeek	= $oDate->get(WDDate::DB_DATE);

		$aClassWeeks		= Ext_Thebing_Tuition_Class::getClassWeeksByInquiry($iInquiryId);

		$aWeekOptions		= Ext_Thebing_Util::getWeekOptions();

		$aAllocationWeeks	= array();
		foreach($aClassWeeks as $dClassWeek)
		{
			$oDate->set($dClassWeek, WDDate::DB_DATE);
			$iTimeWeek = $oDate->get(WDDate::TIMESTAMP);
			if(
				isset($aWeekOptions[$iTimeWeek])
			)
			{
				$aAllocationWeeks[$iTimeWeek] = $aWeekOptions[$iTimeWeek];
			}
		}

		$sFilterFormHtml	= $this->_getWeekFilterHtml($aAllocationWeeks, $iStart);

		$this->_assign('sFilterFormHtml', $sFilterFormHtml);
		

		$aClasses = Ext_Thebing_Tuition_Class::getClassesByInquiryAndWeek($iInquiryId, $dWeek);

		$sTableHtml = $this->_getAllocationHtml($aClasses);
		$this->_assign('sTuitionTable', $sTableHtml);

		$this->_setTask("showCourseData");
	}

	protected function _getAllocationHtml($aClasses)
	{
		$aClasses			= (array)$aClasses;
		
		$oDummy				= null;
		$oFormatDaysAndTime = new Ext_Thebing_Gui2_Format_School_Tuition_DaysWithTime();
		$oFormatTeacher		= new Ext_Thebing_Gui2_Format_School_Tuition_Teachers();

		$sHtml		= '';
		$sHtml		.= '<table>';


		$sHtml		.= '<tr>';

		//th
		$sHtml		.= '<th>';
		$sHtml		.= $this->t('Days');
		$sHtml		.= '</th>';

		$sHtml		.= '<th>';
		$sHtml		.= $this->t('Time');
		$sHtml		.= '</th>';

		$sHtml		.= '<th>';
		$sHtml		.= $this->t('Class');
		$sHtml		.= '</th>';

		$sHtml		.= '<th>';
		$sHtml		.= $this->t('Building');
		$sHtml		.= '</th>';

		$sHtml		.= '<th>';
		$sHtml		.= $this->t('Room');
		$sHtml		.= '</th>';

		$sHtml		.= '<th>';
		$sHtml		.= $this->t('Teacher');
		$sHtml		.= '</th>';

		$sHtml		.= '</tr>';

		//td
		if(!empty($aClasses))
		{
			foreach($aClasses as $aClassData)
			{
				$sHtml		.= '<tr>';

				$aDaysTime	= $oFormatDaysAndTime->format(null, $oDummy, $aClassData);
				$aInfo		= explode(': ', $aDaysTime);
				$sDays		= $aInfo[0];
				$sTimes		= $aInfo[1];
				$sTeachers	= $oFormatTeacher->format(null, $oDummy, $aClassData);

				$sHtml		.= '<td>';
				$sHtml		.= $sDays;
				$sHtml		.= '</td>';

				$sHtml		.= '<td>';
				$sHtml		.= $sTimes;
				$sHtml		.= '</td>';

				$sHtml		.= '<td>';
				$sHtml		.= $aClassData['class_name'];
				$sHtml		.= '</td>';

				$sHtml		.= '<td>';
				$sHtml		.= $aClassData['building'];
				$sHtml		.= '</td>';

				$sHtml		.= '<td>';
				$sHtml		.= $aClassData['classroom'];
				$sHtml		.= '</td>';

				$sHtml		.= '<td>';
				$sHtml		.= $sTeachers;
				$sHtml		.= '</td>';

				$sHtml		.= '</tr>';
			}
		}
		else
		{
			$sHtml		.= '<tr>';
			$sHtml		.= '<td colspan="6">';
			$sHtml		.= '<p>'.$this->t('No Course Allocations').'</p>';
			$sHtml		.= '</td>';
			$sHtml		.= '</tr>';
		}
		
		$sHtml		.= '</table>';

		return $sHtml;
	}

	protected function _getWeekFilterHtml($aWeeks, $iCurrent)
	{
		$sHtml	= '';
		$aWeeks = (array)$aWeeks;

		$sHtml .= '<form action="'.$this->_getUrl('showCourseData').'" method="post" id="week-form">';

		$sHtml .= '<img src="../icef_login/prev.png" onclick="changeWeekFilter(\'prev\');" alt="prev" class="prev" title="last week" />';

		$sHtml .= '<select name="week" id="week-filter" onchange="this.form.submit();">';

		foreach($aWeeks as $iWeek => $sWeek)
		{
			$sSelected = '';
			
			if(
				$iWeek == $iCurrent
			)
			{
				$sSelected = ' selected="selected"';
			}

			$sHtml .= '<option value="'.$iWeek.'"'.$sSelected.'>';
			$sHtml .= $sWeek;
			$sHtml .= '</option>';
		}

		$sHtml .= '</select>';

		$sHtml .= '<img src="../icef_login/next.png" onclick="changeWeekFilter(\'next\');" alt="next" class="next" title="next week" />';

		$sHtml .= '<div class="clearer"></div>';
		
		$sHtml .= '</form>';

		return $sHtml;
	}
}
