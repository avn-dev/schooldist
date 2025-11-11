<?php

/**
 * @TODO Wird das hier noch benutzt?
 */
class Ext_Thebing_Agency_Materialorder extends Ext_Thebing_Basic {
	
	protected $_aItems = false;

	// Tabellenname
	protected $_sTable = 'kolumbus_material_orders_orders';

	public function getItems() {
		
		
	}
	
	public function getAgency() {
		
		$oAgency = Ext_Thebing_Agency::getInstance($this->agency_id);
		
		return $oAgency;
		
	}
	
	public function getCoverLetter() {
		
		$aData = array();
		$aData['txt_intro'] = $this->txt_intro;
		$aData['txt_subject'] = $this->txt_subject;
		$aData['txt_address'] = $this->txt_address;
		$aData['txt_signature'] = $this->txt_signature;
		$aData['txt_pdf'] = $this->txt_pdf;
		$aData['signature'] = $this->signature;
		
		return $aData;
		
	}
	
	public function getAddress() {

		$oAddress = new Ext_Thebing_Agency_Address($this->address_id);
		$sAddress = $oAddress->getAddressString();
		
		return $sAddress;

	}
	
	public function save($bLog = true) {
		
		$mReturn = parent::save();
		
		if($this->_aItems !== false) {
		
			$sSql = "
					DELETE FROM
						kolumbus_material_orders_orders_items
					WHERE
						`order_id` = :order_id
					";
			$aSql = array('order_id'=>$this->id);
			DB::executePreparedQuery($sSql, $aSql);
			
			foreach((array)$this->_aItems as $iMaterialId=>$iAmount) {
				
				$sSql = "
						INSERT INTO
							kolumbus_material_orders_orders_items
						SET
							`order_id` = :order_id,
							`item_id` = :item_id,
							`amount` = :amount
						";
				$aSql = array('order_id'=>$this->id);
				$aSql['item_id'] = (int)$iMaterialId;
				$aSql['amount'] = (int)$iAmount;
				DB::executePreparedQuery($sSql, $aSql);
				
			}
			
		}
		
	}
	
	public function setMaterialAmount($iMaterialId, $iAmount) {
		$this->_aItems[$iMaterialId] = (int)$iAmount;
	}

	public function getMaterialAmount($iMaterialId) {

		if($this->_aItems === false) {
			
			$sSql = "
					SELECT 
						*
					FROM
						kolumbus_material_orders_orders_items
					WHERE
						`order_id` = :order_id
					";
			$aSql = array('order_id'=>$this->id);
			
			$aItems = DB::getPreparedQueryData($sSql, $aSql);

			foreach((array)$aItems as $aItem) {
				$this->_aItems[$aItem['item_id']] = $aItem['amount']; 
			}
			
		}
		
		$iAmount = $this->_aItems[$iMaterialId];

		return $iAmount;

	}
	
	public function getMaterialString() {
		$mItems = array();
		$oSchool = Ext_Thebing_School::getInstance($this->school_id);
		$aMaterials = $oSchool->getMaterialOrderItems(1);
		
		foreach((array)$aMaterials as $iMaterialId=>$sMaterial) {
			$iAmount = $this->getMaterialAmount($iMaterialId);
			if($iAmount > 0) {
				$mItems[] = (int)$iAmount.' x '.$sMaterial;
			}
		}
		$mItems = implode(", ", $mItems);
		return $mItems;
	}
	
}