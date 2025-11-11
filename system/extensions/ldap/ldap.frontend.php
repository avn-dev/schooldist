<?php

class ldap_frontend {

	function executeHook($strHook, &$mixInput) {
		global $user_data, $system_data, $objWebDynamics;

		switch($strHook) {
			case "login_start":

				if(empty($system_data['ldap_host'])) {
					return;
				}

				$bLogin = false;

				// check if user is logged in
				if(
					!empty($_COOKIE['passcookie']) && 
					!empty($_COOKIE['usercookie'])
				) {
					$sSql = "
							SELECT 
								* 
							FROM 
								`system_access` 
							WHERE 
								`password` = '".\DB::escapeQueryString($_COOKIE['passcookie'])."' AND 
								`username` = '".\DB::escapeQueryString($_COOKIE['usercookie'])."' AND 
								`valid` >= '".time()."'
							";
					$aLogin = DB::getQueryData($sSql);

					if(!empty($aLogin)) {
						$bLogin = true;
					}

				}
				
				// check if ldap special is set and user is not logged in
				if($bLogin == false) {
				
					$bCheckBind = 1;
					
					if(
						!empty($system_data['ldap_env_variable']) &&
						isset($_SERVER[$system_data['ldap_env_variable']]) &&
						!empty($_SERVER[$system_data['ldap_env_variable']])
					) {

						$mixInput['customer_login_1'] = $_SERVER[$system_data['ldap_env_variable']];
						$bCheckBind = 0;
						
					}

					if(empty($mixInput['customer_login_1'])) {
						return false;
					}

					// get LDAP entry
					$system_data['ldap_search'] = str_replace('{USERNAME}', $mixInput['customer_login_1'], $system_data['ldap_search']);

					$pLdapConnector = new Ext_Ldap_Connector($system_data['ldap_host']);
					
					if(
						!empty($system_data['ldap_user']) &&
						!empty($system_data['ldap_pass'])
					) {
						$pLdapConnector->bind($system_data['ldap_user'], $system_data['ldap_pass']);
					}

					$pLdapConnector->search($system_data['ldap_search'], $system_data['ldap_base_dn']);

					// retrieve the first entry
					$mEntry = $pLdapConnector->firstEntry();
					 
					if($mEntry == false) {
						return false;
					}

					$aEntry = $pLdapConnector->getAttributes();

					$sUsername 	= $aEntry[$system_data['ldap_field_username']][0];
					$sEmail 	= $aEntry[$system_data['ldap_field_email']][0];
					$sFirstname = $aEntry[$system_data['ldap_field_firstname']][0];
					$sLastname 	= $aEntry[$system_data['ldap_field_lastname']][0];

					if(
						empty($mixInput['customer_login_3']) &&
						!empty($system_data['ldap_field_password']) &&
						!empty($aEntry[$system_data['ldap_field_password']][0])
					) {
						$sPassword = $aEntry[$system_data['ldap_field_password']][0];
					} else {
						$sPassword = $mixInput['customer_login_3'];
					}

					if($bCheckBind) {

						$bSuccess = $pLdapConnector->bind($sUsername, $sPassword);
						
						if(!$bSuccess) {
							return false;
						}

					}

					$oCustomerDb = new Ext_CustomerDB_DB($mixInput['table_number']);
					
					// check if user exists in local DB
					$aUser = $oCustomerDb->getCustomerByUniqueField('user', $sUsername);

					if(empty($aUser)) {

						$aValues['nickname'] = $sUsername;
						$aValues['password'] = $sPassword;
						$aValues['email'] = $sEmail;
						$aValues['active'] = 1;

						$oCustomerDb->insertCustomer($aValues);

						$aUser = $oCustomerDb->getCustomerByUniqueField('user', $sUsername);

					} else {

						$oCustomerDb->updateCustomerField($aUser['id'], 'password', $sPassword);
						$oCustomerDb->updateCustomerField($aUser['id'], 'email', $sEmail);						

					}

					$aEntry['user_id'] = $aUser['id'];
					
					\System::wd()->executeHook('ldap_login', $aEntry);

					$mixInput['customer_login_1'] = $sEmail;
					$mixInput['customer_login_3'] = $sPassword;

				}

				break;
			default:
				break;
		}

	}

}

\System::wd()->addHook('login_start', 'ldap');
