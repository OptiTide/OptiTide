<?php

use App\Support\Money;

test('constructs from major units', function () {
    $money = Money::fromMajor(2500, 'AUD');

    expect($money->amount)->toBe(250_000)
        ->and($money->major())->toBe(2500.0)
        ->and($money->currency)->toBe('AUD');
});

test('formats as australian currency', function () {
    expect(Money::fromMajor(2500)->format())->toBe('$2,500.00')
        ->and(Money::fromMajor(25)->format())->toBe('$25.00');
});

test('adds and subtracts amounts in the same currency', function () {
    $a = new Money(10_000);
    $b = new Money(2_500);

    expect($a->add($b)->amount)->toBe(12_500)
        ->and($a->subtract($b)->amount)->toBe(7_500);
});

test('rejects operations across currencies', function () {
    (new Money(100, 'AUD'))->add(new Money(100, 'USD'));
})->throws(InvalidArgumentException::class);

test('calculates basis-point percentages for commissions', function () {
    // 10% of $2,500.00
    expect((new Money(250_000))->percentage(1_000)->amount)->toBe(25_000)
        // Rounds (not truncates) when the split is not exact: 10% of $249.99
        ->and((new Money(24_999))->percentage(1_000)->amount)->toBe(2_500);
});

test('avoids float drift when constructing from major units', function () {
    expect(Money::fromMajor(19.99)->amount)->toBe(1_999)
        ->and(Money::fromMajor('0.1')->add(Money::fromMajor('0.2'))->amount)->toBe(30);
});
