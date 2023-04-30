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

namespace local_openlms\notification;

/**
 * Notification type base tests.
 *
 * @group     openlms
 * @package   local_openlms
 * @author    Petr Skoda
 * @copyright 2023 Open LMS
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \local_openlms\notification\manager
 */
class notificationtype_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_component() {
        $this->assertSame('local_openlms', notificationtype::get_component());
    }

    public function test_get_classname() {
        $this->assertSame('notificationtype', notificationtype::get_notificationtype());
    }

    public function test_format_subject() {
        $this->assertSame(
            '',
            notificationtype::format_subject('', [])
        );
        $this->assertSame(
            'Test some subject {$a-&gt;def}',
            notificationtype::format_subject('Test {$a->abc} subject {$a->def}', ['abc' => 'some', 'xyz' => 'opr'])
        );
        $this->assertSame(
            'Test some &lt;subject&gt;',
            notificationtype::format_subject('Test {$a-&gt;abc} <subject>', ['abc' => 'some'])
        );
    }

    public function test_format_body() {
        $this->assertSame(
            '',
            notificationtype::format_body('', FORMAT_HTML, [])
        );
        $this->assertSame(
            '',
            notificationtype::format_body('', FORMAT_MARKDOWN, [])
        );
        $this->assertSame(
            "<span>great</span>\n\n{\$a-&gt;hmm}",
            notificationtype::format_body("<span>{\$a->status}</span>\n\n{\$a->hmm}", FORMAT_HTML, ['status' => 'great'])
        );
        $this->assertSame(
            "<p><span>great</span></p>\n\n<p>{\$a-&gt;hmm}</p>\n",
            notificationtype::format_body("<span>{\$a->status}</span>\n\n{\$a->hmm}", FORMAT_MARKDOWN, ['status' => 'great'])
        );

        try {
            notificationtype::format_body('', FORMAT_MOODLE, []);
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\coding_exception::class, $e);
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Unknown body format: 0',
                $e->getMessage()
            );
        }
    }
}
