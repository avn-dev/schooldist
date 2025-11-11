<?php

namespace Cms\Command;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateStats extends AbstractCommand {

	protected function configure() {
		$this
			->setName('cms:stats:generate')
			->setDescription('Generate CMS statistics');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$oLog = \Log::getLogger('cms', 'statistics');
		
		$this->_setDebug($output);

		$oLog->info('Clean bots');
		
		\System::setLocale();
		
		// Delete Bots
		$db = \DB::getDefaultConnection();
		$parseAgents = $db->getCollection("SELECT `id`, `agent` FROM `cms_stats` WHERE `agent_browser` IS NULL ORDER BY `id` DESC LIMIT 10000");
		foreach($parseAgents as $stats) {
			
			$agent = new \Jenssegers\Agent\Agent;
			$agent->setUserAgent($stats['agent']);

			if(
				empty($stats['agent']) || 
				$agent->isRobot()
			) {
				$db->executePreparedQuery("DELETE FROM `cms_stats` WHERE `id` = :id", ['id'=>$stats['id']]);
			} else {
				$db->executePreparedQuery("UPDATE `cms_stats` SET `time` = `time`, `agent_browser` = :agent_browser, `agent_version` = :agent_version, `agent_os` = :agent_os WHERE `id` = :id", [
					'agent_browser' => $agent->browser(),
					'agent_version' => $agent->version($agent->browser()),
					'agent_os' => $agent->platform()." ".$agent->version($agent->platform()),
					'id'=>$stats['id']
				]);
			}

		}
		
		#DELETE FROM `cms_stats` WHERE `agent` LIKE '%bot%' 
		
		$oLog->info('Start generating');
		
		$sites = \Cms\Entity\Site::getRepository()->findAll();
		
		foreach($sites as $site) {
			
			$oLog->info('Generating '.$site->name);
			
			$generator = new \Cms\Generator\Stats($site);
			$generator->generate();

		}

		$oLog->info('Generating done');
		$output->writeln('Done');

		return Command::SUCCESS;
	}

}
