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
 * Contains bbbparticipation filter from class
 *
 * @package   report_bbbparticipation
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Filter form
 *
 * @package   report_bbbparticipation
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_bbbparticipation_reportfilterform extends moodleform {
    /**
     * constructor method
     *
     * report_bbbparticipation_reportfilterform constructor.
     * @param null $action
     * @param null $customdata
     * @param string $method
     * @param string $target
     * @param null $attributes
     * @param bool $editable
     * @param null $ajaxformdata
     */
    public function __construct($action=null, $customdata=null, $method='post', $target='', $attributes=null, $editable=true,
                                $ajaxformdata=null) {
        $attributes['id'] = 'reportfilterform';
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Definition of filter form
     */
    protected function definition() {
        global $COURSE, $USER;
        $mform = $this->_form;

        $mform->addElement('header', 'bbbparticipation', get_string('pluginname', 'report_bbbparticipation'));

        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->setType('userid', PARAM_INT);

        $bbbs = report_bbbparticipation_get_bbb_activities_for_course($COURSE->id);

        $bbbselects = [get_string('allbbbs', 'report_bbbparticipation')];
        foreach ($bbbs as $bbbs) {
            $bbbselects[$bbbs->id] = $bbbs->name;
        }
        $instances = $mform->addElement('select', 'instances',
                get_string('modulenameplural', 'report_bbbparticipation'),
            $bbbselects, ['size' => 5]);
        $instances->setMultiple(true);

        $mform->addElement('submit', 'submitbutton', get_string('update', 'report_bbbparticipation'));
        $mform->disable_form_change_checker();
    }
}
