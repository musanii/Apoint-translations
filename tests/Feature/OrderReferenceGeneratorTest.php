<?php

use App\Services\OrderReferenceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('generates the first order reference for the current year', function () {
    Carbon::setTestNow('2026-11-09 12:00:00');

    $generator = app(OrderReferenceGenerator::class);

    expect($generator->generate())->toBe('APT-O-2026-000001');

    Carbon::setTestNow();
});

it('increments the order reference number sequentially', function () {
    Carbon::setTestNow('2026-11-09 12:00:00');

    $generator = app(OrderReferenceGenerator::class);

    expect($generator->generate())->toBe('APT-O-2026-000001')
        ->and($generator->generate())->toBe('APT-O-2026-000002')
        ->and($generator->generate())->toBe('APT-O-2026-000003');

    Carbon::setTestNow();
});

it('starts a new sequence for a new year', function () {
    Carbon::setTestNow('2026-12-31 23:59:00');

    $generator = app(OrderReferenceGenerator::class);

    expect($generator->generate())->toBe('APT-O-2026-000001');

    Carbon::setTestNow('2027-01-01 00:01:00');

    expect($generator->generate())->toBe('APT-O-2027-000001');

    Carbon::setTestNow();
});