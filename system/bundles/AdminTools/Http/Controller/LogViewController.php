<?php

namespace AdminTools\Http\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class LogViewController extends \Illuminate\Routing\Controller
{
	public function index()
	{
		$fileOptions = $this->getFiles()
			->unique('namespace')
			->sortBy('namespace')
			->map(fn (array $file) => ['value' => $file['namespace'], 'text' => $file['namespace']])
			->prepend(['value' => '', 'text' => 'Bitte wählen']);

		return Inertia::render('Logs', compact('fileOptions'));
	}

	public function loadLog(Request $request)
	{
		$inputFile = $request->input('file');
		$offset = (int)$request->input('offset');
		$limit = (int)$request->input('limit');

		$files = $this->getFiles()->filter(function (array $file) use ($inputFile, $request) {
			if ($file['namespace'] !== $inputFile) {
				return false;
			}
			if (
				$request->filled('from') &&
				Carbon::parse($request->input('from'))->gt($file['date'])
			) {
				return false;
			}
			if (
				$request->filled('until') &&
				Carbon::parse($request->input('until'))->lt($file['date'])
			) {
				return false;
			}
			return true;
		});

		$maxLines = 1000;
		$lines = [];

		if ($offset > 0) {
			$maxLines = $offset + $limit;
		}

		foreach ($files as $file) {

			$command = 'cat '.$file['path'].' | tail -n '.$maxLines;
			if (str_ends_with($file['path'], '.zip')) {
				$command = 'z'.$command; // zcat
			}

			$result = shell_exec($command);

			if ($result === null) {
				continue;
			}

			$fileLines = array_reverse(explode("\n", trim($result)));

			$maxLines -= count($fileLines);
			$lines = array_merge($lines, $fileLines);

			if ($maxLines <= 0) {
				break;
			}

		}

		if ($offset > 0) {
			$lines = array_slice($lines, $offset, $limit);
		}

		$parsed = array_map(function (string $line, int $index) use ($inputFile, $maxLines) {

			// https://github.com/ddtraceweb/monolog-parser/pull/6/commits/2d74e556b921fedc152fab83f2adf2de7f8ae630#diff-324d39f9965bb48cbf803e2def70b900d984279bc89ee208786058d7237e92a4R24
			preg_match('/\[(?P<date>.*)\]\s(?P<logger>[\w-]+)\.(?P<level>\w+):\s(?P<message>[^\[\{]+)\s(?P<context>[\[\{].*[\]\}])\s(?P<extra>[\[\{].*[\]\}])/', $line, $matches);

			// [ in message funktioniert nicht
			if (empty($matches)) {
				$matches['message'] = 'COULD NOT PARSE LINE: '.$line;
			}

			return [
				'key' => $inputFile.'_'.($index + $maxLines), // Notwendig für Vue v-for
				'date' => $matches['date'],
				'logger' => $matches['logger'],
				'level' => $matches['level'],
				'message' => $matches['message'],
				'context' => $matches['context'] !== '[]' ? $matches['context'] : null,
//				'extra' => $matches['extra']
			];

		}, $lines, array_keys($lines));

		return response([
			'lines' => $parsed
		]);

	}

	private function getFiles(): Collection
	{
		$logDir = \Util::getDocumentRoot().'storage/logs';
		$iterator = new \RecursiveDirectoryIterator($logDir);
		$files = collect();

		foreach ($iterator as $file) {

			if ($file->isDir()) {
				continue;
			}

			preg_match('/^([a-z-_]+)-([0-9-]{10})\.log/i', $file->getFilename(), $matches);

			if (empty($matches[1])) {
				continue;
			}

			$files[] = [
				'namespace' => $matches[1],
				'date' => Carbon::parse($matches[2]),
				'file' => $file->getFilename(),
				'path' => $file->getPathname()
			];

		}

		$files = $files->sort(function (array $file1, array $file2) {
			return $file1['date'] < $file2['date'];
		});

		return $files->values();
	}
}
