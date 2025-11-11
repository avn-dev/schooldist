<?php

/**
 * @TODO Hier ist sehr viel redundant mit Ext_Gui2_Html!
 */
class Ext_Gui2_Page {

	/**
	 * @var array
	 */
	protected $_aElements = array();
	
	/**
	 * @var array
	 */
    protected $_aConfig = array(
		'height' => 50, // Höhe der oberen GUI in %
		'min_height' => 250 // Mindesthöhe der oberen GUI in px
	);

	/**
	 *
	 * @var \Ext_Gui2
	 */
	protected $oParentGui;

	/**
	 * @var string
	 */
	protected $sTemplate = 'system/bundles/Gui2/Resources/views/page.tpl';

	public function setTemplate($sTemplate) {
		$this->sTemplate = $sTemplate;
	}

	public function setElement(&$oGui, $aParentGuiData = array()) {

		if(is_object($oGui)){

			$oAccess = Access::getInstance();

			// Zugriff prüfen
			$sAccess = $oGui->access;

			if(!empty($sAccess)) {
				if($oAccess) {
					$bAccess = $oAccess->hasRight($sAccess);
				} else {
					$bAccess = false;
				}
				if($bAccess !== true) {
					return;
				}
			}

			// Erste Gui
			if(empty($this->_aElements)) {
				$this->oParentGui = $oGui;
			}

			if(!empty($aParentGuiData)){
				if(isset($aParentGuiData['hash'])) {
					$oGui->parent_hash			= $aParentGuiData['hash'];
					// Objekt auch setzen, damit man darauf zugreifen kann
					if($oGui->parent_hash === $this->oParentGui->hash) {
						$oGui->setParent($this->oParentGui);
					}
				}
				if(isset($aParentGuiData['foreign_key'])) {
					$oGui->foreign_key			= $aParentGuiData['foreign_key'];
				}
				if(!empty($aParentGuiData['foreign_key_alias'])){
					$oGui->foreign_key_alias	= $aParentGuiData['foreign_key_alias'];
				}
				if(isset($aParentGuiData['foreign_jointable'])) {
					$oGui->foreign_jointable	= $aParentGuiData['foreign_jointable'];
				}
				if(isset($aParentGuiData['parent_primary_key'])) {
					$oGui->parent_primary_key	= $aParentGuiData['parent_primary_key'];
				}
				if(isset($aParentGuiData['filter'])) {
					$oGui->parent_filter		= (int)$aParentGuiData['filter'];
				}
				if(isset($aParentGuiData['reload']) && $aParentGuiData['reload'] == true){
                    $aParentGui = (array)$oGui->parent_gui;
					$aParentGui[] = $aParentGuiData['hash'];
                    $oGui->parent_gui = $aParentGui;
					$oGui->force_reload	= (boolean)($aParentGuiData['force_reload'] ?? false);
				}

			}
			$oGui->load_admin_header = 0;

		}

		$this->_aElements[] = $oGui;

	}

	/**
	 * @return Ext_Gui2[]
	 */
	public function getElements() {
		return $this->_aElements;
	}
	
    /*
     * Setzt Konfigurationsparameter
     */
    public function __set($sConfig, $mValue){
		if(isset($this->$sConfig)){
			$this->$sConfig = $mValue;
		}
		$this->setConfig($sConfig, $mValue);
	}
    
    /*
     * Setzt Konfigurationsparameter
     */
    public function setConfig($sConfig, $mValue){
		
		if(key_exists($sConfig, $this->_aConfig)){
			if($this->checkConfig($sConfig, $mValue)){
				$this->_aConfig[$sConfig] = $mValue;
			} else {
				throw new Exception("Configuration wrong [".$sConfig."]");	
			}
		} else {
			throw new Exception("Configuration unknown [".$sConfig."]");
		}
		
	}
    
    public function checkConfig($sConfig, $mValue){
		return true;
	}
    
    
	public function setGui(&$oGui, $aParentGuiData = array(), $aFilter = array()){

		$aParentGuiData['filter'] = 0;

		if(!empty($aFilter)){
			$aParentGuiData['filter'] = 1;

			$oBar = $oGui->createBar();
			$oBar->visible = false;
			$oBar->width = '100%';
			$oBar->position = 'top';

			foreach((array)$aFilter as $oFilter){
				$oBar->setElement($oFilter);
			}

			$oGui->setBar($oBar);

		}		
		
		$this->setElement($oGui, $aParentGuiData);

	}

	/**
	 * @TODO Hier gibt es massig Redundanzen mit der normalen GUI
	 *
	 * @param array $aOptionalHeaderData
	 */
	public function display($aOptionalHeaderData = array()){

		// Bei nur einer Gui sofort ausgeben
		if(count($this->_aElements) === 1) {

			$oGui = reset($this->_aElements);
			if($oGui instanceof Ext_Gui2) {
				$oGui->load_admin_header = true;
				$oGui->display($aOptionalHeaderData);
				return;
			}
		}

		$aGuiCssFiles = [];
		$aGuiJsFiles = [];
		$aGuiJsBottomFiles = [];

		foreach((array)$this->_aElements as $iKey => $oGui) {

			if(!is_object($oGui)){
				if(empty($oGui['hash'])){
					$this->_aElements[$iKey]['hash'] = md5(rand(1, 9999));
				}
				if(empty($oGui['title'])){
					$this->_aElements[$iKey]['title'] = '- No Title defined -';
				}
			}

			// Alle JS- und CSS-Dateien der GUIs der Page mergen
			elseif($oGui instanceof Ext_Gui2) {

				$oGuiHtml = new Ext_Gui2_Html($oGui);

				$aGuiCssFiles = array_merge($aGuiCssFiles, $oGuiHtml->getAdditionalCSS());

				$aGuiJsFiles = array_merge($aGuiJsFiles, $oGuiHtml->getAdditionalJS());

				$aGuiJsBottomFiles = array_merge($aGuiJsBottomFiles, $oGuiHtml->aJSBottom);

			}
		}

		$aOptionalHeaderData['js'] = array_merge($aGuiJsFiles, (array)$aOptionalHeaderData['js']);
		$aOptionalHeaderData['js_bottom'] = array_merge($aGuiJsBottomFiles, (array)($aOptionalHeaderData['js_bottom'] ?? []));
		$aOptionalHeaderData['css'] = array_merge($aGuiCssFiles, (array)$aOptionalHeaderData['css']);

		$oHtml = new Ext_Gui2_Html(reset($this->_aElements));
		
		$aOptions = array();
		$aOptions['js'] = '';
		$aOptions['additional'] = '';
		$aOptions['additional_top'] = '';
		$aOptions['additional_bottom'] = '';

		foreach((array)$aOptionalHeaderData as $sKey => $mOptional){
			if(is_string($mOptional)){
				$aOptions['additional'] .= $mOptional;
			} else {
				$mOptional = array_unique($mOptional);
				foreach((array)$mOptional as $sOptional){
					if($sKey == 'css'){
						$aOptions['additional'] .= '<link rel="stylesheet" href="'.$sOptional.\Ext_Gui2_Html::buildCacheBustingParam($sOptional).'" />'."\n";
					} elseif($sKey == 'js') {
						$sType = strpos($sOptional, '.mjs') === false ? 'text/javascript' : 'module';
						$aOptions['js'] .= '<script type="'.$sType.'" src="'.$sOptional.\Ext_Gui2_Html::buildCacheBustingParam($sOptional).'"></script>'."\n";
					} elseif($sKey == 'js_bottom') {
						$aOptions['additional_bottom'] .= '<script type="text/javascript" src="'.$sOptional.\Ext_Gui2_Html::buildCacheBustingParam($sOptional).'"></script>'."\n";
					}
				}
			}
			
		}

		ob_start();

		// Achtung: Alles redundant in Ext_Gui2_Html
		echo '
		<script type="text/javascript">
			if(!aGUI){
				var aGUI = {};
			}

			var sJsonPageData;
			var sParentGuiHash;
			var sTopGuiHash;
			var bUseParentFilter = 0;

			function initPage() {
				
				/*if (window.__FIDELO__) {
					window.__FIDELO__.Gui2.watchComponents();
				}*/

				if($(\'myMessage\')) {
					$(\'myMessage\').show();
				}
			';

		foreach((array)$this->_aElements as $oGui){

			if(is_object($oGui)) {
				$sTempHash = $oGui->hash;
				$sTempInstanceHash = $oGui->instance_hash;
				$sTempJsClass = $oGui->class_js;
				if($oGui->showLeftFrame == false) {
					$iShowLeftFrame = 0;
				} else {
					$iShowLeftFrame = 1;
				}
				
				$iDebugMode = 0;
				if(System::d('debugmode')) {
					$iDebugMode = 1;
				}		

				echo '
						aGUI[\''.$sTempHash.'\'] = new '.$sTempJsClass.'(\''.$sTempHash.'\', '.$iShowLeftFrame.', '.$iDebugMode.');
						aGUI[\''.$sTempHash.'\'].instance_hash = \''.$sTempInstanceHash.'\';
						aGUI[\''.$sTempHash.'\'].name = \''.$oGui->name.'\';
						aGUI[\''.$sTempHash.'\'].sLanguage = \''.System::d('systemlanguage').'\';
					';
				
				if($oGui->api) {
					echo '
						aGUI[\''.$sTempHash.'\'].addAPIMenu();
					';
				}
			
			}

		}

		$aPageData = $this->getPageData();

		foreach((array)$this->_aElements as $oGui){
			
			if(is_object($oGui)){
				echo '
						sJsonPageData = \''.(string)json_encode($aPageData).'\';
						sParentGuiHash = \''.(string)$oGui->parent_hash.'\';
						bUseParentFilter = \''.(int)$oGui->parent_filter.'\';
						aGUI[\''.$oGui->hash.'\'].aPageData = sJsonPageData.evalJSON();
						aGUI[\''.$oGui->hash.'\'].sParentGuiHash = sParentGuiHash;
						aGUI[\''.$oGui->hash.'\'].bUseParentFilter = bUseParentFilter;
						if(sParentGuiHash != ""){
							var oParentGui = aGUI[sParentGuiHash];
							oParentGui.aChildGuiHash[oParentGui.aChildGuiHash.length] = \''.$oGui->hash.'\';
						}

					';
			}

		}

		// Erste GUI finden
		$aGuis = $this->_aElements;
		$oParentGui = array_shift($aGuis);
		
		echo 'sTopGuiHash = \''.$oParentGui->hash.'\';';
		echo 'aGUI[\''.$oParentGui->hash.'\'].bPageTopGui = true;';
		echo 'aGUI[\''.$oParentGui->hash.'\'].setPageEvents();';
		echo 'aGUI[\''.$oParentGui->hash.'\'].loadTable(true);';

		// Parent GUI den ersten Child Hash setzten
		foreach((array)$aGuis as $oGui){

			if(is_object($oGui)){
				$sTempHash = $oGui->hash;
				
				echo '
				aGUI[\''.$oParentGui->hash.'\'].sCurrentActiveChildHash = \''.$sTempHash.'\';
				';
			}
			
			break;
		}

		$bFirstChild = true;
		foreach((array)$aGuis as $oGui){

			if(
				is_object($oGui) &&
				(
					$bFirstChild ||
					$oGui->force_reload		
				)
			){
				echo '
					aGUI[\''.$oGui->hash.'\'].loadTable(true);
					';
				$bFirstChild = false;
			}

			if(
				is_object($oGui) &&
				$oGui->force_reload
			){
				echo '
					aGUI[\''.$oGui->hash.'\'].force_reload = 1;
					';
			}
		}

		echo '
			}
		</script>';

		echo $this->generateHTML(true);
		
		$sHtml = ob_get_clean();

		$oSmarty = new SmartyWrapper;
		$oSmarty->assign('oGui', $oParentGui);
		$oSmarty->assign('sHtml', $sHtml);
		$oSmarty->assign('aOptions', $aOptions);
		$oSmarty->assign('sJs', $oHtml->getJsFooter($aOptions));
		$oSmarty->display($this->sTemplate);

		#Admin_Html::loadAdminFooter();

	}
	
	public function getPageData() {
		$aPageData = array();
		$aPageData['gui_count'] = (int)count($this->_aElements);
        $aPageData['config'] = $this->_aConfig;
		return $aPageData;
	}
	
	public function generateHTML($bFirst=false) {

		// Erste GUI finden
		$aGuis = $this->_aElements;
		$oParentGui = array_shift($aGuis);

		ob_start();

		// Erste Gui ausgeben
		$oParentGui->startPageOutput($bFirst);
		echo '<div class="divHeaderSeparator">
			<div class="Gui2ChildTableDraggable header-line" id="Gui2ChildTableDraggable_'.$oParentGui->hash.'"></div>
		</div>';
			
		// UL/Li der Weiteren GUIs ausgeben
		echo '<div class="Gui2ChildTableButtonContainer">
					<ul class="Gui2ChildTableButtons">';

		$bFirstChild = true;
		foreach($aGuis as $oGui) {
		
			if($bFirstChild){
				$sClass = 'Gui2ChildTableButtonActive';
			} else {
				$sClass = '';
			}

			if(is_object($oGui)) {
				$sTempHash = $oGui->hash;
				$sTempTitle = $oGui->gui_title;
			} else {
				$sTempHash = $oGui['hash'];
				$sTempTitle = $oGui['title'];
			}

			$sChangeAction = '<span>'.$sTempTitle.'</span>';

			echo '			<li class="'.$sClass.' Gui2ChildTableButton" id="Gui2ChildTableButton_'.$sTempHash.'" style="cursor:pointer;" onclick="aGUI[\''.$sTempHash.'\'].prepareSwitchTable(\''.$sTempHash.'\'); return false;">
								'.$sChangeAction.'
							</li>';

			$bFirstChild = false;
		}

		echo '		</ul>
				<div class="divCleaner"></div>
			</div>';

		$i = 1;
		foreach($aGuis as $oGui) {

			if(is_object($oGui)) {
				$sTempHash = $oGui->hash;
			} else {
				$sTempHash = $oGui['hash'];
			}

			if($i == 1){
				$sStyle = '';
			} else {
				$sStyle = 'display:none';
			}

			echo '<div id="divGui2ChildContainer_'.$sTempHash.'" class="divGui2ChildContainer" style="'.$sStyle.'">';

			if(is_object($oGui)) {
				$oGui->startPageOutput(false);
			} else {
				echo (string)$oGui['html'];
			}

			echo '</div>';

			$i++;
		}
		
		$sHtml = ob_get_clean();
		
		return $sHtml;
	}

}
