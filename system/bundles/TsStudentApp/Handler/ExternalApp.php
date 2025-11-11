<?php

namespace TsStudentApp\Handler;

use TsStudentApp\Pages\Home;

class ExternalApp extends \Ts\Handler\ExternalAppPerSchool {

	const APP_NAME = 'fidelo_student_app';

	public function getTitle(): string {
		return $this->t('Fidelo Student App');
	}

	public function getDescription(): string {
		return $this->t('Die Fidelo Student App muss separat bestellt werden und kann über diese externe App konfiguriert werden.');
	}

	public function getIcon(): string {
		return 'fa fa-mobile';
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::CRM;
	}

	public function getSettings(): array {

		$config = \Core\Helper\BundleConfig::of('TsStudentApp');

		$templates = \Ext_TC_Communication_Template::getSelectOptions('mail', [
			'application' => 'mobile_app_forgotten_password'
		])->toArray();

		$combinations = \Ext_TS_Frontend_Combination::query()
			->where('usage', \Ext_Thebing_Form::TYPE_REGISTRATION_V3)
			->get()
			->filter(function (\Ext_TS_Frontend_Combination $combination) {
				$form = \Ext_Thebing_Form::getInstance($combination->items_form);
				if (
					empty($combination->items_url) ||
					$form->purpose !== \Ext_Thebing_Form::PURPOSE_EDIT ||
					empty($form->getFilteredBlocks(function (\Ext_Thebing_Form_Page_Block $block) {
						return $block->block_id == \Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY && $block->getSetting('based_on') === 'scheduling';
					}))
				) {
					return false;
				}
				return true;
			})
			->mapWithKeys(fn(\Ext_TS_Frontend_Combination $combination) => [$combination->id => $combination->name])
			->prepend('', '');

		$pages = collect($config->get('pages'))
			->filter(fn(array $page) => !empty($page['deactivatable']))
			->map(fn(array $page) => $page['title'])
			->sort();

		$attendanceSettings = collect(\Ext_TC_Flexibility::getSectionFieldData(['tuition_attendance_register'], true))
			->mapWithKeys(function (\Ext_TC_Flexibility $field) {
				return ['flex_'.$field->id => sprintf('%s: %s', $this->t('Feld'), $field->title)];
			})
			->put('attendance_score', sprintf('%s: %s', $this->t('Feld'), $this->t('Punkte')))
			->put('attendance_comment', sprintf('%s: %s', $this->t('Feld'), $this->t('Kommentar')))
			->put('charts_course', $this->t('Diagramme für Anwesenheit pro Kurs'))
			->put('charts_teacher', $this->t('Diagramme für Anwesenheit pro Lehrer'))
			->sort();

		$settings = [
			'student_app_template_forgotten_password' => [
				'label' => $this->t('Template für vergessenes Passwort'),
				'type' => 'select',
				'options' => \Util::addEmptyItem($templates),
				'description' => $this->t('Ohne diese Option kann ein Schüler sein Passwort nicht zurücksetzen.')
			],
			'student_app_combination_activity_booking' => [
				'label' => $this->t('Kombination für das Buchen von Aktivitäten'),
				'type' => 'select',
				'options' => $combinations,
				'description' => $this->t('Die Kombination muss ein URL und ein Formular enthalten, welches Daten aktualisiert und einen Block für Aktivitäten enthalten, welcher auf der Planung basiert.')
			],
			'student_app_disabled_pages' => [
				'label' => $this->t('Deaktivierte Seiten'),
				'type' => 'select_multiple',
				'options' => $pages
			],
			'student_app_home_boxes' => [
				'label' => $this->t('Inhalt Startseite'),
				'type' => 'select_multiple',
				'options' => collect(Home::getBoxes())->mapWithKeys(function ($box, $key) {
					return [$key => $this->t($box['title'])];
				})
			],
			'student_app_attendance_settings' => [
				'label' => $this->t('Einstellungen für Anwesenheit'),
				'type' => 'select_multiple',
				'options' => $attendanceSettings
			],
			'student_app_student_can_change_picture' => [
				'label' => $this->t('Schüler kann Foto in App ändern oder löschen'),
				'type' => 'checkbox'
			],
			'student_app_show_tuition_block_description' => [
				'label' => $this->t('Inhalt aus Klassenblock (Label "Inhalt") in App anzeigen'),
				'type' => 'checkbox'
			],
			'student_app_show_tuition_block_daily_comments' => [
				'label' => $this->t('Tägliche Kommentare aus Klassenblock in App anzeigen'),
				'type' => 'checkbox'
			],
			'student_app_show_activity_block_cancelable' => [
				'label' => $this->t('Schüler können sich in der App von Aktivitäten abmelden'),
				'type' => 'checkbox'
			],
			'student_app_student_messages' => [
				'label' => $this->t('Schüler können in der App auf Nachrichten antworten'),
				'type' => 'checkbox'
			]
		];
/*
		$languages = \TsStudentApp\Gui2\Data\Handbook::getLanguages();

		foreach ($languages as $language) {
			$settings['student_app_welcome_title_'.$language['iso']] = [
				'label' => $this->t('Willkommensseite – Überschrift').' ('.$language['name'].')',
				'type' => 'input'
			];
			$settings['student_app_welcome_text_'.$language['iso']] = [
				'label' => $this->t('Willkommensseite – Text').' ('.$language['name'].')',
				'type' => 'html'
			];
		}
*/
		return $settings;

	}

	public function saveSettings(\Core\Handler\SessionHandler $oSession, \MVC_Request $oRequest) {

		$data = [];
		foreach ($oRequest->input('config') as $schoolId => $values) {
			foreach ($values as $key => $value) {
				$data['config'][$schoolId][$key] = $value;
				if (
					str_starts_with($key, 'student_app_welcome_title_') ||
					str_starts_with($key, 'student_app_welcome_text_')
				) {
					$purifier = new \Core\Service\HtmlPurifier(\Core\Service\HtmlPurifier::SET_FRONTEND);
					$data['config'][$schoolId][$key] = $purifier->purify($value);
				}
			}
		}

		$oRequest->request->replace($data);

		parent::saveSettings($oSession, $oRequest);

	}

}
