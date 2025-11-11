<?php

namespace Codemirror\Service;

use Codemirror\Helper\Resource;
use Codemirror\Exception\CodeMirrorException;

/**
 * @todo This is not the final class. Events and other stuff had be left out.
 * @version v0.1
 * @author Okan Sadik Köse <okan.koese@gmx.de>
 */
class EditorService {

	/**
	 * @var Resource <p>
	 * An object of the class resources, to retrieve the configuration
	 * depended resources.
	 * </p>
	 */
	private $_oResource;

	/**
	 * @var array <p>
	 * The configuration for the codemirror editor.
	 * </p>
	 */
	private $_aConfiguration;

	public function __construct() {
		$this->_oResource = new Resource();
		$this->_aConfiguration = array();
	}

	/**
	 * Retrieve the configuration as a string.
	 * @return string <p>
	 * The configuration as a string.
	 * </p>
	 */
	private function _getConfiguration(){
		// return value
		$sConfiguration = "";
		foreach ($this->_aConfiguration as $sKey => $mValue){
			$sConfiguration .= "\n";
			// Extend the congiguration depended on the type. (Quoting)
			if(
				is_numeric($mValue)	||
				substr($mValue, 0, 1) === "{"
			){
				// Extend without quotes
				$sConfiguration .= "$sKey: $mValue";
			} elseif (is_bool($mValue)){
				// If boolean, write true or false
				if($mValue){
					$sConfiguration .= "$sKey: true";
				} else {
					$sConfiguration .= "$sKey: false";
				}
			} else {
				// Extend with quotes
				$sConfiguration .= "$sKey: \"$mValue\"";
			}
			$sConfiguration .= ",";
		}
		// remove last comma
		$sConfiguration = rtrim($sConfiguration, ",");
		return $sConfiguration;
	}

	/**
	 * Retrieve the JavaScript code to initialize the CodeMirror editor.
	 * @param string $sTextAreaId <p>
	 * This is the id of the textarea where the CodeMirror editor will be
	 * appended. The Element must be a textarea.
	 * </p>
	 */
	public function getJavaScriptCode($sTextAreaId){

		// Get the configuration as a string
		$sConfiguration = $this->_getConfiguration();

		// build the initialize command
		$sToInitialize .= "var myCodeMirror = CodeMirror.fromTextArea(document.getElementById('$sTextAreaId'), {";
		$sToInitialize .= $sConfiguration . "\n";
		$sToInitialize .= "});\n";

		return $sToInitialize;
	}

	/**
	 * <p>
	 * Returns all resources as a string, that you can print into a html page.
	 * Resources will be unset, after you retrieve them as a result, that you
	 * can just require them once. 
	 * </p>
	 * @return string <p>
	 * All resources as a string.
	 * <p>
	 * @throws CodeMirrorException
	 */
	public function getResources(){
		$sResources = $this->_oResource->getResources();
		return $sResources;
	}

	/**
	 * <p>
	 * The mode to use. When not given, this will default to the first mode that
	 * was loaded. It may be a string, which either simply names the mode or is
	 * a MIME type associated with the mode. Alternatively, it may be an object
	 * containing configuration options for the mode, with a name property that
	 * names the mode (for example {name: "javascript", json: true}).
	 * </p>
	 * @param string|object $mMode <p>
	 * The mode to use.
	 * </p>
	 * @param type $bAutoComplete <p>
	 * If there is a auto complete for the mode, you can enable it.
	 * </p>
	 * @param type $sAutoCompleteKeys <p>
	 * The keys to show the auto complete in the editor.
	 * </p>
	 * @throws CodeMirrorException
	 */
	public function mode($mMode, $bAutoComplete = false, $sAutoCompleteKeys = "Ctrl-Space"){
		$this->_aConfiguration["mode"] = $mMode;

		/**
		 * The cases of this switch are representing all available modes
		 * If the mode is available, the corresponding file is loaded
		 */
		switch ($mMode) {
			case "html":
			case "text/html":
				// Add the xml.js, otherwise a JavaScript error will occur: "TypeError: htmlMode.startState is not a function"
				$this->_oResource->addResource("js_mode_xml");
				$this->_oResource->addResource("js_mode_htmlmixed");
				// auto complete for javascript exists => add resourece
				if($bAutoComplete){
					$this->_oResource->addResource("js_addon_hint_html");
				}
				break;
			case "javascript":
			case "text/javascript":
				$this->_oResource->addResource("js_mode_javascript");
				// auto complete for html exists  => add resourece
				if($bAutoComplete){
					$this->_oResource->addResource("js_addon_hint_javascript");
				}
				break;
			default:
				// if the searched mode was not found.
				throw new CodeMirrorException("Code editor configuration error!", 200);
				break;
		}

		// add resources if auto complete is true
		if($bAutoComplete){
			$this->_oResource->addResource("css_addon_hint_show");
			$this->_oResource->addResource("js_addon_hint_show");
			$this->extraKeys($sAutoCompleteKeys, "autocomplete");
		}
	}

	/**
	 * <p>
	 * The theme to style the editor with. You must make sure the CSS file
	 * defining the corresponding .cm-s-[name] styles is loaded. The default is
	 * "default", for which colors are included in codemirror.css.
	 * It is possible to use multiple theming classes at once—for example
	 * "foo bar" will assign both the cm-s-foo and the cm-s-bar classes to the
	 * editor. The follwoing styles are possible:
	 * <table>
	 * <tr><td>Code</td><td>Theme</td></tr>
	 * <tr><td>0</td><td>3024-day</td></tr>
	 * <tr><td>1</td><td>3024-night</td></tr>
	 * <tr><td>2</td><td>3024-night</td></tr>
	 * <tr><td>3</td><td>ambiance</td></tr>
	 * <tr><td>4</td><td>base16-dark</td></tr>
	 * <tr><td>5</td><td>base16-light</td></tr>
	 * <tr><td>6</td><td>blackboard</td></tr>
	 * <tr><td>7</td><td>cobalt</td></tr>
	 * <tr><td>8</td><td>eclipse</td></tr>
	 * <tr><td>9</td><td>elegant</td></tr>
	 * <tr><td>10</td><td>erlang-dark</td></tr>
	 * <tr><td>11</td><td>lesser-dark</td></tr>
	 * <tr><td>12</td><td>mbo</td></tr>
	 * <tr><td>13</td><td>mdn-like</td></tr>
	 * <tr><td>14</td><td>midnight</td></tr>
	 * <tr><td>15</td><td>monokai</td></tr>
	 * <tr><td>16</td><td>neat</td></tr>
	 * <tr><td>17</td><td>night</td></tr>
	 * <tr><td>18</td><td>paraiso-dark</td></tr>
	 * <tr><td>19</td><td>paraiso-light</td></tr>
	 * <tr><td>20</td><td>pastel-on-dark</td></tr>
	 * <tr><td>21</td><td>rubyblue</td></tr>
	 * <tr><td>22</td><td>solarized dark</td></tr>
	 * <tr><td>23</td><td>solarized light</td></tr>
	 * <tr><td>24</td><td>the-matrix</td></tr>
	 * <tr><td>25</td><td>tomorrow-night-eighties</td></tr>
	 * <tr><td>26</td><td>twilight</td></tr>
	 * <tr><td>27</td><td>vibrant-ink</td></tr>
	 * <tr><td>28</td><td>xq-dark</td></tr>
	 * <tr><td>29</td><td>xq-light</td></tr>
	 * </table>
	 * </p>
	 * @param integer $iCode <p>
	 * The code for the style you want to use. Look at teh table obove.
	 * </p>
	 * @throws CodeMirrorException 
	 */
	public function theme($iCode){
		// all themes
		$aThemes = array(
			"3024-day","3024-night","3024-night","ambiance","base16-dark","base16-light","blackboard","cobalt","eclipse","elegant","erlang-dark","lesser-dark",
			"mbo", "mdn-like", "midnight", "monokai", "neat", "night", "paraiso-dark", "paraiso-light", "pastel-on-dark", "rubyblue", "solarized dark",
			"solarized light", "the-matrix", "tomorrow-night-eighties", "twilight", "vibrant-ink", "xq-dark", "xq-light"
		);
		if(!is_numeric($iCode)){
			throw new CodeMirrorException("Theme code is not numeric value", 300);
		} elseif(!array_key_exists($iCode, $aThemes)){
			throw new CodeMirrorException("Theme not found!", 301);
		}
		// extend the editor config
		$this->_aConfiguration["theme"] = $aThemes[$iCode];
		// Name of the resource
		$sRequirement = "css_theme_" . $aThemes[$iCode];
		// add the css resource
		$this->_oResource->addResource($sRequirement);
	}

	/**
	 * <p>
	 * How many spaces a block (whatever that means in the edited language)
	 * should be indented. The default is 2.
	 * </p>
	 * @param integer $iIndentUnit <p>
	 * 
	 * </p>
	 */
	public function indentUnit($iIndentUnit){
		$this->_aConfiguration["indentUnit"] = $iIndentUnit;
	}

	/**
	 * <p>
	 * Whether to use the context-sensitive indentation that the mode provides
	 * (or just indent the same as the line before). Defaults to true.
	 * </p>
	 * @param boolean $bSmartIndent <p>
	 * 
	 * </p>
	 */
	public function smartIndent($bSmartIndent){
		$this->_aConfiguration["smartIndent"] = $bSmartIndent;
	}

	/**
	 * The width of a tab character. Defaults to 4.
	 * @param integer $iTabSize <p>
	 * The width of a tab character.
	 * </p>
	 */
	public function tabSize($iTabSize){
		$this->_aConfiguration["tabSize"] = $iTabSize;
	}

	/**
	 * <p>
	 * Whether, when indenting, the first N*tabSize spaces should be replaced by
	 * N tabs. Default is false.
	 * </p>
	 * @param boolean $bIndentWithTabs <p>
	 * TRUE if the first N*tabSize spaces should be replaced by N tabs, FALSE
	 * otherwise.
	 * </p>
	 */
	public function indentWithTabs($bIndentWithTabs){
		$this->_aConfiguration["indentWithTabs"] = $bIndentWithTabs;
	}

	/**
	 * <p>
	 * Configures whether the editor should re-indent the current line when a
	 * character is typed that might change its proper indentation (only works
	 * if the mode supports indentation). Default is true.
	 * </p>
	 * @param boolean $bElectricChars <p>
	 * TRUE if re-indent, FALSE otherwise.
	 * </p>
	 */
	public function electricChars($bElectricChars){
		$this->_aConfiguration["electricChars"] = $bElectricChars;
	}

	/**
	 * <p>
	 * A regular expression used to determine which characters should be
	 * replaced by a special placeholder. Mostly useful for non-printing special
	 * characters. The default is /[\u0000-\u0019\u00ad\u200b\u2028\u2029\ufeff]/.
	 * </p>
	 * @param string $sSpecialChars <p>
	 * The regular expression as a string.
	 * </p>
	 */
	public function specialChars($sSpecialChars){
		$this->_aConfiguration["specialChars"] = $sSpecialChars;
	}

	/**
	 * <p>
	 * A function that, given a special character identified by the specialChars
	 * option, produces a DOM node that is used to represent the character. By
	 * default, a red dot (•) is shown, with a title tooltip to indicate the
	 * character code.
	 * </p>
	 * @param string $sSpecialCharPlaceholder <p>
	 * The (JavaScript) function as a string: "function (char) : Element"
	 * </p>
	 */
	public function specialCharPlaceholder($sSpecialCharPlaceholder){
		$this->_aConfiguration["specialCharPlaceholder"] = $sSpecialCharPlaceholder;
	}

	/**
	 * <p>
	 * Determines whether horizontal cursor movement through right-to-left
	 * (Arabic, Hebrew) text is visual (pressing the left arrow moves the cursor
	 * left) or logical (pressing the left arrow moves to the next lower index
	 * in the string, which is visually right in right-to-left text). The
	 * default is false on Windows, and true on other platforms.
	 * </p>
	 * @param boolean $bRtlMoveVisually <p>
	 * </p>
	 */
	public function rtlMoveVisually($bRtlMoveVisually){
		$this->_aConfiguration["rtlMoveVisually"] = $bRtlMoveVisually;
	}

	/**
	 * <p>
	 * Configures the keymap to use. The default is "default", which is the only
	 * keymap defined in codemirror.js itself. Extra keymaps are found in the
	 * keymap directory. See the section on keymaps for more information.
	 * The follwoing key maps are possible:
	 * <ul>
	 * <li>emacs</li>
	 * <li>extra</li>
	 * <li>vim</li>
	 * </ul>
	 * </p>
	 * @param string $sKeyMap <p>
	 * </p>
	 */
	public function keyMap($sKeyMap){
		$this->_aConfiguration["keyMap"] = $sKeyMap;

		// Name of the resource
		$sRequirement = "js_keymap_" . $sKeyMap;
		// add the js resource
		$this->_oResource->addResource($sRequirement);
	}

	/**
	 * <p>
	 * Allows to run the editor in fullscreen. If you enable this, you cannot
	 * disable fullscreen afterwards.
	 * </p>
	 * @param string $sOnKey <p>
	 * The key the client must press to enter fullscreen.
	 * </p>
	 * @param string $sOffKey <p>
	 * The key the client must press to exit fullscreen.
	 * </p>
	 */
	public function enableFullscreen($sOnKey = "F11", $sOffKey = "Esc"){

		$this->extraKeys("$sOnKey", "function(cm){cm.setOption(\"fullScreen\", !cm.getOption(\"fullScreen\"));}");
		$this->extraKeys("$sOffKey", "function(cm){if (cm.getOption(\"fullScreen\")) cm.setOption(\"fullScreen\", false);}");

		// add the js resource
		$this->_oResource->addResource("js_addon_display_fullscreen");
		// add the css resource
		$this->_oResource->addResource("css_addon_display_fullscreen");
	}

	/**
	 * <p>
	 * Can be used to specify extra keybindings for the editor, alongside the
	 * ones defined by keyMap. Should be either null, or a valid keymap value.
	 * </p>
	 * @param type $sKey <p>
	 * i.e. "F11", or "Esc".
	 * </p>
	 * @param type $sFunction <p>
	 * i.e. a JavaScript function or something else.
	 * </p>
	 */
	public function extraKeys($sKey, $mValue){
		// Extra Keys
		$sExtraKeys = $this->_aConfiguration["extraKeys"];

		// If extrakeys exists, then remove first and last bracket (Will be added later)
		if($sExtraKeys !== null){
			// Remove first bracket
			$sExtraKeys = substr($sExtraKeys, 1);
			// Remove last bracket
			$sExtraKeys = substr($sExtraKeys, 0, -1);
			// Extend a comma, because an extra key will be add now
			$sExtraKeys .= ",";
		} else {
			// New
			$sExtraKeys = "";
		}

		// If numeric or a function, extend without quotes, otherwise with quotes
		if(
			is_numeric($mValue)	||
			substr($mValue, 0, 8) === "function"
		){
			// Extend without quotes
			$sExtraKeys .= "\"$sKey\": $mValue";
		} elseif(is_bool($mValue)) {
			// If boolean, write true or false
			if($mValue){
				$sExtraKeys .= "\"$sKey\": true";
			} else {
				$sExtraKeys .= "\"$sKey\": false";
			}
		} else {
			// Extend with quotes
			$sExtraKeys .= "\"$sKey\": \"$mValue\"";
		}

		// Open and close brackets
		$sExtraKeys = "{" . $sExtraKeys . "}";
		// Set config
		$this->_aConfiguration["extraKeys"] = stripcslashes($sExtraKeys);

	}

	/**
	 * <p>
	 * Whether CodeMirror should scroll or wrap for long lines. Defaults to
	 * false (scroll).
	 * </p>
	 * @param boolean $bLineWrapping <p>
	 * TRUE if wrap, FALSE otherwise.
	 * </p>
	 */
	public function lineWrapping($bLineWrapping){
		$this->_aConfiguration["lineWrapping"] = $bLineWrapping;
	}

	/**
	 * Whether to show line numbers to the left of the editor.
	 * @param boolean $bLineNumbers <p>
	 * TRUE if you want to enable this function, false otherwise.
	 * </p>
	 */
	public function lineNumbers($bLineNumbers){
		$this->_aConfiguration["lineNumbers"] = $bLineNumbers;
	}

	/**
	 * At which number to start counting lines. Default is 1.
	 * @param integer $iFirstLineNumber <p>
	 * The number to start counting the lines.
	 * </p>
	 */
	public function firstLineNumber($iFirstLineNumber){
		$this->_aConfiguration["firstLineNumber"] = $iFirstLineNumber;
	}

	/**
	 * <p>
	 * A function used to format line numbers. The function is passed the line
	 * number, and should return a string that will be shown in the gutter.
	 * </p>
	 * @param string $sLineNumberFormatter <p>
	 * The (JavaScript) function as a string: "function (line: integer) : string"
	 * </p>
	 */
	public function lineNumberFormatter($sLineNumberFormatter){
		$this->_aConfiguration["lineNumberFormatter"] = $sLineNumberFormatter;
	}

	/**
	 * <p>
	 * Can be used to add extra gutters (beyond or instead of the line number
	 * gutter). Should be an array of CSS class names, each of which defines a
	 * width (and optionally a background), and which will be used to draw the
	 * background of the gutters. May include the CodeMirror-linenumbers class,
	 * in order to explicitly set the position of the line number gutter (it
	 * will default to be to the right of all other gutters). These class names
	 * are the keys passed to setGutterMarker.
	 * </p>
	 * @param string[] $aGutters <p>
	 * The guetters as an array of strings which are the class names (CSS).
	 * </p>
	 */
	public function gutters($aGutters){
		$this->_aConfiguration["gutters"] = $aGutters;
	}

	/**
	 * <p>
	 * Determines whether the gutter scrolls along with the content horizontally
	 * (false) or whether it stays fixed during horizontal scrolling (true, the 
	 * default).
	 * </p>
	 * @param boolean $bFixedGutter <p>
	 * TRUE if stays fixed, FALSE otherwise.
	 * </p>
	 */
	public function fixedGutter($bFixedGutter){
		$this->_aConfiguration["fixedGutter"] = $bFixedGutter;
	}

	/**
	 * <p>
	 * When fixedGutter is on, and there is a horizontal scrollbar, by default
	 * the gutter will be visible to the left of this scrollbar. If this option
	 * is set to true, it will be covered by an element with class
	 * CodeMirror-gutter-filler.
	 * </p>
	 * @param boolean $bCoverGutterNextToScrollbar <p>
	 * TRUE if your want to convert.
	 * </p>
	 */
	public function coverGutterNextToScrollbar($bCoverGutterNextToScrollbar){
		$this->_aConfiguration["coverGutterNextToScrollbar"] = $bCoverGutterNextToScrollbar;
	}

	/**
	 * <p>
	 * This disables editing of the editor content by the user. If the special
	 * value "nocursor" is given (instead of simply true), focusing of the
	 * editor is also disallowed.
	 * </p>
	 * @param boolean|string $mReadOnly <p>
	 * TRUE if editing shall be disallowed.
	 * "nocursor" if focusing of the editor should be disallowed.
	 * FALSE otherwise.
	 * </p>
	 */
	public function readOnly($mReadOnly){
		$this->_aConfiguration["readOnly"] = $mReadOnly;
	}

	/**
	 * <p>
	 * Whether the cursor should be drawn when a selection is active. Defaults
	 * to false.
	 * </p>
	 * @param boolean $bShowCursorWhenSelecting <p>
	 * TRUE if cursor should be drawn when a selection is active, FALSE otherwise.
	 * </p>
	 */
	public function showCursorWhenSelecting($bShowCursorWhenSelecting){
		$this->_aConfiguration["showCursorWhenSelecting"] = $bShowCursorWhenSelecting;
	}

	/**
	 * The maximum number of undo levels that the editor stores. Defaults to 40.
	 * @param integer $iUndoDepth <p>
	 * The maximum number of undo levels.
	 * </p>
	 */
	public function undoDepth($iUndoDepth){
		$this->_aConfiguration["undoDepth"] = $iUndoDepth;
	}

	/**
	 * <p>
	 * The period of inactivity (in milliseconds) that will cause a new history
	 * event to be started when typing or deleting. Defaults to 500.
	 * </p>
	 * @param integer $bHistoryEventDelay <p>
	 * Time in milliseconds.
	 * </p>
	 */
	public function historyEventDelay($bHistoryEventDelay){
		$this->_aConfiguration["historyEventDelay"] = $bHistoryEventDelay;
	}

	/**
	 * <p>
	 * The tab index to assign to the editor. If not given, no tab index will be
	 * assigned.
	 * </p>
	 * @param integer $iTabindex <p>
	 * The tabindex as integer.
	 * </p>
	 */
	public function tabindex($iTabindex){
		$this->_aConfiguration["tabindex"] = $iTabindex;
	}

	/**
	 * <p>
	 * Can be used to make CodeMirror focus itself on initialization. Defaults
	 * to off. When fromTextArea is used, and no explicit value is given for
	 * this option, it will be set to true when either the source textarea is
	 * focused, or it has an autofocus attribute and no other element is focused.
	 * </p>
	 * @param boolean $bAutofocus <p>
	 * TRUE if self focus on initialization, FALSE otherwise.
	 * </p>
	 */
	public function autofocus($bAutofocus){
		$this->_aConfiguration["autofocus"] = $bAutofocus;
	}

	/**
	 * Controls whether drag-and-drop is enabled. On by default.
	 * @param boolean $bDragDrop <p>
	 * TRUE if drag and drop should be enabled, FALSE otherwise.
	 * </p>
	 */
	public function dragDrop($bDragDrop){
		$this->_aConfiguration["dragDrop"] = $bDragDrop;
	}

	/**
	 * <p>
	 * Half-period in milliseconds used for cursor blinking. The default blink
	 * rate is 530ms. By setting this to zero, blinking can be disabled.
	 * </p>
	 * @param integer $iCursorBlinkRate <p>
	 * Blinking time in milliseconds.
	 * </p>
	 */
	public function cursorBlinkRate($iCursorBlinkRate){
		$this->_aConfiguration["cursorBlinkRate"] = $iCursorBlinkRate;
	}

	/**
	 * <p>
	 * How much extra space to always keep above and below the cursor when
	 * approaching the top or bottom of the visible view in a scrollable
	 * document. Default is 0.
	 * </p>
	 * @param integer $iCursorScrollMargin <p>
	 * The extra space as a number.
	 * </p>
	 */
	public function cursorScrollMargin($iCursorScrollMargin){
		$this->_aConfiguration["cursorScrollMargin"] = $iCursorScrollMargin;
	}

	/**
	 * <p>
	 * Determines the height of the cursor. Default is 1, meaning it spans the
	 * whole height of the line. For some fonts (and by some tastes) a smaller
	 * height (for example 0.85), which causes the cursor to not reach all the
	 * way to the bottom of the line, looks better
	 * </p>
	 * @param float $fCursorHeight <p>
	 * The height of the cursor.
	 * 1 represents 100% and 0.53 represents 53% of the whole line height.
	 * </p>
	 */
	public function cursorHeight($fCursorHeight){
		$this->_aConfiguration["cursorHeight"] = $fCursorHeight;
	}

	/**
	 * <p>
	 * Controls whether, when the context menu is opened with a click outside
	 * of the current selection, the cursor is moved to the point of the click.
	 * Defaults to true.
	 * </p>
	 * @param boolean $bResetSelectionOnContextMenu <p>
	 * TRUE if the cursor shall be moved to the point of the click when the
	 * context menu is opened with a click outside of the current selection, 
	 * FALSE otherwise.
	 * </p>
	 */
	public function resetSelectionOnContextMenu($bResetSelectionOnContextMenu){
		$this->_aConfiguration["resetSelectionOnContextMenu"] = $bResetSelectionOnContextMenu;
	}

	/**
	 * <p>
	 * Highlighting is done by a pseudo background-thread that will work for
	 * workTime milliseconds, and then use timeout to sleep for workDelay
	 * milliseconds. The defaults are 200 and 300, you can change these options
	 * to make the highlighting more or less aggressive.
	 * </p>
	 * @param integer $iWorkTime <p>
	 * The work time in milliseconds.
	 * </p>
	 * @param integer $iWorkDelay <p>
	 * The work delay in milliseconds.
	 * </p>
	 */
	public function workTime($iWorkTime){
		$this->_aConfiguration["workTime"] = $iWorkTime;
	}

	/**
	 * <p>
	 * Highlighting is done by a pseudo background-thread that will work for
	 * workTime milliseconds, and then use timeout to sleep for workDelay
	 * milliseconds. The defaults are 200 and 300, you can change these options
	 * to make the highlighting more or less aggressive.
	 * </p>
	 * @param integer $iWorkDelay <p>
	 * The work delay in milliseconds.
	 * </p>
	 */
	public function workDelay($iWorkDelay){
		$this->_aConfiguration["workDelay"] = $iWorkDelay;
	}

	/**
	 * <p>
	 * Indicates how quickly CodeMirror should poll its input textarea for
	 * changes (when focused). Most input is captured by events, but some
	 * things, like IME input on some browsers, don't generate events that allow
	 * CodeMirror to properly detect it. Thus, it polls. Default is 100
	 * milliseconds.
	 * </p>
	 * @param integer $iPollInterval <p>
	 * The speed of polling in milliseconds.
	 * </p>
	 */
	public function pollInterval($iPollInterval){
		$this->_aConfiguration["pollInterval"] = $iPollInterval;
	}

	/**
	 * <p>
	 * By default, CodeMirror will combine adjacent tokens into a single span if
	 * they have the same class. This will result in a simpler DOM tree, and
	 * thus perform better. With some kinds of styling (such as rounded
	 * corners), this will change the way the document looks. You can set this
	 * option to false to disable this behavior.
	 * </p>
	 * @param boolean $bFlattenSpans <p>
	 * FALSE if you want to disable the behavior of flatten spans.
	 * </p>
	 */
	public function flattenSpans($bFlattenSpans){
		$this->_aConfiguration["flattenSpans"] = $bFlattenSpans;
	}

	/**
	 * <p>
	 * When enabled (off by default), an extra CSS class will be added to each
	 * token, indicating the (inner) mode that produced it, prefixed with
	 * "cm-m-". For example, tokens from the XML mode will get the cm-m-xml
	 * class.
	 * </p>
	 * @param boolean $bAddModeClass <p>
	 * TRUE if you want mode classes, FALSE otherwise.
	 * </p>
	 */
	public function addModeClass($bAddModeClass){
		$this->_aConfiguration["addModeClass"] = $bAddModeClass;
	}

	/**
	 * <p>
	 * When highlighting long lines, in order to stay responsive, the editor
	 * will give up and simply style the rest of the line as plain text when it
	 * reaches a certain position. The default is 10 000. You can set this to
	 * Infinity to turn off this behavior.
	 * </p>
	 * @param integer $iMaxHighlightLength <p>
	 * The maximal highlight length as an integer.
	 * </p>
	 */
	public function maxHighlightLength($iMaxHighlightLength){
		$this->_aConfiguration["maxHighlightLength"] = $iMaxHighlightLength;
	}

	/**
	 * <p>
	 * When measuring the character positions in long lines, any line longer
	 * than this number (default is 10 000), when line wrapping is off, will
	 * simply be assumed to consist of same-sized characters. This means that,
	 * on the one hand, measuring will be inaccurate when characters of varying
	 * size, right-to-left text, markers, or other irregular elements are
	 * present. On the other hand, it means that having such a line won't freeze
	 * the user interface because of the expensiveness of the measurements.
	 * </p>
	 * @param integer $iCrudeMeasuringFrom <p>
	 * 
	 * </p>
	 */
	public function crudeMeasuringFrom($iCrudeMeasuringFrom){
		$this->_aConfiguration["crudeMeasuringFrom"] = $iCrudeMeasuringFrom;
	}

	/**
	 * <p>
	 * Specifies the amount of lines that are rendered above and below the part
	 * of the document that's currently scrolled into view. This affects the
	 * amount of updates needed when scrolling, and the amount of work that such
	 * an update does. You should usually leave it at its default, 10. Can be
	 * set to Infinity to make sure the whole document is always rendered, and
	 * thus the browser's text search works on it. This will have bad effects on
	 * performance of big documents.
	 * </p>
	 * @param integer $iViewportMargin <p>
	 * The viewport margin as an integer.
	 * </p>
	 */
	public function viewportMargin($iViewportMargin){
		$this->_aConfiguration["viewportMargin"] = $iViewportMargin;
	}
}