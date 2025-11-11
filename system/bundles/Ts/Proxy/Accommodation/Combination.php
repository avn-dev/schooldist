<?php

namespace Ts\Proxy\Accommodation;

class Combination extends \Ts\Proxy\AbstractProxy {
	
	/**
	 * Primäre Entität
	 * @var string
	 */
	protected $sEntityClass = 'Ext_Thebing_Accommodation_Category';

	protected $sLabel;
	protected $oRoomtype;
	protected $oMeal;
	protected $sKey;

	
	public function setKey($sKey) {
		$this->sKey = $sKey;
	}
	
	public function getKey() {
		return $this->sKey;
	}
	
	public function setLabel($sLabel) {
		$this->sLabel = $sLabel;
	}
	
	public function getLabel() {
		return $this->sLabel;
	}
	
	public function setRoomtype(\Ext_Thebing_Accommodation_Roomtype $oRoomtype) {
		$this->oRoomtype = $oRoomtype;
	}
	
	public function setMeal(\Ext_Thebing_Accommodation_Meal $oMeal) {
		$this->oMeal = $oMeal;
	}
	
	public function getRoomtype() {
		return new Roomtype($this->oRoomtype);
	}
	
	public function getMeal() {
		return new Meal($this->oMeal);
	}

	public function getPrice($oSchool, \Ts\Proxy\Season $oSeason, $iWeek) {

		$oSchool = \Ext_Thebing_School::getInstance($oSchool->getProperty('id'));
		
		$oInquiry = new \Ext_TS_Inquiry();
		
		$oJourney = $oInquiry->getJoinedObjectChild('journeys');
		$oJourney->school_id = $oSchool->getId();
		$oJourney->productline_id = $oSchool->getProductLineId();

		$oInquiry->currency_id = $oSchool->currency;

		$oAmount = new \Ext_Thebing_Inquiry_Amount($oInquiry);

		$oInquiryAccommodation = $oJourney->getJoinedObjectChild('accommodations');
		$oInquiryAccommodation->accommodation_id = $this->oEntity->id;
		$oInquiryAccommodation->roomtype_id = $this->oRoomtype->id;
		$oInquiryAccommodation->meal_id = $this->oMeal->id;
		$oInquiryAccommodation->from = $oSeason->getProperty('valid_from');
		$oInquiryAccommodation->until = $oSeason->getProperty('valid_until');
		$oInquiryAccommodation->weeks = $iWeek;

		$iAmount = $oAmount->calculateAccommodationAmount($oInquiryAccommodation);

		return $iAmount;
	}

}