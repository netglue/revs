<?php
declare(strict_types=1);

namespace Netglue\RevsTest;

use InvalidArgumentException;
use Netglue\Revs\RevvedFile;
use Netglue\Revs\Revver;
use Netglue\Revs\RevverOptions;
use function basename;
use function chmod;
use function clearstatcache;
use function current;
use function file_exists;
use function file_put_contents;
use function touch;
use function unlink;

class RevverTest extends TestCase
{
    /** @var RevverOptions */
    private $options;

    /** @var Revver */
    private $revver;

    protected function setUp() : void
    {
        parent::setUp();
        $this->options = RevverOptions::fromArray([
            'destinationDirectory' => $this->varDir,
        ]);
        $this->revver = new Revver($this->options);
    }

    public function testRevFile() : void
    {
        $sourceFile = __DIR__ . '/fixture/empty.txt';
        $info = $this->revver->revFile($sourceFile);
        $this->assertInstanceOf(RevvedFile::class, $info);
        $this->assertFileExists($info->destination());
        $this->assertStringStartsWith('empty', basename($info->destination()));
        $this->assertStringEndsWith('.txt', $info->destination());
    }

    public function testRevFileWithoutExtension() : void
    {
        $sourceFile = __DIR__ . '/fixture/no-extension';
        $info = $this->revver->revFile($sourceFile);
        $this->assertFileExists($info->destination());
        $this->assertStringStartsWith('no-extension', basename($info->destination()));
    }

    public function testExceptionThrownForNonFile() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given argument is not a file');
        $this->revver->revFile(__DIR__);
    }

    public function testExceptionThrownForUnreadableFile() : void
    {
        $file = $this->varDir . '/no-read.txt';
        try {
            touch($file);
            chmod($file, 0200);
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The given file cannot be read');
            $this->revver->revFile($file);
        } finally {
            unlink($file);
        }
    }

    public function testMultipleRevsAndCleanup() : void
    {
        // Current options mean that the first 3 revs will be kept on disk:
        $sourceFile = __DIR__ . '/fixture/empty.txt';
        $revver = new Revver($this->options);
        $firstRev = $revver->revFile($sourceFile);
        file_put_contents($sourceFile, '2nd');
        $revver = new Revver($this->options);
        $secondRev = $revver->revFile($sourceFile);
        file_put_contents($sourceFile, '3rd');
        $revver = new Revver($this->options);
        $thirdRev = $revver->revFile($sourceFile);

        $this->assertFileExists($firstRev->destination());
        $this->assertFileExists($secondRev->destination());
        $this->assertFileExists($thirdRev->destination());

        // Change options so that automatic cleanup occurs for the 4th rev
        $options = RevverOptions::fromArray([
            'destinationDirectory' => $this->varDir,
            'cleanUp' => true,
            'revisionCount' => 1,
        ]);

        file_put_contents($sourceFile, '4th');
        $revver = new Revver($options);
        $fourthRev = $revver->revFile($sourceFile);

        $this->assertFileNotExists($firstRev->destination());
        $this->assertFileNotExists($secondRev->destination());
        $this->assertFileExists($thirdRev->destination());
        $this->assertFileExists($fourthRev->destination());

        $deleted = $fourthRev->deletedRevisions();
        $this->assertCount(2, $deleted);
        $this->assertContains($firstRev->destination(), $deleted);
        $this->assertContains($secondRev->destination(), $deleted);
    }

    public function testThatNewRevWillNotBeDeletedWhenRevisionCountIsZero() : void
    {
        $options = RevverOptions::fromArray([
            'destinationDirectory' => $this->varDir,
            'cleanUp' => true,
            'revisionCount' => 0,
        ]);
        $sourceFile = __DIR__ . '/fixture/empty.txt';
        $revver = new Revver($options);
        $info = $revver->revFile($sourceFile);
        $this->assertTrue(file_exists($info->destination()));
        $this->assertCount(0, $info->deletedRevisions());
    }

    public function testThatLastRevWillNotBeDeletedWhenRevisionCountIs1() : void
    {
        $options = RevverOptions::fromArray([
            'destinationDirectory' => $this->varDir,
            'cleanUp' => true,
            'revisionCount' => 1,
        ]);
        $sourceFile = __DIR__ . '/fixture/empty.txt';
        $revver = new Revver($options);
        $firstRev = $revver->revFile($sourceFile);
        $this->assertTrue(file_exists($firstRev->destination()));

        file_put_contents($sourceFile, '2nd');

        $revver = new Revver($options);
        $secondRev = $revver->revFile($sourceFile);
        clearstatcache();
        $this->assertTrue(file_exists($firstRev->destination()));
        $this->assertTrue(file_exists($secondRev->destination()));

        file_put_contents($sourceFile, '3rd');

        $revver = new Revver($options);
        $thirdRev = $revver->revFile($sourceFile);
        clearstatcache();
        $this->assertFalse(file_exists($firstRev->destination()));
        $this->assertTrue(file_exists($secondRev->destination()));
        $this->assertTrue(file_exists($thirdRev->destination()));

        $deleted = $thirdRev->deletedRevisions();
        $this->assertCount(1, $deleted);
        $this->assertSame($firstRev->destination(), current($deleted));
    }

    public function testThatFilenameIsUnchangedWhenSourceFileContentHashIsTheSame() : void
    {
        $sourceFile = __DIR__ . '/fixture/empty.txt';
        $firstRev = $this->revver->revFile($sourceFile);
        $this->assertTrue(file_exists($firstRev->destination()));
        $this->revver->revFile($sourceFile);
        $this->assertTrue(file_exists($firstRev->destination()));
    }

    public function testThatRemovingOldRevsOnlyAppliesToCorrectlyNamedFile() : void
    {
        $firstSource  = __DIR__ . '/fixture/no-extension';
        $secondSource = __DIR__ . '/fixture/empty.txt';
        $this->options->setCleanUp(true);
        $this->options->setRevisionCount(0);
        $firstRev = $this->revver->revFile($firstSource);
        $this->assertTrue(file_exists($firstRev->destination()));

        $secondRev = $this->revver->revFile($secondSource);
        $this->assertTrue(file_exists($firstRev->destination()));
        $this->assertTrue(file_exists($secondRev->destination()));

        file_put_contents($secondSource, 'Foo');

        $thirdRev = $this->revver->revFile($secondSource);
        $this->assertTrue(file_exists($firstRev->destination()));
        $this->assertFalse(file_exists($secondRev->destination()));
        $this->assertTrue(file_exists($thirdRev->destination()));
    }
}
