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

namespace local_openlms;

/**
 * JSON schema helper tests.
 *
 * @group     openlms
 * @package   local_openlms
 * @author    Petr Skoda
 * @copyright 2024 Open LMS
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \local_openlms\json_schema
 */
class json_schema_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_validate(): void {
        $schema = <<<'JSON'
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "Test",
  "description": "A product in the catalog",
  "type": "object",
    "properties": {
        "somename": {
            "type": ["string", "null"],
            "minLength": 1,
            "maxLength": 20
        },
        "someint": {
            "type": "integer",
            "minimum": 10,
            "maximum": 20
        },
        "somedate": {
            "type": "string",
            "format": "date-time"
        }
    },
    "required": ["somename"]  
}
JSON;

        $data = (object)[
            'somename' => 'abc',
            'someint' => 15,
            'somedate' => '2020-11-13T23:10:05+02:00'
        ];
        list($valid, $errors) = json_schema::validate($data, $schema);
        $this->assertTrue($valid);
        $this->assertSame([], $errors);

        $data = (object)[
            'somename' => null,
            'someint' => 15,
            'somedate' => '2020-11-13T23:10:05+02:00'
        ];
        list($valid, $errors) = json_schema::validate($data, $schema);
        $this->assertTrue($valid);
        $this->assertSame([], $errors);

        $data = (object)[
            'somename' => 'abc',
        ];
        list($valid, $errors) = json_schema::validate($data, $schema);
        $this->assertTrue($valid);
        $this->assertSame([], $errors);

        $data = (object)[
            'noname' => 'abc',
        ];
        list($valid, $errors) = json_schema::validate($data, $schema);
        $this->assertFalse($valid);
        $this->assertSame(['/' => ['The required properties (somename) are missing']], $errors);

        $data = (object)[
            'somename' => '',
            'someint' => 15,
            'somedate' => '2020-11-13T23:10:05+02:00'
        ];
        list($valid, $errors) = json_schema::validate($data, $schema);
        $this->assertFalse($valid);
        $this->assertSame(['/somename' => ['Minimum string length is 1, found 0']], $errors);

        $data = (object)[
            'somename' => 'abc',
            'someint' => 100,
            'somedate' => '2020-11-13T23:10:05+02:00'
        ];
        list($valid, $errors) = json_schema::validate($data, $schema);
        $this->assertFalse($valid);
        $this->assertSame(['/someint' => ['Number must be lower than or equal to 20']], $errors);

        $data = (object)[
            'somename' => 'abc',
            'someint' => 15,
            'somedate' => '2020-02-30T23:10:05+02:00'
        ];
        list($valid, $errors) = json_schema::validate($data, $schema);
        $this->assertFalse($valid);
        $this->assertSame(['/somedate' => ['The data must match the \'date-time\' format']], $errors);

        $data = (object)[
            'somename' => 'abc',
            'someint' => 15,
            'somedate' => '2020/02/02 23:10:05'
        ];
        list($valid, $errors) = json_schema::validate($data, $schema);
        $this->assertFalse($valid);
        $this->assertSame(['/somedate' => ['The data must match the \'date-time\' format']], $errors);
    }

    public function test_normalise_data(): void {
        $data = ['a', 1, false];
        $this->assertSame($data, json_schema::normalise_data($data));

        $data = 'a';
        $this->assertSame($data, json_schema::normalise_data($data));

        $data = 1;
        $this->assertSame($data, json_schema::normalise_data($data));

        $data = null;
        $this->assertSame($data, json_schema::normalise_data($data));

        $data = ['x'=> 'a', 'Y' => 1];
        $result = json_schema::normalise_data($data);
        $this->assertIsObject($result);
        $this->assertSame($data, (array)$result);

        $data = (object)['x'=> 'a', 'Y' => 1];
        $result = json_schema::normalise_data($data);
        $this->assertIsObject($result);
        $this->assertSame((array)$data, (array)$result);
    }
}
