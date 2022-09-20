<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_openlms\hook;

/**
 * Hook parent class based on "Event" from PSR-14.
 *
 * Due to class/method naming restrictions and collision with
 * Moodle events the definitions from PSR-14 should be interpreted as:
 *
 *  1. Event --> Hook
 *  2. Listener --> Hook callback
 *  3. Emitter --> Hook emitter
 *  4. Dispatcher --> Hook dispatcher
 *  5. Listener Provider --> Hook callback provider
 *
 * Hook instances may be mutable.
 *
 * Developers are responsible for making sure that hook callbacks may be called
 * safely during upgrades and when plugins are not installed yet.
 * For example, you can use plugin version to find out if plugin was already
 * installed/upgraded, or use $CFG->version if callback needs a core feature.
 *
 * @package   local_openlms
 * @author    Petr Skoda
 * @copyright 2022 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /**
     * Dispatch the hook instance - execute all hook callbacks in order of priority.
     *
     * The extra parameter $throwexceptions==false is intended to be
     * used in special cases where we were wrapping the legacy callbacks
     * with try-catch.
     *
     * @param bool $throwexceptions use false if exceptions should be ignored
     * @return $this
     */
    final public function dispatch(bool $throwexceptions = true): base {
        return manager::get_instance()->dispatch($this, $throwexceptions);
    }

    /**
     * Mandatory hook purpose description in Markdown format
     * used on Hooks overview page.
     *
     * It should include description of callback priority setting
     * rules if applicable.
     *
     * @return string
     */
    abstract public static function get_hook_description(): string;

    /**
     * Returns list of lib.php plugin callbacks that were deprecated by the hook.
     *
     * It is used for automatic debugging messages and if present it
     * also skips relevant legacy callbacks in plugins that implemented callbacks
     * for this hook (to allow plugin compatibility with multiple Moodle branches).
     *
     * @return array
     */
    public static function get_deprecated_plugin_callbacks(): array {
        // Override if hook replaces one or more legacy plugin callbacks.
        return [];
    }
}
