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

namespace local_openlms\hook;

/**
 * Hooks tests.
 *
 * @coversDefaultClass \local_openlms\hook\manager
 *
 * @group     openlms
 * @package   local_openlms
 * @author    Petr Skoda
 * @copyright 2022 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager_test extends \advanced_testcase {
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        // NOTE: we cannot modify PHPUnit bootstrap, the instance is initialised without any overrides,
        // so make sure the $CFG->hooks_callback_overrides_enabled is set to 0;
        $CFG->hooks_callback_overrides_enabled = '0';
        manager::get_instance();
    }

    protected function tearDown(): void {
        global $CFG;
        unset($CFG->hooks_callback_overrides_enabled);
        parent::tearDown();
    }

    /**
     * Test public factory method to get hook manager.
     * @covers ::get_instance
     */
    public function test_get_instance() {
        $manager = manager::get_instance();
        $this->assertInstanceOf(manager::class, $manager);

        $this->assertSame($manager, manager::get_instance());
    }

    /**
     * Test getting of manager test instance.
     * @covers ::phpunit_get_instance
     */
    public function test_phpunit_get_instance() {
        $testmanager = manager::phpunit_get_instance([]);
        $this->assertSame([], $testmanager->get_hooks_with_callbacks());

        $componentfiles = [
            'test_plugin1' => __DIR__ . '/../fixtures/hook/hooks1_valid.php',
        ];
        $testmanager = manager::phpunit_get_instance($componentfiles);
        $this->assertSame(['test_plugin\\hook\\hook'], $testmanager->get_hooks_with_callbacks());
    }

    /**
     * Test reset of test instance.
     *
     * NOTE: normal hook manger instance cannot be reset in PHPUnit test
     * because it may be used to control the test environment itself.
     *
     * @covers ::reset_caches
     * @covers ::init_standard_callbacks
     */
    public function test_reset_caches() {
        $testmanager = manager::phpunit_get_instance([]);
        $this->assertSame([], $testmanager->get_hooks_with_callbacks());

        $testmanager->reset_caches();
        $manager = manager::get_instance();
        $this->assertSame($manager->get_hooks_with_callbacks(), $testmanager->get_hooks_with_callbacks());
    }

    /**
     * Test loading and parsing of callbacks from files.
     *
     * @covers ::get_callbacks_for_hook
     * @covers ::get_hooks_with_callbacks
     * @covers ::load_callbacks
     * @covers ::add_component_callbacks
     */
    public function test_callbacks() {
        $componentfiles = [
            'test_plugin1' => __DIR__ . '/../fixtures/hook/hooks1_valid.php',
            'test_plugin2' => __DIR__ . '/../fixtures/hook/hooks2_valid.php',
        ];
        $testmanager = manager::phpunit_get_instance($componentfiles);
        $this->assertSame(['test_plugin\\hook\\hook'], $testmanager->get_hooks_with_callbacks());
        $callbacks = $testmanager->get_callbacks_for_hook('test_plugin\\hook\\hook');
        $this->assertCount(2, $callbacks);
        $this->assertSame([
            'callback' => 'test_plugin\\callbacks::test2',
            'priority' => 200,
            'component' => 'test_plugin2',
            'disabled' => false], $callbacks[0]
        );
        $this->assertSame([
            'callback' => 'test_plugin\\callbacks::test1',
            'priority' => 100,
            'component' => 'test_plugin1',
            'disabled' => false], $callbacks[1]
        );

        $this->assertDebuggingNotCalled();
        $componentfiles = [
            'test_plugin1' => __DIR__ . '/../fixtures/hook/hooks1_broken.php',
        ];
        $testmanager = manager::phpunit_get_instance($componentfiles);
        $this->assertSame([], $testmanager->get_hooks_with_callbacks());
        $debuggings = $this->getDebuggingMessages();
        $this->resetDebugging();
        $this->assertSame('Hook callback definition requires \'hook\' name in \'test_plugin1\'', $debuggings[0]->message);
        $this->assertSame('Hook callback definition requires \'callback\' callable in \'test_plugin1\'', $debuggings[1]->message);
        $this->assertSame('Hook callback definition contains invalid \'callback\' static class method string in \'test_plugin1\'', $debuggings[2]->message);
        $this->assertCount(3, $debuggings);
    }

    /**
     * Test hook dispatching, that is callback execution.
     * @covers ::dispatch
     */
    public function test_dispatch() {
        require_once(__DIR__ . '/../fixtures/hook/hook.php');
        require_once(__DIR__ . '/../fixtures/hook/callbacks.php');

        $componentfiles = [
            'test_plugin1' => __DIR__ . '/../fixtures/hook/hooks1_valid.php',
            'test_plugin2' => __DIR__ . '/../fixtures/hook/hooks2_valid.php',
        ];
        $testmanager = manager::phpunit_get_instance($componentfiles);
        \test_plugin\callbacks::$calls = [];
        $hook = new \test_plugin\hook\hook();
        $result = $testmanager->dispatch($hook);
        $this->assertSame($hook, $result);
        $this->assertSame(['test2', 'test1'], \test_plugin\callbacks::$calls);
        \test_plugin\callbacks::$calls = [];
        $this->assertDebuggingNotCalled();

        // Dispatch ignoring exceptions.
        $componentfiles = [
            'test_plugin1' => __DIR__ . '/../fixtures/hook/hooks1_exception.php',
            'test_plugin2' => __DIR__ . '/../fixtures/hook/hooks2_valid.php',
        ];
        $testmanager = manager::phpunit_get_instance($componentfiles);
        \test_plugin\callbacks::$calls = [];
        $hook = new \test_plugin\hook\hook();
        $testmanager->dispatch($hook, false);
        $this->assertDebuggingCalled('Exception in callback \'test_plugin\callbacks::exception\' for hook \'test_plugin\hook\hook\' from \'test_plugin1\': grrr');
        $this->assertSame(['exception', 'test2'], \test_plugin\callbacks::$calls);
        \test_plugin\callbacks::$calls = [];

        // Dispatch not ignoring exceptions.
        \test_plugin\callbacks::$calls = [];
        $hook = new \test_plugin\hook\hook();
        try {
            $testmanager->dispatch($hook);
            $this->fail('Exception expected');
        } catch (\Exception $e) {
            $this->assertSame('grrr', $e->getMessage());
        }
        $this->assertDebuggingNotCalled();
        $this->assertSame(['exception'], \test_plugin\callbacks::$calls);
        \test_plugin\callbacks::$calls = [];

        // Missing callbacks is ignored.
        $componentfiles = [
            'test_plugin1' => __DIR__ . '/../fixtures/hook/hooks1_missing.php',
            'test_plugin2' => __DIR__ . '/../fixtures/hook/hooks2_valid.php',
        ];
        $testmanager = manager::phpunit_get_instance($componentfiles);
        \test_plugin\callbacks::$calls = [];
        $hook = new \test_plugin\hook\hook();
        $testmanager->dispatch($hook);
        $this->assertDebuggingCalled('Cannot execute callback \'test_plugin\callbacks::missing\' for hook \'test_plugin\hook\hook\' from \'test_plugin1\'');
        $this->assertSame(['test2'], \test_plugin\callbacks::$calls);
        \test_plugin\callbacks::$calls = [];
    }

    /**
     * Test stoppping of hook dispatching.
     * @covers ::dispatch
     */
    public function test_dispatch_stoppable() {
        require_once(__DIR__ . '/../fixtures/hook/stoppablehook.php');
        require_once(__DIR__ . '/../fixtures/hook/callbacks.php');

        $componentfiles = [
            'test_plugin1' => __DIR__ . '/../fixtures/hook/hooks1_stoppable.php',
            'test_plugin2' => __DIR__ . '/../fixtures/hook/hooks2_stoppable.php',
        ];
        $testmanager = manager::phpunit_get_instance($componentfiles);
        \test_plugin\callbacks::$calls = [];
        $hook = new \test_plugin\hook\stoppablehook();
        $result = $testmanager->dispatch($hook);
        $this->assertSame($hook, $result);
        $this->assertSame(['stop1'], \test_plugin\callbacks::$calls);
        \test_plugin\callbacks::$calls = [];
        $this->assertDebuggingNotCalled();
    }

    /**
     * Test deprecated callback lookup.
     * @covers ::is_deprecated_plugin_callback
     */
    public function testy_is_deprecated_plugin_callback() {
        require_once(__DIR__ . '/../fixtures/hook/hook.php');

        $componentfiles = [
            'test_plugin1' => __DIR__ . '/../fixtures/hook/hooks1_valid.php',
        ];
        $testmanager = manager::phpunit_get_instance($componentfiles);
        $this->assertTrue($testmanager->is_deprecated_plugin_callback('oldcallback'));
        $this->assertFalse($testmanager->is_deprecated_plugin_callback('legacycallback'));
    }

    /**
     * Test detection of legacy callbacks.
     * @covers ::is_deprecating_hook_present
     */
    public function testy_is_deprecating_hook_present() {
        $componentfiles = [
            'test_plugin1' => __DIR__ . '/../fixtures/hook/hooks1_valid.php',
        ];
        $testmanager = manager::phpunit_get_instance($componentfiles);
        // There is not much to test because there should not be any legacy callbacks left,
        // plugin that have these should test their code.
        $this->assertFalse($testmanager->is_deprecating_hook_present('test_pluing', 'xyz'));
    }

    /**
     * Tests callbacks can be overridden via CFG settings.
     * @covers ::load_callbacks
     * @covers ::dispatch
     */
    public function test_callback_overriding() {
        global $CFG;
        $this->resetAfterTest();
        $this->assertSame('0', $CFG->hooks_callback_overrides_enabled);

        $componentfiles = [
            'test_plugin1' => __DIR__ . '/../fixtures/hook/hooks1_valid.php',
            'test_plugin2' => __DIR__ . '/../fixtures/hook/hooks2_valid.php',
        ];

        unset($CFG->hooks_callback_overrides_enabled);
        $testmanager = manager::phpunit_get_instance($componentfiles);
        $this->assertSame(['test_plugin\\hook\\hook'], $testmanager->get_hooks_with_callbacks());
        $callbacks = $testmanager->get_callbacks_for_hook('test_plugin\\hook\\hook');
        $this->assertCount(2, $callbacks);
        $this->assertSame([
            'callback' => 'test_plugin\\callbacks::test2',
            'priority' => 200,
            'component' => 'test_plugin2',
            'disabled' => false], $callbacks[0]
        );
        $this->assertSame([
            'callback' => 'test_plugin\\callbacks::test1',
            'priority' => 100,
            'component' => 'test_plugin1',
            'disabled' => false], $callbacks[1]
        );

        $CFG->hooks_callback_overrides_enabled = true;
        $CFG->hooks_callback_overrides = json_encode([
            'test_plugin\\hook\\hook' => [
                'test_plugin\\callbacks::test2' => ['priority' => 33]
            ]
        ]);
        $testmanager = manager::phpunit_get_instance($componentfiles);
        $this->assertSame(['test_plugin\\hook\\hook'], $testmanager->get_hooks_with_callbacks());
        $callbacks = $testmanager->get_callbacks_for_hook('test_plugin\\hook\\hook');
        $this->assertCount(2, $callbacks);
        $this->assertSame([
            'callback' => 'test_plugin\\callbacks::test1',
            'priority' => 100,
            'component' => 'test_plugin1',
            'disabled' => false], $callbacks[0]
        );
        $this->assertSame([
            'callback' => 'test_plugin\\callbacks::test2',
            'priority' => 33,
            'component' => 'test_plugin2',
            'disabled' => false,
            'defaultpriority' => 200], $callbacks[1]
        );

        $CFG->hooks_callback_overrides_enabled = true;
        $CFG->hooks_callback_overrides = json_encode([
            'test_plugin\\hook\\hook' => [
                'test_plugin\\callbacks::test2' => ['priority' => 33, 'disabled' => true]
            ]
        ]);
        $testmanager = manager::phpunit_get_instance($componentfiles);
        $this->assertSame(['test_plugin\\hook\\hook'], $testmanager->get_hooks_with_callbacks());
        $callbacks = $testmanager->get_callbacks_for_hook('test_plugin\\hook\\hook');
        $this->assertCount(2, $callbacks);
        $this->assertSame([
            'callback' => 'test_plugin\\callbacks::test1',
            'priority' => 100,
            'component' => 'test_plugin1',
            'disabled' => false], $callbacks[0]
        );
        $this->assertSame([
            'callback' => 'test_plugin\\callbacks::test2',
            'priority' => 33,
            'component' => 'test_plugin2',
            'disabled' => true,
            'defaultpriority' => 200], $callbacks[1]
        );

        $CFG->hooks_callback_overrides_enabled = true;
        $CFG->hooks_callback_overrides = json_encode([
            'test_plugin\\hook\\hook' => [
                'test_plugin\\callbacks::test2' => ['disabled' => true],
            ]
        ]);
        $testmanager = manager::phpunit_get_instance($componentfiles);
        $this->assertSame(['test_plugin\\hook\\hook'], $testmanager->get_hooks_with_callbacks());
        $callbacks = $testmanager->get_callbacks_for_hook('test_plugin\\hook\\hook');
        $this->assertCount(2, $callbacks);
        $this->assertSame([
            'callback' => 'test_plugin\\callbacks::test2',
            'priority' => 200,
            'component' => 'test_plugin2',
            'disabled' => true], $callbacks[0]
        );
        $this->assertSame([
            'callback' => 'test_plugin\\callbacks::test1',
            'priority' => 100,
            'component' => 'test_plugin1',
            'disabled' => false], $callbacks[1]
        );

        require_once(__DIR__ . '/../fixtures/hook/hook.php');
        require_once(__DIR__ . '/../fixtures/hook/callbacks.php');

        \test_plugin\callbacks::$calls = [];
        $hook = new \test_plugin\hook\hook();
        $result = $testmanager->dispatch($hook);
        $this->assertSame($hook, $result);
        $this->assertSame(['test1'], \test_plugin\callbacks::$calls);
        \test_plugin\callbacks::$calls = [];
        $this->assertDebuggingNotCalled();

    }
}
