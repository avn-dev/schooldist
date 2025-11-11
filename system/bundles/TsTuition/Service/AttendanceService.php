<?php

namespace TsTuition\Service;

use TsTuition\Handler\ParallelProcessing\AttendanceService as Task;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

class AttendanceService
{
	private ?Collection $attendances = null;

	private bool $onlyExcused = false;

	private ?\Ext_Thebing_School $school = null;

	public function only(\Ext_Thebing_Tuition_Attendance|Collection $attendance): static
	{
		if ($attendance instanceof \Ext_Thebing_Tuition_Attendance) {
			$this->attendances = collect([$attendance]);
		} else {
			$this->attendances = $attendance;
		}

		return $this;
	}

	public function onlyExcused(bool $onlyExcused = true): static
	{
		$this->onlyExcused = $onlyExcused;
		return $this;
	}

	public function forSchool(\Ext_Thebing_School $school): static
	{
		$this->school = $school;
		return $this;
	}

	public function refresh(int $prio = 0): void
	{
		$attendances = $this->getAttendances();

		foreach ($attendances as $attendance) {
			/* @var \Ext_Thebing_Tuition_Attendance $attendance */
			if ($prio > 0) {
				$this->writeLazyTask(Task::TASK_REFRESH, ['id' => $attendance->id], $prio);
			} else {

				// Mir ist es hier an der Stelle zu heikel um mit save() zu arbeiten, hier können durchaus mehrere Anwesenheiten
				// parallel gespeichert werden und falls hier mal JoinedObjects dazukommen würden die wieder mitgespeichert
				// werden und das kann im PP zu Datenverlust führen.

				$attendance->lock()->refreshIndex();

				$intersectionData = $attendance->getIntersectionData();

				if (!empty($intersectionData)) {
					$update = "
						UPDATE 
							#table
						SET 
						    `changed` = `changed`,
						    ".implode(',', array_map(fn ($column) => "`".$column."` = :".$column, array_keys($intersectionData)))."
						WHERE
						    `id` = :id
					";

					$parameters = [...['table' => $attendance->getTableName(), 'id' => $attendance->id], ...$intersectionData];

					\DB::executePreparedQuery($update, $parameters);

					// Entity-Log schreiben
					$attendance->log(\Ext_TC_Log::UPDATED, $intersectionData);
				}

				$attendance->unlock();
			}
		}
	}

	public function writeLazyTask(string $task, array $payload = [], int $prio = 10): void
	{
		$data = [
			...['task' => $task, 'excused_only' => $this->onlyExcused, 'school_id' => $this->school?->id, 'attendances' => $this->attendances?->map(fn ($attendance) => $attendance->id)->toArray()],
			...$payload
		];

		\Core\Entity\ParallelProcessing\Stack::getRepository()
			->writeToStack('ts-tuition/attendance-service', $data, $prio);
	}

	private function getAttendances(): Collection
	{
		if ($this->attendances !== null) {
			return $this->attendances;
		}

		$query = \Ext_Thebing_Tuition_Attendance::query()
			->select('kta.*');

		if ($this->school !== null) {
			$query->join('kolumbus_tuition_blocks_inquiries_courses as ktbic', 'ktbic.id', '=', 'kta.allocation_id');
			$query->join('kolumbus_tuition_blocks as ktb', function (JoinClause $join) {
				$join->on('ktb.id', '=', 'ktbic.block_id')
					->where('ktb.school_id', $this->school->id);
			});
		}

		if ($this->onlyExcused) {
			$query->where('kta.excused', '>', 0);
		}

		return $query->get();
	}

	public static function fromArray(array $payload): static
	{
		$service = new static();

		if (!empty($payload['attendances'])) {
			$attendances = \Ext_Thebing_Tuition_Attendance::query()->whereIn('id', $payload['attendances'])->get();
			$service->only($attendances);
		} else {
			$service->onlyExcused($payload['excused_only']);
			if (!empty($payload['school_id'])) {
				$school = \Ext_Thebing_School::query()->findOrFail($payload['school_id']);
				$service->forSchool($school);
			}
		}

		return $service;
	}
}