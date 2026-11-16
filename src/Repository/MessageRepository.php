<?php

namespace Base\Mailbox\Repository;

use Base\Database\Repository\Finder\Key;
use Base\Entity\User;
use Base\Database\Repository\ServiceEntityRepository;
use Base\Mailbox\Entity\Message;

/**
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    /** The member's latest message, whatever the conversation - the flood check. */
    public function findLastBySender(User $sender): ?Message
    {
        return $this->findOneBy(['sender' => Key::of($sender)], ['createdAt' => \SortDirection::Descending, 'id' => \SortDirection::Descending]);
    }
}
