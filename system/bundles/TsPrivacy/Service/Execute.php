<?php

namespace TsPrivacy\Service;

use TsPrivacy\Entity\Depuration;
use TsPrivacy\Interfaces\Entity;

class Execute {

	/**
	 * @var \Monolog\Logger
	 */
	private $oLogger;

	/**
	 * @var EntityCheck
	 */
	private $oEntityChecker;

	/**
	 * @var Notification
	 */
	private $oNotifier;

	public function __construct() {
		$this->oLogger = \Log::getLogger('privacy');
		$this->oEntityChecker = new EntityCheck();
		$this->oNotifier = new Notification();
	}

	/**
	 * Executor: Einträge aus Tabelle abarbeiten
	 */
	public function execute() {

		\DB::begin(__METHOD__);

		$iStartTime = microtime(true);

		$aEntityClasses = $this->oEntityChecker->getEntityClasses();
		$oRepository = Depuration::getRepository();

		// Maximal 1000 Einträge, da sonst Speicher überläuft
		$aResult = $oRepository->getEntries(new \DateTime());

		$iCounter = 0;
		foreach($aResult as $aRow) {

			// Konkreten Eintrag nochmal überprüfen
			$aEntityResult = $this->oEntityChecker->checkEntity($aRow['entity'], $aRow['entity_id']);
			if(!empty($aEntityResult)) {

				if(!isset($aEntityClasses[$aRow['entity']])) {
					throw new \RuntimeException($aRow['entity'].' is not mapped!');
				}

				/** @var Entity $oEntity */
				$oEntity = \Factory::getInstance($aRow['entity'], $aRow['entity_id']);

				if(!$oEntity instanceof Entity) {
					throw new \RuntimeException($aRow['entity'].' does not implement interface!');
				}

				$bAnonymize = false;
				$sLogAction = 'DELETED';
				if($oEntity::getPurgeSettings()['action'] === 'anonymize') {
					$bAnonymize = true;
					$sLogAction = 'ANONYMIZED';
				}

				try {
					$oEntity->purge($bAnonymize);
					$this->oLogger->addInfo('Executer: '.$sLogAction.' '.$aRow['entity'].':'.$aRow['entity_id'], [$aRow, $aEntityResult]);
				} catch(\Exception $e) {
					$this->oLogger->addInfo('Executer: FAILED '.$sLogAction.' '.$aRow['entity'].':'.$aRow['entity_id'], [$e->getMessage(), $e->getTraceAsString(), $aRow, $aEntityResult]);
				}

			} else {
				// Löschbedingung trifft nicht mehr zu, daher überspringen
				$this->oLogger->addInfo('Executer: Skipped '.$aRow['entity'].':'.$aRow['entity_id'], [$aRow, $aEntityResult]);
			}

			$oRepository->deleteEntry($aRow['id']);

			if($iCounter % 100 == 0) {
				\WDBasic::clearAllInstances();
			}

			$iCounter++;

		}

		\DB::commit(__METHOD__);

		// Benötigt um die Einträge aus dem Index zu löschen
		//\Ext_Gui2_Index_Stack::executeCache();
		\Ext_Gui2_Index_Stack::save(true);

		$this->oLogger->addInfo('Executer: Memory usage: '.memory_get_peak_usage(true).' b');
		$this->oLogger->addInfo('Executer: Execution time: '.(microtime(true) - $iStartTime));

	}

	/**
	 * Preparer: Tabelle füllen
	 */
	public function prepare() {

		\DB::begin(__METHOD__);

		$oRepository = Depuration::getRepository();

		$aEntities = [];
		foreach(array_keys($this->oEntityChecker->getEntityClasses()) as $sEntity) {
			$aResult = $this->oEntityChecker->checkEntity($sEntity, null, true);
			$aEntities[$sEntity] = $aResult;
		}

		$dDate = new \DateTime();
		$dDate->add(new \DateInterval('P1W'));
		$sDeletionDate = $dDate->format('Y-m-d');

		$iCountTotal = 0;
		foreach($aEntities as $sEntity => $aEntries) {

			$iCount = 0;
			foreach($aEntries as $aEntry) {

				$oRepository->insertEntry($sEntity, $aEntry['id'], $sDeletionDate);

				$this->oLogger->addInfo('Preparer: Added '.$sEntity.'::'.$aEntry['id'], $aEntry);

				$iCount++;
				$iCountTotal++;

			}

			$this->oLogger->addInfo('Preparer: '.$iCount.' entities of '.$sEntity);

		}

		$this->oLogger->addInfo('Preparer: Inserted '.$iCountTotal.' entities');

		try {

			$bSuccess = true;
			if($iCountTotal > 0) {
				$bSuccess = $this->oNotifier->send($aEntities);
			}

			if($bSuccess) {
				\DB::commit(__METHOD__);
			} else {
				// Wenn E-Mail nicht verschickt werden konnte soll nichts passieren
				\DB::rollback(__METHOD__);
			}

		} catch(\Exception $e) {

			\DB::rollback(__METHOD__);

			throw $e;

		}

	}

}
