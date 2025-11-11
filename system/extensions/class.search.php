<?php

class Ext_Search {

	const MAX_SAME_PAGE_COUNTER		= 1000;

	/**
	 * Debug array
	 * 
	 * @var array
	 */
	public static $aDebug			= array();

	/**
	 * Last page counter
	 * 
	 * @var array
	 */
	protected $_aLastPage			= array();

	/**
	 * Search table
	 * 
	 * @var array
	 */
	protected $_aSearchTable		= array();

	/**
	 * The available sites
	 * 
	 * @var array
	 */
	protected $_aSites				= array();

	/**
	 * Available system languages
	 * 
	 * @var array
	 */
	protected $_aSystemLanguages	= array();

	/**
	 * Visited pages
	 * 
	 * @var array
	 */
	protected $_aVisitedPages		= array();

	/**
	 * WebDynamicsDAO object
	 * 
	 * @var webdynamicsDAO object
	 */
	protected $_oWebDynamicsDAO		= null;

	/**
	 * @var Site
	 */
	protected $oSite;
	
	/* ==================================================================================================== */

	/**
	 * The constructor
	 */
	public function __construct(\Cms\Entity\Site $oSite) {

		$iStart = microtime(true);

		ignore_user_abort(true);
		set_time_limit(7200);
		ini_set('max_execution_time', 7200);
		ini_set('memory_limit', '2G');

		$this->_oWebDynamicsDAO		= new Cms\Helper\Data();;
		$this->_aSites				= (array)$this->_oWebDynamicsDAO->getSites();
		$this->_aSystemLanguages	= (array)$this->_oWebDynamicsDAO->getWebSiteLanguages(true);

		$this->oSite = $oSite;
		
		self::$aDebug = [];
		self::$aDebug['__construct'] += microtime(true) - $iStart;

	}

	/* ==================================================================================================== */

	/**
	 * Create new search index for given site
	 * 
	 * @param mixed $mSite
	 * @return Ext_Search
	 */
	public function index() {

		$iStart = microtime(true);

		$aPages = $this->_getPages();

		$sUrlEnding = $this->oSite->url_ending;
		
		foreach($aPages as $aPage) {

			if(
				$aPage['language'] == '' ||
				$this->oSite->no_language_folder == 1
			) {
				if($aPage['path'] != '') {
					$sPath = '/' . $aPage['path'] . $aPage['file'];
				} else {
					$sPath = '/' . $aPage['file'];
				}
			} else {
				if($aPage['path'] != '') {
					$sPath = '/' . $aPage['language'] . '/' . $aPage['path'] . $aPage['file'];
				} else {
					$sPath = '/' . $aPage['language'] . '/' . $aPage['file'];
				}
			}

			if(!empty($sUrlEnding)) {
				$sPath .= '.'.$sUrlEnding;
			}
			
			$this->_aVisitedPages[$sPath] = $sPath;

			$sPath = str_replace('//.html', '', $sPath);

			$this->processIndexing($sPath, $aPage['id']);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Clear the DB

		$sSql = "
			DELETE FROM 
				`search_index` 
			WHERE 
				`site_id` = :site_id
		";
		$aSql = [
			'site_id' => (int)$this->oSite->id
		];
		DB::executePreparedQuery($sSql, $aSql);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		self::$aDebug['index'] += microtime(true) - $iStart;

		return $this->save();
	}

	/**
	 * Process indexing
	 * 
	 * @param string $sPath
	 * @param mixed $mSite
	 * @return Ext_Search
	 */
	public function processIndexing($sPath, $iPageId=null) {

		$iStart = microtime(true);

		$sPath = $this->_clearPath($sPath);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$partStart	= @strpos($sPath, '/') + 1;
		$partEnd	= @strpos($sPath, '/', $partStart);
		$sLanguage	= @substr($sPath, $partStart, $partEnd - $partStart);

		// Check if there is a language at this site
		$bCheck = false;

		if(in_array($sLanguage, $this->_aSystemLanguages)) {
			$bCheck = true;
		}

		$partStart	= $partEnd + 1;
		$partEnd	= @strrpos($sPath, '/', $partStart) + 1;

		if($partEnd == 1) {
			$partEnd = $partStart;
		}

		$sSearchPath = @substr($sPath, $partStart, $partEnd - $partStart);

		if($bCheck !== true) {
			$sSearchPath = $sLanguage . '/' . $sSearchPath;

			$sLanguage = '';
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$partStart	= $partEnd;
		$partEnd	= @strpos($sPath, '.', $partStart);
		$sFile		= @substr($sPath, $partStart, $partEnd - $partStart);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sExtension = @strtolower(substr($sPath, strrpos($sPath, '.') + 1));

		if(
			$sExtension === 'jpg' ||
			$sExtension === 'png' ||
			$sExtension === 'svg' ||
			$sExtension === 'css' ||
			$sExtension === 'js' ||
			$sExtension === 'ico' ||
			strpos($sPath, '/image.php') === 0
		) {
			return false;
		}

		#echo 'Processing: '.$sPath.'<br>';
		flush();

		self::$aDebug['pages'][] = $sPath;
		
		if($sExtension === 'pdf') {

			$sPdfPath = \Util::getDocumentRoot().$sPath;

			if(is_file($sPdfPath)) {

				$sTxtPath = \Util::getDocumentRoot().$sPath . '.txt';

				exec('pdftotext ' . $sPdfPath . ' ' . $sTxtPath);

				if(is_file($sTxtPath)) {
					$sContent = utf8_encode(file_get_contents($sTxtPath));

					$this->_aSearchTable[] = array(
						'pageId'		=> 9999999,
						'path'			=> $sPath,
						'title'			=> $sFile,
						'language'		=> $sLanguage,
						'description'	=> '',
						'content'		=> $sContent
					);

					unlink($sTxtPath);

					$this->_aVisitedPages[$sPath] = $sPath;
				}

			}
			
		} else {
			
			if($iPageId !== null) {
				
				$sSql = "
					SELECT
						`id`,
						`title`,
						`description`,
						`search`
					FROM
						`cms_pages`
					WHERE
						`id` = :id
				";

				$aSql = array(
					'id'		=> $iPageId
				);
				
			// Get ID and title from DB by language path und file
			} elseif($sFile === 'index') {

				$sSql = "
					SELECT
						`id`,
						`title`,
						`description`,
						`search`
					FROM
						`cms_pages`
					WHERE
						`active`	= 1 AND
						`indexpage`	= 1 AND
						`path`		= :sPath AND
						`language`	IN('', :sLanguage)
				";

				$aSql = array(
					'sPath'		=> $sSearchPath,
					'sLanguage'	=> $sLanguage
				);

			} else {

				$sSql = "
					SELECT
						`id`,
						`title`,
						`description`,
						`search`
					FROM
						`cms_pages`
					WHERE
						`active`	= 1 AND
						`file`		= :sFile AND
						`path`		IN('', :sPath)
				";

				
				$aSql = array(
					'sFile'		=> $sFile,
					'sPath'		=> $sSearchPath
				);
				
				if(!empty($sLanguage)) {
					$sSql .= " AND
						`language`	IN('', :sLanguage)";
					$aSql['sLanguage'] = $sLanguage;
				}

			}

			$sSql .= "
				AND `site_id` = :iSiteId
			";
			$aSql['iSiteId'] = $this->oSite->id;

			$sSql .= "
				LIMIT
					1
			";

			$aPage = (array)DB::getQueryRow($sSql, $aSql);

			if(
				!empty($aPage) &&
				$aPage['search'] == 0
			) {
				return false;
			}
			
			if(empty($aPage)) {
				#return false;
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			$this->_aVisitedPages[$sPath] = $sPath;

			list($aContent, $aLinks, $sTitle, $sDescription) = $this->_getContent($sPath);

			$bIsSame = false;

			if(is_array($aContent) && !empty($aContent)) {
	
				$sImplodedContent = implode(' ', $aContent);

				if(isset($this->_aSearchTable[$sPath])) {
					$bIsSame = true;
				}

				if(!$bIsSame) {
				
					if(empty($sDescription)) {
						$sDescription = $aPage['description'];
					}
				
					$this->_aSearchTable[$sPath] = array(
						'pageId'		=> $aPage['id'],
						'path'			=> $sPath,
						'title'			=> $sTitle,
						'language'		=> $sLanguage,
						'description'	=> $sDescription,
						'content'		=> $sImplodedContent
					);

					if($this->_aLastPage['id'] == $aPage['id']) {
						if(++$this->_aLastPage['counter'] >= self::MAX_SAME_PAGE_COUNTER) {
							$aLinks = array();
						}
					} else {
						$this->_aLastPage = array(
							'id'		=> $aPage['id'],
							'counter'	=> 0
						);
					}
				} else {
					__out('Is same');
					__out($sPath);
				}

				unset($aContent, $sImplodedContent);

			} else {
				__out('No content');
				__out($sPath);
			}

			foreach((array)$aLinks as $sPath) {
				
				$sClearPath = $this->_clearPath($sPath);
				
				if(
					!isset($this->_aVisitedPages[$sPath]) &&
					!isset($this->_aVisitedPages[$sClearPath])
				) {
					$this->processIndexing($sPath);
				}
			}

			unset($aLinks);
		}

		self::$aDebug['processIndexing'] += microtime(true) - $iStart;

		return $this;
	}


	/**
	 * Save the founds into the DB
	 * 
	 * @return Ext_Search
	 */
	public function save() {

		$iStart = microtime(true);

		foreach((array)$this->_aSearchTable as $aValue) {
				
			$aValue['content'] = preg_replace('/[\s]+/', ' ', $aValue['content']);

			$aInsert = array(
				'site_id'		=> (int)$this->oSite->id,
				'pageId'		=> (int)$aValue['pageId'],
				'path'			=> (string)$aValue['path'],
				'title'			=> (string)$aValue['title'],
				'language'		=> (string)$aValue['language'],
				'description'	=> (string)$aValue['description'],
				'lastUpdate'	=> date('Y-m-d H:i:s'),
				'content'		=> (string)$aValue['content']
			);
			
			DB::insertData('search_index', $aInsert);

		}

		self::$aDebug['save'] += microtime(true) - $iStart;

		return $this;
	}

	/**
	 * Clear the path
	 * 
	 * @param string $sPath
	 * @param mixed $mSite
	 * @return string
	 */
	protected function _clearPath($sPath) {

		$sDomain = $this->_getDomain();

		$sPath = str_replace(array('http://', 'https://', $sDomain), '', $sPath);

		if(strpos($sPath, '#') !== false) {
			$sPath = substr($sPath, 0, strpos($sPath, '#'));
		}

		return $sPath;
	}

	/**
	 * Get the content
	 * 
	 * @param string $sPath
	 * @param mixed $mSite
	 * @return array
	 */
	protected function _getContent($sPath) {

		$iStart = microtime(true);

		$sPath = $this->_clearPath($sPath);

		$sDomain = $this->_getDomain();

		if($this->oSite->force_https == 1) {
			$sUrl = 'https://' . $sDomain . $sPath;	
		} else {
			$sUrl = 'http://' . $sDomain . $sPath;
		}

		$rUrl = curl_init();

		curl_setopt($rUrl, CURLOPT_URL, $sUrl);
		curl_setopt($rUrl, CURLOPT_HEADER, 0);
		curl_setopt($rUrl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($rUrl, CURLOPT_USERAGENT, 'framework_warmup');

		// Execute the action and give the data to the browser
		$sFullHTML = curl_exec($rUrl);

		// Close the cURL-Handle
		curl_close($rUrl);

		$aContents = [];
		$aLinks = [];

		$sTagStart = $sBody = $sTitle = '';
		$sDescription = '';

		if($sFullHTML) {

			// Get page title
			$sTitle = $this->_getTagContent($sFullHTML, 'title');

			// Parse page title
			$sTitle = $this->_parsePageTitle($sTitle);

			preg_match_all("/<\!\-\- SEARCH:(.*?):START \-\->/", $sFullHTML, $aRegex);

			foreach($aRegex[0] as $iRegex=>$strItem) {
			
				$strTagStart	= $strItem;
				$intStartPos	= strpos($sFullHTML, $strTagStart);
				$intStartPos	= $intStartPos + strlen($sTagStart);
				$strTagEnd		= str_replace('START', 'END', $strTagStart);
				$intEndPos		= strpos($sFullHTML, $strTagEnd, $intStartPos);
				$sContent		= substr($sFullHTML, $intStartPos, $intEndPos - $intStartPos);

				$sBody .= $sContent;

				$this->_removeTagContent($sContent, 'script');
				$this->_removeTagContent($sContent, 'style');

				$sContent = str_ireplace('<br', ' <br', $sContent);
				$sContent = strip_tags($sContent);
				$sContent = trim($sContent);

				// Beschreibung
				if($aRegex[1][$iRegex] === 'DESCRIPTION') {
					$sDescription = $sContent;
				}
				
				if($sContent !== '') {
					$aContents[] = $sContent;
				}

			}

			$aLinks = $this->_getLink($sFullHTML);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		self::$aDebug['_getContent'] += microtime(true) - $iStart;

		return array($aContents, $aLinks, $sTitle, $sDescription);
	}

	/**
	 * Get the domain
	 * 
	 * @param mixed $mSite
	 * @return string
	 */
	protected function _getDomain() {

		$sSql = "
			SELECT 
				`domain`
			FROM 
				`cms_sites_domains` 
			WHERE 
				`site_id` = :site_id AND 
				`master` = 1 AND 
				`active` = 1
			";
		$aSql = [
			'site_id' => (int)$this->oSite->id
		];
	
		$sDomain = DB::getQueryOne($sSql, $aSql);

		return $sDomain;
	}

	/**
	 * Get the links
	 * 
	 * @param string $sContent
	 */
	protected function _getLink($sContent) {

		$iStart = microtime(true);

		$iUrlStart = $iUrlEnd = 0;

		$aLinks = array();

		do {

			// $urlStart gibt jetzt die Position des ersten Zeichens im href="" an!
			$iUrlStart	= @strpos($sContent, 'href=', $iUrlEnd);
			$sQuote = substr($sContent, $iUrlStart + 5, 1);

			$iUrlEnd	= @strpos($sContent, $sQuote, $iUrlStart + 6);

			if(!$iUrlEnd) {
				$iUrlEnd = @strpos($sContent, '>', $iUrlStart + 6);
			}

			if(!$iUrlEnd) {
				$iUrlEnd = @strpos($sContent, ' ', $iUrlStart + 6);
			}
			
			$sLink = @substr($sContent, $iUrlStart + 6, $iUrlEnd - $iUrlStart - 6);

			if($iUrlStart !== false) {

				// Has the link a parameter?
				if(substr($this->_clearPath($sLink), 0, 1) == '/') {

					if(!in_array($sLink, $aLinks)) {
						$sLink = str_replace(' ', '+', $sLink);

						$aLinks[] = $sLink;
					}
				}
			}

		} while($iUrlStart !== false);

		self::$aDebug['_getLink'] += microtime(true) - $iStart;

		return $aLinks;
	}

	/**
	 * Get selected pages
	 * 
	 * @param mixed $mSite
	 * @return array
	 */
	protected function _getPages() {

		$sSql = "
			SELECT
				`id`,
				`path`,
				`file`,
				`language`,
				`title`,
				`description`
			FROM
				`cms_pages`
			WHERE
				`element`	= 'page'	AND
				`file`		!= 'index'	AND
				`active`	= 1			AND
				`search`	= 1
		";
		$aSql = array(
			'iSiteId' => (int)$this->oSite->id
		);

		$sSql .= "
			AND `site_id` = :iSiteId
		";
		
		$aPages = (array)DB::getPreparedQueryData($sSql, $aSql);

		return $aPages;
	}

	/**
	 * Get the contant of a tag
	 * 
	 * @param string $sContent
	 * @param string $sTag
	 * @return string
	 */
	protected function _getTagContent($sContent, $sTag) {

		$iStart = microtime(true);

		$iPosTagOpen		= strpos($sContent, '<' . $sTag);
		$iPosContentStart	= strpos($sContent, '>', $iPosTagOpen) + 1;
		$iPosContentEnd		= strpos($sContent, '</' . $sTag . '>', $iPosContentStart);
		$iContentLength		= $iPosContentEnd - $iPosContentStart;

		$sContent = substr($sContent, $iPosContentStart, $iContentLength);

		self::$aDebug['_getTagContent'] += microtime(true) - $iStart;

		return $sContent;
	}

	/**
	 * Parse the page title
	 * 
	 * @param string $sTitle
	 * @return string
	 */
	protected function _parsePageTitle($sTitle) {

		$iStart = microtime(true);

		global $system_data, $page_data;

		$sSeperator = '#||#';

		$page_data['title'] = $sSeperator;
		$system_data['project_name'] = $this->oSite->name;

		$system_data['title_template'] = \Cms\Service\ReplaceVars::execute($system_data['title_template']);

		$arrItems = explode($sSeperator, $system_data['title_template']);

		$arrItems[0] = trim($arrItems[0]);
		$arrItems[1] = trim($arrItems[1]);

		if($arrItems[0] != '') {
			$sTitle = str_replace($arrItems[0], '', $sTitle);
		}

		if($arrItems[1] != '') {
			$sTitle = str_replace($arrItems[1], '', $sTitle);
		}

		self::$aDebug['_parsePageTitle'] += microtime(true) - $iStart;

		return trim($sTitle);
	}

	/**
	 * Remove <script> and <style> tags with content
	 * 
	 * @param string $sOriginalContent
	 * @param string $sTag
	 */
	protected function _removeTagContent(&$sOriginalContent, $sTag) {

		$iStart = microtime(true);

		$iStartPos = strpos($sOriginalContent, '<' . $sTag);

		while($iStartPos !== false) {

			$iEndPos = strpos($sOriginalContent, '</' . $sTag . '>', $iStartPos);

			$iEndPos += strlen('</' . $sTag . '>');

			$sOriginalContent =
				substr($sOriginalContent, 0, $iStartPos-1) .
				substr($sOriginalContent, $iEndPos + 1, strlen($sOriginalContent) - $iEndPos);

			$iStartPos = strpos($sOriginalContent, '<' . $sTag);
		}

		self::$aDebug['_removeTagContent'] += microtime(true) - $iStart;
	}

}