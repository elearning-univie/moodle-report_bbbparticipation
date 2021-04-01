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
 * BBB Participation report
 *
 * @package   report_bbbparticipation
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/lib/tablelib.php');
require_once($CFG->dirroot.'/report/bbbparticipation/locallib.php');
require_once($CFG->dirroot.'/report/bbbparticipation/lib.php');


define('DEFAULT_PAGE_SIZE', 20);
define('SHOW_ALL_PAGE_SIZE', 5000);

$id         = required_param('id', PARAM_INT); // course id.
$roleid     = optional_param('roleid', 0, PARAM_INT); // which role to show
$action     = optional_param('action', '', PARAM_ALPHA);

$url = new moodle_url('/report/bbbparticipation/index.php', array('id' => $id));

$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

if ($action != 'view' and $action != 'post') {
    $action = ''; // default to all (don't restrict)
}

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourse');
}

if ($roleid != 0 and !$role = $DB->get_record('role', array('id' => $roleid))) {
    print_error('invalidrole');
}

require_login($course);
$context = context_course::instance($course->id);
require_capability('report/bbbparticipation:view', $context);
$strparticipation = get_string('bbbparticipationreport', 'report_bbbparticipation');
$PAGE->set_title(format_string($course->shortname, true, array('context' => $context)) .': '. $strparticipation);
$PAGE->set_heading(format_string($course->fullname, true, array('context' => $context)));
$PAGE->set_context($context);
$PAGE->set_course($course);

$output = $PAGE->get_renderer('report_bbbparticipation');

echo $output->header();

$customdata = [
    'courseid' => $id,
    'hideusers' => true
];

$mform = new report_bbbparticipation_reportfilterform($PAGE->url, $customdata, 'get');
if ($data = $mform->get_data()) {
    $instances = $data->instances;

} else {
    $instances = optional_param_array('instances', [0], PARAM_INT);
    $mform->set_data(['instances' => $instances]);
}
$mform->display();

$bbbparticipationreport = new report_bbbparticipation_overview($id, $instances);

echo $output->render($bbbparticipationreport);

echo $output->footer();

