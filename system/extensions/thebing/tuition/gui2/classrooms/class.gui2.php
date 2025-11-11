<?php

class Ext_Thebing_Tuition_Gui2_Classrooms_Gui2 extends Ext_Thebing_Gui2_Data {

	static public function getDialog(\Ext_Gui2 $oGui) {
		
		$oFloors			= Ext_Thebing_Tuition_Floors::getInstance();
		$aFloorBuildings	= $oFloors->getListWithBuildings();

		$oDialog						= $oGui->createDialog(L10N::t('Klassenzimmer editieren', $oGui->gui_description).' - {name}', L10N::t('Neues Klassenzimmer anlegen', $oGui->gui_description));
		$oDialog->width					= 900;
		$oDialog->height				= 650;

		$oDialog->save_as_new_button		= true;
		$oDialog->save_bar_options			= true;
		$oDialog->save_bar_default_option	= 'new';

		$oDialog->aOptions['section']	= 'tuition_course_classrooms';
		$oDialog->setElement($oDialog->createRow(L10N::t('Bezeichnung', $oGui->gui_description), 'input', array('db_alias' => '', 'db_column'=>'name','required' => 1)));
		$oDialog->setElement($oDialog->createRow(L10N::t('Max. Anzahl Schüler', $oGui->gui_description), 'input', array('db_alias' => 'kc', 'db_column'=>'max_students','required' => 1)));
		if(!empty($aFloorBuildings)) {
			$aFloorBuildings = Ext_Thebing_Util::addEmptyItem($aFloorBuildings);
			$oDialog->setElement($oDialog->createRow(L10N::t('Gebäude, Etage', $oGui->gui_description), 'select', array('db_alias' => '', 'db_column'=>'floor_id','required' => 0, 'select_options' => $aFloorBuildings)));
		}
		$oDialog->setElement($oDialog->createRow(L10N::t('Online', $oGui->gui_description), 'checkbox', array('db_alias' => '', 'db_column'=>'online')));
		$oDialog->setElement($oDialog->createRow(L10N::t('Verfügbar im Lehrerportal', $oGui->gui_description), 'checkbox', array('db_alias' => '', 'db_column'=>'teacher_portal')));


		$oDialog->setElement(
			$oDialog->createRow(
				L10N::t('Eigenschaften', $oGui->gui_description), 
				'input', 
				[
					'db_alias' => '', 
					'db_column'=>'tags', 
					'fastselect'=>['url'=>\Core\Helper\Routing::generateUrl('TsTuition.ts_tuition_classrooms_tags')],
					'format' => new \TsTuition\Gui2\Format\Classroom\Tags
				]
			)
		);

		$oDialog->setElement($oDialog->createRow(L10N::t('Kommentar', $oGui->gui_description), 'textarea', array('db_alias' => '', 'db_column'=>'comment')));
		
		return $oDialog;
	}
	
	static public function getWhere() {
		return ['kc.idSchool' => \Core\Handler\SessionHandler::getInstance()->get('sid')];
	}
	
	static public function getOrderby() {
		return ['kc.name' => 'ASC'];
	}
	
}