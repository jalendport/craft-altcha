<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Rename the login and forgot-password settings in project config.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\migrations;

use Craft;
use craft\db\Migration;

/**
 * Renames the native user form settings.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.1.0
 */
class m261112_000000_rename_user_form_settings extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.1.0
     */
    public function safeDown(): bool
    {
        echo "The user form settings migration cannot be reverted.\n";

        return false;
    }

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.1.0
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        if (version_compare($projectConfig->get('plugins.altcha.schemaVersion', true) ?? '0', '1.1.0', '>=')) {
            return true;
        }

        $settings = $projectConfig->get('plugins.altcha.settings') ?? [];
        $changed = false;

        foreach ([
            'protectLogin' => 'enableUserLogin',
            'protectForgotPassword' => 'enableUserForgotPassword',
        ] as $oldName => $newName) {
            if (!array_key_exists($oldName, $settings)) {
                continue;
            }

            if (!array_key_exists($newName, $settings)) {
                $settings[$newName] = $settings[$oldName];
            }

            unset($settings[$oldName]);
            $changed = true;
        }

        if (!$changed) {
            return true;
        }

        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        try {
            $projectConfig->set('plugins.altcha.settings', $settings);
        } finally {
            $projectConfig->muteEvents = $muteEvents;
        }

        return true;
    }
}
