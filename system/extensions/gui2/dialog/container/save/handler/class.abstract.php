<?php

abstract class Ext_Gui2_Dialog_Container_Save_Handler_Abstract {

	/**
	 * @var WDBasic
	 */
	protected $parent;

	/**
	 * @var WDBasic
	 */
	protected $child;

	/**
	 * @var string
	 */
	protected $containerType;

	/**
	 * @var array
	 */
	protected $containerOptions = [];

	/**
	 * @var int
	 */
	protected $elementId = 0;

	public function setParentObject(WDBasic $parent) {
		$this->parent = $parent;
	}

	public function setChildObject(WDBasic $child) {
		$this->child = $child;
	}

	public function setContainerType($containerType) {
		$this->containerType = $containerType;
	}

	public function setContainerOptions(array $containerOptions) {
		$this->containerOptions = $containerOptions;
	}

	public function setElementId($elementId) {
		$this->elementId = $elementId;
	}

	abstract public function handle();

}