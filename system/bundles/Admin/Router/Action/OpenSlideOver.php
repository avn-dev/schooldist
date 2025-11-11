<?php

namespace Admin\Router\Action;

use Admin\Dto\Component\Parameters;
use Admin\Enums\RouterAction as Target;
use Admin\Enums\Size;
use Admin\Instance;
use Admin\Interfaces\RouterAction;
use Admin\Interfaces\HasTranslations;
use Admin\Interfaces\RouterAction\StorableRouterAction;
use Admin\Router\ComponentContent;
use Admin\Router\Content;
use Admin\Factory\Content as ContentFactory;
use Admin\Traits\RouterAction\OuterClosable;
use Admin\Traits\RouterAction\Resizeable;
use Admin\Traits\RouterAction\Storable;

class OpenSlideOver implements RouterAction, HasTranslations, StorableRouterAction
{
	use Resizeable, OuterClosable, Storable;

	public function __construct(private readonly Content $content) {}

	public function getTarget(): Target
	{
		return Target::SLIDEOVER;
	}

	public function getStorableParameters(Instance $admin): ?Parameters
	{
		if ($this->content instanceof ComponentContent) {
			return $this->content->getParameters();
		}
		return null;
	}

	public function getPayload(Instance $admin): array
	{
		return [
			'content' => $this->content->toArray(),
			'size' => $this->size->value,
			'closable' => $this->closable,
			'outer_closable' => $this->outerClosable,
			'text' => array_map('strip_tags', $this->text ?? [])
		];
	}

	public static function fromPayload(Instance $admin, array $payload): ?static
	{
		$action = new self(ContentFactory::fromArray($payload['content']));

		if (!empty($payload['size'])) {
			$action->size(Size::from($payload['size']));
		}

		if (!empty($payload['closable'])) {
			$action->closable((bool)$payload['closable']);
		}

		if (!empty($payload['outer_closable'])) {
			$action->outerClosable((bool)$payload['outer_closable']);
		}

		if (!empty($payload['text'])) {
			$action->text($payload['text']);
		}

		return $action;
	}

	public function getTranslations(): array
	{
		return $this->content->getTranslations();
	}
}