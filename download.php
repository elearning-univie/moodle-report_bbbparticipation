<?php
// This file is part of report_bbbparticipation for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Serves download-files
 *
 * @package   report_bbbparticipation
 * @copyright  2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

$id = required_param('id', PARAM_INT);   // Course.

$users = optional_param_array('users', [0], PARAM_INT);
$instances = optional_param_array('bbbs', [0], PARAM_INT);
$format = optional_param('format', report_bbbparticipation_base::FORMAT_XLSX, PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course);

$coursecontext = context_course::instance($course->id);

require_capability('report/bbbparticipation:view', $coursecontext, $USER->id);

$PAGE->set_pagelayout('popup');
$arrays = http_build_query([
        'users' => $users,
        'instances' => $instances
]);
$PAGE->set_url('/report/bbbparticipation/download.php?' . $arrays, [
        'id' => $id,
        'format' => $format
]);

$output = $PAGE->get_renderer('report_bbbparticipation');

switch ($format) {
    case report_bbbparticipation_base::FORMAT_CSV:
        $formatreadable = 'CSV';
        break;
    case report_bbbparticipation_base::FORMAT_ODS:
        $formatreadable = 'ODS';
        break;
    default:
    case report_bbbparticipation_base::FORMAT_XLSX:
        $formatreadable = 'XLSX';
        break;
}

$report = new report_bbbparticipation_overview($id, $instances);

switch ($format) {
    case report_bbbparticipation_base::FORMAT_CSV:
        $report->get_csv();
        break;
    case report_bbbparticipation_base::FORMAT_ODS:
        $report->get_ods();
        break;
    default:
    case report_bbbparticipation_base::FORMAT_XLSX:
        $report->get_xlsx();
        break;
}
