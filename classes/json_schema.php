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

namespace local_openlms;

/**
 * JSON Schema validation related helper code.
 *
 * @package    local_openlms
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class json_schema {
    public static function validate($data, $schema): array {
        require_once __DIR__ . '/../vendor/autoload.php';

        $validator = new \Opis\JsonSchema\Validator();
        try {
            $result = $validator->validate($data, $schema);
            $valid = $result->isValid();
            if ($valid) {
                $errors = [];
            } else {
                $error = $result->error();
                $formatter = new \Opis\JsonSchema\Errors\ErrorFormatter();
                $errors = $formatter->formatKeyed($error);
            }
        } catch (\Opis\JsonSchema\Exceptions\SchemaException $e) {
            $valid = false;
            $errors = [];
            $errors['/'][] = $e->getMessage();
        }
        return [$valid, $errors];
    }

    /**
     * Normalise objects and arrays for JSON processing.
     *
     * @param mixed $data
     * @return mixed
     */
    public static function normalise_data($data) {
        require_once __DIR__ . '/../vendor/autoload.php';

        return \Opis\JsonSchema\Helper::toJSON($data);
    }
}
