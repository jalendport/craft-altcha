<?php

use jalendport\altcha\helpers\ActionAllowlist;

test('exact action paths match', function(): void {
    expect(ActionAllowlist::matches('freeform/submit', ['freeform/submit']))->toBeTrue();
});

test('trailing wildcards match action path prefixes', function(): void {
    expect(ActionAllowlist::matches('campaign/forms/subscribe', ['campaign/forms/*']))->toBeTrue();
});

test('unlisted action paths do not match', function(): void {
    expect(ActionAllowlist::matches('contact-form/send', ['freeform/submit', 'campaign/forms/*']))->toBeFalse();
});

test('Altcha actions are always exempt', function(string $actionUniqueId): void {
    expect(ActionAllowlist::matches($actionUniqueId, ['*']))->toBeFalse();
})->with([
    'module action' => 'altcha',
    'challenge action' => 'altcha/challenge',
]);

test('action paths and patterns are normalized for case and slashes', function(): void {
    expect(ActionAllowlist::matches(' /FREEFORM/SUBMIT/ ', ['/freeform/submit/']))->toBeTrue();
});
