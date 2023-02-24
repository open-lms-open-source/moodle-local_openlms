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

namespace local_openlms\form;

/**
 * Notification delete form.
 *
 * @package    local_openlms
 * @copyright  2023 Open LMS
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class notification_delete extends \local_openlms\dialog_form {
    protected function definition() {
        $mform = $this->_form;
        $notification = $this->_customdata['notification'];
        /** @var class-string<\local_openlms\notification\manager> $manager */
        $manager = $this->_customdata['manager'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $notification->id);

        $instance = $manager::get_instance_name($notification->instanceid);
        $mform->addElement('static', 'staticinstance', get_string('notification_instance', 'local_openlms'), $instance);

        $types = $manager::get_all_types();
        $type = $types[$notification->notificationtype] ?? null;
        if ($type) {
            $type = $type::get_name();
        } else {
            $type = get_string('error');
        }
        $mform->addElement('static', 'staticnotificationtype', get_string('notification_type', 'local_openlms'), $type);

        $warning = '<em>' . get_string('notification_delete_confirm', 'local_openlms') . '</em>';
        $mform->addElement('static', 'staticwarning', '', $warning);

        $this->add_action_buttons(true, get_string('notification_delete', 'local_openlms'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
