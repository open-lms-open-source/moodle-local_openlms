<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_openlms;

/**
 * Date/time related helper code.
 *
 * @package    local_openlms
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class date_util {
    /**
     * Formats two dates of event on one short line.
     *
     * @param int|null $timestart start of event
     * @param int|null $timeend end
     * @param mixed $tz
     * @return string
     */
    public static function format_event_date(?int $timestart, ?int $timeend, $tz = null): string {
        if (!$timestart) {
            return '';
        }

        $formattedline = userdate($timestart, get_string('strftimedate', 'langconfig'), $tz);
        $formattedline .= '&nbsp;&nbsp;&nbsp;';
        $formattedline .= userdate($timestart, get_string('strftimetime', 'langconfig'), $tz);

        if ($timestart < $timeend) {
            $formattedline .= '&ndash;' . userdate($timeend, get_string('strftimetime', 'langconfig'), $tz);

            $tzobject = \core_date::get_user_timezone_object($tz);
            $objstart = new \DateTime('@' . $timestart);
            $objstart->setTimezone($tzobject);
            $objstart->setTime(0, 0, 0);
            $objend = new \DateTime('@' . $timeend);
            $objend->setTimezone($tzobject);
            $objend->setTime(0, 0, 0);
            $daydiff = ($objend->getTimestamp() - $objstart->getTimestamp()) / DAYSECS;
            $daydiff = floor($daydiff);
            if ($daydiff > 0) {
                if ($daydiff == 1) {
                    $dayslater = '(+' . $daydiff . ' ' . get_string('day') . ')';
                } else {
                    $dayslater = '(+' . $daydiff . ' ' . get_string('days') . ')';
                }
                $formattedline .= '<sup> ' . $dayslater . '</sup>';
            }
        }

        return $formattedline;
    }
}
