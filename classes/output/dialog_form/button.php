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

namespace local_openlms\output\dialog_form;

/**
 * Button that opens legacy form in modal dialog.
 *
 * @package    local_openlms
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class button extends action {
    /** @var bool is this a primary button? */
    protected $primary;

    /** @var string extra CSS classes */
    protected $class = '';

    /** @var \pix_icon */
    protected $pixicon;

    public function __construct(\moodle_url $formurl, $title, bool $primary = false) {
        parent::__construct($formurl, $title);
        $this->primary = $primary;
    }

    public function is_primary(): bool {
        return $this->primary;
    }

    public function set_primary(bool $value): void {
        $this->primary = $value;
    }

    public function set_class(string $class): void {
        $this->class = $class;
    }

    public function get_class(): string {
        return $this->class;
    }

    public function set_icon(string $pix, string $component): void {
        $this->pixicon = new \pix_icon($pix, '', $component, ['aria-hidden' => 'true']);
    }

    public function get_icon(): ?\pix_icon {
        return $this->pixicon;
    }
}
