# PR quality gates

CI on pull requests fails when PHP line coverage drops below a floor, or when PHPMD codesize rules fire. PHPCS and PHPStan still fail the job as before; they now also annotate the Files tab. There is no Codecov or SonarCloud account.

Thresholds live in [`.github/quality-thresholds.json`](../.github/quality-thresholds.json). The same numbers are copied into [`phpmd.xml.dist`](../phpmd.xml.dist).

| Gate | Tool | Where |
| --- | --- | --- |
| Line coverage floor | PHPUnit Clover + [`bin/check-coverage.php`](../bin/check-coverage.php) | `phpunit` job (`pcov`) |
| Cyclomatic / NPath / method length | PHPMD codesize | `php-analysis` job |
| Style | PHPCS (checkstyle → `cs2pr`) | `php-analysis` job |
| Types | PHPStan `--error-format=github` | `php-analysis` job |

A sticky PR comment (`pr-quality-comment` job) repeats coverage % and PHPMD hits. HTML coverage is the **coverage-html** workflow artifact. Push to `main` still runs the gates; it does not post a comment.

Coverage includes plugin and theme PHP under `inc/`, `functions.php`, and similar. It excludes `build/`, `node_modules/`, `languages/`, Gutenberg `src/` render templates (they echo HTML when PHPUnit includes uncovered files), theme `patterns/`, `template-parts/`, plugin `views/`, and `generate-placeholders.php`.

## Local

```bash
ddev phpcs
ddev phpstan
ddev phpmd
ddev phpunit
```

Coverage is optional on DDEV. Default PHP has no `pcov`/`xdebug`, so `ddev phpunit` skips the report. To generate Clover/HTML under `coverage/` (gitignored):

```bash
ddev xdebug on
ddev exec bash -c 'XDEBUG_MODE=coverage ./vendor/bin/phpunit'
ddev xdebug off
ddev exec php bin/check-coverage.php
```

`coverage/` and `phpmd-report.xml` are not in the theme/plugin zip.

## Changing the floor

Measure first (`XDEBUG_MODE=coverage` PHPUnit, `ddev phpmd`), then edit `.github/quality-thresholds.json` and the matching PHPMD properties. Do not raise cyclomatic by ignoring new methods; lower it only after the worst methods shrink.
