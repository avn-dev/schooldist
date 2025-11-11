<?php


/**
 * @author Mehmet Durmaz
 * @todo Diese Klasse ist noch nicht fertig, weiter nach Gemeinsamkeiten bei den Kindern suchen...
 */

abstract class Ext_Thebing_Payment_Provider_Abstract extends Ext_Thebing_Basic 
{
	/**
	 * Cache-Variable
	 * @var int
	 */
	public $iSelectedId;

	public function validate($bThrowExceptions = false) 
	{
		$mReturn = parent::validate($bThrowExceptions);
		
		if(
			$this->active == 0
		)
		{
			return $mReturn;
		}

		if(
			$mReturn === true
		)
		{
			$mReturn = array();
		}
		
//		$iFoundedUnique = $this->checkUnique();
//
//		if(
//			$iFoundedUnique > 0
//		)
//		{
//			$mReturn[] = 'UNIQUE_DATA_FOUND';
//		}
			
		if(
			empty($mReturn)
		)
		{
			$mReturn = true;
		}
		
		return $mReturn;
	}
	
	public function checkIgnoringErrors()
	{
		$aHints = array();

		//wenn der betrag übereinstimmt und die währung nicht, ist bestimmt was faul
		/*if(
			$this->amount == $this->amount_school &&
			$this->payment_currency_id != $this->school_currency_id
		){
			$aHints[] = 'CURRENCY_CONVERT_HINT';
		}*/

		if(empty($aHints)){
			return true;
		}else{
			return $aHints;
		}
	}

	// TODO Die und untere Methode entfernen, da das anscheinend seit langer Zeit nicht mehr benötigt wird
	public function checkUnique()
	{
		$aFields = $this->_getUniqueFields();
		
		$sTable = $this->_sTable;
		
		$sSql = '
			SELECT
				`id`
			FROM
				#table
			WHERE
				`id` != :self_id AND
				`active` = 1
		';
		
		if(
			array_key_exists('parent_id', $this->_aData)
		)
		{
			$iParentId = (int)$this->parent_id;
			
			if(
				$iParentId <= 0	
			)
			{
				$sSql .= ' AND `parent_id` = 0 ';
			}
			else
			{
				//AdditionalPayments können leider nicht überprüft werden
				
				return 0;
			}
		}
		
		$aSql			= array(
			'table'		=> $this->getTableName(),
			'self_id'	=> (int)$this->id,
		);
		
		foreach($aFields as $sFieldName)
		{	
			$mFieldValue	= $this->$sFieldName;
			
			$sValueKey		= 'value_' . $sFieldName;
			
			$sSql .= ' AND `' . $sFieldName . '` = :' . $sValueKey;
			
			$aSql[$sValueKey] = $mFieldValue;
		}

		$iFounded = (int)DB::getQueryOne($sSql, $aSql);
		
		return $iFounded;
	}

	// TODO Die und obere Methode entfernen, da das anscheinend seit langer Zeit nicht mehr benötigt wird
	abstract protected function _getUniqueFields();
}