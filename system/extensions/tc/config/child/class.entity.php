<?php

use Illuminate\Support\Arr;

/**
 * Prototyp einer WDBasic, die ausnahmsweise mal ohne eigene DB-Tabelle funktioniert: Daten als JSON in system_config schreiben
 */
abstract class Ext_TC_Config_Child_Entity extends WDBasic {

	// Muss für GUI da sein (so viel zur Trennung zwischen Model und View)
	protected $_sTable = 'system_config';

	abstract public static function getConfigKey(): string;

	public function __construct($id = 0) {

		$this->_loadData($id);

	}

	public static function getInstance($id = 0) {

		return new static($id);

	}

	protected function _loadData($id) {

		if ($id > 0) {
			$entry = collect(static::findAll())->firstWhere('id', $id);
			if ($entry === null) {
				throw new RuntimeException('ID '.$id.' not found!');
			}
			$this->_aData = $entry->getData();
		}

	}

	public function validate($bThrowExceptions = false) {

		return true;

	}

	public function delete() {

		$data = $this->getConfigObject()->__get($this->getConfigKey());

		array_splice($data, $this->_aData['id'] - 1, 1);

		$this->getConfigObject()->__set($this->getConfigKey(), $data);

		return true;

	}

	public function save($bLog = true) {

		$data = $this->getConfigObject()->__get($this->getConfigKey());

		$keys = Arr::except(array_keys($this->_aData), ['id']);
		$row = Arr::only($this->getData(), $keys);

		if ($this->_aData['id'] > 0) {
			$data[$this->_aData['id'] - 1] = $row;
		} else {
			$data[] = $row;
		}

		$this->getConfigObject()->__set($this->getConfigKey(), $data);

		return true;

	}

	/**
	 * @return Ext_TC_Config
	 */
	protected static function getConfigObject() {

		return Factory::getObject('Ext_TC_Config');

	}

	/**
	 * @return static[]
	 */
	public static function findAll(): array {

		$data = (array)static::getConfigObject()->__get(static::getConfigKey());

		// IDs mithilfe des nummerischen Indexs faken; 0 funktioniert in GUI nicht
		return array_map(function ($key, $value) {
			$value['id'] = $key + 1;
			$obj = new static();
			$obj->_aData = $value;
			return $obj;
		}, array_keys($data), $data);

	}

}