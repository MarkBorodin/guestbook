<?php

namespace App\MessageHandler;

use App\ImageOptimizer;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Notifier\NotifierInterface;
use App\Notification\CommentReviewNotification;

class CommentMessageHandler implements MessageHandlerInterface
{
    private $spamChecker;
    private $entityManager;
    private $commentRepository;
    private $bus;
    private $workflow;
    private $logger;
    private $mailer;
    private $adminEmail;
    private $imageOptimizer;
    private $photoDir;
    private $notifier;


    public function __construct(EntityManagerInterface $entityManager, SpamChecker $spamChecker,
                                CommentRepository $commentRepository, MessageBusInterface $bus,
                                WorkflowInterface $commentStateMachine, MailerInterface $mailer,
                                ImageOptimizer $imageOptimizer, string $adminEmail, string $photoDir,
                                NotifierInterface $notifier, LoggerInterface $logger = null)
    {
        $this->entityManager = $entityManager;
        $this->spamChecker = $spamChecker;
        $this->commentRepository = $commentRepository;
        $this->bus = $bus;
        $this->workflow = $commentStateMachine;
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
        $this->imageOptimizer = $imageOptimizer;
        $this->photoDir = $photoDir;
        $this->notifier = $notifier;

    }

    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            return;
        }

        if ($this->workflow->can($comment, 'accept')) {
            $score = $this->spamChecker->getSpamScore($comment, $message->getContext());
            $transition = 'accept';
            if (2 === $score) {
                    $transition = 'reject_spam';
                }
            elseif (1 === $score) {
                    $transition = 'might_be_spam';
                }
            $this->workflow->apply($comment, $transition);
            $this->entityManager->flush();
            $this->bus->dispatch($message);

        }
        elseif ($this->workflow->can($comment, 'publish') || $this->workflow->can($comment, 'publish_ham')) {
            // send email:
//            $this->mailer->send((new NotificationEmail())
//                            ->subject('New comment posted')
//                            ->htmlTemplate('emails/comment_notification.html.twig')
//                            ->from($this->adminEmail)
//                            ->to($this->adminEmail)
//                            ->context(['comment' => $comment])
//                        );
            // or:
            $this->notifier->send(new CommentReviewNotification($comment), ...$this->notifier->getAdminRecipients());
        }
        elseif ($this->workflow->can($comment, 'optimize')) {
            if ($comment->getPhotoFilename()) {
                $this->imageOptimizer->resize($this->photoDir . '/' . $comment->getPhotoFilename());
            }
            $this->workflow->apply($comment, 'optimize');
            $this->entityManager->flush();
        }
        elseif ($this->logger) {
            $this->logger->debug('Dropping comment message', ['comment' => $comment->getId(), 'state' => $comment->getState()]);
        }
    }
}
