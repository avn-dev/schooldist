<?php

namespace TsCompany\Gui2\Dialog;

abstract class AbstractDialog {

	protected $gui2;

	/**
	 * 
	 * @var \Ext_Gui2_Dialog
	 */
	protected $dialog;

	protected $entity;

	protected $currentElement;

	public function __construct(\Ext_Gui2 $gui2) {
		$this->gui2 = $gui2;
	}

	abstract public function getTitle(): string;

	abstract public function getEditTitle(): string;

	abstract public function build(): void;

	public function buildDialog(\Ext_Gui2_Dialog $dialog, \WDBasic $entity) {

		$this->dialog = $dialog;
		$this->entity = $entity;

		$this->currentElement = $this->dialog;

		$this->build();

		return $dialog;
	}

	protected function t(string $translate): string {
		return $this->gui2->t($translate);
	}

	protected function tab(string $title, \Closure $closure) {

		if($this->currentElement instanceof \Ext_Gui2_Dialog_Tab) {
			throw new \LogicException('Don\'t create tab inside an other tab');
		}

		$tab = $this->dialog->createTab($title);

		$this->currentElement = $tab;
		$closure($tab);
		$this->currentElement = $this->dialog;

		$this->dialog->setElement($tab);
	}

	protected function heading(string $heading, string $type = 'h4') {

		$element = $this->dialog->create($type);
		$element->setElement($heading);
		$this->currentElement->setElement($element);

		return $this;
	}

}
