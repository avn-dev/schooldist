<?
class Ext_Thebing_System_Checks_Customernumber extends GlobalChecks {
		
		
	public function isNeeded(){

		if($this->checkClient()){
			return false;
		}
		return true;
	}

	public function modifyCheckData(){
		global $user_data;
		
		$oClient = new Ext_Thebing_Client($user_data['client']);
		$aMaster = $oClient->getMasterUser();

		if($aMaster['id'] != $user_data['id'] && empty($this->_aFormErrors)){
			$this->_aFormErrors[] = 'Only your master user has access!';
			$this->bError = true;
			return false; 
		}
		
		
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
						'check_id' =>(int)$this->_aCheck['id'],
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
		
		$bSchool = false;
		$bClient = false;

		
		$oClient = new Ext_Thebing_Client($user_data['client']);
		if($oClient->id <= 0){
			$this->_aFormErrors[] = 'No Client Data!';
		}
		
		if(
			empty($_VARS['school']) && 
			empty($_VARS['client'])
		){
			// Pro Schule aber noch keine schulen forhanden => true
			$bSchool = true;
		} else if(!empty($_VARS['school'])){
			foreach($_VARS['school'] as $iSchool => $aData){
				if(
					empty($aData['customer_number_start']) ||
					empty($aData['customer_number_format'])
				){
						$this->_aFormErrors[] = 'Please fill out all fields!';
						return false; 
				}
			}
			$oClient->customernumber_for_schools = 1;
			$oClient->save();
			$bSchool = true;
		} else {
			if(
				empty($_VARS['client']['customer_number_start']) ||
				empty($_VARS['client']['customer_number_format'])
			){
					$this->_aFormErrors[] = 'Please fill out all fields!';
					return false; 
			}
			$oClient->customernumber_for_schools = 0;
			$oClient->customernumber_start = $_VARS['client']['customer_number_start'];
			$oClient->customernumber_format = $_VARS['client']['customer_number_format'];
			$oClient->save();
			$bClient = true;
		}
		
		if($bSchool){
			$aSchools = $oClient->getSchools(false);

			foreach((array)$aSchools as $aSchool){
				
				$oSchool = Ext_Thebing_School::getInstance( $aSchool['id']);
				if($oSchool->id <= 0){
					$this->_aFormErrors[] = 'No School ID!';
					return false;
				} else {
					$oSchool->ext_331 = $_VARS['school'][$aSchool['id']]['customer_number_start'];
					$oSchool->ext_332 = $_VARS['school'][$aSchool['id']]['customer_number_format'];
					$oSchool->save();
				}
				
			}
			
		}

		# customer_db_1 sichern (1mal pro mandant) #
		
			$sTable = 'customer_db_1';
			$sBackupTable = '__'.date('Y_m_d').'_'.$sTable.'_client'.$oClient->id;

			$sDrop = "DROP TABLE IF EXISTS `".$sBackupTable."`";
			DB::executeQuery($sDrop);

			$sSql = " SHOW CREATE TABLE ".$sTable;
			$aT = DB::getQueryData($sSql);
			$sCreate = $aT[0]['Create Table'];
			$sCreate = str_replace($sTable, $sBackupTable, $sCreate);
			$bSuccess = DB::executeQuery($sCreate);
			
			if(!$bSuccess){
				//$this->_aFormErrors[] = 'Error: Create Backup for '.$sTable;
				//return false;
			}
			
			$sSql = " INSERT INTO #backuptable SELECT * FROM #table ";
			$aSql = array('table'=>$sTable,'backuptable'=>$sBackupTable);
			$bSuccess = DB::executePreparedQuery($sSql, $aSql);
			if(!$bSuccess){
				//$this->_aFormErrors[] = 'Error: Insert Data for Backup '.$sBackupTable;
				//return false;
			}
			
			# feld customerNumber anlegen # 
			try {
				$sSql = " ALTER TABLE `customer_db_1` ADD `customerNumber` VARCHAR( 255 ) NOT NULL ";
				$bSuccess = DB::executeQuery($sSql);
				if(!$bSuccess){ 
					// fehler unterdrücken da es mehrmals gestartet wird ( pro mandant )
					//$this->_aFormErrors[] = 'Error: Create Field customerNumber in customer_db_1';
					//return false;
				}
			} catch(Exception $e) {
			
			}
			# ende feld customerNumber anlegen # 
			
			
			// Eintragen der Kundennummern abhänig von der einstellung pro Mandant/Schule und der startzahl/formatierung
			
		# ende der sicherung #

		if($bSchool){
			$aSchools = $oClient->getSchools(false);
			foreach((array)$aSchools as $aSchool){
				$this->saveAllCustomerNumbers($oClient->id, $aSchool['id']);
			}
			
		} else {
	
			$this->saveAllCustomerNumbers($oClient->id);
		}
		
		$this->saveClient();
		
		return false;
		
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
	
	public function updateCheck(){
		
	}
	
	public function printFormContent(){
		global $_VARS, $user_data;
		
		$oClient = new Ext_Thebing_Client($user_data['client']);
		
		if($_VARS['task'] == 'for_client'){
?>		
			<div style="width:350px;float:left;">
				<label><?=L10N::t('Customernumber start (e.g 0001)')?>: </label>
			</div>
			<div style="float:left;">
				<input type="text" value="" name="client[customer_number_start]" />
			</div>
			<div style="clear:both;"></div>
			<div style="width:350px;float:left;">
				<label><?=L10N::t('Customernumber format (e.g Customer-%count)')?>: </label>
			</div>
			<div style="float:left;">
				<input type="text" value="" name="client[customer_number_format]" />
			</div>
			<div style="clear:both;"></div>
			<div style="width:100%; text-align:right;">
				<input type="submit" value="<?=L10N::t('write Customernumbers')?>" class="btn" />
			</div>
<?
		} else if ($_VARS['task'] == 'for_school') {
		
			$aSchools = $oClient->getSchools(false);
?>
			<br/>
<?
			foreach($aSchools as $aSchool){
?>		
				<h3><?=$aSchool['ext_1']?></h3>
				<div style="width:350px;float:left;">
					<label><?=L10N::t('Customernumber start (e.g 0001)')?>: </label>
				</div>
				<div style="float:left;">
					<input type="text" value="" name="school[<?=$aSchool['id']?>][customer_number_start]" />
				</div>
				<div style="clear:both;"></div>
				<div style="width:350px;float:left;">
					<label><?=L10N::t('Customernumber format (e.g Customer-%count)')?>: </label>
				</div>
				<div style="float:left;">
					<input type="text" value="" name="school[<?=$aSchool['id']?>][customer_number_format]" />
				</div>
				<div style="clear:both;"></div>
				
<?
			}
?>
			<div style="width:100%; text-align:right;">
				<input type="submit" value="<?=L10N::t('write Customernumbers')?>" class="btn" />
			</div>
<?
		} else {
				
?> 
				<div style="width:350px;float:left;">
					<label> </label>
				</div>
				<div style="float:left;">
					<select id="option_select">
						<option value="for_school">
							<?=L10N::t('Settings for each school')?>
						</option>
						<option value="for_client">
							<?=L10N::t('central Settings')?>
						</option>
					</select>
				</div>
				<div style="clear:both;"></div>
				<div style="width:100%; text-align:right;">
					<input type="submit"  onclick="$('task').value=$F('option_select');" value="<?=L10N::t('next')?>" class="btn" />
				</div>
<?		
		
		}
		
	}

	protected function saveAllCustomerNumbers($iClientId, $iSchoolId = 0){
		
		$sQuery = "SELECT 
						`ki`.`id` `inquiry_id`
					FROM 
						`customer_db_1` `cdb1` INNER JOIN
						`kolumbus_inquiries` `ki` ON
							`ki`.`idUser` = `cdb1`.`id`
					WHERE 
						`ki`.`office` = :client_id";
						
		if($iSchoolId > 0){
			$sQuery .= "   AND 
						`ki`.`crs_partnerschool` = :school_id ";
		}
						
		$sQuery .= "
					ORDER BY 
						`cdb1`.`created` ASC
					";

		$aSql = array('school_id'=>(int)$iSchoolId,'client_id'=>(int)$iClientId);
		$aCustomers = DB::getPreparedQueryData($sQuery, $aSql);

		$sSql = "UPDATE 
					`customer_db_1` `cd1` ,
					`kolumbus_inquiries` `ki`
				SET 
					`cd1`.`customerNumber` = '' 
				WHERE 
					`ki`.`idUser` = `cd1`.`id` AND
					`ki`.`office` = :client_id AND
					`ki`.`idUser` > 0 ";
		
		if($iSchoolId > 0){
			$sSql .= "   AND 
						`ki`.`crs_partnerschool` = :school_id ";
		}			
		
		$aSql = array('school_id'=>(int)$iSchoolId, 'client_id'=>(int)$iClientId);
	
		DB::executePreparedQuery($sSql, $aSql);
		
		foreach($aCustomers as $aCustomer){
			$oInquiry = new Ext_TS_Inquiry($aCustomer['inquiry_id']);
			$oCustomerNumber = new Ext_Thebing_Customer_CustomerNumber($oInquiry);
			$oCustomerNumber->saveCustomerNumber();
			
		} 
		
	}
	
}
