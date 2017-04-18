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
 * Main code for My mentees block.
 *
 * @package   block_mymentees
 * @copyright  2012 Nathan Robbins (https://github.com/nrobbins)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_mymentees extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_mymentees');
    }

    public function applicable_formats() {
        return array('all' => true);
    }

    public function specialization() {
        $this->title = isset($this->config->title) ? $this->config->title : get_string('pluginname', 'block_mymentees');
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';

        if (isloggedin() && !isguestuser()) {
            $userid = $USER->id;

            $mentees = $DB->get_records_sql('SELECT c.instanceid, ra.userid, ra.contextid, c.id AS cid, u.id AS id, u.firstname,
                                             u.lastname, u.lastaccess, u.picture, u.imagealt, u.email
                                             FROM {role_assignments} ra, {context} c, {user} u
                                             WHERE ra.userid = ?
                                               AND ra.contextid = c.id
                                               AND c.instanceid = u.id
                                               AND c.contextlevel = '.CONTEXT_USER, array($userid));

            $divconfig = array();

            $timetoshowusers = 300;
            $divconfig['timefrom'] = 100 * floor((time()-$timetoshowusers) / 100);

            $divconfig['showmsg'] = false;
            $divconfig['showblog'] = false;

            if (has_capability('moodle/site:sendmessage', $this->page->context) && !empty($CFG->messaging)) {
                $divconfig['showmsg'] = true;
            }
            if ($CFG->bloglevel > 0) {
                $divconfig['showblog'] = true;
            }

            foreach ($mentees as $record) {
                $mentee_div = new block_mymentees_mentee_element($record, $CFG, $OUTPUT, $divconfig);
                $this->content->text .= $mentee_div->get_output();
            }
        }

        $this->content->footer = '';

        return $this->content;
    }

}

class block_mymentees_mentee_element {
    private $canshowblog = false;
    private $canshowmsgicon = false;

    private $record = null;

    private $CFG;
    private $OUTPUT;

    private $config = array();

    public function __construct($record, $CFG, $OUTPUT, $config) {
        $this->record = $record;
        $this->canshowblog = $canshowblog;
        $this->canshowmsgicon = $canshowmsgicon;

        $this->CFG = $CFG;
        $this->OUTPUT = $OUTPUT;

        $this->config = array(
            'showpic'  => isset($config['showpic'])  ? $config['showpic']  : true,
            'showgrad' => isset($config['showgrad']) ? $config['showgrad'] : true,
            'showfrm'  => isset($config['showfrm'])  ? $config['showfrm']  : true,
            'showblog' => isset($config['showblog']) ? $config['showblog'] : false,
            'showmsg'  => isset($config['showmsg'])  ? $config['showmsg']  : false,
            'showdot'  => isset($config['showdot'])  ? $config['showdot']  : true,
            'timefrom' => isset($config['timefrom']) ? $config['timefrom'] : 0,
        );
    }

    public function get_output() {
        $out = '';
        $out .= '<div class="mymentees_mentee">';
        $out .= $this->config['showpic'] ? $this->mentee_pic() : '';
        $out .= $this->mentee_name();

        $icons = array(
            $this->config['showgrad'] ? $this->mentee_grades() : '',
            $this->config['showfrm']  ? $this->mentee_forum() : '',
            $this->config['showblog'] ? $this->mentee_blog() : '',
            $this->config['showmsg']  ? $this->mentee_messages() : '',
            $this->config['showdot']  ? $this->mentee_online() : '',
        );

        $out .= "<div>" . implode(' | ', array_filter($icons)) . "</div>";
        $out .= '</div>';
        return $out;
    }

    private function mentee_pic() {
        return '<div class="mymentees_pic">'.$this->OUTPUT->user_picture($this->record, array('size'=>30)).'</div>';
    }
    private function mentee_name() {
        global $CFG;
        return '<div class="mymentees_name"><a href="'.$this->CFG->wwwroot.'/user/view.php?id='.
                            $this->record->instanceid.'&amp;course='.SITEID.'">'.fullname($this->record).'</a></div>';
    }
    private function mentee_grades() {
        $gradelinkcontents = '<input type="hidden" name="studentid" value="'.$this->record->id.'">'.
                             '<input type="submit" class="mymentees_grades" value="" style="background-image:url('.
                             $this->OUTPUT->pix_url('t/grades').');" title="'.get_string('grades').'">';
        return '<form action="'.$this->CFG->wwwroot.'/blocks/mymentees/grades.php" method="post" style="display:inline">'.
                             $gradelinkcontents.'</form>';
    }
    private function mentee_forum() {
        $title = get_string('forumposts', 'forum');
        $link = $this->icon($this->OUTPUT->pix_url('icon', 'forum'), $title);
        return $this->link('/mod/forum/user.php?id='.$this->record->id, $title, $link);
    }
    private function mentee_blog() {
        $title = get_string('blogentries', 'blog');
        $link = $this->icon($this->OUTPUT->pix_url('i/feedback'), $title);
        return $this->link('/blog/index.php?userid='.$this->record->id, $title, $link);
    }
    private function mentee_messages() {
        $title = get_string('messageselectadd');
        $link = $this->icon($this->OUTPUT->pix_url('t/message'), $title);
        return $this->link('/message/index.php?id='.$this->record->id, $title, $link);
    }
    private function mentee_online() {
        if ($this->record->lastaccess > $this->config['timefrom']) {
            return $this->icon($this->OUTPUT->pix_url('t/go'), get_string('online', 'block_mymentees'));
        } else {
            return $this->icon($this->OUTPUT->pix_url('t/stop'), get_string('offline', 'block_mymentees'));
        }
    }

    private function icon($icon, $title='') {
        return "<img class='iconsmall' src='{$icon}' alt='{$title}' title='{$title}' />";
    }
    private function link($href, $title, $innerHTML) {
        return "<a class='mymentees_msg' href='{$this->CFG->wwwroot}{$href}' title='{$title}'>{$innerHTML}</a>";
    }
}