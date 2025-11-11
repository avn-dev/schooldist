<?php

class Ext_Thebing_Examination_Sections_Entity_Select extends Ext_Thebing_Examination_Sections_Entity_Int
{

	public function getInput()
	{
		return 'select';
	}

	public function getEntityKey()
	{
		return 'entity_int';
	}

	public function  addValue(Ext_Gui2_Html_Abstract $oInput, $mValue){

		$sName = $oInput->name;

		$oSelect = new Ext_Gui2_Html_Select();
		$oSelect->name = $oInput->name;
		$oSelect->class = $oInput->class;

		$oSection = Ext_Thebing_Examination_Sections::getInstance($this->section_id);

		$aOptions = $oSection->getOptions();

		foreach((array)$aOptions as $oOptionObj){
			$oOption = new Ext_Gui2_Html_Option();
			$oOption->value = $oOptionObj->id;
			if($oOptionObj->id == $mValue){
				$oOption->selected = 'selected';
			}
			$oOption->setElement($oOptionObj->title);

			$oSelect->setElement($oOption);
		}



		return $oSelect;
	}

	// Optionen an Select hängen
	public function addOptions(Ext_Gui2_Html_Abstract $oInput){

		$oSection = Ext_Thebing_Examination_Sections::getInstance($this->section_id);

		$aOptions = $oSection->getOptions();

		foreach((array)$aOptions as $oOptionObj){
			$oOption = new Ext_Gui2_Html_Option();
			$oOption->value = $oOptionObj->id;
			$oOption->setElement($oOptionObj->title);
			
			$oInput->setElement($oOption);
		}


		return $oInput;
	}

	// formatieren
	public function getStringValue(){
		$oSection = Ext_Thebing_Examination_Sections::getInstance($this->aData['section_id']);

		$aOptions = $oSection->getOptions();
		$sValue = '';
		foreach((array)$aOptions as $oOptionObj){
			if($oOptionObj->id == $this->aData['value']){
				$sValue = $oOptionObj->title;
				break;
			}

		}

		return $sValue;
	}
}