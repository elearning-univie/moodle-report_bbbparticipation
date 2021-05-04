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
 * This file contains functions used by the participation reports
 *
 * @package   report_bbbparticipation
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns list of all BBB activities in this course
 *
 * @param int $courseid
 * @return array
 */
function report_bbbparticipation_get_bbb_activities_for_course(int $courseid) {
    global $DB;

    $bbblist = $DB->get_records('bigbluebuttonbn', ['course' => $courseid], 'id');
    return $bbblist;
}

/**
 * Helper function renders wait for moderator settings if the feature is enabled.
 *
 * @param object $renderer
 *
 * @return void
 */
function report_bbbparticipation_settings_additionaluserinfo(&$renderer) {

    $renderer->render_group_header('additionaluserinfo');
    $renderer->render_group_element(
        'additionaluserinfo_default',
        $renderer->render_group_element_checkbox('additionaluserinfo_default', 0)
        );

}