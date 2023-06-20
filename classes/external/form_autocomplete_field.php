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

namespace local_openlms\external;

use external_function_parameters;
use external_description;
use external_single_structure;
use external_multiple_structure;
use external_value;

if (!class_exists('external_api')) {
    global $CFG;
    require_once("$CFG->libdir/externallib.php");
}

/**
 * Base class for simplified form ajax autocomplete fields.
 *
 * @package     local_openlms
 * @copyright   2023 Open LMS (https://www.openlms.net/)
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class form_autocomplete_field extends \external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    abstract public static function execute_parameters(): external_function_parameters;

    /**
     * Describes the external function result value.
     *
     * @return external_description
     */
    final public static function execute_returns(): external_description {
        return new external_single_structure([
            'notice' => new external_value(PARAM_RAW, 'Notice message when data cannot be returned, NULl means success.',
                VALUE_OPTIONAL, null, NULL_ALLOWED),
            'list' => new external_multiple_structure(
                new external_single_structure([
                    'value' => new external_value(PARAM_RAW, 'Value of item'),
                    'label' => new external_value(PARAM_RAW, 'Label of item'),
                ], 'List of options, empty if notice set')
            ),
        ]);
    }

    /**
     * Return function that return label for given value.
     *
     * @param array $arguments
     * @return callable
     */
    public static function get_label_callback(array $arguments): callable {
        return function($value) use ($arguments): string {
            return "get_label_callback() not implemented: $value";
        };
    }

    /**
     * True means returned field data is array, false means value is scalar.
     *
     * @return bool
     */
    public static function is_multi_select_field(): bool {
        return false;
    }

    /**
     * Return name of WS function which is defined in db/services.php file.
     *
     * @return string
     */
    public static function get_web_service_name(): string {
        $parts = explode('\\', static::class);
        $component = reset($parts);
        $name = array_pop($parts);
        return $component . '_' . $name;
    }

    /**
     * Add form element.
     *
     * @param \MoodleQuickForm $mform
     * @param array $arguments WS call parameters
     * @param string $name field name
     * @param string $label field label
     * @param array $attributes autocomplete field attributes
     * @return \html_quickform_element
     */
    public static function add_form_element(\MoodleQuickForm $mform, array $arguments, string $name, string $label, array $attributes = []): \html_quickform_element {
        $attributes['tags'] = false;
        $attributes['multiple'] = static::is_multi_select_field();
        $attributes['ajax'] = 'local_openlms/form_autocomplete_ajax';
        $attributes['valuehtmlcallback'] = static::get_label_callback($arguments);
        $attributes['data-ws-method'] = static::get_web_service_name();
        $attributes['data-ws-args'] = json_encode($arguments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $mform->addElement('autocomplete', $name, $label, [], $attributes);
    }
}
