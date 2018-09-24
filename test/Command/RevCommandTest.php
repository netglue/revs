<?php
declare(strict_types=1);

namespace Netglue\RevsTest\Command;

use Netglue\Revs\Command\RevCommand;
use Netglue\RevsTest\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class RevCommandTest extends TestCase
{
    /** @var Application */
    private $app;

    public function setUp()
    {
        parent::setUp();
        $this->app = new Application('Some app');
    }

    public function addCommand() : RevCommand
    {
        $command = new RevCommand();
        $this->app->add($command);
        return $command;
    }

    public function testRevCountMustBeANumber()
    {
        $command = $this->addCommand();
        $tester = new CommandTester($command);
        $tester->execute([
            'command' => $command->getName(),
            '-c' => 'a',
        ]);
        $output = $tester->getDisplay();
        $this->assertContains('The revision count argument must be a number', $output);
        $this->assertEquals(-1, $tester->getStatusCode());
    }

    public function testInvalidOptionsReturnError()
    {
        $command = $this->addCommand();
        $tester = new CommandTester($command);
        $tester->execute([
            'command' => $command->getName(),
            '-t' => 'dir-doesnt-exist',
        ]);
        $output = $tester->getDisplay();
        $this->assertContains('The given destination directory is not a directory', $output);
        $this->assertEquals(-1, $tester->getStatusCode());
    }

    public function testZeroSourceFilesIsWarning()
    {
        $command = $this->addCommand();
        $tester = new CommandTester($command);
        $tester->execute([
            'command' => $command->getName(),
            '-t' => $this->varDir,
            '-s' => __DIR__ . '/*.notThere',
        ]);
        $output = $tester->getDisplay();
        $this->assertContains('yielded no source files to process', $output);
        $this->assertEquals(0, $tester->getStatusCode());
    }

    public function testSuccessfulRev()
    {
        $command = $this->addCommand();
        $tester = new CommandTester($command);
        $source = __DIR__ . '/../fixture/empty.txt';
        $tester->execute([
            'command' => $command->getName(),
            '-t' => $this->varDir,
            '-s' => $source,
        ], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);
        $output = $tester->getDisplay();
        $expect = sprintf('empty.txt copied as', $source);
        $this->assertContains($expect, $output);
        $this->assertEquals(0, $tester->getStatusCode());
    }
}
