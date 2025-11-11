<?php

namespace TsStudentApp\Http\Resources;

use Core\Helper\BundleConfig;
use Illuminate\Container\Container;
use Illuminate\Http\Resources\Json\JsonResource;
use TsStudentApp\Facades\PropertyKey;
use TsStudentApp\Properties\Property;

/**
 * @mixin Property
 */
class PropertyResource extends JsonResource
{
	private BundleConfig $bundleConfig;

	public function __construct($resource)
	{
		parent::__construct($resource);
		$this->bundleConfig = Container::getInstance()->make(BundleConfig::class);
	}

	/**
	 * @param $request
	 * @return array
	 * @throws \Exception
	 */
	public function toArray($request)
	{
		[$rawKey] = PropertyKey::match($this->property());

		$config = $this->bundleConfig->get('properties.'.$rawKey);

		$data = [
			'property' => $this->property(),
			'value' => $this->data(),
			'destroy' => $this->destroy()
		];

		if (!$data['destroy']) {
			$refreshAfter = $config['refresh_after'] ?? null;
			if ((int)$refreshAfter > 0) {
				$data['refresh_after'] = $refreshAfter * 1000; // Millisekunden
			}
		}

		return $data;
	}

}
