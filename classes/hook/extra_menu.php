<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_openlms\hook;

use local_openlms\output\extra_menu\dropdown;

/**
 * Base class for extra menu hooks.
 *
 * Extra menus are commonly used in management UIs in Open LMS.
 * In tabbed interfaces there might be tab specific or managed element
 * specific extra menus.
 *
 * Plugins may define one or more extra menu hooks that contain
 * additional page context information.
 *
 * @package    local_openlms
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class extra_menu {
    /** @var dropdown */
    protected $dropdown;

    /**
     * Constructor.
     *
     * @param dropdown $dropdown
     */
    public function __construct($dropdown) {
        if (!$dropdown instanceof dropdown) {
            debugging('Constructor of extra_menu now expects dropdown instead of page.', DEBUG_DEVELOPER);
            $dropdown = new dropdown(get_string('extramenu', 'local_openlms'));
        }
        $this->dropdown = $dropdown;
    }

    /**
     * Returns dropdown instance.
     *
     * @return dropdown
     */
    public function get_dropdown(): dropdown {
        return $this->dropdown;
    }

    /**
     * Add standard link item to extra menu.
     *
     * @param string $label
     * @param \moodle_url $url
     */
    public function add_item(string $label, \moodle_url $url): void {
        $this->dropdown->add_item($label,$url);
    }

    /**
     * Add divider element.
     */
    public function add_divider(): void {
        $this->dropdown->add_divider();
    }

    /**
     * Add link that opens dialog_form.
     *
     * @param \local_openlms\output\dialog_form\link $link
     */
    public function add_dialog_form(\local_openlms\output\dialog_form\link $link): void {
        $this->dropdown->add_dialog_form($link);
    }

    /**
     * Are there any items in the dropdown?
     *
     * @return bool
     */
    public function has_items(): bool {
        return $this->dropdown->has_items();
    }

    /**
     * Returns collected extra items.
     *
     * @deprecated to be removed in Open LMS Work 4.0
     *
     * @return array
     */
    public function get_items(): array {
        global $OUTPUT;

        debugging('extra_menu::get_items() is deprecated, render extra_menu::get_dropdown() instead', DEBUG_DEVELOPER);
        return $this->dropdown->export_for_template($OUTPUT)['items'];
    }
}
