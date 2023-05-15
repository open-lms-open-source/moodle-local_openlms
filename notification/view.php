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
 * Notification details page.
 *
 * @package    local_openlms
 * @copyright  2023 Open LMS
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

if (!$manager::can_view($notification->instanceid)) {
    redirect('/');
}

$context = $manager::get_instance_context($notification->instanceid);

$PAGE->set_context($context);
$PAGE->set_url('/local/openlms/notification/view.php', ['id' => $notification->id]);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('notification', 'local_openlms'));
$PAGE->set_title(get_string('notification', 'local_openlms'));

/** @var class-string<\local_openlms\notification\notificationtype> $classname */
$classname = $manager::get_classname($notification->notificationtype);
if (!$classname || !class_exists($classname)) {
    throw new invalid_parameter_exception('Unknown notification type');
}

$manager::setup_view_page($notification);

/** @var \local_openlms\output\dialog_form\renderer $dialogformoutput */
$dialogformoutput = $PAGE->get_renderer('local_openlms', 'dialog_form');

echo '<dl class="row">';
$name = $classname::get_name();
echo '<dt class="col-3">' . get_string('notification', 'local_openlms') . ':</dt><dd class="col-9">' . $name . '</dd>';
$instancename = $manager::get_instance_name($notification->instanceid);
$manageurl = $manager::get_instance_management_url($notification->instanceid);
if ($manageurl) {
    $instancename = html_writer::link($manageurl, $instancename);
}
echo '<dt class="col-3">' . get_string('notification_instance', 'local_openlms') . ':</dt><dd class="col-9">' . $instancename . '</dd>';
$description = $classname::get_description();
$enabled = $notification->enabled ? get_string('yes') : get_string('no');
echo '<dt class="col-3">' . get_string('notification_enabled', 'local_openlms') . ':</dt><dd class="col-9">' . $enabled  . '</dd>';
echo '<dt class="col-3">' . get_string('description') . ':</dt><dd class="col-9">' . $description  . '</dd>';
$custom = $notification->custom ? get_string('yes') : get_string('no');
echo '<dt class="col-3">' . get_string('notification_custom', 'local_openlms') . ':</dt><dd class="col-9">' . $custom  . '</dd>';
$a = [];
$subject = $classname::get_subject($notification, $a);
echo '<dt class="col-3">' . get_string('notification_subject', 'local_openlms') . ':</dt><dd class="col-9">' . $subject  . '</dd>';
$body = $classname::get_body($notification, $a);
echo '<dt class="col-3">' . get_string('notification_body', 'local_openlms') . ':</dt><dd class="col-9">' . $body  . '</dd>';
echo '</dl>';

$buttons = [];

if ($manager::can_manage($notification->instanceid)) {
    $url = new \moodle_url('/local/openlms/notification/delete.php', ['id' => $notification->id]);
    $button = new \local_openlms\output\dialog_form\button($url, get_string('notification_delete', 'local_openlms'));
    $button->set_after_submit($button::AFTER_SUBMIT_REDIRECT);
    $buttons[] = $dialogformoutput->render($button);
    if ($classname) {
        $url = new \moodle_url('/local/openlms/notification/update.php', ['id' => $notification->id]);
        $button = new \local_openlms\output\dialog_form\button($url, get_string('notification_update', 'local_openlms'));
        $buttons[] = $dialogformoutput->render($button);
    }
}
if ($manageurl) {
    $button = new single_button($manageurl, get_string('back'), 'get');
    $buttons[] = ' ' . $OUTPUT->render($button);
}

if ($buttons) {
    echo $OUTPUT->box(implode('', $buttons), 'buttons');
}
echo $OUTPUT->footer();
