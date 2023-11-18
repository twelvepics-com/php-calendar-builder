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

Open the project in your browser:

* https://www.calendar-builder.localhost/
* https://www.calendar-builder.localhost/api/v1
* https://www.calendar-builder.localhost/api/v1/version.json

> Hint: If you want to use real urls instead of using port numbers,
> try to use https://github.com/bjoern-hempel/local-traefik-proxy

## Create your first calendar on command line

### Create new directory structure

```bash
bin/console calendar:new
```

```bash

→ Directory "data/calendar/2c93279fb467" was successfully created.
→ Got to this directory.
→ Add your own images.
→ Edit the "data/calendar/2c93279fb467/config.yml" config file to your needs.
→ Build your calendar with: bin/console calendar:build "data/calendar/2c93279fb467/config.yml"
→ The 13 calendar pages are then located here by default: "data/calendar/2c93279fb467/ready/*"
→ Enjoy

```

### Add your own images to the folder created above

There are already 13 sample images in the folder. Replace these with your own. Allowed are png and jpg images.

### Edit the config.yml file

Things that can be changed

* Design and design configurations
* Birthdays
* Holidays
* Title and subtitle of the main page
* Title of the monthly pages
* Positions/coordinates of the images
* Source and destination of the images
* Year and month of the monthly pages
* Output quality
* etc.

### Finally create calendar pages

```bash
bin/console calendar:build "data/calendar/2c93279fb467/config.yml"
```

or if want to execute the command within the running docker container:

```bash
docker compose exec -u www-data php bin/console calendar:build data/calendar/2c93279fb467/config.yml
```

### Check the calendar

```bash
ls data/calendar/2c93279fb467/ready
```

```bash
2024-00.jpg  2024-01.jpg  2024-02.jpg  2024-03.jpg 
2024-04.jpg  2024-05.jpg  2024-06.jpg  2024-07.jpg 
2024-08.jpg  2024-09.jpg  2024-10.jpg  2024-11.jpg 
2024-12.jpg
```

Example images can be found here: [Example Images](./data/examples/simple/ready)

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