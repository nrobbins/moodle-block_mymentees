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
 * Stats page for 'block_mymentees'
 *
 * @package   block_mymentees
 * @copyright  2012 onwards Nathan Robbins (https://github.com/nrobbins)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

global $CFG, $DB, $PAGE, $OUTPUT, $USER, $COURSE;

error_reporting(E_ALL);
ini_set('display_errors', 1);

$course   = $DB->get_record('course', array('id' => 1), '*', MUST_EXIST);
require_login($course, false);

$stats = block_mymentees_load_stats();
if (!count($stats)) {
    die;
}
//print "<pre>".print_r($stats,1)."</pre>";


$PAGE->set_url('/blocks/mymentees/stats.php');
$PAGE->set_context(context_system::instance());
$title = 'Mentee Access Stats';
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add('Mentee Stats');
$PAGE->navbar->add($title);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2);

$periods = block_mymentees_periods();
$courseStats = array();
$userStats = array();
$coursenames = array();
foreach ($stats as $record) {
    if (!isset($courseStats[0])) {
        $courseStats[0]['periods'] = $periods;
        $courseStats[0]['name'] = 'Last Access Totals';
        $courseStats[0]['totalaccesses'] = 0;
    }
    if ($record->courseid) {
        $coursenames[$record->courseid] = array('name'=>$record->coursename, 'accessed'=>0);

        $courseStats[0]['totalaccesses']++;
        foreach ($courseStats[0]['periods'] as $pname=>$period) {
            if ($record->courselastaccess > $period['pdate']) {
                $courseStats[0]['periods'][$pname]['accesses']++;
                break;
            }
        }
        if (!isset($courseStats[$record->courseid])) {
            $courseStats[$record->courseid]['periods'] = $periods;
            $courseStats[$record->courseid]['name'] = $record->coursename;
            $courseStats[$record->courseid]['totalaccesses'] = 0;
        }
        $courseStats[$record->courseid]['totalaccesses']++;
        foreach ($courseStats[$record->courseid]['periods'] as $pname=>$period) {
            if ($record->courselastaccess > $period['pdate']) {
                $courseStats[$record->courseid]['periods'][$pname]['accesses']++;
                break;
            }
        }
    }
}

$lastaccesstotals = array_shift($courseStats);
$courseStats[999999] = $lastaccesstotals;

foreach ($stats as $record) {
    if ($record->userid) {
        if (!isset($userStats[$record->userid])) {
            $userStats[$record->userid]['courses'] = $coursenames;
            $userStats[$record->userid]['name'] = $record->firstname." ".$record->lastname;
            $userStats[$record->userid]['totalaccesses'] = 0;
        }
        foreach ($userStats[$record->userid]['courses'] as $cid=>$course) {
            if ($record->courseid == $cid) {
                $userStats[$record->userid]['courses'][$cid]['accessed'] = 1;
                $userStats[$record->userid]['totalaccesses']++;
            }
        }
    }

}

if (count($courseStats)) {
    print "<h3>Course Access Stats</h3>";
    print "<table class='mymentees_fullstats' id='mymentees_coursestats'><tr><th>Course Name</th>";
    foreach ($periods as $period) {
        print "<th class='rotate'><div><span>{$period['title']}</span></div></th>";
    }
    print "<th class='rotate'><div><span>Total</span></div></th>";
    print "</tr>";

    foreach ($courseStats as $courseStat) {
        print "<tr>";
        print "<td>{$courseStat['name']}</td>";
        foreach ($courseStat['periods'] as $pname=>$period) {
            print "<td class='{{$pname}}'>{$period['accesses']}</td>";
        }
        print "<td>{$courseStat['totalaccesses']}</td>";
        print "</tr>";
    }
    print "</table>";
}

if (count($userStats)) {
    print "<h3>User Access Stats</h3>";
    print "<table class='mymentees_fullstats' id='mymentees_userstats'><tr><th>User Name</th>";
    foreach ($coursenames as $cid=>$course) {
        print "<th class='rotate'><div><span>{$course['name']}</span></div></th>";
    }
    print "<th class='rotate'><div><span>Total</span></div></th>";
    print "</tr>";

    foreach ($userStats as $userStat) {
        print "<tr>";
        print "<td>{$userStat['name']}</td>";
        foreach ($userStat['courses'] as $cid=>$course) {
            print "<td class='course_{$cid}'>". ($course['accessed']?'Y':'') ."</td>";
        }
        print "<td>{$userStat['totalaccesses']}</td>";
        print "</tr>";
    }
    print "</table>";
}

echo $OUTPUT->footer();

//----------------------------------------

function block_mymentees_periods() {
    $periods = array(
            '24h' => array('title' => 'Last 24 hours', 'period'=>'P1D', 'accesses'=>0),
            '7d' => array('title' => 'Last 7 days', 'period'=>'P7D', 'accesses'=>0),
            '30d' => array('title' => 'Last month', 'period'=>'P1M', 'accesses'=>0),
            '60d' => array('title' => 'Last 2 months', 'period'=>'P2M', 'accesses'=>0),
            '90d' => array('title' => 'Last 3 months', 'period'=>'P3M', 'accesses'=>0),
            '180d' => array('title' => 'Last 6 months', 'period'=>'P6M', 'accesses'=>0),
            '1y' => array('title' => 'Last 12 months', 'period'=>'P1Y', 'accesses'=>0),
            '*' => array('title' => 'Over 12 months ago', 'period'=>null, 'accesses'=>0, 'pdate'=>0),
    );
    foreach ($periods as $pname=>$period) {
        if ($period['period']) {
            $pdate = new DateTime();
            $pdate->sub(new DateInterval($period['period']));
            $periods[$pname]['pdate'] = $pdate->getTimestamp();
        }
    }
    return $periods;
}

function block_mymentees_load_stats() {
    global $DB, $USER;

    return $DB->get_records_sql('SELECT concat(ifnull(u.id,0),"-",ifnull(c.id,0),"-",ifnull(ula.id,0),"-",ifnull(mc.id,0),"-",ifnull(ra.id,0),"-",floor(rand()*9999)) as ukey,
                                            u.id AS userid, u.firstname, u.lastname,
                                            u.lastaccess sitelastaccess,
                                            ula.timeaccess courselastaccess,
                                            mc.id as courseid, mc.fullname as coursename
                                     FROM {role_assignments} ra
                                     INNER JOIN {context} c on c.id = ra.contextid
                                     INNER JOIN {user} u on u.id = c.instanceid
                                     LEFT OUTER JOIN {user_lastaccess} ula on ula.userid = u.id
                                     LEFT OUTER JOIN {course} mc on mc.id = ula.courseid
                                     WHERE ra.userid = ?
                                       AND c.contextlevel = '.CONTEXT_USER, array($USER->id));

}
