<?php

declare(strict_types=1);

use App\Dependency\Application\Pipeline\Stage\NotifyProgressStage;
use App\Dependency\Application\Pipeline\SyncContext;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

describe('NotifyProgressStage', function () {
    it('publishes Mercure update when syncId is set', function () {
        $hub = test()->createMock(HubInterface::class);
        $hub->expects(test()->once())->method('publish')
            ->with(test()->callback(function (Update $update) {
                $data = \json_decode((string) $update->getData(), true);
                expect($data['syncId'])->toBe('sync-001');
                expect($data['status'])->toBe('running');

                return true;
            }));

        $stage = new NotifyProgressStage($hub);
        $ctx = SyncContext::initial('vue', PackageManager::Npm)
            ->withProgress(syncId: 'sync-001', index: 2, total: 5);

        $stage($ctx);
    });

    it('publishes completed status when index equals total', function () {
        $hub = test()->createMock(HubInterface::class);
        $hub->expects(test()->once())->method('publish')
            ->with(test()->callback(function (Update $update) {
                $data = \json_decode((string) $update->getData(), true);
                expect($data['status'])->toBe('completed');

                return true;
            }));

        $stage = new NotifyProgressStage($hub);
        $ctx = SyncContext::initial('vue', PackageManager::Npm)
            ->withProgress(syncId: 'sync-001', index: 5, total: 5);

        $stage($ctx);
    });

    it('does not publish when syncId is null', function () {
        $hub = test()->createMock(HubInterface::class);
        $hub->expects(test()->never())->method('publish');

        $stage = new NotifyProgressStage($hub);
        $ctx = SyncContext::initial('vue', PackageManager::Npm);

        $stage($ctx);
    });

    it('does not publish when total is zero', function () {
        $hub = test()->createMock(HubInterface::class);
        $hub->expects(test()->never())->method('publish');

        $stage = new NotifyProgressStage($hub);
        $ctx = SyncContext::initial('vue', PackageManager::Npm)
            ->withProgress(syncId: 'sync-001', index: 0, total: 0);

        $stage($ctx);
    });
});
