<?php

namespace Tc\Service\Wizard\Sitemap;

use Tc\Service\Wizard;

class SitemapHtml
{
	public function __construct(private Wizard $wizard) {}

	/**
	 * HTML generieren
	 *
	 * @param Wizard\Structure\Step $currentStep
	 * @return string
	 * @throws \SmartyException
	 */
	public function render(Wizard\Structure\Step $currentStep): string
	{
		$finishedLogs = collect($this->wizard->getLogs())
			->filter(fn($log) => $log->isFinished())
			->toArray();

		$smarty = new \SmartyWrapper();
		$smarty->assign('wizard', $this->wizard);
		$smarty->assign('structure', $this->wizard->getStructure()->toSitemapArray($this->wizard, $currentStep, $finishedLogs));
		return $smarty->fetch('@Tc/wizard/sitemap.tpl');
	}
}