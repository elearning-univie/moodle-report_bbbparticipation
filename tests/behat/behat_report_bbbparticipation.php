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
 * Behat bbbparticipation-related steps definitions.
 *
 * @package   report_bbbparticipation
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Steps definitions related with the bbbaprticipation.
 *
 * @package   report_bbbparticipation
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_report_bbbparticipation extends behat_base {

    /**
     * checks the status code.
     *
     * @Then /^I should see response status code "([^"]*)"$/
     * @param string $statuscode
     */
    public function ishouldseeresponsertatuscode($statuscode) {
        $responsestatuscode = $this->response->getStatusCode();
        if (!$responsectatuscode == intval($statuscode)) {
            throw new \Exception(sprintf("Did not see response status code %s, but %s.", $statuscode, $responsectatuscode));
        }
    }

}
