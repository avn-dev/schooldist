<?php

namespace Gui2\Traits;

use Gui2\Entity\FilterQuery;

trait GuiFilterDataTrait {

	public function requestSaveFilterQuery() {

		$request = $this->getGui()->getRequest();

		$data = $request->input('filter');
		$this->setFilterValues($data);

		if (
			!$request->input(FilterQuery::REQUEST_PARAM_ID) ||
			$request->boolean('save_as_new')
		) {
			$query = new FilterQuery();
		} else {
			$query = FilterQuery::query()->findOrFail($request->input(FilterQuery::REQUEST_PARAM_ID));
		}

		$query->gui_hash = $this->getGui()->hash;
		$query->name = $request->input('name', '');
		$query->visibility = $request->input('visibility', 'all');
		$query->filters = [];

		foreach ($this->getGui()->getAllFilterElements() as $filter) {
			$value = $data[$filter->id];
			if ($filter instanceof \Ext_Gui2_Bar_Timefilter) {
				$value = explode(',', $value);
			}
			if ($filter->hasValue($value)) {
				$negated = !empty($data[$filter->buildKeyForNegate()]);
				$query->setFilterValue($filter, $value, $negated);
			}
		}

		if (($validate = $query->validate()) !== true) {
			$message = 'Error';
			if (in_array('TOO_MANY', $validate)) {
				$message = $this->t('Es wurden bereits zu viele Abfragen für diese Liste angelegt.', \Ext_Gui2::$sAllGuiListL10N);
			}

			return [
				'status' => 'error',
				'message' => $message
			];
		}

		$query->save();

		return [
			'filter_query' => $query,
			'status' => 'success',
			'message' => $this->t('Abfrage erfolgreich gespeichert.', \Ext_Gui2::$sAllGuiListL10N)
		];

	}

	public function requestSetDefaultFilterQuery() {

		$request = $this->getGui()->getRequest();
		$user = \System::getCurrentUser();

		// Default aus allen anderen Querys dieser GUI löschen
		FilterQuery::query()
			->where('gui_hash', $this->getGui()->hash)
			->get()
			->each(function (FilterQuery $query) use ($user) {
				$defaultPerUser = collect($query->default_per_user);
				$key = $defaultPerUser->search($user->id);
				if ($key !== false) {
					$query->default_per_user = $defaultPerUser->except($key);
					$query->save();
				}
			});

		// Default neu setzen (und bei Leereintrag überspringen)
		$query = FilterQuery::query()->find($request->input(FilterQuery::REQUEST_PARAM_ID)); /** @var ?FilterQuery $query */
		if ($query !== null) {
			$query->default_per_user = collect($query->default_per_user)->push((int)$user->id);
			$query->save();
		}

		return [
			'status' => 'success',
			'message' => $this->t('Abfrage wurde als Standard gesetzt.', \Ext_Gui2::$sAllGuiListL10N)
		];

	}

	public function requestDeleteFilterQuery() {

		$request = $this->getGui()->getRequest();

		$query = FilterQuery::query()->findOrFail($request->input(FilterQuery::REQUEST_PARAM_ID));
		$query->delete();

		return [
			'filter_query' => [],
			'status' => 'success',
			'message' => $this->t('Abfrage erfolgreich gelöscht.', \Ext_Gui2::$sAllGuiListL10N)
		];

	}
	
	private function getFilterTranslations(): array {

		$data = [];
		$data['help'] = $this->t('Hilfe', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter'] = $this->t('Filter', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_search'] = $this->t('Suche…', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_change'] = $this->t('Filter verändern', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_remove'] = $this->t('Filter entfernen', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_is'] = $this->t('ist', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_is_not'] = $this->t('ist nicht', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_query_name'] = $this->t('Name', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_query_label'] = $this->t('Abfrage', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_query_settings'] = $this->t('Einstellungen', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_visibility'] = $this->t('Sichtbarkeit', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_visibility_all'] = $this->t('Für alle sichtbar', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_visibility_user'] = $this->t('Nur für mich sichtbar', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_save'] = $this->t('Speichern', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_save_as_new'] = $this->t('Als neue Abfrage speichern', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_save_info'] = $this->t('Zeitfilter werden immer relativ zum jeweils aktuellen Tag gespeichert.', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_delete'] = $this->t('Löschen', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_delete_confirm'] = $this->t('Möchten Sie die aktuelle Abfrage wirklich löschen?', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_delete_confirm_all'] = $this->t('Möchten Sie die aktuelle Abfrage wirklich für alle Benutzer löschen?', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_default'] = $this->t('Standard', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_default_title'] = $this->t('Als Standard für meinen Benutzer setzen', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_label_used'] = $this->t('Verwendete Filter', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_label_available'] = $this->t('Verfügbare Filter', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_label_timefilter'] = $this->t('Zeitfilter', \Ext_Gui2::$sAllGuiListL10N);
		$data['filter_label_select'] = $this->t('Auswahlfilter', \Ext_Gui2::$sAllGuiListL10N);

		return $data;
		
	}

}
