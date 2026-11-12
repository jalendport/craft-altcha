<?php

use jalendport\altcha\services\Altcha;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

test('rendered widget keeps the payload field name', function(): void {
    Craft::$app = (object)['language' => 'en'];

    $service = new class() extends Altcha {
        public function getChallengeUrl(): string
        {
            return '/actions/altcha/challenge';
        }
    };
    $service->setSettings(altchaSettings());

    $method = new ReflectionMethod(Altcha::class, '_widgetAttributes');
    $attributes = $method->invoke($service, []);
    $twig = new Environment(new FilesystemLoader(__DIR__ . '/../../src/templates'));
    $html = $twig->render('_widget.twig', ['attributes' => $attributes]);

    expect($html)->toContain('name="altcha"');
});
