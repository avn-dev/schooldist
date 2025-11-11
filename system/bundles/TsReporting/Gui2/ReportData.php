<?php

namespace TsReporting\Gui2;

use Tc\Traits\Gui2\Dialog\WithAccessMatrix;

class ReportData extends \Ext_Gui2_Data
{
	use WithAccessMatrix;

	protected function getAccessMatrix(): \Ext_TC_Access_Matrix
	{
		return new AccessMatrix();
	}

	public static function getBaseOptions(): array
	{
		$config = (new \Core\Helper\Bundle())->readBundleFile('TsReporting', 'definitions');

		return collect($config['bases'])
			->mapWithKeys(fn(string $c) => [$c => (new $c)->getTitle()])
			->toArray();
	}

	public static function getVisualizationOptions(): array
	{
		return [
			'table' => \L10N::t('Tabelle', \TsReporting\Entity\Report::TRANSLATION_PATH),
			'pivot' => \L10N::t('Pivot-Tabelle', \TsReporting\Entity\Report::TRANSLATION_PATH)
		];
	}
}
