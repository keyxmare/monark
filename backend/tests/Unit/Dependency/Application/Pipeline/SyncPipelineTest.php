<?php

declare(strict_types=1);

use App\Dependency\Application\Pipeline\SyncContext;
use App\Dependency\Application\Pipeline\SyncPipeline;
use App\Dependency\Application\Pipeline\SyncStageInterface;
use App\Shared\Domain\ValueObject\PackageManager;

function makePassthroughStage(): SyncStageInterface
{
    return new class () implements SyncStageInterface {
        public function __invoke(SyncContext $context): SyncContext
        {
            return $context;
        }
    };
}

function makeMutatingStage(string $latestVersion): SyncStageInterface
{
    return new class ($latestVersion) implements SyncStageInterface {
        public function __construct(private readonly string $latestVersion)
        {
        }

        public function __invoke(SyncContext $context): SyncContext
        {
            return $context->withLatestVersion($this->latestVersion);
        }
    };
}

function makeOrderTrackingStage(array &$log, string $label): SyncStageInterface
{
    return new class ($log, $label) implements SyncStageInterface {
        public function __construct(private array &$log, private readonly string $label)
        {
        }

        public function __invoke(SyncContext $context): SyncContext
        {
            $this->log[] = $this->label;

            return $context;
        }
    };
}

describe('SyncPipeline', function () {
    it('runs zero stages and returns initial context', function () {
        $ctx = SyncContext::initial('vue', PackageManager::Npm);
        $pipeline = new SyncPipeline([]);

        $result = $pipeline->process($ctx);

        expect($result->packageName)->toBe('vue')
            ->and($result->latestVersion)->toBeNull();
    });

    it('runs a single passthrough stage unchanged', function () {
        $ctx = SyncContext::initial('vue', PackageManager::Npm);
        $pipeline = new SyncPipeline([\makePassthroughStage()]);

        $result = $pipeline->process($ctx);

        expect($result->latestVersion)->toBeNull();
    });

    it('applies mutations from a stage', function () {
        $ctx = SyncContext::initial('vue', PackageManager::Npm);
        $pipeline = new SyncPipeline([\makeMutatingStage('3.5.0')]);

        $result = $pipeline->process($ctx);

        expect($result->latestVersion)->toBe('3.5.0');
    });

    it('passes output of each stage as input to the next', function () {
        $ctx = SyncContext::initial('vue', PackageManager::Npm);
        $pipeline = new SyncPipeline([
            \makeMutatingStage('1.0.0'),
            \makeMutatingStage('2.0.0'),
        ]);

        $result = $pipeline->process($ctx);

        expect($result->latestVersion)->toBe('2.0.0');
    });

    it('executes stages in order', function () {
        $log = [];
        $ctx = SyncContext::initial('vue', PackageManager::Npm);
        $pipeline = new SyncPipeline([
            \makeOrderTrackingStage($log, 'first'),
            \makeOrderTrackingStage($log, 'second'),
            \makeOrderTrackingStage($log, 'third'),
        ]);

        $pipeline->process($ctx);

        expect($log)->toBe(['first', 'second', 'third']);
    });
});
