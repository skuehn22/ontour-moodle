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

/**
 * Prints an instance of mod_finishing.
 *
 * @package     mod_finishing
 * @copyright   2021 skuehn22 <kuehn.sebastian@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$f = optional_param('f', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('finishing', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('finishing', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($f) {
    $moduleinstance = $DB->get_record('finishing', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('finishing', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_finishing'));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

#$event = \mod_finishing\event\course_module_viewed::create(array(
#    'objectid' => $moduleinstance->id,
#    'context' => $modulecontext
#));

#$event->add_record_snapshot('course', $course);
#$event->add_record_snapshot('finishing', $moduleinstance);
#$event->trigger();#

$userid = $USER->id;
$courseid =  $course->id;

$PAGE->set_url('/mod/finishing/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

echo "<h3>Schüler Liste</h3>";

echo $OUTPUT->render_from_template('finishing/user_list', [$userid, $courseid]);
echo "<br>".$id;


echo $OUTPUT->footer();
