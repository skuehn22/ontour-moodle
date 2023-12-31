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
 * renderers.php
 *
 * @package    theme_klass
 * @copyright  2015 onwards LMSACE Dev Team (http://www.lmsace.com)
 * @author    LMSACE Dev Team , lmsace.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once('renderers/course_renderer.php');
require_once('renderers/core_renderer.php');

/**
 * Class theme_YOURTHEMENAME_core_h5p_renderer
 *
 * Extends the H5P renderer so that we are able to override the relevant
 * functions declared there
 */
class theme_klass_core_h5p_renderer extends \core_h5p\output\renderer {

    /**
     * Add styles when an H5P is displayed.
     *
     * @param array $styles Styles that will be applied.
     * @param array $libraries Libraries that will be shown.
     * @param string $embedType How the H5P is displayed.
     */
    public function h5p_alter_styles(&$styles, $libraries, $embedType) {
        global $CFG;

        $styles[] = (object) array(
            'path' => $CFG->httpswwwroot . '/theme/klass/style/h5p.css',
            'version' => '?ver=0.0.1',
        );
    }
}