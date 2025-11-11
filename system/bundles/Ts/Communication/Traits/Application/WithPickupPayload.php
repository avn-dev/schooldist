<?php

namespace Ts\Communication\Traits\Application;

use Communication\Dto\Message\Attachment;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;

trait WithPickupPayload
{
	/**
	 * TODO Das Dokument wird bei JEDEM Öffnen des Dialogs generiert!
	 *
	 * @param LanguageAbstract $l10n
	 * @param Collection $models
	 * @return Attachment|null
	 */
	protected function withPickupExcelAttachment(LanguageAbstract $l10n, Collection $models, \Ext_Thebing_School $school): ?Attachment
	{
		$file = '';
		try {

			$export = new \Ext_Thebing_Pickup_Export($school, $l10n->getContext());

			foreach($models as $model) {
				/* @var \Ext_TS_Inquiry_Journey_Transfer $model */
				$export->loadStudentData($model);
			}

			$file = $export->save();
		} catch(\Throwable $e) {
			__pout($e);
		}

		if (file_exists($file)) {
			return new Attachment('students_list', $file, $l10n->translate('Schülerdaten'));
		}

		return null;
	}
}