<?php
/**
 * Klasse die Default methoden bereit stellt für die default.json 
 */
class Ext_Gui2_Factory_Default {
	
	public static function getDialog(Ext_Gui2 $oGui){
		$oDialog = $oGui->createDialog();
		return $oDialog;
	}
	
	public static function getFilterSelectOptions(){
		return array();
	}

	/**
	 * @param bool $bFormatted
	 * @param string $sType
	 * @return string
	 */
	public static function getDefaultFilterFrom($bFormatted = false, $sType = 'week') {

		$dDate = new DateTime();
		$dDate->sub(self::getTimefilterInterval($sType));
		$sDate = $dDate->format('Y-m-d');

        if($bFormatted) {
            $oFormat = Factory::getObject('Ext_Gui2_View_Format_Date');
            $sDate = $oFormat->formatByValue($sDate);
        }
        
		return $sDate;
	}

	/**
	 * @param bool $bFormatted
	 * @param string $sType
	 * @return string
	 */
	public static function getDefaultFilterUntil($bFormatted = false, $sType = 'week') {

		$dDate = new DateTime();
		$dDate->add(self::getTimefilterInterval($sType));
		$sDate = $dDate->format('Y-m-d');

        if($bFormatted) {
            $oFormat = Factory::getObject('Ext_Gui2_View_Format_Date');
            $sDate = $oFormat->formatByValue($sDate);
        }
        
		return $sDate;
	}

	/**
	 * @param string $sType
	 * @return DateInterval
	 */
	private static function getTimefilterInterval($sType) {
		switch($sType) {
			case 'week':
				return new DateInterval('P1W');
			case 'month':
				return new DateInterval('P1M');
			case 'year':
				return new DateInterval('P1Y');
			default:
				throw new InvalidArgumentException('Unknown type:'.$sType);
		}
	}
	
	public static function getIcon($sIconType){
		return 'fa-pencil';
	}
	
	public static function getValue(){
		return "";
	}
	
	public static function getWhere(){
		return array();
	}
	
	public static function getOrderby(){
		return array();
	}
	
	public static function getSelectFilterNavigation(){
		return array('default_value', '');
	}
}