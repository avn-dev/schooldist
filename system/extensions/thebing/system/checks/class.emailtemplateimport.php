<?
class Ext_Thebing_System_Checks_EmailTemplateImport extends Ext_Thebing_System_Check {

	public function executeCheck(){
		global $user_data, $_VARS;

		$oClient = new Ext_Thebing_Client($user_data['client']);
		if($oClient->id <= 0){
			$this->_aFormErrors[] = 'No Client Data!';
		}

		$aSchools = $oClient->getSchools();

		foreach((array)$aSchools as $aSchool){
			$sSql = " SELECT
						*
					FROM
						`kolumbus_emails`
					WHERE
						`active` = 1 AND
						`idSchool` = :idSchool";
			$aSql = array();
			$aSql['idSchool'] = $aSchool['id'];
			$aTemplates = DB::getPreparedQueryData($sSql, $aSql);

			foreach((array)$aTemplates as $aTemplate){
				$oEmailTemplate = new Ext_Thebing_Email_Template(0);
				$oEmailTemplate->client_id = $oClient->id;
				$oEmailTemplate->active = 1;
				$oEmailTemplate->name	= $aTemplate['title'];
				$oEmailTemplate->cc		= $aTemplate['cc'];
				$oEmailTemplate->bcc	= $aTemplate['bcc'];
				$oEmailTemplate->html	= 0;

				$aApplications = array(
										'accommodation_communication',
										'accommodation_resources_provider',
										#'admin_users',
										'arrival_list',
										'departure_list',
										'inbox',
										'marketing_agencies',
										'placement_test',
										'simple_view',
										'transfer',
										'transfer_provider',
										'tuition_teacher'
										);

				$oEmailTemplate->schools	= array($aTemplate['idSchool']);
				$oEmailTemplate->applications = $aApplications;
				$oEmailTemplate->languages	= array($aTemplate['language']);
				$sTemp = 'subject_'.$aTemplate['language'];
				$oEmailTemplate->$sTemp = $aTemplate['subject'];
				$sTemp = 'content_'.$aTemplate['language'];
				$oEmailTemplate->$sTemp = $aTemplate['body'];
				$oEmailTemplate->save();


			}

		}

		
		
		// Check zum client speichern damit es nichte rneut gestartet wird
		$this->saveClient();

		return false;

	}

}
