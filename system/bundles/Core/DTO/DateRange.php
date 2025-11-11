<?php

namespace Core\DTO;

/**
 * @TODO Ersetzen mit CarbonPeriod oder spatie/period, wo man auch direkt mit der Periode arbeiten kann
 * @deprecated
 */
class DateRange {

	/** @var \DateTime */
	public $from;

	/** @var \DateTime */
	public $until;

	/**
	 * @param \DateTime $dFrom
	 * @param \DateTime $dUntil
	 */
	public function __construct(\DateTime $dFrom=null, \DateTime $dUntil=null) {
		$this->from = $dFrom;
		$this->until = $dUntil;
	}

}
