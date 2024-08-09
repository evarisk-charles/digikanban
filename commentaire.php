<?php 

if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");       // For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php"); // For "custom" directory


require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

dol_include_once('/digikanban/class/digikanban_commnts.class.php');
dol_include_once('/core/class/html.form.class.php');

$langs->load('digikanban@digikanban');
$langs->loadLangs(array('projects', 'users', 'companies', 'bills', 'products', 'stocks', 'orders', 'other'));

$modname = $langs->trans("Comments");
$hookmanager->initHooks(array('projecttaskcard', 'globalcard'));

// Initial Objects
$digikanban  = new digikanban_commnts($db);
$objectp  = new Project($db);

$object  = new Task($db);
$tache   = new Task($db);
$form    = new Form($db);
// Get parameters
$request_method = $_SERVER['REQUEST_METHOD'];
$action         = GETPOST('action', 'alpha');
$confirm        = GETPOST('confirm', 'alpha');
$page           = GETPOST('page');
$ref            = GETPOST('ref', 'alpha');
$idline         = GETPOST('idline', 'alpha');
$id             = (int) ( (!empty($_GET['id'])) ? $_GET['id'] : GETPOST('id') ) ;
$id_comment     = GETPOST('id_comment');
$id_delete      = GETPOST('id_delete');
$withproject    = GETPOST('withproject', 'int');
$digikanban->fetch($id);
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$param="";
$num="";
$nbtotalofrecords ="";
$linktocreatetime ="";
$limit ="";

if(GETPOST('cancel')){
    header('Location: ./list.php');
    exit;
}

if($id>0 || !empty($ref)){
    $result = $object->fetch($id, $ref);
    if ($result < 0) {
        setEventMessages(null, $object->errors, 'errors');
    }
    $result = $object->fetch_thirdparty();
    if ($result < 0) {
        setEventMessages(null, $object->errors, 'errors');
    }
    $result = $object->fetch_optionals();
    if ($result < 0) {
        setEventMessages(null, $object->errors, 'errors');
    }
    $id = $id ? $id : $object->id;    
    $id_proj = $object->fk_project;
    $objectp->fetch($id_proj);

}
if($objectp->id>0);
    $object->project = clone $objectp;


$comment = addslashes(GETPOST('comment'));

if (GETPOST('save')) {
    $now = dol_now();


    $data = [
        'comment'   => $comment,
        'date'      => $db->idate($now),
        'fk_user'   => $user->id,
        'fk_task'   => $id,
    ];


    $res = $digikanban->create($data);     

    if ($res > 0) {
        header('Location: ./commentaire.php?id='. $id);
        exit;
    } else {
        header('Location: commentaire.php?action=create');
        exit;
    }
}

if (GETPOST('update')) {

    $data = [
        'comment'   => $comment,
    ];

    $isvalid = $digikanban->update($id_comment, $data);
    // d($isvalid);

    if($isvalid > 0 && $id){ 
        header('Location: ./commentaire.php?id='.$id);
        exit;
    }else {
        header('Location: ./commentaire.php?id='. $id .'&update=0');
        exit;
    }
}
// If delete of request
if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {
    
    if (!$id || $id <= 0) {
        header('Location: ./card.php?action=request&error=dalete_failed&id='.$id);
        exit;
    }

    $obj = new digikanban_commnts($db);
    $obj->fetch($id_delete);
    $error = $obj->delete($id_delete);

    if ($error == 1) {
        header('Location: commentaire.php?id='.$id);
        exit;
    }
    else {      
        header('Location: commentaire.php?id=1');
        exit;
    }
}


$morejs  = array();
$morecss = array('digikanban/css/style.css');

llxHeader(array(), $modname,'','','','',$morejs,$morecss,0);


    $tab = (GETPOSTISSET('tab') ? GETPOST('tab') : 'tasks');

    if (!empty($withproject)) {

        $head = project_prepare_head($objectp);
        print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($objectp->public ? 'projectpub' : 'project'));
            $param = '&id='.$objectp->id;

            $linkback = '<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$id_proj.'&restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

            $morehtmlref = '<div class="refidno">';
            // Title
            $morehtmlref .= $langs->trans('Project').' : <a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$id_proj.'">'.$objectp->title.'</a>';
            // Thirdparty
            if ($objectp->thirdparty->id > 0) {
                $morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$objectp->thirdparty->getNomUrl(1, 'project');
            }
            $morehtmlref .= '</div>';

            // Define a complementary filter for search of next/prev ref.
            if (!$user->rights->projet->all->lire) {
                $objectsListId = $objectp->getProjectsAuthorizedForUser($user, 0, 0);
                $objectp->next_prev_filter = " rowid IN (".$db->sanitize(count($objectsListId) ?join(',', array_keys($objectsListId)) : '0').")";
            }

            dol_banner_tab($objectp, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

            print '<div class="fichecenter">';
                print '<div class="fichehalfleft">';
                    print '<div class="underbanner clearboth"></div>';
                    print '<table class="border tableforfield centpercent">';

                        // Usage
                        if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES) || empty($conf->global->PROJECT_HIDE_TASKS) || !empty($conf->eventorganization->enabled)) {
                            print '<tr><td class="tdtop">';
                            print $langs->trans("Usage");
                            print '</td>';
                            print '<td>';
                            if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
                                print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($objectp->usage_opportunity ? ' checked="checked"' : '')).'"> ';
                                $htmltext = $langs->trans("ProjectFollowOpportunity");
                                print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
                                print '<br>';
                            }
                            if (empty($conf->global->PROJECT_HIDE_TASKS)) {
                                print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($objectp->usage_task ? ' checked="checked"' : '')).'"> ';
                                $htmltext = $langs->trans("ProjectFollowTasks");
                                print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
                                print '<br>';
                            }
                            if (empty($conf->global->PROJECT_HIDE_TASKS) && !empty($conf->global->PROJECT_BILL_TIME_SPENT)) {
                                print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($objectp->usage_bill_time ? ' checked="checked"' : '')).'"> ';
                                $htmltext = $langs->trans("ProjectBillTimeDescription");
                                print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
                                print '<br>';
                            }
                            if (!empty($conf->eventorganization->enabled)) {
                                print '<input type="checkbox" disabled name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($objectp->usage_organize_event ? ' checked="checked"' : '')).'"> ';
                                $htmltext = $langs->trans("EventOrganizationDescriptionLong");
                                print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
                            }
                            print '</td></tr>';
                        }

                        // Visibility
                        print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
                        if ($objectp->public) {
                            print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
                            print $langs->trans('SharedProject');
                        } else {
                            print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
                            print $langs->trans('PrivateProject');
                        }
                        print '</td></tr>';

                        // Date start - end
                        print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
                        $start = dol_print_date($objectp->date_start, 'day');
                        print ($start ? $start : '?');
                        $end = dol_print_date($objectp->date_end, 'day');
                        print ' - ';
                        print ($end ? $end : '?');
                        if ($objectp->hasDelay()) {
                            print img_warning("Late");
                        }
                        print '</td></tr>';

                        // Budget
                        print '<tr><td>'.$langs->trans("Budget").'</td><td>';
                        if (strcmp($objectp->budget_amount, '')) {
                            print price($objectp->budget_amount, '', $langs, 1, 0, 0, $conf->currency);
                        }
                        print '</td></tr>';
                    print '</table>';
                print '</div>';

                print '<div class="fichehalfright">';
                    print '<div class="ficheaddleft">';
                        print '<div class="underbanner clearboth"></div>';
                            print '<table class="border tableforfield centpercent">';
                                // Description
                                print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
                                print nl2br($object->description);
                                print '</td></tr>';


                                // Categories
                                if ($conf->categorie->enabled) {
                                    print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
                                    print $form->showCategories($object->id, Categorie::TYPE_PROJECT, 1);
                                    print "</td></tr>";
                                }

                            print '</table>';
                        print '</div>';
                    print '</div>';
                print '</div>';

            print '<div class="clearboth"></div>';


        print dol_get_fiche_end();

        print '<br>';

    }

// $method = "GET";
// if($action == "editline" || $action == "create") $method = 'POST';*
// if($action == "create"){
$fk_task = !empty($fk_task) ? $fk_task : '';

print '<form class="formlistdigikanban" method="POST" action="'.$_SERVER["PHP_SELF"].'">';

    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    // print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="action" value="updateline">';
    print '<input type="hidden" name="withproject" value="'.$withproject.'">';
    print'<input type="hidden" value="'.$id.'" id="id_task">';
    print '<input type="hidden" name="id" value="'.$id.'">';
    print '<input type="hidden" name="id_comment" value="'.$id_comment.'">';
    print '<input type="hidden" name="id_delete" value="'.$id_delete.'">';

    $object->fetch($id);
    $head = task_prepare_head($object);
    print dol_get_fiche_head($head, 'tab_commentaire', $langs->trans("Task"), -1, 'projecttask', 0, '', '');

    // $fk_task = GETPOST('id_task');

    $sql = ' SELECT * FROM '.MAIN_DB_PREFIX.'digikanban_commnts';
    $sql .= ' WHERE fk_task='.$id;
    $sql .= ' ORDER BY rowid DESC';

    $resql = $db->query($sql);
    $num = $db->num_rows($resql);
    $nbtotalofrecords = $nbtotalofrecords;
    print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, 'rowid', 'DESC', '', $num, $nbtotalofrecords, 'list', 0, $linktocreatetime, '', $limit, 0, 0, 1);

        print '<table class="border centpercent">';
            print '<tbody>';
                print '<tr>';
                    print '<td width="1%"> '.$user->getNomUrl(-2).' </td>';
                    print ' <td class="kanban_txt_comments" width="74%">';
                        print'<textarea name="comment" placeholder="'.$langs->trans('writecomment').'" style="width: 98%;" rows="3" id="txt_comments"></textarea>';
                    print '</td>';
                    print ' <td width="25%">';
                        print '<input type="submit" value="'.$langs->trans('Save').'" name="save" class="butAction" />';
                        print'<a class="butAction" onclick="cancelcomments(this)">'.$langs->trans('Cancel').'</a>';
                    print '</td>';
                print '</tr>';
            print '</tbody>';
        print '</table>';
        print '<br>';
        
        if($action == "delete" && $id_delete){
            print $form->formconfirm($_SERVER['PHP_SELF'].'?id_delete='.$id_delete.'&id='.$id, $langs->trans('Confirm'), $langs->trans('msg_confirm'), 'confirm_delete', '', 'no', 1);
        }

        if($resql){
            while ($obj = $db->fetch_object($resql)) {
                $Users  = new User($db);
                $Users->fetch($obj->fk_user);

                print '<table style="background: #f7f7f7;" width="100%">';
                    print '<tbody>';
                        print '<tr>';
                            print '<td>';
                                print $Users->getNomUrl(-2);
                                print '<span class="user_comments">'.$Users->lastname.' '.$Users->firstname.'</span>' ;
                                print '<span class="date_comments">'.$langs->trans('at').' '.dol_print_date($db->jdate($obj->date), 'dayhour').'</span>';
                            print '</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td class="show_comments">';
                            if($action == "edit" && $id_comment == $obj->rowid)

                                print'<textarea name="comment" placeholder="'.$langs->trans('writecomment').'" style="width: 98%;" rows="3" id="txt_comments">'.$obj->comment.'</textarea>';
                            else
                                print $obj->comment;

                            print '</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td>';
                            if($action == "edit" && $id_comment == $obj->rowid){
                                print '<div>';
                                    print '<input type="submit" value="'.$langs->trans('Save').'" name="update" class="butAction" />';
                                    print '<a href="./commentaire.php?id='.$id.'" class="butAction">'.$langs->trans("Cancel").'</a></center>';
                                print '</div>';
                            }elseif($user->id == $obj->fk_user){
                                print '<div>';
                                    print '<a class="edit_comments" href="commentaire.php?action=edit&id_comment='.$obj->rowid.'&id='.$id.'"> '.img_edit().' '.$langs->trans('Modify').'</a>';
                                    print '<a class="delete_comments" href="commentaire.php?action=delete&id_delete='.$obj->rowid.'&id='.$id.'" >'.img_delete().' '.$langs->trans('Delete').'</a>';
                                print '</div>';
                            }
                            print '</td>';
                        print '</tr>';
                    print '</tbody>';
                print '</table><br>';
            }
        }

    print dol_get_fiche_end();
print '</form>';

?>

<script>
    $(document).ready(function() {
    });
    function cancelcomments(that) {
        $('.kanban_txt_comments textarea').val('');
    }

</script>

<?php

llxFooter();

if (is_object($db)) $db->close();
?>