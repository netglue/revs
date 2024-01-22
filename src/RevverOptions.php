<?php

declare(strict_types=1);

namespace Netglue\Revs;

use InvalidArgumentException;
use OutOfRangeException;
use RuntimeException;

use function array_map;
use function explode;
use function implode;
use function is_dir;
use function is_writable;
use function method_exists;
use function rtrim;
use function sprintf;

use const DIRECTORY_SEPARATOR;

final class RevverOptions
{
    /**
     * Whether to delete old revisions. Defaults to false
     */
    private bool $cleanUp = false;

    /**
     * The number of old revisions to keep if cleanup is true
     */
    private int $revisionCount = 1;

    /**
     * Where the revved files will be stored
     */
    private string|null $destinationDirectory = null;

    public function setCleanUp(bool $cleanUp): void
    {
        $this->cleanUp = $cleanUp;
    }

    public function cleanUp(): bool
    {
        return $this->cleanUp;
    }

    public function setRevisionCount(int $count): void
    {
        if ($count < 0) {
            throw new OutOfRangeException(sprintf(
                'The revision count must an integer greater than or equal to 0. Received %d',
                $count,
            ));
        }

        $this->revisionCount = $count;
    }

    public function revisionCount(): int
    {
        return $this->revisionCount;
    }

    public function setDestinationDirectory(string $directory): void
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);
        if (! is_dir($directory)) {
            throw new InvalidArgumentException(sprintf(
                'The given destination directory is not a directory. Make sure it exists and is writable: %s',
                $directory,
            ));
        }

        if (! is_writable($directory)) {
            throw new InvalidArgumentException(sprintf(
                'The destination directory provided cannot be written to: %s',
                $directory,
            ));
        }

        $this->destinationDirectory = $directory;
    }

    public function destinationDirectory(): string
    {
        if ($this->destinationDirectory === null) {
            throw new RuntimeException('The destination directory has not been set');
        }

        return $this->destinationDirectory;
    }

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        $instance = new self();
        /** @psalm-suppress MixedAssignment */
        foreach ($values as $key => $value) {
            $instance->setProperty($key, $value);
        }

        return $instance;
    }

    private function setProperty(string $key, mixed $value): void
    {
        $setter = 'set' . implode('', array_map('ucfirst', explode('_', $key)));
        if (! method_exists($this, $setter)) {
            throw new InvalidArgumentException(sprintf(
                'The key %s which resolves to the setter %s is not valid. There’s no method by that name',
                $key,
                $setter,
            ));
        }

        $this->{$setter}($value);
    }
}
