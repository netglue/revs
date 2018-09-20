<?php
declare(strict_types=1);

namespace Netglue\Revs;

use InvalidArgumentException;
use function copy;
use function is_file;
use function is_readable;
use function md5_file;
use function sprintf;
use const DIRECTORY_SEPARATOR;

class Revver
{
    /** @var RevverOptions */
    private $options;

    public function __construct(RevverOptions $options)
    {
        $this->options = $options;
    }

    public function revFile(string $file) : string
    {
        $this->assertReadableFile($file);
        $hash = md5_file($file);
        $info = pathinfo(basename($file));
        $basename = $info['filename'];
        $extension = isset($info['extension']) ? '.' . $info['extension'] : '';
        $fileName = sprintf(
            '%s-%s%s',
            $basename,
            $hash,
            $extension
        );
        copy($file, sprintf(
            '%s%s%s',
            $this->options->destinationDirectory(),
            DIRECTORY_SEPARATOR,
            $fileName
        ));
        return $fileName;
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
