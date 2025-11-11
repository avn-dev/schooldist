<?php

namespace Ts\Events\Conditions;

use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Facades\EventManager;
use Illuminate\Support\Arr;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Interfaces\Events\InquiryEvent;

class UploadCondition implements Manageable
{

	const DEFAULT_UPLOAD_FIELD1 = 'photo';
	const DEFAULT_UPLOAD_FIELD2 = 'passport';

	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Datei wurde nicht hochgeladen');
	}

	public static function toReadable(Settings $settings): string
	{
		$uploadIds = $settings->getSetting('upload_ids');
		$uploadFields = self::getUploadFieldOptions();

		$uploadFieldNames = array_intersect_key($uploadFields, array_flip($uploadIds));

		return sprintf(
			EventManager::l10n()->translate('Wenn im Uploadfeld "%s" nichts hochgeladen wurde'),
			implode(', ', $uploadFieldNames)
		);
	}

	// Eins der ausgewählten Uploadfelder muss "leer" sein
	public function passes(InquiryEvent $event): bool
	{
		$customer = $event->getInquiry()->getCustomer();
		$uploadFieldIds = $this->managedObject->getSetting('upload_ids', []);

		// Wenn im Default Uploadfeld "Foto" nichts hochgeladen wurde
		if(in_array(self::DEFAULT_UPLOAD_FIELD1, $uploadFieldIds) && empty($customer->getPhoto())) {
			return true;
		}
		// Wenn im Default Uploadfeld "Reisepass" nichts hochgeladen wurde
		if(in_array(self::DEFAULT_UPLOAD_FIELD2, $uploadFieldIds) && empty($customer->getPassport())) {
			return true;
		}
		// Default UploadFields entfernen, werden nicht mehr benötigt
		array_splice($uploadFieldIds, 0, 2);

		// Custom UploadField
		foreach ($uploadFieldIds as $uploadFieldId) {

			// TODO Die Methode aufzurufen mit Parametern aus der gleichen Klasse ist subobtimal..
			// Wenn im Custom-Uploadfeld nichts hochgeladen wurde
			if (empty($customer->getStudentUpload($uploadFieldId, $customer->getSchool()->id, $customer->getInquiry()->id))) {
				return true;
			}
		}
		// Wenn kein ausgewähltes Uploadfeld "leer" ist
		return false;
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$uploadFields = self::getUploadFieldOptions();

		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Uploadfeld'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_upload_ids',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'required' => true,
			'select_options' => $uploadFields
		]));
	}

	public static function getUploadFieldOptions() {

		$uploadFields[self::DEFAULT_UPLOAD_FIELD1] = EventManager::l10n()->translate('Foto');
		$uploadFields[self::DEFAULT_UPLOAD_FIELD2] = EventManager::l10n()->translate('Reisepass');
		$uploadFields += \Ext_Thebing_School_Customerupload::query()->pluck('name', 'id')->toArray();

		return $uploadFields;
	}

}
