<?php

namespace Ts\Handler\AccommodationProvider;

use Core\Helper\DateTime;
use Ts\Gui2\AccommodationProvider\PaymentData;
use Ts\Entity\AccommodationProvider\Payment;
use Ts\Helper\Accommodation\AllocationCombination;

class PaymentHandler {

	const IGNORE_ALLOCATIONS_OLDER_THAN_MONTHS = 12;

	/**
	 * @var array
	 */
	protected $aReport = array();
	
	/**
	 * @var \Ext_Thebing_School
	 */
	protected $oSchool;

	/**
	 * @TODO Wieder entfernen
	 *
	 * @var bool
	 */
	public $bCheckBackup = true;
	
	public function __construct(\Ext_Thebing_School $oSchool) {
		$this->oSchool = $oSchool;
	}

	/**
	 * @return array
	 */
	public function getReport() {
		return $this->aReport;
	}
	
	/**
	 * Aktuelle Einträge werden entferne und verknüpfte Zuweisungen zurückgesetzt
	 * @param \Ext_Thebing_School $oSchool
	 * @return boolean
	 */
	public function resetPendingPayments() {

		$bLock = $this->lock();
		
		if($bLock === false) {
			throw new LockException('Lock PaymentHandler::resetPendingPayments');
		}
		
		$bSuccess = false;
		
		try {

			PaymentData::setSchool($this->oSchool);

			$oGui2Factory = new \Ext_Gui2_Factory('ts_accommodation_provider_payments');
			$oGui2 = $oGui2Factory->createGui();
			$oGui2->sView = 'single';

			$oGui2Data = $oGui2->getDataObject();

			$aData = $oGui2Data->getTableQueryData(array(), array(), array(), true);


			if(!empty($aData['data'])) {

				$this->aReport['reset']['current_payments'] = count($aData['data']);

				foreach($aData['data'] as $aPayment) {
					$oPayment = Payment::getInstance($aPayment['id']);
					$oPayment->delete();

				}

			}
			$bSuccess = true;
			
		} catch(\Exception $e) {
			// @todo Fehlerbehandlung
			__pout($e);
		}

		$this->unlock();
		
		return $bSuccess;
	}

	public function generate() {

		$bLock = $this->lock();

		if($bLock === false) {
			throw new LockException('Lock PaymentHandler::generate');
		}

		\DB::begin('PaymentHandler::generate');

//		$bSuccess = false;
//
//		try {

			// Zuweisungen ab dem Datum, welches vor X Monaten war, berücksichtigen
			$oAllocationsFrom = new DateTime();
			$oAllocationsFrom->modify('-'.self::IGNORE_ALLOCATIONS_OLDER_THAN_MONTHS.' month');

			/* @var $oAllocationRepo \Ext_Thebing_Accommodation_AllocationRepository */
			$oAllocationRepo = \Ext_Thebing_Accommodation_Allocation::getRepository();
			$aAccommodationAllocations = $oAllocationRepo->getNotCompletePayedAllocations($this->oSchool, $oAllocationsFrom);

			$this->aReport['generate']['allocations_found'] = count($aAccommodationAllocations);

			$aSkipAllocationIds = [];
			
			foreach($aAccommodationAllocations as $iKey => $oAccommodationAllocation) {

				// In Kombinationen enthaltene IDs überspringen
				if(in_array($oAccommodationAllocation->id, $aSkipAllocationIds)) {
					continue;
				}

				$oAccommodationAllocationCombination = new AllocationCombination($oAccommodationAllocation);

				$aSkipAllocationIds += $oAccommodationAllocationCombination->getAllocationIds();

				$oPaymentGenerator = new \Ts\Generator\AccommodationProvider\PaymentGenerator($oAccommodationAllocationCombination);

				try {

					$mRun = $oPaymentGenerator->run();

					if($mRun === null) {
						$this->aReport['generate']['skipped'][] = $oPaymentGenerator->getMessages();
					} elseif($mRun === false) {
						$this->aReport['generate']['error'][] = $oPaymentGenerator->getMessages();
					} else {
						$this->aReport['generate']['entry'] += $mRun;
					}

				} catch (\Throwable $e) {

					$aMessage = $oPaymentGenerator->getAllocationDescription();
					$aMessage['message'] = 'Error: '.$e->getMessage();
					$this->aReport['generate']['error'][] = [$aMessage];

				}

			}

//			$bSuccess = true;
			
//		} catch(\Exception $e) {
//			// @todo Fehlerbehandlung
//			__pout($e);
//
//			\DB::rollback('PaymentHandler::generate');
//
//		}

		\DB::commit('PaymentHandler::generate');
		
		$this->unlock();

//		return $bSuccess;
	}
	
	/**
	 * @return string
	 */
	protected function getLockKey() {
		
		$sKey = 'PaymentHandler_Lock_'.$this->oSchool->id;
		
		return $sKey;
	}
	
	/**
	 * @return boolean
	 */
	protected function lock() {

		// Kein Lock wenn Debugmode aktiv ist
		if(\System::d('debugmode') == 2) {
			return true;
		}
		
		$iReturn = \WDCache::set($this->getLockKey(), 5*60, 1);
		
		// Ein Replace bedeutet, dass es schon ein Lock gab
		if($iReturn === \WDCache::REPLACED) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * 
	 */
	protected function unlock() {
		\WDCache::delete($this->getLockKey());
	}
	
}

class LockException extends \RuntimeException {
	
}