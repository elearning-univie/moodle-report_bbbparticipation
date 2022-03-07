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
 * The questiontype class for the flashcard question type.
 *
 * @package    report_bbbparticipation
 * @copyright  2022 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_bbbparticipation\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for report_bbbparticipation.
 */
class bbb_observer {
    /**
     * Triggered via bbb activity_viewed core event
     *
     * @param \mod_bigbluebuttonbn\event\activity_viewed $event
     */
    public static function course_module_viewed(\mod_bigbluebuttonbn\event\activity_viewed $event) {
        global $PAGE;

        $courseid = $event->courseid;
        $bbbid = $event->objectid;
        $url = new \moodle_url('/report/bbbparticipation/index.php');
        $url->param('id', $courseid);
        $url->param('sesskey', sesskey());
        $url->param('bbbs[]', $bbbid);
        $linktext = get_string('bbbparticipation:view', 'report_bbbparticipation');
        $PAGE->requires->js_call_amd('report_bbbparticipation/bbbreportlink', 'init',
            ['reportlink' => $url->out(false), 'linktext' => $linktext]);

    }

}
