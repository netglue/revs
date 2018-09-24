<?php
declare(strict_types=1);

namespace Netglue\Revs;

class RevvedFile
{
    /** @var string */
    private $sourceFile;

    /** @var string */
    private $destinationFile;

    /** @var string */
    private $matchPattern;

    /** @var array */
    private $deletedRevisions;

    public function __construct(string $source, string $destination, string $matchPattern, ?array $unlinked = null)
    {
        $this->sourceFile = $source;
        $this->destinationFile = $destination;
        $this->matchPattern = $matchPattern;
        $this->deletedRevisions = $unlinked ? $unlinked : [];
    }

    public function source() : string
    {
        return $this->sourceFile;
    }

    public function destination() : string
    {
        return $this->destinationFile;
    }

    public function deletedRevisions() : array
    {
        return $this->deletedRevisions;
    }

    public function matchPattern() : string
    {
        return $this->matchPattern;
    }
}
