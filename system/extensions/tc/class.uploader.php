<?php

/**
 * Beschreibung der Klasse 
 * @param $filename
 */
class Ext_TC_Uploader extends Ext_TC_Basic { 

	// Tabellenname
	protected $_sTable = 'tc_uploader';
    
    public function __set($sName, $mValue) {
        if($sName == 'path'){
            $mValue = str_replace(Ext_TC_Util::getDocumentRoot(), '', $mValue);
        }
        parent::__set($sName, $mValue);
    }

	public function __get($sName) {
		if($sName == 'filename') {
			$aFileData = explode('/', $this->path);
			return array_pop($aFileData);
		} else {
			return parent::__get($sName);
		}
	}

    
	/**
	 * liefert den Pfad zu der Datei
	 * 
	 * @param bool $bRootPath
	 * @return string
	 */
	public function getPath($bRootPath = true) {		
		$sPath = $this->path;
		if($bRootPath) {
			$sPath = Ext_TC_Util::getDocumentRoot() . $sPath;
		}
		
		return $sPath;
	}
	
    public static function deleteAllByNamespace($iSelected, $iNamespace, $sNamespace){
        global $user_data;
        
        $aSql = array(
			'object_id'			=> $iSelected,
            'namespace_id'      => $iNamespace,
            'namespace'         => $sNamespace,
            'creator_id'        => $user_data['id']
        );
        
        // Zuerst suchen und dateien im system löschen
        $sSql = "SELECT * FROM 
                 `tc_uploader` WHERE 
                    `namespace_id` = :namespace_id AND 
                    `namespace` = :namespace AND
					`object_id` = :object_id";
        
        // Bei "temporären" einträgen die User id prüfen
        // ist bei neu anlegen wichtig damit nicht die neuen bilder von anderen Usern beeinflusst werden
		//	
		// wegen #4972 auskommentiert
		// Uploaderfelder, die durch den GUI-Designer keinem Elternelement untergeordnet sind, haben als namepsace_id
		// auch 0....daher können bereits hochgeladene Dokumente nich von allen Benutzer wieder gelöscht werden
		// 
        #if($iNamespace <= 0){
        #    $sSql .= ' AND `creator_id` = :creator_id';
        #}
        
        $aResult = (array)DB::getPreparedQueryData($sSql, $aSql);
        foreach($aResult as $aFile){
            @unlink(Ext_TC_Util::getDocumentRoot().$aFile['path']);
        }
        
        // dann datenbank löschen
        $sSql = "DELETE FROM 
                `tc_uploader` 
            WHERE 
                `namespace_id` = :namespace_id AND 
                `namespace` = :namespace AND
				`object_id` = :object_id";
        
        // Bei "temnporären" einträgen die User id prüfen
        // ist bei neu anlegen wichtig damit nicht die neuen bilder von anderen Usern beeinflusst werden
		//	
		// wegen #4972 auskommentiert
		// Uploaderfelder, die durch den GUI-Designer keinem Elternelement untergeordnet sind, haben als namepsace_id
		// auch 0....daher können bereits hochgeladene Dokumente nich von allen Benutzer wieder gelöscht werden
		// 
        #if($iNamespace <= 0){
        #    $sSql .= ' AND `creator_id` = :creator_id';
        #}
        
        DB::executePreparedQuery($sSql, $aSql);
        
    }
    
    public static function updateEntry($iSelectedId, $iNamespace, $iNewNamespace, $sNamespace, $sNewNamespace){
        global $user_data;
        
        $aData = array(
			'object_id'		=> $iSelectedId,
            'namespace_id'  => $iNewNamespace,
            'namespace'     => $sNewNamespace
        );
        
        $sWhere = array(
            'namespace_id'  => $iNamespace,
            'namespace'     => $sNamespace,
			'object_id'		=> $iSelectedId
        );
        
        // Bei "temnporären" einträgen die User id prüfen
        // ist bei neu anlegen wichtig damit nicht die neuen bilder von anderen Usern beeinflusst werden
        if($iNamespace <= 0){
            $sWhere['creator_id'] = $user_data['id'];
        }
        
        DB::updateData('tc_uploader', $aData, $sWhere, true);
        
    }
    
    public static function search($iSelectedId, $iNamespace, $sNamespace){
        global $user_data;
        
        $aSql = array(
			'object_id'			=> $iSelectedId,
            'namespace_id'      => $iNamespace,
            'namespace'         => $sNamespace,
            'creator_id'        => $user_data['id']
        );
        
        // Zuerst suchen und dateien im system löschen
        $sSql = "SELECT * FROM 
                    `tc_uploader` WHERE 
                `namespace_id` = :namespace_id AND 
                `namespace` = :namespace";
        
        // Bei "temporären" Einträgen die User id prüfen
        // ist bei neu anlegen wichtig damit nicht die neuen bilder von anderen Usern beeinflusst werden
		//
		// wegen #4972 auskommentiert
		// Uploaderfelder, die durch den GUI-Designer keinem Elternelement untergeordnet sind, haben als namepsace_id
		// auch 0....daher können bereits hochgeladene Dokumente nich von allen Benutzer gesehen werden
		//
        #if($iNamespace <= 0){			
			#$sSql .= ' AND `creator_id` = :creator_id';
        #}
        
        $aResult = (array)DB::getPreparedQueryData($sSql, $aSql);
        
        return $aResult;
    }

	/**
	 * Liefert alle Uploads zu einem Objekt in der gewünschten Section
	 * 
	 * @param int $iObjectId
	 * @param string $sSection
	 * @return Ext_TC_Uploader[]
	 */
	public static function searchGuiDesignerUploads($iObjectId, $sSection) {
		
		if(!$sSection) {
			return array();
		}
		
		$oRepository = Ext_TC_Uploader::getRepository();
		$aUploads = $oRepository->findBy(array('object_id' => $iObjectId));
		$aReturn = array();
		
		foreach($aUploads as $oUpload) {
			// GUI-Design-Element aus dem Namespace lesen
			$sNamespace = str_replace('gui_designer_', '', $oUpload->namespace);
			$aNamespaceData = explode('_', $sNamespace);
			$iDesignElement = (int)array_shift($aNamespaceData);
			
			$oDesignElement = Ext_TC_Gui2_Design_Tab_Element::getInstance($iDesignElement);
			// Section überprüfen
			if($oDesignElement->getSection() == $sSection) {
				$aReturn[$oUpload->id] = $oUpload;
			}
		}
		
		return $aReturn;
	}
}