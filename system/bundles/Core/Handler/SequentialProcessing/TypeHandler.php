<?php

namespace Core\Handler\SequentialProcessing;

abstract class TypeHandler {

	/**
	 * Objekt mit dem TypeHandler ausführen
	 *
	 * @param $oObject
	 * @return mixed
	 */
	abstract public function execute($oObject);

	/**
	 * Prüfen, ob TypeHandler dieses Objekt akzeptiert (instanceof o.ä.)
	 *
	 * @param $oObject
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	abstract public function check($oObject);

}