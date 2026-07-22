<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * The bundled ALTCHA widget script.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\web\assets\widget;

use craft\web\AssetBundle;
use yii\web\View;

/**
 * The front-end ALTCHA widget asset bundle.
 *
 * `dist/altcha.min.js` is vendored verbatim from the npm package `altcha`
 * version **3.2.1** (`dist/main/altcha.min.js`). That is the standard bundle,
 * which inlines its own styles and proof-of-work workers and solves the
 * `PBKDF2/SHA-256` challenges this plugin issues, so no sibling files are
 * needed. Re-vendor by copying the file from a newer tarball and updating the
 * version above.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class WidgetAsset extends AssetBundle
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $js = [
        // The widget is an ES module custom element; deferring keeps it from
        // blocking the parser while still upgrading `<altcha-widget>` on load.
        ['altcha.min.js', 'type' => 'module', 'defer' => true],
    ];

    /**
     * @inheritdoc
     */
    public $jsOptions = [
        'position' => View::POS_HEAD,
    ];

    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';
}
