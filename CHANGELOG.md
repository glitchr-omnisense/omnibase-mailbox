# Changelog

## [Unreleased]

## [1.0.1] — 2026-11-14

### Changed
- The current names of what moved to omnibridge/symfony, in place of the former ones the core keeps as aliases only
  until 6.0: `Base\BaseBundle` → `Omnibridge\Symfony\Omnibase\BaseBundle`;
  `Base\Bundle\AbstractBaseBundle` → `Omnibridge\Symfony\Omnibase\Bundle\AbstractBaseBundle`;
  `Base\Bundle\AbstractBaseConfiguration` → `Omnibridge\Symfony\Omnibase\Bundle\AbstractBaseConfiguration`;
  `Base\Bundle\AbstractBaseExtension` → `Omnibridge\Symfony\Omnibase\Bundle\AbstractBaseExtension`.
  No behaviour change; the bundle no longer needs any alias of the core's `former-names*.php`.

## [1.0.0] — 2026-11-13

### Changed
- First stable release: installs with the default minimum-stability. Requires omnisense/omnibase `^5.1` (no longer
  the 3.0, 4.0 or 5.0 development branches), omnibridge/symfony `^1.1`, without `@dev`; `minimum-stability: dev` removed;
  branch alias `dev-main` = `1.x-dev`.
- CI: glitchr-studio/ci's package workflow (validate, PHPUnit, PHPStan level 5 with a baseline, a consumer install)
  and release workflow on a `v*` tag.
- README: the install line, how to enable the bundle under Symfony, a first use.

### Fixed
- Requires doctrine/doctrine-bundle and omnibridge/doctrine, whose classes `src/` uses: they came only with a host
  application (omnibridge/symfony suggests them), so the bundle installed alone failed to load its repositories.
- The compose form refuses a subject longer than `mailbox.subject_max_length`, and only then:
  `ComposeModel` capped it at 55 whatever the option, so a site that raised it still had longer
  subjects refused. The constraint is the form's (`ComposeType`'s `subject_max_length` option).
- README: omnisense/omnibase on GitHub in place of base-bundle on the abandoned gitlab.glitchr.dev;
  the demo image named omnibase-mailbox-demo.
