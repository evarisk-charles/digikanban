<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    admin/setup.php
 * \ingroup digikanban
 * \brief   DigiKanban setup config page
 */

// Load DigiKanban environment
if (file_exists('../digikanban.main.inc.php')) {
    require_once __DIR__ . '/../digikanban.main.inc.php';
} elseif (file_exists('../../digikanban.main.inc.php')) {
    require_once __DIR__ . '/../../digikanban.main.inc.php';
} else {
    die('Include of digikanban main fails');
}


require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

// Libraries
require_once __DIR__ . '/../class/digikanban.class.php';
require_once __DIR__ . '/../lib/digikanban.lib.php';

// Global variables definitions
global $conf, $db, $langs, $moduleName, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);


// Parameters
$action                     = GETPOST('action', 'alpha');
$status_grey                = GETPOST('grey');
$status_green               = GETPOST('green');
$status_red                 = GETPOST('red');
$t_typecontact              = GETPOST('t_typecontact','alpha') ? GETPOST('t_typecontact','alpha') : '';
$searchbycontacttype        = GETPOST('searchbycontacttype','alpha') ? GETPOST('searchbycontacttype','alpha') : '';
$nbrheurstravail            = GETPOST('nbrheurstravail','alpha') ? GETPOST('nbrheurstravail','alpha') : '';
$DELEY_ALERTE_DATEJALON     = GETPOST('DELEY_ALERTE_DATEJALON');
$showallprojets             = GETPOST('showallprojets');
$hidetaskisprogress100      = GETPOST('hidetaskisprogress100');
$showtaskinfirstcolomn      = GETPOST('showtaskinfirstcolomn');
$refreshpageautomatically   = GETPOST('refreshpageautomatically');
$maxnumbercontactstodisplay = GETPOST('maxnumbercontactstodisplay');
$fields_edit_popup          = GETPOST('fields_edit_popup', 'array') ? implode(',', GETPOST('fields_edit_popup', 'array')) : '';
$fields_hover_popup         = GETPOST('fields_hover_popup', 'array') ? implode(',', GETPOST('fields_hover_popup', 'array')) : '';

$task        = new Task($db);
$form        = new Form($db);
$formcompany = new FormCompany($db);
$kanban      = new digikanban($db);

$columns = saturne_fetch_dictionary('c_tasks_columns');

foreach ($columns as $column) {
    $columnName[] = $column->ref;
}

// Security check - Protection if external user
$permissiontoread = $user->rights->digikanban->adminpage->read;

saturne_check_access($permissiontoread);

/*
 * Actions
 */

if(!empty($action)){
    $error = 0;

    $arrayofparameters = [
        'KANBAN_MAXIMUM_NUMBER_OF_CONTACTS_TO_DISPLAY',
        'KANBAN_NOMBRE_HEURES_DE_TRAVAIL_PAR_JOUR',
        'KANBAN_TYPE_CONTACT_TO_BASE_ON',
        'KANBAN_STATUT_DATE_GREY',
        'KANBAN_STATUT_DATE_GREEN',
        'KANBAN_STATUT_DATE_RED',
        'DELEY_ALERTE_DATEJALON',
        'KANBAN_COLUMNS'
    ];
    $arrayofparameters = array_flip($arrayofparameters);

    require DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

    header('Location: ./setup.php');
    exit;
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', $moduleName);
$help_url = 'FR:Module_DigiQuali';

saturne_header(0,'', $title);

// Subheader
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header

$t_typecontact              = $kanban->t_typecontact;
$searchbycontacttype        = $kanban->searchbycontacttype;
$maxnumbercontactstodisplay = $kanban->maxnumbercontactstodisplay;

$fields_edit_popup  = $kanban->fields_edit_popup ? explode(',', $kanban->fields_edit_popup) : [];
$fields_hover_popup = $kanban->fields_hover_popup ? explode(',', $kanban->fields_hover_popup) : [];

print '<table class="noborder centpercent">';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update" />';
print '<table class="border dstable_" width="100%">';

$status_grey  = (dolibarr_get_const($db,'KANBAN_STATUT_DATE_GREY',$conf->entity) ? dolibarr_get_const($db,'KANBAN_STATUT_DATE_GREY',$conf->entity) : $langs->trans('grey') );
$status_green = (dolibarr_get_const($db,'KANBAN_STATUT_DATE_GREEN',$conf->entity) ? dolibarr_get_const($db,'KANBAN_STATUT_DATE_GREEN',$conf->entity) : $langs->trans('green') );
$status_red   = (dolibarr_get_const($db,'KANBAN_STATUT_DATE_RED',$conf->entity) ? dolibarr_get_const($db,'KANBAN_STATUT_DATE_RED',$conf->entity) : $langs->trans('red'));

print load_fiche_titre($langs->trans('GeneralConfig'), '', '');

print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Value') . '</td></tr>';

print '<tr>';
print '<td class="titlefield">' . $langs->trans('MaximumNumberOfContactsToDisplayNextToThePrimaryUser') . '</td>';
print '<td>' . $langs->transnoentities('NumberOfUsers') . '</td>';
print '<td class="center"><input type="number" name="KANBAN_MAXIMUM_NUMBER_OF_CONTACTS_TO_DISPLAY" value="' . $maxnumbercontactstodisplay . '" min="0" class="width50"> ' . $langs->transnoentities('Users') . ' </td></tr>';

$delay = $conf->global->DELEY_ALERTE_DATEJALON;

print '<tr>';
print '<td class="titlefield">' . $langs->trans('DELEY_ALERTE_DATEJALON') . '</td>';
print '<td>' . $langs->transnoentities('WarningDate') . '</td>';
print '<td class="center"><input type="number" name="DELEY_ALERTE_DATEJALON" value="' . $delay . '" class="width50"> ' . $langs->trans('Days') . '</td></tr>';

$nbrheurs = $conf->global->KANBAN_NOMBRE_HEURES_DE_TRAVAIL_PAR_JOUR;

print '<tr>';
print '<td class="titlefield" >'.$langs->trans('DaylyHours') . '</td>';
print '<td>' . $langs->transnoentities('WorkingHours') . '</td>';
print '<td class="center"><input type="number" name="KANBAN_NOMBRE_HEURES_DE_TRAVAIL_PAR_JOUR" value="' . $nbrheurs . '" class="width50"> ' . $langs->trans('Hours') . '</td></tr>';

print '<tr>';
print '<td class="titlefield">' . $langs->trans("DELEY_ALERTE_DATEJALON") . ' ' . $langs->transnoentities('grey') . '</td>';
print '<td>' . $langs->trans("DelayDesc") . '</td>';
print '<td class="center"><input type="text" name="KANBAN_STATUT_DATE_GREY" value="' . $status_grey . '"></td></tr>';

print '<tr >';
print '<td class="titlefield">' . $langs->trans("DELEY_ALERTE_DATEJALON") . ' ' . $langs->transnoentities('green') . '</td>';
print '<td>' . $langs->trans("DelayDesc") . '</td>';
print '<td class="center"><input type="text" name="KANBAN_STATUT_DATE_GREEN" value="' . $status_green . '"></td></tr>';

print '<tr>';
print '<td class="titlefield">' . $langs->trans("DELEY_ALERTE_DATEJALON") . ' ' . $langs->transnoentities('red') . '</td>';
print '<td>'.$langs->trans("DelayDesc").'</td>';
print '<td class="center"><input type="text" name="KANBAN_STATUT_DATE_RED" value="' . $status_red . '"></td></tr>';
print '<tr>';
print '<td class="titlefield">' . $langs->trans("ColomnDigikanban") . '</td>';
print '<td>'.$langs->trans("ColomnDesc").'</td>';
print '<td class="center">' . saturne_select_dictionary('KANBAN_COLUMNS', 'c_tasks_columns', 'ref') . '</td></tr>';
print '</table>';
print $form->buttonsSaveCancel('Save', '');

$constArray['digikanban'] = [
    'refresh' => [
        'name'        => 'Refresh',
        'description' => 'RefreshPage',
        'code'        => 'DIGIKANBAN_REFRESH_PAGE_AUTOMATICALLY'
    ],
    'show' => [
        'name'        => 'showallprojets',
        'description' => 'SelectProjects',
        'code'        => 'DIGIKANBAN_SHOW_ALL_PROJETS'
    ],
    'hide' => [
        'name'        => 'HideTaskIsProgress100',
        'description' => 'HideTasks',
        'code'        => 'DIGIKANBAN_HIDE_TASKISPROGRESS100'
    ],
    'showtaskinfirstcolomn' => [
        'name'        => 'ShowTaskInFirstColomn',
        'description' => 'NoStatusTasks',
        'code'        => 'SHOW_TASK_IN_FIRST_COLUMN'
    ],
];

require_once __DIR__ . '/../../saturne/core/tpl/admin/object/object_const_view.tpl.php';
print '</form>';
print '</div>';

?>
<script>
    $(document).ready(function(){
    $("#fields_edit_popup").select2();
    $("#fields_hover_popup").select2();
    });
</script>
<?php

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();