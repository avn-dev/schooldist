<?php

namespace Tc\Admin\Components\Dashboard;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\VueComponentDto;

use Admin\Instance;
use Admin\Interfaces\Component\VueComponent;
use Carbon\Carbon;
use Core\Service\HtmlPurifier;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class NewsWidgetComponent implements VueComponent
{
	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('NewsWidget', '@Tc/admin/components/dashboard/NewsWidget.vue');
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		[$dateAsOf, $news] = \Ext_TC_Welcome::checkNews(!$request->boolean('force'));

		\Tc\Service\SystemEvents::dispatchNewsEvents(true);

		$news = array_map(function ($entry) {
			$entry['date'] = \Factory::getObject(\Ext_TC_Gui2_Format_Date_Time::class)->formatByValue($entry['date']);
			$entry['important'] = (bool) $entry['important'];
			$entry['title'] = strip_tags($entry['title']);
			$entry['content'] = (new HtmlPurifier(HtmlPurifier::SET_TCPDF))->purify($entry['content']);
			return Arr::only($entry, ['key', 'title', 'date', 'important', 'content']);
		}, $news);

		return (new InitialData([
				'news' => $news,
			]))
			->dateAsOf(Carbon::createFromTimestamp($dateAsOf, date_default_timezone_get()))
			->l10n([
				'dashboard.news.empty' => $admin->translate('Keine Ankündigungen vorhanden', 'Dashboard')
			]);
	}

	public function isAccessible(\Access $access): bool
	{
		return true;
	}
}