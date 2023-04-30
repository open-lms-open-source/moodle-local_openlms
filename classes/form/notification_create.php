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
 * Notification create form.
 *
 * @package    local_openlms
 * @copyright  2023 Open LMS
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class notification_create extends \local_openlms\dialog_form {
    protected function definition() {
        $mform = $this->_form;
        $component = $this->_customdata['component'];
        $instanceid = $this->_customdata['instanceid'];
        /** @var class-string<\local_openlms\notification\manager> $manager */
        $manager = $this->_customdata['manager'];

        $mform->addElement('hidden', 'instanceid');
        $mform->setType('instanceid', PARAM_INT);
        $mform->setConstant('instanceid', $instanceid);

        $mform->addElement('hidden', 'component');
        $mform->setType('component', PARAM_COMPONENT);
        $mform->setConstant('component', $component);

        $instance = $manager::get_instance_name($instanceid);
        $mform->addElement('static', 'staticinstance', get_string('notification_instance', 'local_openlms'), $instance);

        $types = $manager::get_candidate_types($instanceid);
        $elements = [];
        foreach ($types as $type => $typename) {
            $elements[] = $mform->createElement('checkbox', $type, $typename);
        }
        $mform->addGroup($elements, 'types', get_string('notification_types', 'local_openlms'), '<br />');

        $mform->addElement('advcheckbox', 'enabled', get_string('notification_enabled', 'local_openlms'), ' ');
        $mform->setDefault('enabled', 1);

        $this->add_action_buttons(true, get_string('notification_create', 'local_openlms'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['types'])) {
            $errors['types'] = get_string('required');
        }

        return $errors;
    }
}
