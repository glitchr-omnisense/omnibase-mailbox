# Changelog

## [Unreleased]

### Changed
- Accepts omnisense/omnibase 5.0 (`3.0.*-dev || 4.0.*-dev || 5.0.*-dev`; plan "omnibase agnostic", step S): the bundle runs
  unchanged on it, `Base\Bundle\AbstractBaseBundle` and the other former names it uses being aliases of
  omnibridge/symfony's classes there. The sites on 4.x resolve as before.

### Fixed
- The compose form refuses a subject longer than `mailbox.subject_max_length`, and only then:
  `ComposeModel` capped it at 55 whatever the option, so a site that raised it still had longer
  subjects refused. The constraint is the form's (`ComposeType`'s `subject_max_length` option).
- README: omnisense/omnibase on GitHub in place of base-bundle on the abandoned gitlab.glitchr.dev;
  the demo image named omnibase-mailbox-demo.
