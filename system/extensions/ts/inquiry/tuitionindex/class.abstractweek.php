<?php

abstract class Ext_TS_Inquiry_TuitionIndex_AbstractWeek {

	/** @var string */
	protected $sTable;

	/** @var DateTime */
	protected $dWeek;

	/** @var Ext_Thebing_Basic */
	protected $oEntity;

	/** @var int */
	protected $iState;

	/** @var string */
	protected $sFrom;

	/** @var string */
	protected $sUntil;

	/** @var int */
	protected $iCurrentWeek;

	/** @var int */
	protected $iTotalWeeks;

	/** @var int */
	protected $iTotalCourseWeeks;

	/** @var int */
	protected $iTotalCourseDuration;

	/**
	 * @param Ext_Thebing_Basic $oEntity
	 * @param DateTime $dWeek
	 */
	public function __construct(Ext_Thebing_Basic $oEntity, DateTime $dWeek) {
		$this->oEntity = $oEntity;
		$this->dWeek = $dWeek;
	}

	/**
	 * @param string $sFrom
	 */
	public function setFrom($sFrom) {
		$this->sFrom = $sFrom;
	}

	/**
	 * @param string $sUntil
	 */
	public function setUntil($sUntil) {
		$this->sUntil = $sUntil;
	}

	/**
	 * @param int $iCurrentWeek
	 */
	public function setCurrentWeek($iCurrentWeek) {
		$this->iCurrentWeek = $iCurrentWeek;
	}

	/**
	 * @param int $iTotalWeeks
	 */
	public function setTotalWeeks($iTotalWeeks) {
		$this->iTotalWeeks = $iTotalWeeks;
	}

	/**
	 * @param int $iTotalCourseWeeks
	 */
	public function setTotalCourseWeeks($iTotalCourseWeeks) {
		$this->iTotalCourseWeeks = $iTotalCourseWeeks;
	}

	/**
	 * @param int $iTotalCourseDuration
	 */
	public function setTotalCourseDuration($iTotalCourseDuration) {
		$this->iTotalCourseDuration = $iTotalCourseDuration;
	}

	/**
	 * @param int $iState
	 */
	public function setState($iState) {
		$this->iState = $iState;
	}

	/**
	 * Setzt ein Bit
	 *
	 * @param int $iBit
	 */
	public function setStateBit($iBit) {
		$this->iState |= $iBit;
	}

	/**
	 * Prüft, ob ein Bit gesetzt ist
	 *
	 * @param int $iBit
	 * @return bool
	 */
	public function checkStateBit($iBit) {
		return $this->iState & $iBit;
	}

	/**
	 * Aktualisiert den Status wenn in der Woche ein Kurs ist
	 */
	public function updateState() {

		$bContinuous = false;

		// Letzte Woche
		if($this->iCurrentWeek == $this->iTotalWeeks) {
			$this->setStateBit(Ext_TS_Inquiry_TuitionIndex::STATE_LAST);
			$bContinuous = true;
		}

		// Erste Woche
		if($this->iCurrentWeek == 1) {
			$this->setStateBit(Ext_TS_Inquiry_TuitionIndex::STATE_NEW);
			$bContinuous = true;
		}

		// Wenn nicht letzte und nicht erste Woche
		if($bContinuous !== true) {
			$this->setStateBit(Ext_TS_Inquiry_TuitionIndex::STATE_CONTINUOUS);
		}

	}

	/**
	 * Daten für DB-Insert
	 *
	 * @return array
	 */
	public function getSaveData() {

		if(!$this->oEntity->exist()) {
			throw new RuntimeException(sprintf('Entity does not exist [%s]!', $this->oEntity::class));
		}

		$aData = array(
			'week' => (string)$this->dWeek->format('Y-m-d'),
			'state' => (int)$this->iState,
			'from' => (string)$this->sFrom,
			'until' => (string)$this->sUntil,
			'current_week' => (int)$this->iCurrentWeek,
			'total_weeks' => (int)$this->iTotalWeeks,
			'total_course_weeks' => (int)$this->iTotalCourseWeeks,
			'total_course_duration' => (int)$this->iTotalCourseDuration
		);

		return $aData;

	}

	/**
	 * Speichern
	 */
	public function save() {

		// Ohne Woche nicht speichern!
		if($this->iCurrentWeek < 1) {
			return;
		}

		DB::insertData($this->sTable, $this->getSaveData(), true);

	}

}
