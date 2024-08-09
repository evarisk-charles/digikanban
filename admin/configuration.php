<?php
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

dol_include_once('/ganttproadvanced/class/ganttproadvanced.class.php');

$ganttproadvanced       = new ganttproadvanced($db);
$formcompany    = new FormCompany($db);
$task           = new Task($db);
$formother      = new FormOther($db);

// Translations
$langs->load("ganttproadvanced@ganttproadvanced");
$langs->load("project");
$langs->load("admin");


// Parameters
$action = GETPOST('action', 'alpha');

if(!empty($action)){
    if (! $user->admin) accessforbidden();
}


$p_sortfield = GETPOST('p_sortfield','alpha') ? GETPOST('p_sortfield','alpha') : 'p.ref';
$p_sortorder = GETPOST('p_sortorder','alpha') ? GETPOST('p_sortorder','alpha') : 'ASC';

$t_typecontact = GETPOST('t_typecontact','alpha') ? GETPOST('t_typecontact','alpha') : '';


/*
 * Actions
 */

if(!empty($action)){

    $error = 0;

    if($action == "update"){

        if(!dolibarr_set_const($db, "DIGIKANBAN_TYPE_CONTACT_TO_BASE_ON", $t_typecontact, 'chaine', 0, '', $conf->entity))
            $error++;

    }

    if(!$error)
        setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    else
        setEventMessage($langs->trans("Error"), 'errors');

    header('Location: ./configuration.php');
    exit;
}



/*
 * View
 */
$page_name = $langs->trans('ModuleSetup').' '.$langs->trans('ganttproadvanced');
llxHeader('', $page_name);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'. $langs->trans("BackToModuleList").'</a>';
print_fiche_titre($page_name, $linkback);


// Setup page goes here
$form=new Form($db);

$var=false;

$t_showuserinchart = $ganttproadvanced->t_showuserinchart;
$scroll_currentday = $ganttproadvanced->scroll_currentday;
$default_zoom = $ganttproadvanced->default_zoom;

print '<div class="tabBar tabBarWithBottom">';

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update" />';
    print '<table class="border dstable_" width="100%">';


            print '<tr>';
                print '<td class="titlefield ">'.$langs->trans('ContactTypeTasksFromWhereWeGetColor').'</td>';
                print '<td>';
                    $formcompany->selectTypeContact($task, $t_typecontact, 't_typecontact', 'internal', 'rowid', 1);
                print '</td>';
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
dol_fiche_end(1);

print '</div>';



llxFooter();
$db->close();

