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

            $timetoshowusers = 300;
            $timefrom = 100 * floor((time()-$timetoshowusers) / 100);

            $canshowmsgicon = false;
            $canshowblog = false;

            if (has_capability('moodle/site:sendmessage', $this->page->context) && !empty($CFG->messaging)) {
                $canshowmsgicon = true;
            }
            if ($CFG->bloglevel > 0) {
                $canshowblog = true;
            }

            foreach ($mentees as $record) {
                $this->content->text .= '<div class="mymentees_mentee">';
                $this->content->text .= '<div class="mymentees_pic">'.$OUTPUT->user_picture($record, array('size'=>30)).'</div>';
                $this->content->text .= '<div class="mymentees_name"><a href="'.$CFG->wwwroot.'/user/view.php?id='.
                                        $record->instanceid.'&amp;course='.SITEID.'">'.fullname($record).'</a></div>';
                $this->content->text .= '<div>';

                $gradelinkcontents = '<input type="hidden" name="studentid" value="'.$record->id.'">'.
                                     '<input type="submit" class="mymentees_grades" value="" style="background-image:url('.
                                     $OUTPUT->pix_url('t/grades').');" title="'.get_string('grades').'">';
                $gradelink = '<form action="'.$CFG->wwwroot.'/blocks/mymentees/grades.php" method="post" style="display:inline">'.
                                         $gradelinkcontents.'</form>';
                $this->content->text .= $gradelink.' | ';

                $postlinkcontents = '<img class="iconsmall" src="'.$OUTPUT->pix_url('icon', 'forum').'" alt="'.
                                         get_string('forumposts', 'forum') .'" />';
                $postlink = '<a href="'.$CFG->wwwroot.'/mod/forum/user.php?id='.$record->id.'" title="'.
                                         get_string('forumposts', 'forum') .'">'.$postlinkcontents.'</a>';
                $this->content->text .= $postlink.' | ';

                if ($canshowblog) {
                    $bloglinkcontents = '<img class="iconsmall" src="'.$OUTPUT->pix_url('i/feedback') . '" alt="'.
                                         get_string('blogentries', 'blog') .'" />';
                    $bloglink = '<a class="mymentees_msg" href="'.$CFG->wwwroot.'/blog/index.php?userid='.$record->id.
                                         '" title="'.get_string('blogentries', 'blog').'">'.$bloglinkcontents .'</a>';
                    $this->content->text .= $bloglink.' | ';
                }

                if ($canshowmsgicon) {
                    $msglinkcontents = '<img class="iconsmall" src="'.$OUTPUT->pix_url('t/message') . '" alt="'.
                                         get_string('messageselectadd') .'" />';
                    $msglink = '<a class="mymentees_msg" href="'.$CFG->wwwroot.'/message/index.php?id='.$record->id.
                                         '" title="'.get_string('messageselectadd').'">'.$msglinkcontents .'</a>';

                    $this->content->text .= $msglink.' | ';
                }
                if ($record->lastaccess > $timefrom) {
                    $this->content->text .= '<img class="iconsmall" src="'.$OUTPUT->pix_url('t/go') . '" alt="'.
                                         get_string('online', 'block_mymentees').
                                         '" title="'.get_string('online', 'block_mymentees').'" />';
                } else {
                    $this->content->text .= '<img class="iconsmall" src="'.$OUTPUT->pix_url('t/stop') . '" alt="'.
                                         get_string('offline', 'block_mymentees')
                                         .'" title="'.get_string('offline', 'block_mymentees').'" />';
                }
                $this->content->text .= '</div></div>';
            }
        }

        $this->content->footer = '';

        return $this->content;
    }
}
