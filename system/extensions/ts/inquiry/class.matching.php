<?php

/**
 * @property int $id
 * @property int $inquiry_id
 * @property int $cats
 * @property int $dogs
 * @property int $pets
 * @property int $smoker WEICHES KRITERIUM (FAMILIE)
 * @property int $distance_to_school
 * @property int $air_conditioner
 * @property int $bath
 * @property int $family_age
 * @property string $residential_area
 * @property int $family_kids
 * @property int $internet
 * @property int $acc_vegetarian
 * @property int $acc_muslim_diat
 * @property int $acc_smoker HARTES KRITERIUM (SCHÜLER)
 * @property string $acc_allergies
 * @property string $acc_comment
 * @property string $acc_comment2
 * @property string $accommodation_data
 */
class Ext_TS_Inquiry_Matching extends Ext_Thebing_Basic {

	use Tc\Traits\Placeholder;
	
	// Tabellenname
	protected $_sTable = 'ts_inquiries_matching_data';

	protected $_sTableAlias = 'ts_i_m_d';
	
	protected $_aFormat = array(

	);
	
	protected $_sPlaceholderClass = \Ts\Service\Placeholder\Booking\Matching::class;
	
	public function save($bLog = true) {
		
		if(
			$this->isAllEmpty()
		) {
			if(
				$this->id > 0
			) {
				$this->delete();
				return;
			}else{
				return true;
			}
		}

		parent::save($bLog);

	}
	
	public function delete($bLog = true) {
		
		$sSql = "
			DELETE FROM
				#table
			WHERE
				`id` = :data_id
		";
		
		$aSql = array(
			'table'		=> $this->_sTable,
			'data_id'	=> (int)$this->id
		);
		
		$bSuccess = DB::executePreparedQuery($sSql, $aSql);
		
		// Log entry
		if($bLog && $bSuccess) {
			$this->log(Ext_Thebing_Log::DELETED, $this->_aData);
		}

	}
	
	public function isAllEmpty() {

		$aData = $this->_aData;
		unset(
			$aData['id'],
			$aData['inquiry_id']
		);
		
		foreach($aData as $mValue){
			if(!empty($mValue)){
				return false;
			}
		}
		
		return true;
	}

}