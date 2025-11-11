<?php

namespace TsTuition\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;
use Core\Interfaces\ParallelProcessing\TaskAware;
use TsTuition\Service\CourseRenewalService;

final class CourseRenewal extends TypeHandler implements TaskAware
{
	const TASK_NAME = 'course-renewal';

	private array $task;

	public function execute(array $data, $debug = false)
	{
		$inquiry = \Ext_TS_Inquiry::getInstance($data['inquiry_id']);
		$journeyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($data['journey_course_id']);

		$service = new CourseRenewalService();
		$service->runTask($inquiry, $journeyCourse);

		\Ext_Gui2_Index_Stack::save();
		\Ext_Gui2_Index_Stack::executeCache();

		return true;
	}

	public function getLabel()
	{
		return \L10N::t('Automatische Kursverlängerung');
	}

	/**
	 * Da mehrere CourseRenewal-Prozesse Rechnungen parallel generieren können, gleiche Nummernkreise aber nicht
	 * parallel verwendet werden können (außer alles findet in einem Prozess statt), muss das Generieren so oft neu
	 * probiert werden bis es funktioniert. Sollte der max. Counter hier ausgereizt werden, muss man sich etwas anderes
	 * überlegen für die Problematik mit den Nummernkreisen.
	 */
	public function getRewriteAttempts()
	{
		if ((int)$this->task['execution_count'] >= 100) {
			return (int)$this->task['execution_count'];
		}

		return (int)$this->task['execution_count'] + 1;
	}

	public function setTask(array $task): void
	{
		$this->task = $task;
	}
}
