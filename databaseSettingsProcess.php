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

use Gibbon\Data\Validator;
use Gibbon\Module\SyncGrades\Domain\MoodleGradeQueryableGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['indexText' => 'HTML', 'analytics' => 'RAW']);
include '../../config.php';

// Module includes
include './moduleFunctions.php';

$URL = $session->get('absoluteURL') . '/index.php?q=/modules/' . getModuleName($_POST['address']) . '/setting.php';

if (isActionAccessible($guid, $connection2, '/modules/Sync Grades/setting.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    $settings = [
        'gibbonMoodleDBHost' => 'required',
        'gibbonMoodleDBName' => 'required',
        'gibbonMoodleDBUsername' => 'required',
        'gibbonMoodleDBPassword' => 'required',
    ];

    foreach ($settings as $name => $property) {
        if ($property == 'required' && empty($_POST[$name])) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }
    }

    $queryAbleGateway = $container->get(MoodleGradeQueryableGateway::class);

    $result = $queryAbleGateway->insertOrUpdateToMoodleConfig($_POST);
    if (!$result) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}