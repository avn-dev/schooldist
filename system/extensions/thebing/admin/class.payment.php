<?php

use Core\Interfaces\Optionable;

/**
 * @TODO Entität ist nicht gut benannt
 */
class Ext_Thebing_Admin_Payment extends Ext_Thebing_Basic implements Optionable {

	const TYPE_CHEQUE = 'cheque';

	const TYPE_CLEARING = 'clearing';

	const TYPE_PROVIDER_PREFIX = 'provider_';

	/**
	 * @var string
	 */
	protected $_sTable = 'kolumbus_payment_method';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'kpm';

	/**
	 * @var array
	 */
	protected $_aJoinTables = array(
		'schools' => [
			'table' => 'kolumbus_payment_method_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'payment_method_id',
			'on_delete' => 'no_action',
			'class' => Ext_Thebing_School::class
		],
		'reciept_template_customer' => array(
			'table' => 'kolumbus_payment_method_reciept_template_customer',
			'primary_key_field' => 'payment_method_id'
		),
		'reciept_template_agency' => array(
			'table' => 'kolumbus_payment_method_reciept_template_agency',
			'primary_key_field' => 'payment_method_id'
		),
		'reciept_template_creditnote' => array(
			'table' => 'kolumbus_payment_method_reciept_template_creditnote',
			'primary_key_field' => 'payment_method_id'
		)
	);

	/**
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function  __set($sName, $mValue) {

		if(
			strpos($sName, 'reciept_template') !== false &&
			$sName != 'reciept_template_customer' &&
			$sName != 'reciept_template_agency' &&
			$sName != 'reciept_template_creditnote'
		) {
			$iPos = strrpos($sName, '_');
			$iTypeId = substr($sName, $iPos+1);
			$sName = substr($sName, 0, $iPos);
			$aEntries = (array)$this->$sName;

			foreach($aEntries as $iKey=>$aEntry) {
				if($aEntry['payment_type_id'] == $iTypeId) {
					unset($aEntries[$iKey]);
				}
			}

			foreach((array)$mValue as $aTemplateIds) {
				foreach((array)$aTemplateIds as $iTemplateId) {
				$aEntries[] = array(
					'payment_type_id' => $iTypeId,
					'template_id' => $iTemplateId
				);
			}
			}

			$this->$sName = $aEntries;

		} else {
			parent::__set($sName, $mValue);
		}

	}

	/**
	 * @param string $sName
	 * @return array|mixed
	 */
	public function  __get($sName) {

		if(
			strpos($sName, 'reciept_template') !== false &&
			$sName != 'reciept_template_customer' &&
			$sName != 'reciept_template_agency' &&
			$sName != 'reciept_template_creditnote'
		) {
		
			$iPos = strrpos($sName, '_');
			$iTypeId = substr($sName, $iPos+1);
			$sName = substr($sName, 0, $iPos);
			
			$aEntries = $this->$sName;

			$mReturn = array();
			foreach((array)$aEntries as $aEntry) {
				if($aEntry['payment_type_id'] == $iTypeId) {
					$mReturn[] = $aEntry['template_id'];
				}
			}
			
		} else {
			$mReturn = parent::__get($sName);
		}

		return $mReturn;

	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		$aSqlParts['select'] .= ",
			GROUP_CONCAT(DISTINCT `schools`.`school_id`) AS `schools`
		";

	}

	/**
	 * @param bool $bForSelect
	 * @param int[] $aSchoolIds
	 * @return static[]|string[]
	 */
	public static function getPaymentMethods($bForSelect = false, array $aSchoolIds = []) {

		$sJoin = "";

		foreach ($aSchoolIds as $iSchoolId) {
			$iSchoolId = (int)$iSchoolId;
			$sJoin .= " INNER JOIN
				`kolumbus_payment_method_schools` `kpms_{$iSchoolId}` ON
					`kpms_{$iSchoolId}`.`payment_method_id` = `kpm`.`id` AND
					`kpms_{$iSchoolId}`.`school_id` = {$iSchoolId}
			";
		}

		$sSql = "
			SELECT
				`kpm`.*
			FROM
				`kolumbus_payment_method` `kpm`
				{$sJoin}
			WHERE
				`kpm`.`active` = 1 AND (
					`kpm`.`valid_until` = '0000-00-00' OR
					`kpm`.`valid_until` >= NOW()
				)
			GROUP BY
				`kpm`.`id`
			ORDER BY
				`kpm`.`position`
		";

		$aMethods = (array)DB::getQueryRows($sSql);

		$aReturn = [];
		foreach($aMethods as $aMethod) {
			if(!$bForSelect) {
				$aReturn[] = static::getObjectFromArray($aMethod);
			} else {
				$aReturn[$aMethod['id']] = $aMethod['name'];
			}
		}

		return $aReturn;

	}

	public static function findFirstWithType(string $sType, array $aSchoolIds = []): static {

		$aPaymentMethods = collect(Ext_Thebing_Admin_Payment::getPaymentMethods(false, $aSchoolIds));

		$oPaymentMethod = $aPaymentMethods->firstWhere('type', $sType);
		if ($oPaymentMethod == null) {
			$oPaymentMethod = $aPaymentMethods->first();
		}

		return $oPaymentMethod;

	}

	public function getOptionValue(): string|int
	{
		return $this->getId();
	}

	public function getOptionText(): string
	{
		return $this->name;
	}
}
