<?php

namespace TsFrontend\Helper;

use Carbon\Carbon;
use Tc\Traits\Placeholder\ReplaceInterface;
use TsRegistrationForm\Generator\CombinationGenerator;

class FormProcessPlaceholder implements ReplaceInterface {

	private array $placeholder;

	public function setPlaceholder(array $placeholder) {
		$this->placeholder = $placeholder;
	}

	public function replace(\Ext_TC_Basic $object, \Ext_TC_Basic $parent = null): string {

		if (
			!$object instanceof \Ext_TS_Inquiry ||
			!($object->type & \Ext_TS_Inquiry::TYPE_BOOKING)
		) {
			return '';
		}

		$options = \Ext_TC_Util::parsePipedString($this->placeholder['modifier']);

		$combination = \Ext_TC_Frontend_Combination::getUsageObjectByKey($options->get('combination'));
		if (!$combination instanceof CombinationGenerator) {
			throw new \InvalidArgumentException('Combination does not exist or is of wrong type.');
		}

		$combination->initCombination(new \Illuminate\Http\Request());
		if ($combination->getForm()->isCreatingBooking()) {
			return '';
		}

		/** @var \TsFrontend\Entity\InquiryFormProcess $process */
		$process = \TsFrontend\Entity\InquiryFormProcess::query()
			->where('inquiry_id', $object->id)
			->where('combination_id', $combination->getCombination()->getId())
			->where('valid_until', '>=', date('Y-m-d'))
			->where(function (\Core\Database\WDBasic\Builder $query) {
				$query->whereNull('submitted')
					->orWhere('multiple', 1);
			})
			->first();

		if ($process === null) {
			$process = new \TsFrontend\Entity\InquiryFormProcess();
			$process->inquiry_id = $object->id;
			$process->combination_id = $combination->getCombination()->getId();
		}

		$process->valid_until = Carbon::now()->addYear()->toDateString(); // Aktuell immer ein Jahr Gültigkeit
		$process->multiple = (int)$options->has('multiple');

		$process->save();

		return $process->key;

	}

	public function isModifierAware(): bool {
		return true;
	}

}
