<?php
 
class Ext_TC_Uploader_Builder {

    protected $_bMultiple           = true;
    protected $_bDragDrop           = true;
    protected $_aFileTypes          = array();
    protected $_sTemplate           = '';
    protected $_sRequestFile        = '/system/extensions/tc/uploader/request.php';
    protected $_iNamespace          = 0;
	protected $_iSelectedId         = 0;
    protected $_sNamespace          = '';
    protected $_iUID                = '';
    protected $_sUploadDir          = 'storage/tc/uploads/';
    protected $_bRandomFileName     = true;


    /**
     * max file Size in Bytes
     * @var int
     */
    protected $_iMaxFileSize = null; //1048576;


    public function __construct($iSelected, $iNamespace, $sNamespace) {
        $this->_iUID                = md5(uniqid('tc_upload', true));
		$this->_iSelectedId			= $iSelected;
        $this->_iNamespace          = $iNamespace;
        $this->_sNamespace          = $sNamespace;
    }
    
    public static function getFromSession($iUID){
        return $_SESSION['tc_uploader'][$iUID];
    }

    public function generateHTML(){
        $this->_prepareTemplate();
        $this->_prepareSession();
        return $this->_generateHTML();
    }
    
	public function getSelectedId(){
        return $this->_iSelectedId;
    }
	
    public function getNamespaceId(){
        return $this->_iNamespace;
    }
    
    public function getNamespace(){
        return $this->_sNamespace;
    }
    
    public function setMultiple($bBool){
        $this->_bMultiple = $bBool;
    }
    
    public function setDragDrop($bBool){
        $this->_bDragDrop = $bBool;
    }

    public function setMaximumFileSizeInByte($iSize){
        $this->_iMaxFileSize = $iSize;
    }
    
    public function setUploadDir($sDir){
        Ext_TC_Util::checkDir($sDir);
        if(strpos($sDir, '/') === 0){
            $sDir = mb_substr($sDir, 1);
        }
        $this->_sUploadDir = $sDir;
    }
    
    public function setAllFileTypes(){
        $aTypes = array(
            '', // z.b Word dateien kp warum da im JS nichts ankommt...
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'application/msword',
            'application/pdf ',
            'application/vnd.ms-excel',
            'application/vnd.ms-works',
            'application/x-compressed',
            'application/x-compress',
            'application/x-gzip',
            'application/x-tar',
            'application/zip',
            'application/force-download' // PDFs haben das hier meistens kp warum... hab auch keine lösung gefunden
        );
        $this->setFileTypes($aTypes);
    }
    
    public function setFileTypes($aTypes){
        $this->_aFileTypes = $aTypes;
    }
    
    public function createFilePath($sName){
        
        if($this->_bRandomFileName) {
            $aTemp = explode('.', $sName);
            $sName = uniqid('tc_upload_file', true);
            $sName = md5($sName);
            $sName .= '.'.end($aTemp);
        }
        
        $sDir   = $this->_sUploadDir;
        if(mb_substr($sDir, -1, 1) !== "/") {
            $sDir .= '/';
        }
        
        $sDir   = Ext_TC_Util::getDocumentRoot().$sDir;
        
        try {
            Ext_TC_Util::checkDir($sDir);
        } catch (Exception $exc) {
            return null;
        }
        
        $sPath  = $sDir.$sName;

        return $sPath;
    }
    
    protected function _prepareSession(){
        $_SESSION['tc_uploader'][$this->_iUID] = $this;
    }
    

    protected function _prepareTemplate(){
        
        if($this->_bMultiple){
            $this->_sTemplate = 'multiple';
        } else {
            $this->_sTemplate = 'single';
        }
        
        if($this->_bDragDrop){
            $this->_sTemplate .= '_dragdrop';
        }
        
        $this->_sTemplate .= '.tpl';
    }
    
    protected function _generateHTML()
	{

		$oSmarty = new SmartyWrapper();
		$sDir = __DIR__ . '/template';
		$oSmarty->setTemplateDir($sDir);
		$oSmarty->assign('uid', $this->_iUID);
		$oSmarty->assign('request_file', $this->_sRequestFile);
		$oSmarty->assign('max_file_size', $this->_iMaxFileSize);
        $oSmarty->assign('file_types', implode(',', $this->_aFileTypes));
        if($this->_bMultiple){
            $oSmarty->assign('btn_delete', L10N::t('remove files'));
            $oSmarty->assign('btn_add', L10N::t('add files'));
        } else {
            $oSmarty->assign('btn_delete', L10N::t('remove file'));
            $oSmarty->assign('btn_add', L10N::t('add files'));
        }
        $oSmarty->assign('btn_upload', L10N::t('Upload'));
        $oSmarty->assign('current_files', $this->_getCurrentFilesAsString());
        
        return $oSmarty->fetch($this->_sTemplate);
    }
    
    protected function _getCurrentFilesAsString(){
        $aResult = Ext_TC_Uploader::search($this->_iSelectedId, $this->_iNamespace, $this->_sNamespace);
        $aFiles = array();
        foreach($aResult as $aFile){
            $aFiles[] = $aFile['path'];
        }
        return implode(',', $aFiles);
    }
}
