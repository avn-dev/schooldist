<?php

namespace TcStatistic\Controller;

use Illuminate\Support\Str;
use TcStatistic\Exception\AlertException;
use TcStatistic\Exception\InvalidDateException;
use TcStatistic\Exception\NoResultsException;
use TcStatistic\Generator\Statistic\AbstractGenerator;

abstract class StatisticController extends \MVC_Abstract_Controller {

	const BUNDLE_NAME = 'TcStatistic';

	const TRANSLATION_PATH = 'Thebing Core » Statistics';

	/**
	 * HTML-Seite
	 */
	public function getPageAction() {

		$oGenerator = $this->createStatisticGenerator($this->_oRequest->get('statistic'));

		$oSmarty = $this->createSmartyObject($oGenerator);

		$oGui = new \Ext_Gui2();
		$oGui->gui_title = $oGenerator->getTitle();

		$oGuiHtml = new \Ext_Gui2_Html($oGui);
		$sTemplate = \Util::getDocumentRoot().'system/bundles/TcStatistic/Resources/views/page.tpl';

		$oSmarty->assign('oGui', $oGui);
		$oSmarty->assign('sFilterTemplatePath', \Util::getDocumentRoot().'system/bundles/TcStatistic/Resources/views/page/filter.tpl');
		$oSmarty->assign('aOptions', $oGuiHtml->generateHtmlHeader());
		$oSmarty->assign('sJs', $oGuiHtml->getJsFooter());
		$oSmarty->display($sTemplate);

		die();

	}

	/**
	 * AJAX-Request von HTML-Seite
	 */
	public function getStatisticAjaxAction() {

		$oGenerator = $this->createStatisticGenerator($this->_oRequest->get('statistic'));

		try {
			$oGenerator->setFilterValues($this->_oRequest);

			try {
				$oHtmlGenerator = $oGenerator->createHtmlGenerator();
				$sHtml = $oGenerator->generateViewGenerator($oHtmlGenerator);
				$this->_oView->set('table', $sHtml);
			} catch(NoResultsException $oException) {
				if (!empty($oException->getMessage())) {
					$this->_oView->set('error', $oException->getMessage());
				} else {
					$this->_oView->set('error', $this->getTranslations()['no_results']);
				}
			} catch(AlertException $e) {
				$this->_oView->set('error', $e->getMessage());
			}
		} catch(InvalidDateException $oException) {
			$this->_oView->set('error', $this->getTranslations()['date_error']);
		}

	}

	/**
	 * Export (Button)
	 */
	public function getExportExcelAction() {

		$oGenerator = $this->createStatisticGenerator($this->_oRequest->get('statistic'));

		try {
			$oGenerator->setFilterValues($this->_oRequest);

			if($this->_oRequest->exists('filters_checked')) {
				try {
					$oExcelGenerator = $oGenerator->createExcelGenerator();
					$oGenerator->generateViewGenerator($oExcelGenerator);
					$oExcelGenerator->render();
				} catch(NoResultsException $oException) {
					// Da die Seite erst geöffnet wird und dann die Exception auftritt, geht das leider nicht per Alert
					echo $this->getTranslations()['no_results'];
				} catch(AlertException $e) {
					echo $e->getMessage();
				}
				die();
			} else {
				$this->_oView->set('filters_checked', true);
			}
		} catch(InvalidDateException $oException) {
			$this->_oView->set('error', $this->getTranslations()['date_error']);
		}

	}

	/**
	 * Statistik-Generator anhand des übergebenen Strings ermitteln
	 *
	 * @param string $sStatistic
	 * @return AbstractGenerator $oGenerator
	 */
	protected function createStatisticGenerator($sStatistic) {

		$sStatistic = str_replace('/', '\\', $sStatistic);

		if(
			!Str::startsWith($sStatistic, 'Ext_') &&
			!Str::startsWith($sStatistic, '\\')
		) {
			$sClass = '\\'.static::BUNDLE_NAME.'\Generator\Statistic\\'.$sStatistic;
		} else {
			// Modules
			$sClass = $sStatistic;
		}

		if(!class_exists($sClass)) {
			throw new \RuntimeException('Unknown statistic: '.$sStatistic);
		}

		if(!is_subclass_of($sClass, AbstractGenerator::class)) {
			throw new \RuntimeException('Statistic generator is not a child of AbstractGenerator!');
		}

		return new $sClass();

	}

	/**
	 * Smarty-Objekt erzeugen
	 *
	 * @param AbstractGenerator $oGenerator
	 * @return \SmartyWrapper
	 */
	protected function createSmartyObject(AbstractGenerator $oGenerator) {

		$oSmarty = new \SmartyWrapper();
		$oSmarty->assign('sBundle', static::BUNDLE_NAME);
		$oSmarty->assign('sTitle', $oGenerator->getTitle());
		$oSmarty->assign('aTranslations', $this->getTranslations());
		$oSmarty->assign('aDateFilterBasedOnOptions', $oGenerator->getBasedOnOptionsForDateFilter());
		$oSmarty->assign('aInfoBoxItems', $oGenerator->getInfoTextListItems());
		$oSmarty->assign('aFilters', $oGenerator->getFilters());
		$oSmarty->assign('bFiltersShown', $oGenerator->isShowingFiltersInitially());

		$oSmarty->registerPlugin('modifier', 't', [self::class, 't']);

		return $oSmarty;

	}

	/**
	 * Übersetzungen
	 *
	 * @return string[]
	 */
	protected function getTranslations() {
		return [
			'filter' => static::t('Filter'),
			'show_more_options' => static::t('weitere Optionen anzeigen'),
			'hide_more_options' => static::t('weitere Optionen ausblenden'),
			'from' => static::t('Von'),
			'until' => static::t('bis'),
			'refresh' => static::t('Aktualisieren'),
			'export' => static::t('Exportieren'),
			'date_error' => static::t('Bitte füllen Sie die Datumsfilter korrekt aus.'),
			'based_on' => static::t('basierend auf'),
			'no_results' => static::t('Für den ausgewählten Zeitraum stehen keine Daten zur Verfügung.'),
			'hints' => static::t('Hinweise')
		];
	}

	/**
	 * Übersetzen-Funktion
	 *
	 * @param string $sTranslation
	 * @return string
	 */
	public static function t($sTranslation) {
		return \L10N::t($sTranslation, static::TRANSLATION_PATH);
	}

}
