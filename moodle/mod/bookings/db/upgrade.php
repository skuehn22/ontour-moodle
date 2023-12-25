<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_bookings_upgrade($oldversion) {
global $DB;
$dbman = $DB->get_manager();

if ($oldversion < 2023092810) {
// Define field custom_column to be added to local_myplugin_table.
$table = new xmldb_table('booking_data3');
$field = new xmldb_field('product', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, 'previous_field');

// Conditionally launch add field custom_column.
if (!$dbman->field_exists($table, $field)) {
$dbman->add_field($table, $field);
}

// Myplugin savepoint reached.
    upgrade_mod_savepoint(true, 2023092810, 'bookings');
}

return true;
}