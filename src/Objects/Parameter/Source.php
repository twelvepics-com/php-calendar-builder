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

namespace App\Objects\Parameter;

use App\Objects\Image\Image;
use App\Objects\Image\ImageHolder;
use App\Objects\Parameter\Base\BaseParameter;
use DateTimeImmutable;
use ImagickException;
use Ixnode\PhpCliImage\CliImage;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use LogicException;
use Symfony\Component\Console\Input\InputInterface;
use UnexpectedValueException;

/**
 * Class Source
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-08)
 * @since 0.1.0 (2023-11-08) First version.
 */
class Source extends BaseParameter
{
    private ImageHolder $image;

    private CliImage $cliImage;

    /** @var array<int, array{date: DateTimeImmutable, title: string}> $holidays */
    private array $holidays = [];

    /** @var array<int, array{date: DateTimeImmutable, title: string}> $birthdays */
    private array $birthdays = [];

    private string $identification;

    /**
     * @return ImageHolder
     */
    public function getImageHolder(): ImageHolder
    {
        if (!isset($this->image)) {
            throw new UnexpectedValueException(sprintf('Call method readParameter before using this method "%s".', __METHOD__));
        }

        return $this->image;
    }

    /**
     * @param ImageHolder $image
     * @param string $identification
     * @return void
     */
    private function setImageHolder(ImageHolder $image, string $identification): void
    {
        $this->setIdentification($identification);

        $this->image = $image;
    }

    /**
     * @return CliImage
     */
    public function getCliImage(): CliImage
    {
        if (!isset($this->cliImage)) {
            throw new UnexpectedValueException(sprintf('Call method readParameter before using this method "%s".', __METHOD__));
        }

        return $this->cliImage;
    }

    /**
     * @param CliImage $cliImage
     * @return void
     */
    private function setCliImage(CliImage $cliImage): void
    {
        $this->cliImage = $cliImage;
    }

    /**
     * @return array<int, array{date: DateTimeImmutable, title: string}>
     */
    public function getHolidays(): array
    {
        return $this->holidays;
    }

    /**
     * @return array<int, array{date: DateTimeImmutable, title: string}>
     */
    public function getBirthdays(): array
    {
        return $this->birthdays;
    }

    /**
     * @return string
     */
    public function getIdentification(): string
    {
        return $this->identification;
    }

    /**
     * @param string $identification
     * @return self
     */
    public function setIdentification(string $identification): self
    {
        $this->identification = $identification;

        return $this;
    }

    /**
     * Get DateTimeImmutable object from given date.
     */
    private function getDateTimeImmutable(int|string $date): DateTimeImmutable
    {
        $dateTimeImmutable = match (true) {
            ctype_digit((string) $date) => DateTimeImmutable::createFromFormat('U', (string) $date),
            default => DateTimeImmutable::createFromFormat('Y-m-d', (string) $date)
        };

        if (!$dateTimeImmutable instanceof DateTimeImmutable) {
            throw new LogicException(sprintf('Unable to parse DateTimeImmutable value: %s', $date));
        }

        return $dateTimeImmutable;
    }

    /**
     * Reads all holidays.
     *
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     */
    private function readHolidays(): void
    {
        $this->holidays = [];
        if ($this->getConfig()->hasKey('holidays')) {
            $holidays = $this->getConfig()->getKeyArray('holidays');

            foreach ($holidays as $date => $holiday) {

                $dateImmutable = $this->getDateTimeImmutable($date);

                if (is_string($holiday)) {
                    $holiday = [
                        'name' => $holiday,
                    ];
                }

                if (!is_array($holiday)) {
                    throw new LogicException('Unable to get holiday.');
                }

                $jsonHoliday = (new Json($holiday))->setKeyMode(Json::KEY_MODE_UNDERLINE);

                if (!$jsonHoliday->hasKey('name_short')) {
                    $jsonHoliday->addValue('name_short', $jsonHoliday->getKeyString('name'));
                }

                $this->holidays[] = [
                    'date' => $dateImmutable,
                    'title' => $jsonHoliday->getKeyString('name_short'),
                ];
            }
        }
    }


    /**
     * Reads all birthdays.
     *
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function readBirthdays(): void
    {
        $this->birthdays = [];
        if ($this->getConfig()->hasKey('birthdays')) {
            $birthdays = $this->getConfig()->getKeyArray('birthdays');

            foreach ($birthdays as $date => $title) {
                $titleShort = null;

                if (is_array($title) && array_key_exists('date', $title)) {
                    $date = $title['date'];
                }

                if (is_array($title) && array_key_exists('name-short', $title)) {
                    $titleShort = $title['name-short'];
                }

                if (is_array($title) && array_key_exists('name', $title)) {
                    $title = $title['name'];
                }

                if (!is_string($title)) {
                    throw new LogicException('Unable to read name of birthday.');
                }

                if (is_null($titleShort)) {
                    $titleShort = $title;
                }

                $dateImmutable = $this->getDateTimeImmutable($date);

                if (!is_string($titleShort)) {
                    throw new LogicException('Invalid title');
                }

                $this->birthdays[] = [
                    'date' => $dateImmutable,
                    'title' => $titleShort,
                ];
            }
        }
    }

    /**
     * Reads and sets the parameter to this class.
     *
     * @param InputInterface $input
     * @param int $sourceCliWidth
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws ParserException
     * @throws FunctionReplaceException
     * @throws ImagickException
     */
    public function readParameter(InputInterface $input, int $sourceCliWidth = Image::CLI_IMAGE_WIDTH): void
    {
        $identifier = $this->getIdentifier($input);

        $page = $this->getConfig()->getKeyJson(['pages', (string) $this->getPageNumber()]);

        $source = $page->getKey('source');

        $imageConfig = match (true) {
            is_string($source) => $source,
            is_array($source) => new Json($source),
            default => null,
        };

        if (is_null($imageConfig)) {
            throw new LogicException('Unable to read image source.');
        }

        $imageHolder = new ImageHolder($this->projectDir, $identifier, $imageConfig, $input);

        $this->setImageHolder($imageHolder, $identifier);

        $this->setCliImage(new CliImage(
            image: $imageHolder->getImageString(),
            width: $sourceCliWidth,
            engineType: CliImage::ENGINE_IMAGICK
        ));

        $this->readHolidays();
        $this->readBirthdays();
    }
}
