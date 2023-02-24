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
 * Component notification management.
 *
 * @package   local_openlms
 * @copyright 2022 Open LMS
 * @author    Petr Skoda
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class util {
    /** @var \moodle_text_filter[] cache of multilang filters */
    private static $filters = null;

    /**
     * Returns classname for notification manager.
     *
     * @param string $component
     * @return string|null PHP class name, null if not exists
     */
    public static function get_manager_classname(string $component): ?string {
        $classname = "$component\\local\\notification_manager";
        if (class_exists($classname)) {
            return $classname;
        }
        return null;
    }

    /**
     * Create a new instance notification.
     *
     * @param array $data
     * @return \stdClass
     */
    public static function notification_create(array $data): \stdClass {
        global $DB;

        $data = (object)$data;

        if (empty($data->component)) {
            throw new \invalid_parameter_exception('Notification component is required');
        }
        /** @var class-string<manager> $manager */
        $manager = self::get_manager_classname($data->component);
        if (!$manager) {
            throw new \invalid_parameter_exception('Invalid notification component');
        }
        if (empty($data->notificationtype)) {
            throw new \invalid_parameter_exception('Notification type is required');
        }
        $classname = $manager::get_classname($data->notificationtype);
        if (!$classname) {
            throw new \invalid_parameter_exception('Invalid notification type');
        }
        if (empty($data->instanceid)) {
            throw new \invalid_parameter_exception('Invalid notification instanceid');
        }
        if (empty($data->enabled)) {
            $data->enabled = 0;
        } else {
            $data->enabled = 1;
        }

        if (!empty($data->custom)) {
            $data->custom = '1';
            $data->customjson = json_encode([
                'subject' => $data->subject ?? '',
                'body' => $data->body['text'] ?? $data->body ?? '',
            ]);
        } else {
            $data->custom = '0';
            $data->customjson = null;
        }

        // TODO: add aux data support

        $id = $DB->insert_record('local_openlms_notifications', $data);
        $record = $DB->get_record('local_openlms_notifications', ['id' => $id], '*', MUST_EXIST);

        return $record;
    }

    /**
     * Update existing instance notification.
     *
     * @param array $data
     * @return \stdClass
     */
    public static function notification_update(array $data): \stdClass {
        global $DB;

        $data = (object)$data;

        $oldrecord = $DB->get_record('local_openlms_notifications', ['id' => $data->id], '*', MUST_EXIST);

        /** @var class-string<manager> $manager */
        $manager = self::get_manager_classname($oldrecord->component);
        if (!$manager) {
            throw new \invalid_parameter_exception('Invalid notification component');
        }
        $classname = $manager::get_classname($oldrecord->notificationtype);
        if (!$classname) {
            throw new \invalid_parameter_exception('Invalid notification type');
        }
        unset($data->component);
        unset($data->notificationtype);
        unset($data->instanceid);
        if (property_exists($data, 'enabled')) {
            $data->enabled = (int)(bool)$data->enabled;
        }

        if (property_exists($data, 'custom')) {
            if ($data->custom) {
                $data->custom = '1';
                $data->customjson = json_encode([
                    'subject' => $data->subject ?? '',
                    'body' => $data->body['text'] ?? $data->body ?? '',
                ]);
            } else {
                $data->custom = '0';
                $data->customjson = null;
            }
        } else {
            unset($data->customjson);
        }

        // TODO: add aux data support

        $DB->update_record('local_openlms_notifications', $data);
        $record = $DB->get_record('local_openlms_notifications', ['id' => $data->id], '*', MUST_EXIST);

        return $record;
    }

    /**
     * Delete instance notification.
     *
     * @param int $notificationid
     * @return void
     */
    public static function notification_delete(int $notificationid): void {
        global $DB;

        $trans = $DB->start_delegated_transaction();

        $DB->delete_records('local_openlms_user_notified', ['notificationid' => $notificationid]);
        $DB->delete_records('local_openlms_notifications', ['id' => $notificationid]);

        $trans->allow_commit();
    }

    /**
     * Optimised placeholder replacement method with support for values with closures.
     *
     * @param string $text text with {$a->xyz} placeholders
     * @param array $a list of place-holder values
     * @return string
     */
    public static function replace_placeholders(string $text, array $a): string {
        if (preg_match_all('/\{\$a-(>|&gt;)([^}]+)}/', $text, $matches)) {
            $search = [];
            $replace = [];
            foreach (array_unique($matches[2]) as $placeholder) {
                if (array_key_exists($placeholder, $a)) {
                    $search[]  = '{$a->'.$placeholder.'}';
                    $search[]  = '{$a-&gt;'.$placeholder.'}';
                     $value = $a[$placeholder];
                    if (is_object($value) && get_class($value) === \Closure::class) {
                        $value = $value();
                    }
                    $value = (string)$value;
                    $replace[] = $value;
                    $replace[] = $value;
                }
            }
            if ($search) {
                $text = str_replace($search, $replace, $text);
            }
        }

        return $text;
    }

    /**
     * Apply multilang filters.
     *
     * @param string $text
     * @param bool $cachefilters
     * @return string
     */
    public static function filter_multilang(string $text, bool $cachefilters = true): string {
        global $CFG;

        // This is a very nasty hack, but unfortunately there is no way to tell filter manager
        // to use only a subset of filters that are compatible with external messaging.

        if (!$cachefilters || !is_array(self::$filters)) {
            $syscontext = \context_system::instance();
            self::$filters = [];
            $globalfilters = filter_get_globally_enabled();
            foreach ($globalfilters as $filtername) {
                if ($filtername !== 'multilang' && $filtername !== 'multilang2') {
                    continue;
                }
                $path = $CFG->dirroot .'/filter/'. $filtername .'/filter.php';
                if (!is_readable($path)) {
                    continue;
                }
                include_once($path);
                $filterclassname = 'filter_' . $filtername;
                if (!class_exists($filterclassname)) {
                    continue;
                }
                self::$filters[] = new $filterclassname($syscontext, []);
            }
        }

        foreach (self::$filters as $filter) {
            $text = $filter->filter($text, ['noclean' => true]);
        }

        return $text;
    }
}
