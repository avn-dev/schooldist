<?php

namespace Codemirror\Helper;

use Codemirror\Helper\Resource\HtmlFile;
use Codemirror\Helper\Resource\HTMLFile\CssFile;
use Codemirror\Helper\Resource\HTMLFile\JavaScriptFile;
use Codemirror\Exception\CodeMirrorException;

class Resource {

	public function __construct() {
		// Add the main javascript and css file for the CodeMirror editor
		$this->addResource("js_libary");
		$this->addResource("css_libary");
	}

	/* all possible resources */
	private $_aPossibleResources = array(
		"js_libary"							=> "/codemirror/resource/lib_codemirror.js",
		"css_libary"						=> "/codemirror/resource/lib_codemirror.css",

		"js_mode_xml"						=> "/codemirror/resource/mode_xml_xml.js",
		"js_mode_htmlmixed"					=> "/codemirror/resource/mode_htmlmixed_htmlmixed.js",
		"js_mode_javascript"				=> "/codemirror/resource/mode_javascript_javascript.js",
		"js_keymap_emacs"					=> "/codemirror/resource/keymap_emacs.js",
		"js_keymap_extra"					=> "/codemirror/resource/keymap_extra.js",
		"js_keymap_vim"						=> "/codemirror/resource/keymap_vim.js",
		"js_addon_display_fullscreen"		=> "/codemirror/resource/addon_display_fullscreen.js",
		"js_addon_hint_show"				=> "/codemirror/resource/addon_hint_show-hint.js",
		"js_addon_hint_javascript"			=> "/codemirror/resource/addon_hint_javascript-hint.js",
		"js_addon_hint_html"				=> "/codemirror/resource/addon_hint_html-hint.js",

		"css_addon_display_fullscreen"		=> "/codemirror/resource/addon_display_fullscreen.css",
		"css_addon_hint_show"				=> "/codemirror/resource/addon_hint_show-hint.css",
		"css_theme_3024-day"				=> "/codemirror/resource/theme_3024-day.css",
		"css_theme_3024-night"				=> "/codemirror/resource/theme_3024-night.css",
		"css_theme_ambiance"				=> "/codemirror/resource/theme_ambiance.css",
		"css_theme_ambiance-mobile"			=> "/codemirror/resource/theme_ambiance-mobile.css",
		"css_theme_base16-dark"				=> "/codemirror/resource/theme_base16-dark.css",
		"css_theme_base16-light"			=> "/codemirror/resource/theme_base16-light.css",
		"css_theme_blackboard"				=> "/codemirror/resource/theme_blackboard.css",
		"css_theme_cobalt"					=> "/codemirror/resource/theme_cobalt.css",
		"css_theme_eclipse"					=> "/codemirror/resource/theme_eclipse.css",
		"css_theme_elegant"					=> "/codemirror/resource/theme_elegant.css",
		"css_theme_erlang-dark"				=> "/codemirror/resource/theme_erlang-dark.css",
		"css_theme_lesser-dark"				=> "/codemirror/resource/theme_lesser-dark.css",
		"css_theme_mbo"						=> "/codemirror/resource/theme_mbo.css",
		"css_theme_mdn-like"				=> "/codemirror/resource/theme_mdn-like.css",
		"css_theme_midnight"				=> "/codemirror/resource/theme_midnight.css",
		"css_theme_monokai"					=> "/codemirror/resource/theme_monokai.css",
		"css_theme_neat"					=> "/codemirror/resource/theme_neat.css",
		"css_theme_night"					=> "/codemirror/resource/theme_night.css",
		"css_theme_paraiso-dark"			=> "/codemirror/resource/theme_paraiso-dark.css",
		"css_theme_paraiso-light"			=> "/codemirror/resource/theme_paraiso-light.css",
		"css_theme_pastel-on-dark"			=> "/codemirror/resource/theme_pastel-on-dark.css",
		"css_theme_rubyblue"				=> "/codemirror/resource/theme_rubyblue.css",
		"css_theme_solarized"				=> "/codemirror/resource/theme_solarized.css",
		"css_theme_the-matrix"				=> "/codemirror/resource/theme_the-matrix.css",
		"css_theme_tomorrow-night-eighties"	=> "/codemirror/resource/theme_tomorrow-night-eighties.css",
		"css_theme_twilight"				=> "/codemirror/resource/theme_twilight.css",
		"css_theme_vibrant-ink"				=> "/codemirror/resource/theme_vibrant-ink.css",
		"css_theme_xq-dark"					=> "/codemirror/resource/theme_xq-dark.css",
		"css_theme_xq-light"				=> "/codemirror/resource/theme_xq-light.css"
	);

	/**
	 * All files which must be included.
	 * @var HtmlFile[]
	 */
	private $_aResources= array();

	/**
	 * <p>
	 * Returns all resources as a string, that you can print into a html page.
	 * A resources must be added before, otherwise it will not be considered.
	 * </p>
	 * @return string <p>
	 * All resources as a string.
	 * <p>
	 * @throws CodeMirrorException
	 */
	public function getResources(){
		// the resources string
		$sResources = '';
		// put all resources together
		foreach ($this->_aResources as $oResourcesFile) {
			/* @var $oResourcesFile HtmlFile */
			if (!($oResourcesFile instanceof HtmlFile)){
				throw new CodeMirrorException("Not an instance of HtmlFile!", 100);
			}
			// extend string
			$sResources .= $oResourcesFile->encode();
		}
		unset($this->_aResources);
		return $sResources;
	}

	/**
	 * <p>
	 * Add a resource.</p><p>
	 * In the following lists you can find all resources which can be added.
	 * <br />
	 * Javascript resources:
	 * <ul>
	 * <li>js_mode_htmlmixed</li>
	 * <li>js_mode_javascript</li>
	 * <li>js_keymap_emacs</li>
	 * <li>js_keymap_extra</li>
	 * <li>js_keymap_vim</li>
	 * <li>js_addon_display_fullscreen</li>
	 * </ul>
	 * Css resources:
	 * <ul>
	 * <li>css_addon_display_fullscreen</li>
	 * <li>css_theme_3024-day</li>
	 * <li>css_theme_3024-night</li>
	 * <li>css_theme_ambiance</li>
	 * <li>css_theme_ambiance-mobile</li>
	 * <li>css_theme_base16-dark</li>
	 * <li>css_theme_base16-light</li>
	 * <li>css_theme_blackboard</li>
	 * <li>css_theme_cobalt</li>
	 * <li>css_theme_eclipse</li>
	 * <li>css_theme_elegant</li>
	 * <li>css_theme_erlang-dark</li>
	 * <li>css_theme_lesser-dark</li>
	 * <li>css_theme_mbo</li>
	 * <li>css_theme_mdn-like</li>
	 * <li>css_theme_midnight</li>
	 * <li>css_theme_monokai</li>
	 * <li>css_theme_neat</li>
	 * <li>css_theme_night</li>
	 * <li>css_theme_paraiso-dark</li>
	 * <li>css_theme_paraiso-light</li>
	 * <li>css_theme_pastel-on-dark</li>
	 * <li>css_theme_rubyblue</li>
	 * <li>css_theme_solarized</li>
	 * <li>css_theme_the-matrix</li>
	 * <li>css_theme_tomorrow-night-eighties</li>
	 * <li>css_theme_twilight</li>
	 * <li>css_theme_vibrant-ink</li>
	 * <li>css_theme_xq-dark</li>
	 * <li>css_theme_xq-light</li>
	 * </ul>
	 * </p>
	 * @param string $sResource <p>
	 * The resource you want to add. Look at the list obove.
	 * </p>
	 * @throws CodeMirrorException
	 */
	public function addResource($sResource){
		// If resource is not added before
		if(!(array_key_exists($sResource, $this->_aResources))){
			// Check if this resource exists anyway, if not, throw an exception
			if (!(array_key_exists($sResource, $this->_aPossibleResources))){
				throw new CodeMirrorException("Resources not found!", 110);
			}
			// Add a resource
			$this->_addResource($sResource);
		}
	}

	/**
	 * <p>
	 * Adds a HtmlFile to the resources. If the resource has a prefix,
	 * which does not exists an exception will be thrown.
	 * Prefixes can be: "css_" or "js_"
	 * </p>
	 * @param string $sResource
	 * @throws CodeMirrorException
	 */
	private function _addResource($sResource){
		// The matches for the prefix
		$aMatches = array();
		preg_match('/[^_]*/', $sResource, $aMatches);

		// Look for the prefix and instanziate a new HtmlFile object
		switch ($aMatches[0]) {
			case "css":
				$oHtmlFile = new CssFile($this->_aPossibleResources[$sResource]);
				break;
			case "js":
				$oHtmlFile = new JavaScriptFile($this->_aPossibleResources[$sResource]);
				break;
			default:
				throw new CodeMirrorException("Wrong prefix!", 111);
				break;
		}
		// Add file to resources.
		$this->_aResources[] = $oHtmlFile;
	}
}