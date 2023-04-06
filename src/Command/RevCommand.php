<?php

declare(strict_types=1);

namespace Netglue\Revs\Command;

use Netglue\Revs\Replacer;
use Netglue\Revs\RevvedFile;
use Netglue\Revs\Revver;
use Netglue\Revs\RevverOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function assert;
use function count;
use function glob;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_scalar;
use function is_string;
use function sprintf;

final class RevCommand extends Command
{
    private SymfonyStyle|null $io;

    protected function configure(): void
    {
        parent::configure();

        $this->setName('netglue:rev');
        $this->setDescription('rev file names and replace references to them in files');

        $this->addOption(
            'source',
            's',
            InputOption::VALUE_REQUIRED,
            'A glob to match file names that will be revved',
        );

        $this->addOption(
            'target',
            't',
            InputOption::VALUE_REQUIRED,
            'A target directory, where the revved copies will be placed',
        );

        $this->addOption(
            'delete',
            'd',
            InputOption::VALUE_NONE,
            'Whether to delete old revisions or not',
        );

        $this->addOption(
            'revisionCount',
            'c',
            InputOption::VALUE_OPTIONAL,
            'The number of old revisions to keep. Defaults to none',
            0,
        );

        $this->addOption(
            'replace',
            'r',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
            'Replacement targets such as layout files, HTML files etc',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $revCount = $input->getOption('revisionCount');
        if (! is_numeric($revCount)) {
            $this->io->error('The revision count argument must be a number');

            return -1;
        }

        try {
            $options = new RevverOptions();
            $target = $input->getOption('target');
            assert(is_string($target));
            $options->setDestinationDirectory($target);
            $delete = $input->getOption('delete');
            assert(is_bool($delete));
            $options->setCleanUp($delete);
            assert(is_scalar($revCount));
            $options->setRevisionCount((int) $revCount);
        } catch (Throwable $exception) {
            $this->io->error(sprintf(
                'Invalid Option: %s',
                $exception->getMessage(),
            ));

            return -1;
        }

        $revver = new Revver($options);
        $sourceGlob = $input->getOption('source');
        assert(is_string($sourceGlob));
        $sources = glob($sourceGlob);
        assert(is_array($sources));
        if (! count($sources)) {
            $this->io->warning(sprintf(
                'The --source|-s argument %s yielded no source files to process',
                $sourceGlob,
            ));

            return 0;
        }

        foreach ($sources as $file) {
            $revvedFile = $revver->revFile($file);
            if ($output->isVerbose()) {
                $this->io->success(sprintf(
                    'File %s copied as %s. %d old revisions removed',
                    $revvedFile->source(),
                    $revvedFile->destination(),
                    count($revvedFile->deletedRevisions()),
                ));
            }

            $this->replaceInFiles($input, $output, $revvedFile);
        }

        return 0;
    }

    private function replaceInFiles(InputInterface $input, OutputInterface $output, RevvedFile $info): void
    {
        $args = $input->getOption('replace');
        assert(is_array($args));

        $targets = [];
        $count = 0;
        foreach ($args as $glob) {
            $globbed = glob($glob);
            assert(is_array($globbed));

            foreach ($globbed as $file) {
                $targets[] = $file;
            }
        }

        if (! count($targets)) {
            return;
        }

        foreach ($targets as $target) {
            $count += Replacer::replaceInFile($target, $info);
        }

        if (! $output->isVerbose()) {
            return;
        }

        $this->io->success(sprintf(
            'Replaced %d references to %s within %d target files',
            $count,
            $info->source(),
            count($targets),
        ));
    }
}
