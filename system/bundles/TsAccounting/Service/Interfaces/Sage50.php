<?php

namespace TsAccounting\Service\Interfaces;

use TsAccounting\Dto\BookingStack\ExportFileContent;

class Sage50 extends AbstractInterface
{
	public function get($name, $default)
	{
		return match ($name) {
			'create_claim_debt' => true,
			'columns_export_full' => array_map(function (array $column) {
				switch ($column['column']) {
					case 'document_date':
						$column['content'] = 'Ymd';
						break;
					case 'amount_if_claim':
					case 'amount_if_position':
					case 'amount_tax':
						$column['content'] = '###0.00|en_US';
						break;
				}
				return $column;
			}, $default),
			default => null
		};
	}

}