<?php

namespace Gui2\Dialog;

use Gui2\Factory\DialogFactory;

final class LazyDialogProxy implements \Gui2\Dialog\DialogInterface
{
	private string|array $factory;

	/**
	 * @var \Ext_Gui2_Dialog
	 */
	private \Ext_Gui2_Dialog $dialog;

	private $prepared = false;

	public function __construct(string|array $factory)
	{
		$this->factory = $factory;
	}

	public function create(\Ext_Gui2 $gui): \Ext_Gui2_Dialog
	{
		if (isset($this->dialog)) {
			return $this->dialog;
		}

		$factory = new DialogFactory($this->factory);

		$this->dialog = $factory->create($gui);

		return $this->dialog;
	}

	public function prepare(\Ext_Gui2 $gui)
	{
		if ($this->prepared) {
			return;
		}

		/** @var \Gui2\Dialog\LazyFactoryInterface $factory */
		$factory = $this->dialog->getOption(\Gui2\Factory\DialogFactory::FACTORY_INSTANCE);
		$factory?->prepare($gui);
		$this->prepared = true;
	}

	public function __get(string $name)
	{
		return $this->dialog->{$name};
	}

	public function __set(string $name, $value): void
	{
		$this->dialog->{$name} = $value;
	}

	public function __call($name, $arguments)
	{
		$method = new \ReflectionMethod($this->dialog, $name);
		return $method->invokeArgs($this->dialog, $arguments);
	}

	public function __sleep(): array
	{
		return ['factory'];
	}
}
