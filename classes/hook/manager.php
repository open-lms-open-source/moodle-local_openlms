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
 * Hook manager implementing "Dispatcher" and "Event Provider" from PSR-14.
 *
 * Due to class/method naming restrictions and collision with
 * Moodle events the definitions from PSR-14 should be interpreted as:
 *
 *  1. Event --> Hook
 *  2. Listener --> Hook callback
 *  3. Emitter --> Hook emitter
 *  4. Dispatcher --> Hook dispatcher - implemented in manager::dispatch()
 *  5. Listener Provider --> Hook callback provider - implemented in manager::get_callbacks_for_hook()
 *
 * @package   local_openlms
 * @author    Petr Skoda
 * @copyright 2022 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manager {
    /** @var ?manager the one instance of listener provider and dispatcher */
    private static $instance = null;

    /** @var array list of callback definitions for each hook class. */
    private $allcallbacks = [];

    /** @var array list of all deprecated lib.php plugin callbacks. */
    private $alldeprecations = [];

    /**
     * Constructor can be used only from factory methods.
     */
    private function __construct() {
    }

    /**
     * Factory method, returns instance of manager that serves
     * as hook dispatcher and callback provider.
     *
     * NOTE: do not use this directly in normal code, use $hook->dispatch() instead.
     *
     * @return self
     */
    public static function get_instance(): manager {
        if (!self::$instance) {
            // We cannot tweak PHPUnit bootstrap in Open LMS, so hack it here.
            if (PHPUNIT_TEST) {
                global $CFG;
                $CFG->hooks_callback_overrides_enabled = '0';

            }
            self::$instance = new self();
            self::$instance->init_standard_callbacks();
        }
        return self::$instance;
    }

    /**
     * Factory method for testing of hook manager in PHPUnit tests.
     *
     * @param array $componentfiles list of hook callback files for each component.
     * @return self
     */
    public static function phpunit_get_instance(array $componentfiles): manager {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('Invalid call of manager::phpunit_get_instance() outside of tests');
        }
        $instance = new self();
        $instance->load_callbacks($componentfiles);
        return $instance;
    }

    /**
     * Reset all hook caches. This is intended to be called only
     * from the admin/hooks.php page after callback override is changed.
     *
     * @return void
     */
    public function reset_caches(): void {
        if (PHPUNIT_TEST && $this === self::$instance) {
            debugging('\local_openlms\hook\manager::get_instance()->reset_caches() is not supposed to be called in PHPUnit tests', DEBUG_DEVELOPER);
            return;
        }

        // WARNING: This will not work when callback overrides are changed
        // and multiple web nodes with local cache stores are present - in that
        // case admins must purge all caches when tweaking callback overrides.
        $cache = \cache::make('local_openlms', 'hookcallbacks');
        $cache->delete('callbacks');
        $cache->delete('deprecations');

        $this->init_standard_callbacks();
    }

    /**
     * Returns list of callbacks for given hook name.
     *
     * NOTE: this is the "Listener Provider" described in PSR-14,
     * instead of instance parameter it uses real PHP class names.
     * Moodle hooks should be final and parents of hook class are not
     * considered when resolving callbacks.
     *
     * @param string $hookclassname PHP class name of hook
     * @return array list of callback definitions
     */
    public function get_callbacks_for_hook(string $hookclassname): array {
        return $this->allcallbacks[$hookclassname] ?? [];
    }

    /**
     * Returns the list of Hook class names that have registered callbacks.
     * @return array
     */
    public function get_hooks_with_callbacks(): array {
        return array_keys($this->allcallbacks);
    }

    /**
     * Do not call directly, use $hook->dispatch() instead.
     *
     * NOTE: this is the "Dispatcher" described in PSR-14,
     * the extra parameter $throwexceptions==false is intended to be
     * used in special cases where we were wrapping the legacy callbacks
     * with try-catch.
     *
     * @param base $hook
     * @param bool $throwexceptions use false if exceptions should be ignored
     * @return base original $hook parameter
     */
    public function dispatch(base $hook, bool $throwexceptions = true): base {
        // We can dispatch only after the lib/setup.php includes,
        // that is right before the database connection is made,
        // the MUC caches need to be working already.
        if (!function_exists('setup_DB')) {
            debugging('Hooks cannot be dispatched yet', DEBUG_DEVELOPER);
            return $hook;
        }

        // Do not consider hook PHP class hierarchy, always use only the hook class name.
        $hookclassname = get_class($hook);

        $callbacks = $this->get_callbacks_for_hook($hookclassname);
        if (!$callbacks) {
            // Nothing is interested in this hook.
            return $hook;
        }

        foreach ($callbacks as $definition) {
            if ($definition['disabled']) {
                continue;
            }
            $callback = $definition['callback'];
            $component = $definition['component'];
            if (!is_callable($callback, false, $callablename)) {
                debugging("Cannot execute callback '$callablename' for hook '$hookclassname' from '$component'", DEBUG_DEVELOPER);
                continue;
            }
            try {
                $result = call_user_func($callback, $hook);
                if ($result === false) {
                    debugging("Error executing callback '$callablename' for hook '$hookclassname' from '$component'", DEBUG_DEVELOPER);
                }
                if ($hook instanceof stoppable) {
                    if ($hook->is_propagation_stopped()) {
                        return $hook;
                    }
                }
                if ($result === false) {
                    continue;
                }
            } catch (\Throwable $e) {
                if ($throwexceptions) {
                    throw $e;
                }
                debugging("Exception in callback '$callablename' for hook '$hookclassname' from '$component': " . $e->getMessage(),
                    DEBUG_DEVELOPER, $e->getTrace());
                if ($hook instanceof stoppable) {
                    if ($hook->is_propagation_stopped()) {
                        return $hook;
                    }
                }
                continue;
            }
        }

        // Developers need to be careful to not create infinite loops in hook callbacks.
        return $hook;
    }

    /**
     * Initialise list of all callbacks for each hook.
     *
     * @return void
     */
    private function init_standard_callbacks(): void {
        global $CFG;

        $this->allcallbacks = [];
        $this->alldeprecations = [];

        $cache = null;
        if (!PHPUNIT_TEST && !CACHE_DISABLE_ALL) {
            $cache = \cache::make('local_openlms', 'hookcallbacks');
            $callbacks = $cache->get('callbacks');
            $deprecations = $cache->get('deprecations');
            if (is_array($callbacks) && is_array($deprecations)) {
                $this->allcallbacks = $callbacks;
                $this->alldeprecations = $deprecations;
                return;
            }
        }

        // Get list of all files with callbacks, one per component.
        $components = ['core' => $CFG->dirroot . '/lib/db/hooks.php'];
        $plugintypes = \core_component::get_plugin_types();
        foreach ($plugintypes as $plugintype => $plugintypedir) {
            $plugins = \core_component::get_plugin_list($plugintype);
            foreach ($plugins as $pluginname => $plugindir) {
                if (!$plugindir) {
                    continue;
                }
                $hookfile = $plugindir . '/db/hooks.php';
                if (!file_exists($hookfile)) {
                    continue;
                }
                $components[$plugintype . '_' . $pluginname] = $hookfile;
            }
        }

        // Load the callbacks and apply overrides.
        $this->load_callbacks($components);

        if ($cache) {
            $cache->set('callbacks', $this->allcallbacks);
            $cache->set('deprecations', $this->alldeprecations);
        }
    }

    /**
     * Load callbacks from component db/hooks.php files.
     *
     * @param array $componentfiles list of all components with their callback files
     * @return void
     */
    private function load_callbacks(array $componentfiles): void {
        global $CFG;

        $this->allcallbacks = [];
        $this->alldeprecations = [];

        foreach ($componentfiles as $component => $hookfile) {
            if (!file_exists($hookfile)) {
                continue;
            }
            $this->add_component_callbacks($component, $hookfile);
        }

        // Apply overrides from settings.
        if (!empty($CFG->hooks_callback_overrides_enabled) && !empty($CFG->hooks_callback_overrides)) {
            $overrides = json_decode($CFG->hooks_callback_overrides, true);
            if (is_array($overrides)) {
                foreach ($this->allcallbacks as $hookclassname => $callbacks) {
                    foreach ($callbacks as $k => $definition) {
                        $callback = $definition['callback'];
                        if (!isset($overrides[$hookclassname][$callback])) {
                            continue;
                        }
                        $override = $overrides[$hookclassname][$callback];
                        if (isset($override['priority'])) {
                            $this->allcallbacks[$hookclassname][$k]['defaultpriority'] = $this->allcallbacks[$hookclassname][$k]['priority'];
                            $this->allcallbacks[$hookclassname][$k]['priority'] = (int)$override['priority'];
                        }
                        if (!empty($override['disabled'])) {
                            $this->allcallbacks[$hookclassname][$k]['disabled'] = true;
                        }
                    }
                }
            }
        }

        // Prioritise callbacks.
        foreach ($this->allcallbacks as $hookclassname => $callbacks) {
            \core_collator::asort_array_of_arrays_by_key($callbacks, 'priority', \core_collator::SORT_NUMERIC);
            $callbacks = array_reverse($callbacks);
            $this->allcallbacks[$hookclassname] = $callbacks;
        }

        // Make a list of deprecated lib.php plugin callbacks.
        foreach ($this->allcallbacks as $hookclassname => $callbacks) {
            if (!class_exists($hookclassname) || !method_exists($hookclassname, 'get_deprecated_plugin_callbacks')) {
                continue;
            }
            $deprecations = $hookclassname::get_deprecated_plugin_callbacks();
            if (!$deprecations) {
                continue;
            }
            foreach ($deprecations as $deprecation) {
                $this->alldeprecations[$deprecation][] = $hookclassname;
            }
        }
    }

    /**
     * Add hook callbacks from file.
     *
     * @param string $component component where hook callbacks are defined
     * @param string $hookfile file with list of all callbacks for component
     * @return void
     */
    private function add_component_callbacks(string $component, string $hookfile): void {
        $parsecallbacks = function($hookfile) {
            $callbacks = [];
            include($hookfile);
            return $callbacks;
        };

        $callbacks = $parsecallbacks($hookfile);

        if (!is_array($callbacks) || !$callbacks) {
            return;
        }

        foreach ($callbacks as $callback) {
            if (empty($callback['hook'])) {
                debugging("Hook callback definition requires 'hook' name in '$component'", DEBUG_DEVELOPER);
                continue;
            }
            $hook = ltrim($callback['hook'], '\\'); // Normalise hook class name.

            if (empty($callback['callback'])) {
                debugging("Hook callback definition requires 'callback' callable in '$component'", DEBUG_DEVELOPER);
                continue;
            }
            $classmethod = $callback['callback'];
            if (!is_string($classmethod)) {
                debugging("Hook callback definition contains invalid 'callback' string in '$component'", DEBUG_DEVELOPER);
                continue;
            }
            if (strpos($classmethod, '::') === false) {
                debugging("Hook callback definition contains invalid 'callback' static class method string in '$component'", DEBUG_DEVELOPER);
                continue;
            }
            // Normalise the callback class:::method name, we use it later as identifier.
            $classmethod = ltrim($classmethod, '\\');

            if (isset($callback['priority'])) {
                $priority = (int)$callback['priority'];
            } else {
                $priority = 100;
            }

            $this->allcallbacks[$hook][] = [
                'callback' => $classmethod,
                'priority' => $priority,
                'component' => $component,
                'disabled' => false,
            ];
        }
    }

    /**
     * Is the plugin callback from lib.php deprecated by any hook?
     *
     * @param string $plugincallback short callback name without the component prefix
     * @return bool
     */
    public function is_deprecated_plugin_callback(string $plugincallback): bool {
        return isset($this->alldeprecations[$plugincallback]);
    }

    /**
     * Is there a hook callback in component that deprecates given lib.php plugin callback?
     *
     * NOTE: if there is both hook and deprecated callback then we ignore the old callback
     * to allow compatibility of contrib plugins with multiple Moodle branches.
     *
     * @param string $component
     * @param string $plugincallback short callback name without the component prefix
     * @return bool
     */
    public function is_deprecating_hook_present(string $component, string $plugincallback): bool {
        if (!isset($this->alldeprecations[$plugincallback])) {
            return false;
        }

        foreach ($this->alldeprecations[$plugincallback] as $hookclassname) {
            if (!isset($this->allcallbacks[$hookclassname])) {
                continue;
            }
            foreach ($this->allcallbacks[$hookclassname] as $definition) {
                if ($definition['component'] === $component) {
                    return true;
                }
            }
        }

        return false;
    }
}
