<?php


class Ext_Gui2_Index_Controller extends MVC_Abstract_Controller {
 
	/**
     *execute the Current Stack of the given Index 
     */
    public function executeStack() {
		global $user_data;
		
		if(isset($_COOKIE['systemlanguage'])){
			System::setInterfaceLanguage($_COOKIE['systemlanguage']);
		}
 
        $sIndex             = $this->_oRequest->get('index');
		
		// Lock
		$sCacheKey = 'gui2_index_execute_stack_'.$sIndex;
		
		$iLockUser = WDCache::get($sCacheKey);
		
		if(
			$iLockUser === null ||
			$iLockUser == $user_data['id']
		) {

			// Zugriff eine Minute sperren
			WDCache::set($sCacheKey, 60, $user_data['id']);
			
			$aErrors            = array();
			$sAction            = 'executeIndexStackCallback';
			$aData              = array();
			$aData['action']    = 'executeIndexStackCallback';
			$aData['index']     = $sIndex;
			$aData['id']        = 'ID_0';

			$iDebug             = (int)$this->_oRequest->get('debug');

			try {
				$bSuccess  = Ext_Gui2_Index_Stack::execute(99, 10, $sIndex, $iDebug);
			} catch(Exception $e) {
				$bSuccess = false;
				__pout($e);
			}

			if(!$bSuccess){
				$sAction = 'showError';
				$aErrors = array(L10N::t('Es ist ein Fehler aufgetreten!'));
			}

		} else {
			$sAction = 'showError';
			$aErrors = array(L10N::t('Der Index wird bereits von einem anderen Benutzer aktualisiert!'));
		}

        $this->set('action', $sAction);
        $this->set('data', $aData);
        $this->set('error', $aErrors);

    }
    
}