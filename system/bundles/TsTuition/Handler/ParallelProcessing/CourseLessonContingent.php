<?php

namespace TsTuition\Handler\ParallelProcessing;

use Core\Exception\Entity\EntityLockedException;
use Core\Exception\ParallelProcessing\RewriteException;
use Core\Handler\ParallelProcessing\TypeHandler;
use Ts\Entity\Inquiry\Journey\Course\LessonsContingent;

class CourseLessonContingent extends TypeHandler
{
	public function getLabel()
	{
		return \L10N::t('Buchung: Lektionskontingent aktualisieren', 'School');
	}

	public function execute(array $data, $debug = false)
	{
		/* @var LessonsContingent $lessonContingent */
		$lessonContingent = LessonsContingent::query()->find($data['id']);

		if (!$lessonContingent) {
			return true;
		}

		try {

			$lessonContingent
				->refresh($data['columns'] ?? null)
				->lock()
				->save();

		} catch (EntityLockedException) {
			throw new RewriteException();
		}

		return true;
	}
}