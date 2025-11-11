<?

// Speichern Modul der customer_db_
// Pr�ft, ob ein Eintrag zu entsprechendem Login vorliegt, und wenn, pr�ft, ob dieser
// g�ltig eingelogt ist.

#die();
	
if(
	!$user_data['cms'] &&
	$_VARS['customer_task'] == 'save'
) {
	
	DB::begin('customer_db_save');
	
	global $db_config;
	global $Form_ID;
	global $parent_config;
	global $_VARS;
	
	if(!$objWebDynamics) {
		global $objWebDynamics;
	}
	
	global $db_class_is_declared;
	if (!$db_class_is_declared) {
	    require (\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_class.inc.php");
	}
	
	global $customer_db_functions_is_declared;
	if(!$customer_db_functions_is_declared) {
	    include(\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_functions.inc.php");
	}
	
	$SQL_handler = new customer_db;
	$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);
	
	
	$pos = strrpos($parent_config->table_name,"_");
	$identifier = substr($parent_config->table_name,0,$pos+1);
	$table_number = substr($parent_config->table_name,$pos+1);
	$idTable = $table_number;
	$definition_table = $identifier."definition";
	$value_table=$identifier."values";
	$tree_table="tree_db_1";
	
	$i=0;
	$aData = array();
	
	if(is_numeric($config->email_link_destination)) {
		$config->email_link_destination = Cms\Entity\Page::getInstance(intval($config->email_link_destination))->getLink($page_data['language']);
	}
	
	if($_SESSION)
	{
		foreach ($_SESSION as $key => $value) {
		    // pr�fen, ob variable zu dem Bereich customer_db_ geh�ren
		    if(strpos($key,"customer_db_save_")===0) {
		    	
		        if($key == "customer_db_save_email") {
		        	
		        	// nickname gleich der neuen email adresse setzen, falls dies vor dem ändern der fall war.
					if(isset($aData["nickname"]) && isset($aData["email"]) && $aData["email"] == $aData["nickname"]) {
						$aData["nickname"] = $value;
					}
	
					$aData["email"]	= $value;
		        } elseif($key == "customer_db_save_nickname") {
					$aData["nickname"] = $value;
		        } elseif($key == "customer_db_save_password") {
					$sPlainPw = $value;
					if($db_config['db_encode_pw']) {
			            $value = md5($value);
					}
					$aData["password"] = $value;
		        } else {
		            $sFieldId = str_replace("customer_db_save_","",$key);
					$sType = getCustomerFieldType($sFieldId,$idTable);
					switch($sType) {
						case "timestamp":
							if(WDDate::isDate($value, WDDate::STRFTIME, '%x')) {
								$date = new WDDate();
								$date->set($value, WDDate::STRFTIME, '%x');
								$value = $date->get(WDDate::DB_DATE);
							}
						break;
					}
					if(is_numeric($sFieldId)) {
						$aData["ext_".$sFieldId] = $value;
					} else {
						$aData[$sFieldId] = $value;
					}
		        }
				unset($_SESSION[$key]);
		        $i++;
		    }
		}
	}
	
	#echo "<br><br>if(!".$config->newentry." && ".$_SESSION." && ".$user_data['login'].")";
	#var_dump($config);
	
	if(!$config->newentry && $_SESSION && $user_data['login']) {
		
	    // speichere alle Daten
	    if($aData)
	    {
			$bolInputOk = 1; 
			//hook check input 
			$arrHook = array();
			$arrHook['data'] = $aData;
			$arrHook['error'] = 0;
			\System::wd()->executeHook('customer_db_save_'.$element_data['content_id'], $arrHook);
			if($arrHook['error']) {
				$bolInputOk = 0;
			}
			$aData = $arrHook['data'];
	        if($bolInputOk){
		        if(save_customer_data($aData, false, 0, "customer_db_".(int)$idTable, (int)$user_data['id'])) {
					$sMessage .= $config->error_success;
					// Daten neu laden
					$user_data['data'] = DB::getQueryRow("SELECT * FROM customer_db_".(int)$idTable." WHERE id = '".(int)$user_data['id']."' LIMIT 1");
				} else {
					$bError = true;
					$sMessage .= $config->error_nosuccess;
				}
	    	}else{
	    		$sMessage .= $config->error_input;
	    		$bError = true;	
	    	}
	    }
	
	} elseif($config->newentry) {
	
		$user_data['table'] = $parent_config->table_name;
	
	    // pr�fe, ob nick oder mail bereits in DB vorhanden
		if($aData["email"] && !$aData["nickname"]) {
			$aData["nickname"] = $aData["email"];
		}
		if($aData["nickname"] && !$aData["email"]) {
			$aData["email"] = $aData["nickname"];
		}
	
	    // Erzeuge query auf nick
	    $bEmailAllowed 	= check_unique($aData["email"],		"email",	$parent_config->table_name);
	    // erzeuge query auf mail
	    $bNickAllowed 	= check_unique($aData["nickname"],	"nickname",	$parent_config->table_name);
	
		$bolInputOk = 1; 
		//hook check input 
		$arrHook = array();
		$arrHook['data'] = $aData;
		$arrHook['error'] = 0;
		// -> hook
		\System::wd()->executeHook('customer_db_save_'.$element_data['content_id'], $arrHook);
		if($arrHook['error']) {
			$bolInputOk = 0;
		}
		$aData = $arrHook['data'];
	    
	    if($bEmailAllowed && $bNickAllowed && $bolInputOk) {
	
			if($config->saveaffiliate) {
				$aData["ext_".$config->saveaffiliate] = $_SESSION['spy'];
			}
	
			// Wenn kein Passwort angegeben, dann generieren
			if(!$aData["password"]) {
				$sValue = \Util::generateRandomString(6);
				$sPlainPw = $sValue;
				if($db_config['db_encode_pw']) {
					$aData["password"] = md5($sValue);
				} else {
					$aData["password"] = $sValue;
				}
			}
	
	        //speichere die Daten
	        $idEntry = save_customer_data($aData, $config->newentry, $config->sendconfirm, $user_data['table']);

	        if($idEntry > 0) {

	        	$aData['id']=$idEntry;
				$user_data['data'] = DB::getQueryRow("SELECT * FROM customer_db_".(int)$idTable." WHERE id = '".(int)$idEntry."' LIMIT 1");

				// Bestätigung per E-Mail an User versenden
				if($config->sendconfirm == 1) {            	

					$config->email_message = fReplaceTags($config->email_message, $aData, $sPlainPw);            					
					
					$oMail = new WDMail;
					$oMail->subject = $config->email_subject;
					$oMail->text = $config->email_message;
					$oMail->send([$user_data['data']['email']]);
					
	            } elseif($config->sendconfirm == 2) {

	            	$key= rand(100000, 999999); # einfach 6 Zahlen

	                $query="INSERT INTO `customer_db_activation` (`activation_key`,`id_user`) VALUES ('$key','$idEntry')";
	                db_query($query);
	
	                $aData['link']=$system_data['domain'].$config->email_link_destination."?task=activate&activation_key=$key&id_user=$idEntry";
	
					$config->email_message = fReplaceTags($config->email_message, $aData, $sPlainPw);				
	                
					$oMail = new WDMail;
					$oMail->subject = $config->email_subject;
					$oMail->text = $config->email_message;
					$oMail->send([$user_data['data']['email']]);
					
	            }
	
				// Bestätigung ausgeben
				$sMessage .= $config->error_success;
	
				///////////////////////////////////////////////////
				// Benachrichtigung per E-Mail an Admin versenden
				///////////////////////////////////////////////////
				$config->admin_email_message = fReplaceTags($config->admin_email_message, $aData, $sPlainPw);
				
				if($config->sendinfo)
	            {
	
	                $aData['activate_link']=$system_data['domain'].$config->email_link_destination."?task=activate&activation_key=$key&id_user=$idEntry";
	                $aData['delete_link']=$system_data['domain'].$config->email_link_destination."?task=deactivate&activation_key=$key&id_user=$idEntry";
	                
	                wdmail(\System::d('admin_email'), $config->admin_email_subject, $config->admin_email_message, $system_data['mail_from']);#
	
				}
	
				///////////////////////////////////////////////////
				///////////////////////////////////////////////////
	
	            // Falls eine alternative Mailadresse angegeben wurde, wird auch an diese verschickt!
	            if(trim($config->alt_email))
	            {
	                wdmail($config->alt_email, $config->admin_email_subject, $config->admin_email_message, $system_data['mail_from']);#
	            }
	            
	            unset($user_data['data']);
	
			} else {
				$bError = true;
				$sMessage .= $config->error_nosuccess;
			}

	    } else {

	    	if(!$bEmailAllowed)
	        {
				$bError = true;
	            $sMessage .= $config->error_email;
	        }
	        if(!$bNickAllowed)
	        {
				$bError = true;
	            $sMessage .= $config->error_nickname;
	        }
	        if(!$bolInputOk)
	        {
				$bError = true;
	            $sMessage .= $config->error_input;
	        }

	    }
	
	}
	else
	{
		error("Fehlfunktion der Speichern Funktion im Kundendatenbankmodul");
	}
	
	if($page_data['cms'] && $bError && $config->backonerror)
	{
		$sParameter = "?customer_error=".urlencode($sMessage);
		foreach($_VARS as $k=>$v) {
			$sParameter .= "&".$k."=".urlencode($v);
		}
		header("Location: ".$_VARS['customer_returnurl'].$sParameter);
	}
	else
	{
		echo $sMessage;
	}
	
	DB::commit('customer_db_save');
	
}
