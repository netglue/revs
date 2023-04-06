<?php

declare(strict_types=1);

namespace Netglue\Revs;

final class RevvedFile
{
    /** @param list<string> $deletedRevisions */
    public function __construct(
        private string $sourceFile,
        private string $destinationFile,
        private string $matchPattern,
        private array $deletedRevisions = [],
    ) {
    }

    public function source(): string
    {
        return $this->sourceFile;
    }

    public function destination(): string
    {
        return $this->destinationFile;
    }

    /** @return list<string> */
    public function deletedRevisions(): array
    {
        return $this->deletedRevisions;
    }

    public function matchPattern(): string
    {
        return $this->matchPattern;
    }
}
