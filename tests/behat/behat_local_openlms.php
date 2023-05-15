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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ElementNotFoundException;

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Dialog form steps.
 *
 * @package    local_openlms
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_openlms extends behat_base {
    /**
     * Submits modal form dialog.
     *
     * @When I press dialog form button :element
     *
     * @param string $element Element we look for
     */
    public function i_press_dialog_form_button($element) {
        if (!$this->running_javascript()) {
            $node = $this->get_node_in_container('button', $element, 'css_element', '.mform');
            $node->click();
            return;
        }

        $node = $this->get_node_in_container('button', $element, 'css_element', '.local_openlms-dialog_form');
        $node->focus(); // Scroll to the button, it might be outside the dialog viewport.
        $this->ensure_node_is_visible($node);

        $node->click();
    }

    /**
     * Looks into definition of a term in a list and makes sure text is not there.
     *
     * @Given I run the :taskname task
     *
     * @param string $task
     */
    public function execute_scheduled_task(string $taskname) {
        global $CFG;

        $task = \core\task\manager::get_scheduled_task($taskname);

        if (!$task) {
            throw new DriverException('The "' . $taskname . '" scheduled task does not exist');
        }
        $taskname = get_class($task);

        $ch = new curl();
        $options = [
            'FOLLOWLOCATION' => true,
            'RETURNTRANSFER' => true,
            'SSL_VERIFYPEER' => false,
            'SSL_VERIFYHOST' => 0,
            'HEADER' => 0,
        ];

        $content = $ch->get("$CFG->wwwroot/local/openlms/tests/behat/task_runner.php",
            ['behat_task' => $taskname], $options);

        if (strpos($content, "Scheduled task '$taskname' completed") === false) {
            throw new ExpectationException("Scheduled task '$taskname' did not complete successfully, content : " . $content, $this->getSession());
        }

        $this->look_for_exceptions();
    }

    /**
     * Admin bookmark takes way too much space on admin pages,
     * so get rid of it.
     *
     * @Given unnecessary Admin bookmarks block gets deleted
     */
    public function delete_admin_bookmarks_block() {
        global $CFG, $DB;
        require_once("$CFG->libdir/blocklib.php");

        $instance = $DB->get_record('block_instances', ['blockname' => 'admin_bookmarks']);
        if ($instance) {
            blocks_delete_instance($instance);
        }
    }

    /**
     * @Given I skip tests if :plugin is not installed
     */
    public function skip_if_plugin_missing($plugin) {
        if (!get_config($plugin, 'version')) {
            throw new \Moodle\BehatExtension\Exception\SkippedException("Tests were skipped because plugin '$plugin' is not installed");
        }
    }

    /**
     * Looks for definition of a term in a list.
     *
     * @Then I should see :text in the :label definition list item
     *
     * @param string $label
     * @param string $text
     */
    public function list_term_contains_text($text, $label) {

        $labelliteral = behat_context_helper::escape($label);
        $xpath = "//dl/dt[text()=$labelliteral]/following-sibling::dd[1]";

        $nodes = $this->getSession()->getPage()->findAll('xpath', $xpath);
        if (empty($nodes)) {
            throw new ExpectationException(
                'Unable to find a term item with label = ' . $labelliteral,
                $this->getSession()
            );
        }
        if (count($nodes) > 1) {
            throw new ExpectationException(
                'Found more than one term item with label = ' . $labelliteral,
                $this->getSession()
            );
        }
        $node = reset($nodes);

        $xpathliteral = behat_context_helper::escape($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
            "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        // Wait until it finds the text inside the container, otherwise custom exception.
        try {
            $nodes = $this->find_all('xpath', $xpath, false, $node);
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException('"' . $text . '" text was not found in the "' . $label . '" term', $this->getSession());
        }

        // If we are not running javascript we have enough with the
        // element existing as we can't check if it is visible.
        if (!$this->running_javascript()) {
            return;
        }

        // We also check the element visibility when running JS tests. Using microsleep as this
        // is a repeated step and global performance is important.
        $this->spin(
            function($context, $args) {

                foreach ($args['nodes'] as $node) {
                    if ($node->isVisible()) {
                        return true;
                    }
                }

                throw new ExpectationException('"' . $args['text'] . '" text was found in the "' . $args['label'] . '" element but was not visible', $context->getSession());
            },
            array('nodes' => $nodes, 'text' => $text, 'label' => $label),
            false,
            false,
            true
        );
    }

    /**
     * Looks into definition of a term in a list and makes sure text is not there.
     *
     * @Then I should not see :text in the :label definition list item
     *
     * @param string $label
     * @param string $text
     */
    public function list_term_note_contains_text($text, $label) {

        $labelliteral = behat_context_helper::escape($label);
        $xpath = "//dl/dt[text()=$labelliteral]/following-sibling::dd[1]";

        $nodes = $this->getSession()->getPage()->findAll('xpath', $xpath);
        if (empty($nodes)) {
            throw new ExpectationException(
                'Unable to find a term item with label = ' . $labelliteral,
                $this->getSession()
            );
        }
        if (count($nodes) > 1) {
            throw new ExpectationException(
                'Found more than one term item with label = ' . $labelliteral,
                $this->getSession()
            );
        }
        $node = reset($nodes);

        $xpathliteral = behat_context_helper::escape($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
            "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        $nodes = null;
        try {
            $nodes = $this->find_all('xpath', $xpath, false, $node);
        } catch (ElementNotFoundException $e) {
            // Good!
            $nodes = null;
        }
        if ($nodes) {
            throw new ExpectationException('"' . $text . '" text was found in the "' . $label . '" element', $this->getSession());
        }
    }

    /**
     * Opens user profile page.
     *
     * @Given I am on the profile page of user :username
     *
     * @param string $username
     */
    public function i_am_on_user_profile_page(string $username) {
        global $DB;
        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $url = new moodle_url('/user/profile.php', ['id' => $user->id]);
        $this->execute('behat_general::i_visit', [$url]);
    }

    /**
     * Activate tenants if available.
     *
     * @Given tenant support was activated
     */
    public function activate_tenants() {
        $this->skip_if_plugin_missing('tool_olms_tenant');
        \tool_olms_tenant\tenants::activate_tenants();
    }
}
