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
 * Date helper tests.
 *
 * @group     openlms
 * @package   local_openlms
 * @author    Petr Skoda
 * @copyright 2023 Open LMS
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \local_openlms\date_util
 */
class date_util_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_date_util(): void {
        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-15T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM&ndash;3:00 PM', $result);

        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-16T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM&ndash;3:00 PM<sup> (+1 day)</sup>', $result);

        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-17T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM&ndash;3:00 PM<sup> (+2 days)</sup>', $result);

        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-20T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM&ndash;3:00 PM<sup> (+5 days)</sup>', $result);

        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-14T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM', $result);

        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), 0);
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM', $result);

        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), null);
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM', $result);

        $result = date_util::format_event_date(0, strtotime('2022-08-15T15:00:00'));
        $this->assertSame('', $result);

        $result = date_util::format_event_date(null, strtotime('2022-08-15T15:00:00'));
        $this->assertSame('', $result);

        // Plan text decoding.
        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-16T15:00:00'));
        $result = strip_tags($result);
        $result = \core_text::entities_to_utf8($result);
        $this->assertSame('15 August 2022   11:00 AM–3:00 PM (+1 day)', $result);
    }
}