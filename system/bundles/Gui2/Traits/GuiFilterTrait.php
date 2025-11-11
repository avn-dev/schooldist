<?php

namespace Gui2\Traits;

use Illuminate\Support\Collection;
use Gui2\Entity\FilterQuery;
use Illuminate\Support\Arr;

/**
 * TODO macht es keine Probleme dass hier überall TC-Klassen verwendet werden?
 * @see \Ext_Gui2_Data::_setFilterElementDataByRef()
 * @see \Ext_Gui2_Data::requestSaveFilterQuery()
 */
trait GuiFilterTrait {

	/**
	 * @var \Ext_Gui2_Bar_Filter_Abstract[]
	 */
	protected array $filters = [];

	private FilterQuery $filterQuery;

	private Collection $filterQueries;

	private bool $filterBarAdded = false;

	/**
	 * Neuen Filter (Sidebar) setzen und konfigurieren
	 *
	 * @see prepareFilters()
	 */
	public function setFilter(\Ext_Gui2_Bar_Filter_Abstract $filter) {

		$filter->sidebar = true;

		if (
			$filter instanceof \Ext_Gui2_Bar_Filter &&
			$filter->filter_type === 'select'
		) {

			// Filter, die nicht standardmäßig funktionieren, sind in der Sidebar simpel, d.h. z.B. kein ist/ist nicht und nicht multiple
			if (
				//!empty($filter->filter_query) ||
				!empty($filter->filter_wdsearch) ||
				$filter->db_operator !== '='
			) {
				$filter->simple = true;
			}

			// Filter ist negierbar?
			if (
				!$filter->simple &&
				$filter->negateable === null
			) {
				$filter->negateable = true;
			}

			// Versuchen, das Label zu extrahieren, da dieses bei alten Filtern nicht gesetzt wird (Hack: Immer als Option gesetzt)
			$first = key($filter->select_options);
			if (
				empty($filter->label) &&
				str_starts_with($filter->select_options[$first], '--') // Ext_Gui2_Util::addLabelItem()
			) {
				$filter->label = trim(str_replace('--', '', $filter->select_options[$first]));
				// Label leeren
				$filter->select_options = \Illuminate\Support\Arr::set($filter->select_options, $first, '');
			}

			if (!$filter->simple) {
				// Leere Option wird bei Vue-Select nicht benötigt (weder multiple noch einfach)
				$filter->select_options = \Illuminate\Support\Arr::except($filter->select_options, $first);
			} else {
				// Leerwert muss korrekt zurückgesetzt werden können bzw. bekannt sein
				// TODO Das dürfte so noch nicht korrekt sein; Case ist z.B. Klassenliste mit gesetztem initial_value
				if (empty($filter->initial_value)) {
					$filter->initial_value = (string)$first;
				}
			}

			// Alle Selects als multiple betrachten, die nicht simple sind
			if (
				!$filter->simple &&
				$filter->multiple === null && // null = nicht explizit gesetzt
				empty($filter->filter_query) &&
				empty($filter->select_options[0]) // ["0"] ist ein Wert für Ext_Gui2_Data::setFilterValues() (z.B. inquiry.yml transfer_mode)
			) {
				$filter->initial_value = [];
				$filter->multiple = true;
			}

		}

		// Bereits gesetzten Wert normalisieren
		if (
			$filter->value === 'xNullx' ||
			$filter->value === ''
		) {
			$filter->value = $filter->initial_value;
		}

		// Das wird bei den neuen Filtern nicht mehr benötigt und sorgt ansonsten für weitere Probleme
		if ($filter instanceof \Ext_Gui2_Bar_Timefilter) {
			$filter->default_from = '';
			$filter->default_until = '';
		}

		$this->filters[] = $filter;

	}

	/**
	 * Input-Filter hinzufügen
	 *
	 * @see prepareFilters()
	 * @param \Ext_Gui2_Bar_Filter_Abstract $filter
	 * @return void
	 */
	private function setInputFilter(\Ext_Gui2_Bar_Filter_Abstract $filter) {

		if ($filter->filter_type !== 'input') {
			throw new \InvalidArgumentException('Please only use input filter in '.__METHOD__);
		}

		// Alle Suchfilter werden zu einem Filter zusammengefügt
		$existingInputFilter = Arr::first($this->filters, fn($filter) => $filter->filter_type === 'input');

		if ($existingInputFilter === null) {
			$this->setFilter($filter);
			return;
		}

		$dbColumns = (array)$existingInputFilter->db_column;
		$dbAliases = (array)$existingInputFilter->db_alias;
				
		$dbColumns[] = $filter->db_column;
		
		if(!empty($filter->db_alias)) {
			$lastKey = array_key_last($dbColumns);
			$dbAliases[$lastKey] = $filter->db_alias;
		}

		$existingInputFilter->db_column = $dbColumns;
		$existingInputFilter->db_alias = $dbAliases;
	}

	/**
	 * Neue Filter vorbereiten
	 *
	 * Achtung: $filter darf hier nicht verändert werden, da diese in der GUI-Session stehen!
	 * Möchte man die Filter beim Setzen modifizieren, geht das in der setFilter()
	 *
	 * @param $filterValues
	 * @return array
	 * @see setFilter()
	 */
	private function prepareFilters(&$filterValues): array {

		$helpTexts = collect(\Gui2\Entity\InfoText::getRepository()->findLanguageValuesForGuiDialog($this, \Ext_Gui2_Bar_Filter_Abstract::INFO_ICON_KEY, \System::getInterfaceLanguage()));

		$filters = [];
		$collapsed = count($this->filters) > 7; // TODO Vlt. besser einstellbar machen, z.B. für Klassenliste

		$types = array_column($this->filters, 'sort_order');
		$labels = array_column($this->filters, 'label');
		array_multisort($types, SORT_ASC, $labels, SORT_ASC, $this->filters);

		foreach ($this->filters as $filter) {

			$value = null;
			$negated = false;

			$options = [];
			if (
				$filter instanceof \Ext_Gui2_Bar_Filter &&
				$filter->filter_type === 'select'
			) {
				// Für identische Vergleiche alle Keys auf String casten (select.value ist im DOM immer String)
				$options = array_map(function ($key, $label) {
					$key = $key === 'xNullx' ? '' : (string)$key;
					return ['key' => $key, 'label' => $label];
				}, array_keys($filter->select_options), $filter->select_options);

			}

			$this->filterQuery->prepareDefaultFilterValue($filter, $value, $negated);

			// Filter-Werte der GUI überschreiben, da das schon immer als eigene Variable/State überall behandelt wurde
			// Achtung: Es gibt auch noch Stellen, wo weiter direkt auf $_VARS['filter'] zugegriffen wird
			$filterValues[$filter->id] = $value;
			if ($negated) {
				$filterValues[$filter->buildKeyForNegate()] = true;
			}

			$data = [
				'key' => $filter->id,
				'label' => $filter->label,
				'type' => $filter->filter_type,
				'options' => $options,
				'value' => $value, // State: Aktueller Wert
				'initial_value' => $filter->initial_value, // Leerwert
				'multiple' => $filter->multiple ?? false,
				'simple' => $filter->simple ?? false,
				'negateable' => $filter->negateable ?? false,
				'negated' => $negated, // State
				'show_in_bar' => $filter->show_in_bar ?? false,
				'additional_html' => $filter->additional_html ?? '',
				'collapsed' => $collapsed,
				'help_text' => data_get($helpTexts->firstWhere('field', $filter->id), 'value')
			];

			$filters[] = $data;

		}

		// Bar automatisch einbinden, da hierüber auch die Sidebar eingefügt wird
		if (!empty($filters)) {
			$this->addFilterBar();
		}

		return $filters;

	}

	private function addFilterBar() {

		// Nur einmal hinzufügen, da $this->_aBar in der GUI-Session ist
		if ($this->filterBarAdded) {
			return;
		}

		$oBar = $this->createBar();
		$oBar->class = 'gui-filter-toolbar';
		$oBar->data = [
			'vue' => true,
//			'vue-component' => 'GuiFilterBar',
//			'hash' => $this->hash
		];
		$oBar->show_if_empty = true;
//		$oBar->setElement($oBar->createLabelGroup($this->t('Filter')));
//		$oBar->setElement('<div data-vue-component="GuiFilterBar" data-hash="'.$this->hash.'"></div>');
		array_unshift($this->_aBar, $oBar);

		$this->filterBarAdded = true;

	}

	private function prepareFilterQueries() {

		$this->filterQueries = FilterQuery::query()
			->where('gui_hash', $this->hash)
			->where(function (\Core\Database\WDBasic\Builder $query) {
				$query->where('visibility', 'all');
				$query->orWhere(function (\Core\Database\WDBasic\Builder $query) {
					$query->where('visibility', 'user');
					$query->where('creator_id', \System::getCurrentUser()->id);
				});
			})
			->get();

		$this->filterQueries->prepend($this->createDefaultFilterQuery());

	}

	/**
	 * Default-Query besteht immer aus Leerwerten und die in der YML gesetzten Standardwerte (z.B. Timefilter)
	 *
	 * @return FilterQuery
	 */
	private function createDefaultFilterQuery(): FilterQuery {

		$defaultQuery = new FilterQuery();
		$defaultQuery->name = ''; // $this->t('Standard');

		foreach ($this->filters as $filter) {
			$defaultQuery->setFilterValue($filter, $filter->value, false);
		}

		return $defaultQuery;

	}

	private function setFilterQuery() {

		if ($this->oRequest && $this->oRequest->has('filter_query_changed')) {
			// 0 wird bei tatsächlichem Wechsel nicht mitgeschickt, daher filter_query_changed
			$id = (int)$this->oRequest->input(FilterQuery::REQUEST_PARAM_ID);
		} else {
			$user = \System::getCurrentUser();
			$query = $this->filterQueries->first(function (FilterQuery $query) use ($user) {
				return collect($query->default_per_user)->search($user->id) !== false;
			}); /** @var FilterQuery $query */
			$id = $query ? $query->id : 0;
		}

		$this->filterQuery = $this->filterQueries->firstWhere('id', $id);

		if (empty($this->filterQuery)) {
			throw new \RuntimeException('Could not find filter query: '.$id);
		}

	}

	/**
	 * @TODO Partielle Redundanz mit \Ext_Gui2_Index_Generator::_addFlexFields()
	 *
	 * @see \Ext_Gui2_Index_Generator::_addFlexFields()
	 * @see \Ext_TC_Gui2_Filterset_Bar_Element::getFlexColumns()
	 *
	 * @param \Ext_TC_Flexibility $field
	 */
	protected function setCustomFieldFilter(\Ext_TC_Flexibility $field) {

		if (!$field->visible || !$field->isFilterable()) {
			return;
		}

		// Siehe Ext_Gui2_Index_Generator::_addFlexFields()
		$key = 'flex_'.$field->id.'_original';
		$alias = 'flex_'.$field->id;

		$filterset = [];
		if ((int)$field->type === \Ext_TC_Flexibility::TYPE_TEXT) {
			$filterset = [
				'type' => 'input',
			];
		} else if (
			(int)$field->type === \Ext_TC_Flexibility::TYPE_SELECT ||
			(int)$field->type === \Ext_TC_Flexibility::TYPE_MULTISELECT
		) {
			$filterset = [
				'type' => 'select',
				'options' => [\Ext_TC_Flexibility::class, 'getOptions', $field->id, \System::getInterfaceLanguage()]
			];
		} else if ((int)$field->type === \Ext_TC_Flexibility::TYPE_DATE) {
			$filterset = [
				'type' => 'date',
				'search_type' => 'between'
			];
		} else if ((int)$field->type === \Ext_TC_Flexibility::TYPE_YESNO) {
			$key = 'flex_'.$field->id; // Wird nicht mit '_original' geschrieben
			$filterset = [
				'type' => 'select',
				'options' => [\Ext_TC_Util::class, 'getYesNoArray', false]
			];
		} else if ((int)$field->type === \Ext_TC_Flexibility::TYPE_CHECKBOX) {
			$key = 'flex_'.$field->id; // Wird nicht mit '_original' geschrieben
			$filterset = [
				'type' => 'select',
				'options' => [\Ext_TC_Util::class, 'getYesNoArray', true, false]
			];
			if(!$this->checkWDSearch()) {
				$filterset['query'] = [
					// Bei Checkbox existiert evtl. kein Eintrag, wenn die Checkbox nicht ausgewählt wurde
					0 => "`".\DB::escapeQueryString($key)."`.`value` = 0 OR `".\DB::escapeQueryString($key)."`.`value` IS NULL",
					1 => "`".\DB::escapeQueryString($key)."`.`value` = 1"
				];
			}
		}

		if (empty($filterset)) {
			throw new \RuntimeException('No filterset configuration defined for type "'.$field->type.'"');
		}

		$column = [
//			'column' => $key,
			'_column' => $key,
			'title' => fn() => $field->title,
			'filterset' => $filterset
		];

		if (!$this->checkWDSearch() && $this instanceof \Ext_TC_Gui2) {
			$section = Arr::first($this->getFlexSections(), fn ($section) => $section['section'] === $field->getSection()->type);
			if ($section) {
				$column['_column'] = 'value';
				$column['alias'] = $alias;
				$this->addFlexJoin($key, $field->id, $section['primary_key'], $section['primary_key_alias']);
			}
		}

		$factory = new \Ext_Gui2_Factory_Filter($this, $column);
		$filter = $factory->create();

		if ($filterset['type'] === 'input') {
			$this->setInputFilter($filter);
		} else {
			$this->setFilter($filter);
		}

	}



}