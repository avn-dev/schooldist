<?php

namespace TcAccounting\Service\eInvoice\Service\Italy\Format;

class Amount {
	
	public function format($fAmount) {

		$fAmount = round((float)$fAmount, 2);
		
		if(strpos($fAmount, '.') === false) {
			$fAmount .= '.00';
		}
		
		return number_format($fAmount, 2, '.', '');
	}
	
}

