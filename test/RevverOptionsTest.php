<?php
declare(strict_types=1);

namespace Netglue\RevsTest;

use Netglue\Revs\RevverOptions;
use PHPUnit\Framework\TestCase;
use function rmdir;
use Throwable;

class RevverOptionsTest extends TestCase
{
    public function testFromArray()
    {
        $options = [
            'clean_up' => true,
            'revision_count' => 10,
        ];
        $object = RevverOptions::fromArray($options);
        $this->assertTrue($object->cleanUp());
        $this->assertSame(10, $object->revisionCount());
    }

    /**
     * @expectedExceptionMessage There’s no method by that name
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionThrownForUnknownOptions()
    {
        $options = ['foo' => 'bar'];
        RevverOptions::fromArray($options);
    }

    /**
     * @expectedExceptionMessage Expected all option keys to be strings
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionThrownForIntegerKeys()
    {
        $options = [0 => 'bar'];
        RevverOptions::fromArray($options);
    }

    /**
     * @expectedExceptionMessage The revision count must an integer greater than or equal to 0
     * @expectedException \OutOfRangeException
     */
    public function testRevisionCountMustBeAnIntegerBiggerThanZero()
    {
        $options = ['revisionCount' => -1];
        RevverOptions::fromArray($options);
    }

    /**
     * @expectedExceptionMessage The given destination directory is not a directory
     * @expectedException \InvalidArgumentException
     */
    public function testDestinationMustBeADirectory()
    {
        $options = [
            'destinationDirectory' => __FILE__,
        ];
        RevverOptions::fromArray($options);
    }

    /**
     * @expectedExceptionMessage The destination directory provided cannot be written to
     * @expectedException \InvalidArgumentException
     */
    public function testNonWritableDirectoryIsExceptional()
    {
        $dir = __DIR__ . '/var';
        if (! file_exists($dir)) {
            mkdir($dir);
        }
        chmod($dir, 0500);
        $options = ['destinationDirectory' => $dir];
        try {
            RevverOptions::fromArray($options);
        } finally {
            rmdir($dir);
        }
    }

    /**
     * @expectedExceptionMessage The destination directory has not been set
     * @expectedException \RuntimeException
     */
    public function testDestinationRetrievalIsExceptionalWhenUnset()
    {
        $options = new RevverOptions();
        $options->destinationDirectory();
    }
}
