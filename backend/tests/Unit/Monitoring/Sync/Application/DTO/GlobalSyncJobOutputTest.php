<?php

declare(strict_types=1);

use App\Monitoring\Sync\Application\DTO\GlobalSyncJobOutput;

describe('GlobalSyncJobOutput', function (): void {
    it('exposes constructor properties as public readonly', function (): void {
        $output = new GlobalSyncJobOutput(
            syncId: '0191d61e-f000-7000-8000-000000000000',
            status: 'running',
            currentStep: 2,
        );

        expect($output->syncId)->toBe('0191d61e-f000-7000-8000-000000000000');
        expect($output->status)->toBe('running');
        expect($output->currentStep)->toBe(2);
    });
});
