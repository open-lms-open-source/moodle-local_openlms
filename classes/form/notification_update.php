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
 * Notification update form.
 *
 * @package    local_openlms
 * @copyright  2023 Open LMS
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class notification_update extends \local_openlms\dialog_form {
    protected function definition() {
        $mform = $this->_form;
        $notification = $this->_customdata['notification'];
        /** @var class-string<\local_openlms\notification\manager> $manager */
        $manager = $this->_customdata['manager'];
        /** @var class-string<\local_openlms\notification\notificationtype> $classname */
        $classname = $manager::get_classname($notification->notificationtype);

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

        $mform->addElement('advcheckbox', 'enabled', get_string('notification_enabled', 'local_openlms'), ' ');
        $mform->setDefault('enabled', $notification->enabled);

        // TODO: add aux data support

        $mform->addElement('advcheckbox', 'custom', get_string('notification_custom', 'local_openlms'), ' ');
        $mform->setDefault('custom', $notification->custom);

        $subject = '';
        $body = '';
        if ($notification->custom) {
            if ($notification->customjson) {
                $decoded = json_decode($notification->customjson, true);
                $subject = $decoded['subject'] ?? '';
                $body = $decoded['body'] ?? '';
            }
        } else {
            $subject = $classname::get_default_subject();
            $body = markdown_to_html($classname::get_default_body());
            $body = str_replace('{$a->', '{$a-&gt;', $body);
        }

        $mform->addElement('text', 'subject', get_string('notification_subject', 'local_openlms'), ['size' => 100]);
        $mform->setType('subject', PARAM_RAW);
        $mform->setDefault('subject', $subject);
        $mform->hideIf('subject', 'custom', 'notchecked');

        // Hack: put editor into group to work around hideIf editor incompatibility.
        $editor = $mform->createElement('editor', 'body', get_string('notification_body', 'local_openlms'));
        $mform->addElement('group', 'bodygroup', get_string('notification_body', 'local_openlms'), [$editor], null, false);
        $mform->setType('body', PARAM_RAW);
        $mform->setDefault('body', ['text' => $body, 'format' => FORMAT_HTML]);
        $mform->hideIf('bodygroup', 'custom', 'notchecked');

        $this->add_action_buttons(true, get_string('notification_update', 'local_openlms'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
