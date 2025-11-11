<?php

namespace Gui2\Entity;

use Illuminate\Support\Arr;

/**
 * @property string|int $id
 * @property string $created
 * @property string $changed
 * @property string|int $active
 * @property string $creator_id
 * @property string $editor_id
 * @property string $gui_hash
 * @property string $name
 * @property string $dependency
 * @property string $visibility
 * @property array $filters
 * @property ?array $default_per_user
 */
class FilterQuery extends \WDBasic implements \JsonSerializable {

	const REQUEST_PARAM_ID = 'filter_query_id';

	protected $_sTable = 'gui2_filter_queries';

	protected $_aJoinTables = [
		'filters' => [
			'table' => 'gui2_filter_queries_filters',
			'primary_key_field' => 'filter_query_id',
			'foreign_key_fields' => ['filter', 'type', 'negate', 'value']
		]
	];

	protected $_aAttributes = [
		'default_per_user' => ['type' => 'array']
	];

	protected $_aFormat = [
		'gui_hash' => [
			'required' => true
		],
		'name' => [
			'required' => true
		]
	];

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'name' => $this->name,
			'visibility' => $this->visibility
		];
	}

	/*public function validate($bThrowExceptions = false) {

		if (!$this->exist()) {
			$count = self::query()->where('gui_hash', $this->gui_hash)->count();
			if ($count > 25) { // TODO
				return ['TOO_MANY'];
			}
		}

		return parent::validate($bThrowExceptions);

	}*/

	public function setFilterValue(\Ext_Gui2_Bar_Filter_Abstract $filter, $value, bool $negated) {

		$data = [
			'filter' => $filter->id,
			'type' => $filter->filter_type,
			'negated' => (int)$negated,
			'value' => $filter->convertSaveValue($value)
		];

		$this->filters = array_merge($this->filters, [$data]);

	}

	public function prepareDefaultFilterValue(\Ext_Gui2_Bar_Filter_Abstract $filter, &$value, bool &$negated) {

		$data = Arr::first($this->filters, fn(array $data) => $data['filter'] === $filter->id);

		if (!$data) {
			$value = $filter->initial_value;
			$negated = false;
			return;
		}

		$value = $filter->prepareSaveValue($data['value']);

		if (
			$filter->negateable &&
			$data['negated']
		) {
			$negated = true;
		}

	}

}
