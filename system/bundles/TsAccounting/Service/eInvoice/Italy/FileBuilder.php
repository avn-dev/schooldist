<?php

namespace TsAccounting\Service\eInvoice\Italy;

use TcAccounting\Service\eInvoice\Service\BuilderResponse;
use TcAccounting\Service\eInvoice\Exceptions\BuildException;
use TcAccounting\Service\eInvoice\Factory\BuilderFactory;

/**
 * Generiert für die zugewiesen Dokumente die XML-Dateien
 */
class FileBuilder {
	/**
	 * @var bool 
	 */
	private $bFinal = false;
	/**
	 * @var \Ext_Thebing_Inquiry_Document[]
	 */
	private $aDocuments = [];

	public function __construct(bool $bFinal) {
		$this->bFinal = $bFinal;
	}
	
	/**
	 * Dokument hinzufügen
	 * 
	 * @param \Ext_Thebing_Inquiry_Document $oDocument
	 */
	public function addDocument(\Ext_Thebing_Inquiry_Document $oDocument) : void {
		$this->aDocuments[] = $oDocument;
	}
	
	/**
	 * Erstellt für jedes Dokument eine XML-Datei
	 * 
	 * @return \TcAccounting\Service\eInvoice\Service\BuilderResponse
	 */
	public function generate() : BuilderResponse {
		
		$oBuilderResponse = new BuilderResponse();
		$oFactory = new BuilderFactory();
		
		foreach($this->aDocuments as $iIndex => $oDocument) {

			if($oBuilderResponse->hasErrors()) {
				// Bei einem vorherigen Dokument ist ein Fehler aufgetreten, daher
				// kann die Generierung erstmal abgebrochen werden
				break;
			}

			try {
				// Generelle Daten für das XML zusammenstellen
				$oStructure = (new \TsAccounting\Service\eInvoice\Italy\XmlStructureBuilder())
					->build($oDocument);
				
				// XML generieren
				$oFile = $oFactory->build(($iIndex + 1), $oStructure, $oDocument->getId(), 'xml_it');

				$oBuilderResponse->addFile($oFile);
				
			} catch(BuildException $e) {
				$oBuilderResponse->addError($e->getTranslatedMessage());
			} catch(\Exception $e) {
				$oBuilderResponse->addError($e->getMessage());
			}
		
		}

		if($oBuilderResponse->hasErrors()) {
			// Fehler aufgetreten - Alle bisher generierten Dateien löschen
			$oBuilderResponse->cleanUp();
		} else if($this->bFinal) {
			// Beim finalen Export werden die Dateien für die Historie gespeichert
			$oBuilderResponse->backup();
		}

		return $oBuilderResponse;
	}
	
}
