<?php

namespace Admin\Factory;

use Admin\Dto\Component\Parameters;
use Admin\Enums\ContentType;

class Content
{
	public static function iframe(string $url = '', string $html = ''): \Admin\Router\Content
	{
		$payload = (!empty($html)) ? ['html' => $html] : ['url' => $url];
		return new \Admin\Router\Content(ContentType::IFRAME, $payload);
	}

	public static function html(string $url): \Admin\Router\Content
	{
		return new \Admin\Router\Content(ContentType::HTML, ['url' => $url]);
	}

	public static function component(string $apiKey, string $vueComponent, array $payload = [], Parameters $parameters = null, bool $initialize = true): \Admin\Router\ComponentContent
	{
		$content = new \Admin\Router\ComponentContent(['api_key' => $apiKey, 'component' => $vueComponent, 'payload' => $payload], $initialize);

		if ($parameters) {
			$content->parameters($parameters);
		}

		return $content;
	}

	public static function fromArray(array $data): ?\Admin\Router\Content
	{
		if ($data['type'] === ContentType::COMPONENT) {
			return \Admin\Router\ComponentContent::fromArray($data);
		}

		return \Admin\Router\Content::fromArray($data);
	}

}