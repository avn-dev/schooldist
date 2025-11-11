<?php

namespace Cms\Entity;

class Page extends \WDBasic {
	
	const MODE_EDIT = 3;
	const MODE_PREVIEW = 2;
	const MODE_LIVE = 1;
	
	private $iMode = 1;
	
	private $bUpToDate = true;
	
	private $bInpageEditableElements = false;
	
	protected $_sTable = 'cms_pages';

	protected $_aJoinedObjects = [
		'site' => [
			'class' => '\Cms\Entity\Site',
			'key' => 'site_id',
			'type' => 'parent'
		]
	];

	public function setInpageEditableElements(bool $bExists) {
		$this->bInpageEditableElements = $bExists;
	}

	public function hasInpageEditableElements() {
		return $this->bInpageEditableElements;
	}
	
	public function setUpToDate($bUpToDate) {
		$this->bUpToDate = $bUpToDate;
	}
	
	public function isUpToDate() {
		return $this->bUpToDate;
	}
	
	public function setMode($iMode) {
		$this->iMode = $iMode;
	}
	
	public function getMode() {
		return $this->iMode;
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	public static function getNotPublishedPages($iLimit=10) {
		
		$sSql = "
				SELECT 
					sp.*
				FROM 
					cms_pages sp LEFT OUTER JOIN
					cms_content sc ON
						sp.id = sc.page_id LEFT OUTER JOIN
					cms_blockdata sb ON
						sc.id = sb.content_id OR
						sp.id = sb.page_id
				WHERE  
					(
						sc.uptodate = 0 OR
						sb.uptodate = 0
					) AND 
					sp.element = 'page' AND 
					sp.active = 1 
				GROUP BY 
					sc.page_id 
				ORDER BY 
					sc.changed DESC,
					sb.changed DESC 
				LIMIT ".(int)$iLimit;
		$aPages = \DB::getQueryData($sSql);
		
		return $aPages;
	}
	
	public function setLevel() {

		$oSite = Site::getInstance($this->site_id);
		
		if($this->path == '' && $this->file == '') {
			$this->level = 0;
		} elseif(
			$this->path == '' && 
			$this->file != ''
		) {
			if($oSite->no_language_folder == 1) {
				$this->level = 0;
			} else {
				$this->level = 1;	
			}
		} else {
			$iCount = substr_count($this->path, '/');
			if($oSite->no_language_folder == 1) {
				$this->level = ($iCount);	
			} else {
				$this->level = ($iCount+1);	
			}		
		}

	}

	public function isValid() {
		
		// Temporär veröffentlichen (Klappt nicht einwandfrei weil 2 eigentlich gelöscht ist)
		if($this->active == 2) {
			
			$dFrom = new \DateTime($this->validfrom);
			$dTo = new \DateTime($this->validto);
			
			$oNow = new \Core\Helper\DateTime;
			$bActive = $oNow->isBetween($dFrom, $dTo);
			
			return $bActive;
			
		} elseif($this->active == 0) {
			return false;
		} else {
			return true;
		}
		
	}
	
	public function copy($strLanguage, $sTargetPath, $sTargetFile, $sToTitle=false, $iToSiteId=false) {
		global $user_data;

		$idPage = $this->id;
		
		$file_data["name"] 		= $sTargetFile;
		$file_data["path"]	 	= $sTargetPath;
		$arr = explode("/", $file_data["path"]);
		if(
			empty($file_data["name"]) &&
			empty($file_data["path"])
		) {
			$i=0;
		} else {
			$i=1;
			foreach($arr as $elem) {
				if($elem) {
					$file_data["dir"][$i] = $elem;
					$i++;
				}
			}
		}
		$file_data["level"] = $i;

		$aFromPage = \DB::getQueryRow("SELECT * FROM cms_pages WHERE id = ".(int)$this->id."");

		$aToPage = $aFromPage;
		unset($aToPage['id']);

		if($sToTitle) {
			$aToPage['title'] = $sToTitle;
		}
		if($iToSiteId) {
			$aToPage['site_id'] = $iToSiteId;
		}
		$aToPage['path'] = $file_data["path"];
		$aToPage['file'] = $file_data["name"];
		$aToPage['level'] = $file_data["level"];
		$aToPage['language'] = $strLanguage;
		$aToPage['author'] = (int)$user_data['id'];
		$aToPage['created'] = date("YmdHis");

		$aToPage['id'] = \DB::insertData('cms_pages', $aToPage);
		
		$oNewPage = self::getInstance($aToPage['id']);
		
		$this->copyContents($oNewPage);

		return $oNewPage;
	}

	public function copyContents($oNewPage, $iOldParentId=null, $iNewParentId=null) {
		global $user_data;

		$aSql = [
			'page_id' => (int)$this->id
		];
		$sSql = "
			SELECT 
				* 
			FROM 
				cms_content 
			WHERE 
				page_id = :page_id AND 
		";
		
		if($iOldParentId === null) {
			$sSql .= " (`parent_id` IS NULL OR `parent_id` = 0) ";
		} else {
			$sSql .= " `parent_id` = :parent_id";
			$aSql['parent_id'] = (int)$iOldParentId;
		}

		$sSql .= " AND
				active = 1 
			ORDER BY 
				`id`";

		$aContents = \DB::getQueryRows($sSql, $aSql);
		
		if(!empty($aContents)) {

			foreach($aContents as $aContent) {

				$aNewContent = $aContent;
				unset($aNewContent['id']);
				$aNewContent['page_id'] = (int)$oNewPage->id;
				$aNewContent['author'] = (int)$user_data['id'];

				if($iNewParentId !== null) {
					$aNewContent['parent_id'] = (int)$iNewParentId;
				} else {
					$aNewContent['parent_id'] = null;
				}

				$iNewContentId = \DB::insertData('cms_content', $aNewContent);

				$rConfig = \DB::getQueryRows("SELECT * FROM cms_extensions_config WHERE page_id = '".$this->id."' AND element_id = '".$aContent['number']."' AND (content_id = 0 || content_id = '".$aContent['id']."')");
				foreach($rConfig as $aConfig) {
					\DB::executeQuery("INSERT INTO cms_extensions_config SET page_id = '".$oNewPage->id."', element_id = '".$aNewContent['number']."', level_id = '".$aConfig['level_id']."', content_id = '".(($aConfig['content_id']=="0")?"0":$iNewContentId)."', param = '".\DB::escapeQueryString($aConfig['param'])."'");
				}

				$rBlockdata = \DB::getQueryRows("SELECT * FROM cms_blockdata WHERE (page_id IS NULL OR page_id = ".(int)$this->id.") AND content_id = ".(int)$aContent['id']."");
				foreach($rBlockdata as $aBlockdata) {
					\DB::executeQuery("INSERT INTO cms_blockdata SET page_id = ".(int)$oNewPage->id.", content_id = '".(int)$iNewContentId."', data_id = '".(int)$aBlockdata['data_id']."', item = '".$aBlockdata['item']."', content = '".\DB::escapeQueryString($aBlockdata['content'])."', public = '".\DB::escapeQueryString($aBlockdata['public'])."', uptodate = '".$aBlockdata['uptodate']."'");
				}

				$this->copyContents($oNewPage, $aContent['id'], $iNewContentId);

			}
		
		}
		
	}
	
	public static function getLayoutTypes() {

		$aTypes = array(
			"block"=>\L10N::t("Blocklayout"),
			"free"=>\L10N::t("freies Layout")
		);

		return $aTypes;
	}

	public function getLink($sLanguage=null, $bWithHost=false) {
		
		$oSite = \Cms\Entity\Site::getInstance($this->site_id);
		
		$bWithLanguage = true;
		
		// Wenn der Internetauftritt Sprachordner deaktiviert hat
		if($oSite->no_language_folder == 1) {
			$bWithLanguage = false;
		}

		if(empty($sLanguage)) {

			if(
				$this->language === '' ||
				$this->language === null
			) {

				// Wenn im Frontend, dann Interfacesprache nehmen
				if(\System::getInterface() === 'frontend') {
					$sLanguage = \System::getInterfaceLanguage();
				}
				
				// Wenn immernoch leer, dann erste Sprache des Internetauftrittes
				if(empty($sLanguage)) {
					$aSiteLanguages = $oSite->getLanguages(1);
					$sLanguage = reset($aSiteLanguages);
				}

			} else {
				$sLanguage = $this->language;
			}

		}
		
		$sLink = '';
		
		if($bWithHost === true) {
			if($oSite->force_https) {
				$sLink .= 'https://';
			} else {
				$sLink .= 'http://';
			}
			$sLink .= $oSite->getMainDomain();
		}
		
		if(
			$this->path === '' &&
			$this->file === ''
		) {
			$sLink .= "/";
		} else {
			$sLink .= "/";
			if($bWithLanguage) {
				$sLink .= $sLanguage."/";
			}
			$sLink .= $this->path."";
			
			if($this->file != 'index') {
				$sLink .= $this->file;
			}

			if(!empty($oSite->url_ending)) {
				$sLink .= '.'.$oSite->url_ending;
			}
			
		}

		return $sLink;
	}


	public function getIndexPage($sLanguageIso) {
		
		$sSql = "
			SELECT 
				*
			FROM 
				`cms_pages` 
			WHERE 
				`element` != 'template' AND
				`site_id` = :site_id AND
				`path` = :path AND 
				`indexpage` = 1 AND 
				(
					`language` = :language OR 
					`language` = ''
				)
			LIMIT 1";
		$aSql = [
			'site_id' => (int)$this->site_id,
			'path' => $this->path,
			'language' => $sLanguageIso
		];
		$aIndexPage = \DB::getQueryRow($sSql, $aSql);

		if(empty($aIndexPage)) {
			return;
		}

		$oIndexPage = self::getObjectFromArray($aIndexPage);
		
		return $oIndexPage;
	}
	
	public function save() {

		return parent::save();
	}

	public function getTrack($seperator=" &raquo; ", $iStartlevel=1, $bShowSite=false) {

		$objWebDynamicsDAO = new \Cms\Helper\Data;

		$file_data = explode("/", $this->path);
		$output = "";
		$h=0;
		$link = "";
		$bEndSeparator = false;

		if($this->id < 1) {
			return false;
		}

		if($bShowSite) {
			$aSite = $objWebDynamicsDAO->getSiteData($this->site_id);
			$output .= $aSite['name']." &raquo; ";
		}

		while(count($file_data) > $h) {

			if($h >= $iStartlevel) {

				$my_dir = self::getFromPath($link, $this->site_id, $this->language);
				$output .= $my_dir['title'];

				if(count($file_data)-1 > $h) {
					$output .= $seperator;
				} else {
					$bEndSeparator = true;
				}

			}

			$link .= $file_data[$h]."/";
			$h++;

		}

		// Wenn nicht Verzeichnis, Name der Datei ranhängen
		if($this->file != "index" || $output == "") {
			if($bEndSeparator) {
				$output .= $seperator;
			}
			$output .= $this->title;
		}

		return stripslashes($output);
	}

	public static function getFromPath($sPath, $iSite, $sLanguage) {
		global $session_data;

		if(!isset($session_data['page_from_path'][$sPath][$iSite][$sLanguage])) {

			$sSql = "SELECT title FROM cms_pages WHERE path = '".$sPath."' AND file = 'index' AND site_id = ".(int)$iSite." AND (language = '".$sLanguage."' OR language = '') ORDER BY language DESC";
			$my_dir = \DB::getQueryRow($sSql);

			$session_data['page_from_path'][$sPath][$iSite][$sLanguage] = $my_dir;

		}

		return $session_data['page_from_path'][$sPath][$iSite][$sLanguage];
	}

	public function setParentPage(Page $oParentPage) {

		if(
			$this->file === 'index' || 
			$this->file === ''
		) {

			if(empty($this->path)) {
				return;
			}

			$aCurrentPath = explode('/', trim($this->path, '/'));
			$sItemPath = end($aCurrentPath);

			$this->path = $oParentPage->path.$sItemPath.'/';

		} else {
			$this->path = $oParentPage->path;
		}
		
	}

	public function setIndex() {

		$sSql = "
			UPDATE 
				cms_pages 
			SET 
				indexpage = 0
			WHERE 
				site_id = :site_id AND 
				path = :path_name AND 
				(
					language = :language OR 
					language = ''
				)";
		$aSql = [
			'site_id' => $this->site_id,
			'path_name' => $this->path,
			'language' => $this->language
		];
		\DB::executePreparedQuery($sSql, $aSql);

		$this->indexpage = 1;

	}
	
	public function __toString() {
		return $this->title;
	}
	
	public function clearCache($sLanguage) {
		
		$oRouting = \Factory::getObject('\Cms\Service\Routing');
		$sCacheKey = $oRouting->getCacheKey($this, $sLanguage, []);

		\WDCache::delete($sCacheKey);

	}
	
}
