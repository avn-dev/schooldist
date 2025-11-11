<?php

namespace Elearning\Command;

use Core\Command\AbstractCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReminderEmails extends AbstractCommand {

    protected function configure() {   

        $this->setName("elearning:reminderemails")
             ->setDescription("Sends all overdue reminder e-mails.");

    }

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
    protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$this->_setDebug($output);
		
		ignore_user_abort(true);
		set_time_limit(7200);

		$aResult = array();
		
		$oLogger = \Log::getLogger('elearning_reminderemails');
		$oLogger->addInfo('Start');
		
		// Alle aktiven Tests durchgehen.
		$aExams = \Ext_Elearning_Exam::getList();
		foreach((array)$aExams as $aExam) {

			$oExam = new \Ext_Elearning_Exam($aExam['id']);

			$bIsActive = $oExam->isActiveAndRunning();
			
			$iReminderWeeks = $oExam->reminder_weeks;

			if(
				$bIsActive === true &&
				$iReminderWeeks > 0
			) {

				$aInfo = $oExam->sendInvitaitions('reminderemail', $output->isDebug());
				
				$aResult[$oExam->id] = array(
					'name' => $oExam->name,
					'info' => $aInfo
				);
				
				$oLogger->addInfo($oExam->name, (array)$aInfo);

			}

		}

		$oLogger->addInfo('End');
		
        $output->writeln(json_encode($aResult));
		return Command::SUCCESS;
    }

}