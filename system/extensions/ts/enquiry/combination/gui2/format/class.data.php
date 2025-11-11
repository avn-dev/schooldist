<?php

class Ext_TS_Enquiry_Combination_Gui2_Format_Data extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var array
	 */
	private $callPath;

	/**
	 * @var string|null
	 */
	private $format;

	public function __construct(string $type, string $callPath, string $format = null) {

		$this->type = $type;
		$this->callPath = json_decode($callPath);
		$this->format = $format;

	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		// Es ist schneller, einfach die Objekte zu erzeugen, als das Rad zum wiederholten Male im Query neu zu erfinden
		$objects = $this->createObjects($aResultData[$this->type.'_ids']);

		$data = array_map(function (Ext_TS_Inquiry_Journey_Service $service) {
			return $this->formatValue($this->callMethod($service));
		}, $objects);

		return join('<br>', $data);

	}

	private function callMethod($object) {

		$method = reset($this->callPath);
		$args = array_slice($this->callPath, 1);
		$method = new ReflectionMethod($object, $method);

		return $method->invokeArgs($object, $args);

	}

	private function formatValue($value) {

		if ($this->format) {
			$format = new $this->format;
			return $format->format($value);
		}

		return $value;

	}

	private function createObjects(string $ids = null) {

		if ($ids === null) {
			return [];
		}

		$ids = explode(',', $ids);

		return array_map(function (string $id) {
			return $this->createObject($id);
		}, $ids);

	}

	private function createObject(string $id): Ext_TS_Inquiry_Journey_Service {

		switch ($this->type) {
			case 'course':
				return Ext_TS_Inquiry_Journey_Course::getInstance($id);
			case 'accommodation':
				return Ext_TS_Inquiry_Journey_Accommodation::getInstance($id);
			case 'insurance':
				return Ext_TS_Inquiry_Journey_Insurance::getInstance($id);
			default:
				throw new DomainException('Unknown type: '.$this->type);
		}

	}

}
