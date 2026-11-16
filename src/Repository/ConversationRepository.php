<?php

namespace Base\Mailbox\Repository;

use Base\Database\Repository\Finder\Field;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Criterion;
use Base\Entity\User;
use Base\Database\Repository\ServiceEntityRepository;
use Base\Mailbox\Entity\Conversation;

/**
 * @method Conversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conversation|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Conversation[]    findAll()
 * @method Conversation[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class ConversationRepository extends ServiceEntityRepository
{
    public const BOX_INBOX = 'inbox';
    public const BOX_ARCHIVE = 'archive';
    public const BOX_SENT = 'sent';

    /** The order of a box: the latest message first. */
    public const BOX_ORDER = ['lastMessageAt' => 'DESC'];

    /**
     * The criteria of a member's box (Base\Service\PaginatorInterface::paginate() pages this repository with them, the
     * latest message first: self::BOX_ORDER): their participation row, not deleted, archived or not; the sent box, the
     * member wrote last.
     *
     * @return array<int|string, mixed>
     */
    public function boxCriteria(User $user, string $box = self::BOX_INBOX): array
    {
        $criteria = self::mine($user);
        $criteria[] = Group::of(Parser::SEPARATOR_AND, Criterion::of('participants.archivedAt', null, self::BOX_ARCHIVE === $box ? Parser::OPTION_NOT_NULL : Parser::OPTION_NULL));
        if (self::BOX_SENT === $box) {
            $criteria['lastSender'] = Key::of($user);
        }

        return $criteria;
    }

    /** How many conversations sit in the member's inbox (the quota). */
    public function countInbox(User $user): int
    {
        return $this->count([...self::mine($user), Group::of(Parser::SEPARATOR_AND, Criterion::of('participants.archivedAt', null, Parser::OPTION_NULL))]);
    }

    /** Conversations with something the member has not read yet. */
    public function countUnread(User $user): int
    {
        // Counted on the member's participations (one per conversation): not deleted, someone else wrote last, after
        // the member last read it (or never read).
        return (new ParticipantRepository($this->finders()))->count([
            'user' => Key::of($user),
            Group::of(Parser::SEPARATOR_AND, Criterion::of('deletedAt', null, Parser::OPTION_NULL)),
            Group::of(Parser::SEPARATOR_OR, Criterion::of('conversation.lastSender', null, Parser::OPTION_NULL), Criterion::of('conversation.lastSender', $user, Parser::OPTION_NOT_EQUAL)),
            Group::of(Parser::SEPARATOR_OR, Criterion::of('lastReadAt', null, Parser::OPTION_NULL), Criterion::of('lastReadAt', Field::of('conversation.lastMessageAt'), Parser::OPTION_LOWER)),
        ]);
    }

    /** A conversation the member takes part in (and has not deleted), fully loaded. */
    public function findOneForUser(int $id, User $user): ?Conversation
    {
        // The participants, their users, the messages and their senders were fetch-joined; they load lazily now
        //, the conversation found the same.
        return $this->findOneBy(['id' => $id] + self::mine($user));
    }

    /**
     * The criteria of the member's own participation, not deleted: one join of the participants, both members on
     * the same row, as `JOIN c.participants me WITH me.user = :user ... me.deletedAt IS NULL` said.
     *
     * @return array<int|string, mixed>
     */
    private static function mine(User $user): array
    {
        return ['participants.user' => $user, Group::of(Parser::SEPARATOR_AND, Criterion::of('participants.deletedAt', null, Parser::OPTION_NULL))];
    }
}
