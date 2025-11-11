<?php

namespace TsStudentApp\Http\Resources;

use Illuminate\Container\Container;
use Illuminate\Http\Resources\Json\JsonResource;
use TsStudentApp\AppInterface;

/**
 * @mixin \Ext_TS_Inquiry
 */
class InquiryResource extends JsonResource
{

	private AppInterface $appInterface;

	public function __construct($resource)
	{
		parent::__construct($resource);
		$this->appInterface = Container::getInstance()->make(AppInterface::class);
	}

	/**
	 * @param $request
	 * @return array
	 * @throws \Exception
	 */
	public function toArray($request)
	{
		return [
			'id' => (int)$this->getId(),
			'label' => $this->createInquiryLabel(), // TODO Entfernen wenn alle Apps >= 2.1.0
			'schoolName' => $this->getSchool()->ext_1,
			'school_name' => $this->getSchool()->ext_1,  // TODO Abwärtskompatibilität (< 2.1.0)
			'from' => $this->getServiceFrom(true)?->toIso8601String(),
			'until' => $this->getServiceUntil(true)?->toIso8601String()
		];
	}

	/**
	 * Generiert ein Label für die Buchung
	 *
	 * @return string
	 */
	private function createInquiryLabel() {

		$school = $this->getSchool();

		$label = $this->appInterface->t('Booking').': ';

		if(
			$this->service_from !== '0000-00-00' &&
			$this->service_until !== '0000-00-00'
		) {
			$label .= $this->appInterface->formatDate($this->service_from, $school).' – '.$this->appInterface->formatDate($this->service_until, $school);
		} else {
			$label .= $this->appInterface->formatDate($this->created, $school);
		}

		$label .= ', '.$school->getName();

		return $label;
	}

}
