<?php
declare(strict_types=1);

namespace Netglue\Revs;

use InvalidArgumentException;
use function basename;
use function file_get_contents;
use function file_put_contents;
use function is_file;
use function is_writable;
use function preg_quote;
use function preg_replace;
use function sprintf;

class Replacer
{

    public static function replaceInString(string $subject, RevvedFile $info, ?int &$replacementCount = null) : string
    {
        $c1 = $c2 = 0;
        $replacement = basename($info->destination());
        $pattern = $info->matchPattern();
        $value = preg_replace($pattern, $replacement, $subject, -1, $c1);

        $nakedFile = basename($info->source());
        $pattern = sprintf(
            '#(\b)%s(\b)#',
            preg_quote($nakedFile)
        );
        $value = preg_replace($pattern, '$1' . $replacement . '$2', $value, -1, $c2);

        $replacementCount = $c1 + $c2;
        return $value;
    }

    public static function replaceInFile(string $sourceFile, RevvedFile $info) : int
    {
        if (! is_file($sourceFile)) {
            throw new InvalidArgumentException(sprintf(
                'The given replacement target at %s is not a file',
                $sourceFile
            ));
        }
        if (! is_writable($sourceFile)) {
            throw new InvalidArgumentException(sprintf(
                'The replacement target cannot be written to (%s)',
                $sourceFile
            ));
        }
        file_put_contents(
            $sourceFile,
            self::replaceInString(file_get_contents($sourceFile), $info, $count)
        );
        return $count;
    }
}
