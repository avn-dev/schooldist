<?php

namespace TsTuition\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;
use TsTuition\Service\AttendanceService as Service;

class AttendanceService extends TypeHandler
{
	const TASK_REFRESH = 'refresh';
	const TASK_FIND_AND_REFRESH = 'find_and_refresh';

	public function getLabel()
	{
		return \L10N::t('Anwesenheit: Aktualisierung', 'School');
	}

	public function execute(array $data, $debug = false)
	{
		return match ($data['task']) {
			self::TASK_REFRESH => $this->refresh($data),
			self::TASK_FIND_AND_REFRESH => $this->findAndRefresh($data),
			default => throw new \RuntimeException(sprintf('Unknown attendance service task [%s]', $data['task'])),
		};
	}

	private function refresh($data): bool
	{
		$attendance = \Ext_Thebing_Tuition_Attendance::query()->find($data['id']);

		if (!$attendance) {
			return true;
		}

		Service::fromArray($data)->only($attendance)->refresh();

		return true;
	}

	private function findAndRefresh($data): bool
	{
		Service::fromArray($data)->refresh(10);

		return true;
	}
}