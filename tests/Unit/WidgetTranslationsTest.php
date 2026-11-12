<?php

use jalendport\altcha\services\Altcha;

test('widget string keys match the bundled English translation', function(): void {
    $bundle = file_get_contents(__DIR__ . '/../../src/web/assets/widget/dist/altcha.i18n.min.js');

    expect($bundle)->not->toBeFalse();
    expect(preg_match('/i18n\.set\("en",\{(.*?)\}\);/s', $bundle, $translation))->toBe(1);

    $pattern = <<<'REGEX'
        /(?:^|,)(\w+):(?:"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*')/
        REGEX;
    preg_match_all($pattern, $translation[1], $matches);

    $bundledKeys = $matches[1];
    $supportedKeys = Altcha::WIDGET_STRING_KEYS;
    sort($bundledKeys);
    sort($supportedKeys);

    expect($bundledKeys)->toBe($supportedKeys);
});
