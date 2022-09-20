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

/**
 * Hooks overview page.
 *
 * @package   local_openlms
 * @author    Petr Skoda
 * @copyright 2022 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

$action = optional_param('action', null, PARAM_ALPHA);
$hook = optional_param('hook', null, PARAM_RAW);
$callback = optional_param('callback', null, PARAM_RAW);

admin_externalpage_setup('hooksoverview');
require_capability('moodle/site:config', context_system::instance());

$canoverride = false;
if (!empty($CFG->hooks_callback_overrides_enabled)) {
    // Admin explicitly enabled overriding of hooks from admin UI.
    if (!isset($CFG->config_php_settings['hooks_callback_overrides'])) {
        // And overriding was not forced via config.php file.
        $canoverride = true;
    }
}

$hookmanager = \local_openlms\hook\manager::get_instance();

$found = false;
$callbackdefinition = null;
if ($hook) {
    $callbacks = $hookmanager->get_callbacks_for_hook($hook);
    foreach ($callbacks as $definition) {
        if ($definition['callback'] === $callback) {
            $found = true;
            $callbackdefinition = $definition;
            break;
        }
    }
}
if (!$found || ($action !== 'override' && $action !== 'reset')) {
    $hook = null;
    $callback = null;
    $callbackdefinition = null;
    $action = null;
    $found = false;
}

$form = null;

if ($canoverride && $hook && $callback) {
    $customdata = [
        'hook' => $hook,
        'callback' => $callback,
        'priority' => $callbackdefinition['priority'],
        'disabled' => (int)$callbackdefinition['disabled'],
        'overridden' => (isset($callbackdefinition['defaultpriority']) || $callbackdefinition['disabled']),
    ];
    if ($action === 'override') {
        $form = new \local_openlms\form\hook_callback_override(null, $customdata);
    } else {
        $form = new \local_openlms\form\hook_callback_reset(null, $customdata);
    }

    if ($form->is_cancelled()) {
        redirect($PAGE->url);
    }
    if ($data = $form->get_data()) {
        if (empty($CFG->hooks_callback_overrides)) {
            $overrides = [];
        } else {
            $overrides = json_decode($CFG->hooks_callback_overrides, true);
        }
        if ($action === 'override') {
            $overrides[$hook][$callback] = [
                'priority' => $data->priority,
                'disabled' => $data->disabled,
            ];
        } else { // Must be 'reset' action.
            unset($overrides[$hook][$callback]);
        }
        if ($overrides) {
            set_config('hooks_callback_overrides', json_encode($overrides));
        } else {
            unset_config('hooks_callback_overrides');
        }
        $hookmanager->reset_caches();
        redirect($PAGE->url);
    }

    echo $OUTPUT->header();
    if ($action === 'override') {
        echo $OUTPUT->heading(get_string('hookoverride', 'local_openlms'));
    } else { // Must be 'reset' action.
        echo $OUTPUT->heading(get_string('hookreset', 'local_openlms'));
    }
    echo $form->render();
    echo $OUTPUT->footer();
    die;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('hooksoverview', 'local_openlms'));

if (empty($CFG->hooks_callback_overrides_enabled) || empty($CFG->hooks_callback_overrides)) {
    $overrides = [];
} else {
    $overrides = json_decode($CFG->hooks_callback_overrides, true);
}

$table = new html_table();
$table->head = [get_string('hookname', 'local_openlms'), get_string('hookcallbacks', 'local_openlms'),
    get_string('hookdescription', 'local_openlms'), get_string('hookdeprecates', 'local_openlms')];
$table->align = ['left', 'left', 'left', 'left'];
$table->id = 'hookslist';
$table->attributes['class'] = 'admintable generaltable';
$table->data = [];

// Look for hooks in standard places only, devs should always use 'some_plugin\hook\' namespace!
$candidates = core_component::get_component_classes_in_namespace('core', 'hook');
foreach (core_component::get_plugin_types() as $plugintype => $plugintypedir) {
    foreach (core_component::get_plugin_list($plugintype) as $pluginname => $plugindir) {
        $component = $plugintype . '_' . $pluginname;
        $candidates = array_merge($candidates, core_component::get_component_classes_in_namespace($component, 'hook'));
    }
}
$allhooks = [];
foreach ($candidates as $classname => $classfile) {
    $reflection = new ReflectionClass($classname);
    if ($reflection->isAbstract() || !$reflection->isSubclassOf(local_openlms\hook\base::class)) {
        continue;
    }
    $allhooks[$classname] = $hookmanager->get_callbacks_for_hook($classname);
}

foreach ($allhooks as $hookclass => $callbacks) {
    $cbinfo = [];
    foreach ($callbacks as $definition) {
        $iscallable = is_callable($definition['callback'], true, $callbackname);
        $isoverridden = isset($overrides[$hookclass][$definition['callback']]);
        $info = $callbackname . '&nbsp(' . $definition['priority'] . ')';
        if (!$iscallable) {
            $info .= ' <span class="badge badge-danger">' . get_string('error') . '</span>';
        }
        if ($isoverridden) {
            // We can reuse grads lang pack because the meaning here is exactly the same
            // and it is not expected there will be any overrides.
            $info .= ' <span class="badge badge-warning">' . get_string('overridden', 'grades') . '</span>';
        }

        if ($canoverride) {
            $overrideurl = new moodle_url($PAGE->url,
                ['hook' => $hookclass, 'callback' => $definition['callback'], 'action' => 'override']);
            $info .= html_writer::link($overrideurl, $OUTPUT->pix_icon('i/edit', get_string('hookoverride', 'local_openlms')));
            if ($isoverridden) {
                $reseturl = new moodle_url($PAGE->url,
                    ['hook' => $hookclass, 'callback' => $definition['callback'], 'action' => 'reset']);
                $info .= html_writer::link($reseturl, $OUTPUT->pix_icon('i/reload', get_string('hookreset', 'local_openlms')));
            }
        }

        $cbinfo[] = $info;
    }
    if ($cbinfo) {
        foreach ($cbinfo as $k => $v) {
            $class = '';
            if ($definition['disabled']) {
                $class = 'dimmed_text';
            }
            $cbinfo[$k] = "<li class='$class'>" . $v . '</li>';
        }
        $cbinfo = '<ol>' . implode("\n", $cbinfo) . '</ol>';
    } else {
        $cbinfo = '';
    }

    $description = call_user_func([$hookclass, 'get_hook_description']);
    $description = clean_text(markdown_to_html($description), FORMAT_HTML);

    $deprecates = call_user_func([$hookclass, 'get_deprecated_plugin_callbacks']);
    if ($deprecates) {
        foreach ($deprecates as $k => $v) {
            $deprecates[$k] = '<li>' . $v . '</li>';
        }
        $deprecates = '<ul>' . implode("\n", $deprecates) . '</ul>';
    } else {
        $deprecates = '';
    }

    $table->data[] = new html_table_row([$hookclass, $cbinfo, $description, $deprecates]);
}

echo html_writer::table($table);

echo $OUTPUT->footer();
