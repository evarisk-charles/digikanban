<?php
//require_once('../main.inc.php');
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");       // For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php"); // For "custom" directory
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
dol_include_once('/digikanban/class/digikanban.class.php');
dol_include_once('/digikanban/lib/digikanban.lib.php');
dol_include_once('/digikanban/class/taches/elements_contacts.class.php');
dol_include_once('/digikanban/class/digikanban_tags.class.php');
dol_include_once('/digikanban/class/digikanban_columns.class.php');

$ganttproadvanced = new stdClass();

if(isset($conf->ganttproadvanced) && $conf->ganttproadvanced->enabled){
	dol_include_once('/ganttproadvanced/class/ganttproadvanced.class.php');
	$ganttproadvanced = new ganttproadvanced($db);
}

$langs->load('digikanban@digikanban');
$langs->loadLangs(array('projects','mails'));
$modname = $langs->trans("Tâches");
$var 				= true;
$form 				= new Form($db);
$formother      	= new FormOther($db);
$object 			= new digikanban($db);
$digikanban_tags = new digikanban_tags($db);
// $elements_contacts 	= new elements_contacts($db);
$userp 				= new User($db);
$tmpuser			= new User($db);
$project 			= new Project($db);
$projectstatic 	    = new Project($db);
$tasks              = new Task($db);
$extrafields        = new ExtraFields($db);

$extrafields->fetch_name_optionals_label($tasks->table_element);
// d($extrafields, false);

$object->upgradeThedigikanbanModule();



$fin = strtotime(" +3 months");
$dt_fin = dol_getdate($fin);

$searst = (GETPOST('search_status') != '') ? 1 : 0;

// ------------------------------------------------------------------------------------------- User last search
$latestsearch_status = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_STATUS)) ? (int)$user->conf->DIGIKANBAN_LATEST_SEARCH_STATUS : 99;
$latestsearch_projects = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_PROJECTS)) ? explode(',', $user->conf->DIGIKANBAN_LATEST_SEARCH_PROJECTS) : [];
$latestsearch_debutyear = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_DEBUTYEAR)) ? $user->conf->DIGIKANBAN_LATEST_SEARCH_DEBUTYEAR : date('Y');
$latestsearch_debutmonth = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_DEBUTMONTH)) ? $user->conf->DIGIKANBAN_LATEST_SEARCH_DEBUTMONTH : date('m');
$latestsearch_finyear = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_FINYEAR)) ? $user->conf->DIGIKANBAN_LATEST_SEARCH_FINYEAR : $dt_fin['year'];
$latestsearch_finmonth = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_FINMONTH)) ? $user->conf->DIGIKANBAN_LATEST_SEARCH_FINMONTH : $dt_fin['mon'];
$latestsearch_tasktype = (!$searst && isset($user->conf->DIGIKANBAN_LATEST_SEARCH_TASKTYPE)) ? explode(',', $user->conf->DIGIKANBAN_LATEST_SEARCH_TASKTYPE) : [];
$latestsearch_affecteduser = (isset($user->conf->DIGIKANBAN_LATEST_SEARCH_AFFECTEDUSER)) ? explode(',', $user->conf->DIGIKANBAN_LATEST_SEARCH_AFFECTEDUSER) : [];



$sortfield 			= (!empty(GETPOST('sortfield')) && GETPOST('sortfield') != '-1') ? GETPOST('sortfield') : "";
$sortorder 			= GETPOST('sortorder') ? GETPOST('sortorder') : "DESC";
$id 				= GETPOST('id');
$search_status 		= (GETPOST("search_status", 'int') != '') ? GETPOST("search_status", 'int') : $latestsearch_status;
$action   			= GETPOST('action');
$search_category 	= GETPOST("search_category", 'int');
$search_all 	    = GETPOST("search_all");
$progressless100    = GETPOST('progressless100');

$search_year = GETPOST('search_year') ? GETPOST('search_year') : date('Y');
$search_months = GETPOST('search_months', 'array');
$search_tags = GETPOST('search_tags', 'array');

$debutyear = GETPOST('debutyear', 'int') ? GETPOST('debutyear', 'int') : $latestsearch_debutyear;
$debutmonth = GETPOST('debutmonth', 'int') ? GETPOST('debutmonth', 'int') : $latestsearch_debutmonth;

$finyear = GETPOST('finyear', 'int') ? GETPOST('finyear', 'int') : $latestsearch_finyear;
$finmonth = GETPOST('finmonth', 'int') ? GETPOST('finmonth', 'int') : $latestsearch_finmonth;

$search_projects = GETPOST("search_projects", 'array') ? GETPOST("search_projects", 'array') : $latestsearch_projects;
$search_tasktype = GETPOST("search_tasktype", 'array') ? GETPOST("search_tasktype", 'array') : $latestsearch_tasktype;
$search_affecteduser = ($searst) ? GETPOST("search_affecteduser", 'array') : $latestsearch_affecteduser;

$monthstart = GETPOST('monthstart') ? GETPOST('monthstart') : 1;
$monthend = GETPOST('monthend') ? GETPOST('monthend') : 12;

$srch_year     		= GETPOST('srch_year');

// if(GETPOST('savecolomn')){
// 	$title = GETPOST('title', 'alpha');
// 	if($title){
// 		$colomn = new digikanban_columns($db);
// 		$colomn->label = $title;
// 		$colomn->create($user);
// 	}

// 	$title='';
// }

$page='';

if (!$user->rights->projet->lire) {
	accessforbidden();
}

$emptyfilter = false;

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) {
	$emptyfilter = true;

	$filter = "";
	$filter = "";
	$srch_matricule 	= "";
	$srch_type 			= "";
	$srch_date_service 	= "";
	$srch_date_achat 	= "";
	$srch_affectation 	= "";
	$srch_ville 		= "";
	$srch_month 		= "";
	$srch_year 			= "";
	$search_months 		= "";
	$search_year        = '';
	$str_month          = "";
	$progressless100    = "";
	$monthstart 		= 12;

	// $debutyear = $latestsearch_debutyear;
	// $debutmonth = $latestsearch_debutmonth;
	// $finyear = $latestsearch_finyear;
	// $finmonth = $latestsearch_finmonth;

	$debutyear = date('Y'); 
	$debutmonth = date('m'); 
	$finyear = $dt_fin['year']; 
	$finmonth = $dt_fin['mon'];



	$search_affecteduser = array();
	$search_tags = array();
	// $search_status = 0;
	
	$search_all = '';
	$sortfield = '';
	$sortorder = 'Desc';
	$search_projects    = [];
	$search_tasktype    = [];
	// if(is_array($projectsListId)){
	//     $keyp = key($projectsListId);
	//     if(empty($search_projects)) $search_projects[] = $keyp;
	// }

}



$debut = dol_mktime(0, 0, 0, $debutmonth, 1, $debutyear);
$fin = dol_mktime(0, 0, 0, $finmonth, 1, $finyear);

$diff = $fin-$debut;
$diff_m = (($finyear - $debutyear) * 12) + ($finmonth - $debutmonth);

// $diff = date_diff($debut, $fin);

$months = array();
if($diff_m){
	for ($i=0; $i <= $diff_m; $i++) { 
		$date = strtotime($db->idate($debut)." +".$i." months");
		$months[$date]=dol_print_date($date, '%B %Y');
	}
}

if ($search_status == '' && $search_status != '0') $search_status = 99; // 100 = All

$filterprojstatus = '';
if($search_status != 100){
    if ($search_status == 99) {
        $filterprojstatus .= " AND p.fk_statut <> 2";
    } else {
        $filterprojstatus .= " AND p.fk_statut = ".((int) $search_status);
    }
}

if($debut && $fin){
	// $filterprojet = ' AND (CAST(p.dateo as date) BETWEEN "'.$db->idate($debut).'" AND "'.$db->idate($fin).'"';
	// $filterprojstatus .= ' AND (CAST(p.dateo as date) BETWEEN "'.$db->idate($debut).'" AND "'.$db->idate($fin).'"';
	// $filterprojstatus .= ' OR CAST(p.datee as date) BETWEEN "'.$db->idate($debut).'" AND "'.$db->idate($fin).'")';
}

// $p_sortfield = (isset($conf->ganttproadvanced) && $conf->ganttproadvanced->enabled) ? $ganttproadvanced->p_sortfield : $object->p_sortfield;
// $p_sortorder = (isset($conf->ganttproadvanced) && $conf->ganttproadvanced->enabled) ? $ganttproadvanced->p_sortorder : $object->p_sortorder;
// if (!$user->rights->projet->all->lire) {
// 	// $filterprojstatus .= $db->order($p_sortfield, $p_sortorder);
// 	$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0, 0, $filterprojstatus);
// } else {
// 	if($action == 'showall' || $conf->global->DIGIKANBAN_SHOW_ALL_PROJETS)
// 	$projectsListId = $object->selectProjectsdigikanbanAuthorized(0, 0, $search_status, true, 1);
// }

$projectsListId = '';
if($action == 'showall' || $object->showallprojet) {
	if($action == "showall") $search_projects = array();
	$projectsListId = $object->selectProjectsdigikanbanAuthorized(0, 0, $search_status, true, 1, $debut, $fin);
}

// echo "projectsListId: ";

if(is_array($projectsListId)){
    $keyp = array_keys($projectsListId);
    if(empty($search_projects)) $search_projects = $keyp;
}



if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) {
	$search_projects = [];
}

if (!$sortfield) { 
	if(is_array($project->fields)) {
		reset($project->fields); 
	}
	$sortfield="dateo"; 
}
if (!$sortorder) $sortorder = "ASC";

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


$filter ='';
$filter .= (!empty($srch_year) && $srch_year != -1) ? " AND YEAR(date_service) = ".$srch_year." " : "";


$str_month = $search_months ?  implode(',', $search_months) : (GETPOST('str_month') ? GETPOST('str_month') : '');
$search_months = $search_months ? $search_months : ($str_month ? explode(',', $str_month) : []);



$limit='';
$offset='';

$sql_proj = implode(",", $search_projects);
$sql_users = (count($search_affecteduser) > 0) ? implode(",", $search_affecteduser) : '';



// $sql_tasktypes = (isset($conf->ganttproadvanced) && $conf->ganttproadvanced->enabled) ? $ganttproadvanced->t_typecontact : $object->t_typecontact;

$users_tasks = array();
// ------------------------------------------------------------------------------------------- SQL

// ---------------------------------------------;

// $colordefaulttask = $ganttproadvanced->coloredbyuser ? $ganttproadvanced->colorgristask : $ganttproadvanced->defaultcolortask;

$projectids = array();

// if($search_projects > 0) $projectstatic->fetch($search_projects);

if (count($search_projects) > 0 && $action == 'pdf') {
	require_once 'ganttpro_export.php';
}











###################################################################################################################### Save personnal parameter
$userparametres = array();

$tmpsearchprojects = GETPOST("search_projects", 'array');
if($tmpsearchprojects || $action == 'showall' || $emptyfilter) {
	$tmpsearchprojects = $emptyfilter ? array() : (($action == 'showall') ? $search_projects : $tmpsearchprojects);
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

// d($userparametres,0);

// -----------------------------------------------------------------------------------------------------------------------------------
$sql_tasktypes = "";

if($search_tasktype) {
	$tmptasktypes = implode('","', $search_tasktype);
	$sql_tasktypes = '"'.$tmptasktypes.'"';
}

$returned = $object->selectdigikanbanUsersThatSignedAsTasksContacts($sql_proj, $sql_tasktypes, $search_affecteduser);
$selectusers_html = $returned['html'];
$selectusers_array = $returned['array'];
$_data 	= '';
$_links = '';
$param='';
$taskobject = new Task($db);
// $nametypecontact = $ganttproadvanced->nametypecontact;

$param .= ($search_status!='') ? '&search_status='.urldecode($search_status) : '';
$param .= $debutyear ? '&debutyear='.urldecode($debutyear) : '';
$param .= $debutmonth ? '&debutmonth='.urldecode($debutmonth) : '';

$param .= $finyear ? '&finyear='.urldecode($finyear) : '';
$param .= $finmonth ? '&finmonth='.urldecode($finmonth) : '';
$param .= $finmonth ? '&search_maxdatemonth='.urldecode($finmonth) : '';
$param .= $finyear ? '&search_maxdateyear='.urldecode($finyear) : '';
// $param .= $search_tags ? '&search_tags[]='.urlencode($search_tags) : '';
$param .= $search_all ? '&search_all='.urlencode($search_all) : '';
$param .= $progressless100 ? '&progressless100='.$progressless100 : '';
$param .= ($sortfield != '-1') ? '&sortfield='.urlencode($sortfield) : '';
// $param .= $sortorder ? '&sortorder='.urlencode($sortorder) : '';

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

// $id_tasktype = (isset($search_tasktype[0]) && !empty($search_tasktype[0])) ? $search_tasktype[0] : 0;

$tosendingantt .= $action ? '&action='.$action : '';
// $tosendingantt .= '&debutyear='.$debutyear;
// $tosendingantt .= '&debutmonth='.$debutmonth;
// $tosendingantt .= '&finyear='.$finyear;
// $tosendingantt .= '&finmonth='.$finmonth;
// $tosendingantt .= $id_tasktype ? '&search_tasktype='.urlencode($id_tasktype) : '';

if($action == 'hideall'){
	$search_projects = "";
	$sql_proj='';
}

$morejs  = array('includes/jquery/plugins/blockUI/jquery.blockUI.js', 'core/js/blockUI.js', "/digikanban/js/jquery.slimscroll.min.js","/digikanban/js/script.js.php","/includes/jquery/plugins/timepicker/jquery-ui-timepicker-addon.js");
$morecss = array('digikanban/css/style.css');

$moreheadjs = '';
$moreheadjs .= '<script type="text/javascript">'."\n";
$moreheadjs .= 'var indicatorBlockUI = \''.DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif".'\';'."\n";

// $moreheadjs .= '$( document ).ready(function() {
// 	$(\'.button_removefilter, .button_search, .massaction, #action_import_xls\').click( function() {
// 		pleaseBePatientJs();
// 	});
// });';

$moreheadjs .= 'function pleaseBePatientJs() {'."\n";
$moreheadjs .= '$.pleaseBePatient("'.$langs->trans('PleaseBePatient').'");'."\n";
$moreheadjs .= '}'."\n";
$moreheadjs .= '</script>'."\n";
llxHeader($moreheadjs, $modname,'','','','',$morejs,$morecss,0);

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

		// $('#debut').datepicker({
        //     dateFormat: "mm/yy",
        //     changeMonth: true,
        //     changeYear: true,
		// 	autoclose: true,
        //     // showButtonPanel: true,

        //     onClose: function(dateText, inst) {


        //         function isDonePressed(){
        //             // return ($('#ui-datepicker-div').html().indexOf('ui-datepicker-close ui-state-default ui-priority-primary ui-corner-all ui-state-hover') > -1);
        //         }
        //         if (isDonePressed()){
        //         }

        //         var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
        //         var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
        //         // console.log('monthdebut:'+month);
        //         $(this).datepicker('setDate', new Date(year, month, 1)).trigger('change');
        //         var m = parseInt(month)+1;
        //         $('#debutmonth').val(m);
        //         $('#debutyear').val(year);
        //          $('.date_picker').focusout()//Added to remove focus from datepicker input box on selecting date
        //     },
        //     beforeShow : function(input, inst) {

        //         inst.dpDiv.addClass('month_year_datepicker')

        //         if ((datestr = $(this).val()).length > 0) {
        //             year = datestr.substring(datestr.length-4, datestr.length);
        //             month = datestr.substring(0, 2);
        //             $(this).datepicker('option', 'defaultDate', new Date(year, month-1, 1));
        //             $(this).datepicker('setDate', new Date(year, month-1, 1));
        //             $(".ui-datepicker-calendar").hide();
        //         }
        //     }
        // })
		// $('#fin').datepicker({
        //     dateFormat: "mm/yy",
        //     changeMonth: true,
        //     changeYear: true,
		// 	autoclose: true,
        //     // showButtonPanel: true,

        //     onClose: function(dateText, inst) {


        //         function isDonePressed(){
        //             // return ($('#ui-datepicker-div').html().indexOf('ui-datepicker-close ui-state-default ui-priority-primary ui-corner-all ui-state-hover') > -1);
        //         }
        //         if (isDonePressed()){
        //         }

        //         var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
        //         var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
        //         $(this).datepicker('setDate', new Date(year, month, 1)).trigger('change');
        //         // console.log('monthfin:'+month);
        //         var m = parseInt(month)+1;
                
        //         $('#finmonth').val(m);
        //         $('#finyear').val(year);
        //          $('.date_picker').focusout()//Added to remove focus from datepicker input box on selecting date
        //     },
        //     beforeShow : function(input, inst) {

        //         inst.dpDiv.addClass('month_year_datepicker')

        //         if ((datestr = $(this).val()).length > 0) {
        //             year = datestr.substring(datestr.length-4, datestr.length);
        //             month = datestr.substring(0, 2);
        //             $(this).datepicker('option', 'defaultDate', new Date(year, month-1, 1));
        //             $(this).datepicker('setDate', new Date(year, month-1, 1));
        //             $(".ui-datepicker-calendar").hide();
        //         }
        //     }
        // })

	});
</script>

<?php

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" id="FormProjSearch" class="digikanbanformindex">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	// print '<input type="hidden" id="sql_proj" name="sql_proj" value="'.$sql_proj.'" />';
	// print '<input type="hidden" id="sql_users" name="sql_users" value="'.$sql_users.'" />';
	print '<input type="hidden" id="sortorder" name="sortorder" value="'.$sortorder.'" />';
	// print '<input type="hidden" id="sortfield" name="sortfield" value="'.$sortfield.'" />';
	// print '<input type="hidden" id="str_month" name="str_month" value="'.$str_month.'" />';
	print '<input type="hidden" class="search_year" name="search_year" value="'.$search_year.'" />';
	print '<input type="hidden" class="search_progress" name="search_progress" value="1" />';
	print '<input type="hidden" id="users_tasks" name="users_tasks" value="'.base64_encode(json_encode($users_tasks)).'" />';


	if(isset($conf->ganttproadvanced) && $conf->ganttproadvanced->enabled){

		$scale 	= GETPOST("scale", 'alpha') ? GETPOST("scale", 'alpha') : ($user->conf->GANTTPROADVANCED_DEFAULT_ZOOM_BY ? $user->conf->GANTTPROADVANCED_DEFAULT_ZOOM_BY : $ganttproadvanced->default_zoom);
		if($scale){
			$tosendingantt .= '&scale='.$scale;
			$resusrs = dol_set_user_param($db, $conf, $user, ['GANTTPROADVANCED_DEFAULT_ZOOM_BY' => $scale]);
		}
		print_fiche_titre($modname);
	} else {

		print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', '', '', 'project', 0, '', '', 0);
	}

	print '<fieldset id="fieldsetkanban">';
		print '<legend align="right" class="openclosebtn"><i class="fas fa-filter"></i> ';
			print '<a class="closesearch"><span class="fas fa-angle-up"></span></a>';
			print '<a class="opensearch unvisible"><span class="fas fa-angle-down"></span></a>';
		print '</legend>';
		
		print '<div class="titre kanbanfilterdiv">';
			print '<div class="width100p kanbanfilterfirstdiv">';
				print '<span class="filterspan ganttprofilterstatus">';
					// print $langs->trans("Status").': ';
					$arrayofstatus = array();
					// $arrayofstatus['100'] = $langs->trans("All");
					$arrayofstatus['99'] = $langs->trans("NotClosed").' ('.$langs->trans('Draft').' + '.$langs->trans('Opened').')';
					if(!empty($projectstatic->statuts_short)){
						foreach ($projectstatic->statuts_short as $key => $val) {
						    $arrayofstatus[$key] = $langs->trans($val);
						}
					}
					$arrayofstatus[Project::STATUS_CLOSED] = $langs->trans("Closed");
					print $form->selectarray('search_status', $arrayofstatus, $search_status, 0, 0, 0, '', 0, 0, 0, '', 'minwidth75imp maxwidth75 selectarrowonleft');
					print ajax_combobox('search_status');
				print '</span>';

				// if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire) {
				//     print '<span class="ganttprofiltercategory">';
				//     print $langs->trans("Categories").': '.$object->SelectFilterCategory($search_category);
				//     print '</span>';
				// }

				print '<span class="filterspan ">';
					// print $langs->trans("Projects").': ';
					print '<a class="externopenlink" target="_blank" href="'.dol_buildpath('/digikanban/admin/admin.php',1).'" style="padding-right: 13px;">';
					print img_picto($langs->trans('SortOrder'), 'setup', ' class="linkobject"');
					print '</a>';
					// print img_picto($langs->trans('Projects'), 'project', '');
					// $totproject = 0;
					// if(is_array($search_projects)) {
					// 	$totproject = count($search_projects);

					// 	if($totproject > 0) {
					// 		echo '<span class="marginleftonly" title="'.$langs->trans('Projects').'">';
					// 		echo '('.$totproject.') ';
					// 		echo '</span>';
					// 	}
					// }
					echo '<span id="digikanbanselectprojectsauthorized">';
					print $object->selectProjectsdigikanbanAuthorized($search_projects, $search_category, $search_status, false, 1, $debut, $fin);
					echo '</span>';


					// print '<a class="butAction selectallprojects" href="'.dol_buildpath('/digikanban/index.php?action=showall'.$param,1).'" >'.$langs->trans('All').'</a>';
					print '<a class="butAction selectallprojects" id="selectallprojects" href="#" >'.$langs->trans('All').'</a>';
					print '<a class="butAction selectallprojects" id="selectnoneprojects" href="#" >'.$langs->trans('None').'</a>';
					// print '<a class="butAction" href="'.dol_buildpath('/digikanban/index.php?action=hideall'.$param,1).'" >'.$langs->trans('HideAll').'</a>';

				print '</span>';

				$coloredbyuser = (isset($conf->ganttproadvanced) && $conf->ganttproadvanced->enabled) ? $ganttproadvanced->coloredbyuser : $object->coloredbyuser;

				echo '<span class="filterspan ">';
				echo info_admin($langs->trans('ContactType').' ('.$langs->trans('Tasks').')', 1);
				echo $object->selectMultipleTypeContact($search_tasktype, 'search_tasktype', 'internal', 'rowid', $_showempty = 0, $_multiple = true);
		        echo '</span>';

				print '<span class="filterspan ">';
					print img_picto($langs->trans('Users'), 'user', '').' ';
					print '<span id="digikanban_users_as_taskcontact">';
						print $selectusers_html;
					print '</span>';
				print '</span>';
				print '<span class="filterspan ">';
					print $langs->trans('From').' ';
					print '<input type="text" class="date_picker width50 center" autocomplete="off" value="'.dol_print_date($debut, '%m/%Y').'" onKeyUp="changeInputDatePickerData(this)" onchange="submitFormWhenChange(1)" id="debut" name="debut">';
					print '<input type="hidden" id="debutmonth" name="debutmonth" value="'.$debutmonth.'">';
					print '<input type="hidden" id="debutyear" name="debutyear" value="'.$debutyear.'">';

					echo '<span class="marginleftonly">';
						print $langs->trans('to').' ';
						print '<input type="text" class="date_picker width50 center" autocomplete="off" value="'.dol_print_date($fin, '%m/%Y').'" onKeyUp="changeInputDatePickerData(this)" onchange="submitFormWhenChange(1)" id="fin" name="fin">';
						print '<input type="hidden" id="finmonth" name="finmonth" value="'.$finmonth.'">';
						print '<input type="hidden" id="finyear" name="finyear" value="'.$finyear.'">';
					echo '</span>';

				print '</span>';
			print '</div>';

			print '<div class="width100p ">';
				print '<span class="filterspan">';
					$sort_order = (strtoupper($sortorder) == 'DESC') ? 'ASC' : 'DESC';
					$arr_sorfield =  array('t.ref'=>$langs->trans('NumTask'), 'p.ref'=>$langs->trans('NumProjet'), 't.dateo'=>$langs->trans('DateStart'), 't.datee'=>$langs->trans('DateEnd'), 'ef.ganttproadvanceddatejalon'=>$langs->trans('JalonDate'));
				
					print '<img src="'.dol_buildpath('digikanban/img/tri.png', 1).'" height="12px">';
					print ' '.$langs->trans('Tris').': ';
					
					print $form->selectarray('sortfield', $arr_sorfield, $sortfield, 1, 0, 0, 'onchange="submitFormWhenChange(1)"');
					print ajax_combobox('sortfield');
				
					print '<a href="index.php?sortorder='.$sort_order.'&sortfield='.$sortfield.$param.'" class="pointercursor">';
						if ($sort_order == 'DESC') {
							print 'A-Z';
							print '<span class="nowrap"> '.img_down("A-Z", 0, 'paddingright').'</span>';
						}
						else {
							print 'Z-A';
							print '<span class="nowrap"> '.img_up("Z-A", 0, 'paddingright').'</span>';
						}
					print '</a>';
				print '</span>';

				print '<span class="filterspan">';
					print img_picto($langs->trans('Etiquette'), 'category').' ';
					print '<span id="filtertags">'.$digikanban_tags->selectAllTags('search_tags', $search_tags, 'onchange="submitFormWhenChange(1)"');
					print '</span>';
				print '</span>';

				print '<span class="filterspan ">';
					print '<input name="search_all" id="search_all" value="'.$search_all.'" placeholder="'.$langs->trans('Search').' ..." class="minwidth200">';
				print '</span>';
				
				print '<span class="filterspan classfortooltip">';
					$checked = ($object->hidetaskisprogress100 && !isset($_POST['search_progress']) ? 'checked' : '');
					$checked = $progressless100 ? 'checked' : $checked;
					print '<input type="checkbox" name="progressless100" '.$checked.' value="1" class="pointercursor" id="progressless100"> <label for="progressless100">'.$langs->trans('progressless100').'</label>';
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
		$head = digikanbanTasksAdminPrepareHead($tosendingantt);
		dol_fiche_head($head, 'kanban', '', -1,  '');
	}

	// print '<div style="clear:both;"></div>';
	// print '<br>';
	$columns = $object->Columnsdigikanban();

	print '<div id="kabantask">';
		print '<div class="todo_content">';
			// print '<div class="todo_div columns_ fourth_width" data-etat="enattent" id="enattent">';
			// 	print '<div class="todo_titre"><span class="sp_title">'.$langs->trans("enattentkanban").'</span><span class="filter_in_etat" id="nbr_enattent"/></span></div>';
			// 	print '<a id="addtask" onclick="addtask(this)"><span class="fas fa-plus"></span> '.$langs->trans("Addtask").'</a>';
			// 	print '<div class="contents">';
			// 		print '<div class="scroll_div">';
			// 		print '</div>';
			// 	print '</div>';
			// print '</div>';

			// print '<div class="avalide_div columns_ fourth_width" data-etat="urgents" id="urgents">';
			// 	print '<div class="todo_titre"><span class="sp_title">'.$langs->trans("urgentskanban").'</span><span class="filter_in_etat" id="nbr_urgents"/></span></div>';
			// 	print '<a id="addtask" onclick="addtask(this)"><span class="fas fa-plus"></span> '.$langs->trans("Addtask").'</a>';
			// 	print '<div class="contents">';
			// 		print '<div class="scroll_div">';
			// 		print '</div>';
			// 	print '</div>';
			// print '</div>';
			
			// if($search_months && $search_months[0]){
			// 	foreach ($search_months as $key => $value) {
			// 		if($value == -1) continue;
			// 		print '<div class="avalide_div columns_ fourth_width" data-etat="month'.$value.'" id="month'.$value.'">';
			// 			$month = 'Month'.sprintf("%02d", $value);
			// 			print '<div class="todo_titre"><span class="sp_title">'.$langs->trans($month).'</span><span class="filter_in_etat" id="nbr_month'.$value.'"/></span></div>';
			// 			print '<a id="addtask" data-month="'.$value.'" onclick="addtask(this)"><span class="fas fa-plus"></span> '.$langs->trans("Addtask").'</a>';
			// 			print '<div class="contents">';
			// 				print '<div class="scroll_div">';
			// 				print '</div>';
			// 			print '</div>';
			// 		print '</div>';
			// 	}
			// }elseif($search_months || !$search_months[0]){
				// for ($i=1; $i <= 12 ; $i++) { 
				// 	if((isset($search_months[0]) && $search_months[0] != -1) && !in_array($i, $search_months))
				// 		continue;
				// }
			if($columns){
				foreach ($columns as $key => $value) {
					print '<div class="avalide_div columns_ fourth_width" data-etat="colomn'.$key.'" id="colomn'.$key.'">';

						print '<div class="todo_titre">';

							print '<span class="creatmodele digikanbanmodelicon classfortooltip" title="'.$langs->trans("ModelsManagement").'" onclick="managemodels(this)" data-colomn="'.$key.'">';
								print '<img src="'.dol_buildpath('/digikanban/img/icon-model.png',1).'">';
							print '</span>';
							print '<span class="sp_title">'.$value.'</span>';

							if($user->admin)
							print ' <a target="_blank" href="'.dol_buildpath('/digikanban/columns/card.php?id='.$key.'&action=edit', 1).'" class="classfortooltip edittitlecolomn" title="'.$langs->trans('ModifyColomn').'">'.img_edit('').'</a>';

							print '<span class="filter_in_etat" id="nbr_month'.$key.'"/></span>';
						print'</div>';
						print '<a id="addtask" data-colomn="'.$key.'" onclick="addtask(this)"><span class="fas fa-plus"></span> '.$langs->trans("Addtask").'</a>';
						print '<div class="contents">';
							print '<div class="scroll_div">';
							print '</div>';
						print '</div>';
					print '</div>';
				}
			}

			print '<div class="avalide_div columns_ fourth_width newcolomn" data-etat="colomn'.$key.'" id="colomn'.$key.'">';

				print '<div class="todo_titre pointercursor">';
					print '<a class="sp_title" onclick="addcolomn(this)"> <span class="fa fa-plus"></span> '.$langs->trans('newcolomn').'</a>';
					print '<div class="printtitle hidden">';
						print '<input class="titlenewcolomn" name="title" placeholder="'.$langs->trans('printcolomntitle').'"><br>';
						print '<a class="button" onclick="createnewcolomn(this)">'.$langs->trans('Add').'</a>';
						print ' <a class="button" onclick="closecolomn(this)">'.$langs->trans('Cancel').'</a>';
					print '</div>';
				print'</div>';
				// print '<a id="addtask" data-colomn="'.$key.'" onclick="addtask(this)"><span class="fas fa-plus"></span> '.$langs->trans("Addtask").'</a>';
				// print '<div class="contents">';
				// 	print '<div class="scroll_div">';
				// 	print '</div>';
				// print '</div>';
			print '</div>';

			print '<div class="clear"></div>';
		print '</div>';
	print '</div>';


	// print '<div class="hover_bkgr_fricc">';
	//     print '<span class="helper"></span>';
	//     print '<div class="windows_pop nc_pop">';
	//         print '<div class="popupCloseButton" changed="no">X</div>';
	//         print '<div class="window-header">';
	//         	print '<span class="icon-lg icon-card"></span>';
	// 	        print '<div class="window-title" id="tache_title">';
	// 		        print '<h2 class="title"></h2>
	// 	        </div>
	// 	        <div>
	// 	    		'.$langs->trans("dans_la_liste").' : <span id="tache_etat"></span>
	// 	    		<span class="tache_avance" style="float: right;">'.$langs->trans("Progression_déclarée").' : <span id="tache_avance" ></span>
	// 	        </div>
	// 	        <div>
	// 		        <button class="createorupdatetask button button_todo" onclick="createorupdatetask();"  style="display:none;">'.$langs->trans("save").'</button>
	// 		        <h3 class="title">'.$langs->trans("Description").'</h3>
	// 	        	<p><textarea id="tache_description" class="" rows="4" onkeyup="slct_etat_change()" placeholder="'.$langs->trans("write_description").'…"></textarea></p>
	// 	        </div>
	// 	        <div style="overflow: auto;">
	// 		        <h3 class="title">'.$langs->trans("Temps_consommé").'</h3>
	// 	        	<span id="tache_temps_consomme"></span>
	// 	        </div>
	// 	        <hr style="margin: 25px 0 17px;">
	// 	        <div>
	// 		        <h3 class="title">'.$langs->trans("Sous-tâches").'</h3>
	// 	        	<div id="sous_taches" style="overflow: auto;">
	// 	        		<form method="POST" action="'.$_SERVER["PHP_SELF"].'" id="form_progress_tasks">
	// 		        		<table id="tablelines" class="noborder" width="100%">
	// 							<thead><tr class="oddeven"><th class="">'.$langs->trans("Réf_Libellé_Tâche").'</th><th class="" align="right">'.$langs->trans("Progression_déclarée").'</th></tr></thead>
	// 							<tbody>
	// 							</tbody>
	// 						</table>
	// 						<div style="text-align:right;margin-top: 12px;">
	// 							<button type="submit" class="button_avanc_tasks button button_save_" onclick="update_avanc_tasks();" disabled="disabled">'.$langs->trans("save").'</button>
	// 						</div>
	// 					</form>
	// 	        	</div>
	// 	        </div>
	// 	        <hr style="margin: 25px 0 17px;">
	// 	        <div>
	// 		        <h3 class="title">'.$langs->trans("addcomment").'</h3>
	// 	        	<p style="margin-bottom: 0;"><textarea id="tache_comment" class="textarea_comment" rows="2" onkeyup="comment_change()" placeholder="'.$langs->trans("Écrivez_un_commentaire").'…"></textarea></p>
	// 	        	<input class="id_comment" value="" type="hidden" />
	// 	        	<input class="id_comment" value="" type="hidden" />
	// 	        	<input id="editornew_cmt" value="edit" type="hidden" />
	// 	        	<form method="POST" action="'.$_SERVER["PHP_SELF"].'" class="photos" enctype="multipart/form-data" onsubmit="upload_file(this,event)">
	// 					<div class="one_file">
	// 	        			<span class="add_joint" onclick="trigger_upload_file(this)"><i class="fa fa-paperclip"></i></span>
	// 		        		<input class="add_photo" type="file" name="photo[]" onchange="change_upload_file(this)"/>
	//         			</div>
	//         			<span class="add_plus" onclick="new_input_joint(this)"><i class="fa fa-plus"></i></span>
	//         			<div></div>
	//         			<hr>
	//         		</form>
	// 		        <button class="comment_btn create_comment button button_save_ disabled" onclick="create_comment(this);" disabled>'.$langs->trans("save").'</button>
	// 		        <br><br>
	// 	        </div>
	// 	        <div>
	// 		        <div id="commentaires">
	// 		        </div>
	// 	        </div>
	//         </div>
	//     </div>
	// </div>';

	// print '<div id="lightbox" style="display:none;"><div id="content"><img src="" /></div></div>';

print '</form>';

// print '<div class="window-overlay" id="poptasks">';
// 	print '<div id="kanban_new_task">';

		
// 	print '</div>';
// print '</div>';

// print '<div class="window-overlay" id="popcomments">';
// 	print '<div id="kanban_comments">';
// 		print '<a class="kanban_close_comments" onclick="closecomments(this)"><span class="fas fa-times"></span></a>';
// 		print '<div class="title_commnts">';
// 			print '<span>'.$langs->trans('Comments').'</span>';
// 		print '</div>';
// 		print '<div class="kanban_body_comments">';
// 			print '<div class="kanban_new_comment">';
// 				print '<input type="hidden" id="id_task">';
// 				print '<div class="kanban_user_comment">';
// 					print $user->getNomUrl(-2);
// 				print ': </div>';
// 				print '<form>';
// 				print '<div class="kanban_txt_comment">';
// 					print '<textarea placeholder="'.$langs->trans('writecomment').'" id="txt_comment"></textarea>';
// 					print '<a class="butAction savecomment" onclick="savecomment(this)">'.$langs->trans('Save').'</a>';
// 					print '<a class="butAction cancelcomment" onclick="cancelcomment(this)">'.$langs->trans('Cancel').'</a>';
// 				print '</div>';
// 				print '</form>';
// 			print '</div>';
// 			print '<div class="kanban_list_comments">';
// 			print '</div>';
// 		print '</div>';
// 	print '</div>';
// print '</div>';

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
llxFooter();