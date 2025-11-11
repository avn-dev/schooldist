<?php

class Ext_TC_Frontend_Flexible_Mapping extends Ext_TC_Frontend_Extrafield_Mapping {
	
	public function __construct(string $section, string|array $usage = null) {

		$flexFields = Ext_TC_Flexibility::getFields($section, false, \Illuminate\Support\Arr::wrap($usage));

		$types = Ext_TC_Frontend_Template_Field_Gui2_Selection_Display::getInputTypes();

		foreach ($flexFields as $flexField) {
			/* @var Ext_TC_Flexibility $flexField */
			if(
				$flexField->type == Ext_TC_Flexibility::TYPE_HEADLINE ||
				$flexField->type == Ext_TC_Flexibility::TYPE_REPEATABLE ||
				$flexField->isI18N()
			) {
				// Überschriften rausfiltern
				continue;
			}

			$field = $this->createField(array('Type' => 'char'));
			$field->addConfig('label', $flexField->getName());
			$field->addConfig('allowed_input_types', array_intersect_key($types, array_flip($this->getFlexibilityInputTypes($flexField))));
			$this->addIndividualField($flexField->id, $field);
		}

	}

	protected function getFlexibilityInputTypes(Ext_TC_Flexibility $oField) {

		$iType = (int) $oField->type;

		$aTypes = [];
		switch($iType) {
			case 1: // Großes Textfeld
			case 6: // HTML
				$aTypes[] = 'textarea';
				break;
			case 2: // Checkbox
				$aTypes[] = 'checkbox';
				break;
			case 5: // Dropdown
				$aTypes[] = 'select';
				$aTypes[] = 'radio';
				break;
			case 8: // Multiselect
				$aTypes[] = 'multiselect';
				break;
			case 4: // Datum
				$aTypes[] = 'date';
				break;
			case 0: // Text
			default:
				$aTypes[] = 'input';
				break;
		}

		return $aTypes;
	}
}