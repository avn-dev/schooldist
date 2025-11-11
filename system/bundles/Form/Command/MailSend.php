<?php

namespace Form\Command;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Console\Command;

class MailSend extends AbstractCommand {

    protected function configure() {   

        $this->setName("form:mail:send")
             ->setDescription("Send all outstanding e-mails");

    }

	/**
	 * Sendet alle E-Mails die über ein Formular geschickt wurden
	 * 
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
    protected function execute(InputInterface $input, OutputInterface $output): int
	{
		
		$aInfo = [
			'success' => 0,
			'failed' => 0
		];
		
		$aEmails = \Form\Entity\Mail::getRepository()->findBy(['status' => 0], 10);
		
		$aIds = array_column($aEmails, 'id');
		
		\DB::executePreparedQuery("UPDATE `form_mailing` SET `status` = 1 WHERE `id` IN (:mail_ids)", ['mail_ids' => $aIds]);
		
		$output->writeln('Sending '.count($aIds).' mails...');
		
		foreach($aEmails as $oEmail) {
			
			$oWDMail = new \WDMail();

			$oWDMail->subject = $oEmail->subject;

			if(!empty($oEmail->cc)) {
				$oWDMail->cc = $oEmail->cc;
			}
			
			if(!empty($oEmail->bcc)) {
				$oWDMail->bcc = $oEmail->bcc;
			}
			
			if(!empty($oEmail->mail_from)) {
				$oWDMail->from = $oEmail->mail_from;
			}
			
			if(!empty($oEmail->reply_to)) {
				$oWDMail->replyto = $oEmail->reply_to;
			}
			
			if(!empty($oEmail->attachments)) {
				$oWDMail->attachments = $oEmail->getAttachments();
			}
			
			if($oEmail->isHtml()) {
				$oWDMail->html = $oEmail->content;
			} else {
				$oWDMail->text = $oEmail->content;
			}

			$bResult = $oWDMail->send($oEmail->mail_to);
			
			if($bResult !== true) {
				$output->writeln('Mail "ID:'.$oEmail->getId().'" failed');
				// failed
				\DB::updateData('form_mailing', ['status' => 2], ['id' => $oEmail->getId()]);
				$aInfo['failed']++;
			} else {
				$output->writeln('Mail "ID:'.$oEmail->getId().'" send');
				$oEmail->delete();
				$aInfo['success']++;
			}
			
		}
		
		$log = \Log::getLogger('form');
		$log->info('Mail send', $aInfo);
		
		$output->writeln('done');
		
		return Command::SUCCESS;
    }
	
}