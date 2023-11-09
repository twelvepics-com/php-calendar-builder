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

namespace App\Parameter;

use App\Constants\Parameter\Argument;
use DateTimeImmutable;
use Ixnode\PhpCliImage\CliImage;
use Ixnode\PhpContainer\File;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;
use UnexpectedValueException;

/**
 * Class Source
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-08)
 * @since 0.1.0 (2023-11-08) First version.
 */
class Source
{
    private string $path;

    private string $pathConfig;

    private File $image;

    private CliImage $cliImage;

    /** @var array<int, array{date: DateTimeImmutable, title: string}> $holidays */
    private array $holidays = [];

    /** @var array<int, array{date: DateTimeImmutable, title: string}> $birthdays */
    private array $birthdays = [];

    /**
     * @param KernelInterface $appKernel
     */
    public function __construct(private readonly KernelInterface $appKernel)
    {
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        if (!isset($this->path)) {
            throw new UnexpectedValueException(sprintf('Call method readParameter before using this method "%s".', __METHOD__));
        }

        return $this->path;
    }

    /**
     * @param string $path
     * @return void
     */
    private function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPathConfig(): string
    {
        return $this->pathConfig;
    }

    /**
     * @param string $pathConfig
     */
    public function setPathConfig(string $pathConfig): void
    {
        $this->pathConfig = $pathConfig;
    }

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
     * @param File $config
     * @return void
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function addConfig(File $config): void
    {
        /* Check if the config file exists. */
        try {
            $config->getPathReal();
        } catch (FileNotFoundException) {
            return;
        }

        $configValues = Yaml::parse($config->getContentAsText());

        if (!is_array($configValues)) {
            throw new LogicException('Array value expected.');
        }

        $this->holidays = [];
        if (array_key_exists('holidays', $configValues) && is_array($configValues['holidays'])) {
            foreach ($configValues['holidays'] as $date => $title) {
                $dateImmutable = DateTimeImmutable::createFromFormat('U', (string) $date);

                if ($dateImmutable === false) {
                    throw new LogicException(sprintf('Invalid date "%s".', $date));
                }

                $this->holidays[] = [
                    'date' => $dateImmutable,
                    'title' => $title,
                ];
            }
        }

        $this->birthdays = [];
        if (array_key_exists('birthdays', $configValues) && is_array($configValues['birthdays'])) {
            foreach ($configValues['birthdays'] as $date => $title) {
                $dateImmutable = DateTimeImmutable::createFromFormat('U', (string) $date);

                if ($dateImmutable === false) {
                    throw new LogicException(sprintf('Invalid date "%s".', $date));
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
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     */
    public function readParameter(InputInterface $input, int $sourceCliWidth = 80): void
    {
        $source = $input->getArgument(Argument::SOURCE);

        if (!is_string($source) && !is_int($source)) {
            throw new LogicException(sprintf('Invalid argument for source "%s".', Argument::SOURCE));
        }

        $this->setPath((string) $source);

        $this->setImage(new File($this->getPath(), $this->appKernel->getProjectDir()));

        $this->setCliImage(new CliImage($this->getImage(), $sourceCliWidth));

        $this->setPathConfig(sprintf('%s/%s', pathinfo($this->getPath(), PATHINFO_DIRNAME), 'config.yml'));

        $this->addConfig(new File($this->getPathConfig(), $this->appKernel->getProjectDir()));
    }
}
