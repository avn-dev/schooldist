<?php

namespace TcAccounting\Service\eInvoice\Service\Italy;

use TcAccounting\Service\eInvoice\Interfaces;
use TcAccounting\Service\eInvoice\Service\Italy\DTO\Transmitter;
use TcAccounting\Service\eInvoice\Service\Italy\DTO\InvoiceContact;
use TcAccounting\Service\eInvoice\Service\Italy\DTO\Document;
use TcAccounting\Service\eInvoice\Service\Italy\DTO\Address;

class XmlStructure implements Interfaces\Structure {
	
	private $oTransmitter;
	
	private $oInvoiceContact;
	
	private $oInvoiceAddress = [];
	
	private $aExtraNodes = [];
	
	private $oDocument;
	
	public function setTransmitter(Transmitter $oTransmitter) : void {
		$this->oTransmitter = $oTransmitter;
	}
	
	/**
	 * @return \TcAccounting\Service\eInvoice\Service\Italy\Transmitter
	 */
	public function getTransmitter() : Transmitter {
		return $this->oTransmitter;
	}
	
	public function setInvoiceContact(InvoiceContact $oInvoiceContact) : void {
		$this->oInvoiceContact = $oInvoiceContact;
	}
	
	/**
	 * @return \TcAccounting\Service\eInvoice\Service\Italy\InvoiceContact
	 */
	public function getInvoiceContact() : InvoiceContact {
		return $this->oInvoiceContact;
	}
	
	public function setDocument(Document $oDocument) : void {
		$this->oDocument = $oDocument;
	}
	
	/**
	 * @return array
	 */
	public function getDocument() : Document {
		return $this->oDocument;
	}
	
	public function setCodex() {
		$this->sCodex = $sCodex;
	}
	
	public function setInvoiceAddress(Address $oAddress) {
		$this->oInvoiceAddress = $oAddress;
	}
	
	public function getInvoiceAddress() : Address {
		return $this->oInvoiceAddress;
	}
	
	public function addExtraNode(string $sNode, string $sValue) : void {
		$this->aExtraNodes[$sNode] = $sValue;
	}
	
	public function getExtraNodes() : array {
		return $this->aExtraNodes;
	}
}