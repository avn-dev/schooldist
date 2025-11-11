<?php

namespace Tc\Gui2\Format\Contact;

class Details extends \Ext_Gui2_View_Format_Abstract {

	private $types;

	public function __construct(...$types) {
		$this->types = $types;
	}

	public function format($value, &$column = null, &$resultData = null) {

		if (!empty($value)) {
			$final = array_column($this->convertToArray($value), 'value');
			return implode(', ', $final);
		}

		return $value;
	}

	public function getTitle(&$column = null, &$resultData = null) {

		$value = $resultData[$column->select_column] ?? "";

		if (empty($value)) {
			return null;
		}

		$values = $this->convertToArray($value);

		$labels = \Ext_TC_Contact_Detail::getTypes();

		$final = [];
		foreach($values as $detail) {
			if (!isset($labels[$detail['type']])) {
				continue;
			}

			$final[] = sprintf('<b>%s</b>: %s', $labels[$detail['type']], $detail['value']);
		}

		return [
			'tooltip' => true,
			'content' => implode('<br/>', $final)
		];

	}

	private function convertToArray(string $selectData): array {

		$rows = explode('{||}', $selectData);
		$final = [];

		foreach ($rows as $row) {

			[$type, $value] = explode('{|}', $row);

			if (
				empty($this->types) ||
				in_array($type, $this->types)
			) {
				$final[] = ['type' => $type, 'value' => $value];
			}
		}

		return $final;
	}

}
