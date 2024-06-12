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
use Gibbon\Module\SyncGrades\Domain\MoodleGradeQueryableGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';
if (isActionAccessible($guid, $connection2, '/modules/Sync Grades/setting.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Moodle Database Setting'));

    echo '<h3>';
    echo __('Moodle Database Setting');
    echo '</h3>';
    echo '<p>';
    echo __('This is the configuration form for the Moodle database.');
    echo '</p>';

    $form = Form::create('systemSettings', $session->get('absoluteURL') . '/modules/' . $session->get('module') . '/databaseSettingsProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));

    $queryAbleGateway = $container->get(MoodleGradeQueryableGateway::class);
    $setting = $queryAbleGateway->getMoodleSettings();
    $row = $form->addRow();
    $row->addLabel('gibbonMoodleDBHost', __('Host Address'));
    $row->addTextField('gibbonMoodleDBHost')->setValue($setting['gibbonMoodleDBHost'] ?? '')
        ->placeholder('Host Address')
        ->required();

    $row = $form->addRow();
    $row->addLabel('gibbonMoodleDBName', __('DB Name'));
    $row->addTextField('gibbonMoodleDBName')->setValue($setting['gibbonMoodleDBName'] ?? '')
        ->placeholder('DB Name')
        ->required();

    $row = $form->addRow();
    $row->addLabel('gibbonMoodleDBUsername', __('Username'));
    $row->addTextField('gibbonMoodleDBUsername')->setValue($setting['gibbonMoodleDBUsername'] ?? '')
        ->placeholder('Username')
        ->required();

    $row = $form->addRow();
    $row->addLabel('gibbonMoodleDBPassword', __('Password'));
    $row->addPassword('gibbonMoodleDBPassword')->setValue($setting['gibbonMoodleDBPassword'] ?? '')
        ->placeholder('Password')
        ->required();

    $row = $form->addRow();
    $row->addFooter();
    $row->addSubmit();

    echo $form->getOutput();
}

