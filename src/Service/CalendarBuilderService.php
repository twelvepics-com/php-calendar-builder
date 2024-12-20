<?php

/*
 * This file is part of the twelvepics-com/php-calendar-builder project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Service;

use App\Calendar\ImageBuilder\Base\BaseImageBuilder;
use App\Constants\Service\Calendar\CalendarBuilderService as CalendarBuilderServiceConstants;
use App\Objects\Image\ImageContainer;
use App\Objects\Parameter\ParameterWrapper;
use App\Objects\Parameter\Source;
use App\Objects\Parameter\Target;
use DateTime;
use DateTimeImmutable;
use Exception;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class CalendarBuilderService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.1 (2023-11-09)
 * @since 0.1.0 (2023-11-09) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CalendarBuilderService
{
    private BaseImageBuilder $design;

    private ParameterWrapper $parameterWrapper;

    /** @var array<array{name: string[]}> $eventsAndHolidaysRaw */
    protected array $eventsAndHolidaysRaw = [];

    /** @var array<array{name: string}> $eventsAndHolidays */
    protected array $eventsAndHolidays = [];

    /** @var array<bool> $holidays */
    protected array $holidays = [];

    protected bool $deleteTargetImages = false;

    /**
     * @param KernelInterface $appKernel
     */
    public function __construct(protected KernelInterface $appKernel)
    {
    }

    /**
     * Returns the parameter wrapper.
     *
     * @return ParameterWrapper
     */
    public function getParameterWrapper(): ParameterWrapper
    {
        return $this->parameterWrapper;
    }

    /**
     * Sets the parameter wrapper.
     *
     * @param ParameterWrapper $parameterWrapper
     * @return self
     */
    public function setParameterWrapper(ParameterWrapper $parameterWrapper): self
    {
        $this->parameterWrapper = $parameterWrapper;

        return $this;
    }

    /**
     * Returns the source parameter.
     *
     * @return Source
     */
    public function getParameterSource(): Source
    {
        return $this->parameterWrapper->getSource();
    }

    /**
     * Returns the target parameter.
     *
     * @return Target
     */
    public function getParameterTarget(): Target
    {
        return $this->parameterWrapper->getTarget();
    }

    /**
     * @return array<array{name: string}>
     */
    public function getEventsAndHolidays(): array
    {
        return $this->eventsAndHolidays;
    }

    /**
     * @return array<bool>
     */
    public function getHolidays(): array
    {
        return $this->holidays;
    }

    /**
     * Init function.
     *
     * @param ParameterWrapper $parameterWrapper
     * @param BaseImageBuilder $imageBuilder
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function init(
        ParameterWrapper $parameterWrapper,
        BaseImageBuilder $imageBuilder
    ): void
    {
        $this->setParameterWrapper($parameterWrapper);

        $this->design = $imageBuilder;
        $this->design->init(calendarBuilderService: $this);
    }

    /**
     * Get days for the left and right side.
     *
     * @return array{left: array<int>, right: array<int>}
     * @throws Exception
     */
    #[ArrayShape(['left' => "array", 'right' => "array"])]
    public function getDays(): array
    {
        $days = intval((new DateTime(sprintf('%d%02d01', $this->getParameterTarget()->getYear(), $this->getParameterTarget()->getMonth())))->format('t'));

        $dayToLeft = intval(ceil($days / 2));

        return [
            'left' => [
                'from' => 1,
                'to' => $dayToLeft,
            ],
            'right' => [
                'from' => $dayToLeft + 1,
                'to' => $days,
            ]
        ];
    }

    /**
     * Return timestamp from given year, month and year.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return int
     * @throws Exception
     */
    protected function getTimestamp(int $year, int $month, int $day): int
    {
        $timestamp = mktime(12, 0, 0, $month, $day, $year);

        if ($timestamp === false) {
            throw new Exception(sprintf('Unable to create timestamp (%s:%d)', __FILE__, __LINE__));
        }

        return $timestamp;
    }

    /**
     * Returns the day of the week.
     * 0 - Sunday
     * 6 - Saturday
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return int
     * @throws Exception
     */
    public function getDayOfWeek(int $year, int $month, int $day): int
    {
        return intval(date('w', $this->getTimestamp($year, $month, $day)));
    }

    /**
     * Returns the number of the week.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return int
     * @throws Exception
     */
    protected function getWeekNumber(int $year, int $month, int $day): int
    {
        return intval(date('W', $this->getTimestamp($year, $month, $day)));
    }

    /**
     * Returns the number of the week if current day is monday. Otherwise, null.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return int|null
     * @throws Exception
     */
    public function getCalendarWeekIfMonday(int $year, int $month, int $day): ?int
    {
        $dayOfWeek = $this->getDayOfWeek($year, $month, $day);

        if ($dayOfWeek !== CalendarBuilderServiceConstants::DAY_MONDAY) {
            return null;
        }

        return $this->getWeekNumber($year, $month, $day);
    }

    /**
     * Returns the day key.
     *
     * @param int $day
     * @return string
     */
    public function getDayKey(int $day): string
    {
        return sprintf('%04d-%02d-%02d', $this->getParameterTarget()->getYear(), $this->getParameterTarget()->getMonth(), $day);
    }

    /**
     * Checks and creates given directory or directory for given file
     *
     * @param string $path
     * @param bool $isFile
     * @return string
     * @throws Exception
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function checkAndCreateDirectory(string $path, bool $isFile = false): string
    {
        $pathToCheck = match (true) {
            $isFile => dirname($path),
            default => $path,
        };

        if (!file_exists($pathToCheck)) {
            mkdir($pathToCheck, 0775, true);
        }

        if (!file_exists($pathToCheck)) {
            throw new Exception(sprintf('Unable to create directory "%s" (%s:%d)', $pathToCheck, __FILE__, __LINE__));
        }

        return $pathToCheck;
    }

    /**
     * Returns the year month key.
     *
     * @param int $year
     * @param int $month
     * @return string
     */
    protected function getYearMonthKey(int $year, int $month): string
    {
        return sprintf('%04d-%02d', $year, $month);
    }

    /**
     * Adds given event.
     *
     * @param string $key
     * @param string $name
     * @param bool $holiday
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function addEventOrHoliday(string $key, string $name, bool $holiday = false): void
    {
        /* Add new key */
        if (!array_key_exists($key, $this->eventsAndHolidaysRaw)) {
            $this->eventsAndHolidaysRaw[$key] = [
                'name' => [],
            ];
        }

        /* Add name */
        $this->eventsAndHolidaysRaw[$key]['name'][] = $name;

        /* Add holiday */
        $this->holidays[$key] = $holiday;
    }

    /**
     * Adds all events according to this month.
     *
     * @throws Exception
     */
    protected function addEvents(): void
    {
        $target = $this->getParameterTarget();
        $source = $this->getParameterSource();

        /* Build current year and month */
        $yearMonthPage = $this->getYearMonthKey($target->getYear(), $target->getMonth());

        foreach ($source->getBirthdays() as $birthday) {
            /** @var DateTimeImmutable $date */
            $date = $birthday['date'];

            /** @var string $title */
            $title = $birthday['title'];

            /* Get the event key */
            $eventKey = $this->getDayKey(intval($date->format('j')));

            /* Get year from event */
            $year = intval($date->format('Y'));

            /* This event does not fit the month → Skip */
            if ($yearMonthPage !== $this->getYearMonthKey($target->getYear(), intval($date->format('n')))) {
                continue;
            }

            /* Birthday event → But no year given */
            if ($year === CalendarBuilderServiceConstants::BIRTHDAY_YEAR_NOT_GIVEN) {
                $this->addEventOrHoliday($eventKey, $title);
                continue;
            }

            /* Calculate age from event */
            $age = $target->getYear() - $year;

            /* Birthday event → Age must be greater than 0 */
            if ($age <= 0) {
                $this->addEventOrHoliday($eventKey, $title);
                continue;
            }

            /* Birthday event → Add age to name */
            $this->addEventOrHoliday($eventKey, sprintf('%s (%d)', $title, $age));
        }
    }

    /**
     * Add holidays to this month.
     *
     * @throws Exception
     */
    public function addHolidays(): void
    {
        $target = $this->getParameterTarget();
        $source = $this->getParameterSource();

        /** @var array{date: DateTimeImmutable, title: string} $holiday */
        foreach ($source->getHolidays() as $holiday) {
            /** @var DateTimeImmutable $date */
            $date = $holiday['date'];

            /** @var string $title */
            $title = $holiday['title'];

            /* Get the event key */
            $holidayKey = $this->getDayKey(intval($date->format('j')));

            /* Get year and month */
            $month = intval($date->format('n'));
            $year = intval($date->format('Y'));

            /* Check holiday (month && year) → Skip if not equal */
            if ($target->getMonth() !== $month || $target->getYear() !== $year) {
                continue;
            }

            /* Add event or holiday label */
            $this->addEventOrHoliday($holidayKey, $title, true);
        }
    }

    /**
     * Combine entries from $this->eventsAndHolidaysRaw to $this->eventsAndHolidays
     *
     * @return array<array{name: string}>
     */
    protected function combineEventsAndHolidays(): array
    {
        $eventsAndHolidays = [];

        foreach ($this->eventsAndHolidaysRaw as $key => $eventOrHoliday) {
            $eventsAndHolidays[$key] = [
                'name' => implode(', ', $eventOrHoliday['name']),
            ];
        }

        /* Return events and holidays. */
        return $eventsAndHolidays;
    }

    /**
     * Build all events and holidays according to this month.
     *
     * @return void
     * @throws Exception
     */
    public function createEventsAndHolidays(): void
    {
        /* Reset events and holidays. */
        $this->eventsAndHolidaysRaw = [];

        /* Add events. */
        $this->addEvents();

        /* Add holidays */
        $this->addHolidays();

        /* Combine events and holidays */
        $this->eventsAndHolidays = $this->combineEventsAndHolidays();
    }

    /**
     * Removes target images.
     *
     * @param string $pathTargetAbsolute
     * @return bool
     * @throws Exception
     */
    protected function removeTargetImages(string $pathTargetAbsolute): bool
    {
        if (file_exists($pathTargetAbsolute)) {
            unlink($pathTargetAbsolute);
        }

        return true;
    }

    /**
     * Builds the given source image to a calendar page.
     *
     * @param bool $writeToFile
     * @return ImageContainer
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws Exception
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function build(bool $writeToFile = false): ImageContainer
    {
        $config = $this->getParameterWrapper()->getConfig();

        $config = $config->getKeyJson(['pages', (string) $this->parameterWrapper->getPageNumber()]);

        $pathTargetAbsolute = sprintf('%s/data/calendar/%s/%s', $this->appKernel->getProjectDir(), $this->parameterWrapper->getIdentification(), $config->getKeyString('target'));

        if ($this->deleteTargetImages) {
            $this->removeTargetImages($pathTargetAbsolute);
        }

        /* Check the target path */
        $this->checkAndCreateDirectory($pathTargetAbsolute, true);

        $this->createEventsAndHolidays();

        return $this->design->build($writeToFile);
    }
}
