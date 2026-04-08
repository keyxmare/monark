<?php

declare(strict_types=1);

namespace App\Dependency\Application\Pipeline;

use App\Dependency\Domain\DTO\RegistryVersion;
use App\Dependency\Domain\Model\DependencyVersion;
use App\Shared\Domain\ValueObject\PackageManager;

final readonly class SyncContext
{
    private function __construct(
        public string $packageName,
        public PackageManager $packageManager,
        /** @var list<RegistryVersion> */
        public array $registryVersions,
        /** @var list<DependencyVersion> */
        public array $newVersions,
        /** @var list<DependencyVersion> */
        public array $persistedVersions,
        public ?string $latestVersion,
        public ?string $syncId,
        public int $index,
        public int $total,
    ) {
    }

    public static function initial(string $packageName, PackageManager $packageManager): self
    {
        return new self(
            packageName: $packageName,
            packageManager: $packageManager,
            registryVersions: [],
            newVersions: [],
            persistedVersions: [],
            latestVersion: null,
            syncId: null,
            index: 0,
            total: 0,
        );
    }

    /** @param list<RegistryVersion> $versions */
    public function withRegistryVersions(array $versions): self
    {
        return new self(
            packageName: $this->packageName,
            packageManager: $this->packageManager,
            registryVersions: $versions,
            newVersions: $this->newVersions,
            persistedVersions: $this->persistedVersions,
            latestVersion: $this->latestVersion,
            syncId: $this->syncId,
            index: $this->index,
            total: $this->total,
        );
    }

    /** @param list<DependencyVersion> $versions */
    public function withNewVersions(array $versions): self
    {
        return new self(
            packageName: $this->packageName,
            packageManager: $this->packageManager,
            registryVersions: $this->registryVersions,
            newVersions: $versions,
            persistedVersions: $this->persistedVersions,
            latestVersion: $this->latestVersion,
            syncId: $this->syncId,
            index: $this->index,
            total: $this->total,
        );
    }

    /** @param list<DependencyVersion> $versions */
    public function withPersistedVersions(array $versions): self
    {
        return new self(
            packageName: $this->packageName,
            packageManager: $this->packageManager,
            registryVersions: $this->registryVersions,
            newVersions: $this->newVersions,
            persistedVersions: $versions,
            latestVersion: $this->latestVersion,
            syncId: $this->syncId,
            index: $this->index,
            total: $this->total,
        );
    }

    public function withLatestVersion(string $latestVersion): self
    {
        return new self(
            packageName: $this->packageName,
            packageManager: $this->packageManager,
            registryVersions: $this->registryVersions,
            newVersions: $this->newVersions,
            persistedVersions: $this->persistedVersions,
            latestVersion: $latestVersion,
            syncId: $this->syncId,
            index: $this->index,
            total: $this->total,
        );
    }

    public function withProgress(string $syncId, int $index, int $total): self
    {
        return new self(
            packageName: $this->packageName,
            packageManager: $this->packageManager,
            registryVersions: $this->registryVersions,
            newVersions: $this->newVersions,
            persistedVersions: $this->persistedVersions,
            latestVersion: $this->latestVersion,
            syncId: $syncId,
            index: $index,
            total: $total,
        );
    }
}
