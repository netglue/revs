<?php
declare(strict_types=1);

namespace Netglue\RevsTest;

use InvalidArgumentException;
use Netglue\Revs\RevverOptions;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;
use function chmod;
use function file_exists;
use function mkdir;
use function rmdir;

class RevverOptionsTest extends TestCase
{
    public function testFromArray() : void
    {
        $options = [
            'clean_up' => true,
            'revision_count' => 10,
        ];
        $object = RevverOptions::fromArray($options);
        $this->assertTrue($object->cleanUp());
        $this->assertSame(10, $object->revisionCount());
    }

    public function testExceptionThrownForUnknownOptions() : void
    {
        $options = ['foo' => 'bar'];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('There’s no method by that name');
        RevverOptions::fromArray($options);
    }

    public function testExceptionThrownForIntegerKeys() : void
    {
        $options = [0 => 'bar'];
        $this->expectException(TypeError::class);
        RevverOptions::fromArray($options);
    }

    public function testRevisionCountMustBeAnIntegerBiggerThanZero() : void
    {
        $options = ['revisionCount' => -1];
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('The revision count must an integer greater than or equal to 0');
        RevverOptions::fromArray($options);
    }

    public function testDestinationMustBeADirectory() : void
    {
        $options = ['destinationDirectory' => __FILE__];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given destination directory is not a directory');
        RevverOptions::fromArray($options);
    }

    public function testNonWritableDirectoryIsExceptional() : void
    {
        $dir = __DIR__ . '/var';
        if (! file_exists($dir)) {
            mkdir($dir);
        }

        chmod($dir, 0500);
        $options = ['destinationDirectory' => $dir];
        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The destination directory provided cannot be written to');
            RevverOptions::fromArray($options);
        } finally {
            rmdir($dir);
        }
    }

    public function testDestinationRetrievalIsExceptionalWhenUnset() : void
    {
        $options = new RevverOptions();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The destination directory has not been set');
        $options->destinationDirectory();
    }
}
