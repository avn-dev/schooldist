<?php

class SmartyWrapper extends \Smarty\Smarty {

	/**
	 * @var string
	 */
	private $sTranslationPath = null;
	
	public function __construct() {
		global $_VARS, $system_data, $user_data;
		
		parent::__construct();
		
		$oAccess = Access::getInstance();
		
		$sSecureDir = \Util::getDocumentRoot() . 'storage/smarty3/';

		$this->setTemplateDir($sSecureDir . 'templates/');
        $this->setCompileDir($sSecureDir . 'templates_c/');
        $this->setConfigDir($sSecureDir . 'configs/');
        $this->setCacheDir($sSecureDir . 'cache/');

		$this->addTemplateDir(\Util::getDocumentRoot().'storage/');
		$this->addTemplateDir(\Util::getDocumentRoot());
		
		if(
			\System::d('debugmode') == 0 || 
			(
				(
					\System::d('debugmode') == 2 ||
					\System::d('debugmode') == 4
				) && 
				!$oAccess instanceof Access_Backend
			)
		) {
			$this->error_reporting = error_reporting();
		} else {
			$this->error_reporting = null;
		}

		// Abwärtskompatibilität
		if(method_exists($this, 'muteUndefinedOrNullWarnings')) {
			$this->muteUndefinedOrNullWarnings();
		}
		
		$this->assign('_VARS', $_VARS);
		$this->assign('_SERVER', $_SERVER);
		$this->assign('system_data', $system_data);
		
		if($oAccess instanceof Access) {
			$this->assign('user_data', $oAccess->getUserData());
		}

		$this->registerPlugin("modifier", 'number_format', [$this, 'number_format']);
		$this->registerPlugin("modifier", 'array_key_exists', 'array_key_exists');
		$this->registerPlugin("modifier", 'is_file', 'is_file');
		$this->registerPlugin("modifier", 'truncate_font', array('Util', 'truncateText'));
		$this->registerPlugin("modifier", 'L10N', [$this, 'translate']);
		$this->registerPlugin("modifier", 'sortby', 'SmartyWrapper::sortBy');
		$this->registerPlugin("modifier", 'join', [$this, 'join']);
		$this->registerPlugin("modifier", 'json_encode', 'json_encode');
		$this->registerPlugin("modifier", 'json_decode', [$this, 'json_decode']);
		$this->registerPlugin("modifier", 'in_array', 'in_array');
		$this->registerPlugin("modifier", 'strpos', 'strpos');
		$this->registerPlugin("modifier", 'var_dump', 'var_dump');
		$this->registerPlugin("modifier", 'dd', 'dd');
		$this->registerPlugin("modifier", 'array_keys', 'array_keys');
		$this->registerPlugin("modifier", 'array_column', 'array_column');
		$this->registerPlugin("modifier", 'array_unique', 'array_unique');
		$this->registerPlugin("modifier", 'array_combine', 'array_combine');
		$this->registerPlugin("modifier", 'date', 'date');
		$this->registerPlugin("modifier", 'trim', 'trim');
		$this->registerPlugin("modifier", 'explode', [$this, 'explode']);
		$this->registerPlugin("modifier", 'intl_date_format', [$this, 'intlDateFormatter']);
		$this->registerPlugin("modifier", 'sprintf', 'sprintf');
		$this->registerPlugin("modifier", 'substr', 'substr');
		$this->registerPlugin("modifier", 'print_r', [$this, 'print_r']);
		$this->registerPlugin('modifier', 'contains', [$this, 'strContains']);
		$this->registerPlugin('modifier', 'is_scalar', 'is_scalar');
		$this->registerPlugin('modifier', 'key', [$this, 'modifier_key']);
		$this->registerPlugin('modifier', 'ceil', 'ceil');
		$this->registerPlugin("modifier", "reset", "reset");
		$this->registerPlugin("modifier", "ucfirst", "ucfirst");
		$this->registerPlugin("modifier", "strtolower", "strtolower");

		$this->registerResource("intern", new Smarty_Resource_Intern());

		$this->registerPlugin("function", 'route', ['\SmartyWrapper', 'createRoute']);
		
		$this->registerClass('Util', 'Util');
		$this->registerClass('System', 'System');
		
	}

	public function intlDateFormatter($date, $language, $timezone) {
		
		$fmt = new IntlDateFormatter(
			$language,
			IntlDateFormatter::FULL,
			IntlDateFormatter::NONE,
			$timezone,
			IntlDateFormatter::GREGORIAN
		);
		
		return $fmt->format(new \DateTime($date));		
	}
	
	public function number_format($num, $decimals, $decimal_separator='.', $thousands_separator=',') {
		return number_format(floatval($num), intval($decimals), $decimal_separator, $thousands_separator);
	}
	
	public function join($array, $glue) {
		return implode((string)$glue, (array)$array);
	}

	public function explode($array, $glue) {
		return explode((string)$glue, (string)$array);
	}

	public function json_decode($array) {
		return json_decode($array, true);
	}

	function print_r(mixed $data): string
	{
		return print_r($data, true);
	}

	public function strContains($string, $substring)
	{
		return str_contains($string, $substring);
	}

	public function modifier_key(mixed $array): mixed
	{
		return key($array);
	}

	/**
	 * Methode wird abgeleitet um einen Alias für Bundles zu ermöglichen
	 * 
	 * @see parent
	 */
	public function createTemplate($template, $cache_id = null, $compile_id = null, $parent = null, $do_clone = true): \Smarty\Template
	{
		
		if(is_string($template) && substr($template, 0, 1) === '@') {
			// @ ist ein Alias für ein Bundle
			$sBundle = substr($template, 1, strpos($template, '/') - 1);
			// ersetzen durch "bundles/{bundle}/Resources/views"
			$template = str_replace('@'.$sBundle, (new \Core\Helper\Bundle())->getBundleResourcesDirectory($sBundle).'/views', $template);
		}
		
		return parent::createTemplate($template, $cache_id, $compile_id, $parent, $do_clone);
	}
	
	/**
	 * Übersetzungspfad setzen für alle L10N-Aufrufe ohne 2. Parameter
	 *
	 * @param string $sPath
	 */
	public function setTranslationPath($sPath) {
		$this->sTranslationPath = $sPath;
	}

	static public function createRoute($aParameters) {

		$sRouteName = $aParameters['name'];

		unset($aParameters['name']);

		return Core\Helper\Routing::generateUrl($sRouteName, $aParameters);

	}

	function setTemplate($strTemplate, $intChanged) {

		$this->registered_resources['intern']->sTemplateCode = $strTemplate;
		$this->registered_resources['intern']->iTemplateChanged = $intChanged;
		
	}

	function displayExtension($arrElementData, $bDisplay=true, $sCacheId=null) {
		global $session_data;

		$this->registered_resources['intern']->sTemplateCode = $arrElementData['content'];
		$this->registered_resources['intern']->iTemplateChanged = $arrElementData['changed'];

		$aTemplateKeys = [$arrElementData['content_id'], $session_data['mode']];

		if (isset($arrElementData['language'])) {
			$aTemplateKeys[] = $arrElementData['language'];
		}

		$sTemplate = "intern:template_".implode('_', $aTemplateKeys).".tpl";

		if($bDisplay === true) {
			$mReturn = $this->display($sTemplate, $sCacheId);
		} else {
			$mReturn = $this->fetch($sTemplate, $sCacheId);
		}

		return $mReturn;
	}

	public static function sortBy($data, $sortby=null) { 
		
		if(is_array($sortby)) { 
			$sortby = join(',', $sortby); 
		} 
		
		$data = (array)$data;
		
		uasort($data, 
			 function( $a, $b) use($sortby) {
				if(empty($sortby)) {
					if( ($c = strcasecmp($a, $b)) != 0 ){ 
						return($c); 
					}
				} else {
					$skeys = explode(',', $sortby); 
					foreach($skeys as $key){
						if( ($c = strcasecmp($a[$key],$b[$key])) != 0 ){ 
							return($c); 
						}
					}
				}
				return($c);
			}
		); 

		return $data;
	}

	/**
	 * @param string $sTranslation
	 * @param string $sPath
	 * @return string
	 */
	public function translate($sTranslation, $sPath = null) {

		if($sPath === null) {
			$sPath = $this->sTranslationPath;
		}

		// TODO Entfernen, weil jede Übersetzung einen Pfad haben muss
		if($sPath === null) {
			$sPath = false;
		}

		return L10N::t($sTranslation, $sPath);

	}

}

class Smarty_Resource_Intern extends \Smarty\Resource\BasePlugin {

	public $sTemplateCode;
	public $iTemplateChanged;

	public function getContent(\Smarty\Template\Source $source): string
	{
		$content = $this->sTemplateCode;
		if ($content === null) {
			throw new \Smarty\Exception("Could not load template content: " . $source->name);
		}
		return $content;
	}

	public function populate(\Smarty\Template\Source $source, \Smarty\Template|null $_template = null): void
	{
		$sourceCode = $this->sTemplateCode;
		$source->content = $sourceCode;
		$source->timestamp = $this->iTemplateChanged;

	}

}
