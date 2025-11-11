<?


class Ext_Thebing_System_Checks_Defaultexporttemplates extends GlobalChecks {
	
	public function isNeeded(){
		global $user_data;
		
		// wenn bereits ausgeführt
		if($this->checkClient()){
			return false;
		}
		$oClient = new Ext_Thebing_Client($user_data['client']);
		$aSchools = $oClient->getSchools(true);
		
		// wenn keine schulen
		if(count($aSchools) <= 0){
			return false;
		}

		return true;
	}

	/**
	 * Dont Set the Check to active = 0
	 */
	public function updateCheck(){

	}
	
	public function modifyCheckData(){
		global $user_data;
		
		//$oClient = new Ext_Thebing_Client($user_data['client']);
		//$aMaster = $oClient->getMasterUser();

		//if($aMaster['id'] != $user_data['id'] && empty($this->_aFormErrors)){
		//	$this->_aFormErrors[] = 'Only your master user has access!';
		//	$this->bError = true;
		//	return false;
		//}
		
		return true;
		
	}
	
	public function checkClient(){
		global $user_data;
		$sSql = " SELECT 
						* 
					FROM 
						`kolumbus_system_checks` 
					WHERE 
						`check_id` = :check_id AND
						`client_id` = :client_id AND 
						`status` = 1";
		$aSql = array(
						'check_id' => (int)$this->_aCheck['id'],
						'client_id' =>(int)$user_data['client']);		
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		if(empty($aResult)){
			return false;
		}
		return true;
	}
	
	public function saveClient(){
		global $user_data;
		$sSql = " INSERT INTO
						`kolumbus_system_checks` 
					SET 
						`check_id` = :check_id,
						`client_id` = :client_id,
						`status` = 1";
		$aSql = array(
						'check_id' => (int)$this->_aCheck['id'],
						'client_id' =>(int)$user_data['client']);		
		DB::executepreparedQuery($sSql, $aSql);
	}
	
	public function executeCheck(){
		global $user_data, $_VARS;

		if($user_data['client'] <= 0){
			return false;
		}

		$oClient = new Ext_Thebing_Client($user_data['client']);

		$aSchools_ = $oClient->getSchools(true);
		$aSchools = array();
		foreach($aSchools_ as $iSchool => $sSchool){
			$aSchools[] = $iSchool;
		}

		## JE KURS ##

			$aElements = array();

			$aElements['block_name'] = 1;
			$aElements['days'] = 1;
			$aElements['niveau'] = 1;
			$aElements['room'] = 1;
			$aElements['school_name'] = 1;
			$aElements['table_column_age'] = 1;
			$aElements['table_column_last_schoolday'] = 1;
			$aElements['table_column_name'] = 1;
			$aElements['table_column_number'] = 1;
			$aElements['table_column_weekdays'] = 1;
			$aElements['teacher'] = 1;
			$aElements['time'] = 1;
			$aElements['week'] = 1;

			$oTemplate = new Ext_Thebing_Export_Template(0);

			$oTemplate->client_id = $oClient->id;
			$oTemplate->active = 1;
			$oTemplate->name = 'Tuition Overview - Course';
			$oTemplate->type = 'export_tuition_each_course';
			$oTemplate->page_format = 'a4_q';
			$oTemplate->page_format_width = 297;
			$oTemplate->page_format_height = 210;
			$oTemplate->font_size = 10;
			$oTemplate->page_border_top = 10;
			$oTemplate->page_border_right = 10;
			$oTemplate->page_border_left = 10;
			$oTemplate->page_border_bottom = 10;
			$oTemplate->save();
			$oTemplate->saveSchools($aSchools);
			$oTemplate->saveElements($aElements);

		## ENDE Je Kurs ##

		## JE Schüler ##

			$aElements = array();

			$aElements['color_legend'] = 1;
			$aElements['course'] = 1;
			$aElements['customer'] = 1;
			$aElements['school_name'] = 1;
			$aElements['table_element_block_name'] = 1;
			$aElements['table_element_coursetype'] = 1;
			$aElements['table_element_date'] = 1;
			$aElements['table_element_niveau'] = 1;
			$aElements['table_element_room'] = 1;
			$aElements['table_element_teacher'] = 1;
			$aElements['table_element_time'] = 1;
			$aElements['week'] = 1;

			$oTemplate = new Ext_Thebing_Export_Template(0);

			$oTemplate->client_id = $oClient->id;
			$oTemplate->active = 1;
			$oTemplate->name = 'Tuition Overview - Student';
			$oTemplate->type = 'export_tuition_each_customer';
			$oTemplate->page_format = 'a4_q';
			$oTemplate->page_format_width = 297;
			$oTemplate->page_format_height = 210;
			$oTemplate->font_size = 10;
			$oTemplate->page_border_top = 10;
			$oTemplate->page_border_right = 10;
			$oTemplate->page_border_left = 10;
			$oTemplate->page_border_bottom = 10;
			$oTemplate->save();
			$oTemplate->saveSchools($aSchools);
			$oTemplate->saveElements($aElements);

		## ENDE Je Schüler ##

		## Raumplan ##

			$aElements = array();

			$aElements['school_name'] = 1;
			$aElements['table_column_coursetype'] = 1;
			$aElements['table_column_niveau'] = 1;
			$aElements['table_column_room'] = 1;
			$aElements['table_column_teacher'] = 1;
			$aElements['timeframe'] = 1;
			$aElements['table_column_weekdays'] = 1;
			$aElements['table_column_time'] = 1;

			$oTemplate = new Ext_Thebing_Export_Template(0);

			$oTemplate->client_id = $oClient->id;
			$oTemplate->active = 1;
			$oTemplate->name = 'Tuition Overview - Roomplan';
			$oTemplate->type = 'export_tuition_roomplan';
			$oTemplate->page_format = 'a4_q';
			$oTemplate->page_format_width = 297;
			$oTemplate->page_format_height = 210;
			$oTemplate->font_size = 10;
			$oTemplate->page_border_top = 10;
			$oTemplate->page_border_right = 10;
			$oTemplate->page_border_left = 10;
			$oTemplate->page_border_bottom = 10;
			$oTemplate->save();
			$oTemplate->saveSchools($aSchools);
			$oTemplate->saveElements($aElements);

		## ENDE Raumplan##

		$this->saveClient();

	}
	
	protected function prepareExecuteCheck(){
		global $_VARS;
		// If no Check found, dont try to execute
		// If you land on this Method and it dosn´t exist an Check ist must give an Error in your Script!
		if(empty($this->_oUsedClass->_aCheck)){
			$this->_oUsedClass->_aFormErrors[] = L10N::t('No Check Found!');
			return false;
		}

		// if after sending the Form the Check is has Changed trow an Error
		// ( e.g 2 Users make the check to the same time an it give an second Check )
		if($_VARS['check_id'] != $this->_oUsedClass->_aCheck['id']){
			$this->_oUsedClass->_aFormErrors[] = L10N::t('You send Data for an other Check, please go back');
			return false;
		}
		
		$this->_oUsedClass->executeCheck();
		
		return true;
	}
}
