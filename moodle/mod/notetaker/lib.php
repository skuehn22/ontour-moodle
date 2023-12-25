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
 * Library of interface functions and constants.
 *
 * @package     mod_notetaker
 * @copyright   2020 Jo Beaver
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function notetaker_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_notetaker into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $notetaker An object from the form
 * @return int The id of the newly inserted record
 */
function notetaker_add_instance($notetaker) {
    global $DB;

    $notetaker->timecreated = time();
    $notetaker->id = $DB->insert_record('notetaker', $notetaker);

    return $notetaker->id;
}

/**
 * Updates an instance of the mod_notetaker in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $notetaker An object from the form in mod_form.php
 * @return bool True if successful, false otherwise
 */
function notetaker_update_instance($notetaker) {
    global $DB;

    $notetaker->timemodified = time();
    $notetaker->id = $notetaker->instance;

    return $DB->update_record('notetaker', $notetaker);
}

/**
 * Removes an instance of the mod_notetaker from the database.
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return bool True if successful, false on failure
 */
function notetaker_delete_instance($id) {
    global $DB;

    if (!$notetaker = $DB->get_record('notetaker', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('notetaker', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'notetaker', $id, null);

    $DB->delete_records('notetaker', array('id' => $notetaker->id));
    $DB->delete_records('notetaker_notes', array('notetakerid' => $cm->id));

    return true;
}

/**
 * The elements to add the course reset form.
 *
 * @param moodleform $mform
 */
function notetaker_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'notetakerheader', get_string('modulenameplural', 'notetaker'));
    $mform->addElement('checkbox', 'reset_notetaker_notes', get_string('removeallnotetakernotes', 'notetaker'));
    $mform->addHelpButton('reset_notetaker_notes', 'removeallnotetakernotes', 'mod_notetaker');
    $mform->addElement('checkbox', 'reset_notetaker_tags', get_string('removeallnotetakertags', 'notetaker'));
}

/**
 * Course reset form defaults.
 *
 * @param object $course
 * @return array
 */
function notetaker_reset_course_form_defaults($course) {
    return array('reset_notetaker_tags' => 1, 'reset_notetaker_notes' => 1);
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function notetaker_reset_userdata($data) {
    global $DB;

    $status = [];

    $params = array($data->courseid);

    $allnotetakerssql = "SELECT n.id
                         FROM {notetaker} n
                         WHERE n.course = ?";

    // Remove all the notes.
    if (!empty($data->reset_notetaker_notes)) {

        $params[] = 'notetaker_note';
        $DB->delete_records_select('notetaker_notes', "notetakerid IN ($allnotetakerssql)", $params);

        // Loop through the notetakers and remove the tags.
        if ($notetakers = $DB->get_records('notetaker', array('course' => $data->courseid))) {
            foreach ($notetakers as $notetaker) {
                if (!$cm = get_coursemodule_from_instance('notetaker', $notetaker->id)) {
                    continue;
                }

                // Remove the tags.
                $context = context_module::instance($cm->id);
                core_tag_tag::delete_instances('mod_notetaker', null, $context->id);
            }
        }
        $status[] = [
            'component' => get_string('modulenameplural', 'notetaker'),
            'item' => get_string('notetakersreset', 'notetaker'),
            'error' => false
        ];
    }

    // Remove all the tags.
    if (!empty($data->reset_notetaker_tags)) {

        // Loop through the notetakers and remove the tags from the notes.
        if ($notetakers = $DB->get_records('notetaker', array('course' => $data->courseid))) {
            foreach ($notetakers as $notetaker) {
                if (!$cm = get_coursemodule_from_instance('notetaker', $notetaker->id)) {
                    continue;
                }

                $context = context_module::instance($cm->id);
                core_tag_tag::delete_instances('mod_notetaker', null, $context->id);
            }
        }
        $status[] = [
            'component' => get_string('modulenameplural', 'notetaker'),
            'item' => get_string('tagsdeleted', 'notetaker'),
            'error' => false
        ];
    }

    return $status;
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $notetaker   notetaker object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 */
function notetaker_view ($notetaker, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $event = \mod_notetaker\event\course_module_viewed::create(array(
    'objectid' => $notetaker->id,
    'context' => $context
    ));
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('notetaker', $notetaker);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Serves files.
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not to force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function notetaker_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if ($filearea !== 'notefield' && $filearea !== 'intro') {
        return false;
    }

    require_course_login($course, true, $cm);

    $itemid = array_shift($args);

    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/'; // Here $args is empty => the path is '/'.
    } else {
        $filepath = '/'.implode('/', $args).'/'; // Here $args contains elements of the filepath.
    }

    $fs = get_file_storage();

    $file = $fs->get_file($context->id, 'mod_notetaker', 'notefield', $itemid, $filepath, $filename);

    if (!$file) {
        return false; // The file does not exist.
    }

    // Send the file.
    send_stored_file($file, 86400, 0, true, $options);
}

/**
 * Returns notetaker notes tagged with a specified tag.
 *
 * This is a callback used by the tag area mod_notetaker/notetaker_notes to search for notetaker notes
 * tagged with a specific tag.
 *
 * @param core_tag_tag $tag
 * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
 *             are displayed on the page and the per-page limit may be bigger
 * @param int $fromctx context id where the link was displayed, may be used by callbacks
 *            to display items in the same context first
 * @param int $ctx context id where to search for records
 * @param bool $rec search in subcontexts as well
 * @param int $page 0-based number of page being displayed
 * @return \core_tag\output\tagindex
 */
function mod_notetaker_get_tagged_notes($tag, $exclusivemode = false, $fromctx = 0, $ctx = 0, $rec = 1, $page = 0) {
    global $OUTPUT;
    $perpage = $exclusivemode ? 20 : 5;

    // Build the SQL query.
    $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
    $query = "SELECT nn.id, nn.name, nn.notetakerid, nn.userid, nn.publicpost,
                    cm.id AS cmid, c.id AS courseid, c.shortname, c.fullname, $ctxselect
                FROM {notetaker_notes} nn
                JOIN {notetaker} n ON n.id = nn.notetakerid
                JOIN {modules} m ON m.name='notetaker'
                JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = n.id
                JOIN {tag_instance} tt ON nn.id = tt.itemid
                JOIN {course} c ON cm.course = c.id
                JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :coursemodulecontextlevel
            WHERE tt.itemtype = :itemtype AND tt.tagid = :tagid AND tt.component = :component
                AND cm.deletioninprogress = 0
                AND nn.id %ITEMFILTER% AND c.id %COURSEFILTER%";

    $params = array('itemtype' => 'notetaker_notes', 'tagid' => $tag->id, 'component' => 'mod_notetaker',
        'coursemodulecontextlevel' => CONTEXT_MODULE);

    if ($ctx) {
        $context = $ctx ? context::instance_by_id($ctx) : context_system::instance();
        $query .= $rec ? ' AND (ctx.id = :contextid OR ctx.path LIKE :path)' : ' AND ctx.id = :contextid';
        $params['contextid'] = $context->id;
        $params['path'] = $context->path.'/%';
    }

    $query .= " ORDER BY ";
    if ($fromctx) {
        // In order-clause specify that modules from inside "fromctx" context should be returned first.
        $fromcontext = context::instance_by_id($fromctx);
        $query .= ' (CASE WHEN ctx.id = :fromcontextid OR ctx.path LIKE :frompath THEN 0 ELSE 1 END),';
        $params['fromcontextid'] = $fromcontext->id;
        $params['frompath'] = $fromcontext->path.'/%';
    }
    $query .= ' c.sortorder, cm.id, nn.id';

    $totalpages = $page + 1;

    // Use core_tag_index_builder to build and filter the list of items.
    $builder = new core_tag_index_builder('mod_notetaker', 'notetaker_notes', $query, $params, $page * $perpage, $perpage + 1);
    while ($item = $builder->has_item_that_needs_access_check()) {
        context_helper::preload_from_record($item);
        $courseid = $item->courseid;
        if (!$builder->can_access_course($courseid)) {
            $builder->set_accessible($item, false);
            continue;
        }
        $modinfo = get_fast_modinfo($builder->get_course($courseid));
        // Set accessibility of this item and all other items in the same course.
        $builder->walk(function ($taggeditem) use ($courseid, $modinfo, $builder) {
            global $USER;
            if ($taggeditem->courseid == $courseid) {
                $accessible = false;
                if (($cm = $modinfo->get_cm($taggeditem->cmid)) && $cm->uservisible) {
                    if ($taggeditem->userid == $USER->id || $taggeditem->publicpost == 1) {
                        $accessible = true;
                    }
                }
                $builder->set_accessible($taggeditem, $accessible);
            }
        });
    }

    $items = $builder->get_items();
    if (count($items) > $perpage) {
        $totalpages = $page + 2; // We don't need exact page count, just indicate that the next page exists.
        array_pop($items);
    }

    // Build the display contents.
    if ($items) {
        $tagfeed = new core_tag\output\tagfeed();
        foreach ($items as $item) {
            context_helper::preload_from_record($item);
            $modinfo = get_fast_modinfo($item->courseid);
            $cm = $modinfo->get_cm($item->cmid);
            $pageurl = new moodle_url('/mod/notetaker/viewnote.php', array('note' => $item->id, 'cmid' => $item->cmid));
            $pagename = format_string($item->name, true, array('context' => context_module::instance($item->cmid)));
            $pagename = html_writer::link($pageurl, $pagename);
            $courseurl = course_get_url($item->courseid, $cm->sectionnum);
            $cmname = html_writer::link($cm->url, $cm->get_formatted_name());
            $coursename = format_string($item->fullname, true, array('context' => context_course::instance($item->courseid)));
            $coursename = html_writer::link($courseurl, $coursename);
            $icon = html_writer::link($pageurl, html_writer::empty_tag('img', array('src' => $cm->get_icon_url())));
            $tagfeed->add($icon, $pagename, $cmname.'<br>'.$coursename);
        }

        $content = $OUTPUT->render_from_template('core_tag/tagfeed',
                $tagfeed->export_for_template($OUTPUT));

        return new core_tag\output\tagindex($tag, 'mod_notetaker', 'notetaker_notes', $content,
                $exclusivemode, $fromctx, $ctx, $rec, $page, $totalpages);
    }
}
