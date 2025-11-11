<?php

namespace Gui2\Format;

class Status extends \Ext_Gui2_View_Format_ToolTip {

	public function format($value, &$column = null, &$data = null) {

		switch($data['status']) {
			case 'ready':
				$icon = \Ext_TC_Util::getIcon('confirm');
				break;
			case 'pending':
				// Kein spin, da nichts automatisch passiert
				$icon = \Ext_TC_Util::getIcon('refresh');//.' fa-spin';
				break;
			case 'fail':
			default:
			$icon = \Ext_TC_Util::getIcon('error');
				break;
		}

		return '<i class="fa ' . $icon . '"></i>';

	}

	public function getTitle(&$column = null, &$data = null) {

		$status = $data['status'];
		if (isset($data['status_original'])) {
			$status = $data['status_original']; // Elasticsearch-GUIs
		}

		switch($status) {
			case 'ready':
				$content = \L10N::t('Erfolgreich aktualisiert!');
				break;
			case 'pending':
				$content = \L10N::t('Wird aktualisiert!');
				break;
			case 'fail':
			default:
				$content = \L10N::t('Fehler aufgetreten!');
				break;
		}

		return [
			'content' => $content,
			'tooltip' => true
		];

	}

}