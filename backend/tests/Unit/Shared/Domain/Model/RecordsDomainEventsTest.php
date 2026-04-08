<?php

declare(strict_types=1);

use App\Shared\Domain\Model\RecordsDomainEvents;

function createRecordingEntity(): object
{
    return new class () {
        use RecordsDomainEvents;

        public function doSomething(): void
        {
            $this->recordEvent(new \stdClass());
        }
    };
}

describe('RecordsDomainEvents', function () {
    it('starts with no events', function () {
        $entity = \createRecordingEntity();

        expect($entity->pullDomainEvents())->toBeEmpty();
    });

    it('records events', function () {
        $entity = \createRecordingEntity();
        $entity->doSomething();
        $entity->doSomething();

        expect($entity->pullDomainEvents())->toHaveCount(2);
    });

    it('clears events after pull', function () {
        $entity = \createRecordingEntity();
        $entity->doSomething();
        $entity->pullDomainEvents();

        expect($entity->pullDomainEvents())->toBeEmpty();
    });
});
