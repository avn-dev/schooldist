<?php

namespace TcAccounting\Service\eInvoice\Service\Italy\Format;

class Date {
	
	public function format(\DateTime $dDateTime) {
		return $dDateTime->format('Y-m-d');
	}
	
}

