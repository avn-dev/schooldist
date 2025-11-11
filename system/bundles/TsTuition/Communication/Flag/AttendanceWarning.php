<?php

namespace TsTuition\Communication\Flag;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Illuminate\Support\Arr;
use Tc\Service\LanguageAbstract;

class AttendanceWarning implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Anwesenheitswarnung');
	}

	public static function getRecipientKeys(): array
	{
		return ['customer'];
	}

	public function validate(bool $used, LanguageAbstract $l10n, HasCommunication $model, \Ext_TC_Communication_Message $message, bool $finalOutput, array $confirmedErrors): array
	{
		return [];
	}

	public function save(\Ext_TC_Communication_Message $message): void
	{
		$allocations = $message->searchRelations(\Ext_Thebing_School_Tuition_Allocation::class);

		/* @var \Ext_TC_Communication_Message_Template $templateChild */
		$templateChild = Arr::first($message->getJoinedObjectChilds('templates', true));

		$template = ($templateChild) ? $templateChild->getTemplate() : null;

		foreach ($allocations as $allocation) {
			$journeyCourse = $allocation->getJourneyCourse();

			$attendanceWarning = $journeyCourse->index_attendance_warning;

			if (empty($attendanceWarning)) {
				$attendanceWarning = [];
			}

			$attendanceWarning[] = [
				'date' => date('Y-m-d H:i:s'),
				// TODO Warum der Name und nicht ID?
				'template_name' => $template?->name
			];

			$journeyCourse->index_attendance_warning = $attendanceWarning;
			$journeyCourse->save();
		}
	}
}