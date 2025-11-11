<?php


class Ext_Gui2_Html {
	
	/**
	 * @var Ext_Gui2 
	 */
	protected $_oGui;
	public $aJS = array();
	//public $aJSTop = array();
	public $aJSBottom = array();
	public $aCss = array();

	
	public function __construct(&$oGui) {

		$this->_oGui = &$oGui;

		$this->aJS[] = '/admin/extensions/gui2/simple-dialog.js';
		$this->aJS[] = '/admin/extensions/gui2/gui2.js';
		$this->aJS[] = '/admin/extensions/gui2/jquery/fastselect/fastselect.standalone.js'; // TODO Entfernen
		$this->aJS[] = '/assets/core/js/vue.js';
		$this->aJS[] = '/assets/gui/js/gui2.js';
		$this->aJS[] = '/admin/assets/interface/js/admin-iframe.js';

		$this->aCss[] = '/admin/extensions/gui2/simple-dialog.css';
		$this->aCss[] = '/admin/extensions/gui2/gui2.css';
		$this->aCss[] = '/assets/gui/css/gui2.css';

		$this->aCss[] = '/admin/assets/css/admin.css';
		//$this->aCss[] = '/admin/extensions/gui2/jquery/css/ui.multiselect.css';
		$this->aCss[] = '/admin/extensions/gui2/jquery/fastselect/fastselect.css'; // TODO Entfernen


		$this->aCss[] = '/assets/core/jquery/jquery-ui.min.css';
		//$this->aCss[] = '/assets/core/jquery/jquery-ui.theme.min.css';

		if ($this->_oGui->include_bootstrap_tagsinput) {
			$this->aJS[] = '/assets/filemanager/js/bootstrap-tagsinput.js';
			$this->aCss[] = '/assets/filemanager/css/bootstrap-tagsinput.css';
		}

		if($this->_oGui->include_jquery_contextmenu) {
			$this->aCss[] = '/admin/extensions/gui2/jquery/contextmenu/contextmenu.css';
		}

	}

	public function setJs($sJs){
		$this->aJS[] = $sJs;
	}

	public function setCss($sCss){
		$this->aCss[] = $sCss;
	}

	/**
	 * Gibt ein Array mit allen weiteren JS Pfaden zurück
	 * @return array
	 */
	public function getAdditionalJS(){

		$aFiles = $this->_oGui->getJSandCSSFiles();
		$aMajorJsFiles = (array)$aFiles['js'];

		$this->aJS = array_merge($this->aJS, $aMajorJsFiles);

		$this->aJS = array_unique($this->aJS);

		return $this->aJS;
	}
		
	/**
	 * Gibt ein Array mit allen weiteren CSS Pfaden zurück
	 * @return array
	 */
	public function getAdditionalCSS(){
		
		$aFiles = $this->_oGui->getJSandCSSFiles();
		$aMajorCssFiles = (array)$aFiles['css'];
		$this->aCss	= array_merge($this->aCss, $aMajorCssFiles);

		$this->aCss = array_unique($this->aCss);

		return $this->aCss;
	}
		
	public function generateHtml($bNoJavaScript=false) {
		global $session_data;

		$aOptions = $this->generateHtmlHeader();

		$sHtml = '';

		if($this->_oGui->canDisplay()) {

			if($bNoJavaScript === false) {
				$sHtml .= $this->getJsInitializationCode();
			}

			$sHtml .= '<div id="guiLoadingDiv" style="display:none;"><i class="fa fa-spinner fa-spin"></i></div>';

			$aBars = $this->_oGui->getBarList();

			// Bardiv nur anzeigen wenn Barelemente existieren oder ein Guititel gesetzt ist
			if($this->_oGui->load_admin_header == 1 || !empty($aBars)){
				$sHtml .= '<div id="divHeader_'.$this->_oGui->hash.'" class="divHeader clearfix">
					<div class="divHeaderSeparator gui2"><div class="header-line"></div></div>';
				if(
					$this->_oGui->load_admin_header == 1 &&
					$this->_oGui->gui_title != ''
				) {

				}
				$sHtml .= '
					<div id="guiTableBars_'.$this->_oGui->hash.'" class="clearfix guiTableBars"></div>
				</div>';
			}
			$sHtml .= '
				<div id="divBody_'.$this->_oGui->hash.'" class="divBody">
					<div id="guiTableHead_'.$this->_oGui->hash.'" class="guiTableHeadContainer"></div>
					<div id="guiScrollBody_'.$this->_oGui->hash.'"></div>
					<div style="display:none;" id="guiTableSum_'.$this->_oGui->hash.'"></div>
				</div>
				<div id="divFooter_'.$this->_oGui->hash.'" class="divFooter clearfix">
					<div id="guiTableBarsBottom_'.$this->_oGui->hash.'"></div>
					<div class="divCleaner"></div>
				</div>
					';	

		}
		else
		{
			$oDialog 		= $this->_oGui->createDialog();
			$oNotification 	= $oDialog ->createNotification(L10N::t('Fehler'), L10N::t('Sie haben keine Rechte für diese Liste!'), 'error', [
				'dismissible' => false
			]);
			$sHtml 			= $oNotification->generateHtml();
			
			
		}
		
		if($this->_oGui->load_admin_header == 1) {

			$oSmarty = new SmartyWrapper;
			$oSmarty->assign('oGui', $this->_oGui);
			$oSmarty->assign('sHtml', $sHtml);
			$oSmarty->assign('sJs', $this->getJsFooter());
			$oSmarty->assign('aOptions', $aOptions);
			$oSmarty->display('system/bundles/Gui2/Resources/views/page.tpl');

		} else {
			echo $sHtml;
		}
	}

	public function getJsInitializationCode() {

		if($this->_oGui->showLeftFrame == false) {
			$iShowLeftFrame = 0;
		}else{
			$iShowLeftFrame = 1;
		}

		$sHtml = '';

		// Achtung: Alles redundant in Ext_Gui2_Page
		$sHtml .= '
					<script type="text/javascript">

						if(!aGUI){
							var aGUI = {};
							var sTopGuiHash; // nötig fals ne Page in nem Dialog ist!
						}
						';
		if(
			$this->_oGui->load_admin_header == 1 ||
			$this->_oGui->init_observer
		) {
			$sHtml .= '
						function initPage() {
						
							/*if (window.__FIDELO__) {
								window.__FIDELO__.Gui2.watchComponents();
							}*/
						
							if($(\'myMessage\')) {
								$(\'myMessage\').show();
							}
						';
		} else {
			$sHtml .= '';
		}

		$iDebugMode = 0;
		if(System::d('debugmode')) {
			$iDebugMode = 1;
		}

		$bPublic = false;

		$sHtml .= '
							aGUI[\''.$this->_oGui->hash.'\'] = new '.$this->_oGui->class_js.'(\''.$this->_oGui->hash.'\', '.$iShowLeftFrame.', '.$iDebugMode.', \''.$this->_oGui->instance_hash.'\', '.(int)$bPublic.');
							aGUI[\''.$this->_oGui->hash.'\'].instance_hash = \''.$this->_oGui->instance_hash.'\';
							aGUI[\''.$this->_oGui->hash.'\'].name = \''.$this->_oGui->name.'\';
							aGUI[\''.$this->_oGui->hash.'\'].sLanguage = \''.System::d('systemlanguage').'\';';
				
		if($this->_oGui->api) {
			$sHtml .= '
							aGUI[\''.$this->_oGui->hash.'\'].addAPIMenu();
			';
		}

		// Hash der ElternGUI
		if($this->_oGui->parent_hash) {
			$sHtml .= '
							sParentGuiHash = \''.(string)$this->_oGui->parent_hash.'\';
							aGUI[\''.$this->_oGui->hash.'\'].sParentGuiHash = sParentGuiHash;
						';
		} else {
			$sHtml .= '
							aGUI[\''.$this->_oGui->hash.'\'].bPageTopGui = true;';
		}

		// Anzeigebereich (Option wenn mehrere GUIs über eine Classe gehandelt werden
		if($this->_oGui->sView != '') {
			$sHtml .= '
							sView = \''.(string)$this->_oGui->sView.'\';
							aGUI[\''.$this->_oGui->hash.'\'].sView = sView;';
		}

		if ($this->_oGui->hasDialogOnlyMode()) {
			$sHtml .= '
							aGUI[\''.$this->_oGui->hash.'\'].bOnlyDialogMode = true;
			';
		} else {
			$sHtml .= '
						aGUI[\'' . $this->_oGui->hash . '\'].loadTable(true);';
		}

		if (!empty($aOnloadActions = $this->_oGui->getOnloadActions())) {
			foreach ($aOnloadActions as $sFunctionCall) {
				$sFunctionCall = \Illuminate\Support\Str::start($sFunctionCall, '.');
				$sFunctionCall = \Illuminate\Support\Str::finish($sFunctionCall, ';');

				$sHtml .= '
							aGUI[\''.$this->_oGui->hash.'\']'.$sFunctionCall;
			}
		}

		if(
			$this->_oGui->load_admin_header == 1 ||
			$this->_oGui->init_observer
		) {
			$sHtml .= '

						}
						';
		} else {
			$sHtml .= '
						';
		}

		$sHtml .= '
					</script>';

		return $sHtml;
	}

	public function getJsFooter(array $aOptions = null) {

		$oSmarty = new SmartyWrapper;
		$oSmarty->assign('bJqueryNoConflict', true);
		$sFooterJs = $oSmarty->fetch('system/bundles/AdminLte/Resources/views/footer.js.inc.tpl');

		if($aOptions === null) {
			$aOptions = $this->generateHtmlHeader();
		}

		$sJs = '
		<script type="text/javascript" src="/admin/js/prototype/prototype.js?v='.\System::d('version').'"></script>
		<!--<script type="text/javascript" src="/admin/js/scriptaculous/scriptaculous.js?v='.\System::d('version').'"></script>-->
		<script type="text/javascript" src="/admin/js/hook.js?v='.\System::d('version').'"></script>
		
		<script type="text/javascript" src="/tinymce/resource/basic/tinymce.min.js?v='.\System::d('version').'"></script>
		
		'.$sFooterJs.'
		
		<script>			
			// Resolve name collision between jQuery UI and Twitter Bootstrap
			var datepicker = jQuery.fn.datepicker.noConflict();
			jQuery.fn.bootstrapDatePicker = datepicker; 
			
			var tooltip = jQuery.fn.tooltip.noConflict();
			jQuery.fn.bootstrapTooltip = tooltip;
		</script>
		
	    <script src="/assets/core/jquery/jquery-ui.min.js?v='.\System::d('version').'"></script>
	
		'.$aOptions['js'].'
		
	    <script src="/admin/extensions/gui2/jquery/ui/ui.multiselect.js?v='.\System::d('version').'"></script>
	    <script src="/admin/extensions/gui2/jquery/contextmenu/contextmenu.js?v='.\System::d('version').'"></script>
		';

		return $sJs;
	}

	/**
	 * TODO: Umbenennen, da hier nur noch ein Array zurückkommt
	 *
	 * @return array
	 */
	public function generateHtmlHeader() {

		$aOptions = array();
		$aOptions['js'] = '';
		$aOptions['additional'] = '';
		$aOptions['additional_top'] = '';
		$aOptions['additional_bottom'] = '';

		// Zusätzliches JS Laden
		$aJS = $this->getAdditionalJs();

		foreach($aJS as $sJS) {
			$sType = strpos($sJS, '.mjs') === false ? 'text/javascript' : 'module';
			$aOptions['js'] .= '<script type="'.$sType.'" src="'.$sJS.self::buildCacheBustingParam($sJS).'"></script>'."\n";
		}

//		if($this->_oGui->include_jquery) {
//			$aOptions['additional'] .= '
//			<script type="text/javascript">
//				var $j = jQuery.noConflict();
//			</script>';
//		}

		// Zusätzliches CSS Laden
		$aCSS = $this->getAdditionalCSS();
		foreach((array)$aCSS as $sCSS){
			$aOptions['additional'] .= '<link rel="stylesheet" href="'.$sCSS.self::buildCacheBustingParam($sCSS).'" />'."\n";
		}

		// JS nach zusätzlichem JS (was z.B. jQuery benötigt)…
		foreach($this->aJSBottom as $sJS) {
			$aOptions['additional_bottom'] .= '<script type="text/javascript" src="'.$sJS.self::buildCacheBustingParam($sJS).'"></script>'."\n";
		}

		if($this->_oGui->load_admin_header == 1) {
			$aOptions['xhtml'] = true;
		}

		return $aOptions;
	}

	public static function buildCacheBustingParam(string $sFile): string {

		$sParam = '?v='.System::d('version');

		// Vermeiden, Cache ganz aussschalten zu müssen, weil das bei der Flut an JS-Dateien viel zu lange dauert
		if (System::d('debugmode') > 0) {
			// TODO Das funktioniert nicht so optimal, weil kein Debugger-Breakpoint so jemals erhalten bleibt
//			$sPath = \Util::getDocumentRoot().'system/legacy'.$sFile;
//			if (is_file($sPath)) {
//				$sParam .= '&modified='.filemtime($sPath);
//			}
		}

		return $sParam;

	}

	public function generatePageHtml($bFirst = true) {

		$sHtml = '';

		$sHtml .= '
			<div id="guiLoadingDiv" style="display:none;"><i class="fa fa-spinner fa-spin"></i></div>

			<div id="divHeader_'.$this->_oGui->hash.'" class="divHeader clearfix" style="">';

		if ($bFirst) {
			$sHtml .= '<div class="divHeaderSeparator"><div class="header-line"></div></div>';
		}

		$sHtml .= '

				<div class="divCleaner"></div>
				<div id="guiTableBars_'.$this->_oGui->hash.'" class="clearfix guiTableBars"></div>
				<div class="divCleaner"></div>
			</div>
			<div id="divBody_'.$this->_oGui->hash.'" class="divBody">
				<div id="guiTableHead_'.$this->_oGui->hash.'" class="guiTableHeadContainer"></div>
				<div id="guiScrollBody_'.$this->_oGui->hash.'"></div>
				<div style="display:none;" class="divSum" id="guiTableSum_'.$this->_oGui->hash.'"></div>
			</div>
			<div id="divFooter_'.$this->_oGui->hash.'" class="divFooter clearfix">
				<div id="guiTableBarsBottom_'.$this->_oGui->hash.'"></div>
				<div class="divCleaner"></div>
			</div>
				';

		echo $sHtml;

	}

    /**
     * create the index message div
     * @param string $sIndex
     * @param int $iCompleted
     * @param int $iTotal
     * @return \Ext_Gui2_Html_Div 
     */
    public static function getIndexRefreshDiv($sIndex, $iCompleted, $iTotal, $sInfoText) {
    
        $oDiv = new Ext_Gui2_Html_Div();
        
        $oHidden = new Ext_Gui2_Html_Input();
        $oHidden->type  = "hidden";
        $oHidden->id    = "index_completed_hidden";
        $oHidden->value = $iCompleted;
        $oDiv->setElement($oHidden);
        
        $oHidden = new Ext_Gui2_Html_Input();
        $oHidden->type  = "hidden";
        $oHidden->id    = "index_total_hidden";
        $oHidden->value = $iTotal;
        $oDiv->setElement($oHidden);
        
        $oHidden = new Ext_Gui2_Html_Input();
        $oHidden->type  = "hidden";
        $oHidden->id    = "index_name_hidden";
        $oHidden->value = $sIndex;
        $oDiv->setElement($oHidden);

        $sInfoText = str_replace('{completed}', '<span id="index_stack_completed">'.$iCompleted.'</span>', $sInfoText);
        $sInfoText = str_replace('{total}', '<span id="index_stack_total">'.$iTotal.'</span>', $sInfoText);
        
        $sHtml = '
            <p id="index_stack_text">'.$sInfoText.'</p>
            <div id="index_stack_loader" class="loader">
            <div id="index_stack_loader_bar" style="width: 0px;"></div>
            </div>
        ';
         
         $oDiv->setElement($sHtml);
        
         return $oDiv;

    }
	
	static public function getIconObject($sValue) {

		if(
			strpos($sValue, 'fab ') === 0 ||
			strpos($sValue, 'far ') === 0 ||
			strpos($sValue, 'fas ') === 0
		) {
			$oIcon = new Ext_Gui2_Html_I();
			$oIcon->class = $sValue;
		} elseif(strpos($sValue, 'fa-') === 0) {
			$oIcon = new Ext_Gui2_Html_I();
			$oIcon->class = 'fa '.$sValue;
		} else {
			$oIcon = new Ext_Gui2_Html_Image();
			$oIcon->src = $sValue;
		}
		
		return $oIcon;
	}
	
}