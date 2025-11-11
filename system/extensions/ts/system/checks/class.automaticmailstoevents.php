<?php

class Ext_TS_System_Checks_AutomaticMailsToEvents extends GlobalChecks
{
	public function getTitle()
	{
		return 'Automatic E-Mails';
	}

	public function getDescription()
	{
		return 'Changes automatic e-mails into new event management system';
	}

	public function executeCheck()
	{
		$automaticEmails = $this->getAutomaticEmails();
		if (empty($automaticEmails) || !empty($this->getExistingEvents())) {
			return true;
		}

		$backup = \Util::backupTable('tc_communication_automatictemplates');
		if (!$backup) {
			__pout('Backup failed!');
			return false;
		}

		\DB::begin(__METHOD__);

		try {

			$allSchools = $this->getAllSchoolIds();
			$allLanguages = array_keys(\Ext_Thebing_Data::getSystemLanguages());

			foreach ($automaticEmails as $automaticEmail) {

				$event = [
					'name' => $automaticEmail['name'],
					'created' => date('Y-m-d H:i:s')
				];

				$meta = [];
				$childs = [];

				switch ($automaticEmail['type']) {
					case 'birthday_mail':
						$event['event_name'] = \Ts\Events\Inquiry\CustomerBirthday::class;
						$event['execution_time'] = $automaticEmail['execution_time'];
						$event['execution_weekend'] = 1;
						// meta
						$meta['recipient_type'] = $automaticEmail['recipient_type'];
						break;
					case 'booking_mail':
					case 'enquiry_mail':
						if ($automaticEmail['type'] === 'enquiry_mail') {
							$event['event_name'] = \Ts\Events\Inquiry\EnquiryDayEvent::class;
						} else {
							$event['event_name'] = \Ts\Events\Inquiry\InquiryDayEvent::class;
						}
						$event['execution_time'] = $automaticEmail['execution_time'];
						$event['execution_weekend'] = 1;
						// meta
						$meta['days'] = $automaticEmail['days'];
						$meta['direction'] = $automaticEmail['temporal_direction'];
						$meta['event_type'] = $automaticEmail['event_type'];

						// Stornierte Buchungen ignorieren
						if ((int)$automaticEmail['ignore_cancellation'] === 1) {
							$childs['inquiry_status'] = [
								'class' => \Ts\Events\Inquiry\Conditions\InquiryStatus::class,
								'type' => \Tc\Entity\EventManagement\Condition::TYPE,
								'meta' => [
									'operator' => 'is_not',
									'status' => 'cancelled'
								]
							];
						}

						// Minimale Anzahl an vergangenen Tagen seit letzter Korrespondenz
						if ((int)$automaticEmail['days_after_last_message'] > 0) {
							$childs['days_since'] = [
								'class' => \Ts\Events\Inquiry\Conditions\DaysSinceLastMessage::class,
								'type' => \Tc\Entity\EventManagement\Condition::TYPE,
								'meta' => [
									'days' => $automaticEmail['days_after_last_message']
								]
							];
						}

						break;
					case 'registration_mail':
						// Werden gesondert behandelt
						continue 2;
				}

				$this->buildEvent($allSchools, $event, $meta, $automaticEmail, $childs);

			}

			// Formulare

			$forms = \DB::getQueryRows("SELECT * FROM `kolumbus_forms` WHERE `active` = 1");

			$groupedRegistrationMails = [];
			foreach ($forms as $form) {

				$templatesSql = "
					SELECT
						*
					FROM
					    `kolumbus_forms_translations`
					WHERE
					    `item` = 'form' AND
					    `item_id` = :form_id AND
					    `field` LIKE 'schoolTpl%'AND
					    `active` = 1 AND
					    `content` > 0
				";

				$templates = \DB::getPreparedQueryData($templatesSql, ['form_id' => $form['id']]);

				$combinationSql = "
					SELECT
						`tc_frontend_combinations`.`id`
					FROM
					    `tc_frontend_combinations_items` INNER JOIN
					    `tc_frontend_combinations` ON 
					        `tc_frontend_combinations`.`id` = `tc_frontend_combinations_items`.`combination_id` AND
					        `tc_frontend_combinations`.`active` = 1
					WHERE
					    `tc_frontend_combinations_items`.`item` = 'form' AND
					    `tc_frontend_combinations_items`.`item_value` = :form_id AND
					    `tc_frontend_combinations`.`active` = 1
				";

				$combinations = \DB::getQueryCol($combinationSql, ['form_id' => $form['id']]);

				if (!empty($combinations)) {

					foreach ($templates as $template) {
						$automaticTemplateId = $template['content'];
						$schoolId = \Illuminate\Support\Str::after($template['field'], 'schoolTpl');
						$language = $template['language'];

						$groupedRegistrationMails[$automaticTemplateId]['combinations'] = array_merge(
							(array)$groupedRegistrationMails[$automaticTemplateId]['combinations'],
							$combinations
						);
						$groupedRegistrationMails[$automaticTemplateId]['schools'][] = $schoolId;
						$groupedRegistrationMails[$automaticTemplateId]['languages'][] = $language;
						$groupedRegistrationMails[$automaticTemplateId]['forms'][] = $form['title'];

						$groupedRegistrationMails[$automaticTemplateId]['combinations'] =
							array_unique($groupedRegistrationMails[$automaticTemplateId]['combinations']);

						$groupedRegistrationMails[$automaticTemplateId]['schools'] =
							array_unique($groupedRegistrationMails[$automaticTemplateId]['schools']);

						$groupedRegistrationMails[$automaticTemplateId]['languages'] =
							array_unique($groupedRegistrationMails[$automaticTemplateId]['languages']);

						$groupedRegistrationMails[$automaticTemplateId]['forms'] =
							array_unique($groupedRegistrationMails[$automaticTemplateId]['forms']);
					}

				}
			}

			foreach ($groupedRegistrationMails as $automaticTemplateId => $config) {

				$automaticEmail = \Illuminate\Support\Arr::first($automaticEmails, fn ($automaticEmail) => (int)$automaticEmail['id'] === (int)$automaticTemplateId);

				if ($automaticEmail) {
					$event = [
						'name' => $automaticEmail['name'].' - '.implode('/', $config['forms']),
						'created' => date('Y-m-d H:i:s'),
						'event_name' => \TsRegistrationForm\Events\FormSaved::class
					];

					$childs = [];
					$childs['combinations'] = [
						'class' => \TsRegistrationForm\Events\Conditions\Combination::class,
						'type' => \Tc\Entity\EventManagement\Condition::TYPE,
						'meta' => [
							'combination_ids' => $config['combinations'],
						]
					];

					if (!empty(array_diff($allLanguages, $config['languages']))) {
						$childs['combination_languages'] = [
							'class' => \TsFrontend\Events\Conditions\CombinationLanguage::class,
							'type' => \Tc\Entity\EventManagement\Condition::TYPE,
							'meta' => [
								'languages' => array_intersect($config['languages'], $allLanguages),
							]
						];
					}

					if (!empty(array_diff($allSchools, $config['schools']))) {
						$childs['schools'] = [
							'class' => \Ts\Events\Conditions\SchoolCondition::class,
							'type' => \Tc\Entity\EventManagement\Condition::TYPE,
							'meta' => [
								'school_ids' => array_intersect($config['schools'], $allSchools),
							]
						];
					}

					$this->buildEvent($allSchools, $event, [], $automaticEmail, $childs);
				}

			}

			\DB::executeQuery('UPDATE `tc_communication_automatictemplates` SET `active`= 0 WHERE `active`= 1');

		} catch (\Throwable $e) {
			\DB::rollback(__METHOD__);
			__out($e);
			return false;
		}

		\DB::commit(__METHOD__);

		return true;
	}

	private function buildEvent(array $allSchools, $event, $meta, array $automaticEmail, array $childs): int
	{
		$recipients = explode(',', $automaticEmail['recipients']);
		$templateSchools = explode(',', $automaticEmail['schools']);

		$eventId = \DB::insertData('tc_event_management', $event);

		$this->insertMetaData('tc_event_management', $eventId, $meta);

		// Schule
		if (!empty(array_diff($allSchools, $templateSchools))) {

			if (isset($childs['schools'])) {
				$childs['schools']['meta']['school_ids'] = array_intersect($templateSchools, $childs['schools']['meta']['school_ids']);
			} else {
				$childs['schools'] = [
					'class' => \Ts\Events\Conditions\SchoolCondition::class,
					'type' => \Tc\Entity\EventManagement\Condition::TYPE,
					'meta' => [
						'school_ids' => $templateSchools
					]
				];
			}

		}

		foreach ($recipients as $recipient) {
			if (trim($recipient) === 'customer') {
				$childs['send_customer_mail'] = [
					'class' => \Ts\Listeners\Inquiry\SendCustomerEmail::class,
					'type' => \Tc\Entity\EventManagement\Listener::TYPE,
					'meta' => [
						'template_id' => $automaticEmail['layout_id'],
						'send_mode' => \Ext_TC_Communication::SEND_MODE_AUTOMATIC,
					]
				];
			} else if (trim($recipient) === 'subobject') {
				$childs['send_school_mail'] = [
					'class' => \Ts\Listeners\SendSchoolNotification::class,
					'type' => \Tc\Entity\EventManagement\Listener::TYPE,
					'meta' => [
						'template_id' => $automaticEmail['layout_id'],
						'send_mode' => \Ext_TC_Communication::SEND_MODE_AUTOMATIC,
					]
				];
			} else if (trim($recipient) === 'individual') {
				$childs['send_individual_mail'] = [
					'class' => \Ts\Listeners\SendIndividualEmail::class,
					'type' => \Tc\Entity\EventManagement\Listener::TYPE,
					'meta' => [
						'template_id' => $automaticEmail['layout_id'],
						'email_addresses' => $automaticEmail['to'],
						'send_mode' => \Ext_TC_Communication::SEND_MODE_AUTOMATIC,
					]
				];
			}
		}

		$index = 0;
		foreach ($childs as $child) {
			$entry = [
				'event_id' => $eventId,
				'type' => $child['type'],
				'class' => $child['class'],
				'position' => ($index + 1)
			];
			$childId = \DB::insertData('tc_event_management_childs', $entry);

			$this->insertMetaData('tc_event_management_childs', $childId, $child['meta']);
			++$index;
		}

		return $eventId;
	}

	private function getAllSchoolIds(): array
	{
		$sql = "
			SELECT 
				`id`
			FROM 
			    `customer_db_2`
			WHERE 
			   `active` = 1
			";
		return \DB::getQueryCol($sql);
	}

	private function getAutomaticEmails(): array
	{
		$sql = "
			SELECT 
				`tc_ca`.*,
				GROUP_CONCAT(DISTINCT `tc_car`.`recipient`) `recipients`,
				GROUP_CONCAT(DISTINCT `kets`.`school_id`) `schools`
			FROM 
			    `tc_communication_automatictemplates` `tc_ca` LEFT JOIN 
			    `tc_communication_automatictemplates_recipients` `tc_car` ON
					`tc_car`.`template_id` = `tc_ca`.`id` LEFT JOIN
				`kolumbus_email_templates` `ket` ON
				    `ket`.`id` = `tc_ca`.`layout_id` LEFT JOIN
				`kolumbus_email_templates_schools` `kets` ON
				    `kets`.`template_id` = `ket`.`id`
			WHERE 
			   `tc_ca`.`active` = 1
			GROUP BY 
			    `tc_ca`.`id` 
			";
		return (array)\DB::getQueryRows($sql);
	}

	private function getExistingEvents(): array
	{
		$sql = "
			SELECT 
				`tc_em`.*
			FROM 
			    `tc_event_management` `tc_em`
			WHERE 
			   `tc_em`.`active` = 1 
			";
		return (array)\DB::getQueryRows($sql);
	}

	private function insertMetaData(string $entity, int $entityId, array $values): void
	{
		foreach ($values as $key => $value) {
			\DB::insertData('wdbasic_attributes', [
				'entity' => $entity,
				'entity_id' => $entityId,
				'key' => $key,
				'value' => (is_array($value)) ? json_encode($value) : $value,
			]);
		}
	}

}
