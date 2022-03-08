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

$id         = required_param('id', PARAM_INT); // Course id.
$roleid     = optional_param('roleid', 0, PARAM_INT); // Which role to show.
$action     = optional_param('action', '', PARAM_ALPHA);
$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);
$bbbsel = optional_param_array('bbbs', [0], PARAM_INT);
$rolesel = optional_param_array('r', [0], PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

if (!in_array($perpage, [10, 20, 50, 100], true)) {
    $perpage = DEFAULT_PAGE_SIZE;
}

$params = array();
$params['id'] = $id;
$params['perpage'] = $perpage;
if ($download !== '') {
    $params['perpage'] = $download;
}
foreach ($bbbsel as $bbb) {
    $params['bbbs[' . $bbb. ']'] = $bbb;
}
foreach ($rolesel as $r) {
    $params['r[' . $r. ']'] = $r;
}
$url = new moodle_url('/report/bbbparticipation/index.php', $params);

$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

if ($action != 'view' and $action != 'post') {
    $action = '';
}

if (!$course = $DB->get_record('course', array('id' => $id))) {
    new moodle_exception('invalidcourse');
}

if ($roleid != 0 and !$role = $DB->get_record('role', array('id' => $roleid))) {
    new moodle_exception('invalidrole');
}

require_login($course);
$context = context_course::instance($course->id);

require_capability('report/bbbparticipation:view', $context);
$strparticipation = get_string('bbbparticipationreport', 'report_bbbparticipation');
$PAGE->set_title(format_string($course->shortname, true, array('context' => $context)) .': '. $strparticipation);
$PAGE->set_heading(format_string($course->fullname, true, array('context' => $context)));
$PAGE->set_context($context);
$PAGE->set_course($course);

list($fields, $params, $fieldnames, $fieldheaders) = report_bbbparticipation_get_sql_fields($id, $bbbsel);
$params['courseid'] = $course->id;
if ($fields) {
    $fields = ', ' . $fields;
}
list($ctxsql, $ctxparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'ctx');
$params = array_merge($params, $ctxparams);

$configs = get_config('report_bbbparticipation', 'roles_shown');
$showroles = $rolesel;
if (in_array(0, $rolesel)) {
    $showroles = explode(",", $configs);
}
list($rolessql, $rolesparams) = $DB->get_in_or_equal($showroles, SQL_PARAMS_NAMED, 'role');
$params = array_merge($params, $rolesparams);

$table = new report_bbbparticipation\output\attendancetable('uniqueid', $context->id, $PAGE->url,
         $id, $fieldnames, $fieldheaders, $perpage);
$table->set_sql("u.id, u.picture, u.firstname, u.lastname, arty, u.firstnamephonetic, u.lastnamephonetic,
                 u.middlename, u.alternatename, u.imagealt, u.email, u.idnumber $fields",
    "{user} u LEFT JOIN (SELECT DISTINCT eu2_u.id, ra.arty
                             FROM {user} eu2_u
                             JOIN {user_enrolments} ej2_ue ON ej2_ue.userid = eu2_u.id
                             JOIN {enrol} ej2_e ON (ej2_e.id = ej2_ue.enrolid AND ej2_e.courseid = :courseid)
                             JOIN (SELECT DISTINCT userid, r.archetype as arty
                                FROM {role_assignments} ras
                                JOIN {role} r ON  r.id = ras.roleid
                               WHERE contextid $ctxsql
                                 AND r.id $rolessql
                             ) ra ON ra.userid = eu2_u.id",
                        " 1 = 1 AND eu2_u.deleted = 0 AND eu2_u.id <> 1 AND eu2_u.deleted = 0) eu ON eu.id=u.id
                        WHERE u.deleted = 0 AND eu.id=u.id", $params);

$table->define_baseurl($PAGE->url);

$filename = trim($course->shortname, " ") . trim(get_string('bbbparticipationreport', 'report_bbbparticipation'), " ");
$table->is_downloading($download, $filename);
if ($table->is_downloading()) {
    raise_memory_limit(MEMORY_EXTRA);
    $table->download($download);
}

$bbbs = report_bbbparticipation_get_bbb_activities_for_course($COURSE->id);

$bbbselects = [get_string('allbbbs', 'report_bbbparticipation')];
foreach ($bbbs as $bbbs) {
    $bbbselects[$bbbs->id] = $bbbs->name;
}

$templateinfo = [
    'id' => $id,
    'sesskey' => sesskey(),
    'actionurl' => $PAGE->url,
    'bbbselects' => $bbbselects
];
$options = $bbbselects;
$selects = [];

$selects[] = [
    'options' => array_map(function($option) use ($options, $bbbsel) {
        return [
        'name' => $options[$option],
        'value' => $option,
        'selected' => in_array($option, $bbbsel)
        ];
    }, array_keys($options))
    ];

$templateinfo['selects'] = $selects;

// Role select.
 list($rsql, $rparams) = $DB->get_in_or_equal(explode(",", $configs));
 $sql = "SELECT * FROM {role} WHERE id $rsql";
 $rrecords = $DB->get_records_sql($sql, $rparams);

$roleselects = [get_string('allroles', 'report_bbbparticipation')];
foreach ($rrecords as $roles) {
    $roleselects[$roles->id] = $roles->archetype;
}

$templateinfo['roleselects'] = $roleselects;
$roptions = $roleselects;
$rselects = [];

$rselects[] = [
    'roptions' => array_map(function($roption) use ($roptions, $rolesel) {
        return [
        'rname' => $roptions[$roption],
        'rvalue' => $roption,
        'rselected' => in_array($roption, $rolesel)
        ];
    }, array_keys($roptions))
    ];

 $templateinfo['rselects'] = $rselects;

$templateinfo['selected' . $perpage] = true;
$output = $PAGE->get_renderer('core');

$configs = get_config('report_bbbparticipation', 'roles_shown');

if (!$table->is_downloading()) {
    echo $output->header();
    echo $output->render_from_template('report_bbbparticipation/reportform', $templateinfo);
    $table->out($perpage, false);
    echo $output->footer();
}
