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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Open LMS support upgrade code.
 *
 * @package   local_openlms
 * @copyright 2022 Open LMS (https://www.openlms.net/)
 * @author    Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute the plugin upgrade steps from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_openlms_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2023022600) {

        // Define table local_openlms_user_notified to be dropped.
        $table = new xmldb_table('local_openlms_user_notified');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table local_openlms_notifications to be dropped.
        $table = new xmldb_table('local_openlms_notifications');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table local_openlms_notifications to be created.
        $table = new xmldb_table('local_openlms_notifications');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('component', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('notificationtype', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('custom', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('customjson', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('auxjson', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('auxint1', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('auxint2', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('component-notificationtype-instanceid', XMLDB_INDEX_UNIQUE, ['component', 'notificationtype', 'instanceid']);
        $dbman->create_table($table);

        // Define table local_openlms_user_notified to be created.
        $table = new xmldb_table('local_openlms_user_notified');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('notificationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('otherid1', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('otherid2', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timenotified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('messageid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('notificationid', XMLDB_KEY_FOREIGN, ['notificationid'], 'local_openlms_notifications', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_index('otherid1-otherid2', XMLDB_INDEX_NOTUNIQUE, ['otherid1', 'otherid2']);
        $dbman->create_table($table);

        // Openlms savepoint reached.
        upgrade_plugin_savepoint(true, 2023022600, 'local', 'openlms');
    }

    if ($oldversion < 2023042900) {

        if (file_exists("$CFG->dirroot/enrol/programs/db/upgradelib.php")) {
            require_once("$CFG->dirroot/enrol/programs/db/upgradelib.php");
            enrol_programs_migrate_notifications();
        }

        // Openlms savepoint reached.
        upgrade_plugin_savepoint(true, 2023042900, 'local', 'openlms');
    }

    return true;
}
