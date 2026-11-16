<?php

namespace Base\Mailbox\Extension\Repository;

use Base\Database\Repository\Finder\Field;
use Base\Mailbox\Entity\Conversation;
use Base\Mailbox\Extension\Entity\DeskConversation;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method DeskConversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeskConversation|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method DeskConversation[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class DeskConversationRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, DeskConversation::class);
    }

    public function findOneByConversation(Conversation $conversation): ?DeskConversation
    {
        return $this->findOneBy(['conversation' => $conversation]);
    }

    /**
     * @param list<string> $desks
     *
     * @return list<int> the conversations written to those desks (a desk holds few): what the desk box pages, as the
     *                   criterion `'id' => ...` of the conversations (Conversation has no association to its desk row)
     */
    public function conversationIds(array $desks): array
    {
        return array_values(array_unique(array_map(static fn (DeskConversation $row): int => (int) $row->getConversation()->getId(), $desks ? $this->findBy(['desk' => $desks]) : [])));
    }

    /** @param list<string> $desks how many wait for an answer: their author wrote last. */
    public function countWaiting(array $desks): int
    {
        return [] === $desks ? 0 : $this->count(['desk' => $desks, 'conversation.lastSender' => Field::of('author')]);
    }

    /**
     * @param list<string> $desks
     *
     * @return list<DeskConversation>
     */
    public function findWaiting(array $desks, int $limit = 5): array
    {
        return [] === $desks ? [] : $this->findBy(['desk' => $desks, 'conversation.lastSender' => Field::of('author')], ['conversation.lastMessageAt' => 'ASC'], $limit);
    }
}
