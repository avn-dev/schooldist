<?php

/**
 * @TODO Diese Klasse ist eine Mischung aus Entität, Fassade, Service und Collection
 *
 * @property int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $active
 * @property int $user_id
 * @property int $creator_id
 * @property int $version_id
 * @property int $type
 * @property float $amount_gross
 * @property float $amount_net
 * @property float $amount_provision
 * @property float $amount_discount_gross
 * @property float $amount_discount_net
 * @property float $amount_discount_provision
 * @property float $amount_vat_gross Immer mit Discount!
 * @property float $amount_vat_net Immer mit Discount!
 * @property float $amount_vat_provision Immer mit Discount!
 */
class Ext_Thebing_Inquiry_Document_Version_Price extends Ext_Thebing_Basic {
	
	protected $_sTable = 'kolumbus_inquiries_documents_versions_priceindex';

	protected $_sTableAlias = 'kidvp';
	
	protected $_aFormat = array(
		'changed' => array(
			'format' => 'TIMESTAMP'
			),
		'created' => array(
			'format' => 'TIMESTAMP'
			),
		'version_id' => array(
			'required'=>true,
			'validate'=>'INT_POSITIVE'
			),
		'amount_gross' => array(
			'validate'=>'FLOAT'
			),
		'amount_net' => array(
			'validate'=>'FLOAT'
			),
		'amount_provision' => array(
			'validate'=>'FLOAT'
			),
		'amount_discount_gross' => array(
			'validate'=>'FLOAT'
			),
		'amount_discount_net' => array(
			'validate'=>'FLOAT'
			),
		'amount_discount_provision' => array(
			'validate'=>'FLOAT'
			),
		'amount_vat_gross' => array(
			'validate'=>'FLOAT'
			),
		'amount_vat_net' => array(
			'validate'=>'FLOAT'
			),
		'amount_vat_provision' => array(
			'validate'=>'FLOAT'
			),
	);

	// Objecte für Vor Ort und Vor Anreise Kosten
	protected $_oPriceIndexAtSchool		= null;
	protected $_oPriceIndexBeforeArrival = null;
	
	/**
	 * Sucht die gespeicherten Preise zu einer Version
	 *
	 * @param Ext_Thebing_Inquiry_Document_Version|int $mVersion
	 * @param array $mType
	 * @return self[]
	 * @throws UnexpectedValueException
	 */
	public static function getByVersion($mVersion, $mType = array()) {
		
		if($mVersion instanceof Ext_Thebing_Inquiry_Document_Version) {
			$iVersion = $mVersion->id;
		} elseif(is_int($mVersion)) {
			$iVersion = (int)$mVersion;
		} else {
			throw new UnexpectedValueException('Wrong Version Data!');
		}

		// TODO Was ist das?
		$aTypes = array((int)$mType);
		
		if(is_array($mType)) {
			$aTypes = array(0, 1);
		}
		
		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_inquiries_documents_versions_priceindex`
			WHERE
				`version_id` = :version_id AND
				`type` IN (:types)
		";
		
		$aSql = array();
		$aSql['version_id'] = (int)$iVersion;
		$aSql['types']		= $aTypes;

		$aData = (array)DB::getQueryRows($sSql, $aSql);

		return array_map(function (array $aRow) {
			return self::getObjectFromArray($aRow);
		}, $aData);
		
	}

	public static function getVersionAmountArray($mVersion, $bBeforeArrival = true, $bAtSchool = true){

		$aPriceIndexList = (array) self::getPriceIndexList($mVersion, $bBeforeArrival, $bAtSchool);

		if(empty($aPriceIndexList)){
			return array();
		}

		$aAmount = array(
			'amount' => 0,
			'amount_net' => 0,
			'amount_provision' => 0
		);

		foreach($aPriceIndexList as $oPriceIndex){
			//alte Form beibehalten für die Version
			$fAmount			= $oPriceIndex->calculateAmount('gross');
			$fAmountNet			= $oPriceIndex->calculateAmount('net');
			$fAmountProvision	= $oPriceIndex->calculateAmount('provision');
			
			if(
				(
					$oPriceIndex->type == 0 && 
					$bBeforeArrival	
				) ||
				(
					$oPriceIndex->type == 1 && 
					$bAtSchool		
				)
			){
				$aAmount['amount'] += $fAmount;
				$aAmount['amount_net'] += $fAmountNet;
				$aAmount['amount_provision'] += $fAmountProvision;
			}
		}

		return $aAmount;

	}

	/**
	 * Zwischenfunktion damit in der Version keine if-abfragen für den richtigen Typ stattfinden muss
	 *
	 * @param mixed $mVersion
	 * @param bool $bBeforeArrival
	 * @param bool $bAtSchool
	 * @return Ext_Thebing_Inquiry_Document_Version_Price <array>
	 */
	public static function getPriceIndexList($mVersion, $bBeforeArrival = true, $bAtSchool = true)
	{
		if(
			$bBeforeArrival &&
			$bAtSchool
		)
		{
			//alles
			$mType = array(); 
		}
		else
		{
			if($bBeforeArrival)
			{
				//initalcost = 0
				$mType = 0;
			}
			else
			{
				//initalcost = 1
				$mType = 1;
			}
		}

		$aPriceIndexList = self::getByVersion($mVersion, $mType); 

		return $aPriceIndexList;
	}

	/**
	 *
	 * @return Ext_Thebing_Inquiry_Document_Version
	 */
	public function getVersion()
	{
		$iVersionId = (int)$this->version_id;
		$oVersion	= Ext_Thebing_Inquiry_Document_Version::getInstance($iVersionId);

		return $oVersion;
	}

	public function calculateAmount($sType)
	{	
		if(
			$sType != 'gross' &&
			$sType != 'net' &&
			$sType != 'provision'
		)
		{
			return 0;
		}

		$oVersion				= $this->getVersion();

		$sCall					= 'amount_'.$sType;
		$fAmount				= $this->$sCall;

		$sCall					= 'amount_discount_'.$sType;
		$fAmountDiscount		= $this->$sCall;

		$sCall					= 'amount_vat_'.$sType;
		$fAmountVat				= $this->$sCall;

		$fReturn = $fAmount - $fAmountDiscount;

		if(
			$oVersion->tax == 2
		)
		{
			//excl
			$fReturn += $fAmountVat;
		}

		return $fReturn;

	}
	
	public function addItem(Ext_Thebing_Inquiry_Document_Version_Item &$oItem){

		if(
			$oItem->onPdf == 1 &&
			$oItem->calculate == 1
		){
		
			$oVersion = $oItem->getVersion();
		                                     
			if($oItem->initalcost == 1){
				if($this->_oPriceIndexAtSchool instanceof self){
					$oCurrentModel	= $this->_oPriceIndexAtSchool;
				}else{
					$oCurrentModel = $this->_oPriceIndexAtSchool = self::getInstance();
				}				
				
			}else{	
				if($this->_oPriceIndexBeforeArrival instanceof self){
					$oCurrentModel	= $this->_oPriceIndexBeforeArrival;
				}else{
					$oCurrentModel = $this->_oPriceIndexBeforeArrival = self::getInstance();;
				}
			}

			$fAmountGross				= $oItem->amount;
			$fAmountNet					= $oItem->amount_net;
			$fAmountProvision			= $oItem->amount_provision;
			$fFactorDiscount			= $oItem->amount_discount;

			$fAmountDiscountGross		= $fAmountGross * $fFactorDiscount / 100;
			$fAmountDiscountNet			= $fAmountNet * $fFactorDiscount / 100;
			$fAmountDiscountProvision	= $fAmountProvision * $fFactorDiscount / 100;

			//wird in setItemValues gesetzt
			$fFactorTax					= (float)$oItem->tax;

			if($oVersion->tax == 2) {
				$fAmountVatGross = $fAmountGross * $fFactorTax / 100;
				$fAmountVatNet = $fAmountNet * $fFactorTax / 100;
				$fAmountVatProvision = $fAmountProvision * $fFactorTax / 100;

				// Discount abziehen
				$fAmountVatGross -= $fAmountDiscountGross * $fFactorTax / 100;
				$fAmountVatNet -= $fAmountDiscountNet * $fFactorTax / 100;
				$fAmountVatProvision -= $fAmountDiscountProvision * $fFactorTax / 100;
			} else {
				$fAmountVatGross = $fAmountGross - ($fAmountGross / (1 + $fFactorTax / 100));
				$fAmountVatNet = $fAmountNet - ($fAmountNet / (1 + $fFactorTax / 100));
				$fAmountVatProvision = $fAmountProvision - ($fAmountProvision / (1 + $fFactorTax / 100));

				// Discount abziehen
				$fAmountVatGross -= $fAmountDiscountGross - ($fAmountDiscountGross / (1 + $fFactorTax / 100));
				$fAmountVatNet -= $fAmountDiscountNet - ($fAmountDiscountNet / (1 + $fFactorTax / 100));
				$fAmountVatProvision -= $fAmountDiscountProvision - ($fAmountDiscountProvision / (1 + $fFactorTax / 100));
			}
			
			$oCurrentModel->_addBC('gross', $fAmountGross);
			$oCurrentModel->_addBC('net', $fAmountNet);
			$oCurrentModel->_addBC('provision', $fAmountProvision);
                                             
			$oCurrentModel->_addBC('discount_gross', $fAmountDiscountGross);
			$oCurrentModel->_addBC('discount_net', $fAmountDiscountNet);
			$oCurrentModel->_addBC('discount_provision', $fAmountDiscountProvision);

			// Immer mit Discount!
			$oCurrentModel->_addBC('vat_gross', $fAmountVatGross);
			$oCurrentModel->_addBC('vat_net', $fAmountVatNet);
			$oCurrentModel->_addBC('vat_provision', $fAmountVatProvision);
        
		}
		
	}
	
	public function savePrice($iVersionId){
		
		if($iVersionId <= 0){
			throw new Exception('Version ID is < 0');
		}
		
		$mValidateAtSchool = true;
		$mValidateBeforArrival = true;
		
		if($this->_oPriceIndexAtSchool instanceof self){
			
			$this->_oPriceIndexAtSchool->version_id			= (int)$iVersionId;
			$this->_oPriceIndexAtSchool->type				= 1;
			
			$mValidateAtSchool = $this->_oPriceIndexAtSchool->validate();
			
			if($mValidateAtSchool === true){
				$this->_oPriceIndexAtSchool->save();
			}
		}

		if(
			$mValidateAtSchool === true &&
			$this->_oPriceIndexBeforeArrival instanceof self
		){
			
			$this->_oPriceIndexBeforeArrival->version_id	= (int)$iVersionId;
			$this->_oPriceIndexBeforeArrival->type			= 0;
			
			$mValidateBeforArrival = $this->_oPriceIndexBeforeArrival->validate();
			
			if($mValidateBeforArrival === true){
				$this->_oPriceIndexBeforeArrival->save();
			}
		}

		if(
			$mValidateAtSchool !== true ||
			$mValidateBeforArrival !== true	
		){
			// Es ist ein Fehler aufgetreten
			$aError = array();

			if($mValidateAtSchool !== true ){
				$aError = array_merge($aError, $mValidateAtSchool);
			}
			
			if($mValidateBeforArrival !== true ){
				$aError = array_merge($aError, $mValidateBeforArrival);
			}
			
			return $aError;
		}else{
			// Es gab keinen Fehler beim Speichern
			return true;
		}
		
		

	}
	
	/**
	 *
	 * @return self 
	 */
	public function getPriceIndexBeforeArrival()
	{
		return $this->_oPriceIndexBeforeArrival;
	}
	
	/**
	 *
	 * @return self 
	 */
	public function getPriceIndexAtSchool()
	{
		return $this->_oPriceIndexAtSchool;
	}

	/**
	 * bc funktion zum summieren benutzen, da sonst bei sehr vielen nachkommastellen php komische zahlen berechnet
	 * 
	 * @param string $sType
	 * @param float $fAmount 
	 */
	protected function _addBC($sType, $fAmount) {
		
		$sVar = 'amount_' . $sType;

		$fTemp = bcadd($this->$sVar, $fAmount);

		$this->$sVar = $fTemp;

	}
	
}