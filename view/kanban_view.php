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

// Load DigiKanban environment
if (file_exists('../digikanban.main.inc.php')) {
    require_once __DIR__ . '/../digikanban.main.inc.php';
} elseif (file_exists('../../digikanban.main.inc.php')) {
    require_once __DIR__ . '/../../digikanban.main.inc.php';
} else {
    die('Include of digikanban main fails');
}

global $conf, $db, $langs, $moduleNameLowerCase, $user;

// Load dolibarr libraries
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Load libraries
require_once __DIR__ . '/../class/digikanban.class.php';
require_once __DIR__ . '/../class/digikanban_tags.class.php';
require_once __DIR__ . '/../class/digikanban_columns.class.php';
require_once __DIR__ . '/../lib/digikanban.lib.php';
require_once __DIR__ . '/../lib/digikanban_functions.lib.php';



$ganttProAdvanced = new stdClass();

if(isset($conf->ganttproadvanced) && $conf->ganttproadvanced->enabled){
    dol_include_once('/ganttproadvanced/class/ganttproadvanced.class.php');
    $ganttProAdvanced = new ganttproadvanced($db);
}

// Load translation files required by the page
saturne_load_langs();

$langs->loadLangs(['projects','mails']);
$var 				= true;
$form 				= new Form($db);
$formother      	= new FormOther($db);
$object 			= new digikanban($db);
$digikanbanTags    = new digikanban_tags($db);
$userp 				= new User($db);
$tmpuser			= new User($db);
$project 			= new Project($db);
$projectstatic 	    = new Project($db);
$tasks              = new Task($db);
$extrafields        = new ExtraFields($db);

$extrafields->fetch_name_optionals_label($tasks->table_element);
$object->upgradeThedigikanbanModule();



$fin    = strtotime(" +3 months");
$dt_fin = dol_getdate($fin);

$searst = (GETPOST('search_status') != '') ? 1 : 0;

// User last search
$latestsearch_status       = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_STATUS)) ? (int)$user->conf->DIGIKANBAN_LATEST_SEARCH_STATUS : 99;
$latestsearch_projects     = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_PROJECTS)) ? explode(',', $user->conf->DIGIKANBAN_LATEST_SEARCH_PROJECTS) : [];
$latestsearch_debutyear    = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_DEBUTYEAR)) ? $user->conf->DIGIKANBAN_LATEST_SEARCH_DEBUTYEAR : date('Y');
$latestsearch_debutmonth   = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_DEBUTMONTH)) ? $user->conf->DIGIKANBAN_LATEST_SEARCH_DEBUTMONTH : date('m');
$latestsearch_finyear      = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_FINYEAR)) ? $user->conf->DIGIKANBAN_LATEST_SEARCH_FINYEAR : $dt_fin['year'];
$latestsearch_finmonth     = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_FINMONTH)) ? $user->conf->DIGIKANBAN_LATEST_SEARCH_FINMONTH : $dt_fin['mon'];
$latestsearch_tasktype     = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_TASKTYPE)) ? explode(',', $user->conf->DIGIKANBAN_LATEST_SEARCH_TASKTYPE) : [];
$latestsearch_affecteduser = (isset($user->conf->DIGIKANBAN_LATEST_SEARCH_AFFECTEDUSER)) ? explode(',', $user->conf->DIGIKANBAN_LATEST_SEARCH_AFFECTEDUSER) : [];

$sortfield 			 = (!empty(GETPOST('sortfield')) && GETPOST('sortfield') != '-1') ? GETPOST('sortfield') : "";
$sortorder 			 = GETPOST('sortorder') ? GETPOST('sortorder') : "DESC";
$id 				 = GETPOST('id');
$search_status 		 = (GETPOST("search_status", 'int') != '') ? GETPOST("search_status", 'int') : $latestsearch_status;
$action   			 = GETPOST('action');
$search_category 	 = GETPOST("search_category", 'int');
$search_all 	     = GETPOST("search_all");
$progressless100     = GETPOST('progressless100');
$search_year         = GETPOST('search_year') ? GETPOST('search_year') : date('Y');
$search_months       = GETPOST('search_months', 'array');
$search_tags         = GETPOST('search_tags', 'array');
$debutyear           = GETPOST('debutyear', 'int') ? GETPOST('debutyear', 'int') : $latestsearch_debutyear;
$debutmonth          = GETPOST('debutmonth', 'int') ? GETPOST('debutmonth', 'int') : $latestsearch_debutmonth;
$finyear             = GETPOST('finyear', 'int') ? GETPOST('finyear', 'int') : $latestsearch_finyear;
$finmonth            = GETPOST('finmonth', 'int') ? GETPOST('finmonth', 'int') : $latestsearch_finmonth;
$search_projects     = GETPOST("search_projects", 'array') ? GETPOST("search_projects", 'array') : $latestsearch_projects;
$search_tasktype     = GETPOST("search_tasktype", 'array') ? GETPOST("search_tasktype", 'array') : $latestsearch_tasktype;
$search_affecteduser = ($searst) ? GETPOST("search_affecteduser", 'array') : $latestsearch_affecteduser;
$monthstart          = GETPOST('monthstart') ? GETPOST('monthstart') : 1;
$monthend            = GETPOST('monthend') ? GETPOST('monthend') : 12;
$srch_year     		 = GETPOST('srch_year');

$page = '';

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;

saturne_check_access($permissiontoread);

/*
 * Actions
 */

$emptyfilter = false;

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) {
    $emptyfilter         = true;
    $filter              = "";
    $filter              = "";
    $srch_matricule 	 = "";
    $srch_type 			 = "";
    $srch_date_service 	 = "";
    $srch_date_achat 	 = "";
    $srch_affectation 	 = "";
    $srch_ville 		 = "";
    $srch_month 		 = "";
    $srch_year 			 = "";
    $search_months 		 = "";
    $search_year         = '';
    $str_month           = "";
    $progressless100     = "";
    $monthstart 		 = 12;
    $debutyear           = date('Y');
    $debutmonth          = date('m');
    $finyear             = $dt_fin['year'];
    $finmonth            = $dt_fin['mon'];
    $search_affecteduser = [];
    $search_tags         = [];
    $search_all          = '';
    $sortfield           = '';
    $sortorder           = 'Desc';
    $search_projects     = [];
    $search_tasktype     = [];
}
$debut  = dol_mktime(0, 0, 0, $debutmonth, 1, $debutyear);
$fin    = dol_mktime(0, 0, 0, $finmonth, 1, $finyear);
$diff   = $fin-$debut;
$diff_m = (($finyear - $debutyear) * 12) + ($finmonth - $debutmonth);
$months = [];

if ($diff_m) {
    for ($i = 0; $i <= $diff_m; $i++) {
        $date          = strtotime($db->idate($debut) . " +" . $i . " months");
        $months[$date] = dol_print_date($date, '%B %Y');
    }
}

if ($search_status == '' && $search_status != '0') {
    $search_status = 99;
} // 100 = All

$filterprojstatus = '';
if ($search_status != 100) {
    if ($search_status == 99) {
        $filterprojstatus .= " AND p.fk_statut <> 2";
    } else {
        $filterprojstatus .= " AND p.fk_statut = " . ((int) $search_status);
    }
}

$projectsListId = '';
if ($action == 'showall' || $object->showallprojet) {
    if($action == "showall") $search_projects = [];
    $projectsListId = $object->selectProjectsdigikanbanAuthorized(0, 0, $search_status, true, 1, $debut, $fin);
}

if (is_array($projectsListId)) {
    $keyp = array_keys($projectsListId);
    if (empty($search_projects)) {
        $search_projects = $keyp;
    }
}

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) {
    $search_projects = [];
}

if (!$sortfield) {
    if(is_array($project->fields)) {
        reset($project->fields);
    }
    $sortfield = "dateo";
}
if (!$sortorder) {
    $sortorder = "ASC";
}

if ($id > 0) {
    $result = $project->fetch($id);
    if ($result < 0) {
        setEventMessages(null, $project->errors, 'errors');
    }
    $result = $project->fetch_thirdparty();
    if ($result < 0) {
        setEventMessages(null, $project->errors, 'errors');
    }
    $result = $project->fetch_optionals();
    if ($result < 0) {
        setEventMessages(null, $project->errors, 'errors');
    }
}

$filter  = '';
$filter .= (!empty($srch_year) && $srch_year != -1) ? " AND YEAR(date_service) = " . $srch_year . " " : "";

$str_month     = $search_months ?  implode(',', $search_months) : (GETPOST('str_month') ? GETPOST('str_month') : '');
$search_months = $search_months ? $search_months : ($str_month ? explode(',', $str_month) : []);

$limit  = '';
$offset = '';

$sql_proj    = implode(",", $search_projects);
$sql_users   = (count($search_affecteduser) > 0) ? implode(",", $search_affecteduser) : '';
$users_tasks = [];
$projectids  = [];

if (count($search_projects) > 0 && $action == 'pdf') {
    require_once 'ganttpro_export.php';
}

// Save personnal parameter
$userparametres    = [];
$tmpsearchprojects = GETPOST("search_projects", 'array');

if($tmpsearchprojects || $action == 'showall' || $emptyfilter) {
    $tmpsearchprojects                                   = $emptyfilter ? [] : (($action == 'showall') ? $search_projects : $tmpsearchprojects);
    $userparametres['DIGIKANBAN_LATEST_SEARCH_PROJECTS'] = implode(',', $tmpsearchprojects);
}

if($searst && !$emptyfilter) {
    $userparametres['DIGIKANBAN_LATEST_SEARCH_STATUS'] = GETPOST("search_status", 'int').'.0';
}
if($searst && !$emptyfilter) {
    $userparametres['DIGIKANBAN_LATEST_SEARCH_DEBUTYEAR'] = GETPOST("debutyear", 'int');
}
if($searst && !$emptyfilter) {
    $userparametres['DIGIKANBAN_LATEST_SEARCH_DEBUTMONTH'] = GETPOST("debutmonth", 'int');
}
if($searst && !$emptyfilter) {
    $userparametres['DIGIKANBAN_LATEST_SEARCH_FINYEAR'] = GETPOST("finyear", 'int');
}
if($searst && !$emptyfilter) {
    $userparametres['DIGIKANBAN_LATEST_SEARCH_FINMONTH'] = GETPOST("finmonth", 'int');
}
if($searst && !$emptyfilter) {
    $userparametres['DIGIKANBAN_LATEST_SEARCH_TASKTYPE'] = implode(',', GETPOST("search_tasktype", 'array'));
}
if($searst && !$emptyfilter) {
    $userparametres['DIGIKANBAN_LATEST_SEARCH_AFFECTEDUSER'] = implode(',', GETPOST("search_affecteduser", 'array'));
}

if($userparametres) {
    $resusrs = dol_set_user_param($db, $conf, $user, $userparametres);
}


// -----------------------------------------------------------------------------------------------------------------------------------
$sql_tasktypes = "";

if($search_tasktype) {
    $tmptasktypes  = implode('","', $search_tasktype);
    $sql_tasktypes = '"'.$tmptasktypes.'"';
}

$returned          = $object->selectdigikanbanUsersThatSignedAsTasksContacts($sql_proj, $sql_tasktypes, $search_affecteduser);
$selectusers_html  = $returned['html'];
$selectusers_array = $returned['array'];
$_data 	           = '';
$_links            = '';
$param             = '';
$taskobject        = new Task($db);

$param .= ($search_status!='') ? '&search_status=' . urldecode($search_status) : '';
$param .= $debutyear ? '&debutyear=' . urldecode($debutyear) : '';
$param .= $debutmonth ? '&debutmonth=' . urldecode($debutmonth) : '';

$param .= $finyear ? '&finyear=' . urldecode($finyear) : '';
$param .= $finmonth ? '&finmonth=' . urldecode($finmonth) : '';
$param .= $finmonth ? '&search_maxdatemonth=' . urldecode($finmonth) : '';
$param .= $finyear ? '&search_maxdateyear=' . urldecode($finyear) : '';
$param .= $search_all ? '&search_all=' . urlencode($search_all) : '';
$param .= $progressless100 ? '&progressless100=' . $progressless100 : '';
$param .= ($sortfield != '-1') ? '&sortfield=' . urlencode($sortfield) : '';

if (!empty($search_tags)) {
    $param .= '&search_tags[]=' . implode('&search_tags[]=', $search_tags);
}
if (!empty($search_projects) && (int) count($search_projects) <= 200) {
    $param .= '&search_projects[]=' . implode('&search_projects[]=', $search_projects);
}
if (!empty($search_affecteduser)) {
    $param .= '&search_affecteduser[]=' . implode('&search_affecteduser[]=', $search_affecteduser);
}
if (!empty($search_tasktype)) {
    $param .= '&search_tasktype[]=' . implode('&search_tasktype[]=', $search_tasktype);
}

$tosendingantt = $param;

$tosendingantt .= $action ? '&action=' . $action : '';

if($action == 'hideall'){
    $search_projects = "";
    $sql_proj        = '';
}

/*
 * View
 */

$title   = 'DigiKanban';
$helpUrl = 'FR:Module_Digikanban';

$morejs  = ['includes/jquery/plugins/blockUI/jquery.blockUI.js', 'core/js/blockUI.js', "/digikanban/js/jquery.slimscroll.min.js","/digikanban/js/script.js.php","/includes/jquery/plugins/timepicker/jquery-ui-timepicker-addon.js"];
$morecss = ['digikanban/css/style.css'];

$moreheadjs  = '';
$moreheadjs .= '<script type="text/javascript">'."\n";
$moreheadjs .= 'var indicatorBlockUI = \''.DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif".'\';'."\n";
$moreheadjs .= 'function pleaseBePatientJs() {'."\n";
$moreheadjs .= '$.pleaseBePatient("' . $langs->trans('PleaseBePatient') . '");' . "\n";
$moreheadjs .= '}'."\n";
$moreheadjs .= '</script>'."\n";

saturne_header(0, $moreheadjs, $title, $helpUrl, '', 0, 0, $morejs, $morecss);

?>
    <style>
        #id-right { padding-top: 0; }
        div.tabs { margin-top: 0; }
        table.table-fiche-title { margin-bottom: 0px; }
        div.tabBar { padding-top: 7px; margin-bottom: 0px; }
    </style>
    <script>
        $(function(){

            <?php if($search_projects) { ?>
            projet_choose_change();
            <?php } ?>

            getalltagkanban();

            $('.kanbanfilterdiv .date_picker').datepicker({
                dateFormat: "mm/yy",
                changeMonth: true,
                changeYear: true,
                autoclose: true,

                onChangeMonthYear: function (year, month) {
                    // $(this).datepicker('hide');
                },

                onClose: function(dateText, inst) {

                    var m = inst.selectedMonth;
                    var y = inst.selectedYear;

                    $(this).datepicker('setDate', new Date(y, m, 1)).trigger('change');

                    $('#'+inst.id+'month').val(m+1);
                    $('#'+inst.id+'year').val(y);

                    // setTimeout(function(){
                    // 	inst.dpDiv.removeClass('month_year_datepicker');
                    // },1000);

                    // $('.date_picker').focusout();
                },

                beforeShow : function(input, inst) {

                    $('#ui-datepicker-div').addClass('month_year_datepicker');
                    // inst.dpDiv.addClass('month_year_datepicker');

                    if ((datestr = $(this).val()).length > 0) {
                        year = datestr.substring(datestr.length-4, datestr.length);
                        month = datestr.substring(0, 2);

                        $(this).datepicker('option', 'defaultDate', new Date(year, month-1, 1));
                        $(this).datepicker('setDate', new Date(year, month-1, 1));

                        // $(".ui-datepicker-calendar").hide();
                    }
                }
            });
        });
    </script>

<?php

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" id="FormProjSearch" class="digikanbanformindex">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" id="sortorder" name="sortorder" value="' . $sortorder . '" />';
print '<input type="hidden" class="search_year" name="search_year" value="' . $search_year . '" />';
print '<input type="hidden" class="search_progress" name="search_progress" value="1" />';
print '<input type="hidden" id="users_tasks" name="users_tasks" value="' . base64_encode(json_encode($users_tasks)) . '" />';

if (isset($conf->ganttproadvanced) && $conf->ganttproadvanced->enabled) {
    $scale = GETPOST("scale", 'alpha') ? GETPOST("scale", 'alpha') : ($user->conf->GANTTPROADVANCED_DEFAULT_ZOOM_BY ? $user->conf->GANTTPROADVANCED_DEFAULT_ZOOM_BY : $ganttProAdvanced->default_zoom);
    if ($scale) {
        $tosendingantt .= '&scale=' . $scale;
        $resusrs        = dol_set_user_param($db, $conf, $user, ['GANTTPROADVANCED_DEFAULT_ZOOM_BY' => $scale]);
    }
} else {
    print_barre_liste($moduleNameLowerCase, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', '', '', 'project', 0, '', '', 0);
}

print '<fieldset id="fieldsetkanban">';
print '<legend align="right" class="openclosebtn"><i class="fas fa-filter"></i> ';
print '<a class="closesearch"><span class="fas fa-angle-up"></span></a>';
print '<a class="opensearch unvisible"><span class="fas fa-angle-down"></span></a>';
print '</legend>';
print '<div class="titre kanbanfilterdiv">';
print '<div class="width100p kanbanfilterfirstdiv">';
print '<span class="filterspan ganttprofilterstatus">';
$arrayofstatus = [];
$arrayofstatus['99'] = $langs->trans("NotClosed") . ' (' . $langs->trans('Draft') . ' + ' . $langs->trans('Opened') . ')';
if (!empty($projectstatic->statuts_short)) {
    foreach ($projectstatic->statuts_short as $key => $val) {
        $arrayofstatus[$key] = $langs->trans($val);
    }
}
$arrayofstatus[Project::STATUS_CLOSED] = $langs->trans("Closed");
print $form->selectarray('search_status', $arrayofstatus, $search_status, 0, 0, 0, '', 0, 0, 0, '', 'minwidth75imp maxwidth75 selectarrowonleft');
print ajax_combobox('search_status');
print '</span>';

print '<span class="filterspan ">';
print '<a class="externopenlink" target="_blank" href="'.dol_buildpath('/digikanban/admin/setup.php',1).'" style="padding-right: 13px;">';
print img_picto($langs->trans('SortOrder'), 'setup', ' class="linkobject"');
print '</a>';
echo '<span id="digikanbanselectprojectsauthorized">';
print $object->selectProjectsdigikanbanAuthorized($search_projects, $search_category, $search_status, false, 1, $debut, $fin);
echo '</span>';
print '<a class="butAction selectallprojects" id="selectallprojects" href="#" >'.$langs->trans('All').'</a>';
print '<a class="butAction selectallprojects" id="selectnoneprojects" href="#" >'.$langs->trans('None').'</a>';
print '</span>';

$coloredbyuser = (isset($conf->ganttproadvanced) && $conf->ganttproadvanced->enabled) ? $ganttProAdvanced->coloredbyuser : $object->coloredbyuser;

echo '<span class="filterspan ">';
echo info_admin($langs->trans('ContactType') . ' (' . $langs->trans('Tasks') . ')', 1);
echo $object->selectMultipleTypeContact($search_tasktype, 'search_tasktype', 'internal', 'rowid', $_showempty = 0, $_multiple = true);
echo '</span>';

print '<span class="filterspan ">';
print img_picto($langs->trans('Users'), 'user', '') . ' ';
print '<span id="digikanban_users_as_taskcontact">';
print $selectusers_html;
print '</span>';
print '</span>';
print '<span class="filterspan ">';
print $langs->trans('From') . ' ';
print '<input type="text" class="date_picker width50 center" autocomplete="off" value="' . dol_print_date($debut, '%m/%Y').'" onKeyUp="changeInputDatePickerData(this)" onchange="submitFormWhenChange(1)" id="debut" name="debut">';
print '<input type="hidden" id="debutmonth" name="debutmonth" value="' . $debutmonth . '">';
print '<input type="hidden" id="debutyear" name="debutyear" value="' . $debutyear . '">';

echo '<span class="marginleftonly">';
print $langs->trans('to').' ';
print '<input type="text" class="date_picker width50 center" autocomplete="off" value="' . dol_print_date($fin, '%m/%Y').'" onKeyUp="changeInputDatePickerData(this)" onchange="submitFormWhenChange(1)" id="fin" name="fin">';
print '<input type="hidden" id="finmonth" name="finmonth" value="' . $finmonth . '">';
print '<input type="hidden" id="finyear" name="finyear" value="' . $finyear . '">';
echo '</span>';

print '</span>';
print '</div>';

print '<div class="width100p ">';
print '<span class="filterspan">';
$sort_order   = (strtoupper($sortorder) == 'DESC') ? 'ASC' : 'DESC';
$arr_sorfield =  [
        't.ref'                        => $langs->trans('NumTask'),
        'p.ref'                        => $langs->trans('NumProjet'),
        't.dateo'                      => $langs->trans('DateStart'),
        't.datee'                      => $langs->trans('DateEnd'),
        'ef.ganttproadvanceddatejalon' => $langs->trans('JalonDate')
];

print '<img src="' . dol_buildpath('digikanban/img/tri.png', 1) . '" height="12px">';
print ' ' . $langs->trans('Tris') . ': ';
print $form->selectarray('sortfield', $arr_sorfield, $sortfield, 1, 0, 0, 'onchange="submitFormWhenChange(1)"');
print ajax_combobox('sortfield');
print '<a href="index.php?sortorder=' . $sort_order . '&sortfield=' . $sortfield . $param . '" class="pointercursor">';

if ($sort_order == 'DESC') {
    print 'A-Z';
    print '<span class="nowrap"> ' . img_down("A-Z", 0, 'paddingright') . '</span>';
}
else {
    print 'Z-A';
    print '<span class="nowrap"> ' . img_up("Z-A", 0, 'paddingright') . '</span>';
}
print '</a>';
print '</span>';

print '<span class="filterspan">';
print img_picto($langs->trans('Etiquette'), 'category') . ' ';
print '<span id="filtertags">' . $digikanbanTags->selectAllTags('search_tags', $search_tags, 'onchange="submitFormWhenChange(1)"');
print '</span>';
print '</span>';

print '<span class="filterspan ">';
print '<input name="search_all" id="search_all" value="' . $search_all . '" placeholder="' . $langs->trans('Search') . ' ..." class="minwidth200">';
print '</span>';

print '<span class="filterspan classfortooltip">';
$checked = ($object->hidetaskisprogress100 && !isset($_POST['search_progress']) ? 'checked' : '');
$checked = $progressless100 ? 'checked' : $checked;
print '<input type="checkbox" name="progressless100" ' . $checked . ' value="1" class="pointercursor" id="progressless100"> <label for="progressless100">' . $langs->trans('progressless100') . '</label>';
print info_admin($langs->trans("infoprogress100", '100%'), 1);
print '</span>';

print '<span class="filterspan ">';
print '<button type="submit" class="liste_titre butAction button_search reposition" name="button_search_x" value="x"><span class="fa fa-search"></span></button>';
print '<button type="submit" class="liste_titre butAction button_removefilter reposition" name="button_removefilter_x" value="x"><span class="fa fa-remove"></span></button>';
print '</span>';
print '</div>';

print '</div>';
print '</fieldset>';

if(isset($conf->ganttproadvanced) && $conf->ganttproadvanced->enabled){
    $head = digikanban_tasks_admin_prepare_head($tosendingantt);
    dol_get_fiche_head($head, 'kanban', '', -1,  '');
}
$columns = $object->Columnsdigikanban();

print '<div id="kabantask">';
print '<div class="todo_content">';
if($columns){
    foreach ($columns as $key => $value) {
        print '<div class="avalide_div columns_ fourth_width" data-etat="colomn' . $key . '" id="colomn' . $key . '">';
        print '<div class="todo_titre">';
        print '<span class="creatmodele digikanbanmodelicon classfortooltip" title="' . $langs->trans("ModelsManagement") . '" onclick="managemodels(this)" data-colomn="' . $key . '">';
        print '<img src="'.dol_buildpath('/digikanban/img/icon-model.png',1).'">';
        print '</span>';
        print '<span class="sp_title">' . $value . '</span>';
        if($user->admin)
            print ' <a target="_blank" href="' . dol_buildpath('/digikanban/columns/card.php?id=' . $key . '&action=edit', 1) . '" class="classfortooltip edittitlecolomn" title="' . $langs->trans('ModifyColomn') . '">'.img_edit('') . '</a>';

        print '<span class="filter_in_etat" id="nbr_month' . $key . '"/></span>';
        print'</div>';
        print '<a id="addtask" data-colomn="' . $key . '" onclick="addtask(this)"><span class="fas fa-plus"></span> ' . $langs->trans("Addtask") . '</a>';
        print '<div class="contents">';
        print '<div class="scroll_div">';
        print '</div>';
        print '</div>';
        print '</div>';
    }
}

print '<div class="avalide_div columns_ fourth_width newcolomn" data-etat="colomn' . $key . '" id="colomn' . $key . '">';

print '<div class="todo_titre pointercursor">';
print '<a class="sp_title" onclick="addcolomn(this)"> <span class="fa fa-plus"></span> ' . $langs->trans('newcolomn') . '</a>';
print '<div class="printtitle hidden">';
print '<input class="titlenewcolomn" name="title" placeholder="' . $langs->trans('printcolomntitle') . '"><br>';
print '<a class="button" onclick="createnewcolomn(this)">' . $langs->trans('Add') . '</a>';
print ' <a class="button" onclick="closecolomn(this)">' . $langs->trans('Cancel') . '</a>';
print '</div>';
print'</div>';
print '</div>';
print '<div class="clear"></div>';
print '</div>';
print '</div>';
print '</form>';
?>
<style>
    .month_year_datepicker .ui-datepicker-calendar {
        display: none;
    }
    div.ui-tooltip.mytooltip{
        min-width: 285px !important;
    }
</style>

<?php
// End of page
llxFooter();
$db->close();