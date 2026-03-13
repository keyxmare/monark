<?php

declare(strict_types=1);

use App\Dependency\Application\DTO\VulnerabilityListOutput;
use App\Dependency\Application\Query\ListVulnerabilitiesQuery;
use App\Dependency\Application\QueryHandler\ListVulnerabilitiesHandler;
use App\Dependency\Domain\Model\Dependency;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\Model\Vulnerability;
use App\Dependency\Domain\Model\VulnerabilityStatus;
use App\Dependency\Domain\Repository\VulnerabilityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubListVulnRepo(array $vulnerabilities = [], int $count = 0): VulnerabilityRepositoryInterface
{
    return new class ($vulnerabilities, $count) implements VulnerabilityRepositoryInterface {
        public function __construct(private readonly array $vulnerabilities, private readonly int $count)
        {
        }
        public function findById(Uuid $id): ?Vulnerability
        {
            return null;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return $this->vulnerabilities;
        }
        public function count(): int
        {
            return $this->count;
        }
        public function save(Vulnerability $vulnerability): void
        {
        }
    };
}

describe('ListVulnerabilitiesHandler', function () {
    it('returns paginated vulnerabilities', function () {
        $dependency = Dependency::create(
            name: 'symfony/framework-bundle',
            currentVersion: '7.2.0',
            latestVersion: '8.0.0',
            ltsVersion: '7.4.0',
            packageManager: PackageManager::Composer,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: Uuid::v7(),
        );

        $vuln1 = Vulnerability::create(
            cveId: 'CVE-2026-12345',
            severity: Severity::High,
            title: 'RCE vulnerability',
            description: 'Remote code execution.',
            patchedVersion: '7.2.1',
            status: VulnerabilityStatus::Open,
            detectedAt: new \DateTimeImmutable('2026-01-15T10:00:00+00:00'),
            dependency: $dependency,
        );
        $vuln2 = Vulnerability::create(
            cveId: 'CVE-2026-67890',
            severity: Severity::Medium,
            title: 'XSS vulnerability',
            description: 'Cross-site scripting.',
            patchedVersion: '7.2.2',
            status: VulnerabilityStatus::Acknowledged,
            detectedAt: new \DateTimeImmutable('2026-02-01T10:00:00+00:00'),
            dependency: $dependency,
        );

        $handler = new ListVulnerabilitiesHandler(\stubListVulnRepo([$vuln1, $vuln2], 2));
        $result = $handler(new ListVulnerabilitiesQuery(1, 20));

        expect($result)->toBeInstanceOf(VulnerabilityListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
    });

    it('returns empty list when no vulnerabilities', function () {
        $handler = new ListVulnerabilitiesHandler(\stubListVulnRepo([], 0));
        $result = $handler(new ListVulnerabilitiesQuery());

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
    });
});
