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
 * Contains report_bbbparticipation_overview class
 *
 * @package   report_bbbparticipation
 * @copyright  2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * report_bbbparticipation_overview class
 *
 * @package   report_bbbparticipation
 * @copyright  2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_bbbparticipation_overview extends report_bbbparticipation_base implements renderable {

    /** @var string classes to assign to the reporttable */
    protected $tableclass = 'table table-condensed table-hover overview';

    /**
     * Constructor
     *
     * @param int $id course id
     * @param int[] $instances (optional) instances to include
     */
    public function __construct($id, $instances = [0]) {
        parent::__construct($id, $instances);
    }

    /**
     * get html table object representing report data
     *
     * @param boolean $forexport
     * @return html_table report as html_table object
     */
    public function get_table($forexport = false) {

        $context = context_course::instance($this->courseid);

        $performance = new stdClass();
        $performance->start = microtime(true);
        $data = $this->get_coursedata();
        $participationdata = $this->get_participation_data();
        $performance->datafetched = microtime(true);
        $table = new \report_bbbparticipation\html_table_colgroups();

        $table->id = 'user-participation';
        $table->attributes['class'] = $this->tableclass;

        $tableheaders = [];
        $tablecolumns = [];
        $table->colgroups = [];
        $sortable = [];
        $tableheaders['fullnameuser'] = new html_table_cell(get_string('firstname') . '/' . get_string('lastname'));

        $tableheaders['fullnameuser']->header = true;
        $tableheaders['fullnameuser']->rowspan = 2;
        $tableheaders2['fullnameuser'] = null;
        $tablecolumns[] = 'fullnameuser';
        $table->colgroups[] = [
                'span' => '1',
                'class' => 'fullnameuser'
        ];
        $table->colclasses['fullnameuser'] = 'fullnameuser';

        $instances = $this->get_courseinstances();

        $ctr = 1;
        foreach ($instances as $instance) {

            $bbbsessionstime = $this->get_session_time_for_instance($instance->id);
            $span = count($bbbsessionstime);
            if ($span > 0) {
                $tableheaders['instance' . $instance->id] = new html_table_cell($instance->name);
                $tableheaders['instance' . $instance->id]->header = true;
                $tableheaders['instance' . $instance->id]->scope = 'colgroup';
                $tableheaders['instance' . $instance->id]->id = $instance->coursemodule;
                $table->colclasses['instance' . $instance->id] = 'instance' . $instance->id;

                for ($i = 1; $i < $span; $i++) {
                    // Insert empty cells for the colspan!
                    $tableheaders[] = null;
                }
                $tableheaders['instance' . $instance->id]->colspan = $span;
                $table->colgroups[] = [
                 'span' => $span,
                 'class' => 'instancegroup'
                ];

                if (!empty($bbbsessionstime)) {
                    foreach ($bbbsessionstime as $bbbstarts) {
                        $datestring = userdate($bbbstarts, get_string('strftimedatemonthabbr', 'langconfig')) . " " .
                                      userdate($bbbstarts, get_string('strftimetime24', 'langconfig'));
                        $tableheaders2['time' . $ctr . 'i' . $instance->id] = new html_table_cell($datestring);
                        $tableheaders2['time' . $ctr . 'i' . $instance->id]->header = true;
                        $tablecolumns[] = 'time' . $ctr . 'i' . $instance->id;
                        $table->colclasses['time' . $ctr . 'i' . $instance->id] = 'instance' . $instance->id . ' time' . $ctr;
                        $ctr++;
                    }
                }
            }
        }

        $table->head = [];
        $table->head[0] = new html_table_row();
        $table->head[0]->cells = $tableheaders;
        $table->head[1] = new html_table_row();
        $table->head[1]->cells = $tableheaders2;

        foreach ($data as $userid => $curuser) {
            $row = [];
            $userurl = new moodle_url('/user/view.php', [
                'id' => $userid,
                'course' => $this->courseid
            ]);
                $userlink = html_writer::link($userurl, fullname($curuser, has_capability('moodle/site:viewfullnames', $context)));
                $row['fullnameuser'] = new html_table_cell($userlink);
                $ictr = 1;
                $ctr = 1;
            foreach ($instances as $instance) {
                    $bbbsessionstime = $this->get_session_time_for_instance($instance->id);
                    $span = count($bbbsessionstime);
                if ($span > 0) {
                        $sctr = 0;
                    foreach ($bbbsessionstime as $ses) {
                        $text = '1';
                        if ($participationdata['i'. $ictr. 's' . $ctr][$userid] == 0 ) {
                            $text = '0';
                        }
                        $row['time' . $ctr . 'i' . $instance->id] = new html_table_cell($text);
                        $sctr++;
                        $ctr++;
                    }
                }
                $ictr++;
            }

            $table->data[$userid] = new html_table_row();
            $table->data[$userid]->cells = $row;
        }
        $performance->table_built = microtime(true);

        return $table;
    }

    /**
     * Write report data to workbook
     *
     * @param MoodleExcelWorkbook|MoodleODSWorkbook $workbook object to write data into
     * @return void
     */
    public function fill_workbook($workbook) {
        $x = $y = 0;
        $context = context_course::instance($this->courseid);
        $textonlycolumns = get_extra_user_fields($context);
        array_push($textonlycolumns, 'fullname');
        // We start with the html_table-Object.
        $table = $this->get_table(true);

        $worksheet = $workbook->add_worksheet(time());

        // We may use additional table data to format sheets!
        $this->prepare_worksheet($table, $worksheet, $x, $y);

        foreach ($table->head as $headrow) {
            $x = 0;
            foreach ($headrow->cells as $key => $heading) {
                if (!empty($heading) && $this->column_is_hidden($key)) {
                    // Hide column in worksheet!
                    $worksheet->set_column($x, $x + $heading->colspan - 1, 0, null, true);
                }
                $x++;
            }
        }

        if (!empty($table->data)) {
            if (empty($table->head)) {
                // Head was empty, we have to check this here!
                $x = 0;
                $cur = current($table->data);
                $keys = array_keys($cur);
                foreach ($keys as $key) {
                    if ($this->column_is_hidden($key)) {
                        // Hide column in worksheet!
                        $worksheet->set_column($x, $x, 0, null, true);
                    }
                    $x++;
                }
            }

            $oddeven = 1;
            $keys = array_keys($table->data);
            $lastrowkey = end($keys);

            foreach ($table->data as $key => $row) {
                $x = 0;
                // Convert array rows to html_table_rows and cell strings to html_table_cell objects!
                if (!($row instanceof html_table_row)) {
                    $newrow = new html_table_row();
                    
                    foreach ($row as $cell) {
                        if (!($cell instanceof html_table_cell)) {
                            $cell = new html_table_cell($cell);
                        }
                        $newrow->cells[] = $cell;
                    }
                    $row = $newrow;
                }

                $oddeven = $oddeven ? 0 : 1;
                if (isset($table->rowclasses[$key])) {
                    $row->attributes['class'] .= ' ' . $table->rowclasses[$key];
                }

                $row->attributes['class'] .= ' r' . $oddeven;
                if ($key == $lastrowkey) {
                    $row->attributes['class'] .= ' lastrow';
                }

                $keys2 = array_keys($row->cells);
                $lastkey = end($keys2);

                $gotlastkey = false; // Flag for sanity checking.
                foreach ($row->cells as $key => $cell) {
                    if ($gotlastkey) {
                        // This should never happen. Why do we have a cell after the last cell?
                        mtrace("A cell with key ($key) was found after the last key ($lastkey)");
                    }

                    if ($cell == null) {
                        $x++;
                        continue;
                    }

                    if (!($cell instanceof html_table_cell)) {
                        $mycell = new html_table_cell();
                        $mycell->text = $cell;
                        $cell = $mycell;
                    }
                    if ($key == $lastkey) {
                        $gotlastkey = true;
                    }
                    $cell = $this->modify_span($cell);
                    $colorparams = [];
                    if ($this->starts_with($cell->text, '<colorred>')) {
                        $colorparams['bg_color'] = '#e6b8b7';
                    }
                    $format = $workbook->add_format($colorparams);
                    $cell->text = strip_tags($cell->text);
                    // We need this, to overwrite the images for attendance with simple characters!
                    /* If text to be written is numeric, it will be written in number format
                     so it can be used in calculations without further conversion. */
                    if (!empty($cell->character)) {
                        $worksheet->write_string($y, $x, strip_tags($cell->character), $format);
                    } else if (is_numeric($cell->text) && (!in_array($key, $textonlycolumns))) {
                        $worksheet->write_number($y, $x, $cell->text, $format);
                    } else {
                        $worksheet->write_string($y, $x, $cell->text, $format);
                    }
                    $worksheet->merge_cells($y, $x, $y + $cell->rowspan - 1, $x + $cell->colspan - 1);
                    $x++;
                }
                $y++;
            }
        }
    }

}
