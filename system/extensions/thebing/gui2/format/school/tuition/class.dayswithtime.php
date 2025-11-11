<?php


class Ext_Thebing_Gui2_Format_School_Tuition_DaysWithTime extends Ext_Gui2_View_Format_Abstract
{
	protected $_oFormatDay;

	public function __construct()
	{
		$this->_oFormatDay	= new Ext_Thebing_Gui2_Format_Day('%a');
		$this->_oTime		= new Ext_Thebing_Gui2_Format_Time();
	}

	/** @TODO Hier soll die Ext_Thebing_Util::buildJoinedWeekdaysString() für die Wochentage verwendet, um Redundanz zu vermeiden
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		if(empty($aResultData['days'])){
			return $aResultData['days'];
		}
		$aResultsByBlockId	= explode(',', $aResultData['days']);
		$aTemp				= array();
		foreach($aResultsByBlockId as $sInfos){
			$aInfos		= explode('_',$sInfos);
			$iBlockId	= $aInfos[0];
			$iDay		= $aInfos[1];
			$sFrom		= $aInfos[2];
			$sUntil		= $aInfos[3];

			$aTemp[$iBlockId]['days'][] = $iDay;
			$aTemp[$iBlockId]['from']	= $sFrom;
			$aTemp[$iBlockId]['until']	= $sUntil;
		}

		$sReturn = '';
		foreach($aTemp as $iBlockId => $aReturnData)
		{
			$aDays = (array)$aReturnData['days'];
			sort($aDays);
			$aDiff = array_diff(array(1,2,3,4,5),$aDays);
			if(empty($aDiff))
			{
				$sReturn .= $this->_oFormatDay->format(1,$oColumn,$aResultData);
				$sReturn .= '-';
				$sReturn .= $this->_oFormatDay->format(5,$oColumn,$aResultData);
			}
			else
			{
				foreach($aDays as $iKey => $iDay)
				{
					$sReturn .= $this->_oFormatDay->format($iDay,$oColumn,$aResultData);
					if($iKey<count($aDays)-1)
					{
						$sReturn .= ',';
					}
				}
			}
			$sReturn .= ': ';
			$sReturn .= $this->_oTime->format($aReturnData['from'],$oColumn,$aResultData);
			$sReturn .= '-';
			$sReturn .= $this->_oTime->format($aReturnData['until'],$oColumn,$aResultData);
			$sReturn .= '<br />';
		}

		return $sReturn;
	}
}
