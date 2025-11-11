<?php

return [

	'log_messages' => [
		\Cms\Helper\Log::LOG_STATS_UPDATED => 'Die Statistik wurde aktualisiert.',
		\Cms\Helper\Log::LOG_CATEGORIE_CREATED => 'Neue Kategorie erstellt',
		\Cms\Helper\Log::LOG_PAGE_CREATED => 'Neue Seite erstellt',
		\Cms\Helper\Log::LOG_PAGE_COPY => 'Seitenkopie wurde erstellen',
		\Cms\Helper\Log::LOG_TEMPLATE_CREATED => 'Seitenvorlage wurde erstellt',
		\Cms\Helper\Log::LOG_SITES_LANGUAGES_UPDATED => 'Änderung an den Systemsprachen wurden vorgenommen',
		\Cms\Helper\Log::LOG_SITES_LANGUAGE_DELETED => 'Eine Systemsprache wurde gelöscht',
		\Cms\Helper\Log::LOG_SITES_LANGUAGE_POSITION => 'Die Reihenfolge der Systemsprachen wurde geändert',
		\Cms\Helper\Log::LOG_SITES_INIT => 'Systemsprache wurde neu initialisiert',
		\Cms\Helper\Log::LOG_SITES_UPDATED => 'Änderung an den Internetauftritten',
		\Cms\Helper\Log::LOG_SITES_DELETED => 'Internetauftritt wurde gelöscht',
		\Cms\Helper\Log::LOG_SITES_POSITION => 'Reihenfolge der Internetauftritte wurde geändert',
		\Cms\Helper\Log::LOG_PAGE_PUBLISHED => 'Seiteninhalt veröffentlicht!',
		\Cms\Helper\Log::LOG_BLOCK_UPDATED => 'Blockdaten wurden gespeichert.',
		\Cms\Helper\Log::LOG_BLOCK_DELETED => 'Block wurde gelöscht!',
		\Cms\Helper\Log::LOG_BLOCK_POSITION => 'Blockposition wurde geändert!',
		\Cms\Helper\Log::LOG_PAGE_PROPERTIES => 'Seiteneigenschaften wurden ge&auml;ndert.',
		\Cms\Helper\Log::LOG_CATEGORY_PROPERTIES => 'Kategorieeigenschaften wurden ge&auml;ndert.',
	],
	
	'commands' => [
		Cms\Command\GenerateStats::class
	],
	
	'hooks' => [
		Core\Command\Scheduler::HOOK_NAME => [
			'class' => Cms\Hook\SchedulerHook::class,
			'interface' => Core\Service\Hook\AbstractHook::BACKEND
		],
		'meta_data' => [
			'class' => Cms\Hook\MetaData::class,
			'interface' => Core\Service\Hook\AbstractHook::FRONTEND
		]
	]

];