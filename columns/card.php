<?php 

if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

dol_include_once('/digikanban/lib/digikanban.lib.php');
dol_include_once('/digikanban/class/digikanban.class.php');
dol_include_once('/digikanban/class/digikanban_columns.class.php');

if (empty($conf->digikanban->enabled) || !$user->rights->digikanban->lire) accessforbidden();

$langs->load('digikanban@digikanban');

$title = $langs->trans('Colomndigikanban');

$form    = new Form($db);
$author  = new User($db);
$object  = new digikanban_columns($db);

$request_method = $_SERVER['REQUEST_METHOD'];
$action  = GETPOST('action', 'alpha');

$backtopage = GETPOST('backtopage', 'alpha');

$id      = (int) ( (!empty($_GET['id'])) ? $_GET['id'] : GETPOST('id') ) ;
$page    = GETPOST('page');

if(!empty($id)){
    $object->fetch($id);
    if (!($object->rowid > 0))
    {
        $langs->load("errors");
        print($langs->trans('ErrorRecordNotFound'));
        exit;
    }
} 

// $usercanread = $user->rights->digikanban->lire;
// $usercancreate = $user->rights->digikanban->creer;
// $usercandelete = $user->rights->digikanban->supprimer;

$usercanread = $user->admin;
$usercancreate = $user->admin;
$usercandelete = $user->admin;

$error  = false;

if(in_array($action, ["add","update","create","edit"])) {
    if (!$usercancreate) {
        accessforbidden();
    }
}

if($action == "delete") {
    if (!$usercandelete) {
        accessforbidden();
    }
}


if(GETPOST('cancel')){
    header('Location: ./list.php');
    exit;
}

if($action == 'add' || $action == 'update'){
    $label = addslashes(GETPOST('label'));
    $object->label = GETPOST('label');


    if(!$label){
        setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Label')), null, 'errors');
        header('Location: card.php?action=create');
        exit();
    }
}

if ($action == 'add' && $usercancreate) {
    

    $res = $object->create($user);
    if ($res > 0) {
        if (!empty($backtopage))
        {
            $url = str_replace('ID_TYPE', $res, $backtopage);
            header("Location: ".$url);
            exit;
        }
        header('Location: ./card.php?id='. $res);
        exit;
    } else {
        setEventMessages($object->error, null, 'errors');
        header('Location: card.php?action=create');
        exit;
    }
}

if ($action == 'update' && $usercancreate) {

    $isvalid = $object->update($user);

    if($isvalid > 0 && $id){  
        header('Location: ./card.php?id='.$id);
        exit;
    }else {
        header('Location: ./card.php?id='. $id .'&update=0');
        exit;
    }
}

// If delete of request
if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' && $usercandelete ) {
    
    if (!$id || $id <= 0) {
        header('Location: ./card.php?action=request&error=dalete_failed&id='.$id);
        exit;
    }

    $obj = new digikanban_columns($db);
    $obj->fetch($id);
    $error = $obj->delete($id);

    if ($error == 1) {

        header('Location: list.php?delete='.$id.'&page='.$page);
        exit;
    }
    else {      
        header('Location: card.php?delete=1&page='.$page);
        exit;
    }
}



/* ------------------------ View ------------------------------ */

$morejs = array();
$modname = "";
llxHeader(array(), $modname,'','','','',$morejs,0,0);

$linkback ="";
digikanbanPrepareAdminHead('columns', $linkback, 'title_setup');

$htmlright = "";

if($id && !$object->id){
    $object->fetch($id);
}

 print_fiche_titre($title, $htmlright, $object->picto);

// methode ajouter un element
if($action == "create" || $action == "edit"){

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="digikanban" id="formtocreate">';

        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        $actionform = $action == "create" ? 'add' : 'update';
        print '<input type="hidden" name="action" value="'.$actionform.'" />';
        if($backtopage)
            print '<input type="hidden" name="backtopage" value="'.$backtopage.'" />';
        if($id)
            print '<input type="hidden" name="id" value="'.$id.'" />';

        // print dol_get_fiche_head('');
        print '<table class="border centpercent">';
            print '<tbody>';
                print '<tr>';
                    print '<td class="maxwidth100 width100 fieldrequired">'.$langs->trans('Label').'</td>';
                    print '<td><input name="label" class="width75p maxwidth750" value="'.(isset($object->label) ? $object->label : '').'"></td>';
                print '</tr>';
            print '</tbody>';
        print '</table>';

        print '<br>';
       
        // Actions
        print '<br>';
        print '<div class="center">';
            print '<input type="submit" value="'.$langs->trans('Save').'" name="bouton" class="button" />';
            if($action == 'edit')
                print '<a href="./card.php?id='.$id.'" class="button">'.$langs->trans("Cancel").'</a></center>';
            print '<a href="./list.php" class="butAction" >'.$langs->trans("BackToList").'</a></center>';

        print '</div>';
    print '</form>';

}

// methode afficher / delete
if(($id && empty($action)) || $action == "delete"){


    if($action == "delete"){
        print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('Confirm'), $langs->trans('msg_confirm'), 'confirm_delete', '', 'no', 1);
    }

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" class="digikanban hrmreglefaute" id="cardtrips">';

        print '<input type="hidden" name="confirm" value="no" id="confirm" />';
        print '<input type="hidden" name="id" value="'.$id.'" />';
        print '<input type="hidden" name="page" value="'.$page.'" />';
        $author->fetch($object->fk_user_author);
        print '<table class="border tableforfield centpercent">';
            print '<tbody>';
               print '<tr>';
                    print '<td class="titlefield">'.$langs->trans('Label').'</td>';
                    print '<td>'.$object->label.'</td>';
                print '</tr>';
                
                print '<tr>';
                    print '<td class="titlefield">'.$langs->trans('DateLastModification').'</td>';
                    print '<td class="">'.dol_print_date($object->tms, 'day').'</td>';
                print '</tr>';
                
                print '<tr>';
                    print '<td class="titlefield">'.$langs->trans('Date').'</td>';
                    print '<td class="">'.dol_print_date($object->datec, 'day').'</td>';
                print '</tr>';

                print '<tr>';
                    print '<td class="titlefield">'.$langs->trans('Author').'</td>';
                    print '<td class="">'.$author->getNomUrl(1).'</td>';
                print '</tr>';
            print '</tbody>';
        print '</table>';

        print '<br>';
       
        print '<div class="tabsAction">';
            if($usercancreate)
                print '<a class="butAction relative_div_" href="./card.php?id='.$object->rowid.'&action=edit">'.$langs->trans('Modify').'</a>';
            else
                print '<span class="butActionRefused classfortooltip" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('Modify').'</span>';
            if($usercandelete)
                print '<a class="butActionDelete  relative_div_" href="./card.php?id='.$object->rowid.'&action=delete">'.$langs->trans('Delete').'</a>';
            else
                print '<span class="butActionRefused classfortooltip" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('Delete').'</span>';

            print '<a href="./list.php" class="butAction" >'.$langs->trans("BackToList").'</a>';
        print '</div>';

    print '</form>';
}

llxFooter();
if (is_object($db)) $db->close();

?>