<?php

use Illuminate\Support\Arr;

/**
 * Neue Filter für Sidebar aus YML mit Filtersets: Ersatz für Ext_TC_Gui2_Filterset_Bar_Element
 *
 * GUIs ohne Filtersets definieren ihre Filter jeweils direkt (YML + HTML)!
 */
class Ext_Gui2_Factory_Filter {

	private Ext_Gui2 $gui;

	private array $column;

	private string $type;

	public function __construct(Ext_Gui2 $gui, array $column) {
		$this->gui = $gui;
		$this->column = $column;
		$this->type = $column['filterset']['type'];
	}

	public function create(): Ext_Gui2_Bar_Filter_Abstract {

		if (
			$this->type === 'input' ||
			$this->type === 'select'
		) {
			return $this->createInput();
		} elseif ($this->type === 'date') {
			return $this->createTimefilter();
		}

		throw new BadMethodCallException('Unknown filter type: '.$this->type);

	}

	/**
	 * @see \Ext_Gui2::setFilter()
	 */
	private function createInput() {

		$filter = new Ext_Gui2_Bar_Filter($this->type);
		$filter->id = $this->buildColumnKey();
		$filter->label = $this->buildLabel($this->column);
		$filter->db_column = !empty($this->column['select_column']) ? $this->column['select_column'] : $this->buildColumnKey();
		$filter->db_alias = $this->column['alias'];
		$filter->value = Arr::get($this->column, 'filterset.default', '');

		if ($this->type === 'select') {
			$filter->db_operator = '='; // Analog zu Ext_TC_Gui2_Filterset_Bar_Element::setGuiFilterElement()
			$filter->select_options = ['' => ''] + Ext_Gui2_Config_Parser::callMethod($this->column['filterset']['options'], [$this->gui]);

			// Bei boolschen Werten macht weder Mehrfachauswahl noch »ist/ist nicht« Sinn
			if (Arr::get($this->column, 'index.mapping.type') === 'boolean') {
				$filter->multiple = false;
				$filter->negateable = false;
			}
		}

		if (!empty($this->column['filterset']['query'])) {
			// TODO fehlt hier noch etwas?
			$filter->filter_query = $this->column['filterset']['query'];
			//throw new BadMethodCallException('Not implemented: filterset.query');
		}

		if (!empty($this->column['filterset']['wdsearch'])) {
			$filter->filter_wdsearch = array_map(fn(array $callPath) => Ext_Gui2_Config_Parser::callMethod($callPath), $this->column['filterset']['wdsearch']);
		}

		return $filter;

	}

	private function createTimefilter() {

		$format = Factory::getObject(Ext_TC_Gui2_Format_Date::class);
		$filter = new Ext_Gui2_Bar_Timefilter($format);
		$filter->id = $this->buildColumnKey();
		$filter->label = $this->buildLabel($this->column);

		$filter->db_from_column = Arr::get($this->column, 'filterset.from_column', $this->buildColumnKey());
		$filter->db_from_alias = $this->column['alias'];
		$filter->db_until_column = Arr::get($this->column, 'filterset.until_column', $this->buildColumnKey());
		$filter->db_until_alias = $this->column['alias'];
		$filter->search_type = Arr::get($this->column, 'filterset.search_type', 'between');

		if (!empty($this->column['filterset']['skip_query'])) {
			throw new BadMethodCallException('Not implemented: filterset.skip_query');
		}

		$fromDefault = $filter->formatSaveValueDate(Arr::get($this->column, 'filterset.default.from'));
		if ($fromDefault) {
			$filter->default_from = $fromDefault;
		}

		$untilDefault = $filter->formatSaveValueDate(Arr::get($this->column, 'filterset.default.until'));
		if ($untilDefault) {
			$filter->default_until = $untilDefault;
		}

		// Neue Filter nutzen nur noch normalisiert value, alte Filter den anderen Kram
		$filter->value = [$filter->default_from, $filter->default_until];

		return $filter;

	}

	private function buildLabel(array $column): string {

		if (!empty($column['filterset']['title'])) {
			$label = $column['filterset']['title'];
		} else {
			$label = $column['title'];
		}

		if ($label instanceof Closure) {
			$label = $label();
		} elseif (is_array($label)) {
			$label = Ext_Gui2_Config_Parser::callMethod($label);
		} else {
			$label = $this->gui->t($label);
		}

		if (empty($label)) {
			throw new \RuntimeException('No label for filter: '.$this->buildColumnKey());
		}

		return $label;

	}

	private function buildColumnKey(): string {

		if (!empty($this->column['index']['add_original'])) {
			if (str_contains($this->column['_column'], '_original')) {
				// Früherer Workaround in Ext_TC_Gui2_Filterset_Bar_Element::_createColumnKey()
				throw new DomainException('column has add_original and _orignal in column key: '.$this->column['_column']);
			}
			return $this->column['_column'].'_original';
		}

		return $this->column['_column'];

	}

}