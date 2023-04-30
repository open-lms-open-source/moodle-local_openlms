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

namespace local_openlms\notification;

/**
 * Base for classes that describe notifications in a plugin.
 *
 * @package   local_openlms
 * @copyright 2022 Open LMS
 * @author    Petr Skoda
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class manager {
    /**
     * Returns relevant component.
     *
     * @return string
     */
    public static function get_component(): string {
        $parts = explode('\\', static::class);
        return $parts[0];
    }

    /**
     * Returns list of all notifications in plugin.
     * @return array of PHP class names with notificationtype as keys
     */
    abstract public static function get_all_types(): array;

    /**
     * Returns list of candidate types for adding of new notifications.
     *
     * @return array of type names with notificationtype as keys
     */
    abstract public static function get_candidate_types(int $instanceid): array;

    /**
     * Returns notification class for given type string.
     *
     * @param string $notificationtype
     * @return null|string PHP classname
     */
    final public static function get_classname(string $notificationtype): ?string {
        $types = static::get_all_types();
        if (isset($types[$notificationtype])) {
            return $types[$notificationtype];
        }
        return null;
    }

    /**
     * Returns name of instance for notifications.
     *
     * @param int $instanceid
     * @return string|null
     */
    abstract public static function get_instance_name(int $instanceid): ?string;

    /**
     * Returns context of instance for notifications.
     *
     * @param int $instanceid
     * @return null|\context
     */
    abstract public static function get_instance_context(int $instanceid): ?\context;

    /**
     * Returns url of UI that shows all plugin notifications for given instance id.
     *
     * See @link static::render_notifications() method.
     *
     * @param int $instanceid
     * @return \moodle_url|null
     */
    abstract public static function get_instance_management_url(int $instanceid): ?\moodle_url;

    /**
     * Can the current user view instance notifications?
     *
     * @param int $instanceid
     * @return bool
     */
    abstract public static function can_view(int $instanceid): bool;

    /**
     * Can the current user manage instance notifications?
     *
     * @param int $instanceid
     * @return bool
     */
    abstract public static function can_manage(int $instanceid): bool;

    /**
     * Set up notification/view.php page,
     * such as navigation and page name.
     *
     * @param \stdClass $notification
     * @return void
     */
    abstract public static function setup_view_page(\stdClass $notification): void;

    /**
     * Render list of all instance notifications and management UI.
     *
     * @param int $instanceid
     * @return string
     */
    public static function render_notifications(int $instanceid): string {
        global $DB, $PAGE;

        /** @var \local_openlms\output\dialog_form\renderer $dialogformoutput */
        $dialogformoutput = $PAGE->get_renderer('local_openlms', 'dialog_form');

        $component = static::get_component();
        $notifications = $DB->get_records('local_openlms_notifications', ['instanceid' => $instanceid, 'component' => $component]);

        $canmanage = static::can_manage($instanceid);
        $canview = static::can_view($instanceid);
        $types = static::get_all_types();

        foreach ($notifications as $notification) {
            /** @var class-string<\local_openlms\notification\notificationtype> $classname */
            $classname = $types[$notification->notificationtype] ?? null;
            if ($classname) {
                $notification->name = $classname::get_name();
            } else {
                $notification->name = $notification->notificationtype
                    .' <span class="badge badge-danger">'.get_string('error').'</span>';
            }
        }

        $rows = [];
        foreach ($notifications as $notification) {
            $row = [];
            /** @var class-string<\local_openlms\notification\notificationtype> $classname */
            $classname = $types[$notification->notificationtype] ?? null;
            $name = $notification->name;
            if ($classname) {
                if (!$notification->enabled) {
                    $name = '<span class="dimmed_text">' . $name . '</span>';
                }
                $url = new \moodle_url('/local/openlms/notification/view.php', ['id' => $notification->id]);
                $name = \html_writer::link($url, $name);
                $row[] = $name;
            } else {
                $row[] = $name;
            }
            $row[] = $notification->custom ? get_string('yes') : get_string('no');
            if ($classname) {
                $row[] = $notification->enabled ? get_string('yes') : get_string('no');
            } else {
                $row[] = '';
            }
            if ($canmanage) {
                $actions = [];
                if (!$classname) {
                    // Do not show the delete link here if they can go the notification details page,
                    // we do not want to encourage users to randomly deleting notification and loosing
                    // track of who was already notified.
                    $url = new \moodle_url('/local/openlms/notification/delete.php', ['id' => $notification->id]);
                    $icon = new \local_openlms\output\dialog_form\icon($url, 'i/delete', get_string('notification_delete', 'local_openlms'));
                    $actions[] = $dialogformoutput->render($icon);
                }
                if ($classname) {
                    $url = new \moodle_url('/local/openlms/notification/update.php', ['id' => $notification->id]);
                    $icon = new \local_openlms\output\dialog_form\icon($url, 'i/edit', get_string('notification_update', 'local_openlms'));
                    $actions[] = $dialogformoutput->render($icon);
                }
                $row[] = implode('', $actions);
            }
            $rows[] = $row;
        }

        if (static::get_candidate_types($instanceid)) {
            $url = new \moodle_url('/local/openlms/notification/create.php', ['instanceid' => $instanceid, 'component' => $component]);
            $icon = new \local_openlms\output\dialog_form\icon($url, 'e/insert', get_string('notification_create', 'local_openlms'));
            $icon = $dialogformoutput->render($icon);
            $cell = new \html_table_cell($icon);
            if ($canmanage) {
                $cell->colspan = 4;
            } else {
                $cell->colspan = 3;
            }
            $rows[] = [$cell];
        }

        $table = new \html_table();
        $table->id = static::get_component() . '_notifications';
        $table->head = [
            get_string('notification', 'local_openlms'),
            get_string('notification_custom', 'local_openlms'),
            get_string('notification_enabled', 'local_openlms'),
        ];
        if ($canmanage) {
            $table->head[] = get_string('actions');
        }
        $table->data = $rows;
        $table->attributes['class'] = 'admintable generaltable';
        $result = \html_writer::table($table);

        return $result;
    }
}
