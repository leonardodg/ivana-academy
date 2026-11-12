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
 * Ivana Academy v2 theme (fork of Moove 4.5.2 by Willian Mano).
 *
 * @package    theme_ivana
 * @copyright  2022 Willian Mano - https://conecti.me
 * @copyright  2026 LeoDG - Ivana Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'theme_ivana';
$plugin->version = 2026111200;
$plugin->release = '2.0.0';
$plugin->maturity = MATURITY_BETA;
$plugin->requires = 2024100100;
$plugin->dependencies = [
    'theme_boost' => 2024100700,
];
