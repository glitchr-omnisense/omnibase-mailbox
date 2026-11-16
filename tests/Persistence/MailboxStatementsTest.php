<?php

namespace Tests\Base\Mailbox\Persistence;

use Base\Entity\User;
use Base\Mailbox\Entity\Conversation;
use Base\Mailbox\Entity\Message;
use Base\Mailbox\Entity\Participant;
use Base\Mailbox\Extension\Entity\DeskConversation;
use Base\Mailbox\Extension\Repository\DeskConversationRepository;
use Base\Mailbox\Repository\ConversationRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Base\Testing\Engines;
use PHPUnit\Framework\TestCase;

/** The unread count and the desks' waiting conversations: the same rows on Doctrine and on Eloquent. */
final class MailboxStatementsTest extends TestCase
{
    private static Engines $engines;

    /** @return array<string, array{0: string}> */
    public static function backends(): array
    {
        return Engines::backends();
    }

    private const ME = 7;
    private const OTHER = 8;

    /** @var array<string, int> */
    private static array $ids = [];

    public static function setUpBeforeClass(): void
    {
        self::$engines = Engines::of([Conversation::class, Participant::class, Message::class, DeskConversation::class]);
        $conversation = static fn (string $subject, string $last, ?int $lastSender): int => self::$engines->insert('mailbox_conversation', ['subject' => $subject, 'createdAt' => '2026-01-01 10:00:00', 'lastMessageAt' => $last, 'messageCount' => 1, 'lastSender_id' => $lastSender]);
        $participant = static fn (int $conversation, int $user, ?string $read, ?string $deleted = null): int => self::$engines->insert('mailbox_participant', ['starred' => 0, 'conversation_id' => $conversation, 'user_id' => $user, 'lastReadAt' => $read, 'deletedAt' => $deleted]);
        // unread: a message after my last reading, by the other one
        $one = $conversation('One', '2026-02-01 10:00:00', self::OTHER);
        $participant($one, self::ME, '2026-01-15 10:00:00');
        // never read, nobody wrote last
        $two = $conversation('Two', '2026-02-01 10:00:00', null);
        $participant($two, self::ME, null);
        // read since
        $three = $conversation('Three', '2026-02-01 10:00:00', self::OTHER);
        $participant($three, self::ME, '2026-03-01 10:00:00');
        // I wrote last
        $four = $conversation('Four', '2026-02-01 10:00:00', self::ME);
        $participant($four, self::ME, null);
        // deleted by me
        $five = $conversation('Five', '2026-02-01 10:00:00', self::OTHER);
        $participant($five, self::ME, null, '2026-02-02 10:00:00');
        // not mine
        $six = $conversation('Six', '2026-02-01 10:00:00', self::ME);
        $participant($six, self::OTHER, null);

        $desk = static fn (int $conversation, string $desk, ?int $author): int => self::$engines->insert('mailbox_desk_conversation', ['desk' => $desk, 'conversation_id' => $conversation, 'author_id' => $author]);
        $waitingEarly = $desk($one, 'accueil', self::OTHER);     // the author (OTHER) wrote last: waits
        $answered = $desk($four, 'accueil', self::OTHER);        // the desk (ME) wrote last
        $waitingLate = $desk($six, 'secretariat', self::ME);     // the author wrote last: waits
        self::$engines->insert('mailbox_conversation', ['subject' => 'x', 'createdAt' => '2026-01-01 10:00:00', 'lastMessageAt' => '2026-01-01 10:00:00', 'messageCount' => 0]);
        self::$engines->pdo()->exec("UPDATE mailbox_conversation SET lastMessageAt = '2026-01-20 10:00:00' WHERE id = $one");
        self::$ids = ['one' => $one, 'six' => $six, 'waitingEarly' => $waitingEarly, 'waitingLate' => $waitingLate, 'answered' => $answered];
    }

    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testUnread(string $backend): void
    {
        $conversations = new ConversationRepository(self::$engines->finders($backend));

        self::assertSame(2, $conversations->countUnread(self::stub(User::class, self::ME)), 'One (written after my reading) and Two (never read); not Three (read since), Four (mine last), Five (deleted), Six (not mine)');
    }

    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testWaitingAtTheDesks(string $backend): void
    {
        $desks = new DeskConversationRepository(self::$engines->finders($backend));

        self::assertSame(2, $desks->countWaiting(['accueil', 'secretariat']));
        self::assertSame(1, $desks->countWaiting(['accueil']));
        self::assertSame(0, $desks->countWaiting([]));
        self::assertSame([self::$ids['waitingEarly'], self::$ids['waitingLate']], array_map(static fn (DeskConversation $d): int => (int) $d->getId(), $desks->findWaiting(['accueil', 'secretariat'])), 'the oldest last message first');
        self::assertSame([self::$ids['waitingEarly']], array_map(static fn (DeskConversation $d): int => (int) $d->getId(), $desks->findWaiting(['accueil', 'secretariat'], 1)));
    }

    /** An entity of that id, never read: what a statement taking the entity is given. */
    private static function stub(string $class, int $id): object
    {
        $entity = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty($class, 'id'))->setValue($entity, $id);

        return $entity;
    }
}
