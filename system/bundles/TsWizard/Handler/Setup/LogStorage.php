<?php

namespace TsWizard\Handler\Setup;

use Illuminate\Support\Arr;
use Tc\Interfaces\Wizard\Log as LogInterface;
use Tc\Service\Wizard\Iteration;
use Tc\Service\Wizard\Structure\Step;

class LogStorage implements \Tc\Interfaces\Wizard\LogStorage
{
	private array $saved = [];

	public function __construct() {
		$this->saved = $this->read();
	}

	public function getLogs(\User $user = null): array {
		return array_map(fn ($log) => new Log($log), $this->saved);
	}

	public function getLastLog(\User $user = null): ?LogInterface
	{
		if (empty($this->saved)) {
			return null;
		}

		$last = Arr::last($this->saved);

		return new Log($last);
	}

	public function writeLog(Iteration $iteration, Step $step, LogInterface $log = null): LogInterface
	{
		if ($log !== null) {
			$all = array_map(fn($log) => $log['id'], $this->saved);
			$index = array_search($log->getId(), $all);
			if ($index !== false) {
				$this->saved[$index] = $log->toArray();
			}
		} else {
			$log = Log::generate($iteration, $step);
			$this->saved[] = $log->toArray();
		}

		$this->write($this->saved);

		return $log;
	}

	public function removeLogs(\User $user = null, LogInterface $specific = null): static
	{
		if ($specific === null) {
			return $this->write([]);
		}

		$logs = array_filter($this->saved, fn (array $array) => $array['id'] !== $specific->getId());

		return $this->write($logs);
	}

	protected function read(): array
	{
		$saved = [];
		if (file_exists($file = $this->getFilePath())) {
			$content = file_get_contents($file);
			if (!is_array($saved = json_decode($content, true))) {
				throw new \RuntimeException('Unable to load saved wizard steps');
			}
		}

		return $saved;
	}

	protected function write(array $logs): static
	{
		file_put_contents($this->getFilePath(), json_encode($logs));
		return $this;
	}

	private function getFilePath(): string
	{
		$dir = storage_path('ts/wizard');
		if (!is_dir($dir)) {
			\Util::checkDir($dir);
		}
		return $dir.'/setup.txt';
	}

}