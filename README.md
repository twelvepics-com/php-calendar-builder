# PHP Calendar Builder

[![Release](https://img.shields.io/github/v/release/twelvepics-com/php-calendar-builder)](https://github.com/twelvepics-com/php-calendar-builder/releases)
[![](https://img.shields.io/github/release-date/twelvepics-com/php-calendar-builder)](https://github.com/twelvepics-com/php-calendar-builder/releases)
![](https://img.shields.io/github/repo-size/twelvepics-com/php-calendar-builder.svg)
[![PHP](https://img.shields.io/badge/PHP-^8.2-777bb3.svg?logo=php&logoColor=white&labelColor=555555&style=flat)](https://www.php.net/supported-versions.php)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-777bb3.svg?style=flat)](https://phpstan.org/user-guide/rule-levels)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-Unit%20Tests-6b9bd2.svg?style=flat)](https://phpunit.de)
[![PHPCS](https://img.shields.io/badge/PHPCS-PSR12-416d4e.svg?style=flat)](https://www.php-fig.org/psr/psr-12/)
[![PHPMD](https://img.shields.io/badge/PHPMD-ALL-364a83.svg?style=flat)](https://github.com/phpmd/phpmd)
[![Rector - Instant Upgrades and Automated Refactoring](https://img.shields.io/badge/Rector-PHP%208.2-73a165.svg?style=flat)](https://github.com/rectorphp/rector)
[![LICENSE](https://img.shields.io/github/license/ixnode/php-api-version-bundle)](https://github.com/ixnode/php-api-version-bundle/blob/master/LICENSE)

> This project provides a Calendar Builder API.

## Installation

```bash
git clone https://github.com/bjoern-hempel/php-calendar-builder.git && cd php-calendar-builder
```

```bash
docker compose up -d
```

```bash
docker compose exec php composer install
```

```bash
bin/console doctrine:migrations:migrate  --no-interaction
```

Open the project in your browser:

* https://www.calendar-builder.localhost/
* https://www.calendar-builder.localhost/api/v1
* https://www.calendar-builder.localhost/api/v1/version.json

> Hint: If you want to use real urls instead of using port numbers,
> try to use https://github.com/bjoern-hempel/local-traefik-proxy

## Test command

* PHPCS - PHP Coding Standards Fixer
* PHPMND - PHP Magic Number Detector
* PHPStan - PHP Static Analysis Tool
* PHPUnit - The PHP Testing Framework
* Rector - Instant Upgrades and Automated Refactoring

Execute them all:

```bash
composer test:hardcore
```