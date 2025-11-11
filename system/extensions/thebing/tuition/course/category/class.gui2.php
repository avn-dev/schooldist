<?php

class Ext_Thebing_Tuition_Course_Category_Gui2 extends Ext_Thebing_Gui2_Data{

	public function getPlaceHolderFields(){
		
		$aPlaceHolderFields = array(
			array(
				'title'					=> 'Lehrer der Woche (kompletter Name, Vorname, Nachname)',
				'placeholder'			=> '{teacher}, {teacher|firstname}, {teacher|lastname}'
			),
			array(
				'title'					=> 'Lehrer des Tages (kompletter Name, Vorname, Nachname)',
				'placeholder'			=> '{teacher_by_day}, {teacher_by_day|firstname}, {teacher_by_day|lastname}'
			),
			array(
				'title'					=> 'Schüler (kompletter Name, Vorname, Nachname',
				'placeholder'			=> '{students_list}, {students_list|firstname}, {students_list|lastname}'
			),
			array(
				'title'					=> 'Name der Klasse',
				'placeholder'			=> '{name}'
			),
			array(
				'title'					=> 'Startdatum der Klasse',
				'placeholder'			=> '{class_date_start}'
			),
			array(
				'title'					=> 'Enddatum der Klasse',
				'placeholder'			=> '{class_date_end}'
			),
			array(
				'title'					=> 'Name der Vorlage',
				'placeholder'			=> '{blockname}'
			),
			array(
				'title'					=> 'Kurse, Kurse limitiert',
				'placeholder'			=> '{courses}, {courses|max:3}'
			),
			array(
				'title'					=> 'Schüleranzahl (.. / ..)',
				'placeholder'			=> '{students}'
			),
			array(
				'title'					=> 'Klasseninhalt',
				'placeholder'			=> '{content}'
			),
			array(
				'title'					=> 'Uhrzeit',
				'placeholder'			=> '{time}'
			),
			array(
				'title'					=> 'Anzahl der Lektionen',
				'placeholder'			=> '{units}'
			),
			array(
				'title'					=> 'Score (von ...bis ...)',
				'placeholder'			=> '{score}'
			),
			array(
				'title'					=> 'Niveaus',
				'placeholder'			=> '{level}'
			),
			array(
				'title'					=> 'Laufwoche',
				'placeholder'			=> '{week}'
			),
			array(
				'title'					=> 'Laufwoche (relativ zum Niveau)',
				'placeholder'			=> '{week_level}'
			),
			array(
				'title'					=> 'Nationalitäten',
				'placeholder'			=> '{nationalities}'
			),
		);

		return $aPlaceHolderFields;
	}

	static public function getDialog(Ext_Thebing_Gui2 $oGui){
 
//		$oSchool = Ext_Thebing_School::getSchoolFromSession();
//		$aLanguages				= $oSchool->getLanguageList(true);
//		$sDefaultLang			= $oSchool->getLanguage();
		$oData = $oGui->getDataObject(); /** @var Ext_Thebing_Tuition_Course_Category_Gui2 $oData */
		$aPlaceHolderFields = $oData->getPlaceHolderFields();
//		$iClientID = Ext_Thebing_System::getClientId();
		$aSchools = Ext_Thebing_Client::getSchoolList(true);
		
		
		// Dialog
		$oDialog = $oGui->createDialog(
				$oGui->t('Kurskategorie editieren'), 
				$oGui->t('Neue Kurskategorie anlegen'));
		$oDialog->width = 1000;
		$oDialog->height = 650;

		$oDialog->save_as_new_button		= true;
		$oDialog->save_bar_options			= true;
		$oDialog->save_bar_default_option	= 'new';

		$oDialog->aOptions['section'] = 'tuition_course_categories';
		$aLevels = Ext_Thebing_Util::getStudentLevels();

		$oDialog->setElement(
			$oDialog->createI18NRow(
				$oGui-> t('Name', $oGui->gui_description),
				[
					'db_column_prefix' => 'name_',
					'db_alias' => '',
					'required' => true
				],
				Ext_Thebing_Util::getTranslationLanguages()
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Schulen'), 
				'select', 
				[
					'db_alias' => '', 
					'db_column'=>'schools', 
					'select_options' => $aSchools,
					'multiple'=>5,
					'jquery_multiple' => true,
					'required' => true
				]
			)
		);

		$sDescription = '<ul>';
		foreach($aPlaceHolderFields as $aDataForPlaceHolder) {
			$sDescription .= '<li>';
			$sDescription .= '<span class="pull-left" style="width:400px;">'.$oGui->t($aDataForPlaceHolder['title']).'</span>';
			$sDescription .= '<span>'.$aDataForPlaceHolder['placeholder'].'</span>';
			$sDescription .= '</li>';
		}
		$sDescription .= '</ul>';

		$oNotification = $oDialog->createNotification(L10N::t('Verfügbare Platzhalter'), $sDescription, 'info');

		$oDialog->setElement($oNotification);

		$oDialog->setElement($oDialog->createRow($oGui->t('Darstellung in Klassenplanung'), 'html', array(
			'db_column'		=> 'planification_template',
			'db_alias'		=> '',
			'required'		=> 1,
		)));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Buchhaltung'));
		$oDialog->setElement($oH3);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Kostenstelle', $oGui->gui_description),
				'input',
				[
					'db_column'=>'cost_center',
					'db_alias' => ''
				]
			)
		);

		$oH3 = $oDialog->create('h4')->setElement($oGui->t('Frontend'));
		$oDialog->setElement($oH3);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Icon-Klasse', $oGui->gui_description),
				'input',
				[
					'db_column' => 'frontend_icon_class',
					'db_alias' => ''
				]
			)
		);
		
		return $oDialog;
	}

	static public function manipulateSearchFilter()
    {
		$sLanguage = System::getInterfaceLanguage();
		
		return [
			'column' => [
				'id',
				'name_'.$sLanguage
			],
			'alias'=> [
				'ktcc',
				'ktcc'
			]
		];
	}

}