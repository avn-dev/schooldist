<?php

namespace TsTuition\Entity\Block;

use Core\Helper\BitwiseOperator;
use Spatie\Period\Period;
use TsTuition\Service\Placeholder\BlockUnit as BlockUnitPlaceholder;

class Unit extends \Ext_Thebing_Basic
{
	const STATE_HAS_TAKEN_PLACE = 1;

	const STATE_CANCELLED = 2;

	protected $_sPlaceholderClass = BlockUnitPlaceholder::class;

	protected $_sTable = 'ts_tuition_blocks_daily_units';

	protected $_sTableAlias = 'ts_tbdu';

	protected $_aJoinedObjects = [
		'block'	=> [
			'class' => \Ext_Thebing_School_Tuition_Block::class,
			'key' => 'block_id',
			'readonly' => true
		],
	];

	public function getBlock(): \Ext_Thebing_School_Tuition_Block
	{
		return $this->getJoinedObject('block');
	}

	public function getStartDate(): ?\DateTimeImmutable
	{
		$period = $this->getPeriod();
		if ($period) {
			return $period->start();
		}
		return null;
	}

	public function getEndDate(): ?\DateTimeImmutable
	{
		$period = $this->getPeriod();
		if ($period) {
			return $period->end();
		}
		return null;
	}

	public function getPeriod(): ?Period
	{
		$periods = $this->getBlock()->createPeriodCollection()
			->filter(fn (Period $period) => $period->start()->format('N') == $this->day);

		if (!$periods->isEmpty()) {
			return $periods[0];
		}

		return null;
	}

	public function hasTakenPlace(): bool
	{
		return $this->hasState(self::STATE_HAS_TAKEN_PLACE);
	}

	public function isCancelled(): bool
	{
		return $this->hasState(self::STATE_CANCELLED);
	}

	public function hasState(int $check): int|null
	{
		return BitwiseOperator::has($this->state, $check);
	}

	public function addState(int $state, string $comment = null): static
	{
		// Wenn die Einheit als "stattgefunden" markiert wurde kann sie nicht abgesagt werden
		if (
			BitwiseOperator::has($state, self::STATE_CANCELLED) &&
			BitwiseOperator::has($this->state, self::STATE_HAS_TAKEN_PLACE)
		) {
			throw (new \TsTuition\Exception\BlockException(sprintf('Block unit cannot be cancelled, the unit has already been marked as "has taken place" [day: %d]', $this->day)))
				->block($this->getBlock());
		}

		// Wenn die Einheit abgesagt wurde kann sie nicht als stattgefunden markiert werden
		if (
			BitwiseOperator::has($this->state, self::STATE_CANCELLED) &&
			BitwiseOperator::has($state, self::STATE_HAS_TAKEN_PLACE)
		) {
			throw (new \TsTuition\Exception\BlockException(sprintf('Block unit could not have taken place, it is already canceled [day: %d]', $this->day)))
				->block($this->getBlock());
		}

		if ($comment !== null) {
			$this->state_comment = strip_tags($comment);
		}

		$current = $this->state;

		BitwiseOperator::add($current, $state);

		$this->state = $current;

		return $this;
	}

	public function removeState(int $state): static
	{
		$current = $this->state;

		BitwiseOperator::remove($current, $state);

		$this->state = $current;

		return $this;
	}

}