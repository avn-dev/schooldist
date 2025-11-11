<?php

class Ext_TS_Inquiry_Saver extends Ext_TS_Inquiry_Saver_Abstract{
    
    /**
     * @var Ext_TS_Inquiry_Saver_Inquiry 
     */
    protected $_oObject;
    
    public function _prepare(){
        $this->prepareInquiry();
    }
    
    public function prepareInquiry(){
		$this->_oObject = new Ext_TS_Inquiry_Saver_Inquiry($this->_oRequest, $this->_oGui, $this->_bSave);
    }

    public function _save() {
        global $user_data;

		$oInquiry = $this->_oObject->getObject();
		$oInquiry->setInquiryStatus(null, false);
		$oInquiry->editor_id = $user_data['id'];

        return true;
    }
    
    public function getSavedId(){
        return $this->_oObject->getObject()->id;
    }

    /**
     * @param mixed $mError
     * @return mixed
     */
    public function manipulateError($mError) {

        if($mError['input']['dbalias'] == 'travellers') {

            $mError['input']['dbalias'] = 'cdb1';
			// #5021 - Bei dem Geburtsdatum kommt als error_id 
			$mError['error_id'] = '['.$mError['input']['dbcolumn'].'][cdb1]';

		} elseif($mError['input']['dbalias'] == 'emergencies') {

            $mError['input']['dbalias'] = 'tc_c_e';

        } elseif($mError['input']['dbalias'] == 'ts_ijc') {

            $mError['input']['dbalias'] = '';
            $mError['input']['id'] = 'course['.$this->getSavedId().']['.$mError['id'].']'.'['.$mError['input']['dbcolumn'].']';

        } elseif($mError['input']['dbalias'] == 'ts_ija') {

            $mError['input']['dbalias'] = '';
            $mError['input']['id'] = 'accommodation['.$this->getSavedId().']['.$mError['id'].']'.'['.$mError['input']['dbcolumn'].']';

        } elseif($mError['input']['dbalias'] == 'ts_ijt') {

            $mError['input']['dbalias'] = '';
            $mError['input']['id'] = 'transfer['.$this->getSavedId().']['.$mError['id'].']'.'['.$mError['input']['dbcolumn'].']';

        } elseif($mError['input']['dbalias'] == 'ts_ijv') {

			// Visumsfelder haben keine ID vom Objekt (und auch sonst nichts vom JoinedObject)
			$mError['error_id'] = '['.$mError['input']['dbcolumn'].']['.$mError['input']['dbalias'].']';

		} elseif($mError['input']['dbalias'] == 'ts_iji') {

            $mError['input']['dbalias'] = '';
            $iKey = (int)Ext_TS_Inquiry_Saver_Journey::$aKeyMapping['insurance'][$mError['id']];
            $mError['input']['id'] = 'insurance['.$this->getSavedId().']'.'['.$mError['input']['dbcolumn'].']['.$iKey.']';

        } elseif($mError['input']['dbalias'] == 'ts_ijac') {

			$mError['input']['dbalias'] = '';
			$mError['input']['id'] = 'activity['.$this->getSavedId().']['.$mError['id'].']'.'['.$mError['input']['dbcolumn'].']';

		} elseif($mError['input']['dbalias'] == 'sponsoring_guarantees') {

        	// $mError['id'] ist einfach leer; fraglich was Ext_Gui2_Data::getFieldIdentifier() macht
        	preg_match('/sponsoring_guarantees\[(\d+)\].ts_isg/', $mError['identifier'], $aMatches);
        	$mError['id'] = $aMatches[1];

			$mError['input']['dbalias'] = '';
			$mError['input']['id'] = 'sponsoring_gurantee['.$this->getSavedId().']['.$mError['id'].']'.'['.$mError['input']['dbcolumn'].']';

		} elseif($mError['input']['dbalias'] == 'tc_e') {

            if(strpos($mError['identifier'], 'traveller') !== false) {
				$mError['input']['name'] = 'contact_email['.$mError['id'].']';
				$mError['error_id'] = null; // error_id hat höhere Priorität als input.name
            } else if(strpos($mError['identifier'], 'emergencies') !== false) {
                $mError['input']['dbalias'] = 'tc_c_e';
				$mError['error_id'] = '['.$mError['input']['dbcolumn'].']['.$mError['input']['dbalias'].']';
            }


        } elseif($mError['input']['dbalias'] == 'holidays') {
			$mError['input']['id'] = 'holidays[new]'.'['.$mError['input']['dbcolumn'].']';
			if ($mError['input']['dbcolumn'] == 'course_id') {
				$matches = [];
				preg_match_all('/\[(.*?)\]/', $mError['identifier'], $matches);
				if (
					isset($matches[1]) &&
					count($matches[1]) > 0
				) {
					$mError['id'] = end($matches[1]);
				}
			}
		}

		if(strpos($mError['message'], '%s') !== false) {
			$mError['message'] = $this->_replaceErrorPlaceholders($mError);
		}
		
        return $mError;
    }
	
    public function getRequestData(){
        
        $aSelectedIds = (array)$this->getSavedId();
   
        $aTransfer = array(
            'action' => 'saveDialogCallback',
            'error' => array()
        );
        
        $aData      = array();
        $sTitle     = L10N::t('Buchung', $this->_oGui->gui_description);
        $oInquiry   = $this->_oObject->getObject();
        
        if($oInquiry->id > 0){          
            $oCustomer  = $oInquiry->getCustomer();
            $sTitle     .= ' - '.$oCustomer->lastname.', '.$oCustomer->firstname;
        }  
        
        if($this->hasErrors()){
            $iHint = 0;
            $aErrors = $this->getErrors();
            $bError = false;
            foreach($aErrors as $aError){
                if($aError['type'] == "" || $aError['type'] == 'error'){
                    $bError = true;
                }
                if($aError['type'] == 'hint'){
                    $iHint = 1;
                }
            }
            if($bError){
                $aErrors[-1] = $this->_oGui->t('Ein Fehler ist aufgetreten!');
                ksort($aErrors);
                $aErrors = array_values($aErrors);
            }
            $aData['show_skip_errors_checkbox'] = $iHint;
            $aTransfer['error'] = $aErrors;
        } else if($this->_oGui->query_id_alias == 'kit') {
            $aTransfer['action'] = 'closeAllDialog';
        } else {
        	// TODO: Ist es richtig, dass das hier IMMER auf edit steht?
            $aData = $this->_oGui->getDataObject()->prepareOpenDialog('edit', $aSelectedIds, false, false, true);

			if($this->hasWarnings()) {
				$aTransfer['success_title'] = $this->_oGui->t('Erfolgreich gespeichert');
				$aTransfer['success_message'] .= join('<br>', $this->getWarnings());
			}
        }
        
        $aData['id']			= 'ID_'.(int)implode('_', (array)$aSelectedIds);
        $aData['save_id']		= $oInquiry->id;

        $aTransfer['title'] 	= $sTitle;
        $aTransfer['data']      = $aData;

        return $aTransfer;
    }
    
    public function _finish($bSave) {
        
        $bSuccess = parent::_finish($bSave);
        
        if($this->_oObject->oNumberRange){
           $this->_oObject->oNumberRange->removeLock(); 
        }
        
        if($bSuccess && $bSave && !$this->hasErrors()){
            $oInquiry = $this->_oObject->getObject();
            if($oInquiry->id > 0) {
                Ext_Gui2_Index_Registry::insertRegistryTask($oInquiry);
            }
			
			if($this->bNew) {
				\Log::add(\Ext_TS_Inquiry::LOG_INQUIRY_CREATED, $oInquiry->id, get_class($oInquiry));
			} else {
				\Log::add(\Ext_TS_Inquiry::LOG_INQUIRY_UPDATED, $oInquiry->id, get_class($oInquiry));
			}
			
        }
        
        $this->_mergeErrors($this->_oObject);
        
        return $bSuccess;
    }
    
	/**
	 * bietet die Möglichkeit, in der Fehlermeldung vorhandene Platzhalter zu ersetzen
	 * 
	 * @param string $sMessage
	 * @param string $sColumn
	 * @param WDBasic $oObject
	 * @return string
	 */
	protected function _replaceErrorPlaceholders($mError) {
		
		$sMessage = $mError['message'];
		
		$oInquiry = $this->_oObject->getObject();
		/* @var $oInquiry Ext_TS_Inquiry */
		$oJourney = $oInquiry->getJourney();
		
		$sReplace = '';
		
		switch($mError['input']['dbcolumn']) {
			case 'course_id':				
				if(isset($mError['id'])) {
					$aJourneyCourses = $oJourney->getJoinedObjectChilds('courses', true);
					if(isset($aJourneyCourses[$mError['id']])) {
						$oJourneyCourse = $aJourneyCourses[$mError['id']];
						$oCourse = $oJourneyCourse->getJoinedObject('course');
						$sReplace = $oCourse->getName();
					}
				}
				break;
			case 'accommodation_id':
			case 'roomtype_id':
			case 'meal_id':
				
				$aJourneyAccommodations = $oJourney->getJoinedObjectChilds('accommodations', true);
				if(isset($aJourneyAccommodations[$mError['id']])) {
					$oJourneyAccommodation = $aJourneyAccommodations[$mError['id']];
					if($mError['input']['dbcolumn'] == 'accommodation_id') {
						$oCategory = $oJourneyAccommodation->getCategory();
						$sReplace = $oCategory->getName();
					} elseif($mError['input']['dbcolumn'] == 'roomtype_id') {
						$oRoomtype = $oJourneyAccommodation->getRoomType();
						$sReplace = $oRoomtype->getName();
					} elseif($mError['input']['dbcolumn'] == 'meal_id') {
						$oMeal = $oJourneyAccommodation->getMeal();
						$sReplace = $oMeal->getName();
					}
				}
				break;
			default:
				$sReplace = '';
		}
		
		if(!empty($sReplace)) {
			$sMessage = sprintf($sMessage, $sReplace);
		}

		return $sMessage;
	}
	
}
