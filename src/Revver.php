<?php
declare(strict_types=1);

namespace Netglue\Revs;

use DirectoryIterator;
use InvalidArgumentException;
use Ramsey\Uuid\Codec\OrderedTimeCodec;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;
use function array_map;
use function array_slice;
use function basename;
use function copy;
use function dirname;
use function file_exists;
use function is_file;
use function is_readable;
use function md5_file;
use function preg_match;
use function sprintf;
use function unlink;
use function usort;
use const DIRECTORY_SEPARATOR;

class Revver
{
    /** @var RevverOptions */
    private $options;

    /** @var UuidFactory|null */
    private $uuidFactory;

    public function __construct(RevverOptions $options)
    {
        $this->options = $options;
    }

    public function revFile(string $file) : RevvedFile
    {
        $this->assertReadableFile($file);
        $hash = md5_file($file);
        if (! $hash) {
            throw new RuntimeException(sprintf(
                'Failed to compute a hash of the file at %s',
                $file
            ));
        }
        $matcher = $this->filenameMatchPattern(basename($file));
        $existing = $this->getPathOfExistingMatchingHash($file, $hash);
        if ($existing) {
            return new RevvedFile($file, $existing, $matcher);
        }
        $info = pathinfo(basename($file));
        $basename = $info['filename'];
        $extension = isset($info['extension']) ? '.' . $info['extension'] : '';
        $fileName = sprintf(
            '%s-%s-%s%s',
            $basename,
            $hash,
            $this->uuidFactory()->uuid1()->toString(),
            $extension
        );
        $filePath = sprintf(
            '%s%s%s',
            $this->options->destinationDirectory(),
            DIRECTORY_SEPARATOR,
            $fileName
        );
        copy($file, $filePath);
        $info = new RevvedFile($file, $filePath, $matcher);
        if (! $this->options->cleanUp()) {
            return $info;
        }
        $unlinked = $this->cleanUpFile($info);
        return new RevvedFile($file, $filePath, $matcher, $unlinked);
    }

    private function uuidFactory() : UuidFactory
    {
        if (! $this->uuidFactory) {
            $this->uuidFactory = new UuidFactory();
            $this->uuidFactory->setCodec(
                new OrderedTimeCodec(
                    $this->uuidFactory->getUuidBuilder()
                )
            );
        }
        return $this->uuidFactory;
    }

    private function filenameMatchPattern(string $sourceFileBasename) : string
    {
        $info = pathinfo($sourceFileBasename);
        $basename = $info['filename'];
        $extension = isset($info['extension']) ? '.' . $info['extension'] : '';
        $hashPattern = '[0-9a-f]{32}';
        $uuidPattern = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';
        return sprintf(
            '#%s\-(%s)\-(%s)%s#',
            preg_quote($basename, '#'),
            $hashPattern,
            $uuidPattern,
            preg_quote($extension, '#')
        );
    }

    private function getPathOfExistingMatchingHash(string $sourceFilePath, string $hash) :? string
    {
        $basename = basename($sourceFilePath);
        $pattern  = $this->filenameMatchPattern($basename);
        foreach (new DirectoryIterator($this->options->destinationDirectory()) as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }
            if (! preg_match($pattern, $fileInfo->getFilename(), $matches)) {
                continue;
            }
            if ($matches[1] === $hash) {
                return sprintf(
                    '%s%s%s',
                    $fileInfo->getPath(),
                    DIRECTORY_SEPARATOR,
                    $fileInfo->getFilename()
                );
            }
        }
        return null;
    }

    private function cleanUpFile(RevvedFile $info) : array
    {
        $unlinked = [];
        $unlink = $this->buildUnlinkList($info);
        foreach ($unlink as $filePath) {
            if (! file_exists($filePath)) {
                throw new RuntimeException(sprintf(
                    'Expected the file at %s in the destination directory to exist for un-linking '
                    . 'but it wasn’t found.',
                    basename($filePath)
                ));
            }
            if (! unlink($filePath)) {
                throw new RuntimeException(sprintf(
                    'Failed to unlink the file at the following path: %s',
                    $filePath
                ));
            }
            $unlinked[] = $filePath;
        }
        return $unlinked;
    }

    private function buildUnlinkList(RevvedFile $info) : array
    {
        $pattern = $this->filenameMatchPattern(basename($info->source()));
        $unlinkList = [];
        foreach (new DirectoryIterator(dirname($info->destination())) as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }
            $filename = $fileInfo->getFilename();
            if (! preg_match($pattern, $filename, $matches)) {
                continue;
            }
            $filePath = sprintf('%s%s%s', $fileInfo->getPath(), DIRECTORY_SEPARATOR, $filename);
            if ($filePath === $info->destination()) {
                continue;
            }
            $unlinkList[] = [
                'uuid' => $this->uuidFactory()->fromString($matches[2]),
                'path' => $filePath,
            ];
        }
        usort($unlinkList, function ($a, $b) {
            /** @var UuidInterface $aUuid */
            $aUuid = $a['uuid'];
            /** @var UuidInterface $bUuid */
            $bUuid = $b['uuid'];
            return $bUuid->compareTo($aUuid);
        });
        $count = count($unlinkList) - $this->options->revisionCount();
        if ($count < 1) {
            return [];
        }
        $remaining = array_slice($unlinkList, (0 - $count));
        return array_map(function (array $element) {
            return $element['path'];
        }, $remaining);
    }

    private function assertReadableFile(string $file) : void
    {
        if (! is_file($file)) {
            throw new InvalidArgumentException(sprintf(
                'The given argument is not a file: %s',
                $file
            ));
        }
        if (! is_readable($file)) {
            throw new InvalidArgumentException(sprintf(
                'The given file cannot be read: %s',
                $file
            ));
        }
    }
}
