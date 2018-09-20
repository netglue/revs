<?php
declare(strict_types=1);

namespace Netglue\Revs;

use const DIRECTORY_SEPARATOR;
use InvalidArgumentException;
use OutOfRangeException;
use function rtrim;
use RuntimeException;
use function array_map;
use function explode;
use function gettype;
use function implode;
use function is_dir;
use function is_string;
use function is_writable;
use function method_exists;
use function sprintf;

class RevverOptions
{

    /**
     * Whether to delete old revisions. Defaults to false
     * @var bool
     */
    private $cleanUp = false;

    /**
     * The number of old revisions to keep if cleanup is true
     * @var int
     */
    private $revisionCount = 1;

    /**
     * Where the revved files will be stored
     * @var string|null
     */
    private $destinationDirectory;

    public function setCleanUp(bool $cleanUp) : void
    {
        $this->cleanUp = $cleanUp;
    }

    public function cleanUp() : bool
    {
        return $this->cleanUp;
    }

    public function setRevisionCount(int $count)
    {
        if ($count < 0) {
            throw new OutOfRangeException(sprintf(
                'The revision count must an integer greater than or equal to 0. Received %d',
                $count
            ));
        }
        $this->revisionCount = $count;
    }

    public function revisionCount() : int
    {
        return $this->revisionCount;
    }

    public function setDestinationDirectory(string $directory) : void
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);
        if (! is_dir($directory)) {
            throw new InvalidArgumentException(sprintf(
                'The given destination directory is not a directory. Make sure it exists and is writable: %s',
                $directory
            ));
        }
        if (! is_writable($directory)) {
            throw new InvalidArgumentException(sprintf(
                'The destination directory provided cannot be written to: %s',
                $directory
            ));
        }
        $this->destinationDirectory = $directory;
    }

    public function destinationDirectory() : string
    {
        if (! $this->destinationDirectory) {
            throw new RuntimeException('The destination directory has not been set');
        }
        return $this->destinationDirectory;
    }

    public static function fromArray(array $values) : self
    {
        $instance = new static;
        foreach ($values as $key => $value) {
            $instance->setProperty($key, $value);
        }
        return $instance;
    }

    private function setProperty($key, $value) : void
    {
        if (! is_string($key)) {
            throw new InvalidArgumentException(sprintf(
                'Expected all option keys to be strings. Received %s',
                gettype($key)
            ));
        }
        $setter = 'set' . implode('', array_map('ucfirst', explode('_', $key)));
        if (! method_exists($this, $setter)) {
            throw new InvalidArgumentException(sprintf(
                'The key %s which resolves to the setter %s is not valid. There’s no method by that name',
                $key,
                $setter
            ));
        }
        $this->{$setter}($value);
    }
}
