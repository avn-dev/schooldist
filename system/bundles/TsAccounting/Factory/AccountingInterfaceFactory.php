<?php

namespace TsAccounting\Factory;

use TsAccounting\Service\Interfaces;

class AccountingInterfaceFactory
{
	public static function get(\TsAccounting\Entity\Company $company): ?Interfaces\AbstractInterface
	{
		
		return match ($company->interface) {
			'sage50' => new Interfaces\Sage50($company),
			'xero' => new Interfaces\Xero($company),
			'datev' => new Interfaces\Datev($company),
			'git' => new Interfaces\Git($company),
			default => null,
		};
	}

}