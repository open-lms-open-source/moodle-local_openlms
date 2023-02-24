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

/**
 * Update existing notification.
 *
 * @package    local_openlms
 * @copyright  2022 Open LMS
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_openlms\notification\util;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

if (!empty($_SERVER['HTTP_X_LEGACY_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}

require('../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$notification = $DB->get_record('local_openlms_notifications', ['id' => $id], '*', MUST_EXIST);

/** @var class-string<\local_openlms\notification\manager> $manager */
$manager = \local_openlms\notification\util::get_manager_classname($notification->component);
if (!$manager) {
    throw new invalid_parameter_exception('Invalid notification component');
}

$returnurl = $manager::get_instance_management_url($notification->instanceid);
if (!$manager::can_manage($notification->instanceid)) {
    redirect($returnurl);
}

$context = $manager::get_instance_context($notification->instanceid);

$PAGE->set_context($context);
$PAGE->set_url('/local/openlms/notification/update.php', ['id' => $notification->id]);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('notification_update', 'local_openlms'));
$PAGE->set_title(get_string('notification_update', 'local_openlms'));

$form = new \local_openlms\form\notification_update(null, ['notification' => $notification, 'manager' => $manager]);
if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    $notification = util::notification_update((array)$data);
    $returnurl = new moodle_url('/local/openlms/notification/view.php', ['id' => $notification->id]);
    $form->redirect_submitted($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('notification_update', 'local_openlms'));
echo $form->render();
echo $OUTPUT->footer();
