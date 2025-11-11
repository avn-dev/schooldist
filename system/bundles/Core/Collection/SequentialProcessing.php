<?php

namespace Core\Collection;

use \Core\Handler\SequentialProcessing\TypeHandler;

class SequentialProcessing extends \SplObjectStorage {

	/** @var TypeHandler  */
	private $oTypeHandler;

	/**
	 * @inheritdoc
	 */
	public function __construct(TypeHandler $oTypeHandler) {
		$this->oTypeHandler = $oTypeHandler;
	}

	/**
	 * @inheritdoc
	 */
	public function attach(object $oObject, mixed $mData = null) {

		if(!$this->oTypeHandler->check($oObject)) {
			throw new \InvalidArgumentException('Given object is not valid for this collection ('.get_class($this->oTypeHandler).')');
		}

		parent::attach($oObject, $mData);

	}

}