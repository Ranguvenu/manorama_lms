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
 * faq hierarchy renderer file
 *
 * This file defines the current version of the local_faq Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_faq
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use context_system;
use local_faq\controller as collection;
use html_writer;

class local_faq_renderer extends plugin_renderer_base
{
    /**
     * [get_faq_view description]
     * @param  [type] $filter [description]
     * @return [type] [description]
     */
    public function get_faq_view($filter = false)
    {
        global $OUTPUT;

        // Get FAQ data
        $faqdata = (new collection)->faq_data();
        return $OUTPUT->render_from_template('local_faq/faqinfo',  ['categories' => $faqdata]);
    }
}
