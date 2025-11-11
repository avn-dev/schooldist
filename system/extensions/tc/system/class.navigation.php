<?PHP
/**
 * @author Christian Wielath
 */
class Ext_TC_System_Navigation {
	
	protected $_aTopNavigation = array();
	protected $_iCount = 101;
	
	static protected $aInstance;

	static public function getInstance() {
		
		if(static::$aInstance === null) {
			$sClass = get_called_class();
			static::$aInstance = new $sClass;
		}

		return static::$aInstance;
	}
	
	/**
	 * Set all Navigation Elements
	 */ 
	protected function __construct(){

		$iSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		
		$sCacheKey = get_class().'_'.$iSchoolId;
		
		$this->_aTopNavigation = WDCache::get($sCacheKey);
		
		if($this->_aTopNavigation === null) {

			$this->_aTopNavigation = [];
			
			$this->setStructure();

			WDCache::set($sCacheKey, (60*60), $this->_aTopNavigation, false, \Admin\Helper\Navigation::CACHE_GROUP_KEY);

		}

	}

	protected function setStructure() {
		
		$oTop = new Ext_TC_System_Navigation_TopItem();
		$oTop->sName		= "tc_admin";
		$oTop->sTitle		= "Admin";
		$oTop->sL10NAddon	= "Admin";
		$oTop->iExtension	= 0;
		$oTop->sKey			= "admin";
		$oTop->iLoadContent = 0;
		
			$oChildTop = new Ext_TC_System_Navigation_LeftItem();
			$oChildTop->mAccess			= array('core_config', '');
			$oChildTop->sTitle			= "Einstellungen";
			$oChildTop->sL10NAddon		= "Configurations";
			$oChildTop->iSubpoint		= 0;
			$oChildTop->sUrl			= "/admin/extensions/tc/config.html";
			$oChildTop->sKey			= "admin.config";
			$oTop->addChild($oChildTop);
			
			$oChildTop = new Ext_TC_System_Navigation_LeftItem();
			$oChildTop->mAccess			= array('core_gui2_designer', '');
			$oChildTop->sTitle			= "Dialog Designs";
			$oChildTop->sL10NAddon		= "Dialog Designs";
			$oChildTop->iSubpoint		= 0;
			$oChildTop->sUrl			= "/admin/extensions/tc/gui2/designer.html";
			$oChildTop->sKey			= "admin.gui2.designer";
			$oTop->addChild($oChildTop);
		
			$oChildTop = new Ext_TC_System_Navigation_LeftItem();
			$oChildTop->mAccess			= array('core_admin_user', '');
			$oChildTop->sTitle			= "Benutzerverwaltung";
			$oChildTop->sL10NAddon		= "User management";
			$oChildTop->iSubpoint		= 0;
			$oChildTop->sUrl			= "#";
			$oChildTop->sKey			= "admin.users";
			$oTop->addChild($oChildTop);
			
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('core_admin_user', '');
			$oChild->sTitle			= "Benutzer";
			$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » User";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= "/admin/extensions/tc/admin/user.html";
			$oChild->sKey		= "admin.users.list";
			$oTop->addChild($oChild);
			
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('core_admin_user', 'group');
			$oChild->sTitle			= "Gruppen";
			$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Usergroups";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= "/admin/extensions/tc/admin/user/group.html";
			$oChild->sKey			= "admin.users.groups";
			$oTop->addChild($oChild);
		
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= "admin";
			$oChild->sTitle			= "Deployment";
			$oChild->sL10NAddon		= "Update";
			$oChild->iSubpoint		= 0;
			$oChild->sUrl			= "/admin/extensions/internal/update.html";
			$oChild->sKey			= "admin.deployment";
			$oTop->addChild($oChild);
		
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= "admin";
			$oChild->sTitle			= "Mother tongues";
			$oChild->sL10NAddon		= "Data";
			$oChild->iSubpoint		= 0;
			$oChild->sUrl			= "/admin/extensions/ti/data/mothertongues.html";
			$oChild->sKey			= "admin.mothertongues";
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= "admin";
			$oChild->sTitle			= "News";
			$oChild->sL10NAddon		= "Core";
			$oChild->iSubpoint		= 0;
			$oChild->sUrl			= "/admin/extensions/ti/news.html";
			$oChild->sKey			= "admin.news";
			$oTop->addChild($oChild);
			
			$oChildTop = new Ext_TC_System_Navigation_LeftItem();
			$oChildTop->mAccess			= "admin";
			$oChildTop->sTitle			= "Zugriff";
			$oChildTop->sL10NAddon		= "Access";
			$oChildTop->iSubpoint		= 0;
			$oChildTop->sUrl			= "#";
			$oChildTop->sKey			= "admin.access";
			$oTop->addChild($oChildTop);
			
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= "admin";
				$oChild->sTitle			= "Kategorien";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Categories";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/access/section/categories.html";
				$oChild->sKey			= "admin.access.categories";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= "admin";
				$oChild->sTitle			= "Bereiche";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Sections";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/access/sections.html";
				$oChild->sKey			= "admin.access.sections";
				$oTop->addChild($oChild);
			
			$oChildTop = new Ext_TC_System_Navigation_LeftItem();
			$oChildTop->mAccess			= "admin";
			$oChildTop->sTitle			= "Lizenz";
			$oChildTop->sL10NAddon		= "Licence";
			$oChildTop->iSubpoint		= 0;
			$oChildTop->sUrl			= "#";
			$oChildTop->sKey				= "admin.licences";
			$oTop->addChild($oChildTop);
			
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= "admin";
				$oChild->sTitle			= "Module";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Modules";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/licence/modules.html";
				$oChild->sKey			= "admin.licences.modules";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= "admin";
				$oChild->sTitle			= "Lizenzen";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Licences";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/licence.html";
				$oChild->sKey			= "admin.licences.list";
				$oTop->addChild($oChild);
			
			$oChildTop = new Ext_TC_System_Navigation_LeftItem();
			$oChildTop->mAccess			= array(
				array('core_admin_templates_fonts', ''),
				array('core_admin_templates_uploads', ''),
				array('core_admin_templates_pdf_layouts', ''),
				array('core_admin_templates_pdf_templates', ''),
				array('core_admin_templates_positiongroups', ''),
				array('core_frontend_templates', '')
			);
			$oChildTop->sTitle			= "Vorlagen";
			$oChildTop->sL10NAddon		= "Templates";
			$oChildTop->iSubpoint		= 0;
			$oChildTop->sUrl			= "#";
			$oChildTop->sKey			= "admin.templates";
			$oTop->addChild($oChildTop);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_templates_fonts', '');
				$oChild->sTitle			= "Schriftarten";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Fonts";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/gui2/page/Tc_fonts";
				$oChild->sKey			= "admin.templates.fonts";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_templates_uploads', '');
				$oChild->sTitle			= "Dateiuploads";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Uploads";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/uploads.html";
				$oChild->sKey			= "admin.templates.uploads";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_templates_pdf_layouts', '');
				$oChild->sTitle			= "PDF Layouts";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » PDF";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/pdf_layouts.html";
				$oChild->sKey			= "admin.templates.layouts";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_templates_pdf_templates', '');
				$oChild->sTitle			= "PDF Vorlagen";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » PDF";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/pdf_templates.html";
				$oChild->sKey			= "admin.templates.list";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_templates_positiongroups', '');
				$oChild->sTitle			= "Positionsgruppen";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Positiongroups";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/positiongroups.html";
				$oChild->sKey			= "admin.templates.positiongroups";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_frontend_templates', '');
				$oChild->sTitle			= "Frontend";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Frontend";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/gui2/page/Tc_frontend_templates";
				$oChild->sKey			= "admin.templates.frontend";
				$oTop->addChild($oChild);
		
			$oChildTop = new Ext_TC_System_Navigation_LeftItem();
			$oChildTop->mAccess			= array('core_config', '');
			$oChildTop->sTitle			= "Sonstiges";
			$oChildTop->sL10NAddon		= "Other";
			$oChildTop->iSubpoint		= 0;
			$oChildTop->sKey			= "admin.others";
			$oTop->addChild($oChildTop);
			
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_countrygroups', '');
				$oChild->sTitle			= "Ländergruppen";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Country groups";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/gui2/page/Tc_marketing_country_groups";
				$oChild->sKey			= "admin.country_groups";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_exchangerate', '');
				$oChild->sTitle			= "Wechselkurse";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Exchange rate";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/exchangerates/tables.html";
				$oChild->sKey			= "admin.exchangerates";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_accountscode', '');
				$oChild->sTitle			= "Kontenplan";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Accounts code";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/accounting/accountscode.html";
				$oChild->sKey			= "admin.accountscode";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_cancellation_conditions', '');
				$oChild->sTitle			= "Stornobedingungen";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Cancellation Conditions";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/cancellation_conditions.html";
				$oChild->sKey			= "admin.cancellation_conditions";
				$oTop->addChild($oChild);
								
			$oChildTop = new Ext_TC_System_Navigation_LeftItem();
			$oChildTop->mAccess		= array('core_admin_vat', '');
			$oChildTop->sTitle			= "Umsatzsteuer";
			$oChildTop->sL10NAddon		= $oChildTop->sL10NAddon;
			$oChildTop->iSubpoint		= 0;
			$oChildTop->sKey			= "admin.vat";
			$oTop->addChild($oChildTop);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_vat', '');
				$oChild->sTitle			= "Umsatzsteuersätze";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » VAT rates";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/vat.html";
				$oChild->sKey			= "admin.vat.list";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_vat_allocate', '');
				$oChild->sTitle			= "Zuweisungen";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » VAT rates";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/vat_allocation.html";
				$oChild->sKey			= "admin.vat.allocation";
				$oTop->addChild($oChild);
				
			$oChildTop = new Ext_TC_System_Navigation_LeftItem();
			$oChildTop->mAccess			= array('core_config', '');
			$oChildTop->sTitle			= "Kommunikation";
			$oChildTop->sL10NAddon		= "Thebing Core » Communication";
			$oChildTop->sKey			= "admin.communication";
			$oChildTop->iSubpoint		= 0;
			$oTop->addChild($oChildTop);
			
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_templates_email', '');
				$oChild->sTitle			= "Templates » E-Mail";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » E-Mail Templates";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/template/email.html";
				$oChild->sKey			= "admin.communication.templates";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_templates_email_layouts', '');
				$oChild->sTitle			= "Templates » E-Mail Layouts";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » E-Mail Layouts";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/gui2/page/Ts_examination_templates";
				$oChild->sKey			= "admin.communication.templates.layouts";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_templates_sms', '');
				$oChild->sTitle			= "Templates » SMS";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » SMS Templates";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/template/sms.html";
				$oChild->sKey			= "admin.communication.templates.sms";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_incomingfiles_categories', '');
				$oChild->sTitle			= "Eingehende Dateien Kategorien";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Incoming Files Categories";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/incomingfile/category.html";
				$oChild->sKey			= "admin.communication.incomingfile.categories";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_templates_blocks', '');
				$oChild->sTitle			= "Blöcke";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Blocks";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/template/blocks.html";
				$oChild->sKey			= "admin.communication.templates.blocks";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_emailaccounts', '');
				$oChild->sTitle			= "E-Mail-Konten";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » E-Mails";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/communication/emailaccount.html";
				$oChild->sKey			= "admin.communication.emailaccounts";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_admin_communication_category', '');
				$oChild->sTitle			= "Kategorien";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Categories";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/gui2/page/Tc_communication_category";
				$oChild->sKey			= "admin.communication.categories";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('core_communication_signatures', '');
				$oChild->sTitle			= "E-Mail Signaturen";
				$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » E-Mail Signatures";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= "/admin/extensions/tc/admin/communication/signatures.html";
				$oChild->sKey			= "admin.communication.signatures";
				$oTop->addChild($oChild);
			
		$this->addTopNavigation($oTop);
		
	}
	
	/**
	 * Get the Translation for the current File
	 * @todo Performance prüfen
	 * @return string 
	 */
	public static function t() {
		
		$aIndex = static::getIndex();
		
		if(isset($aIndex[$_SERVER['REQUEST_URI']])) {
			return $aIndex[$_SERVER['REQUEST_URI']]['title'];
		}

	}
	
	/**
	 * Get the Translation Path for the current File
	 * @return string 
	 */
	public static function tp() {

		$aIndex = static::getIndex();
		
		if(isset($aIndex[$_SERVER['REQUEST_URI']])) {
			return $aIndex[$_SERVER['REQUEST_URI']]['title_path'];
		}

	}
	
	static public function addIndexEntry($url, $title, $description) {
		
		$aIndex = static::getIndex();
		
		$aIndex[$url] = [
			'title_path' => $description,
			'title' => $title
		];
		
		$iSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		
		$sCacheKey = get_class().'_index_'.$iSchoolId;
		
		WDCache::set($sCacheKey, (60*60), $aIndex);
		
	}
	
	static public function getIndex() {
		
		$iSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		
		$sCacheKey = get_class().'_index_'.$iSchoolId;
		
		$aIndex = WDCache::get($sCacheKey);
		
		if($aIndex === null) {

			$aIndex = [];
			
			$oNavigation = static::getInstance();
			
			$aTopItems = $oNavigation->getTopNavigationObjects();
		
			$aTitle = [];
			
			foreach((array)$aTopItems as $oTop) {
				
				$aTitle[0] = $oTop->generateTitle();
				
				$aIndex[$oTop->sUrl] = [
					'title_path' => $oTop->generateTitlePath(),
					'title' => $aTitle[0]
				];

				$aChilds = $oTop->getChilds();
				foreach((array)$aChilds as $oChild) {

					if($oChild->iSubpoint) {
						$aTitle[2] = $oChild->generateTitle();
					} else {
						$aTitle[1] = $oChild->generateTitle();
						unset($aTitle[2]);
					}
					
					$aTitle = array_unique($aTitle);
					
					$aIndex[$oChild->sUrl] = [
						'title_path' => $oChild->generateTitlePath(),
						'title' => implode(" &raquo; ", $aTitle)
					];

				}
			}

			WDCache::set($sCacheKey, (60*60), $aIndex);

		}
		
		return $aIndex;
	}
	
	/**
	 * Add a Top Element of the Ext_TC_System_Navigation_TopItem Class to the Navigation
	 * @param Ext_TC_System_Navigation_TopItem $oTop 
	 */
	public function addTopNavigation($oTop){
		
		$oTop->iPosition = $this->_iCount;
		
		$this->_incrementElementPosition();
		
		// Linke Items mit Unterpunkten mit allen Unterrechten versehen
		$aChilds = $oTop->getChilds();

		$aChilds = array_reverse($aChilds);
		
		$aTempAccess = array();
		foreach($aChilds as $oChild) {
			if($oChild->iSubpoint == 1) {
				$aTempAccess[] = $oChild->mAccess;
			} else {
				if(!empty($aTempAccess)) {
					$oChild->mAccess = $aTempAccess;
					$aTempAccess = array();
				}
			}
		}
		
		$aChilds = array_reverse($aChilds);

		$oTop->setChilds($aChilds);
		
		$this->_aTopNavigation[] = $oTop;

	}
	
	/**
	 * Get an Array with all Top Navigation Objects
	 * @return Ext_TC_System_Navigation_TopItem[] 
	 */
	public function getTopNavigationObjects(){
		return $this->_aTopNavigation;
	}
	
	/**
	 * Get an Array with all Navigation informations
	 * @return array 
	 */
	public function getTopNavigation(){
		
		$aBack = array();
		foreach((array)$this->_aTopNavigation as $oTop){
			$aBack[$oTop->iPosition] = $oTop->getArray();
		}

		return $aBack;
	}
	
	/**
	 * Get all Child Navigations of the $sTopNavigation Section or of all sections
	 * @param string $sTopNavigation
	 * @return array 
	 */
	public function getLeftNavigation($sTopNavigation = ""){
		
		$aBack = array();
		
		foreach((array)$this->_aTopNavigation as $oTop){
			$aBack[$oTop->sName] = $oTop->getChildArray();
		}
		
		if($sTopNavigation != "") {
			if(!empty($aBack[$sTopNavigation])) {
				$aBack = $aBack[$sTopNavigation];	
			} else {
				$aBack = [];
			}
		}

		return $aBack;
		
	}
	
	/**
	 * increments the position of this element
	 */
	protected function _incrementElementPosition() {
		$this->_iCount = $this->_iCount + 1;
	}

	/**
	 * @param string $sRoute
	 * @return string
	 */
	protected function generateUrl($sName, array $aParameters=[]) {

		try {
			$sUrl = Core\Helper\Routing::generateUrl($sName, $aParameters);
			
			// Schema und Host entfernen
			$aUrlParts = parse_url($sUrl);
					
			return $aUrlParts['path'];
		} catch(\Symfony\Component\Routing\Exception\RouteNotFoundException $e) {
			// Wenn der Cache abläuft und es keine Routen gibt, funktioniert überhaupt nichts mehr
			// Theoretisch müssten hier die Routen aktualisiert werden
			return '#error';
		}

	}
	
}