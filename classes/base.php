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
 * Contains the common base class extended by overview, useroverview and userview!
 *
 * @package   report_bbbparticipation
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Base class for bbbparticipation reports with common logic and definitions
 *
 * @package   report_bbbparticipation
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_bbbparticipation_base {

    /** @var int the courses id */
    protected $courseid = 0;

    /** xml based excel format */
    const FORMAT_XLSX = 0;
    /** binary excel format - unused since 2.8! */
    const FORMAT_XLS = 1;
    /** open document format */
    const FORMAT_ODS = 2;
    /** xml format */
    const FORMAT_XML = 3;
    /** plain text file format */
    const FORMAT_TXT = 4;
    /** show all columns */
    const SHOW_ALL_COLUMNS = 'all';

    /** @var object[] report's data */
    protected $data = null;
    /** @var object[] report's participationdata */
    protected $participationdata = null;
    /** @var int[] user ids */
    protected $users = [0];
    /** @var int[] instance ids */
    protected $instances = [0];

    /**
     * Base constructor
     *
     * @param int $id course id
     * @param int[] $instances (optional) array of bbb instances to include
     */
    public function __construct($id, $instances = [0]) {
        $this->courseid = $id;
        $this->instances = $instances;
        $this->init_hidden();
        $this->init_sortby();
    }

    /**
     * returns instances to include
     *
     * @return int[]
     */
    public function get_instances() {
        return $this->instances;
    }

    /**
     * Get's the course data from the DB, saves it and returns it
     *
     * @return object[]
     */
    public function get_coursedata() {
        global $DB, $SESSION;

        $course = $DB->get_record('course', ['id' => $this->courseid], '*', MUST_EXIST);

        // Get all bigbluebutton instances in course!
        $instances = get_all_instances_in_course('bigbluebuttonbn', $course);

        if (!in_array(0, $this->instances)) {
            foreach ($instances as $key => $inst) {
                if (!in_array($inst->id, $this->instances)) {
                    unset($instances[$key]);
                }
            }
        }

        $context = context_course::instance($course->id);

        // Get general data from users!
        list($esql, $params) = get_enrolled_sql($context, 'report/bbbparticipation:students', 0);

        $sql = 'SELECT u.id FROM {user} u ' .
            'LEFT JOIN (' . $esql . ') eu ON eu.id=u.id ' .
            'WHERE u.deleted = 0 AND eu.id=u.id ';
        if (!empty($this->users) && !in_array(0, $this->users)) {
            list($insql, $inparams) = $DB->get_in_or_equal($this->users, SQL_PARAMS_NAMED, 'user');
            $sql .= ' AND u.id ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $users = $DB->get_fieldset_sql($sql, $params);
        $returndata['users'] = $users;

        $data = $this->get_user_data($course, $users, $this->instances);
        $this->data = $data;

        return $this->data;
    }

    /**
     * Get's the general data from the DB, saves it and returns it
     *
     * @param object $course (optional) course object
     * @param int|int[] $userids (optional) array of user ids to include
     * @param int[] $instances (optional) array of bbbparticipation ids to include
     * @return object[]|null
     */
    public function get_user_data($course = null, $userids = 0, $instances = [0]) {
        global $DB, $COURSE, $SESSION;

        $ufields = user_picture::fields('u');

        if ($course == null) {
            $course = $COURSE;
        } else {
            $course = $DB->get_record('course', ['id' => $course->id], '*', MUST_EXIST);
        }
        $courseid = $course->id;

        $context = context_course::instance($courseid);

        if ($userids == 0) {
            $userids = get_enrolled_users($context, '', 0, 'u.*', 'lastname ASC');
        }

        $sortable = [
            'firstname',
            'lastname'
        ];
        $sortarr = $SESSION->bbbparticipation->{$this->courseid}->sort;
        $sort = '';
        foreach ($sortarr as $field => $direction) {
            if (in_array($field, $sortable)) {
                if (!empty($sort)) {
                    $sort .= ', ';
                }
                $sort .= 'u.' . $field . ' ' . $direction;
            }
        }

        if (!empty($sort)) {
            $sort = ' ORDER BY ' . $sort;
        }

        if (!empty($userids)) {
            list($sqluserids, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'user');

            $sql = 'SELECT ' . $ufields . '
                      FROM {user} u
                     WHERE u.id ' . $sqluserids . '
                  GROUP BY u.id ' . $sort;

            $data = $DB->get_records_sql($sql, $userparams);
            return $data;
        }
        return null;
    }

    /**
     * Get all bbb instances in course indexed by bbb id
     *
     * @return object[] associative array of bbb instances indexed by bbbparticipation ids
     */
    public function get_courseinstances() {
        global $DB;
        if (!empty($this->courseid)) {
            $course = $DB->get_record('course', ['id' => $this->courseid], '*', MUST_EXIST);
            $instances = get_all_instances_in_course('bigbluebuttonbn', $course);
            $newinstances = [];
            if (!in_array(0, $this->instances)) {
                foreach ($instances as $key => $inst) {
                    if (in_array($inst->id, $this->instances)) {
                        $newinstances[$inst->id] = $inst;
                    }
                }
            } else {
                foreach ($instances as $key => $inst) {
                    $newinstances[$inst->id] = $inst;
                }
            }

            return $newinstances;
        } else {
            return null;
        }
    }

    /**
     * Get all starttimes for certain bbb instance in course indexed by bbb id
     *
     * @param int $bbbid
     *
     * @return object[] associative array of bbb instances indexed by bbbparticipation ids
     */
    public function get_session_time_for_instance(int $bbbid) {
        global $DB;
        if (!empty($this->courseid)) {
            $sql = "SELECT timecreated FROM {bigbluebuttonbn_logs}
                     WHERE bigbluebuttonbnid = :bbbid
                       AND log = 'Create';";
            $bbbsessionstime = $DB->get_fieldset_sql($sql, ['bbbid' => $bbbid]);
            return $bbbsessionstime;
        } else {
            return null;
        }
    }

    /**
     * Get BBB session participation data
     *
     * @return array
     */
    public function get_participation_data() {
        global $DB, $SESSION;
        $data[] = [];
        $instances = $this->get_courseinstances();

        // Get all userdata in 1 query!
        $context = context_course::instance($this->courseid);
        // Get general data from users!
        list($esql, $params) = get_enrolled_sql($context, 'report/bbbparticipation:students', 0);

        $ictr = 1;
        $ctr = 1;
        foreach ($instances as $instance) {
            $params['bbbid'] = $instance->id;
            $bbbsessionstime = $this->get_session_time_for_instance($instance->id);
            $numberses = count($bbbsessionstime);
            if ($numberses > 0) {
                for ($sctr = 0; $sctr < $numberses; $sctr++) {
                    $inbetweensql = '> :start';
                    $params['start'] = $bbbsessionstime[$sctr];
                    if ($sctr != ($numberses - 1)) {
                        $inbetweensql = 'BETWEEN :start AND (:startnext -1)';
                        $params['startnext'] = $bbbsessionstime[$sctr + 1];
                    }
                    $insql = "    IF (u.id IN (
                     SELECT DISTINCT (l.userid)
                                FROM {bigbluebuttonbn_logs} l
                               WHERE l.bigbluebuttonbnid = :bbbid
                                 AND l.log  = 'Join'
                                 AND l.timecreated " . $inbetweensql . "),'1','0') att";

                    $sql = 'SELECT u.id, ' . $insql .
                            ' FROM {user} u ' .
                        'LEFT JOIN (' . $esql . ') eu ON eu.id=u.id ' .
                        'WHERE u.deleted = 0 AND eu.id=u.id ';
                    if (!empty($this->users) && !in_array(0, $this->users)) {
                        list($insql, $inparams) = $DB->get_in_or_equal($this->users, SQL_PARAMS_NAMED, 'user');
                        $sql .= ' AND u.id ' . $insql;
                        $params = array_merge($params, $inparams);
                    }

                    $attend = $DB->get_records_sql_menu($sql, $params);
                    $data['i'. $ictr. 's' . $ctr] = $attend;
                    $ctr++;
                }
            }
            $ictr++;
        }

        $this->participationdata = $data;
        return $this->participationdata;
    }

    /**
     * Get's the course id
     *
     * @return int course id
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * Prepares session object to contain data about hidden columns
     *
     * @return void
     */
    public function init_hidden() {
        global $SESSION;

        $thide = optional_param('thide', null, PARAM_ALPHANUM);
        $tshow = optional_param('tshow', null, PARAM_ALPHANUM);
        if (!isset($SESSION->bbbparticipation)) {
            $SESSION->bbbparticipation = new stdClass();
        }
        if (!isset($SESSION->bbbparticipation->{$this->courseid})) {
            $SESSION->bbbparticipation->{$this->courseid} = new stdClass();
        }
        if (!isset($SESSION->bbbparticipation->{$this->courseid}->hidden)) {
            $SESSION->bbbparticipation->{$this->courseid}->hidden = [];
        }
        if (!empty($thide) && !in_array($thide, $SESSION->bbbparticipation->{$this->courseid}->hidden)) {
            $SESSION->bbbparticipation->{$this->courseid}->hidden[] = $thide;
        }
        if ($tshow === self::SHOW_ALL_COLUMNS) {
            unset($SESSION->bbbparticipation->{$this->courseid}->hidden);
        } else if (!empty($tshow)) {
            foreach ($SESSION->bbbparticipation->{$this->courseid}->hidden as $idx => $hidden) {
                if ($hidden == $tshow) {
                    unset($SESSION->bbbparticipation->{$this->courseid}->hidden[$idx]);
                }
            }
        }

    }

    /**
     * Prepares session object to contain data about sorting order of the report table
     *
     * @return void
     */
    public function init_sortby() {
        global $SESSION;

        $tsort = optional_param('tsort', null, PARAM_ALPHANUM);

        if (!isset($SESSION->bbbparticipation)) {
            $SESSION->bbbparticipation = new stdClass();
        }
        if (!isset($SESSION->bbbparticipation->{$this->courseid})) {
            $SESSION->bbbparticipation->{$this->courseid} = new stdClass();
        }
        if (!isset($SESSION->bbbparticipation->{$this->courseid}->sort)) {
            $SESSION->bbbparticipation->{$this->courseid}->sort = [];
        }

        if (!empty($tsort)) {
            $arr = $SESSION->bbbparticipation->{$this->courseid}->sort;
            if (!key_exists($tsort, $SESSION->bbbparticipation->{$this->courseid}->sort)) {
                // Like array_unshift with associative key preservation!
                $arr = array_reverse($arr, true);
                $arr[$tsort] = 'ASC';
                $SESSION->bbbparticipation->{$this->courseid}->sort = array_reverse($arr, true);
            } else {
                switch ($tsort) {
                    case 'bbbparticipation':
                        if ($arr[$tsort] == 'ASC') {
                            $arr[$tsort] = 'DESC';
                        } else {
                            unset($arr[$tsort]);
                        }
                        break;
                    default:
                        reset($arr);
                        // Bring to front!
                        if (key($arr) != $tsort) {
                            $tmp = $arr[$tsort];
                            unset($arr[$tsort]);
                            $arr = array_reverse($arr, true);
                            $arr[$tsort] = $tmp;
                            $arr = array_reverse($arr, true);
                        }
                        // Reverse sort order!
                        $arr[$tsort] = $arr[$tsort] == 'ASC' ? 'DESC' : 'ASC';
                        break;
                }
                $SESSION->bbbparticipation->{$this->courseid}->sort = $arr;
            }
        }
    }

    /**
     * Returns link to change sort order of the table including icon to visualize current sorting
     *
     * @param string $column internal column name
     * @param string $text displayed column name / link text
     * @param string|moodle_url $url the base url for all links
     * @return string HTML snippet
     */
    public function get_sortlink($column, $text, $url) {
        global $SESSION, $OUTPUT;
        $url = $this->get_full_url($url);
        // Sortarray has to be initialized!
        $sortarr = $SESSION->bbbparticipation->{$this->courseid}->sort;
        reset($sortarr);
        $primesort = key($sortarr);
        if (($primesort == 'bbbparticipation') && ($column != 'bbbparticipation')) {
            next($sortarr);
            $primesort = key($sortarr);
        }
        if (($column == $primesort)
                ) {
            // We show only the first sortby column and bbbparticipation!
            switch ($sortarr[$column]) {
                case 'ASC':
                    $text .= $OUTPUT->pix_icon('t/up', get_string('desc'));
                    break;
                case 'DESC':
                    $text .= $OUTPUT->pix_icon('t/down', get_string('asc'));
                    break;
            }
        }
        $sorturl = new moodle_url($url, ['tsort' => $column]);
        $sortlink = html_writer::link($sorturl, $text);

        return $sortlink;
    }

    /**
     * Get link with selected instances
     *
     * @param string|moodle_url $url the base url for all links
     * @return moodle_url
     */
    public function get_full_url($url) {
        global $USER;
        $instances = $this->get_instances();
        if (!in_array(0, $instances)) {
            $url->param('userid', $USER->id);
            $url->param('sesskey', sesskey());
            $url->param('_qf__report_bbbparticipation_reportfilterform', '1');
            $url->param('instances', '_qf__force_multiselect_submission');
            foreach ($instances as $inst) {
                $url->param('instances[]', $inst);
            }
            $url->param('submitbutton', 'Update');
        }

        return $url;
    }
    /**
     * Checks if a column is currently hidden
     *
     * @param string $column internal column name
     * @return bool true if column is hidden
     */
    public function column_is_hidden($column = 'nonexistend') {
        global $SESSION;
        if (!isset($SESSION->bbbparticipation)) {
            $SESSION->bbbparticipation = new stdClass();
            $SESSION->bbbparticipation->{$this->courseid} = new stdClass();
            $SESSION->bbbparticipation->{$this->courseid}->hidden = [];

            return 0;
        }
        if (!isset($SESSION->bbbparticipation->{$this->courseid})) {
            $SESSION->bbbparticipation->{$this->courseid} = new stdClass();
            $SESSION->bbbparticipation->{$this->courseid}->hidden = [];

            return 0;
        }
        if (!isset($SESSION->bbbparticipation->{$this->courseid}->hidden)) {
            $SESSION->bbbparticipation->{$this->courseid}->hidden = [];

            return 0;
        }

        if ((array)$column !== $column) {
            return in_array($column, $SESSION->bbbparticipation->{$this->courseid}->hidden);
        } else {
            $return = false;
            foreach ($column as $cur) {
                $return = $return || in_array($cur, $SESSION->bbbparticipation->{$this->courseid}->hidden);
            }

            return $return;
        }
    }

    /**
     * Checks if no column is currently hidden
     *
     * @return bool True is no column is hidden, False if at least one column is hidden
     */
    public function check_all_columns_visible() {
        // Call column_is_hidden to initialize hidden array if not present.
        global $SESSION;
        $this->column_is_hidden();
        if (empty($SESSION->bbbparticipation->{$this->courseid}->hidden)) {
            return true;
        }
        return false;
    }

    /**
     * get report as open document file (sends to browser, forces download)
     *
     * @return void
     */
    public function get_ods() {
        global $CFG, $DB;

        require_once($CFG->libdir . "/odslib.class.php");

        $workbook = new MoodleODSWorkbook("-");

        $this->fill_workbook($workbook);

        $course = $DB->get_record('course', ['id' => $this->courseid]);

        $filename = get_string('pluginname', 'report_bbbparticipation') . '_' . $course->shortname;
        $workbook->send($filename . '.ods');
        $workbook->close();
    }

    /**
     * get report as xml based excel file (sends to browser, forces download)
     *
     * @return void
     */
    public function get_xlsx() {
        global $CFG, $DB;

        require_once($CFG->libdir . "/excellib.class.php");

        $workbook = new MoodleExcelWorkbook("-", 'Excel2007');

        $this->fill_workbook($workbook);

        $course = $DB->get_record('course', ['id' => $this->courseid]);

        $filename = get_string('pluginname', 'report_bbbparticipation') . '_' . $course->shortname;
        $workbook->send($filename);
        $workbook->close();
    }

    /**
     * Prepare a worksheet for writing the table data
     *
     * @param stdClass $table data for writing into the worksheet
     * @param stdClass $worksheet worksheet to prepare
     * @param int $x current column
     * @param int $y current line
     */
    public function prepare_worksheet(&$table, &$worksheet, &$x, &$y) {
        // Prepare table data and populate missing properties with reasonable defaults!
        if (!empty($table->align)) {
            foreach ($table->align as $key => $aa) {
                if ($aa) {
                    $table->align[$key] = fix_align_rtl($aa);  // Fix for RTL languages!
                } else {
                    $table->align[$key] = null;
                }
            }
        }
        if (!empty($table->size)) {
            foreach ($table->size as $key => $ss) {
                if ($ss) {
                    $table->size[$key] = $ss;
                } else {
                    $table->size[$key] = null;
                }
            }
        }

        if (!empty($table->head)) {
            $keys = array_keys($table->head);
            foreach ($keys as $key) {
                if (!isset($table->align[$key])) {
                    $table->align[$key] = null;
                }
                if (!isset($table->size[$key])) {
                    $table->size[$key] = null;
                }
            }
        }

        if (!empty($table->head)) {
            foreach ($table->head as $row => $headrow) {
                $x = 0;
                $keys = array_keys($headrow->cells);

                foreach ($headrow->cells as $key => $heading) {
                    // Convert plain string headings into html_table_cell objects!
                    if (!($heading instanceof html_table_cell)) {
                        $headingtext = $heading;
                        $heading = new html_table_cell();
                        $heading->text = $headingtext;
                        $heading->header = true;
                    }

                    if ($heading->text == null) {
                        $x++;
                        $table->head[$row]->cells[$key] = $heading;
                        continue;
                    }

                    if ($heading->header !== false) {
                        $heading->header = true;
                    }

                    if (!isset($heading->colspan)) {
                        $heading->colspan = 1;
                    }
                    if (!isset($heading->rowspan)) {
                        $heading->rowspan = 1;
                    }
                    $table->head[$row]->cells[$key] = $heading;

                    $worksheet->write_string($y, $x, strip_tags($heading->text));
                    $worksheet->merge_cells($y, $x, $y + $heading->rowspan - 1, $x + $heading->colspan - 1);

                    $x++;
                }
                $y++;
            }
        }
    }

    /**
     * Checks if a given string starts with another given string
     *
     * @param string $string String that should be checked
     * @param string $startstring String $string's beginning schould be checked for
     * @return bool True if $string starts with $startString, False if not
     */
    public function starts_with ($string, $startstring) {
        $len = strlen($startstring);
        return (substr($string, 0, $len) === $startstring);
    }

    /**
     * Utility function for modifying row and colspan
     *
     * @param stdClass $cell Cell that should be modified
     * @return stdClass Modified cell
     */
    public function modify_span($cell) {
        if (!isset($cell->rowspan)) {
            $cell->rowspan = 1;
        }
        if (!isset($cell->colspan)) {
            $cell->colspan = 1;
        }
        return $cell;
    }

}
