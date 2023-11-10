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

use App\Objects\Parameter\Base\BaseParameter;
use DateTimeImmutable;
use Ixnode\PhpCliImage\CliImage;
use Ixnode\PhpContainer\File;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
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
    private File $image;

    private CliImage $cliImage;

    /** @var array<int, array{date: DateTimeImmutable, title: string}> $holidays */
    private array $holidays = [];

    /** @var array<int, array{date: DateTimeImmutable, title: string}> $birthdays */
    private array $birthdays = [];

    /**
     * @return File
     */
    public function getImage(): File
    {
        if (!isset($this->image)) {
            throw new UnexpectedValueException(sprintf('Call method readParameter before using this method "%s".', __METHOD__));
        }

        return $this->image;
    }

    /**
     * @param File $image
     * @return void
     */
    private function setImage(File $image): void
    {
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
     */
    private function readHolidays(): void
    {
        if (is_null($this->config)) {
            return;
        }

        $this->holidays = [];
        if ($this->config->hasKey('holidays')) {
            $holidays = $this->config->getKeyArray('holidays');

            foreach ($holidays as $date => $title) {
                $dateImmutable = DateTimeImmutable::createFromFormat('U', (string)$date);

                if (!$dateImmutable instanceof DateTimeImmutable) {
                    throw new LogicException(sprintf('Invalid date "%s".', $date));
                }

                if (!is_string($title)) {
                    throw new LogicException('Invalid title');
                }

                $this->holidays[] = [
                    'date' => $dateImmutable,
                    'title' => $title,
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
     */
    private function readBirthdays(): void
    {
        if (is_null($this->config)) {
            return;
        }

        $this->birthdays = [];
        if ($this->config->hasKey('birthdays')) {
            $birthdays = $this->config->getKeyArray('birthdays');

            foreach ($birthdays as $date => $title) {
                $dateImmutable = DateTimeImmutable::createFromFormat('U', (string) $date);

                if ((!$dateImmutable instanceof DateTimeImmutable)) {
                    throw new LogicException(sprintf('Invalid date "%s".', $date));
                }

                if (!is_string($title)) {
                    throw new LogicException('Invalid title');
                }

                $this->birthdays[] = [
                    'date' => $dateImmutable,
                    'title' => $title,
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
     */
    public function readParameter(InputInterface $input, int $sourceCliWidth = 80): void
    {
        $sourcePath = $this->getSourcePath($input);

        $this->setImage(new File($sourcePath, $this->appKernel->getProjectDir()));

        $this->setCliImage(new CliImage($this->getImage(), $sourceCliWidth));

        if (is_null($this->config)) {
            $pathConfig = sprintf('%s/%s', pathinfo($sourcePath, PATHINFO_DIRNAME), self::CONFIG_NAME);

            $this->addConfig(new File($pathConfig, $this->appKernel->getProjectDir()));
        }

        $this->readHolidays();
        $this->readBirthdays();
    }
}
