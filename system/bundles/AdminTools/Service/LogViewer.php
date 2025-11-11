<?php

namespace AdminTools\Service;

use AdminTools\Dto\Log;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Period\Period;
use Spatie\Period\Precision;

class LogViewer
{
	protected ?Period $period = null;

	protected array $files = [];

	protected array $levels = [];

	public function __construct() {}

	public function today(): static
	{
		return $this->period(Carbon::now()->startOfDay());
	}

	public function file(string $file): static
	{
		$this->files[] = $file;
		return $this;
	}

	public function period(\DateTimeInterface $from, \DateTimeInterface $until = null): static
	{
		if (!$until) {
			$until = Carbon::now();
		}

		$this->period = Period::make($from, $until, Precision::SECOND());
		return $this;
	}

	public function levels(array $levels): static
	{
		$this->levels = array_map(fn ($level) => strtoupper($level), $levels);
		return $this;
	}

	public function read(int $maxLines = 1000): Collection
	{
		$files = $this->getFiles();

		$lines = [];
		foreach ($files as $file) {

			$fileLines = $this->readFile($file['path'], $maxLines);

			$lines = array_merge($lines, array_map(fn ($line) => [$file['namespace'], $line], $fileLines));

			$maxLines -= count($fileLines);

			if ($maxLines <= 0) {
				break;
			}
		}

		$logs = array_map(function (array $row, int $index) use ($maxLines) {
			[$namespace, $line] = $row;
			return Log::fromString($line)->key($namespace.'_'.($index + $maxLines));
		}, $lines, array_keys($lines));

		if (!empty($this->levels)) {
			$logs = array_filter($logs, fn ($log) => in_array($log->getLevel(), $this->levels));
		}

		$logs = collect($logs)->sort(fn (Log $log1, Log $log2) => $log1->getDate() < $log2->getDate());

		return $logs;
	}

	public function getFiles(): Collection
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

			$date = Carbon::parse($matches[2]);

			if (
				$this->period && !$this->period->contains($date) ||
				!empty($this->files) && !in_array($matches[1], $this->files)
			) {
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

	protected function readFile(string $filePath, int $maxLines): array
	{
		$command = 'cat '.$filePath.' | tail -n '.$maxLines;
		if (str_ends_with($filePath, '.zip')) {
			$command = 'z'.$command; // zcat
		}

		$result = shell_exec($command);

		if ($result === null) {
			return [];
		}

		$fileLines = array_reverse(explode("\n", trim($result)));

		return $fileLines;
	}

}