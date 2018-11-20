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
 * Form for editing the My mentees block instances.
 *
 * @package   block_mymentees
 * @copyright  2012 Nathan Robbins (https://github.com/nrobbins)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class block_mymentees_edit_form extends block_edit_form {
    private $opts = array(
        'showavatar' => true,
        'showforumicon' => true,
        'showblogicon' => true,
        'showmsgicon' => true,
        'showonlineicon' => true,
        'showinlinestats' => false,
    );

    protected function specific_definition($mform) {
        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_mymentees'));
        $mform->setType('config_title', PARAM_MULTILANG);

        foreach ($this->opts as $opt=>$value) {
            $mform->addElement('advcheckbox', 'config_'.$opt, get_string($opt, 'block_mymentees'));
            $mform->setDefault('config_'.$opt, $value);
            $mform->setType("config_".$opt, PARAM_BOOL);
        }

        $mform->addElement('text', 'config_separator', get_string('configseparator', 'block_mymentees'));
        $mform->setDefault('config_separator', ' | ');
    }
}
