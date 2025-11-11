<?php

namespace TsAccommodation\Gui2\Data;

use TsAccommodation\Entity\Cleaning\Type\Cycle;
use TsAccommodation\Gui2\Selection\CleaningTypesRooms;

class CleaningTypesData extends \Ext_Thebing_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $gui) {

        $schools = \Ext_Thebing_Client::getSchoolList(true);

		$dialog = $gui->createDialog($gui->t('Reinigungsart "{name}" editieren'), $gui->t('Neuen Reinigungsart anlegen'));

        $dialog->setElement($dialog->createRow($gui->t('Name'), 'input', array(
            'db_alias' => 'ts_act',
            'db_column' => 'name',
            'required'	=> true,
        )));

        $dialog->setElement($dialog->createRow($gui->t('Abkürzung'), 'input', array(
			'db_alias' => 'ts_act',
			'db_column' => 'short',
			'required'	=> true
        )));

        $dialog->setElement($dialog->createRow($gui->t('Schulen'), 'select', [
            'db_alias' => '',
            'db_column' => 'schools',
            'multiple' => 3,
            'select_options' => $schools,
            'jquery_multiple' => 1,
            'searchable' => 1,
            'required' => 1,
        ]));

        $dialog->setElement($dialog->createRow($gui->t('Kategorien'), 'select', [
            'db_alias' => '',
            'db_column' => 'accommodation_categories',
            'selection' => new \Ext_Thebing_Gui2_Selection_School_AccommodationCategory(\Ext_Thebing_Util::getInterfaceLanguage(), true),
            'multiple' => 3,
            'jquery_multiple' => 1,
            'searchable' => 1,
            'required' => 1,
            'dependency' => [
                [
                    'db_alias' => '',
                    'db_column' => 'schools',
                ],
            ]
        ]));

        $dialog->setElement($dialog->createRow($gui->t('Zimmer'), 'select', [
            'db_alias' => '',
            'db_column' => 'rooms',
            'selection' => new CleaningTypesRooms(),
            'multiple' => 5,
            'jquery_multiple' => 1,
            'searchable' => 1,
            'required' => 1,
            'dependency' => [
                [
                    'db_alias' => '',
                    'db_column' => 'accommodation_categories',
                ],
            ]
        ]));

        $h3 = $dialog->create('h4');
        $h3->setElement($gui->t('Zyklus'));
        $dialog->setElement($h3);

        $joinedObjectContainer = $dialog->createJoinedObjectContainer('cycles', array('min' => 1, 'max' => 20));

        $joinedObjectContainer->setElement($joinedObjectContainer->createMultiRow("",  [
            'db_alias' => 'ts_actc',
            'items' => [
                [
                    'input' => 'select',
                    'db_column' => 'mode',
                    'select_options' => [
                        Cycle::MODE_REGULAR_BED => $gui->t('Regelmäßig (Bett)'),
                        Cycle::MODE_ONCE_ROOM => $gui->t('Einmalig (Zimmer)'),
                        Cycle::MODE_ONCE_BED => $gui->t('Einmalig (Bett)'),
                        Cycle::MODE_FIX_BED => $gui->t('Fest (Bett)'),
                    ],
                    'class'	=> 'cycle_mode',
                    'events' => [
                        [
                            'event' 	=> 'change',
                            'function' 	=> 'changeCycleMode'
                        ]
                    ]
                ],
                [
                    'input' => 'input',
                    'db_column' => 'count',
                    'class'	=> 'cycle_count',
                    'style' => 'width:40px;'
                ],
                [
                    'input' => 'select',
                    'db_column' => 'count_mode',
                    'select_options' => [
                        'days' => $gui->t('Tag(e)'),
                        'weeks' => $gui->t('Woche(n)'),
                    ],
                    'class'	=> 'cycle_count_mode',
                ],
                [
                    'input' => 'select',
                    'db_column' => 'time',
                    'select_options' => [
                        'after_arrival' => $gui->t('nach Anreise'),
                        'before_departure' => $gui->t('vor Abreise'),
                    ],
                    'class'	=> 'cycle_time',
                ],
                [
                    'input' => 'select',
                    'db_column' => 'weekday',
                    'select_options' => \Ext_Thebing_Util::getLocaleDays(\System::getInterfaceLanguage(), 'wide'),
                    'class'	=> 'cycle_weekday',
                    'style' => 'display:none'
                ]
            ]
        ]));

        /*$translation = $gui->t('Abhängig von Zimmerreinigung in den nächsten {field} Tagen');
        $translationParts = explode('{field}', $translation);

        $joinedObjectContainer->setElement($joinedObjectContainer->createMultiRow("",  [
            'db_alias' => 'ts_actc',
            'row_style' => 'display: block',
            'row_class' => 'dependency_row',
            'items' => [
                [
                    'input' => 'checkbox',
                    'db_column' => 'depending',
                    'text_after' => $translationParts[0],
                    'class'	=> 'cycle_depending',
                    'value' => 1
                ],
                [
                    'input' => 'input',
                    'db_column' => 'depending_days',
                    'text_after' => $translationParts[1],
                    'class'	=> 'cycle_depending_days',
                    'style' => 'width: 40px;'
                ]
            ]
        ]));*/

        $dialog->setElement($joinedObjectContainer);

		return $dialog;

	}

	protected function saveEditDialogData(array $selectedIds, $saveData, $save = true, $action = 'edit', $prepareOpenDialog = true) {

	    if(isset($saveData['depending_days']['ts_actc'])) {
            // wegen der MultiRow klappen die Checkboxen nicht richtig (es wird kein hidden eingebaut)
            $dependingDays = $saveData['depending_days']['ts_actc'];
            foreach($dependingDays as $index => $value) {
                if(!isset($saveData['depending']['ts_actc'][$index]['cycles'])) {
                    $saveData['depending']['ts_actc'][$index]['cycles'] = 0;
                }
            }
        }

        return parent::saveEditDialogData($selectedIds, $saveData, $save, $action, $prepareOpenDialog);
    }

}
