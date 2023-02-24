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
 * Notification util tests.
 *
 * @group     openlms
 * @package   local_openlms
 * @author    Petr Skoda
 * @copyright 2023 Open LMS
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \local_openlms\notification\util
 */
class util_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_manager_classname() {
        if (!get_config('tool_udplans', 'version')) {
            $this->markTestSkipped('Test requires tool_udplans plugin');
        }
        $this->assertSame(\tool_udplans\local\notification_manager::class, util::get_manager_classname('tool_udplans'));
    }

    public function test_notification_create() {
        if (!get_config('tool_udplans', 'version')) {
            $this->markTestSkipped('Test requires tool_udplans plugin');
        }
        /** @var \tool_udplans_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_udplans');

        $framework = $generator->create_framework();

        $data = [
            'component' => 'tool_udplans',
            'notificationtype' => 'plan_started',
            'instanceid' => $framework->id,
            'enabled' => '1',
        ];
        $notification = util::notification_create($data);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data['enabled'], $notification->enabled);
        $this->assertSame('0', $notification->custom);
        $this->assertSame(null, $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);

        $data = [
            'component' => 'tool_udplans',
            'notificationtype' => 'plan_overdue',
            'instanceid' => $framework->id,
            'enabled' => '0',
            'custom' => '1',
            'subject' => 'abc',
            'body' => 'def',
        ];
        $notification = util::notification_create($data);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data['enabled'], $notification->enabled);
        $this->assertSame('1', $notification->custom);
        $this->assertSame('{"subject":"abc","body":"def"}', $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);

        $data = [
            'component' => 'tool_udplans',
            'notificationtype' => 'plan_failed',
            'instanceid' => $framework->id,
            'enabled' => '1',
            'custom' => '1',
            'subject' => 'abc',
            'body' => ['text' => 'def', 'format' => FORMAT_MARKDOWN],
        ];
        $notification = util::notification_create($data);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data['enabled'], $notification->enabled);
        $this->assertSame('1', $notification->custom);
        $this->assertSame('{"subject":"abc","body":"def"}', $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);
    }

    public function test_notification_update() {
        if (!get_config('tool_udplans', 'version')) {
            $this->markTestSkipped('Test requires tool_udplans plugin');
        }
        /** @var \tool_udplans_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_udplans');

        $framework = $generator->create_framework();

        $data = [
            'component' => 'tool_udplans',
            'notificationtype' => 'plan_started',
            'instanceid' => $framework->id,
            'enabled' => '1',
        ];
        $notification = util::notification_create($data);

        $data2 = [
            'id' => $notification->id,
            'enabled' => '0',
            'custom' => '1',
            'subject' => 'abc',
            'body' => 'def',
        ];
        $notification = util::notification_update($data2);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data2['enabled'], $notification->enabled);
        $this->assertSame('1', $notification->custom);
        $this->assertSame('{"subject":"abc","body":"def"}', $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);

        $data3 = [
            'id' => $notification->id,
            'custom' => '1',
            'body' => ['text' => 'ijk', 'format' => FORMAT_MARKDOWN],
        ];
        $notification = util::notification_update($data3);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data2['enabled'], $notification->enabled);
        $this->assertSame('1', $notification->custom);
        $this->assertSame('{"subject":"","body":"ijk"}', $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);

        $data4 = [
            'id' => $notification->id,
            'custom' => '0',
        ];
        $notification = util::notification_update($data4);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data2['enabled'], $notification->enabled);
        $this->assertSame('0', $notification->custom);
        $this->assertSame(null, $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);

        $data5 = [
            'id' => $notification->id,
            'custom' => '1',
        ];
        $notification = util::notification_update($data5);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data2['enabled'], $notification->enabled);
        $this->assertSame('1', $notification->custom);
        $this->assertSame('{"subject":"","body":""}', $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);
    }

    public function test_notification_delete() {
        global $DB;

        if (!get_config('tool_udplans', 'version')) {
            $this->markTestSkipped('Test requires tool_udplans plugin');
        }
        /** @var \tool_udplans_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_udplans');

        $framework = $generator->create_framework();

        $data = [
            'component' => 'tool_udplans',
            'notificationtype' => 'plan_started',
            'instanceid' => $framework->id,
            'enabled' => '1',
        ];
        $notification = util::notification_create($data);

        $admin = get_admin();
        $data = [
            'notificationid' => $notification->id,
            'userid' => $admin->id,
            'timenotified' => time(),
            'messageid' => null,
        ];
        $DB->insert_record('local_openlms_user_notified', $data);

        util::notification_delete($notification->id);
        $this->assertFalse($DB->record_exists('local_openlms_user_notified', ['notificationid' => $notification->id]));
        $this->assertFalse($DB->record_exists('local_openlms_notifications', ['id' => $notification->id]));
    }

    public function test_replace_placeholders() {
        $this->assertSame('abc', util::replace_placeholders('abc', ['opr' => 'OPR']));

        $def = function() {
            return 'DEF';
        };
        $return = util::replace_placeholders('abc {$a->opr} ({$a-&gt;def}) {$a}', ['opr' => 'OPR', 'abc' => 'ABC', 'def' => $def]);
        $this->assertSame('abc OPR (DEF) {$a}', $return);
    }

    public function test_filter_multilang() {
        $text = '<span lang="en" class="multilang">your_content_in English</span>
                <span lang="de" class="multilang">your_content_in_German_here</span>';
        $onelang = 'your_content_in English';

        $this->assertSame($text, util::filter_multilang($text, false));

        // There does not seem to be a better way to purge the ad-hoc cache from filter_get_globally_enabled().
        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'core_filter', 'global_filters');

        filter_set_global_state('multilang', TEXTFILTER_ON);
        $cache->purge();
        $this->assertSame($onelang, util::filter_multilang($text, false));

        filter_set_global_state('multilang', TEXTFILTER_OFF);
        $cache->purge();
        $this->assertSame($onelang, util::filter_multilang($text, false));

        filter_set_global_state('multilang', TEXTFILTER_DISABLED);
        $cache->purge();
        $this->assertSame($text, util::filter_multilang($text, false));
    }

    public function test_filter_multilang2() {
        if (!get_config('filter_multilang2', 'version')) {
            $this->markTestSkipped('Test requires filter_multilang2 plugin');
        }

        $text = '{mlang en}your_content_in English{mlang}
{mlang other}your_content_in_German_here{mlang}';
        $onelang = 'your_content_in English
';

        $this->assertSame($text, util::filter_multilang($text, false));

        // There does not seem to be a better way to purge the ad-hoc cache from filter_get_globally_enabled().
        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'core_filter', 'global_filters');

        filter_set_global_state('multilang2', TEXTFILTER_ON);
        $cache->purge();
        $this->assertSame($onelang, util::filter_multilang($text, false));

        filter_set_global_state('multilang2', TEXTFILTER_OFF);
        $cache->purge();
        $this->assertSame($onelang, util::filter_multilang($text, false));

        filter_set_global_state('multilang2', TEXTFILTER_DISABLED);
        $cache->purge();
        $this->assertSame($text, util::filter_multilang($text, false));
    }
}
