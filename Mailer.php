<?php namespace Ewll\MailerBundle;

use Ewll\MailerBundle\Entity\Letter;
use Ewll\MailerBundle\Exception\CannotSendLetterException;
use Ewll\UserBundle\Entity\User;
use Ewll\MysqlMessageBrokerBundle\MessageBroker;
use Ewll\DBBundle\Repository\RepositoryProvider;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class Mailer
{
    const QUEUE_NAME = 'letter';

    private $messageBroker;
    private $phpMailer;
    private $repositoryProvider;
    private $translator;
    private $templating;
    private $logger;

    public function __construct(
        MessageBroker $messageBroker,
        RepositoryProvider $repositoryProvider,
        TranslatorInterface $translator,
        Environment $templating,
        Logger $logger,
        string $mailerHost,
        int $mailerPort,
        string $mailerSecure,
        string $mailerUser,
        string $mailerPass,
        bool $mailerSmtpAuth,
        string $mailerSenderEmail,
        string $mailerSenderName
    ) {
        $this->messageBroker = $messageBroker;
        $this->repositoryProvider = $repositoryProvider;
        $this->translator = $translator;
        $this->templating = $templating;
        $this->phpMailer = new PHPMailer();
        $this->logger = $logger;
        $this->phpMailer->Host = $mailerHost;
        $this->phpMailer->Port = $mailerPort;
        $this->phpMailer->SMTPAuth = $mailerSmtpAuth;
        $this->phpMailer->Username = $mailerUser;
        $this->phpMailer->Password = $mailerPass;
        $this->phpMailer->Mailer = $mailerSmtpAuth ? 'smtp' : 'mail';
        $this->phpMailer->SMTPSecure = $mailerSecure;
        $this->phpMailer->From = $mailerSenderEmail;
        $this->phpMailer->FromName = $mailerSenderName;
        $this->phpMailer->ContentType = 'text/html';
        $this->phpMailer->CharSet = 'UTF-8';
        $this->phpMailer->Timeout = 5;
        $this->phpMailer->SMTPDebug = 4;
        $this->phpMailer->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
    }

    // Creates any letter for confirmed email and confirmation letter only for unconfirmed email
    public function createForUser(
        User $user,
        Template $template,
        bool $checkEmailConfirmation = true
    ): void {
        if ($checkEmailConfirmation && !$user->isEmailConfirmed) {
            $this->logger->critical(
                'Letter is not created! Only confirmation letters for unconfirmed emails are allowed'
            );

            return;
        }
        $letter = Letter::create(
            $user->id,
            $user->email,
            $this->translator->trans('subject', [], $template->getName()),
            $this->templating->render(
                "@{$template->getBundle()}/letter/{$template->getName()}.html.twig",
                $template->getData()
            )
        );
        $this->repositoryProvider->get(Letter::class)->create($letter);
        $this->toQueue($letter->id);
    }

    public function create(
        string $email,
        Template $template
    ): void {
        $letter = Letter::create(
            null,
            $email,
            $this->translator->trans('subject', [], $template->getName()),
            $this->templating->render(
                "@{$template->getBundle()}/letter/{$template->getName()}.html.twig",
                $template->getData()
            )
        );
        $this->repositoryProvider->get(Letter::class)->create($letter);
        $this->toQueue($letter->id);
    }

    public function toQueue(int $letterId, int $try = 1, int $delay = 5): void
    {
        $this->messageBroker->createMessage(self::QUEUE_NAME, [
            'letterId' => $letterId,
            'try' => $try,
        ], $delay);
    }

    /** @throws CannotSendLetterException */
    public function send(Letter $letter): void
    {
        $errorMessage = '';
        ob_start();
        $this->phpMailer->ClearAddresses();
        $this->phpMailer->ClearAttachments();
        $this->phpMailer->msgHTML($letter->body);
        $this->phpMailer->Subject = $letter->subject;
        $this->phpMailer->AddAddress($letter->email, '');

        try {
            $isSent = $this->phpMailer->send();
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $isSent = false;
        }
        $debug = ob_get_contents();
        ob_end_clean();

        if (!$isSent) {
            $debug = explode("\n", $debug);
            throw new CannotSendLetterException($errorMessage, $debug);
        }
    }
}
