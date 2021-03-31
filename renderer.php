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
 * Contains the renderer class for report_bbbparticipation
 *
 * @package   report_bbbparticipation
 * @copyright  2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer class for BBB Participation report
 *
 * @package   report_bbbparticipation
 * @copyright  2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_bbbparticipation_renderer extends plugin_renderer_base {
    /**
     * Renders the overview report (big html table with all users and all instances - except of filtered ones)
     *
     * @param report_bbbparticipation_overview $report The bbbparticipationreport to render.
     * @return string HTML snippet
     */
    protected function render_report_bbbparticipation_overview(report_bbbparticipation_overview $report) {
        // Render download links!
        $data = [
                'id' => $report->get_courseid(),
                'sesskey' => sesskey(),
                'format' => report_bbbparticipation_base::FORMAT_XLSX
        ];

        /*$bbbs = $report->get_instances();
         $tabletoolbar = html_writer::tag('div', $this->get_downloadlinks(['bbbs' => $bbbs], $data),
                  ['class' => 'download']);
        $tabletoolbar .= html_writer::tag('div', $this->get_reset_table_preferences_link($report)); */
        $tabletoolbar = html_writer::tag('div', $this->get_reset_table_preferences_link($report));
        $out = html_writer::tag('div', $tabletoolbar, ['class' => 'tabletoolbar']);
        // Render the table!
        $table = $report->get_table();
        $out .= html_writer::tag('div', $this->table($table, $report),
             ['class' => 'scrollforced']);

        return $this->output->container($out, 'submission', 'bbbparticipationtable');
    }


    /**
     * Helper function to return the download links
     *
     * @param mixed[] $arrays arrays to include in get parameters
     * @param mixed[] $data array of data to include in get parameters
     * @return string HTML snippet
     */
    private function get_downloadlinks($arrays, $data) {
        $arrays = http_build_query($arrays);
        $uri = new moodle_url('/report/bbbparticipation/download.php?' . $arrays, $data);
        $downloadlinks = get_string('exportas', 'report_bbbparticipation');
        $downloadlinks .= html_writer::tag('span',
                html_writer::link($uri, '.XLSX'),
                ['class' => 'downloadlink']);
        $uri = new moodle_url($uri, ['format' => report_bbbparticipation_base::FORMAT_ODS]);
        $downloadlinks .= html_writer::tag('span',
                html_writer::link($uri, '.ODS'),
                ['class' => 'downloadlink']);
        $uri = new moodle_url($uri, ['format' => report_bbbparticipation_base::FORMAT_XML]);
        $downloadlinks .= html_writer::tag('span',
                html_writer::link($uri, '.XML'),
                ['class' => 'downloadlink']);
        $uri = new moodle_url($uri, ['format' => report_bbbparticipation_base::FORMAT_TXT]);
        $downloadlinks .= html_writer::tag('span',
                html_writer::link($uri, '.TXT'),
                ['class' => 'downloadlink']);

        return $downloadlinks;
    }

    /**
     * Helper function to return link for resetting as table preferences if any columns are hidden
     *
     * @param null $report Report for determining if any columns are hidden
     * @return string HTML for link if any columns are hidden, '' if not
     * @throws coding_exception
     */
    private function get_reset_table_preferences_link($report) {

        if (!empty($report) && !$report->check_all_columns_visible()) {
            return html_writer::tag('div', html_writer::link(new moodle_url($this->page->url,
                    ['tshow' => report_bbbparticipation_base::SHOW_ALL_COLUMNS]), get_string('resettable')));
        }
        return '';
    }

    /**
     * Renders HTML table
     *
     * This method may modify the passed instance by adding some default properties if they are not set yet.
     * If this is not what you want, you should make a full clone of your data before passing them to this
     * method. In most cases this is not an issue at all so we do not clone by default for performance
     * and memory consumption reasons.
     *
     * @param html_table $table data to be rendered
     * @param report_bbbparticipation_base $report optional if given table can hide columns
     * @return string HTML code
     */
    protected function table(html_table $table, report_bbbparticipation_base $report = null) {
        global $USER;

        if ($report == null) {
            $nohide = true;
        } else {
            $nohide = false;
        }

        // Prepare table data and populate missing properties with reasonable defaults!
        if (!empty($table->align)) {
            foreach ($table->align as $key => $aa) {
                if ($aa) {
                    $table->align[$key] = 'text-align:' . fix_align_rtl($aa) . ';';  // Fix for RTL languages!
                } else {
                    $table->align[$key] = null;
                }
            }
        }
        if (!empty($table->size)) {
            foreach ($table->size as $key => $ss) {
                if ($ss) {
                    $table->size[$key] = 'width:' . $ss . ';';
                } else {
                    $table->size[$key] = null;
                }
            }
        }
        if (!empty($table->wrap)) {
            foreach ($table->wrap as $key => $ww) {
                if ($ww) {
                    $table->wrap[$key] = 'white-space:nowrap;';
                } else {
                    $table->wrap[$key] = '';
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
                if (!isset($table->wrap[$key])) {
                    $table->wrap[$key] = null;
                }
            }
        }
        if (empty($table->attributes['class'])) {
            $table->attributes['class'] = 'generaltable';
        }
        if (!empty($table->tablealign)) {
            $table->attributes['class'] .= ' boxalign' . $table->tablealign;
        }

        // Explicitly assigned properties override those defined via $table->attributes!
        $table->attributes['class'] = trim($table->attributes['class']);
        $attributes = array_merge($table->attributes, [
                'id' => $table->id,
                'width' => $table->width,
                'summary' => $table->summary,
                'cellpadding' => $table->cellpadding,
                'cellspacing' => $table->cellspacing
        ]);
        $output = html_writer::start_tag('table', $attributes) . "\n";

        $countcols = 0;

        if (!empty($table->colgrps)) {
            $output .= html_writer::start_tag('colgroup');
            foreach ($table->colgrps as $colgrp) {
                $output .= html_writer::empty_tag('col', $colgrp);
            }
            $output .= html_writer::end_tag('colgroup');
        }

        if (!empty($table->head)) {

            $output .= html_writer::start_tag('thead', []) . "\n";

            foreach ($table->head as $headrow) {
                $output .= html_writer::start_tag('tr', []) . "\n";
                $keys = array_keys($headrow->cells);
                $lastkey = end($keys);
                $countcols = count($headrow->cells);
                $idx = 0;
                foreach ($headrow->cells as $key => $heading) {
                    // Convert plain string headings into html_table_cell objects!
                    if (!($heading instanceof html_table_cell)) {
                        $headingtext = $heading;
                        $heading = new html_table_cell();
                        $heading->text = $headingtext;
                        $heading->header = true;
                    }
                    if ($heading->text == null) {
                        $idx++;
                        continue;
                    }
                    if ($heading->header !== false) {
                        $heading->header = true;
                    }

                    if ($heading->header && empty($heading->scope)) {
                        $heading->scope = 'col';
                    }

                    $heading->attributes['class'] .= ' header c' . $idx;
                    if (isset($heading->colspan) && $heading->colspan > 1) {
                        $countcols += $heading->colspan - 1;
                    }

                    if ($key == $lastkey) {
                        $heading->attributes['class'] .= ' lastcol';
                    }
                    if (isset($table->colclasses[$key])) {
                        $heading->attributes['class'] .= ' ' . $table->colclasses[$key];
                        $classes = explode(' ', $table->colclasses[$key]);
                    } else {
                        $classes = '';
                    }
                    $heading->attributes['class'] = trim($heading->attributes['class']);
                    $attributes = array_merge($heading->attributes, [
                            'style' => $heading->style,
                            'scope' => $heading->scope,
                            'colspan' => $heading->colspan,
                            'rowspan' => $heading->rowspan
                    ]);

                    $tagtype = 'td';
                    if ($heading->header === true) {
                        $tagtype = 'th';
                    }

                    if (!$nohide && ($report->column_is_hidden($key) || $report->column_is_hidden($classes))) {
                        $attributes['class'] .= ' hiddencol';
                    }
                    $content = html_writer::tag('div', $heading->text,
                                    ['class' => 'content']) .
                            $this->get_toggle_links($key, $heading->text, $report);

                    $output .= html_writer::tag($tagtype, $content, $attributes) . "\n";
                    $idx++;
                }
                $output .= html_writer::end_tag('tr') . "\n";
            }
            $output .= html_writer::end_tag('thead') . "\n";

            if (empty($table->data)) {
                /*
                 * For valid XHTML strict every table must contain either a valid tr
                 * or a valid tbody... both of which must contain a valid td
                 */
                $output .= html_writer::start_tag('tbody', ['class' => 'empty']);
                $output .= html_writer::tag('tr', html_writer::tag('td', '', ['colspan' => count($table->head)]));
                $output .= html_writer::end_tag('tbody');
            }
        }

        if (!empty($table->data)) {
            $oddeven = 1;
            $keys = array_keys($table->data);
            $lastrowkey = end($keys);
            $output .= html_writer::start_tag('tbody', []);

            foreach ($table->data as $key => $row) {
                if (($row === 'hr') && ($countcols)) {
                    $output .= html_writer::start_tag('tr') .
                            html_writer::tag('td', html_writer::tag('div', '',
                                    ['class' => 'tabledivider']),
                                    ['colspan' => $countcols]) .
                            html_writer::end_tag('tr') . "\n";;
                } else {
                    $idx = 0;
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

                    $output .= html_writer::start_tag('tr', [
                                    'class' => trim($row->attributes['class']),
                                    'style' => $row->style,
                                    'id' => $row->id
                            ]) . "\n";
                    $keys2 = array_keys($row->cells);
                    $lastkey = end($keys2);

                    $gotlastkey = false; // Flag for sanity checking!
                    foreach ($row->cells as $key => $cell) {
                        if ($gotlastkey) {
                            // This should never happen. Why do we have a cell after the last cell?
                            mtrace("A cell with key ($key) was found after the last key ($lastkey)");
                        }

                        if ($cell == null) {
                            $idx++;
                            continue;
                        }

                        if (!($cell instanceof html_table_cell)) {
                            $mycell = new html_table_cell();
                            $mycell->text = $cell;
                            $cell = $mycell;
                        }

                        if (($cell->header === true) && empty($cell->scope)) {
                            $cell->scope = 'row';
                        }

                        if (isset($table->colclasses[$key])) {
                            $cell->attributes['class'] .= ' ' . $table->colclasses[$key];
                        }

                        $cell->attributes['class'] .= ' cell c' . $idx;
                        if ($key == $lastkey) {
                            $cell->attributes['class'] .= ' lastcol';
                            $gotlastkey = true;
                        }
                        $tdstyle = '';
                        $tdstyle .= isset($table->align[$key]) ? $table->align[$key] : '';
                        $tdstyle .= isset($table->size[$key]) ? $table->size[$key] : '';
                        $tdstyle .= isset($table->wrap[$key]) ? $table->wrap[$key] : '';
                        $cell->attributes['class'] = trim($cell->attributes['class']);
                        $tdattributes = array_merge($cell->attributes, [
                                'style' => $tdstyle . $cell->style,
                                'colspan' => $cell->colspan,
                                'rowspan' => $cell->rowspan,
                                'id' => $cell->id,
                                'abbr' => $cell->abbr,
                                'scope' => $cell->scope
                        ]);
                        $tagtype = 'td';
                        if ($cell->header === true) {
                            $tagtype = 'th';
                        }
                        if (isset($table->colclasses[$key])) {
                            $classes = explode(' ', $table->colclasses[$key]);
                        } else {
                            $classes = '';
                        }
                        if (!$nohide && ($report->column_is_hidden($key) || $report->column_is_hidden($classes))) {
                            $tdattributes['class'] .= ' hiddencol';
                        }
                        $content = html_writer::tag('div', $cell->text, ['class' => 'content']);
                        $output .= html_writer::tag($tagtype, $content, $tdattributes) . "\n";
                        $idx++;
                    }
                }
                $output .= html_writer::end_tag('tr') . "\n";
            }
            $output .= html_writer::end_tag('tbody') . "\n";
        }
        $output .= html_writer::end_tag('table') . "\n";

        return $output;
    }

    /**
     * Helper method to render the toggle links used in the table header
     *
     * @param string $column internal column name
     * @param string $columnstring displayed column name
     * @param report_bbbparticipation_base $report needed to determine if the column is hidden
     * @return string HTML snippet
     */
    protected function get_toggle_links($column = '', $columnstring = '', report_bbbparticipation_base $report) {
        global $USER;
        $html = '';
        if (empty($report)) {
            return '';
        }
         $instances = $report->get_instances();
         $showicon = $this->output->pix_icon('t/switch_plus', get_string('show'));
         $hideicon = $this->output->pix_icon('t/switch_minus', get_string('hide'));
        $url = new moodle_url($this->page->url);
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
        if ($report->column_is_hidden($column)) {
            // Show link!
            $url->param('tshow', $column);
            $html = html_writer::link($url,
                    $showicon,
                    [
                            'class' => $column . ' showcol',
                            'title' => get_string('show') .
                                    ' ' . clean_param($columnstring, PARAM_NOTAGS)
                    ]);
        } else {
            // Hide link!
            $url->param('thide', $column);
            $html = html_writer::link($url,
                    $hideicon,
                    [
                            'class' => $column . ' hidecol',
                            'title' => get_string('hide') .
                                    ' ' . clean_param($columnstring, PARAM_NOTAGS)
                    ]);
        }

        return $html;
    }
}
