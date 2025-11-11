<?php

namespace Core\Traits;

/**
 * @TODO Nach Core\Traits\WdBasic verschieben
 */
trait UniqueKeyTrait {

	/**
	 * Spalte die geprüft werden muss (muss mit dem WDBasic-Objekt übereinstimmen)
	 * Default: key
	 *
	 * @var string
	 */
	protected string $uniqueKeyColumn = 'key';

	/**
	 * Länge des einzigartigen Schlüssels der generiert wird festlegen
	 * Default: 8
	 *
	 * @var int
	 */
	protected int $uniqueKeyLength = 8;

	/**
	 * Generiert einen einzigartigen Schlüssel
	 *
	 * @return string
	 */
	public function getUniqueKey() {

		$sKey = '';
		$bAvailable = true;

		while($bAvailable === true) {
 
			$sKey = \Util::generateRandomString($this->uniqueKeyLength);

			if($this instanceof \WDBasic) {
				$oTmpObject = $this->getRepository()->findOneBy(
					[$this->uniqueKeyColumn => $sKey]
				);
				// Wenn das Objekt eine Id größer als 0 hat, dann gibt es diesen Key bereits,
				// es daher ein neuer Key erzeugt werden.
				$bAvailable = $oTmpObject->id > 0;

			} else {
				throw new \LogicException('You need a wdbasic object!');
			}
		}

		return $sKey;

	}

}