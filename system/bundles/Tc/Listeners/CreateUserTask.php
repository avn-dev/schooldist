<?php

namespace Tc\Listeners;

use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Traits\Events\ManageableTrait;

class CreateUserTask implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Aufgabe für Mitarbeiter anlegen');
	}

	public function handle($payload) {

		dd($payload);

	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow($dataClass->t('Aufgabe'), 'textarea', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_message'
		]));

		$tab->setElement($dialog->createRow($dataClass->t('Empfängergruppe'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_receivers_type',
			'select_options' => [
				'users' => $dataClass->t('Einzelne Benutzer'),
				'user_groups' => $dataClass->t('Bestimmte Benutzergruppen')
			],
			'child_visibility' => [
				[
					'db_alias' => 'tc_emc',
					'db_column' => 'users',
					'on_values' => ['users']
				],
				[
					'db_alias' => 'tc_emc',
					'db_column' => 'user_groups',
					'on_values' => ['user_groups']
				],
			]
		]));

		$tab->setElement($dialog->createRow($dataClass->t('Benutzer'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'users',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'select_options' => collect(\Factory::executeStatic(\User::class, 'getList'))
				->mapWithKeys(fn ($user) => [$user['id'] => implode(', ', [$user['lastname'], $user['firstname']])])
		]));

		$oEmployeeCategory = \Tc\Entity\Employee\Category::getInstance();
		$aEmployeeCategories = $oEmployeeCategory->getArrayList(true);

		$tab->setElement($dialog->createRow($dataClass->t('Benutzergruppen'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'user_groups',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'select_options' => $aEmployeeCategories
		]));
	}

}
