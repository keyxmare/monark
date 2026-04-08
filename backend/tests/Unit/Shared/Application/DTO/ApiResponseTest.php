<?php

declare(strict_types=1);

use App\Shared\Application\DTO\ApiResponse;
use App\Shared\Application\DTO\ErrorOutput;

describe('ApiResponse', function () {
    it('creates success response with data', function () {
        $response = ApiResponse::success(['id' => 1, 'name' => 'test']);

        expect($response->success)->toBeTrue();
        expect($response->data)->toBe(['id' => 1, 'name' => 'test']);
        expect($response->error)->toBeNull();
    });

    it('creates success response without data', function () {
        $response = ApiResponse::success();

        expect($response->success)->toBeTrue();
        expect($response->data)->toBeNull();
        expect($response->error)->toBeNull();
    });

    it('creates error response with default code', function () {
        $response = ApiResponse::error('Something went wrong');

        expect($response->success)->toBeFalse();
        expect($response->data)->toBeNull();
        expect($response->error)->toBeInstanceOf(ErrorOutput::class);
        expect($response->error->message)->toBe('Something went wrong');
        expect($response->error->code)->toBe(400);
        expect($response->error->errors)->toBe([]);
    });

    it('creates error response with custom code and errors', function () {
        $errors = ['field' => ['required']];
        $response = ApiResponse::error('Validation failed', 422, $errors);

        expect($response->error->code)->toBe(422);
        expect($response->error->errors)->toBe($errors);
    });

    it('serializes success to array', function () {
        $response = ApiResponse::success('data');
        $arr = $response->toArray();

        expect($arr)->toBe([
            'success' => true,
            'data' => 'data',
            'error' => null,
        ]);
    });

    it('serializes error to array', function () {
        $response = ApiResponse::error('Fail', 500);
        $arr = $response->toArray();

        expect($arr['success'])->toBeFalse();
        expect($arr['data'])->toBeNull();
        expect($arr['error'])->toBe([
            'message' => 'Fail',
            'code' => 500,
            'errors' => [],
        ]);
    });
});
