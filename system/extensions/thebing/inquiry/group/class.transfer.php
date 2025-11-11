<?php
class Ext_Thebing_Inquiry_Group_Transfer extends Ext_Thebing_Inquiry_Group_Service {
	
	protected $_sTable = 'kolumbus_groups_transfers';

	protected $_aFormat = array(
		'changed' => array(
			'format' => 'TIMESTAMP'
			),
		'created' => array(
			'format' => 'TIMESTAMP'
			),
		'transfer_date' => array(
						'format' => 'DATE'
		),
		'transfer_time' => array(
						'format' => 'TIME',
						'validate' => 'TIME'
		),
		'pickup' => array(
						'format' => 'TIME',
						'validate' => 'TIME'
		)
	);

	// Gibt den Titel für einen Gruppen Transfer
	public function getName($oCalendarFormat = null) {

		$oGroup			= $this->getGroup()->getTransferLocations();
		$oSchool		= $this->getGroup()->getSchool();

		// Buchungsbezogene An/Abreise Orte
		$aTransfer = $this->getGroup()->getTransferLocations();


		$sStart			= $aTransfer[$this->start_type . '_' . $this->start];
		$sEnd			= $aTransfer[$this->end_type . '_' . $this->end];
		if($oCalendarFormat){
			$sDate		= $oCalendarFormat->format($this->transfer_date);
		}else{
			$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
			$aTemp['school_id'] = $oSchool->id;
			$sDate		= $oDateFormat->format($this->transfer_date, $aTemp, $aTemp);
		}
		$sWeekday		= Ext_Thebing_Util::getWeekDay(2, $this->transfer_date);
		$sTime			= substr($this->transfer_time, 0 , 5);


		$sName = '';
		$sName .= $sStart." - ";
		$sName .= $sEnd;
		$sName .= ' (' . $sWeekday . ' ' . $sDate . ' ' . $sTime . ')';

		return $sName;
	}

	public function getGroup(){
		if($this->group_id > 0){
			return Ext_Thebing_Inquiry_Group::getInstance($this->group_id);
		}else{
			return NULL;
		}
	}

	/**
	 * @return null|string
	 */
	public function getIndexTransferDateTime() {

		$sTime = $this->transfer_time;
		$sReturn = $this->transfer_date;

        if(empty($sReturn) || $sReturn == '0000-00-00') {
            return null;
        }

		if(!empty($sTime)) {
			$sReturn .= 'T'.$sTime;
		}

		return $sReturn;

	}

}