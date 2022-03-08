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
 * Table class for displaying the attendance for BBB meetings
 *
 * @package    report_bbbparticipation
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_bbbparticipation\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once('locallib.php');

use moodle_url;
use table_sql;
use html_writer;
use context_module;

/**
 * Table class for displaying the flashcard list of an activity for a teacher.
 *
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendancetable extends table_sql {

    /** @var int course module id */
    private $cmid;

    /** @var int course id */
    private $courseid;

    /** @var array array to save previously looked up authors */
    private $authors;

    /**
     * studentviewtable constructor.
     * @param int $uniqueid
     * @param int $cmid
     * @param string $callbackurl
     * @param int $courseid
     * @param array $fieldnames
     * @param array $fieldheaders
     * @param int $perpage
     * @throws \coding_exception
     */
    public function __construct($uniqueid, $cmid, $callbackurl, $courseid, $fieldnames, $fieldheaders, $perpage) {
        parent::__construct($uniqueid);
        $this->cmid = $cmid;
        $this->courseid = $courseid;
        $this->baseurl = $callbackurl;
        $this->authors = array();
        $this->perpage = $perpage;

        $columns = array('fullname', 'arty', 'idnumber');
        $columns = array_merge($columns, $fieldnames);

        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = array(
            get_string('fullname'),
            get_string('role'),
            get_string('idnumber'),
        );
        $headers = array_merge($headers, $fieldheaders);

        $sortcolumn = 'name';

        $this->collapsible(true);
        $this->define_headers($headers);
        $this->sortable(true, $sortcolumn);
        $this->pageable(true);
        $this->is_downloadable(true);

        $this->column_style('fullname', 'width', '20%');
        $this->column_style('fullname', 'white-space', 'nowrap');

    }

    /**
     * Generate the fullname column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_fullname($data) {
        global $OUTPUT;

        if (!$this->is_downloading()) {
            return $OUTPUT->user_picture($data, array('size' => 35, 'courseid' => $this->courseid, 'includefullname' => true));
        } else {
            $fullname = $data->firstname . ' ' . $data->lastname;
            return $fullname;
        }
    }

    /**
     * If there is not a col_{column name} method then we call this method. If it returns null
     * that means just output the property as in the table raw data. If this returns none null
     * then this is the output for this cell of the table.
     *
     * @param string $colname  The name of this column.
     * @param object $response The raw data for this row.
     * @return string|null The value for this cell of the table or null means use raw data.
     */
    public function other_cols($colname, $response) {
        global $OUTPUT;

        if (substr($colname, 0, 3) == 'att') {
            if (!$this->is_downloading()) {
                if ($response->$colname == '1') {
                    return html_writer::tag('div', $OUTPUT->pix_icon('t/check', get_string('yes')),
                        ['class' => 'content', 'style' => 'color: green']);
                } else {
                    return html_writer::tag('div', $OUTPUT->pix_icon('e/cancel', get_string('no')),
                        ['class' => 'content', 'style' => 'color: red']);
                }
            }
        }
        return null;
    }
    /**
     * Download the BBB attendance report in the selected format.
     *
     * @param string $format The format to download the report.
     */
    public function download($format) {

        $this->is_downloading($format);
        $this->out($this->perpage, false);
    }
}
