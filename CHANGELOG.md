# Changelog

## 2.0.1

- omnibridge/doctrine `^3.0|^4.0`: the bundle runs on omnibridge/doctrine 4.0 (dates read as `\DateTimeImmutable` in UTC) as on 3.x.

## 2.0.0

omnibase/mailbox is private messaging for omnisense/omnibase: member-to-member conversations with an inbox, sent and
archive boxes, per-member read and delete state, a box quota and flood control, optional desks and attachments kept in
omnibase/office's vault. It names only `Base\` classes: its repositories extend
`Base\Database\Repository\ServiceEntityRepository` and speak the core's finder language, its services write through
`Base\Contract\UnitOfWork` and read other classes through `Base\Database\Repository\Repositories`; a member's box is
criteria the core's `Paginator` pages. Each query is written once in the finder language, the counts comparing two
fields included (`Field::of()`), and compiled by the engine of the persistence chosen (omnibridge/doctrine or
omnibridge/eloquent).
