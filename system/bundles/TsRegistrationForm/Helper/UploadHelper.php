<?php

namespace TsRegistrationForm\Helper;

use Core\Factory\ValidatorFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use TsRegistrationForm\Generator\CombinationGenerator;

class UploadHelper {

	/**
	 * @var CombinationGenerator
	 */
	private $combination;

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var string
	 */
	private $tmpPath;

	public function __construct(CombinationGenerator $combination, Request $request) {
		$this->combination = $combination;
		$this->request = $request;
		$this->tmpPath = storage_path('tmp/ts_regform');
	}

	/**
	 * Upload aus Ajax-Request verarbeiten
	 *
	 * @return array
	 */
	public function handleUploadRequest(): array {

		$field = $this->request->input('name');

		\Util::checkDir($this->tmpPath);

		// Alte Datei löschen, wenn neue Datei hochgeladen wird
		$value = $this->request->input('value');
		if (
			$value &&
			preg_match('/^([a-z0-9]+\.[a-z]{3}):.+$/i', $value, $matches) &&
			file_exists($this->tmpPath . '/' . $matches[1])
		) {
			@unlink($this->tmpPath . '/' . $matches[1]);
			$this->combination->log('AJAX upload: Deleted upload: '.$this->tmpPath.'/'.$matches[1], [$field, $value, 'request' => $this->request->all()], false);
		}

		// Nur eine einzige Datei akzeptieren; löschen hierüber darf immer passieren
		$files = $this->request->files;
		if (count($files) !== 1) {
			if ($this->request->input('delete')) {
				return ['status' => 200];
			}
			$this->combination->log('AJAX upload: Wrong number of files: '.count($files), ['request' => $this->request->all()], true);
			return ['status' => 400, 'messages' => ['File missing or more than one file']];
		}

		$validator = $this->createUploadValidator($field);

		$file = $this->request->file($field);

		if ($validator->fails()) {
			$this->combination->log('AJAX upload: Rejected upload: '.($file ? $file->getClientOriginalName() : 'no valid file'), [$validator->failed() , 'request' => $this->request->all()], true);
			if ($file) {
				@unlink($file->getPath());
			}
			return ['status' => 400, 'messages' => $validator->messages()->all()];
		}

		if (
			!$file ||
			!$file->isValid()
		) {
			$this->combination->log('AJAX upload: Invalid upload: '.($file ? $file->getErrorMessage() : 'NULL'), ['request' => $this->request->all()], true);
			return ['status' => 400, 'messages' => ['File rejected']];
		}

		$ext = $file->guessExtension();
		$name = Str::random() . '.' . ($ext === 'jpeg' ? 'jpg' : $ext); // Niemand mag .jpeg (oben auch \.[a-z]{3})
		$value = $name . ':' . $file->getClientOriginalName(); // Client-Dateinamen mitschicken für Label

		// Alle Fehler dieser Methode sollten durch den Validator und $file->isValid() abgefangen sein
		$target = $this->tmpPath.'/'.$name;
		$file->move($this->tmpPath, $name);
		$this->combination->log('AJAX upload: Moved from php tmp to other tmp: '.$target, [$field, $value, 'is_file' => is_file($target), 'request' => $this->request->all()], false);

		return ['status' => 200, 'value' => $value];

	}

	/**
	 * Validator erzeugen für AJAX-Upload
	 *
	 * @param string $field
	 * @return Validator
	 */
	private function createUploadValidator(string $field): Validator {

		// Datei validieren
		$validator = (new ValidatorFactory())->make($this->request->all(), [
			$field => [
				'required',
				'file',
				'mimes:' . join(',', \Ext_Thebing_Form_Page_Block::VALIDATION_TYPE_UPLOAD_ALLOWED_EXTENSIONS), // Laravel überprüft den MIME-Typ
				'max:10240' // TODO Maximal 10 MB, passiert sonst nichts weiter mit
			]
		], [
			'file' => $this->combination->getForm()->getTranslation('errorinternal', $this->combination->getLanguage()),
			'max' => $this->combination->getForm()->getTranslation('extensionsize', $this->combination->getLanguage()),
			'mimes' => $this->combination->getForm()->getFileExtensionError($this->combination->getLanguage())
		]);

		// Prüfen, ob PHP (also die Software) auch etwas mit einem Bild anfangen kann
		$validator->after(function ($validator) use ($field) {
			$file = $this->request->file($field);
			if (
				$file instanceof \Illuminate\Http\UploadedFile &&
				Str::startsWith($file->getMimeType(), 'image/') &&
				!is_array(@getimagesize($file->getPathname()))
			) {
				// Sollte eigentlich nicht vorkommen
				$this->combination->log('Upload: File is not an image: '.$field, [$file->getPathname(), $this->request->all()]);
				$validator->errors()->add($field, 'File is not an image.');
			}
		});

		return $validator;

	}

	/**
	 * Uploads _speichern_
	 *
	 * @param \Ext_TS_Inquiry_Contact_Abstract $contact
	 */
	public function moveUploads(\Ext_TS_Inquiry_Contact_Abstract $contact) {

		$fields = $this->getUploadFields();
		foreach ($fields as $field) {
			if (!empty($field['path'])) {
				$type = Str::of($field['mapping'][1])->replace('upload_', '');
				$file = new \Symfony\Component\HttpFoundation\File\File($field['path']);
				$contact->saveUpload2($type, $file);
				$this->combination->log('Upload: Moved to contact: '.$field['path'], [$type, $this->request->all()], false);
			}
		}

	}

	/**
	 * Definiere Upload-Felder aus dem Form
	 *
	 * @return Collection
	 */
	private function getUploadFields(): Collection {

		$fields = $this->combination->getSchoolData()->get('fields')['fields']; // Anderer Key ist services
		return collect($fields)->filter(function ($field) {
			return $field['type'] === \Ext_Thebing_Form_Page_Block::TYPE_UPLOAD;
		})->map(function ($field) {
			$input = $this->request->input('fields.' . $field['key']);
			if (empty($input)) {
				return $field;
			}
			$data = explode(':', $input, 2);
			$field['file'] = $data[0];
			$field['client_name'] = $data[1];
			$field['path'] = $this->tmpPath . '/' . $data[0];
			return $field;
		});

	}

	/**
	 * Uploads prüfen, ob Datei auch wirklich existiert (sollte eigentlich nicht vorkommen, außer Form wurde schon submitted)
	 *
	 * @return \Closure
	 */
	public function createUploadValidatorHook(): \Closure {

		return function (Validator $validator) {
			foreach ($this->getUploadFields() as $field) {
				if (
					!empty($field['path']) &&
					!is_file($field['path'])
				) {
					$this->combination->log('Could not find upload in tmp dir, prevening submit: ' . $field['path'], [$this->request->all()]);
					$validator->errors()->add($field['key'], 'File could not be found on server: ' . $field['client_name']);
				}
			}
		};

	}

}