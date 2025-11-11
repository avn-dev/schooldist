<?php
/**
 * GUI2 Data-Ableitung der Felder-GUI
 */
class Ext_TC_Frontend_Template_Field_Gui2_Data extends Ext_TC_Gui2_Data
{
	/**
	 * - Prüfen, ob eigenes Template gespeichert werden darf
	 * - Template bei reloadDialogTab nachladen
	 *
	 * @param array $aSelectedIds
	 * @param $aSaveData
	 * @param bool $bSave
	 * @param string $sAction
	 * @param bool $bPrepareOpenDialog
	 * @return array
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {
		global $_VARS;
	
		$oField = $this->_getWDBasicObject($aSelectedIds);

		if($bSave) {

			// Prüfen, ob eigenes Template gespeichert werden darf
			if($aSaveData['overwrite_template']['tc_ftf'] == 0) {
				$aSaveData['template']['tc_ftf'] = '';
			}

		} elseif(
			isset($_VARS['reload_tab'])
		) {

			// Template bei reloadDialogTab nachladen
			$sType = $aSaveData['display']['tc_ftf'];
			$sTemplate = '';

			if($sType) {
				$oTemplate = $oField->getJoinedObject('frontend_template');
				$sTemplate = $oTemplate->getDefaultTemplateContent($sType);
			}

			$aSaveData['template']['tc_ftf'] = $sTemplate;

		}

		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		return $aTransfer;

	}

	protected function requestAddMissingFields($_VARS) {

		$areas = array_keys(Ext_TC_Frontend_Template_Field::getFieldAreas(false));
		$template = \Ext_TC_Frontend_Template::getInstance(reset($_VARS['parent_gui_id']));

		$existingFields = $template->getJoinedObjectChilds('fields');

		$added = 0;

		foreach($areas as $area) {

			$dummy = $template->getJoinedObjectChild('fields');
			$dummy->area = $area;

			$selectionField = Ext_TC_Factory::getObject('Ext_TC_Frontend_Template_Field_Gui2_Selection_Field');
			/* @var Ext_TC_Frontend_Template_Field_Gui2_Selection_Field $selectionField */

			$fields = $selectionField->getOptions([], [], $dummy);
			unset($fields[0]);

			foreach($fields as $key => $label) {

				$existing = collect($existingFields)->first(function ($field) use ($key) {
					return ($field->field === $key);
				});

				if(is_null($existing)) {
					$dummy->field = $key;

					$selectionDisplay = Ext_TC_Factory::getObject('Ext_TC_Frontend_Template_Field_Gui2_Selection_Display');
					/* @var Ext_TC_Frontend_Template_Field_Gui2_Selection_Display $selectionDisplay */

					$display = array_keys($selectionDisplay->getOptions([], [], $dummy));

					if(!empty($display)) {
						$field = new Ext_TC_Frontend_Template_Field();
						$field->template_id = $template->getId();
						$field->area = $area;
						$field->field = $key;
						$field->label = $label;
						$field->placeholder = $key;
						$field->display = reset($display);
						$field->save();

						++$added;
					}
				}

			}

		}

		$transfer = [];
		$transfer['action'] = 'showSuccessAndReloadTable';
		$transfer['success'] = 1;
		if($added === 1) {
			$transfer['message'] = sprintf(\L10N::t('Es wurde "%s" Feld hinzugefügt'), $added);
		} else if($added > 1) {
			$transfer['message'] = sprintf(\L10N::t('Es wurden "%s" Felder hinzugefügt'), $added);
		} else {
			$transfer['message'] = \L10N::t('Es sind bereits alle möglichen Felder vorhanden');
		}

		$transfer['success_title']	= $this->t('Erfolgreich');

		return $transfer;
	}

}
