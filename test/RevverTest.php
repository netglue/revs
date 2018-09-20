<?php
declare(strict_types=1);

namespace Netglue\RevsTest;

use Netglue\Revs\Revver;
use Netglue\Revs\RevverOptions;
use PHPUnit\Framework\TestCase;
use Throwable;
use function fclose;
use function file_exists;
use function fopen;
use function ftruncate;
use function touch;
use function unlink;

class RevverTest extends TestCase
{
    /** @var RevverOptions */
    private $options;

    /** @var Revver */
    private $revver;

    /** @var string */
    private $varDir;

    public function setUp()
    {
        parent::setUp();
        $this->varDir = __DIR__ . '/fixture/var';
        if (! file_exists($this->varDir)) {
            mkdir($this->varDir);
        }
        $this->options = RevverOptions::fromArray([
            'destinationDirectory' => $this->varDir,
        ]);
        $this->revver = new Revver($this->options);
        $resource = fopen(__DIR__ . '/fixture/empty.txt', 'r+');
        ftruncate($resource, 0);
        fclose($resource);
    }

    public function tearDown()
    {
        parent::tearDown();
        if (file_exists($this->varDir)) {
            foreach (glob($this->varDir) as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->varDir);
        }
    }

    public function testRevFile()
    {
        $expectFile = sprintf(
            'empty-%s.txt',
            'd41d8cd98f00b204e9800998ecf8427e'
        );
        $expectPath = sprintf(
            '%s/%s',
            $this->varDir,
            $expectFile
        );
        try {
            $file = $this->revver->revFile(__DIR__  . '/fixture/empty.txt');
            $this->assertSame($expectFile, $file);
            $this->assertTrue(file_exists(sprintf('%s/%s', $this->varDir, $expectFile)));
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            if (file_exists($expectPath)) {
                unlink($expectPath);
            }
        }
    }

    public function testRevFileWithoutExtension()
    {
        $expectFile = sprintf(
            'no-extension-%s',
            'd41d8cd98f00b204e9800998ecf8427e'
        );
        $expectPath = sprintf(
            '%s/%s',
            $this->varDir,
            $expectFile
        );
        try {
            $file = $this->revver->revFile(__DIR__  . '/fixture/no-extension');
            $this->assertSame($expectFile, $file);
            $this->assertTrue(file_exists(sprintf('%s/%s', $this->varDir, $expectFile)));
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            if (file_exists($expectPath)) {
                unlink($expectPath);
            }
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The given argument is not a file
     */
    public function testExceptionThrownForNonFile()
    {
        $this->revver->revFile(__DIR__);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The given file cannot be read
     */
    public function testExceptionThrownForUnreadableFile()
    {
        $this->markTestSkipped('How do you make a write-only file appear like a file?');
        return;
        $file = $this->varDir . '/no-read.txt';
        try {
            touch($file);
            chmod($file, 0100);
            $this->revver->revFile(__DIR__);
        } finally {
            unlink($file);
        }
    }
}
