<?php

namespace TsTuition\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;
use TsTuition\Service\BlockCancellationService;
use TsTuition\Service\CourseLessonsCatchUpService;

class CourseLessonsCatchUp extends TypeHandler
{
	public function getLabel()
	{
		return \L10N::t('Kursbuchung: Kursausfall', 'School');
	}

	public function execute(array $data, $debug = false)
	{
		$journeyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($data['id']);

		if (!$journeyCourse->exist() || !$journeyCourse->isActive()) {
			return;
		}

		(new CourseLessonsCatchUpService($journeyCourse))->update();
	}
}