<?
class Ext_Thebing_Accommodation_Option {
	
	protected $i_Accommodation = 0;
	protected $i_Saison = 0;
	
	public function __construct($iAccommodation, $iSaison){
		$this->i_Accommodation = $iAccommodation;
		$this->i_Saison = $iSaison;
	}
	
	public function getOption($sOption = 'visible'){
		$sSql = " SELECT 
						`value`
					FROM 
						`kolumbus_saison_accommodation_option`
					WHERE
						`saison_id` = :saison_id AND
						`accommodation_id` = :accommodation_id AND
						`option` = :option ";
		$aSql = array(
						'saison_id'=>$this->i_Saison,
						'accommodation_id'=>$this->i_Accommodation,
						'option'=>$sOption
					);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		if(empty($aResult)){
			return false;
		}
		return $aResult[0]['value'];
	}
	
	public function setOption($sOption, $mValue){
		$sSql = "REPLACE INTO 
						`kolumbus_saison_accommodation_option`
					SET
						`option` = :option ,
						`value` = :value, 
						`saison_id` = :saison_id, 
						`accommodation_id` = :accommodation_id ";
		$aSql = array(
						'saison_id'=>$this->i_Saison,
						'accommodation_id'=>$this->i_Accommodation, 
						'option'=>$sOption,
						'value'=>$mValue
					);			
		DB::executePreparedQuery($sSql,$aSql);
	}
	
	public function __get($sOption){
		return $this->getOption($sOption);
	}
	
	public function __set($sOption, $mValue){
		return $this->setOption($sOption, $mValue);
	}
	
	
}
