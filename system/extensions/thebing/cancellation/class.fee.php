<?php 

class Ext_Thebing_Cancellation_Fee extends Ext_TC_Cancellationconditions_Fee {

	protected $_sTableAlias = 'kcfe';

	protected $_aJoinedObjects = [
		'group' => [
			'class' => Ext_Thebing_Cancellation_Group::class,
			'key'	=> 'group_id'
		],
		'dynamic_fees' => [
			'class' => Ext_TC_Cancellationconditions_Dynamic::class,
			'key' => 'cancellation_fee_id',
			'orderby' => 'position',
			'type' => 'child',
			'on_delete' => 'cascade'
		]
	];

	/**
	 *
	 * @param <int> $iDays
	 * @param <string> $sCustomerType
	 * @param <int> $iObjectId
	 * @param <bool> $bLoadSelf
	 * @return Ext_Thebing_Cancellation_Fee[] / array
	 */
	public function getMatchingFee($iDays, $sCustomerType, $iObjectId, $iCurrencyId, $bAsObjects=true)
	{
		$aMatchingFees = array();
		$aValidity	= $this->getCancellationGroup()->getValidity($sCustomerType, $iObjectId);

		if(!empty($aValidity))
		{
			$iGroupId = (int)$aValidity['item_id'];
			if($iGroupId>0)
			{
				if($iDays < 0)
				{
					$sWhere = '';
					$sSelect = 'MIN(`days`)';
				}
				else
				{
					$sWhere = ' AND :days >= `days`';
					$sSelect = 'MAX(`days`)';
				}

				$sSql = "
					SELECT
						#table_alias.*
					FROM
						#table #table_alias INNER JOIN
						`tc_cancellation_conditions_groups` `kcg` ON
							`kcg`.`id` = #table_alias.`group_id`
					WHERE
						`kcg`.`id` = :group_id AND
						`kcg`.`active` = 1 AND
						#table_alias.`active` = 1 AND
						(
							#table_alias.`currency_iso` = :currency_iso OR
							#table_alias.`currency_iso` = ''
						) AND
						#table_alias.`days` = (
							SELECT
								".$sSelect."
							FROM
								#table `fee_sub` INNER JOIN
								`tc_cancellation_conditions_groups` `kcg_sub` ON
									`kcg_sub`.`id` = `fee_sub`.`group_id` AND
									`kcg_sub`.`active` = 1
							WHERE
								`kcg_sub`.`id` = :group_id AND
								`fee_sub`.`active` = 1 AND
								(
									`fee_sub`.`currency_iso` = :currency_iso OR
									`fee_sub`.`currency_iso` = ''
								)
								".$sWhere."
						)
				";

				$aResult = (array)DB::getQueryData($sSql, [
					'table'			=> $this->_sTable,
					'table_alias'	=> $this->_sTableAlias,
					'group_id'		=> $iGroupId,
					'days'			=> (int)$iDays,
					'currency_iso'	=> Ext_Thebing_Currency::getInstance($iCurrencyId)->getIso()
				]);

				if ($bAsObjects) {
					$aMatchingFees = array_map(fn ($aData) => self::getObjectFromArray($aData), $aResult);
				} else {
					$aMatchingFees = array_column($aResult, 'id');
				}
			}
		}

		return $aMatchingFees;
	}

	public function setData(array $aData)
	{
		$iId = (int)$aData['id'];
		unset($aData['id']);

		foreach($aData as $sField => $mValue)
		{
			$this->$sField = $mValue;
		}

		$this->_aData['id'] = $iId;

		return $this;
	}

}
