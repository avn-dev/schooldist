<?php

namespace TcFrontend\Controller;

use Core\Helper\Bundle;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TcFrontend\Dto\WidgetPath;
use TcFrontend\Factory\WidgetCombinationFactory;
use TcFrontend\Factory\WidgetPathHashedFactory;
use TcFrontend\Interfaces\WidgetCombination;

class WrapperController extends WidgetController {

	public const ERROR_COMBINATION_KEY = 'Invalid combination key';

	public const ERROR_TEMPLATE_KEY = 'Invalid template key';

	protected function cleanString(string $string) {
		return preg_replace("/[^a-zA-Z0-9\-]/i", '', $string);
	}
	
	/**
	 * Dynamische JS-Datei
	 *
	 * @param Request $request
	 * @return string
	 */
	protected function buildFile(Request $request): string {

		$combination = \Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Combination', 'getByKey', [$request->get('c')]);
		$template = \Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Template', 'getByKey', [$request->get('t')]);

		if(!$combination instanceof \Ext_TC_Frontend_Combination) {
			return response(\TcFrontend\Controller\WrapperController::ERROR_COMBINATION_KEY, 404);
		}

		if(!$template instanceof \Ext_TC_Frontend_Template) {
			return response(\TcFrontend\Controller\WrapperController::ERROR_TEMPLATE_KEY, 404);
		}

		$prependPath = WidgetPath::buildPrependPath($request);

		$paths = $this->preparePaths([
			'api' => new WidgetPath('assets/tc-frontend', 'js/wrapper', 'widget')
		], $prependPath);
		
		
		$widgetId = 'fidelo-widget';
		$formId = 'fidelo-widget-form';
		if($request->has('w')) {
			$widgetId = $this->cleanString($request->get('w'));
			$formId = $widgetId.'-form';
		}
		
		$output = '
			
	var scriptUrl = null;

	function splitScriptUrl() {
		if (scriptUrl) {
			return scriptUrl;
		}

		var scriptSrc = document.currentScript.src;

		scriptUrl = new URL(scriptSrc);
		return scriptUrl;
	}
	
	function getHost() {
		var url = splitScriptUrl();
		return url.protocol + \'//\' + url.host;
	}
	
	function splitPath(path) {
		var url = [path];
		if (path.indexOf(\'proxy://\') === 0) {
			url[0] = getHost() + \'/\' + path.replace(\'proxy://\', \'\');
		}

		// Convert ?callback=func to a callable function
		var match = path.match(/callback=?([^&]*)/);
		if (match && match[1]) {
			// TODO Improve callback functionality if needed
			match[1] = match[1].replace(\'__FIDELO__.\', \'\');
			if (widget.hasOwnProperty(match[1])) {
				url[1] = widget[match[1]];
			} else {
				console.error(\'Callback not found\', match);
			}
		}

		return url;
	}

	function initForm() {
	
		var form = document.getElementById(\''.$formId.'\');
		
		if(!form) {
			return;
		}

		var container = document.getElementById(\''.$widgetId.'\');
		container.scrollIntoView();

		form.addEventListener("submit", function(e){

			e.preventDefault()
			var form = e.target
			var data = new FormData(form)

			postData(url, data);

		});

	}
		
	function setInnerHtml(elm, html) {
      elm.innerHTML = html;
      Array.from(elm.querySelectorAll("script")).forEach(oldScript => {
        const newScript = document.createElement("script");
        Array.from(oldScript.attributes)
          .forEach(attr => newScript.setAttribute(attr.name, attr.value));
        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
        oldScript.parentNode.replaceChild(newScript, oldScript);
      });
    }
	
	var splitPath = splitPath(\''.$paths['api'].'\');
	var url = splitPath[0];

	if(window.location.search) {
		url += window.location.search;
	} else {
		url += \'?\';
	}

	url += \'&c='.$this->cleanString($request->get('c')).'\';
	url += \'&t='.$this->cleanString($request->get('t')).'\';
	';
		
	if($request->has('w')) {
		$output .= 'url += \'&w='.$widgetId.'\';';
	}
	
	$output .= '
	var data = {};

	async function postData(url = "", data = {}) {

		var params = {
			method: "POST"
		};

		if(data instanceof FormData) {
			// FormData nicht direkt setzen weil der Proxy das nicht weiterleiten kann.
			params.body = new URLSearchParams(data);
		} else {
			params.body = JSON.stringify(data);
		}

		const response = await fetch(url, params);

		var txt = await response.text();
		
		var container = document.getElementById(\''.$widgetId.'\');
		setInnerHtml(container, txt);
		initForm();
	}

	var container = document.querySelector(\'fidelo-widget\');

	if(container.id == \'\') {
		container.id = \'fidelo-widget\';
	}

	postData(url);
';
		
		return $output;
	}

	public function php(Request $request): string {

		/*
		 * Da bei Cross-Domain-Requests nicht mit Cookies gearbeitet werden kann, wird die Session-ID über ein 
		 * Hidden-Field übertragen und hier wieder als Session-ID gesetzt.
		 */
		if($request->has('fidelo-widget-sid')) {
			session_id($this->cleanString($request->input('fidelo-widget-sid')));
		}

		$combination = \Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Combination', 'getByKey', [$request->get('c')]);
		$template = \Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Template', 'getByKey', [$request->get('t')]);

		if(!$combination instanceof \Ext_TC_Frontend_Combination) {
			return response(\TcFrontend\Controller\WrapperController::ERROR_COMBINATION_KEY, 404);
		}

		if(!$template instanceof \Ext_TC_Frontend_Template) {
			return response(\TcFrontend\Controller\WrapperController::ERROR_TEMPLATE_KEY, 404);
		}

		$request->add(['X-Originating-URI' => $request->headers->get('referer', 'n/a')]);

		$smarty = new \SmartyWrapper();
		$html = $combination->generateContent($smarty, $template, $request, (bool)$request->get('d', false));

		$widgetId = $this->cleanString($request->get('w', false));
		
		$formId = 'fidelo-widget-form';
		
		if(!empty($widgetId)) {
			$formId = $widgetId.'-form';
		}

		$html = preg_replace('/\<form (.*?)\>/', '<form id="fidelo-widget-form" $1><input type="hidden" name="fidelo-widget-sid" value="'.session_id().'">', $html);

		return $html;
	}
	
}