<?php

class ldap_backend {

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

						$mixInput['username'] = $_SERVER[$system_data['ldap_env_variable']];
						$bCheckBind = 0;
						
					}

					if(empty($mixInput['username'])) {
						return false;
					}

					// get LDAP entry
					$system_data['ldap_search'] = str_replace('{USERNAME}', $mixInput['username'], $system_data['ldap_search']);

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
						empty($mixInput['password']) &&
						!empty($system_data['ldap_field_password']) &&
						!empty($aEntry[$system_data['ldap_field_password']][0])
					) {
						$sPassword = $aEntry[$system_data['ldap_field_password']][0];
					} else {
						$sPassword = $mixInput['password'];
					}

					if($bCheckBind) {

						$bSuccess = $pLdapConnector->bind($sUsername, $sPassword);
						
						if(!$bSuccess) {
							return false;
						}

					}

					// check if user exists in local DB
					$aUser = user_data::getByUsername($sUsername);

					if($aUser == false) {

						$sSql = "
								INSERT INTO
									`system_user`
								SET
									`created` = NOW(),
									`username` = :username,
									`firstname` = :firstname,
									`lastname` = :lastname,
									`password` = MD5(:password),
									`email` = :email,
									`role` = :role,
									`tab_data` = 3,
									`toolbar_size` = 32,
									`active` = 1
									";
						$aSql = array();
						$aSql['role'] = $system_data['ldap_default_role'];
						$aSql['username'] = $sUsername;
						$aSql['firstname'] = $sFirstname;
						$aSql['lastname'] = $sLastname;
						$aSql['password'] = $sPassword;
						$aSql['email'] = $sEmail;
						DB::executePreparedQuery($sSql, $aSql);

						$aUser = user_data::getByUsername($sUsername);

					} else {
						
						$sSql = "
								UPDATE
									`system_user`
								SET
									`firstname` = :firstname,
									`lastname` = :lastname,
									`password` = MD5(:password),
									`email` = :email
								WHERE
									`id` = :id
									";
						$aSql = array();
						$aSql['id'] = $aUser['id'];
						$aSql['firstname'] = $sFirstname;
						$aSql['lastname'] = $sLastname;
						$aSql['password'] = $sPassword;
						$aSql['email'] = $sEmail;
						DB::executePreparedQuery($sSql, $aSql);

					}

					$aEntry['cms_user_id'] = $aUser['id'];
					
					\System::wd()->executeHook('ldap_login', $aEntry);

					$mixInput['login'] = "ok";
					$mixInput['username'] = $sUsername;
					$mixInput['password'] = $sPassword;

				}

				break;
			default:
				break;
		}

	}

}

\System::wd()->addHook('login_start', 'ldap');
