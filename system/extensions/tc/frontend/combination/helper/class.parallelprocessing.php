<?php

use Core\Entity\ParallelProcessing\Stack;

class Ext_TC_Frontend_Combination_Helper_ParallelProcessing {

    /**
     * @param Ext_TC_Frontend_Combination $oCombination
     * @throws Exception
     */
    public function addToStack(Ext_TC_Frontend_Combination $oCombination) {

        if(
            $oCombination->id > 0 &&
//			$oCombination->canLockStatus() &&
            $oCombination->active == 1
        ) {            
			// Achtung! Das hier muss passieren bevor der Eintrag für den Stack geschrieben wird. Ansonsten könnte es passieren, dass
			// die Kombination überschrieben wird, je nach dem wie viele Daten bei der Kommbination eingestellt sind und je nach dem was beim Abarbeiten des Eintrages
			// dieser Kombination passiert. Bei der Navigation kam jetzt der Fall auf, dass das PP schon gestartet hatte, obwohl die Kombination 
			// noch nicht vollständig gespeichert war.
			$oCombination->updateState('pending');
			
            $oRepository = Stack::getRepository();
			/* @var $oRepository \Core\Entity\ParallelProcessing\StackRepository */
            $oRepository->writeToStack('tc/combination-initialize', array('combination_id' => $oCombination->id), 10);			
        }

    }
	
	/**
	 * Fügt alle Kombinationen in den Stack vom Parallelprocessing
	 */
	public function updateAll() {
		$this->update();		
	}

	/**
	 * Fügt alle Kombinationen bestimmter Typen (Preislisten, Anmeldeformular, ...) in den Stack vom Parallelprocessing
	 * 
	 * @param array $aUsages
	 * @throws RuntimeException
	 */
	public function updateByUsage(array $aUsages) {
		
		if(empty($aUsages)) {
			throw new RuntimeException('No usages defined! Cannot update combinations by usages!');
		}
		
		$this->update($aUsages);
	}
	
	/**
	 * Fügt Kombinationen in den Stack vom ParallelProcessing
	 * 
	 * @param array $aUsages
	 */
	private function update(array $aUsages = array()) {
		$aCombinations = $this->search($aUsages);

		foreach($aCombinations as $oCombination) {
			$this->addToStack($oCombination);
		}
	}
	
	/**
	 * Sucht Kombinationen die sich aktuell nicht im Parallelprocessing befinden (optional Begrenzung auf
	 * bestimmte Typen)
	 * 
	 * @param array $aUsages
	 * @return Ext_TC_Frontend_Combination[]
	 */
	private function search(array $aUsages = array()) {
		$oRepository = Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Combination', 'getRepository');
		/** @var $oRepository WDBasic_Repository */
		$aCriteria = array('status' => Ext_TC_Frontend_Combination::STATUS_READY);
		
		if(!empty($aUsages)) {
			$aCriteria['usage'] = $aUsages;
		}
		
		$aCombinations = $oRepository->findBy($aCriteria);
		
		return $aCombinations;
	}
	
}