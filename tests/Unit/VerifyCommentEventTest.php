<?php

use jalendport\altcha\events\VerifyCommentEvent;

test('comment verification is not skipped by default', function(): void {
    expect(new VerifyCommentEvent()->skipVerification)->toBeFalse();
});

test('comment verification can be marked to skip', function(): void {
    $event = new VerifyCommentEvent();
    $event->skipVerification = true;

    expect($event->skipVerification)->toBeTrue();
});
