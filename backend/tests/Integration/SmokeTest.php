<?php

declare(strict_types=1);

use App\Tests\Helpers\DatabaseHelper;
use Doctrine\ORM\EntityManagerInterface;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
});

it('can boot kernel and access entity manager', function () {
    $em = $this->getEntityManager();

    expect($em)->toBeInstanceOf(EntityManagerInterface::class);
});
