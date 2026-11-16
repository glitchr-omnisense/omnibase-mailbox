<?php

namespace Base\Mailbox\Extension\Repository;

use Base\Mailbox\Entity\Conversation;
use Base\Mailbox\Extension\Entity\Attachment;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method Attachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Attachment|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Attachment[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class AttachmentRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Attachment::class);
    }

    /** @return array<int, list<Attachment>> a conversation's attachments, by message id */
    public function findByConversation(Conversation $conversation): array
    {
        $byMessage = [];
        foreach ($this->findBy(['message.conversation' => $conversation], ['id' => 'ASC']) as $attachment) {
            $byMessage[$attachment->getMessage()->getId()][] = $attachment;
        }

        return $byMessage;
    }
}
