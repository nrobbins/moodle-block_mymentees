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
 * Grade page for 'block_mymentees'
 *
 * @package   block_mymentees
 * @copyright  2012 onwards Nathan Robbins (https://github.com/nrobbins)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

global $CFG, $DB, $PAGE, $OUTPUT, $USER, $COURSE;

require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/report/'.$CFG->grade_profilereport.'/lib.php');


$studentid = $_POST['studentid'];

$canaccess = $DB->get_record_sql("SELECT c.instanceid
                                    FROM {role_assignments} ra, {context} c, {user} u
                                   WHERE ra.userid = ?
                                     AND ra.contextid = c.id
                                     AND c.instanceid = u.id
                                     AND u.id = ?
                                     AND c.contextlevel = ".CONTEXT_USER, array($USER->id, $studentid));

if (!$canaccess) {
    die;
}

$courses = $DB->get_records_sql('SELECT e.courseid, e.id, ue.enrolid
                                   FROM {user_enrolments} ue, {enrol} e
                                   WHERE ue.userid = ?
                                     AND ue.enrolid = e.id', array($studentid));
$course   = $DB->get_record('course', array('id' => 1), '*', MUST_EXIST);

require_login($course, false);
$PAGE->set_url('/blocks/mymentees/grades.php');
$PAGE->set_context(context_system::instance());
$title = 'Course grades';
$PAGE->set_title($title);
$PAGE->set_heading('Grade Report');
$PAGE->navbar->add('Grades');
$PAGE->navbar->add($title);
$PAGE->set_pagelayout('standard');


echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2);


foreach ($courses as $course => $data) {
    $fulluser = $DB->get_record("user", array("id"=>$studentid, 'deleted'=>0), '*', MUST_EXIST);
    $course   = $DB->get_record('course', array('id' => $data->courseid), '*', MUST_EXIST);
    if (empty($CFG->grade_profilereport) or !file_exists($CFG->dirroot.'/grade/report/'.$CFG->grade_profilereport.'/lib.php')) {
        $CFG->grade_profilereport = 'user';
    }
    $functionname = 'grade_report_'.$CFG->grade_profilereport.'_profilereport';
    if (function_exists($functionname)) {
        echo '<br><br><h3>'.$course->fullname.'</h3>';
        $functionname($course, $fulluser);
    }
}



echo $OUTPUT->footer();