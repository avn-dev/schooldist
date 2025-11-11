<?php

namespace Admin\Commands;

use Admin\Dto\Component\VueComponentDto;
use Admin\Instance;
use Core\Command\AbstractCommand;
use Illuminate\Http\Request;
use Symfony\Component\Console\Command\Command;

class Build extends AbstractCommand
{
    protected function configure()
	{
        $this->setName("admin:build")
			->setDescription("Build admin components file");
    }

	public function handle(Instance $admin)
	{
		$this->laravel->instance(\Access_Backend::class, new \Access_Backend(\DB::getDefaultConnection()));
		$this->laravel->instance('request', new Request());

		$admin->boot();

		$buildFile = realpath(base_path('/system/bundles/Admin/Resources/js/build.ts'));

		if (!file_exists($buildFile)) {
			$this->components->error(sprintf('Missing build file in %s', $buildFile));
			return Command::FAILURE;
		}

		$vueComponents = $admin->getComponents(Instance::COMPONENT_VUE)
			->map(fn ($component) => $component::getVueComponent($admin));

		$content = "";
		//$content .= "import { defineAsyncComponent } from 'vue'";
		$content .= "import { Registrar } from './types/backend/app'";
		$content .= "\r\n";

		$added = [];
		foreach ($vueComponents as $vue) {
			if (!isset($added[$vue->getName()])) {
				$content .= sprintf('import %s from "%s"', $vue->getName(), $vue->getFilePath());
				$content .= "\r\n";
				$added[$vue->getName()] = $vue->getFilePath();
			}
		}

		$content .= "\r\n";
		$content .= "const registrar: Registrar = new Registrar()";
		$content .= "\r\n";

		if (!empty($added)) {
			$content .= 'registrar.booting((app) => {'."\r\n";
			foreach (array_keys($added) as $vueName) {
				/* @var VueComponentDto $vue */
				// TODO defineAsyncComponent
				$content .= "\t".sprintf('app.component("%s", %s)', $vueName, $vueName)."\r\n";
				//$content .= "\t".sprintf('app.component("%s", defineAsyncComponent(() => import("%s"))', $vue->getName(), $vue->getFilePath())."\r\n";
			}
			$content .= '})'."\r\n";
		}

		$content .= "\r\n";

		$content .= "export default registrar";

		if (file_put_contents($buildFile, $content) === false) {
			$this->components->error('Updating @Admin/build.ts failed');
			return Command::FAILURE;
		}

		$this->components->info(sprintf('Updated @Admin/build.ts with %d components', $vueComponents->count()));

		return Command::SUCCESS;
    }
}