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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Module\SyncGrades\Domain\ReportingScopeGateway;
use Gibbon\Module\SyncGrades\Domain\ReportingCycleGateway;
use Gibbon\Module\SyncGrades\Domain\MoodleGradeQueryableGateway;
use Gibbon\Module\SyncGrades\Domain\ReportingCriteriaGateway;

require_once 'moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Sync Grades/index.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $urlParams = [
        'gibbonReportingCycleID' => $_GET['gibbonReportingCycleID'] ?? '',
        'gibbonReportingScopeID' => $_GET['gibbonReportingScopeID'] ?? '',
        'gibbonYearGroupID' => $_GET['gibbonYearGroupID'] ?? '',
        'gibbonFormGroupID' => $_GET['gibbonFormGroupID'] ?? '',
        'gibbonCourseID' => $_GET['gibbonCourseID'] ?? '',
        'criteriaSelector' => $_GET['criteriaSelector'] ?? '',
    ];

    $page->breadcrumbs->add(__('Sync data from Moodle Grades'));
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $reportingScopeGateway = $container->get(ReportingScopeGateway::class);
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);
    $queryAbleGateway = $container->get(MoodleGradeQueryableGateway::class);
    $reportingCriteriaGateway = $container->get(ReportingCriteriaGateway::class);
    $reportingCycles = $reportingCycleGateway->selectReportingCyclesBySchoolYear($gibbonSchoolYearID)->fetchKeyPair();

    echo '<h3>';
    echo __('sync data from Moodle Grades');
    echo '</h3>';
    echo '<p>';
    echo __('If you want to perform this step, please configure the Moodle database information first.');
    echo '</p>';
    echo '<p style="color:#c00">';
    echo __('Please do not refresh the page or click the execute button repeatedly during the synchronization process !!!');
    echo '</p>';

    //form
    $form = Form::create('archiveByReport', $session->get('absoluteURL') . '/index.php', 'get');
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder fullWidth');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('q', '/modules/Sync Grades/index.php');
    $form->addHiddenValue('gibbonReportingScopeID', '0');

    $row = $form->addRow();
    $row->addLabel('gibbonReportingCycleID', __('Reporting Cycle'));
    $row->addSelect('gibbonReportingCycleID')
        ->fromArray($reportingCycles)
        ->selected($urlParams['gibbonReportingCycleID'])
        ->setClass('w-64')
        ->placeholder()
        ->required();
    $reportingScopes = $reportingScopeGateway->selectReportingScopesBySchoolYear($gibbonSchoolYearID)->fetchAll();
    $scopesChained = array_combine(array_column($reportingScopes, 'value'), array_column($reportingScopes, 'chained'));
    $scopesOptions = array_combine(array_column($reportingScopes, 'value'), array_column($reportingScopes, 'name'));
    $row = $form->addRow();
    $row->addLabel('gibbonReportingScopeID', __('Scope'));
    $row->addSelect('gibbonReportingScopeID')
        ->fromArray($scopesOptions)
        ->selected($urlParams['gibbonReportingScopeID'])
        ->chainedTo('gibbonReportingCycleID', $scopesChained)
        ->setClass('auto-submit w-64 ')
        ->placeholder();

    if (!empty($urlParams['gibbonReportingScopeID'])) {
        $criteria = $reportingCriteriaGateway->newQueryCriteria()->sortBy(['sequenceNumber', 'nameOrder']);
        $criteriaGroups = $reportingCriteriaGateway->queryReportingCriteriaGroupsByCycle($criteria, $urlParams['gibbonReportingCycleID'], $urlParams['gibbonReportingScopeID']);
        // echo '<pre>';
        // var_dump($criteriaGroups);die;
        $row = $form->addRow();
        $row->addLabel('criteriaSelector', __('Course & Class'));
        $row->addSelect('criteriaSelector')
            ->fromDataSet($criteriaGroups, 'value', 'name', 'scopeName')
            ->setClass('w-64')
            ->selected($urlParams['criteriaSelector'])
            ->placeholder();

    } else {
        $form->addHiddenValue('criteriaSelector', '0');
    }

    $row = $form->addRow();
    $row->addSearchSubmit($session, __('Clear Filters'));
    echo $form->getOutput();

    echo '<h3>';
    echo __('Sync Result');
    echo '</h3>';

    echo '<div id="dialog-again" title="Course Class synchronized" style="display:none;"><p>The currently selected course class has been synchronized. Continuing will overwrite the data from the previous synchronization. Do you want to continue?</p></div>';
    echo '<script>$("#dialog-again").dialog({
        resizable: false,
        height: "auto",
        width: 400,
        modal: true,
        autoOpen: false,
        buttons: {
            "no": function() {
                $("#dialog-again").dialog("close");
            },
            "yes": function() {
                window.location.href = window.location.href + "&confirmed=true";
            },
        }
    });</script>';

    if (!empty($urlParams['gibbonReportingScopeID']) && !empty($urlParams['gibbonReportingCycleID']) && !empty($urlParams['criteriaSelector'])) {
        //query courseid through criteriaSelector , query scopeid through courseid , determine scopeid if equal urlParms's courseid
        $reportingScope = $reportingScopeGateway->getScopeBycritera($urlParams['criteriaSelector'], $urlParams['gibbonReportingScopeID'], $urlParams['gibbonReportingCycleID']);
        if ($reportingScope['gibbonReportingScopeID'] != $urlParams['gibbonReportingScopeID']) {
            echo '<div class="h-48 rounded-sm border bg-gray-100 shadow-inner overflow-hidden"><div class="blankslate w-full h-full flex flex-col items-center justify-center text-gray-600 text-lg">Select one of the options above to start synchronizing.</div></div>';
            return;
        }

        $reportingCycle = $reportingCycleGateway->getByID($reportingScope['gibbonReportingCycleID']);
        $settings = $queryAbleGateway->getMoodleSettings();
        if (!$settings) {
            $page->addError(__('get database info error：Please check Moodle database setting'));
            return;
        }
        //connect Moodle database.
        try {
            $pdoMoodle = new PDO("mysql:host=$settings[gibbonMoodleDBHost];dbname=$settings[gibbonMoodleDBName]", $settings['gibbonMoodleDBUsername'], $settings['gibbonMoodleDBPassword']);
            $pdoMoodle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $page->addError(__('database connect error：' . $e->getMessage()));
            return;
        }
        if ($reportingScope['scopeType'] != 'Course') {
            $page->addError(__('Scope type error：Scope type should be Course'));
            return;
        }

        $persons = $queryAbleGateway->selectPersonsByScopeAndCycle($reportingScope['gibbonReportingScopeID'], $reportingCycle['gibbonReportingCycleID'], $urlParams['criteriaSelector'])->fetchAll();
        if (empty($persons)) {
            $page->addError(__('No student data found for the selected period'));
            return;
        }

        //determine if the currently selected course class has been synchronized 
        $isSynchronized = $queryAbleGateway->selectSyncHistoryByKey((int) $session->get('gibbonSchoolYearID') . (int) $urlParams['gibbonReportingCycleID'] . (int) $urlParams['gibbonReportingScopeID'] . (int) $urlParams['criteriaSelector']);
        if ($isSynchronized && !isset($_GET['confirmed'])) {
            echo '<script>$("#dialog-again").dialog("open");</script>';
            return;
        }
        if ($isSynchronized && isset($_GET['confirmed'])) {
            echo '<script>$("#dialog-again").dialog("close");</script>';
        }

        $tableData = [];
        foreach ($persons as $k => $p) {
            $personGrade = $queryAbleGateway->selectMoodleGradesByCourseAndPerson($pdoMoodle, $p['gibbonCourseID'], $p['username']);
            if (!$personGrade) {
                continue;
            }
            if ($p['valueType'] == 'Number') {
                $reportingValueData['gibbonScaleGradeID'] = NULL;
                $reportingValueData['value'] = $personGrade['finalgrade'];
                $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['numberValue'] = $personGrade['finalgrade'];
            } elseif ($p['valueType'] == 'Grade Scale') {
                $reportingValueData['value'] = number_format((($personGrade['finalgrade'] - $personGrade['rawgrademin']) * 100) / ($personGrade['rawgrademax'] - $personGrade['rawgrademin']), 0) . '%';
                $scaleGrade = $queryAbleGateway->selectScaleGradeByValue($reportingValueData['value']);
                $reportingValueData['gibbonScaleGradeID'] = $scaleGrade['gibbonScaleGradeID'];
                $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['percentValue'] = $reportingValueData['value'];
            } else {
                continue;
            }
            $course = $queryAbleGateway->getCourseByID($p['gibbonCourseID']);
            $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['courseInfo'] = $reportingCycle['name'] . ' - ' . $reportingScope['name'] . ' - ' . $course['nameShort'] . '.' . $p['gibbonCourseClassName'];
            $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['studentName'] = $p['officialName'];
            $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['gibbonPersonIDStudent'] = $p['gibbonPersonID'];
            $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
            $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['gibbonReportingCycleID'] = $p['gibbonReportingCycleID'];
            $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['gibbonReportingScopeID'] = $reportingScope['gibbonReportingScopeID'];
            $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['gibbonCourseClassID'] = $p['gibbonCourseClassID'];
            $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['gibbonPersonID'] = $session->get('gibbonPersonID');

            $reportingValueData['gibbonReportingCycleID'] = $p['gibbonReportingCycleID'];
            $reportingValueData['gibbonReportingCriteriaID'] = $p['gibbonReportingCriteriaID'];
            $reportingValueData['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
            $reportingValueData['gibbonCourseClassID'] = $p['gibbonCourseClassID'];
            $reportingValueData['gibbonPersonIDStudent'] = $p['gibbonPersonID'];
            $reportingValueData['gibbonPersonIDCreated'] = $session->get('gibbonPersonID');

            $reportingProgressData['gibbonReportingScopeID'] = $reportingScope['gibbonReportingScopeID'];
            $reportingProgressData['gibbonCourseClassID'] = $p['gibbonCourseClassID'];
            $reportingProgressData['gibbonPersonIDStudent'] = $p['gibbonPersonID'];
            $reportingProgressData['status'] = 'In Progress';

            try {
                $connection2->beginTransaction();
                $result = $queryAbleGateway->insertOrUpdateToReportingValue($reportingValueData);
                $result2 = $queryAbleGateway->insertOrUpdateToReportingProgress($reportingProgressData);
                if ($result === false || $result2 === false) {
                    $connection2->rollBack();
                    if ($p['valueType'] == 'Number') {
                        $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['numberResultStatus'] = 'error';
                    } elseif ($p['valueType'] == 'Grade Scale') {
                        $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['gradeScaleResultStatus'] = 'error';
                    }
                } else {
                    if ($p['valueType'] == 'Number') {
                        $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['numberResultStatus'] = 'success';
                    } elseif ($p['valueType'] == 'Grade Scale') {
                        $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['gradeScaleResultStatus'] = 'success';
                    }
                    $connection2->commit();
                }

            } catch (Exception $e) {
                $connection2->rollBack();
                if ($p['valueType'] == 'Number') {
                    $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['numberResultStatus'] = 'error';
                } elseif ($p['valueType'] == 'Grade Scale') {
                    $tableData[$p['gibbonCourseID'] . $p['gibbonPersonID']]['gradeScaleResultStatus'] = 'error';
                }
            }
        }

        //insert data to history table if there is data here.
        if (!empty($tableData)) {
            $queryAbleGateway->insertSyncGradesHistory((int) $session->get('gibbonSchoolYearID') . (int) $urlParams['gibbonReportingCycleID'] . (int) $urlParams['gibbonReportingScopeID'] . (int) $urlParams['criteriaSelector'], json_encode($tableData), $session->get('gibbonPersonID'));
            $page->addSuccess(__('Sync successful.'));
        }

        //show result table of sync successful data.
        $table = DataTable::create('reportGrades');
        $table->modifyRows(function ($data, $row) {
            if ($data['numberResultStatus'] == 'error' || $data['gradeScaleResultStatus'] == 'error')
                $row->addClass('error');
            return $row;
        });
        $table->addColumn('courseInfo', __('Course'))->translatable();
        $table->addColumn('studentName', __('Student Name'));
        $table->addColumn('numberValue', __('Number'));
        $table->addColumn('percentValue', __('Grade Scale'));

        $table->addActionColumn()
            ->format(function ($row, $actions) {
                $actions->addAction('edit', __('Edit'))
                    ->setTarget('_blank')
                    ->addParam('gibbonPersonIDStudent', $row['gibbonPersonIDStudent'])
                    ->addParam('gibbonSchoolYearID', $row['gibbonSchoolYearID'])
                    ->addParam('gibbonReportingCycleID', $row['gibbonReportingCycleID'])
                    ->addParam('gibbonReportingScopeID', $row['gibbonReportingScopeID'])
                    ->addParam('scopeTypeID', $row['gibbonCourseClassID'])
                    ->addParam('gibbonPersonID', $row['gibbonPersonID'])
                    ->addParam('allStudents', '')
                    ->setURL('/modules/Reports/reporting_write_byStudent.php');
            });
        echo $table->render($tableData);
    } else {
        echo '<div class="h-48 rounded-sm border bg-gray-100 shadow-inner overflow-hidden"><div class="blankslate w-full h-full flex flex-col items-center justify-center text-gray-600 text-lg">Select one of the options above to start synchronizing.</div></div>';
    }
}