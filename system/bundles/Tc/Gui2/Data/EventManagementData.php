<?php

namespace Tc\Gui2\Data;

use Illuminate\Support\Arr;
use Tc\Events\EntityEventDispatched;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\TestableEvent;
use Tc\Service\EventManager\TestingSettings;

class EventManagementData extends \Ext_TC_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $gui2) {
		$dialog = $gui2->createDialog($gui2->t('Event bearbeiten'), $gui2->t('Neues Event'));

		$eventTab = $dialog->createTab($gui2->t('Ereignis'));
		$dialog->setElement($eventTab);

		$createChildGui = function ($set) use($gui2) {
			$factory = new \Ext_Gui2_Factory('Tc_event_management_childs');
			return $factory->createGui($set, $gui2);
		};

		$conditionTab = $dialog->createTab($gui2->t('Optionale Bedingungen'));
		$conditionTab->setElement($createChildGui('conditions'));
		$dialog->setElement($conditionTab);

		$listenerTab = $dialog->createTab($gui2->t('Aktionen'));
		$listenerTab->setElement($createChildGui('listeners'));
		$dialog->setElement($listenerTab);

		return $dialog;
	}

	public static function getTestingDialog(\Ext_Gui2 $gui2) {
		$dialog = $gui2->createDialog($gui2->t('Event testen'), $gui2->t('Event testen'));
		$dialog->sDialogIDTag = 'TESTING_';
		$dialog->save_button = false;
		$dialog->aButtons = [
			[
				'label' => $gui2->t('Ausführen'),
				'task' => 'saveDialog'
			]
		];
		return $dialog;
	}

	public function getEditDialogHTML(&$dialog, $selectedIds, $additional = false) {

		if ($additional === null) {
			$eventTab = $dialog->aElements[0];
			$eventTab->aElements = [];

			$this->getWDBasicObject($selectedIds);

			$eventList = self::getEventOptions($this->_oGui, !$this->oWDBasic->exist());

			if (!$this->oWDBasic->exist()) {
				// Die Entität-Beobachter können hier nicht gesetzt werden
				unset($eventList[EntityEventDispatched::class]);
			}

			$eventTab->setElement($dialog->createRow($this->t('Ereignis'), 'select', [
				'db_alias' => 'tc_em',
				'db_column' => 'event_name',
				'select_options' => \Ext_TC_Util::addEmptyItem($eventList),
				'required' => true,
				'readonly' => $this->oWDBasic->exist(), // An diesem Event hängen die Listeners und Conditions - daher readonly
				'events' => [
					[
						'event' => 'change',
						'function' => 'reloadDialogTab',
						'parameter' => 'aDialogData.id, [0]'
					]
				]
			]));

			$eventTab->setElement($dialog->createRow($this->t('Bezeichnung'), 'input', [
				'db_alias' => 'tc_em',
				'db_column' => 'name',
				'required' => true,
			]));

			if (!empty($this->oWDBasic->event_name)) {

				$class = $this->oWDBasic->event_name;
				if (empty($this->oWDBasic->name)) {
					$this->oWDBasic->name = EventManager::getEventTitle($class);
				}

				if (method_exists($class, 'getDescription') && null !== $description = $class::getDescription()) {
					$eventTab->setElement(
						$dialog->createNotification($this->t('Achtung'), $description, 'info')
					);
				}

				// Wenn es für dieses Event keine optionalen Bedingungen gibt den Tab ausblenden
				/*if (empty($class::getManageableConditions())) {
					$dialog->aElements[1]->hidden = true;
				} else {
					$dialog->aElements[1]->hidden = false;
				}*/

				if (method_exists($class, 'prepareGui2Dialog')) {
					// Spezielle Einstellungen für das Event
					$class::prepareGui2Dialog($dialog, $eventTab, $this);
				}
			}
		}

		return parent::getEditDialogHTML($dialog, $selectedIds, $additional);
	}

	public static function getEventOptions(\Ext_Gui2 $gui2, bool $checkAccess = true) {

		$access = ($checkAccess) ? \Access_Backend::getInstance() : null;

		return EventManager::getDetailedList($access)
			->mapWithKeys(fn ($data, $eventName) => [$eventName => $data['title']])
			->sort()
			->toArray();
	}

	public static function getUserFilterOptions(\Ext_Gui2 $gui2) {

		$me = \Access_Backend::getInstance()->getUser();

		$options = ['me' => $gui2->t('Meine Ereignisse')];

		$users = \Factory::executeStatic(\Ext_TC_User::class, 'query')
			->orderBy('lastname')
			->get();

		foreach ($users as $user) {
			if ($user->id != $me->id) {
				$options['user_'.$user->id] = $user->getName();
			}
		}

		return $options;
	}

	public static function getUserFilterOptionsQueries() {

		$me = \Access_Backend::getInstance()->getUser();

		$buildQuery = function(\Ext_TC_User $user) {
			if (!empty($user->system_types)) {
				return "
					(
						`tc_emltu`.`type` = 'user' AND `tc_emltu`.`type_id` = ".$user->getId()." OR 
						`tc_emltu`.`type` = 'group' AND `tc_emltu`.`type_id` IN (".implode(',', $user->system_types).")
					)
				";
			}

			return " `tc_emltu`.`type` = 'user' AND `tc_emltu`.`type_id` = ".$user->getId();
		};

		$queries = ['me' => $buildQuery($me)];

		$users = \Factory::executeStatic(\Ext_TC_User::class, 'query')->get();
		foreach ($users as $user) {
			if ($user->id != $me->id) {
				$queries['user_'.$user->id] = $buildQuery($user);
			}
		}

		return $queries;
	}

	protected function getDialogHTML(&$iconAction, &$dialog, $selectedIds = array(), $additional = false)
	{
		if ($iconAction === 'testing') {
			$dialog->aElements = [];

			$this->getWDBasicObject($selectedIds);

			$eventName = $this->oWDBasic->event_name;

			if (!is_a($eventName, TestableEvent::class, true)) {
				throw new \RuntimeException('Event is not an instance of %s [%s]', TestableEvent::class, $eventName);
			}

			$dialog->setElement($dialog->createNotification(
				$this->_oGui->t('Bitte beachten'),
				$this->_oGui->t('Bei dem Testdurchlauf werden die eingestellten Aktionen durchgeführt, d.h. etwaige E-Mails oder Systembenachrichtigungen werden tatsächlich verschickt!'),
				'info'
			));

			$eventName::prepareTestingGui2Dialog($dialog, $this);

			$result = $dialog->create('div');
			$result->id = 'test-result';
			$dialog->setElement($result);
		}

		return parent::getDialogHTML($iconAction, $dialog, $selectedIds, $additional);
	}

	protected function saveDialogData($action, $selectedIds, $data, $additional = false, $save = true)
	{
		if ($action === 'testing') {

			$this->getWDBasicObject($selectedIds);

			$testRun = EventManager::runProcessTest($this->oWDBasic->event_name, $this->oWDBasic, new TestingSettings($data));

			// Es gab vorher schon einen Fehler und das Event ist noch nicht durchgelaufen
			if (!empty($testRun['errors']) && empty($testRun['pipeline'])) {
				$transfer = [];
				$transfer['action'] = 'saveDialogCallback';
				$transfer['data']['id'] = 'TESTING_'.Arr::first($selectedIds);
				$transfer['error'] = [
					['message' => $testRun['errors'], 'type' => 'error']
				];
				return $transfer;
			}

			$summarize = function (array $childs) {
				return array_map(function ($child) {
					$child['title'] = method_exists($child['object'], 'toReadable')
						? $child['object']::toReadable($child['object']->getManagedObject())
						: $child['object']::getTitle();
					return $child;
				}, $childs);
			};

			$result = [];

			if (!empty($testRun['pipeline']['conditions'])) {
				$result[] = [
					'title' => $this->_oGui->t('Optionale Bedingungen'),
					'runs' => $summarize($testRun['pipeline']['conditions'])
				];
			}

			if (!empty($testRun['pipeline']['listeners'])) {
				$result[] = [
					'title' => $this->_oGui->t('Aktionen'),
					'runs' => $summarize($testRun['pipeline']['listeners'])
				];
			}

			$html = '';
			foreach ($result as $test) {
				$html .= '<h4>'.$test['title'].'</h4>';
				foreach ($test['runs'] as $run) {
					$html .= '<div style="display: flex; flex-direction: row; gap: 10px;align-items: center; border: 1px solid #EEE; padding: 5px; margin-bottom: 5px; border-radius: 5px">';
						if ($run['success']) {
							$html .= '<i class="fa fa-check fa-colored"></i>';
						} else {
							$html .= '<i class="fa fa-times fa-colored"></i>';
						}
						$html .= '<div style="flex-grow: 1">'.$run['title'];
						if (!empty($run['error'])) {
							$html .= '<br/><span class="label label-danger">'.$run['error'].'</span>';
						}
						$html .= '</div>';
						if ($run['time'] >= 1) {
							$html .= '<span style="color: '.\Ext_TC_Util::getColor('red_font').'">'.\Ext_Thebing_Format::Number($run['time']).' s</span>';
						} else {
							$html .= '<span style="color: #AAAAAA">'.\Ext_Thebing_Format::Number($run['time'] * 100).' ms</span>';
						}
					$html .= '</div>';
				}
			}

			$transfer = [];
			$transfer['action'] = 'showEventTestResult';
			$transfer['data']['result'] = $html;
			return $transfer;
		}

		return parent::saveDialogData($action, $selectedIds, $data, $additional, $save);
	}

}
