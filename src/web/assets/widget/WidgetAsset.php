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
 * `dist/altcha.i18n.min.js` is vendored verbatim from the npm package `altcha`
 * version **3.6.0** (`dist/main/altcha.i18n.min.js`). It includes the translations,
 * inlines its own styles and proof-of-work workers, and solves the
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
     * @since 1.0.0
     */
    public $js = [
        // The widget is an ES module custom element; deferring keeps it from
        // blocking the parser while still upgrading `<altcha-widget>` on load.
        ['altcha.i18n.min.js', 'type' => 'module', 'defer' => true],
    ];

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public $jsOptions = [
        'position' => View::POS_HEAD,
    ];

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public $sourcePath = '@jalendport/altcha/web/assets/widget/dist';
}
