<?php

namespace TsAccommodation\Communication\Traits\Application;

use Communication\Dto\Message\Attachment;
use Communication\Interfaces\Model\HasCommunication;
use Communication\Services\AddressBook\AddressBookContact;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;

trait WithAccommodationPayload
{
	protected function buildAccommodationRecipient(LanguageAbstract $l10n, HasCommunication $source, \Ext_Thebing_Accommodation $accommodation): AddressBookContact
	{
		return (new AddressBookContact('accommodation.'.$accommodation->id, $accommodation))
			->groups($l10n->translate('Unterkunftsanbieter'))
			->recipients('accommodation_provider')
			->source($source);
	}

	protected function withAccommodationUploads(LanguageAbstract $l10n, HasCommunication $source, \Ext_Thebing_Accommodation $accommodation, string $language): Collection
	{
		$uploads = $accommodation->getUploadedPDFs($language, false);

		$attachments = new Collection();
		foreach ($uploads as $upload) {
			/* @var \Ext_Thebing_Accommodation_Upload $upload */
			if (!file_exists($path = $upload->getPath())) {
				continue;
			}

			$attachments->push(
				(new Attachment('ts.accommodation.upload.'.$upload->id, filePath: $path, fileName: $upload->description, entity: $upload))
					->source($source)
			);
		}

		return $attachments;
	}
}