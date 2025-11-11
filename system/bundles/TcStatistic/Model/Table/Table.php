<?php

namespace TcStatistic\Model\Table;

class Table extends \ArrayObject {

	/** @var string */
	private $sCaption = null;

	/**
	 * @inheritdoc
	 */
	public function offsetSet($mKey, $mValue) {

		if(!$mValue instanceof Row) {
			throw new \UnexpectedValueException('Element for Table object must be a Row object!');
		}

		parent::offsetSet($mKey, $mValue);

	}

	/**
	 * Setzt die Überschrift der Tabelle
	 *
	 * @param string $sCaption
	 */
	public function setCaption($sCaption) {
		$this->sCaption = $sCaption;
	}

	/**
	 * @return string|null
	 */
	public function getCaption() {
		return $this->sCaption;
	}

	/**
	 * @return bool
	 */
	public function hasCaption() {
		return !empty($this->sCaption);
	}

	/**
	 * Maximale Anzahl der Spalten dieser Tabelle
	 *
	 * @return int
	 */
	public function getMaxColCount() {

		$iCount = 0;
		foreach($this as $oRow) {
			// Wegen colspan muss jede Zeile iteriert werden
			$iCount = max($iCount, count($oRow));
		}

		return $iCount;

	}

}
