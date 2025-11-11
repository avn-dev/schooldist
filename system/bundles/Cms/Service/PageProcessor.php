<?php

namespace Cms\Service;

class PageProcessor {

	public $content;
	public $iReplaceVarsRuntime;

	/**
	 * @var PageParser 
	 */
	protected $oPageParser;

	public function __construct(PageParser $oPageParser) {

		$this->oPageParser = $oPageParser;
		
	}

	function start() {
		
		/**
		 * Gzip compression
		 * @todo Einstellbar machen über system_data
		 */
		ob_start();

	}
	
	function preprocess() {
		global $system_data;
		global $page_data;
		global $template_data;

		if(function_exists($system_data['preprocess'])) {
			call_user_func($system_data['preprocess']);
		}

	}

	function replacevars() {

		$iStart = microtime(true);
		
		$this->content = \Cms\Service\ReplaceVars::execute($this->content);

		\System::wd()->executeHook('page_processor_replace', $this->content);

		$iEnd = microtime(true);
		
		$this->iReplaceVarsRuntime = $iEnd - $iStart;
		
	}

	function postprocess() {
		global $system_data;

		if(!empty($system_data['postprocess'])) {
			$this->content = call_user_func($system_data['postprocess'], $this->content);
		}

	}

	function output() {
		global $system_data, $session_data;
		
		if(\System::d('debugmode')) {

			/**
			 * @todo Datenbankausführungszeit
			 */
			$aQueries = \Util::getQueryHistory();
			$fDbRuntime = \DB::getDefaultConnection()->getTotalQueryDuration();
			
			$this->content = str_replace('#post:CURRENTRUNTIME#', round($this->oPageParser->getCurrentRuntime(), 4), $this->content);
			$this->content = str_replace('#post:QUERIES#', \DB::getDefaultConnection()->getQueryCount(), $this->content);
			$this->content = str_replace('#post:MEMORYUSAGE#', \Util::formatFilesize(memory_get_usage()), $this->content);
			$this->content = str_replace('#post:WDTAGRUNTIME#', round($this->iReplaceVarsRuntime, 4), $this->content);
			$this->content = str_replace('#post:DBRUNTIME#', round($fDbRuntime, 4), $this->content);
			
			if(function_exists("memory_get_peak_usage")) {
				$this->content = str_replace('#post:MEMORYMAXUSAGE#', \Util::formatFilesize(memory_get_peak_usage()), $this->content);
			}

		}

		// GZ aktivieren, wenn Debugmodus aus
		if(
			\System::d('debugmode') == 0
		) {
			ob_start("ob_gzhandler");
		}

		echo $this->content;

		ob_flush();
		
	}

}

