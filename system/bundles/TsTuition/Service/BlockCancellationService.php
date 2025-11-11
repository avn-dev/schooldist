<?php

namespace TsTuition\Service;

use Core\Helper\BitwiseOperator;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use TsTuition\Entity\Block\Unit;

readonly class BlockCancellationService
{
	public function __construct(
		private \Ext_Thebing_School_Tuition_Block $block
	) {}

	public function lazyUpdate(int $prio = 1): void
	{
		$payload = [
			'id' => $this->block->id
		];

		\Core\Entity\ParallelProcessing\Stack::getRepository()
			->writeToStack('ts-tuition/block-cancellation', $payload, $prio);
	}

	public function update(): void
	{
		$allocations = $this->block->getAllocations();

		foreach ($allocations as $allocation) {
			$journeyCourse = $allocation->getJourneyCourse();

			// Passiert in \Ext_TS_Inquiry_TuitionIndex::update()
			/*$contingent = $journeyCourse->getLessonsContingent($allocation->getProgramService());
			$contingent->refresh(LessonsContingent::USED | LessonsContingent::CANCELLED);
			$contingent->lock();*/

			// Nachollektionen
			(new CourseLessonsCatchUpService($journeyCourse))->update();

			// Index aktualisieren
			$tuitionIndex = new \Ext_TS_Inquiry_TuitionIndex($journeyCourse->getJourney()->getInquiry());
			$tuitionIndex->update();
		}
	}

}