<?php

namespace Tc\Gui2\Data\EventManagement;

use Tc\Entity\EventManagement;
use Tc\Facades\EventManager;

class TaskData extends \Ext_TC_Gui2_Data {

	/**
	 * @var EventManagement\AbstractManagedChild
	 */
	public $oWDBasic;

	public static function getDialog(\Ext_Gui2 $gui2) {
		$dialog = $gui2->createDialog($gui2->t('Element bearbeiten'), $gui2->t('Neues Element'));
		return $dialog;
	}

	public function getEditDialogHTML(&$dialog, $selectedIds, $additional = false) {

		$dialog->aElements = [];

		$this->getWDBasicObject($selectedIds);

		/* @var EventManagement $event */
		$event = $this->_getParentWDBasic();

		[$listeners, $conditions] = EventManager::getEventListenersAndConditions($event->event_name);

		if ($this->_oGui->getConfig('set') === 'conditions') {
			$options = $conditions;
		} else {
			$options = $listeners;
		}

		$options = collect($options)
			->mapWithKeys(function ($config) {
				if (method_exists($config[0], 'getTitle')) {
					$label = $config[0]::getTitle();
				} else {
					$label = $config[0];
				}
				return [$config[0] => $label];
			});

		$tab = $dialog->createTab($this->t('Einstellungen'));

		$tab->setElement($dialog->createRow($this->t('Element'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'class',
			'select_options' => \Ext_TC_Util::addEmptyItem($options->toArray()),
			'required' => true,
			'events' => [
				[
					'event' 	=> 'change',
					'function' 	=> 'reloadDialogTab',
					'parameter'	=> 'aDialogData.id, [0, 1]'
				]
			]
		]));

		// TODO anders lösen
		$dialog->setOption('placeholders', false);

		if (
			!empty($this->oWDBasic->class) &&
			method_exists($this->oWDBasic->class, 'prepareGui2Dialog')
		) {
			$class = $this->oWDBasic->class;
			$class::prepareGui2Dialog($dialog, $tab, $this);
		}

		$dialog->setElement($tab);

		$eventClass = $event->event_name;

		if (
			class_exists($eventClass) &&
			method_exists($eventClass, 'getPlaceholderObject') &&
			null !== $placeholderObject = $eventClass::getPlaceholderObject()
		) {
			$placeholderTab = $dialog->createTab($this->t('Platzhalter'));
			$placeholderTab->hidden = true;

			// TODO anders lösen
			if ($dialog->getOption('placeholders', false)) {
				$placeholderTab->hidden = false;
			}

			$placeholderHtml = (new \Ext_TC_Placeholder_Html())
				->createPlaceholderContent($placeholderObject->displayPlaceholderTable());

			$placeholderTab->setElement($placeholderHtml);
			$dialog->setElement($placeholderTab);
		}

		return parent::getEditDialogHTML($dialog, $selectedIds, $additional);
	}

	public static function getListenersWhere()
	{
		return ['tc_emc.type' => EventManagement\Listener::TYPE];
	}

	public static function getConditionsWhere()
	{
		return ['tc_emc.type' => EventManagement\Condition::TYPE];
	}

}
