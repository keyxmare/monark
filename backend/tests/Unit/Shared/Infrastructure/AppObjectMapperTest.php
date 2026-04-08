<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Mapper\AppObjectMapper;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

it('delegates mapping to symfony object mapper', function () {
    $source = new \stdClass();
    $target = new \stdClass();

    $inner = $this->createMock(ObjectMapperInterface::class);
    $inner->expects($this->once())
        ->method('map')
        ->with($source, \stdClass::class)
        ->willReturn($target);

    $mapper = new AppObjectMapper($inner);
    $result = $mapper->map($source, \stdClass::class);

    expect($result)->toBe($target);
});
