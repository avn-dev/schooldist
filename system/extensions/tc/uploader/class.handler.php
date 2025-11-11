<?php
 
class Ext_TC_Uploader_Handler {
    
    /**
     * @var Ext_TC_Uploader_Builder 
     */
    protected $_oBuilder;
    
    protected $_aSuccessFiles = array();
    protected $_aErrors = array();
    protected $_sUID = 0;
    protected $_iParentId = 0;

    /**
     * 
     * @param string $sUID
     */
    public function __construct($sUID) {
        $this->_oBuilder = Ext_TC_Uploader_Builder::getFromSession($sUID);
        $this->_sUID = $sUID;
    }
    
    public function deleteFiles(){
        // Löscht alle alten Uploads aus der DB
        Ext_TC_Uploader::deleteAllByNamespace(
				$this->_oBuilder->getSelectedId(),
                $this->_oBuilder->getNamespaceId(),
                $this->_oBuilder->getNamespace()
        );
    }
    
    public function remove(){
        unset($_SESSION['tc_uploader'][$this->_sUID]);
    }
    
    /**
     * startet den Upload der übermittelten Dateien
     * @global type $_VARS
     * @return boolean
     */
    public function upload(){
        global $_VARS;

        // Alte Daten löschen
        if($_VARS['tc_upload_'.$this->_sUID]['parent_id']){
            $this->_iParentId = $_VARS['tc_upload_'.$this->_sUID]['parent_id'];
        }
                
        $this->deleteFiles();
        
        // Neue Daten hochladen
        if(isset($_FILES['tc_upload_'.$this->_sUID]['tmp_name'])){
            
            foreach($_FILES['tc_upload_'.$this->_sUID]['tmp_name'] as $iKey => $sFile){
                $bSuccess = $this->_uploadFile($sFile, $_FILES['tc_upload_'.$this->_sUID]['name'][$iKey]);
                if(!$bSuccess){
                    break;
                }
            }
            
            if(!$bSuccess){
                $this->_removeLastFiles();
            } else {
                $this->_saveFiles2Database();
            }
            
            return $bSuccess;
        } else {
            $this->_aErrors[] = $this->getErrorMessage('e00003');
            return false;
        }
    }
    
    /**
     * gibt alle hochgeladenen Bilder zurück
     * @return type
     */
    public function getFiles(){
        return $this->_aSuccessFiles;
    }
    
    /**
     * gibt einen Ergebnisstext zurück
     * @return string
     */
    public function getResponse(){
        if(!empty($this->_aErrors)){
            $sResponse = implode('<br/>', $this->_aErrors);
        } else {
            $sResponse = L10N::t('Upload erfolgreich');
        }
        return $sResponse;
    }
    
    /**
     * löscht die letzten Dateien
     * wird benutzt wenn ein Fehler auftritt!
     */
    protected function _removeLastFiles(){
        foreach($this->_aSuccessFiles as $sFile => $sName){
            unlink(Ext_TC_Util::getDocumentRoot().$sFile);
        }
    }

    /**
     * läd eine Datei in das passende Verzeichniss
     * @param string $sFile
     * @param string $sName
     * @return boolean
     */
    protected function _uploadFile($sFile, $sName) {

        $sNewFile = $this->_oBuilder->createFilePath($sName);

        if($sNewFile){
            
            $bCheck = move_uploaded_file($sFile, $sNewFile);

            if(!$bCheck){
                $this->_aErrors[] = $this->getErrorMessage('e00001');
                return false;
            } else {
                $this->_aSuccessFiles[$sNewFile] = $sName;
            }
            
        } else {
            $this->_aErrors[] = $this->getErrorMessage('e00002');
            return false;
        }
        
        return true;
    }
    
    protected function _saveFiles2Database(){
        
		$iSelected	= $this->_oBuilder->getSelectedId();
        $iNamespace = $this->_oBuilder->getNamespaceId();
        $sNamespace = $this->_oBuilder->getNamespace();
        
        foreach($this->_aSuccessFiles as $sFile => $sName){
            $oUpload = new Ext_TC_Uploader(0);
            $oUpload->path          = $sFile;
            $oUpload->description   = $sName;
			$oUpload->object_id		= $iSelected;
            $oUpload->namespace_id  = $iNamespace;
            $oUpload->namespace     = $sNamespace;
            $oUpload->save();
        }
        
    }
    
    
    protected function getErrorMessage($sErrorCode){
        
        switch ($sErrorCode) {
            case 'e00001':
            case 'e00002':
                $sError = L10N::t('Fehler beim Hochladen der Datei');
                break;
            case 'e00003':
                $sError = L10N::t('Keine Dateien ausgewählt!');
                break;
            default:
                $sError = L10N::t('Fehler beim Hochladen der Datei');
                break;
        }
        
        return $sError.' ['.$sErrorCode.']';
    }
    
}