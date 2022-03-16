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
 * Place link to BBB participation report in BBB activity.
 *
 * @module    report_bbbparticipation
 * @copyright 2022 University of Vienna
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function (){
    return {
        init: function(reportlink, linktext) {
            var a = document.createElement('a');
            var link = document.createTextNode(linktext);
            a.appendChild(link);
            //a.title = "This is Link";
            a.href = reportlink;
            document.body.appendChild(a);
            var div = document.getElementById("bigbluebuttonbn_view_action_button_box");
            div.parentNode.insertBefore(a, div.nextSibling);
        }
    };
});

