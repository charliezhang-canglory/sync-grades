<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Module\SyncGrades\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryableGateway;

class MoodleGradeQueryableGateway extends QueryableGateway
{

    use TableAware;

    /**
     * @param string $username
     * @return array
     */
    public function selectPersonByUsername($username)
    {
        $sql = "SELECT * FROM `gibbonPerson` WHERE username = $username";

        return $this->db()->selectOne($sql);
    }
    public function getCourseByID($courseID)
    {
        $sql = "SELECT * FROM `gibbonCourse` WHERE gibbonCourseID = $courseID";
        
        return $this->db()->selectOne($sql);
    }
    /**
     * @param number $reportingScopeID
     * @param number $reportingCycleID
     * 
     */
    public function selectPersonsByScopeAndCycle($reportingScopeID, $reportingCycleID,$gibbonCourseClassID)
    {
        $sql = "SELECT gibbonReportingCriteria.*,gibbonReportingCriteriaType.name as typeName,gibbonReportingCriteriaType.valueType,gibbonCourseClass.gibbonCourseClassID,gibbonCourseClass.name as gibbonCourseClassName,gibbonCourseClassPerson.gibbonPersonID,gibbonPerson.username,gibbonPerson.officialName
        FROM gibbonReportingCriteria 
        JOIN gibbonReportingCriteriaType ON (gibbonReportingCriteria.gibbonReportingCriteriaTypeID = gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID) 
        RIGHT JOIN gibbonCourseClass ON (gibbonReportingCriteria.gibbonCourseID = gibbonCourseClass.gibbonCourseID) and gibbonCourseClass.gibbonCourseClassID = $gibbonCourseClassID
        RIGHT JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID) AND gibbonCourseClassPerson.role='Student'
        JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID = gibbonPerson.gibbonPersonID
        WHERE gibbonReportingCriteria.gibbonReportingScopeID = $reportingScopeID 
        AND gibbonReportingCriteria.gibbonReportingCycleID = $reportingCycleID 
        AND gibbonReportingCriteriaType.active = 'Y'";

        return $this->db()->select($sql);
    }

    public function selectMoodleGradesByCourseAndPerson($pdoMoodle, $courseID, $username)
    {
        $sql = "SELECT mdl_grade_grades.id, mdl_grade_grades.rawgrademax,mdl_grade_grades.rawgrademin,mdl_grade_grades.finalgrade,mdl_course.fullname as coursename,mdl_course.idnumber as courseid,mdl_user.username FROM mdl_grade_grades 
        JOIN mdl_grade_items ON mdl_grade_grades.itemid = mdl_grade_items.id  AND mdl_grade_items.itemtype='course'
        JOIN mdl_course ON mdl_grade_items.courseid = mdl_course.id
        JOIN mdl_user ON mdl_grade_grades.userid = mdl_user.id
        where mdl_course.idnumber = :courseID AND mdl_user.username = :username;";

        $stmt = $pdoMoodle->prepare($sql);
        $stmt->bindParam(':courseID', $courseID, \PDO::PARAM_STR);
        $stmt->bindParam(':username', $username, \PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function selectScaleGradeByValue($value)
    {
        $sql = "SELECT * FROM gibbonScaleGrade WHERE value='$value'";

        return $this->db()->selectOne($sql);
    }

    public function insertOrUpdateToReportingValue($data)
    {
        $sql = "INSERT INTO gibbonReportingValue (gibbonReportingCycleID, gibbonReportingCriteriaID,gibbonSchoolYearID,gibbonCourseClassID,gibbonPersonIDStudent,gibbonScaleGradeID,value) VALUES (
            :gibbonReportingCycleID, 
            :gibbonReportingCriteriaID,
            :gibbonSchoolYearID,
            :gibbonCourseClassID,
            :gibbonPersonIDStudent,
            :gibbonScaleGradeID,
            :value) 
        ON DUPLICATE KEY UPDATE gibbonScaleGradeID = :gibbonScaleGradeID, value=:value";

        return $this->db()->insert($sql, [
            ':gibbonReportingCycleID' => $data['gibbonReportingCycleID'],
            ':gibbonReportingCriteriaID' => $data['gibbonReportingCriteriaID'],
            ':gibbonSchoolYearID' => $data['gibbonSchoolYearID'],
            ':gibbonCourseClassID' => $data['gibbonCourseClassID'],
            ':gibbonPersonIDStudent' => $data['gibbonPersonIDStudent'],
            ':gibbonScaleGradeID' => $data['gibbonScaleGradeID'],
            ':value' => $data['value']
        ]);
    }

    public function insertOrUpdateToReportingProgress($data)
    {
        $sql = "INSERT INTO gibbonReportingProgress (gibbonReportingScopeID, gibbonCourseClassID,gibbonPersonIDStudent,status) VALUES (
            :gibbonReportingScopeID, 
            :gibbonCourseClassID,
            :gibbonPersonIDStudent,
            :status) 
        ON DUPLICATE KEY UPDATE status=:status";

        return $this->db()->insert($sql, [
            ':gibbonReportingScopeID' => $data['gibbonReportingScopeID'],
            ':gibbonCourseClassID' => $data['gibbonCourseClassID'],
            ':gibbonPersonIDStudent' => $data['gibbonPersonIDStudent'],
            ':status' => $data['status']
        ]);
    }

    public function insertOrUpdateToMoodleConfig($data)
    {
        $sql = "INSERT INTO gibbonMoodleConfig (gibbonMoodleDBID,gibbonMoodleDBHost, gibbonMoodleDBName,gibbonMoodleDBUsername,gibbonMoodleDBPassword) VALUES (1,
            :gibbonMoodleDBHost, 
            :gibbonMoodleDBName,
            :gibbonMoodleDBUsername,
            :gibbonMoodleDBPassword) 
        ON DUPLICATE KEY UPDATE gibbonMoodleDBHost=:gibbonMoodleDBHost,gibbonMoodleDBName=:gibbonMoodleDBName,gibbonMoodleDBUsername=:gibbonMoodleDBUsername,gibbonMoodleDBPassword=:gibbonMoodleDBPassword";

        return $this->db()->insert($sql, [
            ':gibbonMoodleDBHost' => $data['gibbonMoodleDBHost'],
            ':gibbonMoodleDBName' => $data['gibbonMoodleDBName'],
            ':gibbonMoodleDBUsername' => $data['gibbonMoodleDBUsername'],
            ':gibbonMoodleDBPassword' => $data['gibbonMoodleDBPassword']
        ]);
    }

    public function getMoodleSettings()
    {
        return $this->db()->selectOne('SELECT * FROM gibbonMoodleConfig');
    }

    public function insertSyncGradesHistory($gibbonSyncGradesHistoryKey,$gibbonSyncGradesHistoryValue,$gibbonPersonIDCreator)
    {
        $sql = "INSERT INTO gibbonSyncGradesHistory (gibbonSyncGradesHistoryKey,gibbonSyncGradesHistoryValue,gibbonPersonIDCreator) VALUES (:gibbonSyncGradesHistoryKey,:gibbonSyncGradesHistoryValue,:gibbonPersonIDCreator)";

        return $this->db()->insert($sql,[
            ':gibbonSyncGradesHistoryKey'=>$gibbonSyncGradesHistoryKey,
            ':gibbonSyncGradesHistoryValue'=>$gibbonSyncGradesHistoryValue,
            ':gibbonPersonIDCreator'=>$gibbonPersonIDCreator
        ]);
    }

    public function selectSyncHistoryByKey($gibbonSyncGradesHistoryKey)
    {
        $sql = "SELECT * FROM gibbonSyncGradesHistory where gibbonSyncGradesHistoryKey = $gibbonSyncGradesHistoryKey";

        return $this->db()->select($sql)->fetchAll();
    }


}