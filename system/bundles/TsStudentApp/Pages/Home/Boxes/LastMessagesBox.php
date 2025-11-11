<?php

namespace TsStudentApp\Pages\Home\Boxes;

use TsStudentApp\AppInterface;
use TsStudentApp\Components\Component;
use TsStudentApp\Components\Container;
use TsStudentApp\Facades\PropertyKey;

class LastMessagesBox implements Box
{
	const KEY = 'last-messages';

	public function __construct(private AppInterface $appInterface) {}

	public function generate(): ?Component
	{
		$property = $this->appInterface->getProperty(
			PropertyKey::generate(\TsStudentApp\Properties\LastMessages::PROPERTY, ['limit' => 3])
		);

		$container = new Container();
		$container->add(\TsStudentApp\Facades\Component::Heading($this->appInterface->t('Last messages')));
		$container->add(\TsStudentApp\Facades\Component::MessagesList()->property($property));

		return $container;
	}
}