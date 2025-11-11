<?php

namespace Ts\Communication\Traits\Application;

use Communication\Dto\Message\Attachment;
use Illuminate\Support\Collection;

trait WithSchoolPayload
{
	protected function withSchoolUploads(int $type, Collection $schools, string $language = null): Collection
	{
		$attachments = [];

		foreach ($schools as $school) {
			/* @var \Ext_Thebing_School $school */
			$uploads = $school->getSchoolFiles($type, $language);

			foreach ($uploads as $upload) {
				$attachments[$upload['id']] = (new Attachment('tc.upload.'.$upload['id'], filePath: $upload['path'], fileName: $upload['description'], entity: $upload['object']))
					->groups('Dokumente der Schule');
			}
		}

		return collect($attachments)->values();
	}
}