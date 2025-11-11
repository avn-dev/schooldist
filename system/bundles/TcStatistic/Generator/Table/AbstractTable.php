<?php

namespace TcStatistic\Generator\Table;

use TcStatistic\Model\Table;

abstract class AbstractTable {

	/**
	 * @var Table\Table|Table\Table[]
	 */
	protected $aTables;

	abstract public function generate();

	abstract public function render();

	/**
	 * @param Table\Table|Table\Table[] $mData
	 */
	public function __construct($mData) {

		if($mData instanceof Table\Table) {
			$this->aTables = [$mData];
		} else {
			$this->aTables = $mData;
		}

		$this->checkData();

	}

	/**
	 * Warnings verhindern, indem übergebene Daten der Tabelle verifiziert werden
	 */
	protected function checkData() {

		foreach($this->aTables as $iKey => $oTable) {
			if(!$oTable instanceof Table\Table) {
				throw new \UnexpectedValueException('Given array doesn\'t contain a valid table object (index '.$iKey.')');
			}
		}

	}

}
