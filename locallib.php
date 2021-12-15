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
 * Dynamically build sql fields
 *
 * @param int $courseid
 * @param array $instances
 * @return array[]
 */
function report_bbbparticipation_get_sql_fields(int $courseid, array $instances) {
    global $DB;

    $fields = [];
    $fieldnames = [];
    $fieldheaders = [];
    $params = [];

    if (in_array(0, $instances)) {
        $instances = $DB->get_records_sql('SELECT * FROM {bigbluebuttonbn} WHERE course = :courseid', ['courseid' => $courseid]);
    } else {
        list($sqlinoreqal, $params) = $DB->get_in_or_equal($instances, SQL_PARAMS_NAMED);
        $params['courseid'] = $courseid;
        $instances = $DB->get_records_sql("SELECT * FROM {bigbluebuttonbn} WHERE course = :courseid AND id $sqlinoreqal", $params);
    }

    foreach ($instances as $instance) {
        $bbbsessionstime = report_bbbparticipation_get_session_time_for_bbb_activity($instance->id, $courseid);
        $numberses = count($bbbsessionstime);
        if ($numberses > 0) {
            for ($sctr = 0; $sctr < $numberses; $sctr++) {
                $instancename = (strlen($instance->name) > 20) ? substr($instance->name, 0, 17).'...' : $instance->name;
                $datestring = $instancename . "<br>" .
                    userdate($bbbsessionstime[$sctr], get_string('strftimedatemonthabbr', 'langconfig')) . " " .
                    userdate($bbbsessionstime[$sctr], get_string('strftimedaytime', 'langconfig'));
                    $inbetweensql = '> :start'.$instance->id . 's' . $sctr;
                    $params['start'.$instance->id . 's' . $sctr] = $bbbsessionstime[$sctr];
                if ($sctr != ($numberses - 1)) {
                    $inbetweensql = 'BETWEEN :start'.$instance->id . 's' . $sctr.
                                    ' AND (:startnext'.$instance->id . 's' . $sctr.' -1)';
                    $params['startnext'.$instance->id . 's' . $sctr] = $bbbsessionstime[$sctr + 1];
                }
                $insql = "    IF (u.id IN (
                         SELECT DISTINCT (l.userid)
                                    FROM {bigbluebuttonbn_logs} l
                                   WHERE l.bigbluebuttonbnid = $instance->id
                                     AND l.log  = 'Join'
                                     AND l.timecreated " . $inbetweensql . "),'1','0') att".$instance->id . 's' . $sctr;
                array_push($fields, $insql);
                array_push($fieldnames, 'att'.$instance->id . 's' . $sctr);
                array_push($fieldheaders, $datestring);
            }
        }
    }

    $fields = implode(", ", $fields);
    return array($fields, $params, $fieldnames, $fieldheaders);
}

/**
 * Get all start times for a specific bbb instance.
 *
 * @param int $bbbid
 * @param int $courseid
 *
 * @return object[] associative array of bbb instances indexed by bbbparticipation ids
 */
function report_bbbparticipation_get_session_time_for_bbb_activity(int $bbbid, int $courseid) {
    global $DB;
    if (!empty($courseid)) {
        $sql = "SELECT timecreated FROM {bigbluebuttonbn_logs}
                     WHERE bigbluebuttonbnid = :bbbid
                       AND log = 'Create';";
        $bbbsessionstime = $DB->get_fieldset_sql($sql, ['bbbid' => $bbbid]);
        return $bbbsessionstime;
    } else {
        return null;
    }
}
