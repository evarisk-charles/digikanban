<?php
/* Copyright (C) 2015	Yassine Belkaid	<y.belkaid@nextconcept.ma>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       salariescontracts/common.inc.php
 *		\ingroup    salariescontracts
 *		\brief      Common load of data
 */

if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);

// require_once realpath(dirname(__FILE__)).'/../main.inc.php';
$res=@include("../../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php")) $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");        // For "custom" directory


require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';


dol_include_once('/digikanban/class/digikanban.class.php');
dol_include_once('/digikanban/lib/digikanban.lib.php');


$task  = new Task($db);
$form = new Form($db);
$formcompany   = new FormCompany($db);
$kanban     = new digikanban($db);

// Translations
$langs->load("digikanban@digikanban");
$langs->load("ecm");


// Parameters
$action = GETPOST('action', 'alpha');

if(!empty($action)){
    if (! $user->admin) accessforbidden();
}

$status_grey    = GETPOST('grey');
$status_green   = GETPOST('green');
$status_red     = GETPOST('red');
$t_typecontact = GETPOST('t_typecontact','alpha') ? GETPOST('t_typecontact','alpha') : '';
$searchbycontacttype = GETPOST('searchbycontacttype','alpha') ? GETPOST('searchbycontacttype','alpha') : '';
$nbrheurstravail = GETPOST('nbrheurstravail','alpha') ? GETPOST('nbrheurstravail','alpha') : '';
$DELEY_ALERTE_DATEJALON     = GETPOST('DELEY_ALERTE_DATEJALON');
$showallprojets = GETPOST('showallprojets');
$hidetaskisprogress100 = GETPOST('hidetaskisprogress100');
$showtaskinfirstcolomn = GETPOST('showtaskinfirstcolomn');
$refreshpageautomatically = GETPOST('refreshpageautomatically');
$maxnumbercontactstodisplay = GETPOST('maxnumbercontactstodisplay');

$fields_edit_popup = GETPOST('fields_edit_popup', 'array') ? implode(',', GETPOST('fields_edit_popup', 'array')) : '';
$fields_hover_popup = GETPOST('fields_hover_popup', 'array') ? implode(',', GETPOST('fields_hover_popup', 'array')) : '';

// d($t_typecontact);
if(!empty($action)){

    $error = 0;

	if($action == 'valide'){


	    if(!dolibarr_set_const($db, "KANBAN_SEARCH_BY_CONTACT_TYPE", $searchbycontacttype, 'chaine', 0, '', $conf->entity))
	    	$error++;
	    if(!dolibarr_set_const($db, "KANBAN_MAXIMUM_NUMBER_OF_CONTACTS_TO_DISPLAY", $maxnumbercontactstodisplay, 'chaine', 0, '', $conf->entity))
	    	$error++;
		if(!dolibarr_set_const($db, "KANBAN_NOMBRE_HEURES_DE_TRAVAIL_PAR_JOUR", $nbrheurstravail, 'chaine', 0, '', $conf->entity))
	    	$error++;
		if(!dolibarr_set_const($db, "KANBAN_TYPE_CONTACT_TO_BASE_ON", $t_typecontact, 'chaine', 0, '', $conf->entity))
	    	$error++;
		if(!dolibarr_set_const($db,'KANBAN_STATUT_DATE_GREY',$status_grey,'chaine',0,'',$conf->entity))
			$error++;
		if(!dolibarr_set_const($db,'KANBAN_STATUT_DATE_GREEN',$status_green,'chaine',0,'',$conf->entity))
			$error++;
		if(!dolibarr_set_const($db,'KANBAN_STATUT_DATE_RED',$status_red,'chaine',0,'',$conf->entity))
			$error++;
		if(!dolibarr_set_const($db,'DELEY_ALERTE_DATEJALON',$DELEY_ALERTE_DATEJALON,'int',0,'',$conf->entity))
			$error++;
		if(!dolibarr_set_const($db, "DIGIKANBAN_FIELDS_TO_SHOW_IN_EDIT_POPUP", $fields_edit_popup, 'chaine', 0, '', $conf->entity))
            $error++;
		if(!dolibarr_set_const($db, "DIGIKANBAN_FIELDS_TO_SHOW_IN_HOVER_POPUP", $fields_hover_popup, 'chaine', 0, '', $conf->entity))
            $error++;
	 	if(!$error)
	        setEventMessage($langs->trans("SetupSaved"), 'mesgs');

	    else
	        setEventMessage($langs->trans("Error"), 'errors');


	}
	elseif($action == 'set_showallprojects'){
        if(!dolibarr_set_const($db, "DIGIKANBAN_SHOW_ALL_PROJETS", $showallprojets, 'chaine', 0, '', $conf->entity))
            $error++;
    }
	elseif($action == 'set_hidetaskisprogress100'){
        if(!dolibarr_set_const($db, "DIGIKANBAN_HIDE_TASKISPROGRESS100", $hidetaskisprogress100, 'chaine', 0, '', $conf->entity))
            $error++;
    }
	elseif($action == 'set_showtaskinfirstcolomn'){
        if(!dolibarr_set_const($db, "DIGIKANBAN_SHOW_TASKNOSTATUS_IN_FIRSTCOLOMN", $showtaskinfirstcolomn, 'chaine', 0, '', $conf->entity))
            $error++;
    }
	elseif($action == 'set_refreshpageautomatically'){
        if(!dolibarr_set_const($db, "DIGIKANBAN_REFRESH_PAGE_AUTOMATICALLY", $refreshpageautomatically, 'chaine', 0, '', $conf->entity))
            $error++;
    }

    header('Location: ./admin.php');
    exit;
}

$page_name = $langs->trans('config_vue_kanban');
llxHeader('',$page_name);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

// print_fiche_titre($page_name, $linkback);

digikanbanPrepareAdminHead('general', $linkback, 'title_setup');

$t_typecontact = $kanban->t_typecontact;
$searchbycontacttype = $kanban->searchbycontacttype;
$maxnumbercontactstodisplay = $kanban->maxnumbercontactstodisplay;

$fields_edit_popup = $kanban->fields_edit_popup ? explode(',', $kanban->fields_edit_popup) : [];
$fields_hover_popup = $kanban->fields_hover_popup ? explode(',', $kanban->fields_hover_popup) : [];

print '<div class="tabBar tabBarWithBottom">';
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="">';
	    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	    print '<input type="hidden" name="action" value="valide" />';
	    print '<table class="border dstable_" width="100%">';

	        $status_grey = (dolibarr_get_const($db,'KANBAN_STATUT_DATE_GREY',$conf->entity) ? dolibarr_get_const($db,'KANBAN_STATUT_DATE_GREY',$conf->entity) : $langs->trans('grey') );
	        $status_green = (dolibarr_get_const($db,'KANBAN_STATUT_DATE_GREEN',$conf->entity) ? dolibarr_get_const($db,'KANBAN_STATUT_DATE_GREEN',$conf->entity) : $langs->trans('green') );
	        $status_red = (dolibarr_get_const($db,'KANBAN_STATUT_DATE_RED',$conf->entity) ? dolibarr_get_const($db,'KANBAN_STATUT_DATE_RED',$conf->entity) : $langs->trans('red'));

			// print '<tr>';
			// 	print '<td class="valigntop" style="width:25%;" rowspan="3">'.$langs->trans("color_datejalon").'</td>';
			// 	print '<td style="width:5%;">'.$langs->trans("grey").': </td>';
			// 	print '<td><input type="text" name="grey" value="'.$status_grey.'" class="width200" ></td>';
			// print '</tr>';

			// print '<tr >';
			// 	print '<td style="width:5%;">'.$langs->trans("green").': </td>';
			// 	print '<td><input type="text" name="green" value="'.$status_green.'" class="width200" ></td>';
			// print '</tr>';

			// print '<tr >';
			// 	print '<td style="width:5%;">'.$langs->trans("red").': </td>';
			// 	print '<td><input type="text" name="red" value="'.$status_red.'" class="width200" ></td>';
			// print '</tr>';

			print '<tr>';
                print '<td class="titlefield" >'.$langs->trans('type_contact_tache').'</td>';
                print '<td colspan="2">';
					$lesTypes = $task->liste_type_contact('internal', 'position', 1);
        			print $form->selectarray('t_typecontact', $lesTypes, $t_typecontact, 0, 0, 0, '', 0, 0, 0, '', 'minwidth200', 1);

                print '</td>';
            print '</tr>';

            // print '<tr>';
            //     print '<td class="titlefield" >'.$langs->trans('SearchByContactType').'</td>';
            //     print '<td colspan="2">';
			// 		$lesTypes = $task->liste_type_contact('internal', 'position', 1);
        	// 		print $form->selectarray('searchbycontacttype', $lesTypes, $searchbycontacttype, 0, 0, 0, $moreattr, 0, 0, 0, '', 'minwidth200', 1);

            //     print '</td>';
            // print '</tr>';

			print '<tr>';
                print '<td class="titlefield">'.$langs->trans('MaximumNumberOfContactsToDisplayNextToThePrimaryUser').'</td>';
                print '<td colspan="2"><input type="number" name="maxnumbercontactstodisplay" value="'.$maxnumbercontactstodisplay.'" min="0" class="width50"> </td>';
            print '</tr>';

            $delay = $conf->global->DELEY_ALERTE_DATEJALON;
			print '<tr>';
                print '<td class="titlefield">'.$langs->trans('DELEY_ALERTE_DATEJALON').'</td>';
                print '<td colspan="2"><input type="number" name="DELEY_ALERTE_DATEJALON" value="'.$delay.'" class="width50"> '.$langs->trans('Days').'</td>';
            print '</tr>';

            $nbrheurs = $conf->global->KANBAN_NOMBRE_HEURES_DE_TRAVAIL_PAR_JOUR;
			print '<tr>';
                print '<td class="titlefield" >'.$langs->trans('DaylyHours').'</td>';
                print '<td colspan="2"><input type="number" name="nbrheurstravail" value="'.$nbrheurs.'" class="width50"> '.$langs->trans('Hours').'</td>';
            print '</tr>';

            print '<tr class=""><td colspan="3"><hr></td></tr>';


            print '<tr>';
	            print '<td class="titlefield nowraponall">'.$langs->trans('Refresh').' '.strtolower($langs->trans('Page').' '.$langs->trans('ECMTypeAuto')).'</td>';
	            print '<td colspan="2">';
	            	$refreshpageautomatically = isset($conf->global->DIGIKANBAN_REFRESH_PAGE_AUTOMATICALLY) ? $conf->global->DIGIKANBAN_REFRESH_PAGE_AUTOMATICALLY : 1;
	                if ($refreshpageautomatically) {
	                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_refreshpageautomatically&refreshpageautomatically=0">';
	                    print img_picto($langs->trans("Activated"), 'switch_on');
	                    print '</a>';
	                } else {
	                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_refreshpageautomatically&refreshpageautomatically=1">';
	                    print img_picto($langs->trans("Disabled"), 'switch_off');
	                    print '</a>';
	                }
	            print '</td>';
	        print '</tr>';

            print '<tr>';
	            print '<td class="titlefield nowraponall">'.$langs->trans('showallprojets').'&nbsp;&nbsp;</td>';
	            print '<td colspan="2">';
	            	$showallprojets = isset($conf->global->DIGIKANBAN_SHOW_ALL_PROJETS) ? $conf->global->DIGIKANBAN_SHOW_ALL_PROJETS : '';
	                if ($showallprojets) {
	                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showallprojects&showallprojets=0">';
	                    print img_picto($langs->trans("Activated"), 'switch_on');
	                    print '</a>';
	                } else {
	                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showallprojects&showallprojets=1">';
	                    print img_picto($langs->trans("Disabled"), 'switch_off');
	                    print '</a>';
	                }
	            print '</td>';
	        print '</tr>';

            print '<tr>';
	            print '<td class="titlefield nowraponall">'.$langs->trans('hidetaskisprogress100', '100%').'&nbsp;&nbsp;</td>';
	            print '<td colspan="2">';
	            	$hidetaskisprogress100 = !empty($conf->global->DIGIKANBAN_HIDE_TASKISPROGRESS100) ? $conf->global->DIGIKANBAN_HIDE_TASKISPROGRESS100 : '';
	                if ($hidetaskisprogress100) {
	                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_hidetaskisprogress100&hidetaskisprogress100=0">';
	                    print img_picto($langs->trans("Activated"), 'switch_on');
	                    print '</a>';
	                } else {
	                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_hidetaskisprogress100&hidetaskisprogress100=1">';
	                    print img_picto($langs->trans("Disabled"), 'switch_off');
	                    print '</a>';
	                }
	            print '</td>';

            print '<tr>';
	            print '<td class="titlefield nowraponall">'.$langs->trans('showtaskinfirstcolomn').'&nbsp;&nbsp;</td>';
	            print '<td colspan="2">';
	            	$showtaskinfirstcolomn = $kanban->showtaskinfirstcolomn;
	                if ($showtaskinfirstcolomn) {
	                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showtaskinfirstcolomn&showtaskinfirstcolomn=0">';
	                    print img_picto($langs->trans("Activated"), 'switch_on');
	                    print '</a>';
	                } else {
	                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showtaskinfirstcolomn&showtaskinfirstcolomn=1">';
	                    print img_picto($langs->trans("Disabled"), 'switch_off');
	                    print '</a>';
	                }
	            print '</td>';
	        print '</tr>';
	        print '</tr>';


	        print '<tr class=""><td colspan="3"><hr></td></tr>';

	        print '<tr>';
	            print '<td>'.$langs->trans('FieldsToShowWhenModifyingATask').'</td>';
	            print '<td colspan="2">';
	               print $kanban->selectFieldsToShowInTaskPopup($fields_edit_popup,'fields_edit_popup', $_multiple = 1, $_showempty = 1);
	            print '</td>';
        	print '</tr>';

	        print '<tr class=""><td colspan="3"><hr></td></tr>';

        	print '<tr>';
	            print '<td>'.$langs->trans('FieldsToShowWhenHoveringATask').'</td>';
	            print '<td colspan="2">';
	               print $kanban->selectFieldsToShowInTaskPopup($fields_hover_popup,'fields_hover_popup', $_multiple = 1, $_showempty = 1, $_select_hoverpopup = true);
	            print '</td>';
        	print '</tr>';

	        print '<tr class=""><td colspan="3"><hr></td></tr>';

	        print '<tr>';
				print '<td class="valigntop" style="width:25%;" rowspan="3">'.$langs->trans("color_datejalon").'</td>';
				print '<td style="width:5%;">'.$langs->trans("grey").': </td>';
				print '<td><input type="text" name="grey" value="'.$status_grey.'" class="width200" ></td>';
			print '</tr>';

			print '<tr >';
				print '<td style="width:5%;">'.$langs->trans("green").': </td>';
				print '<td><input type="text" name="green" value="'.$status_green.'" class="width200" ></td>';
			print '</tr>';

			print '<tr >';
				print '<td style="width:5%;">'.$langs->trans("red").': </td>';
				print '<td><input type="text" name="red" value="'.$status_red.'" class="width200" ></td>';
			print '</tr>';

	    print '</table>';

	    print '<br>';

	    // Actions
	    print '<table class="" width="100%">';
	    print '<tr>';
	        print '<td colspan="2" align="left">';
	        print '<input type="submit" value="'.$langs->trans('Validate').'" name="bouton" class="button" />';
	        print '</td>';
	    print '</tr>';
	    print '</table>';

	print '</form>';
print '</div>';

print '<script>';
    print '$(document).ready(function(){';
        print '$("#fields_edit_popup").select2();';
        print '$("#fields_hover_popup").select2();';
    print '});';
print '</script>';

llxFooter();