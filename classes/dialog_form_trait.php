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
 * Trait for legacy modal dialog forms.
 *
 * NOTE: this is an alternative to extending dialog_form.
 *
 * @package    local_openlms
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait dialog_form_trait {
    private $islegacyajaxrequest = false;

    final public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, $ajaxformdata = null) {
        if (AJAX_SCRIPT) {
            // Make sure form element ids are randomised in case there are multiple forms on the page.
            $attributes = ['data-random-ids' => 1] + ($attributes ?: []);
        }

        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);

        if (AJAX_SCRIPT) {
            $this->islegacyajaxrequest = true;
            // There might be two dialog forms initialised on one page.
            if (!defined('PREFERRED_RENDERER_TARGET')) {
                // Do the sam hacks as lib/ajax/service.php here to allow fragment rendering.
                define('PREFERRED_RENDERER_TARGET', RENDERER_TARGET_GENERAL);
                ob_start(); // We will be sending back only the form data.
            }
        }
    }

    /**
     * Replacement for redirect call after processing submitted form.
     *
     * @param $url
     * @param string|null $message
     * @param string $messagetype
     * @return void
     */
    final public function redirect_submitted($url, string $message = null, string $messagetype = \core\output\notification::NOTIFY_INFO): void {
        if ($this->islegacyajaxrequest) {
            if ($message) {
                // The notification will be shown after page reload or redirect.
                \core\notification::add($message, $messagetype);
            }
            // Started in constructor, ignore all output bfore form.
            ob_end_clean();
            if ($url instanceof \moodle_url) {
                $url = $url->out(false);
            }
            $data = [
                'dialog_form' => 'submitted',
                'redirecturl' => $url,
            ];
            echo json_encode(['data' => $data]);
            die;
        } else {
            redirect($url, $message, null, $messagetype);
        }
    }

    final public function render() {
        global $PAGE, $OUTPUT;

        if (!$this->islegacyajaxrequest) {
            // Nothing special to do.
            return parent::render();
        }

        // Ignore all html markup before the form.
        ob_end_clean();

        // NOTE: this code uses the same hackery as fragment API in web services.
        $PAGE->start_collecting_javascript_requirements();
        ob_start();
        $this->display();
        $html = ob_get_contents();
        ob_end_clean();
        $jsfooter = $PAGE->requires->get_end_code();

        $data = [
            'dialog_form' => 'render',
            'html' => $html,
            'javascript' => $jsfooter,
            'pageheading' => $PAGE->heading,
            'pagetitle' => $PAGE->title,
        ];

        echo json_encode(['data' => $data]);
        die;
    }
}
