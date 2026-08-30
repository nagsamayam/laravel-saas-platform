<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

test('health endpoint is reachable', function () {
    getJson('/api/health')
        ->assertSuccessful()
        ->assertJsonStructure([
            'status',
            'service',
            'timestamp',
            'checks' => [
                'database' => [
                    'status',
                ],
                'redis' => [
                    'status',
                ],
                'cache' => [
                    'status',
                ],
            ],
        ])
        ->assertJsonPath('status', 'ok');
});
