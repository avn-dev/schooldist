<?php

namespace Ts\Admin\Components;

use Admin\Components\BookmarksComponent as BaseBookmarksComponent;
use Admin\Facades\Router;
use Admin\Factory\Content;
use Illuminate\Support\Arr;

class BookmarksComponent extends BaseBookmarksComponent
{
	protected function getMainNodes(): array
	{
		$nodes = [];

		if ($this->access->hasRight('thebing_invoice_generate_student')) {
			$client = \Ext_Thebing_Client::getFirstClient();
			$inboxes = array_keys($client->getInboxList('use_id', true));

			$action = Router::openGui2Dialog('ts_inquiry|inquiry', 'new', [], ['inbox_id' => Arr::first($inboxes)], initialize: false);
			$nodes['add_inquiry'] = ['text' => [$this->admin->translate('Neue Buchung')], 'icon' => 'fa fa-plus-circle', 'action' => $action];
		}

		if ($this->access->hasRight('core_communication')) {
			//$content = Content::c('/admin/extensions/tc/communication.html');
			//$action = Router::tab(md5('communication'), 'fa fa-envelope', $this->admin->translate('Kommunikation'), $content)
			//	->source(static::class, 'communication')
			//		->active();

			$action = Router::openCommunication(access: 'core_communication', initialize: false);
			$nodes['communication'] = ['text' => [$this->admin->translate('Kommunikation')], 'icon' => 'fa fa-envelope', 'action' => $action];
		}

		if ($this->access->hasRight('thebing_welcome_wishlist')) {
			$content = Content::iframe('/wishlist');
			$action = Router::tab(md5('wishlist'), 'fa fa-commenting', $this->admin->translate('Wunschzettel'), $content)
				->source(static::class, 'wishlist')
				->active();

			$nodes['wishlist'] = ['text' => ['Wunschzettel'], 'icon' => 'fa fa-commenting', 'action' => $action];
		}

		return [...$nodes, ...parent::getMainNodes()];
	}

}