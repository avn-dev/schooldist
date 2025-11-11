<?php

/**
 * @property int $id
 * @property string $changed
 * @property string $created
 * @property int $editor_id
 * @property int $creator_id
 * @property int $active
 * @property int $group_id
 * @property string $name
 * @property int $days
 * @property string $currency_iso
 * @property int $minimum_value
 */
class Ext_TC_Cancellationconditions_Fee extends Ext_TC_Basic { 
	
	protected $_sTable = 'tc_cancellation_conditions_fees';
	
	protected $_sTableAlias = 'tc_ccf';

	protected $_aFormat = [
		'changed' => ['format' => 'TIMESTAMP'],
		'created' => ['format' => 'TIMESTAMP'],
		'days' => ['validate' => 'INT_POSITIVE', 'required' => true],
		'name' => ['required' => true],
		'tax_category_id' => ['validate' => 'INT_NOTNEGATIVE']
	];

	protected $_aJoinedObjects = [
		'group' => [
			'class' => Ext_TC_Cancellationconditions_Group::class,
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

	public function getCancellationGroup(): Ext_TC_Cancellationconditions_Group {
		return $this->getJoinedObject('group');
	}

	/**
	 * get Select Options
	 * @return array
	 */
	public static function getSelectOptions(){
		$oTemp = new self();
		$aList = $oTemp->getArrayList(true);
		return $aList;
	}

	/**
	 * @return \Illuminate\Support\Collection
	 */
	public function getDynamicFees(): \Illuminate\Support\Collection {
		return collect($this->getJoinedObjectChilds('dynamic_fees', true));
	}

	public function getDynamicAmount(): \Illuminate\Support\Collection {
		$entities = $this->getDynamicFees()
			->sortByDesc(fn ($dynamic) => $dynamic->amount);

		return $entities->map(function (Ext_TC_Cancellationconditions_Dynamic $dynamic) {
			$data = $dynamic->getData();
			$data['selection'] = $dynamic->selection;
			return $data;
		});
	}
	
	/**
	 * TODO - wird das benutzt?
	 * @return type 
	 */
	public static function getCurrencies(){
		
		$aReturn = array();
		$aCurrencies = Ext_TC_Currency::getSelectOptions();
		
		$aTemp = array(
			'EUR',
			'GBP'
		);
		
		foreach((array)$aTemp as $scurrencyIso){
			
			$aReturn[$scurrencyIso] = $aCurrencies[$scurrencyIso];
			
		}
		
		return $aReturn;
		
	}
	
	/**
	 * TODO - wird das benutzt?
	 * @return type 
	 */
	public static function getDynamicFeeTypes(){

		$sTranslationPath = Ext_TC_System_Navigation::tp();
		
		$aReturn =  array(
			1 => L10N::t('alle (gesamt)', $sTranslationPath),
			2 => L10N::t('Kurse', $sTranslationPath)
		);

		
		return $aReturn;
		
	}
	
}
