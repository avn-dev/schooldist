<?php

namespace TcAccounting\Service\eInvoice\Factory;

use Exception;
use TcAccounting\Service\eInvoice\Service\File;
use TcAccounting\Service\eInvoice\Interfaces\Structure;
use TcAccounting\Service\eInvoice\Service;
use TcAccounting\Service\eInvoice\Service\Storage;
use TcAccounting\Service\eInvoice\Exceptions\BuildException;

class BuilderFactory {
	
	/**
	 * @param int $iIndex
	 * @param \TcAccounting\Service\eInvoice\Interfaces\Structure $oStructure
	 * @param int $iDocumentId
	 * @param string $sType
	 * @return \TcAccounting\Service\eInvoice\Service\File
	 * @throws \InvalidArgumentException
	 */
	public function build(int $iIndex, Structure $oStructure, int $iDocumentId, string $sType) : File {
		
		$oFile = new File($iIndex, $iDocumentId, $sType);
		
		switch ($sType) {
			case 'xml_it':
				$oBuilder = new Service\Italy\XmlBuilder();
				break;
			default:
				throw new InvalidArgumentException(sprintf('Unknown build type "%s"', $sType));
		}
		
		try {
			
			$oBuilder->build($oStructure, $oFile);
			
		} catch (BuildException $ex) {
			$oFile->addError($ex->getTranslatedMessage());
		} catch (Exception $ex) {
			__pout($ex->getMessage());
			$oFile->addError(\L10N::t('Es ist ein unerwarteter Fehler aufgetreten!'));
		}
		
		return $oFile;
	}
	
}