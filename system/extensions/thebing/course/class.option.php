<?
class Ext_Thebing_Course_Option {
	
	protected $i_Course = 0;
	protected $i_Saison = 0;
	
	public function __construct($iCourse, $iSaison){
		$this->i_Course = $iCourse;
		$this->i_Saison = $iSaison;
	}
	
	public function getOption($sOption = 'visible'){
		$sSql = " SELECT 
						`value`
					FROM 
						`kolumbus_saison_course_option`
					WHERE
						`saison_id` = :saison_id AND
						`course_id` = :course_id AND
						`option` = :option ";
		$aSql = array(
						'saison_id'=>$this->i_Saison,
						'course_id'=>$this->i_Course,
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
						`kolumbus_saison_course_option`
					SET
						`option` = :option ,
						`value` = :value, 
						`saison_id` = :saison_id, 
						`course_id` = :course_id "; 
		$aSql = array(
						'saison_id'=>$this->i_Saison,
						'course_id'=>$this->i_Course,
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
