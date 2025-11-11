<?php

namespace Gui2\Dialog;

use Illuminate\Support\Arr;

class VueDialogData extends \Ext_Gui2_Dialog_Data
{
	public function getEdit($aSelectedIds, $aSaveData = array(), $sAdditional = false)
	{
		$data = parent::getEdit($aSelectedIds, $aSaveData, $sAdditional);

		// Einfache Datenstruktur, vor allem bei Containern
		$data = array_reduce($data, function (array $data, array $field) {
			if (!empty($field['joined_object_key'])) {
				if (empty($data[$field['joined_object_key']])) {
					$data[$field['joined_object_key']] = [];
				}
				foreach ($field['value'] as $value) {
					Arr::set($data, sprintf('%s.%d.id', $field['joined_object_key'], $value['id']), $value['id']);
					Arr::set($data, sprintf('%s.%d.%s', $field['joined_object_key'], $value['id'], $field['db_column']), $value['value']);
				}
			} else {
				$element = $this->_oDialog->searchSaveDataField($field['db_column'], $field['db_alias'] ?? null);
				$data[$field['db_column']] = $field['value'];
				if ($element['element'] === 'checkbox') {
					// Typsicherheit galore
					$data[$field['db_column']] = (bool)$field['value'];
				}
			}
			return $data;
		}, []);

		return array_map(function ($value) {
			// Container-Keys rauswerfeb für plain JS-Arrays
			if (is_array($value)) {
				return array_values($value);
			}
			return $value;
		}, $data);
	}

	public function saveEdit(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true)
	{
		$aSaveData = array_reduce($this->_oDialog->aSaveData, function (array $data, array $field) use ($aSaveData) {
			if (!empty($field['joined_object_key'])) {
				if (count($aSaveData[$field['joined_object_key']])) {
					foreach ($aSaveData[$field['joined_object_key']] as $key => $element) {
						$id = $element['id'] ?? $key * -1;
						$key = sprintf('%s.%s.%d.%s', $field['db_column'], $field['joined_object_key'], $id, $field['db_alias']);
						Arr::set($data, $key, $element[$field['db_column']]);
						Arr::set($data, 'joined_object_container_hidden.'.$id.'.'.$field['joined_object_key'], 1);
					}
				} else {
					// Damit sich der erste Eintrag löschen lässt
					Arr::set($data, $field['db_column'].'.'.$field['joined_object_key'], []);
					Arr::set($data, 'joined_object_container_hidden.0.'.$field['joined_object_key'], 0);
				}
			} else {
				$data[$field['db_column']] = $aSaveData[$field['db_column']] ?? null;
				if (is_bool($data[$field['db_column']])) {
					$data[$field['db_column']] = (int)$data[$field['db_column']];
				}
			}
			return $data;
		}, []);

		return parent::saveEdit($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);
	}
}
