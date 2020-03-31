<?php
declare(strict_types=1);

namespace Netglue\RevsTest;

use Netglue\Revs\Replacer;
use Netglue\Revs\Revver;
use Netglue\Revs\RevverOptions;
use function basename;
use function copy;
use function sprintf;

class ReplacerTest extends TestCase
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

    public function testStringReplacement() : void
    {
        $source = '
        "empty.txt",
        "empty-1356c67d7ad1638d816bfb822dd2c25d-a8c71744-bdaa-11e8-820f-787b8ac8307f.txt",
        "/some/relative/path/empty-1356c67d7ad1638d816bfb822dd2c25d-a8c71744-bdaa-11e8-820f-787b8ac8307f.txt",
        "empty.css",
        "notempty.txt",
        "empty.text",
        "Empty.txt",
        ';
        $info = $this->revver->revFile(__DIR__ . '/fixture/empty.txt');
        $expect = sprintf('
        "%1$s",
        "%1$s",
        "/some/relative/path/%1$s",
        "empty.css",
        "notempty.txt",
        "empty.text",
        "Empty.txt",
        ', basename($info->destination()));
        $value = Replacer::replaceInString($source, $info, $count);
        $this->assertSame($expect, $value);
        $this->assertSame(3, $count);
    }

    public function testFileReplacement() : void
    {
        $target = __DIR__ . '/fixture/var/target.txt';
        copy(__DIR__ . '/fixture/replacement-target.txt', $target);
        $info = $this->revver->revFile(__DIR__ . '/fixture/empty.txt');
        $count = Replacer::replaceInFile($target, $info);
        $this->assertSame(3, $count);
    }
}
