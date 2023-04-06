<?php

declare(strict_types=1);

namespace Netglue\RevsTest;

use PHPUnit\Framework\TestCase as PHPUnit;
use RuntimeException;

use function fclose;
use function file_exists;
use function fopen;
use function ftruncate;
use function glob;
use function is_dir;
use function is_file;
use function mkdir;
use function rmdir;
use function unlink;

class TestCase extends PHPUnit
{
    protected string $varDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->varDir = __DIR__ . '/fixture/var';
        if (file_exists($this->varDir)) {
            return;
        }

        mkdir($this->varDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->varDir)) {
            foreach (glob($this->varDir . '/*') as $file) {
                if (! is_file($file)) {
                    continue;
                }

                unlink($file);
            }

            rmdir($this->varDir);
        }

        $resource = fopen(__DIR__ . '/fixture/empty.txt', 'r+');
        if (! $resource) {
            throw new RuntimeException('Cannot get resource handle for test fixture');
        }

        ftruncate($resource, 0);
        fclose($resource);
    }
}
