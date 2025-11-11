<?php

namespace Ts\Helper\Accommodation;

use Ext_Thebing_Accommodation_Allocation as Allocation;
use Core\Helper\DateTime;

class AllocationCombination extends \SplObjectStorage {
	
	/**
	 * @var \Ext_Thebing_Accommodation_Allocation 
	 */
	private $oMasterAllocation;
	
	/**
	 * @var DateTime
	 */
	private $dFrom;
	
	/**
	 * @var DateTime
	 */
	private $dUntil;
	
	public function __construct(Allocation $oAllocation) {
		
		$this->oMasterAllocation = $oAllocation;

		$this->dFrom = new DateTime($oAllocation->from);
		$this->dUntil = new DateTime($oAllocation->until);
		
		$this->attach($oAllocation);
		
		$this->attachAdjacentAllocations();
		
	}

	/**
	 * @return \Ext_Thebing_Accommodation_Allocation 
	 */
	public function getMasterAllocation() {
		return $this->oMasterAllocation;
	}
	
	public function __call($sName, $aArguments) {

		$mReturn = call_user_func_array(array($this->oMasterAllocation, $sName), $aArguments);
		
		return $mReturn;
	}
	
	public function __get($sName) {

		switch($sName) {
			case 'from':
				$mReturn = $this->dFrom->format('Y-m-d');
				break;
			case 'until':
				$mReturn = $this->dUntil->format('Y-m-d');
				break;
			default:
				$mReturn = $this->oMasterAllocation->$sName;
		}

		return $mReturn;
	}
	
	public function detach($object) {

		parent::detach($object);
		
		$this->oMasterAllocation = reset($this);
		
	}
	
	/**
	 * Fügt angrenzende Zuweisungen dieser Buchung hinzu, die im selben Bett sind
	 */
	private function attachAdjacentAllocations() {

		$this->rewind();
		
		/* @var $oFirstAllocation \Ext_Thebing_Accommodation_Allocation */
		$oFirstAllocation = $this->oMasterAllocation;

		$oDb = $oFirstAllocation->getDbConnection();
		$oAllocationRepository = new \Ext_Thebing_Accommodation_AllocationRepository($oDb, $oFirstAllocation);

		$aAllocations = $oAllocationRepository->getAdjacentAllocations();

		if(empty($aAllocations)) {
			return;
		}
		
		$aAllocations[] = $oFirstAllocation;

		usort($aAllocations, function($oA, $oB) {
			
			$oDateA = new \Core\Helper\DateTime($oA->until);
			$oDateB = new \Core\Helper\DateTime($oB->from);
			
			if($oDateA <= $oDateB) {
				return -1;
			} else {
				return 1;
			}

		});
		
		$aAllocationGroups = $this->groupByUninterruptedPeriod($aAllocations);

		// Gruppe ermitteln, in der die Masterzuweisung enthalten ist
		foreach($aAllocationGroups as $aAllocationGroup) {
			foreach($aAllocationGroup as $oAllocation) {
				if($oAllocation === $oFirstAllocation) {
					break 2;
				}
			}
		}

		foreach($aAllocationGroup as $oAllocation) {
			if($oAllocation !== $oFirstAllocation) {

				$this->attach($oAllocation);

				$this->dFrom = min(new DateTime($oAllocation->from), $this->dFrom);
				$this->dUntil = max(new DateTime($oAllocation->until), $this->dUntil);
				
			}
		}

	}

	/**
	 * Zuweisungen auf Unterbrechnungen prüfen, ununterbrochenene Zeiträume gruppieren
	 * @param array $aAllocations
	 * @return array
	 */
	private function groupByUninterruptedPeriod($aAllocations) {

		$aAllocationGroups = [];
		$iCurrentAllocationGroup = 0;
		$oLatestEndDate = null;
		foreach($aAllocations as $oAllocation) {
			
			$oFrom = new DateTime($oAllocation->from);
			$oUntil = new DateTime($oAllocation->until);

			if(
				$oLatestEndDate !== null &&
				$oLatestEndDate != $oFrom
			) {
				$iCurrentAllocationGroup++;
			}
			
			$aAllocationGroups[$iCurrentAllocationGroup][] = $oAllocation;

			$oLatestEndDate = clone $oUntil;
		
		}
		
		return $aAllocationGroups;
	}
	
	/**
	 * Gibt die IDs aller enthaltenen Zuweisungen zurück
	 * @return array
	 */
	public function getAllocationIds() {
		
		$aIds = [];
		
		foreach($this as $oAllocation) {
			$aIds[] = (int)$oAllocation->id;
		}
		
		return $aIds;
	}

	/**
	 * @return string
	 */
	public function getAllocationIdsAsString() {
		
		$sAllocationIds = implode('_', $this->getAllocationIds());

		return $sAllocationIds;
	}
	
	public static function getFromAllocationIds($aAllocationIds) {
		
		$iAllocationId = array_shift($aAllocationIds);
		
		$oAllocation = Allocation::getInstance($iAllocationId);
		
		$oAllocationCombination = new self($oAllocation);
		
		if(!empty($aAllocationIds)) {
			foreach($aAllocationIds as $iAllocationId) {
				$oAllocationCombination->attach(Allocation::getInstance($iAllocationId));
			}
		}
		
	}

	public function setPaymentCompleted($bNoValidation=false) {

		foreach($this as $oAllocation) {
			$oAllocation->setPaymentCompleted($bNoValidation);
		}

	}
	
}