<?php

declare(strict_types=1);

namespace Netglue\Revs;

final class RevvedFile
{
    /**
     * @param non-empty-string $sourceFile
     * @param non-empty-string $destinationFile
     * @param non-empty-string $matchPattern
     * @param list<string>     $deletedRevisions
     */
    public function __construct(
        private readonly string $sourceFile,
        private readonly string $destinationFile,
        private readonly string $matchPattern,
        private readonly array $deletedRevisions = [],
    ) {
    }

    /** @return non-empty-string */
    public function source(): string
    {
        return $this->sourceFile;
    }

    /** @return non-empty-string */
    public function destination(): string
    {
        return $this->destinationFile;
    }

    /** @return list<string> */
    public function deletedRevisions(): array
    {
        return $this->deletedRevisions;
    }

    /** @return non-empty-string */
    public function matchPattern(): string
    {
        return $this->matchPattern;
    }
}
