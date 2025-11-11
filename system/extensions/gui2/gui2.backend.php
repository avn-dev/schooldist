<?php

class gui2_backend {

	function executeHook($strHook, &$mixInput) {

		switch($strHook) {
			case "login_ok":
				
				$bValidAccess = $mixInput->checkValidAccess();
				if($bValidAccess === true) {
					Ext_Gui2_Session::reset($mixInput->key);
				}
				
				break;
			case "logout":

				if (!empty($userKey = $mixInput->key)) {
					Ext_Gui2_Session::reset($userKey);
				}
				
				break;
			default:
				break;
		}

	}

}

\System::wd()->addHook('login_ok', 'gui2');
\System::wd()->addHook('logout', 'gui2');

?>