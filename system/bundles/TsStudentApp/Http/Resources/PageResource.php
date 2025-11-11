<?php

namespace TsStudentApp\Http\Resources;

use Illuminate\Container\Container;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use TsStudentApp\AppInterface;

class PageResource extends JsonResource
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
		$item = Arr::only($this->resource, ['key', 'title', 'icon', 'tab', 'refresh_after', 'badge_property', 'badge']);
		$item['title'] = $this->appInterface->t($item['title']);

		// Wenn die Seite eine refresh() hat, wird die Seite bei jedem Aufruf aktualisiert
		$item['has_refresh'] = isset($this->resource['data']) && method_exists($this->resource['data'], 'refresh');

		return $item;
	}
}
