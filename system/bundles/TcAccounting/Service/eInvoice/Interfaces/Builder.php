<?php

namespace TcAccounting\Service\eInvoice\Interfaces;

use TcAccounting\Service\eInvoice\Service\File;

interface Builder {
	
	public function build(Structure $oStructure, File $oFile) : File;
	
}