<?php

declare(strict_types=1);

namespace App\Activity\Domain\Model;

enum SyncTaskType: string
{
    case OutdatedDependency = 'outdated_dependency';
    case Vulnerability = 'vulnerability';
    case StackUpgrade = 'stack_upgrade';
    case NewDependency = 'new_dependency';
    case StalePr = 'stale_pr';
}
