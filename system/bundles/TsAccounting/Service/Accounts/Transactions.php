<?php

namespace TsAccounting\Service\Accounts;

class Transactions {

	public static function logger() {
		return \Log::getLogger('ts_accounting', 'Transactions');
	}

	public static function add(string $sAccountType, int $iAccountId, string $sType, int $iTypeId, float $fAmount, string $sCurrencyIso='EUR', \Carbon\Carbon $dDueDate=null) {
		
		$aData = [
			'account_type' => $sAccountType,
			'account_id' => $iAccountId,
			'amount' => $fAmount,
			'currency_iso' => $sCurrencyIso,
			'type' => $sType,
			'type_id' => $iTypeId,
		];
		
		if($dDueDate !== null) {
			$aData['due_date'] = $dDueDate->toDateString();
		}
		
		\DB::insertData('ts_accounts_transactions', $aData);

	}

	public static function delete(string $type, int $typeId, string $reason) {

		self::logger()->info('Delete transactions', ['type' => $type, 'type_id' => $typeId, 'reason' => $reason]);

		\DB::executePreparedQuery("DELETE FROM `ts_accounts_transactions` WHERE `type` = :type AND `type_id` = :type_id", [
			'type' => $type,
			'type_id' => $typeId
		]);

	}

}
