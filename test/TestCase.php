<?php
declare(strict_types=1);

namespace Netglue\RevsTest;

use PHPUnit\Framework\TestCase as PHPUnit;
use RuntimeException;

class TestCase extends PHPUnit
{
    /** @var string */
    protected $varDir;

    public function setUp()
    {
        parent::setUp();
        $this->varDir = __DIR__ . '/fixture/var';
        if (! file_exists($this->varDir)) {
            mkdir($this->varDir);
        }
    }

    public function tearDown()
    {
        parent::tearDown();
        if (is_dir($this->varDir)) {
            foreach (glob($this->varDir . '/*') as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
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
