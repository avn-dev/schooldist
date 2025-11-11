<?php

namespace TcStatistic\Model\Table;

class Row extends \ArrayObject {

	/** @var string */
	private $sRowSet = 'body';

	/**
	 * @inheritdoc
	 */
	public function offsetSet($mKey, $mValue) {

		if(!$mValue instanceof Cell) {
			throw new \UnexpectedValueException('Element for Row object must be a Cell object!');
		}

		parent::offsetSet($mKey, $mValue);

	}

	/**
	 * array_unshift
	 *
	 * @param Cell $oCell
	 */
	public function prepend(Cell $oCell) {

		$aArray = (array)$this;
		array_unshift($aArray, $oCell);
		$this->exchangeArray($aArray);

	}

	/**
	 * Setzt das Row-Set für diese Row (thead, tbody, tfoot)
	 *
	 * @param string $sSet
	 */
	public function setRowSet($sSet) {

		if(!in_array($sSet, ['head', 'body', 'foot'])) {
			throw new \InvalidArgumentException('Invalid section: '.$sSet);
		}

		$this->sRowSet = $sSet;

	}

	/**
	 * @return string|null
	 */
	public function getRowSet() {
		return $this->sRowSet;
	}

}
