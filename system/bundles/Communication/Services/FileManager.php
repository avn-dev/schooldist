<?php

namespace Communication\Services;

use Communication\Dto\Message\Attachment;
use Communication\Helper\Collections\AttachmentsCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FileManager
{
	private ?\Ext_TC_Communication_Template $template = null;

	private $files = [];

	public function __construct(
		private readonly Communication $communication,
		private readonly string $channel
	) {}

	public function setTemplate(?\Ext_TC_Communication_Template $template): FileManager
	{
		$this->template = $template;
		return $this;
	}

	public function getFiles(string $language = null): array
	{
		$cacheKey = $language ?? 'all';

		if (isset($this->files[$cacheKey])) {
		//	return $this->files[$cacheKey];
		}

		$models = $this->communication->getBasedOnModels();

		$attachments = $this->communication->getApplication()
			->getAttachments($this->communication->l10n(), $models, $this->channel, $language);

		$selected = new AttachmentsCollection();

		if ($this->template && $language) {
			$templateContent = $this->template->getContentObjectByIso($language);

			if ($templateContent) {
				$manualUploads = $templateContent->getUploadFilePaths();

				foreach ($manualUploads as $filePath) {
					$dto = new Attachment('template::' . 'storage/' . Str::after($filePath, 'storage/'), $filePath, basename($filePath));
					$attachments->push($dto);

					$selected->push($dto);
				}

				$uploads = $templateContent->to_uploads;
				foreach ($uploads as $uploadId) {
					$upload = \Factory::getInstance(\Ext_TC_Upload::class, $uploadId);
					if ($upload->isActive() && file_exists($filePath = $upload->getPath(true))) {
						// Nicht "template::" weil die Uploads noch in der Application selber eingebunden werden können. Die
						// sollte es dann nicht doppelt geben
						$dto = new Attachment('tc.upload.' . $upload->id, filePath: $filePath, fileName: $upload->description, entity: $upload);
						if (!$this->hasFile($dto)) {
							$attachments->push($dto);
						}
						$selected->push($dto);
					}
				}
			}
		}

		//$this->files[$cacheKey] = [$attachments, $selected];

		return [$attachments, $selected];
	}

	public function hasFile(Attachment $attachment): bool
	{
		return $this->searchFile($attachment) !== null;
	}

	public function searchFile(Attachment $attachment): ?Attachment
	{
		[$files, ] = $this->getFiles();

		return $files->first(fn (Attachment $loop) => $loop->getKey() === $attachment->getKey());
	}

	public function search(string $path, string $language = null): ?Attachment
	{
		[$files, ] = $this->getFiles($language);

		$found = $files->first(fn (Attachment $attachment) => $attachment->getUrl() === '/storage/'.Str::after($path, 'storage/'));

		return $found;
	}
}