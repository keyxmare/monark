<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model;

trait RecordsDomainEvents
{
    /** @var list<object> */
    private array $domainEvents = [];

    protected function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return list<object> */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
