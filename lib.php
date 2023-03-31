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

/**
 * Utility code for OpenLMS plugins.
 *
 * @package    local_openlms
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function local_openlms_extend_navigation(global_navigation $navigation) {
    if (isloggedin() && !isguestuser()
        && file_exists(__DIR__ . '/../../admin/tool/udplans/version.php')
    ) {
        if (\tool_udplans\local\util::udplans_active()) {
            if (\tool_udplans\local\plan::get_my_plans()) {
                $n = $navigation->create(get_string('myplans', 'tool_udplans'),
                    new moodle_url('/admin/tool/udplans/plans_my.php'),
                    global_navigation::TYPE_CUSTOM,
                    null,
                    'myplans',
                    new pix_icon('myplans', '', 'tool_udplans'));
                $n->showinflatnavigation = true;
                $navigation->add_node($n, 'mycourses');
            }
        }
    }
}
