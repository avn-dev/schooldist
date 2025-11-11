<?php

class Ext_TS_System_Checks_Templates_Mail_SchoolToCore extends \GlobalChecks
{
	const LOCK_KEY = 'update.300.school_templates_to_core';

	public function getTitle()
	{
		return 'E-Mail Templates';
	}

	public function getDescription()
	{
		return 'Adapts existing e-mail templates and layouts to the new communication structure';
	}

	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1G');

		$alreadyExecuted = (bool)\System::d(self::LOCK_KEY, 0);

		if ($alreadyExecuted) {
			return true;
		}

		[$layouts, $templates] = $this->getLayoutsAndTemplates();

		if (empty($templates) && empty($layouts)) {
			// Bereits durchgelaufen
			return true;
		}

		if ($this->backup() === false) {
			__pout('Backup error');
			return false;
		}

		// Temporäre Spalten damit man sich merken kann welche IDs schon ausgetauscht wurden.

		if (!\DB::getDefaultConnection()->checkField('wdbasic_attributes', '__template_converted', true)) {
			\DB::executeQuery("ALTER TABLE `wdbasic_attributes` ADD `__template_converted` TINYINT(1) NULL DEFAULT NULL");
		}

		if (!\DB::getDefaultConnection()->checkField('customer_db_2', '__template_converted', true)) {
			\DB::executeQuery("ALTER TABLE `customer_db_2` ADD `__template_converted` TINYINT(1) NULL DEFAULT NULL");
		}

		if (!\DB::getDefaultConnection()->checkField('customer_db_2', '__template_converted2', true)) {
			\DB::executeQuery("ALTER TABLE `customer_db_2` ADD `__template_converted2` TINYINT(1) NULL DEFAULT NULL");
		}

		\DB::begin(__METHOD__);

		// alte Id => neue Id
		$layoutMapping = (array)\DB::getQueryPairs("SELECT `entity_id`, `value` FROM `wdbasic_attributes` WHERE `key` = 'core_layout_id'");
		$templateMapping = [];

		try {

			foreach ($layouts as $layout) {
				$newLayoutId = $this->convertLayout($layout);
				$layoutMapping[$layout['id']] = $newLayoutId;
			}

			foreach ($templates as $template) {
				$newTemplateId = $this->convertTemplate($template, $layoutMapping);
				$templateMapping[$template['id']] = $newTemplateId;
			}

			\DB::commit(__METHOD__);

			$success = true;

			\System::s(self::LOCK_KEY, 1);

		} catch (\Exception $e) {
			\DB::rollback(__METHOD__);
			__pout($e);
			$success = false;
		}

		// Temporäre Spalten wieder löschen

		\DB::executeQuery("ALTER TABLE `wdbasic_attributes` DROP `__template_converted`");
		\DB::executeQuery("ALTER TABLE `customer_db_2` DROP `__template_converted`");
		\DB::executeQuery("ALTER TABLE `customer_db_2` DROP `__template_converted2`");

		\WDBasic_Attribute::deleteTableCache();
		\Ext_Thebing_School::deleteTableCache();

		return $success;
	}

	private function convertLayout(array $layout): int
	{
		$newLayout = \Illuminate\Support\Arr::except($layout, ['id', 'active', 'client_id']);

		$newLayoutId = \DB::insertData('tc_communication_templates_layouts', $newLayout);

		\DB::insertData('wdbasic_attributes', ['entity' => 'kolumbus_email_layouts', 'entity_id' => $layout['id'], 'key' => 'core_layout_id', 'value' => $newLayoutId]);

		return $newLayoutId;
	}

	private function convertTemplate(array $template, array $layoutMapping): int
	{
		$oldTemplateId = $template['id'];

		$applications = (array)\DB::getQueryCol('SELECT `application` FROM `kolumbus_email_templates_applications` WHERE `template_id` = :id', ['id' => $oldTemplateId]);

		$receivers = array_map(fn ($application) => $this->getApplicationReceiverMapping($application), $applications);
		//$intersectReceivers = array_intersect(...$receivers);
		$intersectReceivers = array_unique(\Illuminate\Support\Arr::flatten($receivers));

		/*if (empty($intersectReceivers)) {
			throw new \RuntimeException(sprintf('No intersection receivers [template: "%s", %s]', $template['name'], implode(', ', \Illuminate\Support\Arr::flatten($receivers))));
		}*/

		$newTemplate = \Illuminate\Support\Arr::except($template, ['id', 'active', 'client_id', 'html']);
		$newTemplate['type'] = 'email';
		$newTemplate['shipping_method'] = ((int)$template['html'] === 1) ? 'html' : 'text';
		// Flag setzen um alte Platzhalter zu verwenden
		$newTemplate['legacy'] = 1;

		$newTemplateId = \DB::insertData('tc_communication_templates', $newTemplate);

		\DB::insertData('wdbasic_attributes', ['entity' => 'kolumbus_email_templates', 'entity_id' => $oldTemplateId, 'key' => 'core_template_id', 'value' => $newTemplateId]);

		foreach ($intersectReceivers as $receiver) {
			\DB::insertData('tc_communication_templates_recipients', ['template_id' => $newTemplateId, 'recipient' => $receiver]);
		}

		$usedApplications = [];
		foreach ($applications as $application) {
			$mapping = [
				'inbox' => 'booking',
				'accommodation_communication_history_agency_canceled' => 'accommodation_communication_history_customer_canceled',
				// In der alten Kommunikation waren das zwei Applications in einem Dialog
				'accommodation_communication_agency' => 'accommodation_communication_customer_agency',
				'transfer_agency_information' => 'transfer_customer_agency_information'
			];

			$newApplication = $mapping[$application] ?? $application;

			if (in_array($newApplication, $usedApplications)) {
				continue;
			}

			$usedApplications[] = $newApplication;

			\DB::insertData('tc_communication_templates_applications', ['template_id' => $newTemplateId, 'application' => $newApplication]);
		}

		$schools = \DB::getQueryCol('SELECT `school_id` FROM `kolumbus_email_templates_schools` WHERE `template_id` = :id', ['id' => $oldTemplateId]);
		foreach ($schools as $schoolId) {
			\DB::insertData('tc_communication_templates_to_objects', ['template_id' => $newTemplateId, 'object_id' => $schoolId]);
		}

		// 'inbox' => 'booking
		$flags = \DB::getQueryCol('SELECT `flag` FROM `kolumbus_email_templates_flags` WHERE `template_id` = :id', ['id' => $oldTemplateId]);
		foreach ($flags as $flag) {

			$mapping = [
				'customer_info' => 'insurance_customer_confirmed',
				'provider_info' => 'insurance_provider_confirmed',
			];

			if (in_array('contract_accommodation', $applications) && $flag === 'contract_sent') {
				$newFlag = 'accommodation_contract_sent';
			} else if (in_array('contract_teacher', $applications) && $flag === 'contract_sent') {
				$newFlag = 'teacher_contract_sent';
			} else {
				$newFlag = $mapping[$flag] ?? $flag;
			}

			\DB::insertData('tc_communication_templates_flags', ['template_id' => $newTemplateId, 'flag' => $newFlag]);
		}

		$languages = (array)\DB::getQueryData('SELECT * FROM `kolumbus_email_templates_languages` WHERE `template_id` = :id', ['id' => $oldTemplateId]);
		$uploads = (array)\DB::getQueryData('SELECT `id`, `language`, `attachment` FROM `kolumbus_email_templates_languages_attachments` WHERE `template_id` = :id', ['id' => $oldTemplateId]);

		foreach ($languages as $language) {

			$oldLayoutId = $language['layout_id'];

			if (!isset($layoutMapping[$oldLayoutId])) {
				//throw new \RuntimeException(sprintf('Missing old layout id mapping [%d]', $oldLayoutId));
			}

			\DB::insertData('tc_communication_templates_languages', ['template_id' => $newTemplateId, 'language_iso' => $language['language']]);

			$newContentId = \DB::insertData('tc_communication_templates_contents', [
				'template_id' => $newTemplateId,
				'layout_id' => $layoutMapping[$oldLayoutId] ?? 0,
				'language_iso' => $language['language'],
				'subject' => $language['subject'],
				'content' => $language['content'],
			]);

			$languageUploads = array_filter($uploads, fn ($upload) => $upload['language'] === $language['language']);

			foreach ($languageUploads as $upload) {
				$attachment = Ext_Thebing_Email_Template_Attachment::getInstance($upload['id']);

				\DB::insertData('tc_communication_templates_contents_uploads', ['content_id' => $newContentId, 'filename' => $upload['attachment']]);

				if (file_exists($path = $attachment->getPath())) {
					$targetDir = storage_path('tc/communication/templates/email');
					$targetPath = $targetDir.DIRECTORY_SEPARATOR.basename($path);
					\Util::checkDir($targetDir);

					copy($path, $targetPath);

					if (!file_exists($targetPath)) {
						throw new \RuntimeException(sprintf('Could not copy file [%s -> %s]', $path, $targetPath));
					}
				}
			}
		}

		\DB::executePreparedQuery("
			UPDATE 
				`wdbasic_attributes` INNER JOIN
				`tc_event_management_childs` ON
				    `tc_event_management_childs`.`id` = `wdbasic_attributes`.`entity_id` AND
				    `tc_event_management_childs`.`class` != :sendApp
			SET 
			    `value` = :newTemplateId,
			    `__template_converted` = 1
			WHERE 
			    `entity` = 'tc_event_management_childs' AND
			    `key` = 'template_id' AND
			    `value` = :oldTemplateId AND
			     `__template_converted` IS NULL
		", ['newTemplateId' => $newTemplateId, 'oldTemplateId' => $oldTemplateId, 'sendApp' => \Ts\Listeners\Inquiry\SendCustomerAppNotification::class]);

		\DB::executePreparedQuery("
			UPDATE 
				`wdbasic_attributes` 
			SET 
			    `value` = :newTemplateId,
			    `__template_converted` = 1
			WHERE 
			    `entity` = 'customer_db_2' AND
			    `key` = 'student_app_template_forgotten_password' AND
			    `value` = :oldTemplateId AND
			     `__template_converted` IS NULL
		", ['newTemplateId' => $newTemplateId, 'oldTemplateId' => $oldTemplateId]);

		\DB::executePreparedQuery("
			UPDATE 
				`wdbasic_attributes` 
			SET 
			    `value` = :newTemplateId ,
			     `__template_converted` = 1
			WHERE 
			    `entity` = 'customer_db_2' AND
			    `key` = 'accommodationlogin_template' AND
			    `value` = :oldTemplateId AND
			     `__template_converted` IS NULL
		", ['newTemplateId' => $newTemplateId, 'oldTemplateId' => $oldTemplateId]);

		\DB::executePreparedQuery("
			UPDATE 
				`customer_db_2` 
			SET 
			    `changed` = `changed`, 
			    `teacherlogin_template` = :newTemplateId,
			     `__template_converted` = 1
			WHERE 
			    `teacherlogin_template` = :oldTemplateId AND
			     `__template_converted` IS NULL 
		", ['newTemplateId' => $newTemplateId, 'oldTemplateId' => $oldTemplateId]);

		\DB::executePreparedQuery("
			UPDATE 
				`customer_db_2` 
			SET 
			    `changed` = `changed`, 
			    `teacherlogin_reportcard_template` = :newTemplateId,
			     `__template_converted2` = 1
			WHERE 
			    `teacherlogin_reportcard_template` = :oldTemplateId AND
			     `__template_converted2` IS NULL 
		", ['newTemplateId' => $newTemplateId, 'oldTemplateId' => $oldTemplateId]);

		//\DB::executePreparedQuery("UPDATE `kolumbus_forms_schools` SET `tpl_id` = :newTemplateId WHERE `tpl_id` = :oldTemplateId", ['newTemplateId' => $newTemplateId, 'oldTemplateId' => $oldTemplateId]);
		//\DB::executePreparedQuery("UPDATE `kolumbus_forms_schools` SET `offer_template_id` = :newTemplateId WHERE `offer_template_id` = :oldTemplateId", ['newTemplateId' => $newTemplateId, 'oldTemplateId' => $oldTemplateId]);

		return $newTemplateId;
	}

	private function getLayoutsAndTemplates(): array
	{
		$layouts = (array)\DB::getQueryRows("
			SELECT
			    `kolumbus_email_layouts`.* 
			FROM 
			    `kolumbus_email_layouts`  LEFT JOIN 
				`wdbasic_attributes` ON 
					`wdbasic_attributes`.`entity` = 'kolumbus_email_layouts' AND
					`wdbasic_attributes`.`entity_id` = `kolumbus_email_layouts`.`id` AND
					`wdbasic_attributes`.`key` = 'core_layout_id'
			WHERE 
			    `kolumbus_email_layouts`.`active` = 1 AND
			    `wdbasic_attributes`.`entity` IS NULL
		");

		$templates = (array)\DB::getQueryRows("
			SELECT 
			   	`kolumbus_email_templates`.* 
			FROM 
			    `kolumbus_email_templates` LEFT JOIN 
				`wdbasic_attributes` ON 
					`wdbasic_attributes`.`entity` = 'kolumbus_email_templates' AND
					`wdbasic_attributes`.`entity_id` = `kolumbus_email_templates`.`id` AND
					`wdbasic_attributes`.`key` = 'core_template_id'
			WHERE 
			    `kolumbus_email_templates`.`active` = 1 AND
			    `wdbasic_attributes`.`entity` IS NULL
		");

		return [$layouts, $templates];
	}

	private function addLegacyColumn()
	{
		$columnExisting = \DB::getDefaultConnection()->checkField('tc_communication_templates', 'legacy', true);

		if (!$columnExisting) {
			// `legacy` als Weiche für alte Platzhalterklassen
			\DB::executeQuery('ALTER TABLE `tc_communication_templates` ADD `legacy` TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER `shipping_method`');
		}
	}

	private function backup(): bool
	{
		$backup = [
			\Util::backupTable('kolumbus_email_templates'),
			\Util::backupTable('kolumbus_email_layouts'),
			\Util::backupTable('tc_communication_templates'),
			\Util::backupTable('tc_communication_templates_layouts'),
			\Util::backupTable('tc_communication_templates_languages'),
			\Util::backupTable('tc_communication_templates_recipients'),
			\Util::backupTable('tc_communication_templates_applications'),
			\Util::backupTable('tc_communication_templates_to_objects'),
			\Util::backupTable('tc_communication_templates_flags'),
			\Util::backupTable('tc_communication_templates_contents_uploads'),
			\Util::backupTable('wdbasic_attributes'),
			\Util::backupTable('customer_db_2'),
			//\Util::backupTable('kolumbus_forms_schools'),
		];

		if (in_array(false, $backup)) {
			__pout('Backup error');
			return false;
		}

		return true;
	}

	private function getApplicationReceiverMapping($application): array
	{
		$mapping = [
			'inbox' => ['customer', 'agency'],
			'cronjob' => ['customer'],
			'mobile_app_forgotten_password' => ['customer'],
			'accommodation_communication_history_agency_canceled' => ['agency'],
			'accommodation_communication_history_agency_confirmed' => ['agency'],
			'accommodation_communication_agency' => ['agency'],
			'accommodation_communication_provider_requests' => ['transfer_provider'],
			'transfer_agency_information' => ['agency'],
		];

		$applications = \Communication\Facades\Communication::getAllApplications();
		foreach ($applications as $key => $class) {
			$mapping[$key] = \Factory::executeStatic($class, 'getRecipientKeys', [$key]);
		}

		return $mapping[$application] ?? throw new \RuntimeException(sprintf('Unknown application receiver mapping [%s]', $application));
	}
}