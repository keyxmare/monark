<?php

declare(strict_types=1);

use App\Shared\Application\DTO\ErrorOutput;

describe('ErrorOutput', function () {
    it('stores all properties', function () {
        $errors = ['email' => ['invalid format']];
        $output = new ErrorOutput('Validation error', 422, $errors);

        expect($output->message)->toBe('Validation error');
        expect($output->code)->toBe(422);
        expect($output->errors)->toBe($errors);
    });

    it('defaults errors to empty array', function () {
        $output = new ErrorOutput('Server error', 500);

        expect($output->errors)->toBe([]);
    });

    it('serializes to array', function () {
        $output = new ErrorOutput('Not found', 404, ['id' => ['missing']]);

        expect($output->toArray())->toBe([
            'message' => 'Not found',
            'code' => 404,
            'errors' => ['id' => ['missing']],
        ]);
    });
});
