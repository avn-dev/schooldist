<?php
abstract class Ext_TS_Inquiry_Saver_Abstract {
    
    /**
     * @var Ext_Thebing_Gui2_Format_Date 
     */
    protected $_oDateFormat;
    
    /**
     * @var Ext_Thebing_School 
     */
    protected $_oSchoolForFormat;


    /**
     * @var MVC_Request 
     */
    protected $_oRequest;
    
    /**
     * Array mit allen Fehlern
     * @var array 
     */
    protected $_aErrors = array();

	protected $aWarnings = [];
    
    /**
     * Key der bei den DB Transactionen benutzt wird
     */
    protected $_sTransactionKey = 'Ext_TS_Inquiry_Saver_Abstract';
    
    protected $_sAlias = '';
    
    /**
     * @var Ext_TC_Basic
     */
    protected $_oObject;
    
    /**
     * @var Ext_Gui2
     */
    protected $_oGui;
	
	protected $bNew = false;
	
	/**
	 * Parameter ob auch wirklich gespeichert werden soll (z.B. bei dependency selections muss nur gesetzt werden)
	 * 
	 * @var bool
	 */
	protected $_bSave;


    /**
     * 
     * @param MVC_Request $oRequest
     */
    public function __construct(MVC_Request $oRequest, Ext_Gui2 $oGui, $bSave = true){
        $this->_oRequest            = $oRequest;
        $this->_oGui                = $oGui;
        $this->_oDateFormat         = new Ext_Thebing_Gui2_Format_Date();
		$this->_oSchoolForFormat    = Ext_Thebing_Client::getFirstSchool($this->_oGui->access);
		$this->_bSave				= $bSave;
        $this->prepare();
    }
    
    /**
     * gibt das (erzeugten) Object zurück
     * @return null
     */
    public function getObject() {
        return $this->_oObject;
    }
    
    /**
     * Setzt Fehler die aus der WDBasic Validate kommen und formatiert sie entsprechend um
     * @param type $mErrors
     */
    public function setWDBasicError($mErrors){
        if($mErrors !== true){
            $sAction        = (string)$this->_oRequest->get('action');
            $sAdditional    = (string)$this->_oRequest->get('additional');
            $mErrors        = $this->_oGui->getDataObject()->getErrorData($mErrors, $sAction, $sAdditional);
            foreach($mErrors as $mError){
                $mError = $this->manipulateError($mError);
                $this->addError($mError);
            }
        }
    }
    
    public function manipulateError($mError){
        return $mError;
    }

    /**
     * @return mixed|bool
     */
    public function validate() {
        $mValidate = $this->_oObject->validate();
        return $mValidate;
    }

    /**
     * Gibt die Save-Values zurück falls gefunden
     *
     * @return null
     */
    public function getRequestSaveValues() {

        $aSaveData = $this->_oRequest->input('save');

        if(is_array($aSaveData)) {
             return $aSaveData;
        } else {
            $this->addError('Es ist ein Fehler beim Übermitteln der Daten aufgetreten!');
        }

        return null;
    }
    
    /**
     * setzt die Values aus dem Request in das aktuelle Objekt
     *
     * @throws Exception
     */
    public function setRequestSaveValues() {
        
        if(
           $this->_oObject &&
           $this->_sAlias != ""
        ) {
            $aSaveData = $this->getRequestSaveValues();
            foreach($aSaveData as $sColumn => $aAliases) {
                if(isset($aAliases[$this->_sAlias])){
                   $this->_oObject->$sColumn = $this->prepareSaveValue($aAliases[$this->_sAlias], $sColumn);
                }
            }
        }
        
    }
    
    /**
     * bereitet das Value das gesetzt wird vor
     * @param mixed $mValue
     * @param string $sColumn
     * @return mixed
     */
    public function prepareSaveValue($mValue, $sColumn){
        return $mValue;
    }
    
    /**
     *  gibt alle Fehler zurück
     * @return type
     */
    public function getErrors(){
        return $this->_aErrors;
    }
    
    /**
     * pr+ft ob fehler vorhanden sind
	 *
	 * @return bool
    */
    public function hasErrors() {
		return count($this->_aErrors) > 0;
    }

	/**
	 * @return array
	 */
	public function getWarnings() {
		return $this->aWarnings;
	}

	/**
	 * @return bool
	 */
	public function hasWarnings() {
		return count($this->aWarnings) > 0;
	}
    
    /**
     * fügt einen Fehler hinzu
     * @param array|string $aError
     */
    public function addError($aError){
        if(!is_array($aError)){
            $aError = array(
                'message' => $this->_oGui->t($aError),
                'input' => array(
                    'db_column' => '',
                    'db_alias' => ''
                ),
                'type' => 'error'
            );
        }
        $this->_aErrors[] = $aError;
    }
    
    /**
     * setzt das Object das für das speichern benutzt wird
     * @param Ext_TC_Basic $oObject
     */
    public function setObject(Ext_TC_Basic $oObject, $sAlias = ''){
        $this->_oObject     = $oObject;
        if(empty($sAlias)){
           $sAlias          = $this->_oObject->getTableAlias();
        }
        $this->_sAlias      = $sAlias;
        $this->setRequestSaveValues();
    }
    
    protected function _prepare(){
        
    }
    
    protected function _finish($bSave){
        return true;
    }
    
    protected function _save(){
        return true;
    }

	/**
	 * Fügt die Fehler eines anderen Savers dem eigenen hinzu
	 *
	 * @param Ext_TS_Inquiry_Saver_Abstract $oSaver
	 */
	protected function _mergeErrors(Ext_TS_Inquiry_Saver_Abstract $oSaver) {
		foreach($oSaver->getErrors() as $aError) {
			$this->addError($aError);
		}

		foreach($oSaver->getWarnings() as $aWarning) {
			$this->aWarnings[] = $aWarning;
		}
	}

    /**
     * wird ganz am anfang aufgerufen
     */
    final public function prepare(){
        // Spezielle Prepared machen
        $this->_prepare();

		if($this->_oObject instanceof WDBasic) {

			if(!$this->_oObject->exist()) {
				$this->bNew = true;
			}
		} elseif($this->_oObject instanceof Ext_TS_Inquiry_Saver_Inquiry) {
			
			if(!$this->_oObject->getObject()->exist()) {
				$this->bNew = true;
			}
			
		}
		
        // Allgemeine Prepared machen
		// Hier entfernt, da das jede Saver-Klasse aufruft (und ggf. unten auch commit trotz Fehler)
		//DB::begin($this->_sTransactionKey);
    }
    
    /**
     * wird ganz am ende aufgerufen, nach dem save
     */
    final public function finish($bSave = true){
        // Spezielle Finish machen
        $bSuccess = $this->_finish($bSave);

        // Allgemeien Finish amchen
        if($this->hasErrors() || $bSuccess === false){
			//DB::rollback($this->_sTransactionKey);
            $bSuccess = true;
        } else {
			//DB::commit($this->_sTransactionKey);
            $bSuccess = false;
        }

        return $bSuccess;
    }
    
    /**
     * Speichert alles  und bendet den Saver
     */
    final public function save(){
                
        if($this->_bSave){

			// Flag überprüfen, damit auch wirklich nur dann gespeichert wird, wenn gespeichert werden soll #17989
			if (!$this->_oRequest->boolean('inquiry_save_handler')) {
				throw new \RuntimeException('Saving not allowed in this context.');
			}

            $bSuccess = $this->_save();

            if($bSuccess && !$this->hasErrors()){
                
                if($this->_oObject){

                    $mSuccess = $this->_oObject->validate();

                    if($mSuccess !== true){
                        $this->setWDBasicError($mSuccess);
                        $mSuccess = false;
                    } else {
                    	$this->beforeSave();
                        $this->_oObject->save();
                    }

                }

                $mSuccessFinish = $this->finish();
				
                if($mSuccess && $mSuccessFinish === false){
                    $mSuccess = false;
                }
                
            }
        }
        
        return $mSuccess;
    }

	/**
	 * Wird vor dem Speichern nach dem (erfolgreichen) Validieren aufgerufen
	 */
    protected function beforeSave() {

	}

	/**
	 * @return MVC_Request
	 */
	public function getRequest() {
		return $this->_oRequest;
	}
    
	public function checkRequestForObjectData(array $aliases) {

		$isEmpty = true;
		$saveData = $this->_oRequest->input('save');
		foreach($saveData as $field=>$fieldData) {
			foreach($fieldData as $alias=>$value) {

				if(
					in_array($alias, $aliases) &&
					!empty($value)
				) {
					$isEmpty = false;
					break 2;
				}

			}
		}

		return $isEmpty;
	}
	
}