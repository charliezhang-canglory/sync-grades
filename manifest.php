<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//This file describes the module, including database tables

//Basic variables
$name = 'Sync Grades';
$description = 'Read data from Moodle and fill it into the Gibbon';
$entryURL = 'index.php';
$type = 'Additional';
$category = 'Other';
$version = '1.0.01';
$author = 'Charlie Zhang';
$url = 'https://everonlearn.org';

//Module tables
$moduleTables[0] = "CREATE TABLE gibbonMoodleConfig (
    gibbonMoodleDBID INT AUTO_INCREMENT PRIMARY KEY,
    gibbonMoodleDBHost VARCHAR(255) NOT NULL,
    gibbonMoodleDBName VARCHAR(255) NOT NULL,
    gibbonMoodleDBUsername VARCHAR(255) NOT NULL,
    gibbonMoodleDBPassword VARCHAR(255) NOT NULL
);";
$moduleTables[1] = "CREATE TABLE gibbonSyncGradesHistory (
    gibbonSyncGradesHistoryID INT AUTO_INCREMENT PRIMARY KEY,
    gibbonSyncGradesHistoryKey INT(10) NOT NULL,
    gibbonSyncGradesHistoryValue TEXT NOT NULL,
    gibbonPersonIDCreator INT(10) NOT NULL,
    timestampCreator TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);";

//Action rows (none)
$actionRows[] = [
    'name'                      => 'Moodle Database Setting', //The name of the action (appears to user in the right hand side module menu)
    'precedence'                => '0', //If it is a grouped action, the precedence controls which is highest action in group
    'category'                  => 'Settings', //Optional: subgroups for the right hand side module menu
    'description'               => 'Moodle Database Setting.', //Text description
    'URLList'                   => 'setting.php',
    'entryURL'                  => 'setting.php',
    'defaultPermissionAdmin'    => 'Y', //Default permission for built in role Admin
    'defaultPermissionTeacher'  => 'N', //Default permission for built in role Teacher
    'defaultPermissionStudent'  => 'N', //Default permission for built in role Student
    'defaultPermissionParent'   => 'N', //Default permission for built in role Parent
    'defaultPermissionSupport'  => 'Y', //Default permission for built in role Support
    'categoryPermissionStaff'   => 'Y', //Should this action be available to user roles in the Staff category?
    'categoryPermissionStudent' => 'N', //Should this action be available to user roles in the Student category?
    'categoryPermissionParent'  => 'N', //Should this action be available to user roles in the Parent category?
    'categoryPermissionOther'   => 'N', //Should this action be available to user roles in the Other category?
];
$actionRows[] = [
    'name'                      => 'Sync grades', //The name of the action (appears to user in the right hand side module menu)
    'precedence'                => '0', //If it is a grouped action, the precedence controls which is highest action in group
    'category'                  => 'View', //Optional: subgroups for the right hand side module menu
    'description'               => 'Sync grades.', //Text description
    'URLList'                   => 'index.php',
    'entryURL'                  => 'index.php',
    'defaultPermissionAdmin'    => 'Y', //Default permission for built in role Admin
    'defaultPermissionTeacher'  => 'Y', //Default permission for built in role Teacher
    'defaultPermissionStudent'  => 'N', //Default permission for built in role Student
    'defaultPermissionParent'   => 'N', //Default permission for built in role Parent
    'defaultPermissionSupport'  => 'Y', //Default permission for built in role Support
    'categoryPermissionStaff'   => 'Y', //Should this action be available to user roles in the Staff category?
    'categoryPermissionStudent' => 'N', //Should this action be available to user roles in the Student category?
    'categoryPermissionParent'  => 'N', //Should this action be available to user roles in the Parent category?
    'categoryPermissionOther'   => 'N', //Should this action be available to user roles in the Other category?
];
