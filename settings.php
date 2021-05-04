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
 * Settings for BBBParticipation Report.
 *
 * @package   report_bbbparticipation
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$settings = new admin_settingpage('bbbparticipationsettings', get_string('pluginname','report_bbbparticipation'));

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox(
        'report_bbbparticipation/additionaluserinfo',
        get_string('additionaluserinfo', 'report_bbbparticipation'),
        get_string('additionaluserinfo_description', 'report_bbbparticipation'),
        1
        ));
}

