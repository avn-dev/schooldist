<?php

namespace Gui2\Factory;

use Gui2\Dialog\FactoryInterface;

class DialogFactory
{
	const FACTORY_INSTANCE = 'factory_instance';

	private array|string $factory;

	/**
	 * String: Klasse erbt von Interface \Gui2\Dialog\FactoryInterface, DI möglich
	 * Array: Statischer Methoden-Aufruf (YML-Array)
	 *
	 * @see FactoryInterface
	 */
	public function __construct(array|string $factory)
	{
		$this->factory = $factory;
	}

	public function create(\Ext_Gui2 $gui): \Ext_Gui2_Dialog
	{
		if (is_array($this->factory)) {
			return \Ext_Gui2_Config_Parser::callMethod($this->factory, $gui);
		}

		if (!is_subclass_of($this->factory, FactoryInterface::class)) {
			throw new \InvalidArgumentException('Invalid factory string given for DialogFactory: '.$this->factory);
		}

		/** @var \Gui2\Dialog\FactoryInterface $factory */
		$factory = app()->make($this->factory);

		$dialog = $factory->create($gui);

		$dialog->setOption(self::FACTORY_INSTANCE, $factory);

		return $dialog;
	}

}
