<?php namespace Ewll\MailerBundle;

use Ewll\MailerBundle\Entity\Letter;
use Ewll\MailerBundle\Exception\CannotSendLetterException;
use Ewll\MysqlMessageBrokerBundle\AbstractDaemon;
use Ewll\MysqlMessageBrokerBundle\MessageBroker;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendLetterDaemon extends AbstractDaemon
{
    private const SENDING_MAIL_DELAY_SEC = 600;

    private $messageBroker;
    private $mailer;

    public function __construct(
        Logger $logger,
        MessageBroker $messageBroker,
        Mailer $mailer
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->messageBroker = $messageBroker;
        $this->mailer = $mailer;
    }

    protected function do(InputInterface $input, OutputInterface $output)
    {
        $message = $this->messageBroker->getMessage(Mailer::QUEUE_NAME);
        $letterId = $message['letterId'];
        $this->logger->info("Sending letter #{$letterId}");
        /** @var Letter $letter */
        $letter = $this->repositoryProvider->get(Letter::class)->findById($letterId);
        if ($letter->statusId === Letter::STATUS_ID_NEW) {
            try {
                $this->mailer->send($letter);
                $letter->statusId = Letter::STATUS_ID_SENT;
                $this->repositoryProvider->get(Letter::class)->update($letter, ['statusId']);
                $this->logger->info("Letter #{$letter->id} sent");
            } catch (CannotSendLetterException $e) {
                $try = $message['try'];
                if ($try <= 3) {
                    $try++;
                    $this->mailer->toQueue(
                        $letter->id,
                        $try,
                        self::SENDING_MAIL_DELAY_SEC
                    );
                    $this->logger->error("Cannot send letter #{$letter->id}. {$e->getMessage()}", $e->getData());
                } else {
                    $letter->statusId = Letter::STATUS_ID_ERROR;
                    $this->repositoryProvider->get(Letter::class)->update($letter, ['statusId']);
                    $this->logger->critical(
                        "Cannot send letter #{$letter->id} max attempts reached. {$e->getMessage()}",
                        $e->getData()
                    );
                }
            }
        } else {
            $this->logger->error(" Letter #{$letter->id} does not expect to send");
        }
    }
}
