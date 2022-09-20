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

namespace local_openlms\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Form that allows admins to remove all hook callback overrides.
 *
 * @package   local_openlms
 * @author    Petr Skoda
 * @copyright 2022 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callback_reset extends \moodleform {
    /**
     * Hook override reset form.
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'action', 'reset');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('static', 'hookinfo', get_string('hookname', 'local_openlms'), s($this->_customdata['hook']));
        $mform->addElement('hidden', 'hook', $this->_customdata['hook']);
        $mform->setType('hook', PARAM_RAW);

        $mform->addElement('static', 'callbackinfo', get_string('hookcallback', 'local_openlms'), s($this->_customdata['callback']));
        $mform->addElement('hidden', 'callback', $this->_customdata['callback']);
        $mform->setType('callback', PARAM_RAW);

        $mform->addElement('text', 'priority', get_string('hookpriority', 'local_openlms'));
        $mform->setType('priority', PARAM_INT);
        $mform->setDefault('priority', $this->_customdata['priority']);
        $mform->hardFreeze('priority');

        $mform->addElement('advcheckbox', 'disabled', get_string('disabled', 'local_openlms'));
        $mform->setDefault('disabled', $this->_customdata['disabled']);
        $mform->hardFreeze('disabled');

        $this->add_action_buttons(true, get_string('hookreset', 'local_openlms'));
    }
}
