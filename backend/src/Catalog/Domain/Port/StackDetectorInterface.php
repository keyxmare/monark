<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Port;

use App\Shared\Domain\DTO\DetectedDependency;
use App\Shared\Domain\DTO\DetectedStack;

interface StackDetectorInterface
{
    /** @return list<string> */
    public function supportedManifests(): array;

    /**
     * @param array<string, string> $manifestContents filename => raw content
     * @return list<DetectedStack>
     */
    public function detect(array $manifestContents): array;

    /**
     * @param array<string, string> $manifestContents filename => raw content
     * @return list<DetectedDependency>
     */
    public function extractDependencies(array $manifestContents): array;

    /**
     * @param list<DetectedStack> $stacks
     * @param array<string, string> $lockVersions package => version
     * @return list<DetectedStack>
     */
    public function enrichStackVersions(array $stacks, array $lockVersions): array;

    /**
     * @param list<DetectedDependency> $dependencies
     * @param array<string, string> $lockVersions package => version
     * @return list<DetectedDependency>
     */
    public function enrichDependencyVersions(array $dependencies, array $lockVersions): array;
}
