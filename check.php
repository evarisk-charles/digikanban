<?php
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
//require_once('../main.inc.php');
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");       // For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php"); // For "custom" directory
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';


require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

include_once DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

dol_include_once('/digikanban/class/digikanban_checklist.class.php');
dol_include_once('/digikanban/class/digikanban.class.php');
dol_include_once('/digikanban/class/digikanban_tags.class.php');
dol_include_once('/digikanban/lib/digikanban.lib.php');
dol_include_once('/digikanban/class/digikanban_commnts.class.php');
dol_include_once('/digikanban/class/digikanban_modeles.class.php');
dol_include_once('/digikanban/class/digikanban_columns.class.php');

$digikanban_tags = new digikanban_tags($db);
$modals      = new digikanban_modeles($db);

$ganttproadvanced = new stdClass();

if(isset($conf->ganttproadvanced) && $conf->ganttproadvanced->enabled){
	dol_include_once('/ganttproadvanced/class/ganttproadvanced.class.php');
	$ganttproadvanced = new ganttproadvanced($db);
}

$task 	    = new Task($db);
$tasks      = new Task($db);
$projet 	= new Project($db);
$kanban     = new digikanban($db);

$kanbancommnts  = new digikanban_commnts($db);

$societe 	= new Societe($db);
$project 	= new Project($db);

$taskstatic = new Task($db);
$object     = new digikanban($db);
$formother  = new FormOther($db);
$form 	    = new Form($db);
$tmpuser	= new User($db);
$extrafields = new ExtraFields($db);
$checklist   = new digikanban_checklist($db);

$action 				= GETPOST('action');
$id_tache 				= GETPOST('id_tache');
$year           		= GETPOST('year');
$search_all      		= GETPOST('search_all');
$sortfield      		= GETPOST('sortfield');
$sortorder      		= GETPOST('sortorder');
$search_tags 			= GETPOST('search_tags', 'array');
$search_projects 		= GETPOST('search_projects', 'array');
$search_tasktype 		= GETPOST("search_tasktype", 'array');
$search_affecteduser 	= GETPOST('search_affecteduser', 'array');


$ids_of_projects 		= $search_projects ? implode(",", $search_projects) : '';
$search_affecteduser 	= $search_affecteduser ? implode(',', $search_affecteduser) : '';
$search_tags 			= $search_tags ? implode(',', $search_tags) : '';



$etats = [
	"ToDo" => $langs->trans("To_Do"),
	"EnCours" => $langs->trans("EnCours"),
	"AValider" => $langs->trans("a_Valider"),
	"Validé" => $langs->trans("Validé")
];

// Mise a jour taches

if($action == 'createtask' && $user->rights->projet->creer){

	$result=array();

	$task	= GETPOST('task');
	$colomn	= GETPOST('colomn');

	$label = GETPOST('label');
	$fk_projet=GETPOST('fk_projet');
	$users_tasks=GETPOST('users_tasks');
    $userid   = GETPOST('userid');
    $budget   = GETPOST('budget');
    $usercontact = GETPOST('usercontact');
	$endmin=GETPOST('endmin');
	$endhour=GETPOST('endhour');
	$endday=GETPOST('endday');
	$endmonth=GETPOST('endmonth');
	$endyear=GETPOST('endyear');

	$startmin=GETPOST('startmin');
	$starthour=GETPOST('starthour');
	$startday=GETPOST('startday');
	$startmonth=GETPOST('startmonth');
	$startyear=GETPOST('startyear');
	$description=GETPOST('description');
	// $progress=GETPOST('progress');
	$progress = GETPOST('progress', 'int');

	// $extrafields->fetch_name_optionals_label($tasks->table_element);
	// 	if($extrafields->attributes[$tasks->table_element]['label']){
	// 		foreach ($extrafields->attributes[$tasks->table_element]['label'] as $key => $value) {
	// 			'options_'.$key = GETPOST('options_'.$key);
	// 			 $extrafield = 'options_'.$key;
	// 			 // d($extrafield, false);
	// 			}	
	// 	}

	        		

	$planned_workloadhour = (GETPOST('durehour', 'int')>0 ? GETPOST('durehour', 'int') : 0);
	$planned_workloadmin = (GETPOST('duremin', 'int')>0 ?GETPOST('duremin', 'int') : 0);
	$planned_workload = $planned_workloadhour * 3600 + $planned_workloadmin * 60;

	$error = 0;

	$projectid 	 = 0;


	$date_start = dol_mktime($starthour, $startmin, 0, $startmonth, $startday, $startyear);
	$date_end = dol_mktime($endhour, $endmin, 0, $endmonth, $endday, $endyear);
	

	// $progress = number_format($progress,2)*100;

	$defaultref = '';
	$obj = empty($conf->global->PROJECT_TASK_ADDON) ? 'mod_task_simple' : $conf->global->PROJECT_TASK_ADDON;
	if (!empty($conf->global->PROJECT_TASK_ADDON) && is_readable(DOL_DOCUMENT_ROOT."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.".php")) {
		require_once DOL_DOCUMENT_ROOT."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.'.php';
		$modTask = new $obj;
		$project->fetch($fk_projet);
		$project->fetch_thirdparty();
		$defaultref = $modTask->getNextValue($project->thirdparty, null);
	}

	if (is_numeric($defaultref) && $defaultref <= 0) {
		$defaultref = '';
	}


	// $date_start = '2022-01-04';
	// $date_end = '2022-01-05';


	if (!$error) {
		// $tmparray = $objtaskparent;
		// $projectid = $tmparray[0];
		// if (empty($projectid)) {
		// 	$projectid = $id; // If projectid is ''
		// }
		// $objtask_parent = $tmparray[1];
		// if (empty($objtask_parent)) {
		// 	$objtask_parent = 0; // If task_parent is ''
		// }

		$task = new Task($db);

		$task->ref = $defaultref;
		$task->label = $label;
		$task->budget_amount = $budget;
		$task->fk_project = $fk_projet;
		$task->description = $description;
		$task->planned_workload = $planned_workload;
		$task->date_c = dol_now();
		$task->date_start = $date_start;
		$task->date_end = $date_end;
		$task->progress = $progress;

		// unset($data['start_date']);
		// unset($data['end_date']);
		// d($data);

		if (!empty($date_start) && !empty($date_end) && $date_start > $date_end) {
			$result['errormsg'] = $langs->trans('StartDateCannotBeAfterEndDate');
		}else{
			$objtaskid = $task->create($user);
		}

		// $result['ref_task'] = $objtask->ref;
		
		if ($objtaskid > 0) {
			$tasks= new task($db);
			$tasks->fetch($objtaskid);
	        $ret = $extrafields->setOptionalsFromPost(null, $tasks);
			$tasks->array_options['options_digikanban_colomn'] = $colomn;
	        $results =$tasks->insertExtraFields();
	        // d($kanban->t_typecontact);
	        if($userid) {

				$res = $task->add_contact($userid, $kanban->t_typecontact, 'internal');

	        }
			
			if($usercontact){
		        foreach ($usercontact as $key => $value) {
					$res = $task->add_contact($value, 'TASKCONTRIBUTOR', 'internal');
		        }
			}
            

			$result['msg'] = $langs->trans('Notify_TASK_CREATE').' : '.$task->ref.($task->label ? '-'.$task->label : '');
			// $result['taskid'] = 'task'.$objtaskid;
			$result['taskid'] = $objtaskid;
			$result['projectid'] = $fk_projet;
			$result['typemsg'] = 'warning';
			
		}
		
		echo json_encode($result);
	}
}

elseif($action == 'cloner_task' && $user->rights->projet->creer){

	$result=array();

	$task	= GETPOST('task');
	$colomn = GETPOST("colomn");
	$id_task = GETPOST("id_tache");

	$tache  = new Task($db);
    $tache->fetch($id_task);
	$label = GETPOST('label');
	$fk_projet=$tache->fk_project;
    // d('$fk_project: '.$tache->fk_project);
	$users_tasks=GETPOST('users_tasks');
	$userid=GETPOST('userid');
	$budget=GETPOST('budget');

	$endmin=GETPOST('endmin');
	$endhour=GETPOST('endhour');
	$endday=GETPOST('endday');
	$endmonth=GETPOST('endmonth');
	$endyear=GETPOST('endyear');

	$startmin=GETPOST('startmin');
	$starthour=GETPOST('starthour');
	$startday=GETPOST('startday');
	$startmonth=GETPOST('startmonth');
	$startyear=GETPOST('startyear');
	$description=GETPOST('description');
	$progress = GETPOST('progress', 'int');

	        		

	$planned_workloadhour = (GETPOST('durehour', 'int')>0 ? GETPOST('durehour', 'int') : 0);
	$planned_workloadmin = (GETPOST('duremin', 'int')>0 ?GETPOST('duremin', 'int') : 0);
	$planned_workload = $planned_workloadhour * 3600 + $planned_workloadmin * 60;

	$error = 0;

	$projectid 	 = 0;


	$date_start = dol_mktime($starthour, $startmin, 0, $startmonth, $startday, $startyear);
	$date_end = dol_mktime($endhour, $endmin, 0, $endmonth, $endday, $endyear);
	

	// $progress = number_format($progress,2)*100;

	$defaultref = '';
	$obj = empty($conf->global->PROJECT_TASK_ADDON) ? 'mod_task_simple' : $conf->global->PROJECT_TASK_ADDON;
	if (!empty($conf->global->PROJECT_TASK_ADDON) && is_readable(DOL_DOCUMENT_ROOT."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.".php")) {
		require_once DOL_DOCUMENT_ROOT."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.'.php';
		$modTask = new $obj;
		$project->fetch($fk_projet);
		$project->fetch_thirdparty();
		$defaultref = $modTask->getNextValue($project->thirdparty, null);
	}

	if (is_numeric($defaultref) && $defaultref <= 0) {
		$defaultref = '';
	}


	if (!$error) {

		$task = new Task($db);

		$task->ref = $defaultref;
		$task->label = $label;
		$task->budget_amount = $budget;
		$task->fk_project = $fk_projet;
		$task->description = $description;
		$task->planned_workload = $planned_workload;
		$task->date_c = dol_now();
		$task->date_start = $date_start;
		$task->date_end = $date_end;
		$task->progress = $progress;


		$objtaskid = $task->create($user);
        // d($objtaskid);
		// $result['ref_task'] = $objtask->ref;
		$lincomments ="";
		if ($objtaskid > 0) {
			$tasks= new task($db);
			$tasks->fetch($objtaskid);
	        $ret = $extrafields->setOptionalsFromPost(null, $tasks);
			$tasks->array_options['options_digikanban_colomn'] = $colomn;
	        $results =$tasks->insertExtraFields();
	       
	       	if($userid){
				$res = $task->add_contact($userid, $kanban->t_typecontact, 'internal');
			}

			$result['msg'] = $langs->trans('Notify_TASK_CREATE').' : '.$task->ref.($task->label ? '-'.$task->label : '');
			// $result['taskid'] = 'task'.$objtaskid;
			$result['taskid'] = $objtaskid;
			
			$result['projectid'] = $fk_projet;
			$result['typemsg'] = 'warning';

				$tab = $tache->liste_contact(-1, 'internal', 0, 'TASKCONTRIBUTOR');
				$num = count($tab);
				$i = 0;
				while ($i < $num) {
					$tasks->add_contact($tab[$i]['id'], 'TASKCONTRIBUTOR', $tab[$i]['source']);
				
					$i++;
				}
			

				$sql = ' SELECT * FROM '.MAIN_DB_PREFIX.'digikanban_commnts';
				$sql .= ' WHERE fk_task='.$id_task;
				$sql .= ' ORDER BY rowid ASC';
				// d($sql, false);
				$resql = $db->query($sql);
				if($resql){
					$num = $db->num_rows($resql);
					if($num>0){
						while ($obj = $db->fetch_object($resql)) {
							$lincomments .=  '("'.$db->escape($obj->comment).'", '.$objtaskid.', '.$obj->fk_user.', "'.$obj->date.'"),';
						}

						$sql2 = 'INSERT INTO '.MAIN_DB_PREFIX.'digikanban_commnts (comment, fk_task, fk_user, date) VALUES';
						$lincomments = substr($lincomments, 0, -1);
						$sql2 .= $lincomments;
						// d($sql2);
						$resql2 = $db->query($sql2);
					}
				}

				$sql = ' SELECT * FROM '.MAIN_DB_PREFIX.'digikanban_tagstask';
				$sql .= ' WHERE fk_task='.$id_task;
				$sql .= ' ORDER BY rowid ASC';

				$linetiquettes='';
				$resql = $db->query($sql);
				if($resql){
					$num = $db->num_rows($resql);
					if($num>0){
						while ($obj = $db->fetch_object($resql)) {
							$linetiquettes .=  '('.$obj->fk_tag.', '.$objtaskid.', '.$obj->checked.'),';
						}
						if($linetiquettes){
							$sql2 = 'INSERT INTO '.MAIN_DB_PREFIX.'digikanban_tagstask (fk_tag, fk_task, checked) VALUES';
							$linetiquettes = substr($linetiquettes, 0, -1);
							$sql2 .= $linetiquettes;
							$resql2 = $db->query($sql2);
						}
					}
				}

				$sql = ' SELECT * FROM '.MAIN_DB_PREFIX.'digikanban_checklist';
				$sql .= ' WHERE fk_task='.$id_task;
				$sql .= ' ORDER BY rowid ASC';

				$resql = $db->query($sql);

				$linecheckedlist = '';

				if($resql){
					$num = $db->num_rows($resql);
					if($num>0){
						while ($obj = $db->fetch_object($resql)) {
							$linecheckedlist .=  '("'.$db->escape($obj->label).'", '.$objtaskid.', '.$obj->checked.'),';
						}

						$sql2 = 'INSERT INTO '.MAIN_DB_PREFIX.'digikanban_checklist (label, fk_task, checked) VALUES';
						$linecheckedlist = substr($linecheckedlist, 0, -1);
						$sql2 .= $linecheckedlist;
						$resql2 = $db->query($sql2);
					}
				}


			echo json_encode($result);
		}
	}
}

elseif($action == 'update_task' && $user->rights->projet->creer){

	$result=array();
    $currentday = strtotime(date('Y-m-d'));
	$task	= GETPOST('id_tache');
	$datejalon	= GETPOST('datejalon');
	$colomn	= GETPOST('colomn');
	// $to_etat 	= $_POST['to_etat'];
	// d('to_etat'.$to_etat);
	$label = GETPOST('label');
	$fk_projet=GETPOST('fk_projet');
	$usercontact=GETPOST('usercontact');
	$userid=GETPOST('userid');

	$endmin=GETPOST('endmin');
	$endhour=GETPOST('endhour');
	$endday=GETPOST('endday');
	$endmonth=GETPOST('endmonth');
	$endyear=GETPOST('endyear');

	$startmin=GETPOST('startmin');
	$starthour=GETPOST('starthour');
	$startday=GETPOST('startday');
	$startmonth=GETPOST('startmonth');
	$startyear=GETPOST('startyear');
	$description=GETPOST('description');
	$progress=GETPOST('progress');

	$jalonday=GETPOST('jalonday');
	$jalonmonth=GETPOST('jalonmonth');
	$jalonyear=GETPOST('jalonyear');

	$planned_workloadhour = (GETPOST('durehour', 'int')>0 ? GETPOST('durehour', 'int') : 0);
	$planned_workloadmin = (GETPOST('duremin', 'int')>0 ?GETPOST('duremin', 'int') : 0);
	$planned_workload = $planned_workloadhour * 3600 + $planned_workloadmin * 60;

	$error = 0;

	$projectid 	 = 0;


	$date_start = dol_mktime($starthour, $startmin, 0, $startmonth, $startday, $startyear);
	$date_end = dol_mktime($endhour, $endmin, 0, $endmonth, $endday, $endyear);

	$date_jalon = dol_mktime(0, 0, 0, $jalonmonth, $jalonday, $jalonyear);

	// d('date_start : '.dol_print_date(($date_start), '%Y-%m-%d'),0);
	// d('date_end : '.dol_print_date(($date_end), '%Y-%m-%d'),0);
	// die;

	// $progress = number_format($progress,2)*100;


	if (!$error) {
		// $tmparray = $objtaskparent;
		// $projectid = $tmparray[0];
		// if (empty($projectid)) {
		// 	$projectid = $id; // If projectid is ''
		// }
		// $objtask_parent = $tmparray[1];
		// if (empty($objtask_parent)) {
		// 	$objtask_parent = 0; // If task_parent is ''
		// }

		$task = new Task($db);
		$task->fetch($id_tache);


		$task->label = GETPOST("label", "alphanohtml");
		if (empty($conf->global->FCKEDITOR_ENABLE_SOCIETE)) $task->description = GETPOST('description', "alphanohtml");
		else $task->description = GETPOST('description', "restricthtml");
		// $task->fk_task_parent = $task_parent;
		$task->planned_workload = $planned_workload;
		$task->date_start = $date_start;
		$task->date_end = $date_end;
		$task->progress = price2num(GETPOST('progress', 'alphanohtml'));
		$task->budget_amount = price2num(GETPOST('budget', 'alphanohtml'));


		// Fill array 'array_options' with data from add form
		// $ret = $extrafields->setOptionalsFromPost(null, $objtask);
		// $ret = $extrafields->setOptionalsFromPost(null, $tasks);
		// // d($ret);
		// if ($ret < 0) {
		// 	$error++;
		// }

		// unset($data['start_date']);
		// unset($data['end_date']);
		// d($task);
	  	if (!empty($date_start) && !empty($date_end) && $date_start > $date_end) {
			$result['errormsg'] = $langs->trans('StartDateCannotBeAfterEndDate');
		}else{
			$objtaskid = $task->update($user);
		}

		// $result['ref_task'] = $objtask->ref;
		
		if ($objtaskid > 0) {

			$tasks= new task($db);
			// $tasks->fetch($objtaskid);
			$tasks->fetch($id_tache);
			$ret = $extrafields->setOptionalsFromPost(null, $tasks);
			$tasks->array_options['options_digikanban_colomn'] = $colomn;
			$results =$tasks->insertExtraFields();
	        if($userid){

				$sql = "DELETE FROM  ".MAIN_DB_PREFIX."element_contact WHERE element_id = ".((int) $task->id);
				// $sql .= ' AND fk_c_type_contact IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'c_type_contact WHERE code ="TASKEXECUTIVE")';
				$resql = $db->query($sql);

				$res = $task->add_contact($userid, $kanban->t_typecontact, 'internal');
	        }
			if($usercontact && count($usercontact)>0){
				foreach ($usercontact as $key => $value) {
					$res = $task->add_contact($value, 'TASKCONTRIBUTOR', 'internal');
				}
			}

		    // if($date_jalon <= $currentday) {
		    // 	$tasks->array_options['options_color_datejalon']='red';
			// 	$res = $tasks->update($user);
		    // }elseif($tasks->array_options['options_color_datejalon'] != 'green'){
		    // 	$tasks->array_options['options_color_datejalon']='grey';
			// 	$res = $tasks->update($user);
		    // }


			$result['msg'] = $langs->trans('Notify_TASK_MODIFY').' : '.$task->ref.($task->label ? '-'.$task->label : '');
			$result['taskid'] = 'task'.$objtaskid;
			$result['taskid'] = $id_tache;
			$result['projectid'] = $fk_projet;
			$result['typemsg'] = 'warning';
			
		}
	echo json_encode($result);
	}
}

elseif($action == 'getinfotask'){

	$top = GETPOST('top');
	$left = GETPOST('left');

	$id_task = GETPOST('id_task');
	$task = new Task($db);
	$task->fetch($id_task);

	$html ='<div class="kanban_tooltip" style="top: '.$top.'px;left: '.$left.'px">';
		$html .= '<div class="kanbanpophover">';
			$html .= '<span class="gTtTitle">'. img_picto('', $task->picto).' <b><u>'.$langs->trans("Task").'</u>:</b></span>';
		$html .= '</div>';
		$html .= '<div class="kanbanpophover">';
			$html .= '<span class="gTtTitle"><b>'. $langs->trans("Label").':</b></span>';
			$html .= '<span class="gTILine">'.$task->label.'</span>';
		$html .= '</div>';
		$html .= '<div class="kanbanpophover">';
			$html .= '<span class="gTtTitle"><b> '.$langs->trans("Period").':</b></span>';
			$html .= '<span class="gTILine">'.dol_print_date($task->date_start, 'dayhour');
			$html .= $task->date_end ? ' - '.dol_print_date($task->date_end, 'dayhour') : '';
			$html .= '</span>';
		$html .= '</div>';
		$html .= '<div class="kanbanpophover">';
			$html .= '<span class="gTtTitle"><b>'.$langs->trans("ProgressDeclared").':</b></span>';
			$html .= '<span class="gTILine">'.($object->progress ? $object->progress.' %' : '').'<span>';
		$html .= '</div>';
	$html .= '</div>';

	echo $html;
}

elseif($action == 'changeSatusdate'){
	$id_task = GETPOST('id_tache');
	$progress = GETPOST('progress', 'int');

	$task = new Task($db);
	$task->fetch($id_task);
	$name_color = isset($task->array_options['options_color_datejalon']) ? $task->array_options['options_color_datejalon'] : 'grey';
    $datejalon  = $task->array_options['options_ganttproadvanceddatejalon'];
    // $coljal = $task->array_options['options_color_datejalon'];
    $currentday = strtotime(date('Y-m-d'));

    $checked = $name_color ? '' : 'checked';
    // if($datejalon > $currentday || ($datejalon <= $currentday && $task->array_options['options_color_datejalon'] !='red') || ($datejalon <= $currentday && $task->progress == 100)) {
    	$html ="";
		$html .= ' <dd class="dropdowndd kanban_colorstatus">
	            <div class="multiselectcheckbox">';
	                $html .= '<ul class="ul Kanban_statuscolor_ul">';

	                $datejalontitle = $langs->trans("Progress").': '.($task->progress ? $task->progress : 0).' %';
	                $html .= '<li class="kanbantitleprogresstask">'.$datejalontitle.'</li>';

	                $disabledgris = '';
	    			if( $datejalon > $currentday || ($datejalon <= $currentday && $task->progress == 100) || !$datejalon) {
	                	// $disabledgris = 'disabled';
	                	$html .= '<li><label class="greylabel"><input '.$task->progress.' '.$datejalon.' '.$currentday.' type="radio" value="grey" id="grey" '.$checked.' name="statuscolor" data-id="'.$id_task.'" onchange="checkstatuscolor(this)" ><span class="inbox_statusgrey"><label for="grey">'.$conf->global->KANBAN_STATUT_DATE_GREY.'</label><span></label></li>';
					}
	                $html .= '<li><label><input type="radio" value="red" id="red" name="statuscolor" data-id="'.$id_task.'" onchange="checkstatuscolor(this)" ><span class="inbox_statusred	"><label for="red">'.$conf->global->KANBAN_STATUT_DATE_RED.'</label></span></label></li>
	                <li><label><input type="radio" value="green" id="green" name="statuscolor" data-id="'.$id_task.'" onchange="checkstatuscolor(this)" ><span class="inbox_statusgreen"><label for="green">'.$conf->global->KANBAN_STATUT_DATE_GREEN.'</label></span></label></li>
	                </ul>
	            </div>
	        </dd>';
	    if($name_color)
	    $html = str_replace('value="'.$name_color.'"', 'value="'.$name_color.'" checked', $html);
		echo $html;
	// }
}

elseif($action == 'checkstatuscolor'){

	$id_task = GETPOST('id_tache');
	$str_color = GETPOST('color');

	// d('from_etat'.$from_etat);

	$statuscolor = $kanban->status_date;
	$color = isset($statuscolor[$str_color]) ? $statuscolor[$str_color] : '';


	$tasks= new task($db);
	$tasks->fetch($id_task);

    $datejalon  = $tasks->array_options['options_ganttproadvanceddatejalon'];
    $currentday = strtotime(date('Y-m-d'));

    // $ret = $extrafields->setOptionalsFromPost(null, $tasks);
    // $results =$tasks->insertExtraFields();

		$extrafields->fetch_name_optionals_label($tasks->table_element);
		$tasks->array_options['options_color_datejalon']=$str_color;
		$res = $tasks->update($user);
		if($res>0){

			$data = array('color' => $color, 'value'=>$str_color);


		    if($str_color == 'red'){
				$tasks->array_options['options_digikanban_taskurgent'] = 1;
				$d = $tasks->update($user, 1);
		        $results = $tasks->insertExtraFields();
			}else{
		        $tasks->array_options['options_color_datejalon']=$str_color;
				$tasks->array_options['options_digikanban_taskurgent'] = '';
				$res = $tasks->update($user);
			}

		}
		

			// $task->array_options['options_digikanban_taskurgent'] = 0;
			// $d = $task->update($user, 1);

		// if($extrafields->attributes[$tasks->table_element]['label']){
		// 	foreach ($extrafields->attributes[$tasks->tatable_elementble_element]['label'] as $key => $value) {
		// 		// d($extrafields->attributes[$tasks->]['label'][$key]);
		// 		if($extrafields->attributes[$tasks->table_element]['label'][$key] == 'color_datejalon'){

		// 		}

		// 	}
		// }

    // d($extrafields, false);

	// $result['taskid'] = 'task'.$objtaskid;
	
	echo json_encode($data);
}

elseif($action == "getallTasks") {

	$arr = [];
	$search_status = GETPOST('search_status');
	$html=[];
	$html['ToDo'] =[];
	$html['ToDo']['content'] ='';
	$data=array();

	$debutmonth = GETPOST('debutmonth');
	$debutyear = GETPOST('debutyear');
	$finmonth = GETPOST('finmonth');
	$finyear = GETPOST('finyear');
	$id_tache = GETPOST('id_tache');
	$progressless100 = GETPOST('progressless100');

	$debut = dol_mktime(0, 0, 0, $debutmonth, 1, $debutyear);
	// $fin = dol_mktime(23, 59, 0, $finmonth, 1, $finyear);
	$fin = dol_get_last_day($finyear, $finmonth);

	$sql = 'SELECT t.*, MONTH(t.dateo) as month, count(DISTINCT c.rowid) as nb_comments, ';
	$sql .= ' p.ref as ref_proj, p.title as label_proj, ef.ganttproadvancedcolor as color, ef.ganttproadvanceddatejalon';
    $sql .= ", us.rowid as usrowid, us.lastname as lastname, us.firstname, us.statut as status, us.login, us.admin, us.photo, us.email";
	$sql .= ' FROM '.MAIN_DB_PREFIX.'projet_task as t ';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as p ON p.rowid = t.fk_projet';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task_extrafields as ef on (t.rowid = ef.fk_object)';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'digikanban_commnts as c on (t.rowid = c.fk_task)';

	// if($conf->global->DIGIKANBAN_TYPE_CONTACT_TO_BASE_ON) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as elem ON (t.rowid = elem.element_id) ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc ON (tc.rowid = elem.fk_c_type_contact) ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON (u.rowid = elem.fk_socpeople) ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as us ON (us.rowid = t.fk_user_creat) ";
	// }

	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."digikanban_tagstask as tag ON (t.rowid = tag.fk_task) ";
	$sql .= ' WHERE t.entity='.$conf->entity;

	if($id_tache) {
		$sql .= ' AND t.rowid = '.$id_tache;
	} else {

		$sql .= ($ids_of_projects!='') ? ' AND t.fk_projet IN ('.$ids_of_projects.')' : ' AND 1<0 ';
		

		if($search_status != 100){
	        if ($search_status == 99) {
	            $sql .= " AND p.fk_statut <> 2";
	        } else {
	            $sql .= " AND p.fk_statut = ".((int) $search_status);
	        }
	    }

		if($search_affecteduser) {
			$sql .= ' AND u.rowid IN ('.$search_affecteduser.')';
			
			if($search_tasktype) {
				$tmptasktypes = implode('","', $search_tasktype);
				$sql_tasktypes = '"'.$tmptasktypes.'"';

				if($sql_tasktypes != '""') {
	                $sql .= '  AND (tc.code IN ('.$sql_tasktypes.') OR tc.code is NULL)';
	            }
			} else {
				// if($kanban->t_typecontact)
				// $sql .= ' AND ( tc.code = "'.$kanban->t_typecontact.'" OR tc.code is NULL)';
			}

		}

		if($debut && $fin){
			$sql .= ' AND (';
			$sql .= ' (CAST(t.dateo as date) BETWEEN "'.$db->idate($debut).'" AND "'.$db->idate($fin).'")';
			$sql .= ' OR ';
			$sql .= ' (CAST(t.datee as date) BETWEEN "'.$db->idate($debut).'" AND "'.$db->idate($fin).'")';
			$sql .= ' OR ';
			$sql .= ' (CAST(t.dateo as date) < "'.$db->idate($debut).'" AND CAST(t.datee as date) > "'.$db->idate($fin).'")';
			// $sql .= ' OR ';
			// $sql .= ' (t.dateo is NULL)';
			// $sql .= ' OR ';
			// $sql .= ' (t.datee is NULL)';
			$sql .= ')';
		}

	}

	$sql .= $search_tags ? ' AND tag.fk_tag IN ('.$search_tags.') AND tag.checked=1' : '';

	$sql .= $search_all ? ' AND (t.ref LIKE "%'.$search_all.'%" OR t.label LIKE "%'.$search_all.'%" OR p.ref LIKE "%'.$search_all.'%" OR p.title LIKE "%'.$search_all.'%" OR tag.fk_tag IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'digikanban_tags WHERE label LIKE "%'.$search_all.'%"))' : '';

	// $sql .= $kanban->hidetaskisprogress100 > 0 ? ' AND t.progress < 100' : '';

	$sql .= $progressless100>0 ? ' AND (t.progress < 100 OR t.progress IS NULL OR t.progress="" OR t.progress=0)' : '';



	if(empty($object->showtaskinfirstcolomn)) {
		$sql .= ' AND ef.digikanban_colomn > 0';
	}




	// $sql .= $year ? ' AND YEAR(t.datec) =' .$year : '';
	$sql .= ' GROUP BY t.rowid ';
	if(!empty($sortfield) && $sortfield != '-1'  && $sortorder){
		$sql .= ' ORDER BY '.$sortfield.' '.$sortorder;
	}else
		$sql .= ' ORDER BY ef.tms DESC ';

		
	$resql = $db->query($sql);
	$colorgantt = (isset($conf->ganttproadvanced) && $conf->ganttproadvanced->enabled) ? $ganttproadvanced->p_projectcolor : '';

	$datebefore = '';

	$alertbeforedays = ($conf->global->DELEY_ALERTE_DATEJALON > 0) ? $conf->global->DELEY_ALERTE_DATEJALON : 0;

	// if($days > 0) {
	// 	$dnow = dol_now();
	// 	$datebefore = strtotime(date('Y-m-d') ."+".$days." days");
	// }

	// d('dnow : '.$dnow,0);
	// d('datebefore : '.$datebefore,0);

	// echo $sql; die;

	if($resql){
		while ($obj = $db->fetch_object($resql)) {
			$task = new Task($db);
			$task->fetch($obj->rowid);
		
			$color = $obj->color ? $obj->color : ($colorgantt ? $colorgantt : '#00ffff');
			$clr='';
			$arr_color = colorStringToArray($color);
			if($arr_color){
				foreach ($arr_color as $key => $value) {
					$clr .= $value.', ';
				}
			}

			// $datejalon = (isset($task->array_options['options_ganttproadvanceddatejalon']) && $task->array_options['options_ganttproadvanceddatejalon']) ? $task->array_options['options_ganttproadvanceddatejalon'] : '';

			// if($datejalon) d($task->array_options['options_ganttproadvanceddatejalon'],0);

			$bgcolor = $clr ? 'rgb('.$clr.'0.3)' : $color;
			$descrptask = '<span class="fas fa-align-left" ></span>';
			$descrptask .= '<u>'.$langs->trans('Description').'</u>: ';
			$descrptask .= '<strong>'.$obj->description.':</strong> ';

			// $debutday = $task->date_start ? date('d', $task->date_start) : '';
			// $debutmonth = $task->date_start ? date('m', $task->date_start) : '';
			// $debutyear = $task->date_start ? date('Y', $task->date_start) : '';

			// $finday = $task->date_end ? date('d', $task->date_end) : '';
			// $finmonth = $task->date_end ? date('m', $task->date_end) : '';
			// $finyear = $task->date_end ? date('Y', $task->date_end) : '';
			


			

			// if($datejalon && $task->date_start && $task->date_end) {

			// 	$coljal = $task->array_options['options_color_datejalon'];

			// 	if((!$coljal || $coljal == 'grey') && $datejalon < $currentday) {
			// 		$task->array_options['options_color_datejalon'] = 'red';
			// 		$updatetask = true;
			// 	}
			// 	elseif($coljal == 'red' && $datejalon >= $currentday) {
			// 		$task->array_options['options_color_datejalon'] = 'grey';
			// 		$updatetask = true;
			// 	}

			// 	if($alertbeforedays && $task->array_options['options_color_datejalon'] != 'green') {
			// 		$datediff 	= $currentday-$datejalon;
			// 		$duration 	= round($datediff / (60 * 60 * 24));

					
			// 		if($task->progress < 100) {
			// 			if($alertbeforedays && ($duration >= 0 || ($duration < 0 && abs($duration) <= $alertbeforedays)) && !$option_taskurgent) {
			// 				$tobetransferedto = 1;
			// 			}
			// 		} else {
			// 			$tobetransferedto = 2;
			// 		}
			// 		// if($task->progress == 100 || ($alertbeforedays && ($duration < 0 && abs($duration) > $alertbeforedays) && $option_taskurgent)) {
			// 		// }
			// 	}

			// }

			$tobetransferedto = 0;
			$updatetask = false;
			$option_taskurgent = !empty($task->array_options['options_digikanban_taskurgent']) ? $task->array_options['options_digikanban_taskurgent'] : '';
			$keytag = !empty($task->array_options['options_digikanban_colomn']) ? $task->array_options['options_digikanban_colomn'] : '';
			checkIfNeedToBeTransfered($task, $updatetask, $tobetransferedto, $option_taskurgent);

			$key = $keytag>0 ? $keytag : 1;


			// if(!$obj->dateo || !$obj->datee){
			// 	$html = getTaskelement($obj, $task, $kanban->status_date, 'enattent');
			// 	$data['enattent'][]=$html;
			// }

			// elseif (($option_taskurgent && !$tobetransferedto) || $tobetransferedto == 1) 
			// {
			// 	if(!$option_taskurgent) {
			// 		$task->array_options['options_digikanban_taskurgent'] = 1;
			// 		$updatetask = 1;
			// 	}

			// 	$html = getTaskelement($obj, $task, $kanban->status_date, 'urgents');
			// 	$data['urgents'][] = $html;

			// }
			// else {
			// 	if($obj->dateo){
			// 		$dd = dol_getdate($db->jdate($obj->dateo));
			// 		$dt = dol_mktime(0, 0, 0, $dd['mon'], 1, $dd['year']);

			// 		$html = getTaskelement($obj, $task, $kanban->status_date);

			// 		$data['month'.$dt][]=$html;
			// 	}

			// 	if($tobetransferedto == 2) {
			// 		$task->array_options['options_digikanban_taskurgent'] = 0;
			// 		$updatetask = 1;
			// 	}
			// }
			
			// if($task->array_options['options_digikanban_colomn']>0 || $object->showtaskinfirstcolomn>0){
				$html = getTaskelement($obj, $task, $kanban->status_date, 'urgents');
				$data['colomn'.$key][]=$html;
			// }

			if($updatetask) {
				$d = $task->update($user, 1);
			}
		}
	}

	echo json_encode($data);
}

$debut = '';
$fin = '';

$date = GETPOST('month');
$colomn = GETPOST('colomn');

if($date) {
	$dd = dol_getdate($date);
	if($dd){
		$debut = dol_mktime(0, 0, 0, $dd['mon'], 1, $dd['year']);
		$fin = dol_get_last_day($dd['year'], $dd['mon']);
	}
}

$search_status = GETPOST('search_status');
// $search_projects = GETPOST('search_projects') ? explode(',', GETPOST('search_projects')) : '';

if($action == 'addtask'){
	$task = new Task($db);

	

	$html = '';

	$html .= '<div class="kanban_titlemodal_task">';
		$html .= '<span class="kanban_title">'.$langs->trans('NewTask').'</span>';
		$html .= '<span class="kanban_time"></span>';
		$html .= '<input type="hidden" id="idcolomn" name="idcolomn" value="'.$colomn.'">';
	$html .= '</div>';

	$contenu = contentmodaltask('addtask', $task, $debut, $fin, $search_projects, $search_status);
	$html .= $contenu;

	$html .= '<div class="kanban_btn_set">';
		$html .= '<button id="save_task" class="button" onclick="createtask(this)">'.$langs->trans('Save').'</button>';
		$html .= '<button id="cancel_task" class="butAction" onclick="canceladdtask(this)">'.$langs->trans('Cancel').'</button>';
	$html .= '</div>';
		
	echo $html;
}

elseif($action == 'edittask'){
	$id_task = GETPOST("id_tache");
	$task = new Task($db);
	$task->fetch($id_tache);

	$fields_edit_popup = $kanban->fields_edit_popup ? explode(',', $kanban->fields_edit_popup) : [];
    $edit_popup = $fields_edit_popup ? array_flip($fields_edit_popup) : [];

	$html='';
	// print '<div id="kanban_new_task">';
		$html .= '<input type="hidden" name="fk_task" value="'.$id_tache.'" >';
		$html .= '<input type="hidden" id="idcolomn" name="idcolomn" value="'.$colomn.'">';
		$html .= '<div class="kanban_titlemodal_task">';
			$html .= '<span class="kanban_title">'.$langs->trans('Task').': ';

			if (isset($edit_popup['Ref'])) {
                $html .= $task->ref.($task->label ? ' - ' : '');
            }
			$html .= $task->label;

			$html .= '</span>';
			$html .= '<span class="kanban_time"></span>';
		$html .= '</div>';

		// Contenu MODEL
		$contenu = contentmodaltask('edittask', $task, $debut, $fin, $search_projects, $search_status);
		$html .= $contenu;

		$html .= '<div class="kanban_btn_set">';
			$html .= '<button id="update_task" class="button" onclick="update_task(this)">'.$langs->trans('Modify').'</button>';
			$html .= '<button id="cancel_task" data-id="'.$task->id.'" class="butAction" onclick="canceladdtask(this)">'.$langs->trans('Cancel').'</button>';
		$html .= '</div>';

	// print '</div>';
	
	echo $html;
}

elseif($action == 'clonertask'){

	$id_task = GETPOST("id_tache");
	$colomn = GETPOST("colomn");
	$task = new Task($db);
	$task->fetch($id_tache);
	$html='';
	// print '<div id="kanban_new_task">';
		$html .= '<input type="hidden" name="fk_task" value="'.$id_tache.'" >';
		$html .= '<input type="hidden" id="idcolomn" name="idcolomn" value="'.$colomn.'">';
		$html .= '<div class="kanban_titlemodal_task">';
			$html .= '<span class="kanban_title">'.$langs->trans('cloner_task').' '.$task->ref.'</span>';
			$html .= '<span class="kanban_time"></span>';
		$html .= '</div>';
		
		$contenu = contentmodaltask('clonertask', $task, $debut, $fin, $search_projects, $search_status);
		$html .= $contenu;

		$html .= '<div class="kanban_btn_set">';
			$html .= '<button id="cloner_task" class="butAction" onclick="cloner_task(this)">'.$langs->trans('Save').'</button>';
			$html .= '<button id="cancel_task" data-id="'.$task->id.'" class="butAction" onclick="canceladdtask(this)">'.$langs->trans('Cancel').'</button>';
		$html .= '</div>';

	// print '</div>';
	
	echo $html;
}

elseif($action == 'updattask'){
	
	$result = array();
	$result['msg'] = '';
	$result['error'] = 0;

	$task = new Task($db);
	$task->fetch($id_tache);

	$from_etat 		= $_POST['from_etat'];
	$to_etat 		= $_POST['to_etat'];
	
	$detailtask = $task->ref.' '.$task->label.' : <br>';


	$datejalon          = $task->array_options['options_ganttproadvanceddatejalon'];
	$option_taskurgent 	= $task->array_options['options_digikanban_taskurgent'];

	// if($from_etat == 'enattent' && $to_etat == 'urgents') { 
	// 	$result['error'] = 1;
	// 	$result['msg'] = $detailtask.$langs->trans("ErrorFieldRequired", $langs->transnoentities("DateStart").' & '.$langs->transnoentities("DateEnd")).'<br>';
	// }

	// elseif($from_etat == 'urgents' && $to_etat != "enattent") {

	// 	$tobetransferedto = 0;
	// 	$updatetask = false;
	// 	checkIfNeedToBeTransfered($task, $updatetask, $tobetransferedto, $option_taskurgent = 0);

	// 	if ($tobetransferedto == 1) {
	// 		$result['error'] = 1;
	// 		$result['msg'] = $detailtask.$langs->trans('DELEY_ALERTE_DATEJALON') .' ('.dol_print_date($datejalon, 'day').')';
	// 	}
	// }

	// elseif($to_etat == 'urgents') { 
	// 	if($task->array_options['options_ganttproadvanceddatejalon'] && $task->array_options['options_color_datejalon'] == 'green') {
	// 		$result['error'] = 1;
	// 		$result['msg'] = $detailtask.$langs->trans('color_datejalon') .' ('.$langs->trans('green').')';
	// 	}
	// }



	if(!$result['error']) {

		if($to_etat == "enattent"){

			if($task->fk_task_parent)
				$task->fk_task_parent = '';

			$task->date_start = '';
			$task->date_end = '';
			$d = $task->update($user, 1);

		}elseif($to_etat == 'urgents'){
			$task->array_options['options_digikanban_taskurgent'] = 1;
			$d = $task->update($user, 1);
	        $results = $tasks->insertExtraFields();
		}
		else{

			$oldcolomn = explode('colomn', $from_etat);
			$newcolomn = explode('colomn', $to_etat);

			// $d_fin = dol_getdate($fin[1]);

			// $dateo = dol_get_first_day($d_fin['year'], $d_fin['mon']);
			// $datee = dol_get_last_day($d_fin['year'], $d_fin['mon']);

			// $task->date_start = $dateo;
			// $task->date_end = $datee;

			$task->array_options['options_digikanban_colomn'] = $newcolomn[1];

			$task->update($user, 1);
	        $results = $task->insertExtraFields();
		}

		if($to_etat != 'urgents' && $to_etat != 'enattent' && $task->array_options['options_digikanban_taskurgent'] > 0) {
			$task->array_options['options_digikanban_taskurgent'] = 0;
			$d = $task->update($user, 1);
		}
	}


    $titletask = kanbanGetTitleOfCurrentTask($task);
    
	$result['titletask'] = $titletask;
	// $html = "true";
	echo json_encode($result);
}

// Mise a jour checklist

elseif($action == 'checklisttask'){
	$id_tache = GETPOST('id_tache');
	$id_modal = GETPOST('id_modal');
	$namemodal = GETPOST('namemodal');
	$task->fetch($id_tache);
	$progress = $checklist->calcProgressCheckTask($id_tache);
	$percent = $progress['percent'] ? $progress['percent'] : 0;
	$bgcolor = ($percent==100) ? 'background: #61bd4f' : 'background: #679fcb'; 
	$html = '<div class="kanban_wrap_section">';
		if($id_modal)
			$html .= '<strong class="title_poptags">'.$langs->trans("Update_checklistmodal").' "'.$namemodal.'"</strong>';
		else
			$html .= '<strong class="title_poptags">'.$langs->trans("Update_checklisttask").' "'.$task->ref.' - '.$task->label.'"</strong>';
	$html .= '</div>';
	if($id_tache){
		$html .= '<div class="progresschecklist">';
			$html .= '<table class="checklist_task_pop">';
				$html .= '<tr>';
			        $html .='<td class="width30px center"><span class="far fa-check-square" fas=""></span></td>';
					$html .= '<td><strong class="title_poptags">'.$langs->trans("checklisttask").'</strong></td>';
				$html .= '</tr>';
				$html .= '<tr>';
					$html .= '<td class="width30px center"><span class="valprogress">'.$percent.'</span>%</td>';
					$html .= '<td>';
						$html .= '<div class="progresstask">';
							$html .= '<div class="progress" style="width: '.$percent.'%; '.$bgcolor.'">';
							$html .= '</div>';
						$html .= '</div>';
					$html .= '</td>';
				$html .= '</tr>';
			$html .= '</table>';
		$html .= '</div>';
		$html .= $checklist->selectCheck($id_tache);
	}elseif($id_modal){
		$html .= selectCheckModal($id_modal);
	}

	$html .= '<div class="kanban_btn_set">';
		$html .= '<button id="saveckecklist" class="butAction" data-modal="'.$id_modal.'" onclick="saveckecklist(this)">'.$langs->trans('Modify').'</button>';
		$html .= '<button id="cancelchangetags" data-id="'.$task->id.'" data-modal="'.$id_modal.'" class="butAction" onclick="cancelchangetags(this)">'.$langs->trans('Cancel').'</button>';
	$html .= '</div>';

	echo $html;
}

elseif($action == 'getchecklist'){
	$id_tache = GETPOST('id_tache');
	$id = GETPOST('id');
	$numcheck = GETPOST('numcheck');
	$checklist->fetch($id);
    $checked = $checklist->checked ? 'checked' : '';
	
	$tags = '<table class="checklist_task_pop"><tr>';
        $tags .= '<td class="width30px center">';  
        $tags .= '<input type="hidden" class="numcheck" id="numcheck_'.$id.'" data-id="'.$id.'" name="checklist[numcheck]['.$id.']" value="'.$numcheck.'" />';

            $tags .= '<input type="checkbox" class="cursorpointer_task check_list" class="check_list" '.$checked.' data-id="'.$checklist->id.'" id="checkbox'.$checklist->id.'" onchange="calcProgress(this)" name="checklist[checked]['.$checklist->id.'] value="1" />';
        $tags .= '</td>';
        $tags .= '<td class="cursormove_task">';    
            $tags .= '<input type="hidden" id="label_check_'.$checklist->id.'" name="checklist[label]['.$checklist->id.']" value="'.$checklist->label.'" >';
            $tags .= '<label class="cursormove_task" for="checkbox'.$checklist->id.'">'.$checklist->label.'</label>';
        $tags .= '</td>';
        $tags .= '<td class="width50px center">';
            $tags .= '<a class="deletecheck cursorpointer_task" data-id="'.$checklist->id.'" onclick="deletecheck(this)">'.img_delete();
            $tags .= '<a class="editcheck cursorpointer_task" data-id="'.$checklist->id.'" data-num="'.$numcheck.'" onclick="editcheck(this)">'.img_edit();
        $tags .= '</td>';

    $tags .= '</tr></table>';

	echo $tags;
}

elseif($action == 'updatecheck'){
	$id_tache = GETPOST('id_tache');
	$id = GETPOST('id');
	$label = GETPOST('label');
	$numcheck = GETPOST('numcheck');

	$res = $checklist->update($id, ['label'=>$label]);

	$checklist->fetch($id);
	$tags = '<table class="checklist_task_pop"><tr>';
        $tags .= '<td class="width30px center">';
            $checked = $checklist->checked ? 'checked' : '';
    		$tags .= '<input type="hidden" class="numcheck" id="numcheck_'.$id.'" data-id="'.$id.'" name="checklist[numcheck]['.$id.']" value="'.$numcheck.'" />';
            $tags .= '<input type="checkbox" class="cursorpointer_task check_list" '.$checked.' data-id="'.$checklist->id.'" id="checkbox'.$checklist->id.'" name="checklist[checked]['.$checklist->id.'] value="1" onchange="calcProgress(this)" />';
        $tags .= '</td>';
        $tags .= '<td class="cursormove_task">';
            $tags .= '<input type="hidden" id="label_check_'.$checklist->id.'" name="checklist[label]['.$checklist->id.']" value="'.$checklist->label.'" >';
            $tags .= '<label class="cursormove_task" for="checkbox'.$checklist->id.'">'.$checklist->label.'</label>';
        $tags .= '</td>';
        $tags .= '<td class="width50px center">';
            $tags .= '<a class="deletecheck cursorpointer_task" data-id="'.$checklist->id.'" onclick="deletecheck(this)">'.img_delete();
        	$tags .= '<a class="editcheck cursorpointer_task" data-id="'.$checklist->id.'" data-num="'.$numcheck.'" onclick="editcheck(this)">'.img_edit().'</a>';
        $tags .= '</td>';
    $tags .= '</tr></table>';

	echo $tags;
}

elseif($action == 'saveckecklist'){
	$id_modal = GETPOST('id_modal');
	$id_tache = GETPOST('id_tache');
	$task = new Task($db);
	$task->fetch($id_tache);
	$dt_newcheck = GETPOST('dt_newcheck');
	$dt_editcheck = GETPOST('dt_editcheck');
	$checkdeleted = GETPOST('checkdeleted');

	$i=0;
	$res = true;
	$checkmodal =array();

	if($dt_editcheck && count($dt_editcheck)>0){
		foreach ($dt_editcheck as $key => $value) {
			if($value && count($value)>0 && $value['label']){
				if($id_tache)
					$checklist->update($key, ['label'=>$value['label'], 'numcheck' => $value['numcheck'], 'checked'=>$value['checked']]);
				$checkmodal[$i]['label']=$value['label'];
				$checkmodal[$i]['numcheck']=$value['numcheck'];
				$i++;
			}
		}
	}
	if($dt_newcheck){
		foreach ($dt_newcheck as $key => $value) {
			if($value && count($value)>0){
				if(!isset($value['numcheck'])){
					$value['numcheck'] ="";
				}
				if($id_tache)
					$res = $checklist->create(['label'=>addslashes($value['label']), 'fk_task'=>$id_tache, 'numcheck' => $value['numcheck'], 'checked'=>$value['checked']]);
				$checkmodal[$i]['label']=$value['label'];
				$checkmodal[$i]['numcheck']=$value['numcheck'];
				$i++;
			}
		}
	}

	if( $id_modal){
		$checklist_modal = $checkmodal ? serialize($checkmodal) : '';
		$result = $db->query("UPDATE ".MAIN_DB_PREFIX."digikanban_modeles SET checklist = '".$db->escape($checklist_modal)."' WHERE rowid=".$id_modal);
	}
	if($checkdeleted){
		$res = $db->query('DELETE FROM '.MAIN_DB_PREFIX.$checklist->table_element.' WHERE rowid IN ('.substr($checkdeleted, 1).')');
	}

	echo json_encode(['msg'=>$langs->trans('Notify_TASK_MODIFY').' : '.$task->ref.($task->label ? '-'.$task->label : '')]);
}

// Mise a jour etiquettes

elseif($action == 'addtags'){
	$id_colomn = GETPOST('id_colomn');
	$id_tache = GETPOST('id_tache');
	$id_modal = GETPOST('id_modal');

	$namemodal = GETPOST('namemodal');
	$html = '<div class="kanban_wrap_section">';
		if($id_modal){
			$html .= '<strong class="title_poptags">'.$langs->trans("EtiquettesModal").' "'.$namemodal.'"</strong>';
		}else
			$html .= '<strong class="title_poptags">'.$langs->trans("Etiquettes").'</strong>';

		$html .= '<input type="hidden" name="id_modal" id="id_modal" value="'.$id_modal.'">';
	$html .= '</div>';
	$html .= $digikanban_tags->selectTags($id_tache, $id_modal);
	$html .= '<div class="kanban_btn_set">';
		$html .= '<button id="savetags" class="butAction" data-colomn="'.$id_colomn.'" data-modal="'.$id_modal.'" onclick="savetags(this)">'.$langs->trans('Modify').'</button>';
		$html .= '<button id="cancelchangetags" data-id="'.$task->id.'" data-colomn="'.$id_colomn.'" data-modal="'.$id_modal.'" class="butAction" onclick="cancelchangetags(this)">'.$langs->trans('Cancel').'</button>';
	$html .= '</div>';

	echo $html;
}

elseif($action == 'gettag'){
	$id_tache = GETPOST('id_tache');
	$id = GETPOST('id');
	$id_tag = GETPOST('id_tag');
	$numtag = GETPOST('numtag');
	$tag = new digikanban_tags($db);
	$tag->fetch($id);
	
	$color = $tag->color ? $tag->color : '#00ffff';
    $clr='';
    $arr_color = colorStringToArray($color);
    if($arr_color){
        foreach ($arr_color as $key1 => $value1) {
            $clr .= $value1.', ';
        }
    }
    $bgcolor = $clr ? 'rgb('.$clr.'0.3)' : $color;

	$tags = '<table class="tags_task"><tr>';
        $tags .= '<td class="width20px">';
            $checked = $id_tag ? 'checked' : '';
            $tags .= '<input type="hidden" name="tagstask[numtag]['.$tag->id.']" class="numtag" id="numtag_'.$tag->id.'" value="'.$numtag.'">';
            $tags .= '<input class="cursorpointer_task" type="checkbox" '.$checked.' data-id="'.$tag->id.'" data-tag="'.$id_tag.'" id="checkbox'.$tag->id.'" name="tagstask[checked]['.$tag->id.'] value="'.$tag->id.'" />';
            $tags .= '<input type="hidden" id="label_tagstask_'.$tag->id.'" name="tagstask[label]['.$tag->id.']" value="'.$tag->label.'" >';
            $tags .= '<input type="hidden" id="color_tagstask_'.$tag->id.'" name="tagstask[color]['.$tag->id.']" value="'.$color.'" >';
        $tags .= '</td>';
        $tags .= '<td class="cursormove_task">';
            $tags .= '<label class="cursormove_task" for="checkbox'.$tag->id.'">';
                $tags .= '<div style="background: '.$bgcolor.'" class="tagstask">';
                    $tags .= '<span style="background:'.$color.';"></span>';
                    $tags .= '  <span class="lbl_tag">'.$tag->label.'</span>';
                $tags .= '</div>';
            $tags .= '</label>';
        $tags .= '</td>';
        $tags .= '<td class="width50px center">';
        	$tags .= '<a class="deteletetag cursorpointer_task" data-id="'.(!empty($obj->rowid) ? $obj->rowid : '').'" data-tag="'.$id_tag.'" onclick="deletetag(this)">'.img_delete();
        	$tags .= '<a class="edittag cursorpointer_task" data-tag="'.$id_tag.'" data-id="'.$tag->id.'" data-num="'.$numtag.'" onclick="edittag(this)">'.img_edit();
        $tags .= '</td>';

    $tags .= '</tr></table>';

	echo $tags;
}

elseif($action == 'savetags'){
	$id_tache = GETPOST('id_tache');
	$id_modal = GETPOST('id_modal');
	$task = new Task($db);
	$task->fetch($id_tache);
	$dt_add = GETPOST('dt_add');
	$tagstodelete = GETPOST('tagstodelete');
	$tagsdeleted = GETPOST('tagsdeleted');
	$dt_addnew = GETPOST('dt_addnew');

	$modal_tag = array();
	$res = true;
	if($dt_addnew){
		foreach ($dt_addnew as $key => $value) {
			if($value && count($value)>0){
				$tag = new digikanban_tags($db);
				$res = $tag->create(['label'=>addslashes($value['label']),'color'=>trim($value['color'])]);

				$modal_tag[$res]['fk_tag']=$res;
				$modal_tag[$res]['checked']=$value['checked'];
				$modal_tag[$res]['num']=$value['numtag'];
				
				if($res && $id_tache)
					$tag->create_tag(['fk_task'=>$id_tache, 'fk_tag'=>$res, 'numtag'=>$value['numtag'], 'checked'=>$value['checked']]);
			}
		}
	}
	if($dt_add && count($dt_add)>1){
		foreach ($dt_add as $key => $value) {
			if($value && count($value)>0 && ($value['label'] || $value['color'])){

				if($id_tache){
					$tag = new digikanban_tags($db);
					if($value['fk_tag']){
						$res_tag = $tag->update_tag($value['fk_tag'], ['numtag'=>$value['numtag'], 'checked'=>$value['checked']]);
					}else
						$res_tag = $tag->create_tag(['fk_task'=>$id_tache, 'fk_tag'=>$key, 'numtag'=>$value['numtag'], 'checked'=>$value['checked']]);
				}

				$modal_tag[$key]['fk_tag']=$key;
				$modal_tag[$key]['checked']=$value['checked'];
				$modal_tag[$key]['num']=$value['numtag'];

			}
		}
	}

	if($tagsdeleted){
		$tag = new digikanban_tags($db);
		$tag->delete_tag(substr($tagsdeleted, 1), ' AND fk_task='.$id_tache);
	}
	if($id_modal){

		$modaltags = serialize($modal_tag);
		$result = $db->query('UPDATE '.MAIN_DB_PREFIX.'digikanban_modeles SET etiquettes = "'.$db->escape($modaltags).'" WHERE rowid='.$id_modal);
		// d('UPDATE '.MAIN_DB_PREFIX.'digikanban_modeles SET etiquettes = "'.$modaltags.'" WHERE rowid='.$id_modal);
	}


	if($tagstodelete){
		$tag = new digikanban_tags($db);
		$res = $db->query('DELETE FROM '.MAIN_DB_PREFIX.$tag->table_element.' WHERE rowid IN ('.substr($tagstodelete, 1).')');
		$res = $db->query('DELETE FROM '.MAIN_DB_PREFIX.$tag->table_element_task.' WHERE fk_tag IN ('.substr($tagstodelete, 1).')');
	}
	echo json_encode(['msg'=>$langs->trans('Notify_TASK_MODIFY').' : '.$task->ref.($task->label ? '-'.$task->label : '')]);
}

elseif($action == 'updatetag'){
	$id_tache = GETPOST('id_tache');
	$id = GETPOST('id');
	$color = GETPOST('color');
	$label = GETPOST('label');
	$numtag = GETPOST('numtag');
	$id_tag = GETPOST('id_tag');

	$tag = new digikanban_tags($db);
	$res = $tag->update($id, ['color'=>$color, 'label'=>$label]);
	$tag->fetch($id);
	
	$color = $tag->color ? $tag->color : '#00ffff';
    $clr='';
    $arr_color = colorStringToArray($color);
    if($arr_color){
        foreach ($arr_color as $key1 => $value1) {
            $clr .= $value1.', ';
        }
    }
    $bgcolor = $clr ? 'rgb('.$clr.'0.3)' : $color;

	$tags = '<table class="tags_task"><tr>';
        $tags .= '<td class="width20px">';
            $checked = $id_tag ? 'checked' : '';

            $tags .= '<input type="hidden" name="tagstask[numtag]['.$tag->id.']" class="numtag" id="numtag_'.$tag->id.'" value="'.$numtag.'">';
            $tags .= '<input class="cursorpointer_task" type="checkbox" '.$checked.' data-id="'.$tag->id.'" data-tag="'.$id_tag.'" id="checkbox'.$tag->id.'" name="tagstask[checked]['.$tag->id.'] value="'.$tag->id.'" />';
            $tags .= '<input type="hidden" id="label_tagstask_'.$tag->id.'" name="tagstask[label]['.$tag->id.']" value="'.$tag->label.'" >';
            $tags .= '<input type="hidden" id="color_tagstask_'.$tag->id.'" name="tagstask[color]['.$tag->id.']" value="'.$color.'" >';
        $tags .= '</td>';

        $tags .= '<td class="cursormove_task">';
            $tags .= '<label class="cursormove_task" for="checkbox'.$tag->id.'">';
                $tags .= '<div style="background: '.$bgcolor.'" class="tagstask">';
                    $tags .= '<span style="background:'.$color.';"></span>';
                    $tags .= '  <span class="lbl_tag">'.$tag->label.'</span>';
                $tags .= '</div>';
            $tags .= '</label>';
        $tags .= '</td>';
        $tags .= '<td class="width50px center">';
        	$tags .= '<a class="deteletetag cursorpointer_task" data-tag="'.$tag->id.'" data-id="'.$tag->id.'" onclick="deletetag(this)">'.img_delete();
        	$tags .= '<a class="edittag cursorpointer_task" data-tag="'.$tag->id.'" data-id="'.$tag->id.'" data-num="'.$numtag.'" onclick="edittag(this)">'.img_edit();
        $tags .= '</td>';
    $tags .= '</tr></table>';

	echo $tags;
}

// Mise a jour commentaires

elseif($action == "loadcomment"){
	$fk_task = GETPOST('id_task');
	// $comments = array();
	// $html = '';
	// $sql = ' SELECT * FROM '.MAIN_DB_PREFIX.'digikanban_commnts';
	// $sql .= ' WHERE fk_task='.$fk_task;
	// $sql .= ' ORDER BY rowid DESC';
	// $resql = $db->query($sql);
	// if($resql){
	// 	while ($obj = $db->fetch_object($resql)) {
	// 		$us = new User($db);
	// 		$us->fetch($obj->fk_user);
	// 		$date = $db->jdate($obj->date);
	// 		$comments[]['user'] = $us->getNomUrl(-2);
	// 		$comments[]['comment'] = nl2br($obj->comment);
	// 		$comments[]['date'] = dol_print_date($date, 'dayhour');

	// 		$html .= '<div id="kanban_comment_'.$obj->rowid.'">';
	// 			$html .= '<div class="kanban_user_comment">';
	// 				$html .= $us->getNomUrl(-2);
	// 			$html .= '</div>';
	// 			$html .= '<div class="kanban_show_comment" id="kanban_comment_'.$obj->rowid.'">';
	// 				$html .= '<div class="kanban_comment_infouser">';
	// 					$html .= '<strong>'.$us->getFullname($langs).'</strong> ';
	// 					$html .= $langs->trans('Le').' '.dol_print_date($date, 'day').' '.$langs->trans('at').' '.dol_print_date($date, 'hour');
	// 				$html .= '</div>';
	// 				$html .= '<div class="kanban_comment_value">';
	// 					$html .= '<span class="show_comment">'.nl2br($obj->comment).'</span>';
	// 					$html .= '<textarea placeholder="'.$langs->trans('writecomment').'" class="update_comment comment_'.$obj->rowid.'" id="txt_comment">'.nl2br($obj->comment).'</textarea>';
	// 				$html .= '</div>';
	// 				$html .= '<div class="btn_comment">';

	// 					if($user->id == $obj->fk_user){

	// 						$html .= '<div class="update_comment">';
	// 							$html .= '<a class="butAction savecomment" data-id="'.$obj->rowid.'" onclick="updatecomment(this)">'.$langs->trans('Save').'</a>';
	// 							$html .= '<a class="butAction cancelupdatecomment" onclick="cancelupdatecomment(this)">'.$langs->trans('Cancel').'</a>';
	// 						$html .= '</div>';
	// 						$html .= '<div class="show_comment">';
	// 							$html .= '<a class="edit_comment cursorpointer_task" data-id="'.$obj->rowid.'" onclick="editcomment(this)">'.img_edit().' '.$langs->trans('Modify').'</a>';
	// 							$html .= '<a class="delete_comment cursorpointer_task" data-id="'.$obj->rowid.'" onclick="deletecomment(this)">'.img_delete().' '.$langs->trans('Delete').'</a>';
	// 						$html .= '</div>';
	// 					}


	// 				$html .= '</div>';
	// 			$html .= '</div>';
	// 		$html .= '</div>';
	// 	}
	// }
	$datacomment = $kanbancommnts->getcomments($fk_task);
	$data['html'] = $datacomment['html'];
	$data['title'] = $datacomment['htmlhover'];;
	$data['nbcomment'] = $datacomment['nbcomment'];


	echo json_encode($data);
	// echo $html;
}

elseif($action == 'savecomment'){
	$fk_task = GETPOST('id_task');
	$comment = GETPOST('comment');
	$fk_user = $user->id;
	$now = dol_now();
	$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'digikanban_commnts (fk_task, fk_user, comment, date) VALUES (';
		$sql .= $fk_task>0 ? $db->escape($fk_task) : 'NULL';
		$sql .= ','. ($fk_user>0 ? $db->escape($fk_user) : 'NULL');
		$sql .= ','. ($comment ? '"'.$db->escape($comment).'"' : 'NULL');
		$sql .= ', "'.$db->idate($now).'"';
	$sql .= ')';
	$resql = $db->query($sql);
	$result=[];
	if($resql){

		$result['msg'] = $langs->trans('COMMENT_TASK_CREATE');
		$result['typemsg'] = 'warning';
		echo json_encode($result);
	}else{
		$result['msg'] = $langs->trans('COMMENT_TASK_ECHECK_CREATE');
		$result['typemsg'] = 'error';
		echo json_encode($result);
	}
}

elseif($action == 'addcomment'){

	$fk_task = GETPOST('id_task');
	$fk_user = $user->id;
	$now = dol_now();
	$html ='';	
	$html.= '<div class="window-overlay" id="popcomments">';
		$html.= '<div id="kanban_comments">';
			$html.= '<a class="kanban_close_comments pointercursor" onclick="closecomments(this)"><span class="fas fa-times"></span></a>';
			$html.= '<div class="title_commnts">';
				$html.= '<span>'.$langs->trans('Comments').'</span>';
			$html.= '</div>';
			$html.= '<div class="kanban_body_comments">';
				$html.= '<div class="kanban_new_comment">';
					$html.= '<input type="hidden" value="'.$fk_task.'" id="id_task">';
					$html.= '<div class="kanban_user_comment">';
						$html.= $user->getNomUrl(-2);
					$html.= ' </div>';
					$html.= '<form>';
					$html.= '<div class="kanban_txt_comment">';
						$html.= '<textarea placeholder="'.$langs->trans('writecomment').'" onkeypress="keypressComment(this)" id="txt_comment"></textarea>';
						$html.= '<a class="butAction savecomment" onclick="savecomment(this)">'.$langs->trans('Save').'</a>';
						$html.= '<a class="butAction cancelcomment" onclick="cancelcomment(this)">'.$langs->trans('Cancel').'</a>';
					$html.= '</div>';
					$html.= '</form>';
				$html.= '</div>';
				$html.= '<div class="kanban_list_comments">';
				$html.= '</div>';
			$html.= '</div>';
		$html.= '</div>';
	$html.= '</div>';

	echo $html;
}

elseif($action == 'updatecomment'){
	$comment = GETPOST('comment');
	$id_comment = GETPOST('id_comment');

	$now = dol_now();
	$sql = 'UPDATE '.MAIN_DB_PREFIX.'digikanban_commnts set comment="'.$db->escape($comment).'"';
	$sql .= ' WHERE rowid ='.$id_comment;
	$resql = $db->query($sql);
	$result=[];
	if($resql){
		$result['msg'] = $langs->trans('COMMENT_TASK_MODIFY');
		$result['typemsg'] = 'warning';
		echo json_encode($result);
	}else{
		$result['msg'] = $langs->trans('COMMENT_TASK_ECHECK_UPDATE');
		$result['typemsg'] = 'error';
		echo json_encode($result);
	}
}

elseif($action == 'deletecomment'){
	$id_comment = GETPOST('id_comment');
	$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'digikanban_commnts WHERE rowid='.$id_comment;
	$resql = $db->query($sql);
	$result=[];
	if($resql){
		$result['msg'] = $langs->trans('COMMENT_TASK_DELETE');
		$result['typemsg'] = 'warning';
		echo json_encode($result);
	}else{
		$result['msg'] = $langs->trans('COMMENT_TASK_ECHECK_DELETE');
		$result['typemsg'] = 'error';
	}
	echo json_encode($result);
}

// Mise a jour modal

elseif($action == 'managemodels'){
	$id_colomn = GETPOST('id_colomn');
	$html = '';
	$html .= '<div class="progresschecklist modele_vuekanban" id="popcomments">';
			$html.= '<div>';
				$html.= '<a class="kanban_close_comments pointercursor" onclick="closemodele(this)"><span class="fas fa-times"></span></a>';
			$html.= '</div>';
			// $html.= '<br>';

		$html .= '<div class="modelsmanagementtitle"><h2>'.$langs->trans("ModelsManagement").'</h2></div>';
		// $html .= '<br>';

		$html .= '<div class="part_modelekanban">';
			$html .= '<table class="checklist_task_pop">';
				$html .= '<tr>';
					$selectmodals = $modals->getAllModals();
					$html .= '<td style="width:85%" class="list_modal">'.$selectmodals.'</td>';
						$html .= '<td class="center" style="width:15%">';
	            			$html .= '<a id="editmodal" class=" cursorpointer_task badge-status4 hidden" onclick="editmodal(this)" data-colomn="'.$id_colomn.'">'.img_edit().'</a>';
				        $html .='</td>';
				$html .= '</tr>';
			$html .= '</table>';
			$html .= '<br>';

			$html .= '<table class="checklist_task_pop">';
				$html .= '<tr>';
				 	$html .= '<td class="center" style="width:50%">';
	                    $html .= '<a id="updatetags" onclick="addtags(this)" class="buttonmodelecolor butAction hidden" data-colomn="'.$id_colomn.'"><i class="fas fa-ticket-alt"></i> '.$langs->trans('EditEtiquettes').'</a>';
	                $html .= '</td>';
	                $html .= '<td class="center">';
	                    $html .= '<a id="updatechecklist" onclick="checklisttask(this)" class="buttonmodelecolor butAction hidden" data-colomn="'.$id_colomn.'"><i class="far fa-check-square"></i> '.$langs->trans('EditChecklist').'</a>';
	                $html .= '</td>';
				$html .= '</tr>';
			$html .= '</table>';

		$html .= '</div>';

		$html .= '<br>';
		// $html .= '<div class="lign_vl"></div>';
		// $html .= '<hr>';
		// $html .= '<br>';
		$html .= '<table class="checklist_task_pop">';
			$html .= '<tr>';
			 	$html .= '<td class="center" style="width:50%">';
                    $html .= '<a id="createtaskbymodal" class="buttonmodelecreat butAction badge-status1 hidden" data-colomn="'.$id_colomn.'" onclick="createtaskbymodal(this)"><i class="fas fa-pencil-alt"></i> '.$langs->trans('UseTemplate').'</a>';
                $html .= '</td>';
                $html .= '<td class="center">';
                    $html .= '<a class="buttonmodeleutilis badge-status4 butAction" data-colomn="'.$id_colomn.'" onclick="createmodal(this)"><i class="fas fa-plus"></i> '.$langs->trans('CreateModel').'</a>';
                $html .= '</td>';
			$html .= '</tr>';
		$html .= '</table>';

	$html .= '</div>';

	echo $html;
}

elseif($action == 'createmodal' || $action == 'editmodal'){

	$task = new Task($db);

	$id_modal = GETPOST('id_modal');
	$id_colomn = GETPOST('id_colomn');

	$search_status = GETPOST('search_status');
	// d('search_status: '.$search_status);

	$debut = '';
	$fin = '';

	$date = '';
	if($date) {
		$dd = dol_getdate($date);
		if($dd){
			$debut = dol_mktime(0, 0, 0, $dd['mon'], 1, $dd['year']);
			$fin = dol_get_last_day($dd['year'], $dd['mon']);
		}
	}


	$modal = $modals->fetch($id_modal);
	$html = '<input type="hidden" name="modal" value="1" >';
	if($id_modal)
		$html .= '<input type="hidden" name="id_modal" id="id_modal" value="'.$id_modal.'" >';

	$html .= '<div class="kanban_titlemodal_task">';
		if($action == 'createmodal') {
			$html .= '<span class="kanban_title">'.$langs->trans('CreateModel').' </span>';
		} else {
			$html .= '<span class="kanban_title">'.$langs->trans('ModelsManagement').' </span>';
		}
		$html .= '<span class="kanban_time"></span>';
	$html .= '</div>';
	$contenu = contentmodaltask('createmodal', $modal, $debut, $fin, $search_projects, $search_status, 'modal');
	$html .= $contenu;

	$html .= '<div class="kanban_btn_set">';
		$html .= '<button id="savemodal" data-colomn="'.$id_colomn.'" class="butAction" onclick="savemodal(this)">'.$langs->trans('Save').'</button>';
		$html .= '<button id="cancel_task" data-id="'.$id_modal.'" data-colomn="'.$id_colomn.'" class="butAction" onclick="canceladdmodal(this)">'.$langs->trans('Cancel').'</button>';
		if($action == 'editmodal')
			$html .= '<button id="cancel_task" data-id="'.$id_modal.'" data-colomn="'.$id_colomn.'" class="butActionDelete" onclick="deletemodal(this)">'.$langs->trans('Delete').'</button>';
	$html .= '</div>';
	echo $html;
}

elseif($action == 'savemodal'){

	$tasks = new Task($db);
	$id_modal = GETPOST('id_modal');
	$modal=array();
	
	$extrafields->fetch_name_optionals_label($tasks->table_element);


	$title = GETPOST('title');
	$label = GETPOST('label');
	$fk_projet=GETPOST('fk_projet');
	$users_tasks=GETPOST('users_tasks');
    $userid   = GETPOST('userid');
    $budget   = GETPOST('budget');
    $usercontact = GETPOST('usercontact');

	$endmin=GETPOST('endmin');
	$endhour=GETPOST('endhour');
	$endday=GETPOST('endday');
	$endmonth=GETPOST('endmonth');
	$endyear=GETPOST('endyear');

	$startmin=GETPOST('startmin');
	$starthour=GETPOST('starthour');
	$startday=GETPOST('startday');
	$startmonth=GETPOST('startmonth');
	$startyear=GETPOST('startyear');

	$progress = GETPOST('progress', 'int');
	$description=GETPOST('description');



	$planned_workloadhour = (GETPOST('durehour', 'int')>0 ? GETPOST('durehour', 'int') : 0);
	$planned_workloadmin = (GETPOST('duremin', 'int')>0 ?GETPOST('duremin', 'int') : 0);
	$planned_workload = $planned_workloadhour * 3600 + $planned_workloadmin * 60;

	$error = 0;

	$projectid 	 = 0;


	$date_start = dol_mktime($starthour, $startmin, 0, $startmonth, $startday, $startyear);
	$date_end = dol_mktime($endhour, $endmin, 0, $endmonth, $endday, $endyear);
	

	$modal['title'] = $title;
	$modal['label'] = $label;
	$modal['userid'] = $userid;
	$modal['budget_amount'] = $budget;
	$modal['fk_project'] = $fk_projet;
	$modal['date_c'] = $db->idate(dol_now());
	$modal['date_start'] = $db->idate($date_start);
	$modal['date_end'] = $db->idate($date_end);
	$modal['progress'] = $progress;
	$modal['description'] = $description;
	$modal['planned_workload'] = $planned_workload;
	$modal['usercontact'] = $usercontact ? implode(',', $usercontact) : '';

	// $data = GETPOST('modal');
	if (isset($extrafields->attributes[$tasks->table_element]['label']) && is_array($extrafields->attributes[$tasks->table_element]['label'])) {
		$extralabels = $extrafields->attributes[$tasks->table_element]['label'];
	}

	if (is_array($extralabels)) {
		// Get extra fields
		foreach ($extralabels as $key => $value) {
			if (!empty($onlykey) && $onlykey != '@GETPOSTISSET' && $key != $onlykey) {
				continue;
			}

			if (!empty($onlykey) && $onlykey == '@GETPOSTISSET' && !GETPOSTISSET('options_'.$key) && (! in_array($extrafields->attributes[$tasks->table_element]['type'][$key], array('boolean', 'chkbxlst')))) {
				//when unticking boolean field, it's not set in POST
				continue;
			}

			$key_type = $extrafields->attributes[$tasks->table_element]['type'][$key];
			if ($key_type == 'separate') {
				continue;
			}

			$enabled = 1;
			if (isset($extrafields->attributes[$tasks->table_element]['enabled'][$key])) {	// 'enabled' is often a condition on module enabled or not
				$enabled = dol_eval($extrafields->attributes[$tasks->table_element]['enabled'][$key], 1, 1, '1');
			}

			$visibility = 1;
			if (isset($extrafields->attributes[$tasks->table_element]['list'][$key])) {		// 'list' is option for visibility
				$visibility = dol_eval($extrafields->attributes[$tasks->table_element]['list'][$key], 1, 1, '1');
			}

			$perms = 1;
			if (isset($extrafields->attributes[$tasks->table_element]['perms'][$key])) {
				$perms = dol_eval($extrafields->attributes[$tasks->table_element]['perms'][$key], 1, 1, '1');
			}
			if (empty($enabled)) {
				continue;
			}
			if (empty($visibility)) {
				continue;
			}
			if (empty($perms)) {
				continue;
			}

			if ($extrafields->attributes[$tasks->table_element]['required'][$key]) {	// Value is required
				// Check if functionally empty without using GETPOST (depending on the type of extrafield, a
				// technically non-empty value may be treated as empty functionally).
				// value can be alpha, int, array, etc...
				if ((!is_array($_POST["options_".$key]) && empty($_POST["options_".$key]) && $extrafields->attributes[$tasks->table_element]['type'][$key] != 'select' && $_POST["options_".$key] != '0')
					|| (!is_array($_POST["options_".$key]) && empty($_POST["options_".$key]) && $extrafields->attributes[$tasks->table_element]['type'][$key] == 'select')
					|| (!is_array($_POST["options_".$key]) && isset($_POST["options_".$key]) && $extrafields->attributes[$tasks->table_element]['type'][$key] == 'sellist' && $_POST['options_'.$key] == '0')
					|| (is_array($_POST["options_".$key]) && empty($_POST["options_".$key]))) {
					//print 'ccc'.$value.'-'.$extrafields->attributes[$tasks->table_element]['required'][$key];

					// Field is not defined. We mark this as a problem. We may fix it later if there is a default value and $todefaultifmissing is set.
					$nofillrequired++;
					$error_field_required[$key] = $langs->transnoentitiesnoconv($value);
				}
			}

			if (in_array($key_type, array('date'))) {
				// Clean parameters
				$value_key = dol_mktime(12, 0, 0, GETPOST("options_".$key."month", 'int'), GETPOST("options_".$key."day", 'int'), GETPOST("options_".$key."year", 'int'));
			} elseif (in_array($key_type, array('datetime'))) {
				// Clean parameters
				$value_key = dol_mktime(GETPOST("options_".$key."hour", 'int'), GETPOST("options_".$key."min", 'int'), GETPOST("options_".$key."sec", 'int'), GETPOST("options_".$key."month", 'int'), GETPOST("options_".$key."day", 'int'), GETPOST("options_".$key."year", 'int'), 'tzuserrel');
			} elseif (in_array($key_type, array('checkbox', 'chkbxlst'))) {
				$value_arr = GETPOST("options_".$key, 'array'); // check if an array
				if (!empty($value_arr)) {
					$value_key = implode(',', $value_arr);
				} else {
					$value_key = '';
				}
			} elseif (in_array($key_type, array('price', 'double'))) {
				$value_arr = GETPOST("options_".$key, 'alpha');
				$value_key = price2num($value_arr);
			} elseif (in_array($key_type, array('html'))) {
				$value_key = GETPOST("options_".$key, 'restricthtml');
			} elseif (in_array($key_type, array('text'))) {
				$value_key = GETPOST("options_".$key, 'alphanohtml');
			} else {
				$value_key = GETPOST("options_".$key);
				if (in_array($key_type, array('link')) && $value_key == '-1') {
					$value_key = '';
				}
			}


			$modal['array_options']["options_".$key] = $value_key;
		}
	}

	$contenu = serialize($modal);
	if($id_modal){
		$sql = "UPDATE ".MAIN_DB_PREFIX."digikanban_modeles SET contenu='".$db->escape($contenu)."' WHERE rowid=".$id_modal;
		$res_model = $db->query($sql);

	}else {
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."digikanban_modeles (contenu) VALUES ('".$db->escape($contenu)."')";
		$res_model = $db->query($sql);

		if ($res_model) {
			$id_modal = $db->last_insert_id(MAIN_DB_PREFIX."digikanban_modeles");
		}
	}
	$list_modal = $modals->getAllModals();

	echo json_encode(['id_modal' => $id_modal, 'list_modal' => $list_modal]);

}

elseif($action == 'deletemodal'){
	$id_modal = GETPOST('id_modal');
	$res = $db->query("DELETE FROM ".MAIN_DB_PREFIX."digikanban_modeles WHERE rowid=".$id_modal);
	$list_modal = $modals->getAllModals();
	echo json_encode(['result' => $res, 'list_modal' => $list_modal]);
}

elseif($action == 'createtaskbymodal'){
	
	$id_modal = GETPOST('id_modal');
	$colomn = GETPOST('id_colomn');

	$modal   = new digikanban_modeles($db);
	$newtask = new Task($db);

	$object = $modal->fetch($id_modal);

	// d($object);

	$debut = '';
	$fin = '';

	if($date) {
		$dd = dol_getdate($date);
		if($dd){
			$debut = dol_mktime(0, 0, 0, $dd['mon'], 1, $dd['year']);
			$fin = dol_get_last_day($dd['year'], $dd['mon']);
		}
	}


	$result = [];
	if($object->id){


		$defaultref = '';
		$obj = empty($conf->global->PROJECT_TASK_ADDON) ? 'mod_task_simple' : $conf->global->PROJECT_TASK_ADDON;
		if (!empty($conf->global->PROJECT_TASK_ADDON) && is_readable(DOL_DOCUMENT_ROOT."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.".php")) {
			require_once DOL_DOCUMENT_ROOT."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.'.php';
			$modTask = new $obj;
			if(!empty($object->fk_projet)){
				$project->fetch($object->fk_projet);
			}
			$project->fetch_thirdparty();
			$defaultref = $modTask->getNextValue($project->thirdparty, null);
		}

		if (is_numeric($defaultref) && $defaultref <= 0) {
			$defaultref = '';
		}


		// $date_start = '2022-01-04';
		// $date_end = '2022-01-05';


		if (!$error) {
			// $tmparray = $objtaskparent;
			// $projectid = $tmparray[0];
			// if (empty($projectid)) {
			// 	$projectid = $id; // If projectid is ''
			// }
			// $objtask_parent = $tmparray[1];
			// if (empty($objtask_parent)) {
			// 	$objtask_parent = 0; // If task_parent is ''
			// }

			$newtask = new Task($db);

			$newtask->ref = $defaultref;
			$newtask->label = $object->label;
			$newtask->budget_amount = $object->budget_amount;
			$newtask->fk_project = $object->fk_project;
			$newtask->description = $object->description;
			$newtask->planned_workload = $object->planned_workload;
			$newtask->date_c = dol_now();
			$newtask->date_start = ($debut ? $debut : $object->date_start);
			$newtask->date_end = ($fin ? $fin : $object->date_end);
			$newtask->progress = $object->progress;
			$newtask->array_options = $object->array_options;
			// unset($data['start_date']);
			// unset($data['end_date']);
			$objtaskid = $newtask->create($user);



			if ($objtaskid > 0) {
				$task= new task($db);
				$task->fetch($objtaskid);

		        // $ret = $extrafields->setOptionalsFromPost(null, $task);
		        $task->array_options['options_digikanban_colomn'] = $colomn;
		        $results =$task->insertExtraFields();

		       	if($object->userid){
					$res = $task->add_contact($object->userid, $kanban->t_typecontact, 'internal');
				}

		        if($object->usercontact){
		        	$usercontact = explode(',', $object->usercontact);
		        	if($usercontact){
		        		foreach ($usercontact as $key => $value) {
							$res = $task->add_contact($value, 'TASKCONTRIBUTOR', 'internal');
		        		}
		        	}
		        }



		     	$tagsmodal = $object->etiquettes ? unserialize($object->etiquettes) : [];
		     	if($tagsmodal && count($tagsmodal)>0){
		     		foreach ($tagsmodal as $key => $value) {

		     			$fk_tag = (is_array($value) && isset($value['fk_tag'])) ? $value['fk_tag'] : $value;
		     			$numtag = (is_array($value) && isset($value['num'])) ? $value['num'] : $key+1;
		     			$checked = (is_array($value) && isset($value['checked'])) ? $value['checked'] : 0;

						$linetiquettes .=  '('.$fk_tag.', '.$objtaskid.', '.$numtag.', '.$checked.'),';
		     		}

					$sql2 = 'INSERT INTO '.MAIN_DB_PREFIX.'digikanban_tagstask (fk_tag, fk_task, numtag, checked) VALUES';
					$linetiquettes = substr($linetiquettes, 0, -1);
					$sql2 .= $linetiquettes;
					$resql2 = $db->query($sql2);
		     	}

		     	$checklistmodal = $object->checklist ? unserialize($object->checklist) : [];
		     	if($checklistmodal && count($checklistmodal)>0){
		     		foreach ($checklistmodal as $key => $value) {

		     			$label = (is_array($value) && isset($value['label'])) ? $value['label'] : $value;
                    	$numcheck = (is_array($value) && isset($value['numcheck'])) ? $value['numcheck'] : $key+1;

						$linecheckedlist .=  '("'.$db->escape($label).'", '.$numcheck.', '.$objtaskid.'),';
		     		}
					$sql2 = 'INSERT INTO '.MAIN_DB_PREFIX.'digikanban_checklist (label, numcheck, fk_task) VALUES';
					$linecheckedlist = substr($linecheckedlist, 0, -1);
					$sql2 .= $linecheckedlist;
					$resql2 = $db->query($sql2);
		     	}


				$result['msg'] = $langs->trans('Notify_TASK_CREATE').' : '.$task->ref.($task->label ? '-'.$task->label : '');
				$result['taskid'] = $objtaskid;
		    }
			
		}
	}

	$result['projectid'] = $object->fk_project;
	$result['typemsg'] = 'warning';
			
	echo json_encode($result);
}

elseif($action == 'getalltag'){
	$selectedtags = GETPOST('selectedtags');
	$debutmonth = GETPOST('debutmonth');
	$debutyear = GETPOST('debutyear');
	$finmonth = GETPOST('finmonth');
	$finyear = GETPOST('finyear');

	$debut = dol_mktime(0, 0, 0, $debutmonth, 1, $debutyear);
	$fin = dol_get_last_day($finyear, $finmonth);
	// d($tag->table_element);
    $sql = 'SELECT t.rowid, t.label, t.color FROM '.MAIN_DB_PREFIX.'digikanban_tags as t';
    $sql .= ' WHERE 1>0'; 
	$sql .= ' AND t.rowid IN (SELECT fk_tag FROM '.MAIN_DB_PREFIX.'digikanban_tagstask WHERE checked = 1 AND fk_task IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'projet_task';
	$sql .= ($ids_of_projects!='') ? ' WHERE fk_projet IN ('.$ids_of_projects.')' : ' AND 1<0 ';

	if($debut && $fin){
		$sql .= ' AND (';
		$sql .= ' (CAST(dateo as date) BETWEEN "'.$db->idate($debut).'" AND "'.$db->idate($fin).'")';
		$sql .= ' OR ';
		$sql .= ' (CAST(datee as date) BETWEEN "'.$db->idate($debut).'" AND "'.$db->idate($fin).'")';
		// $sql .= ' OR ';
		// $sql .= ' (dateo is NULL)';
		// $sql .= ' OR ';
		// $sql .= ' (datee is NULL)';
		$sql .= ')';
		}
    $sql .= '))';
    // d($sql, 0);
	$resql = $db->query($sql);
	$data = array();
	$tag = array();
	if($resql){
        while ($obj = $db->fetch_object($resql)) {
            $data[$obj->rowid]=$obj->label;
            // $opts .= '<option value="'.$obj->rowid.'"><span class="grey_color" style="background-color: '.$obj->color.'">'.$obj->label.'</span></option>';
        }
    }
    $returned = $form->multiselectarray('search_tags', $data, $selectedtags, 0, 0, 'minwidth150 maxwidth200', 0, 0, 'onchange="submitFormWhenChange(1)"');
	echo ($returned);
}

elseif($action == 'createnewcolomn'){
	$title = GETPOST('title', 'alpha');

	$createdid = 0;

	if($title){
		$colomn = new digikanban_columns($db);
		$colomn->label = $title;
		$createdid = $colomn->create($user);
	}

	echo $createdid;
}


elseif($action == "refreshfilter") {

	$result = array();


	$search_customer		= GETPOST("search_customer", 'int');
	$search_userid			= GETPOST("search_userid", 'int');
	$search_category		= GETPOST("search_category", 'int');
	$search_status			= GETPOST("search_status", 'int');
	$search_projects		= GETPOST("search_projects", 'array');
	$search_tasktype		= GETPOST("search_tasktype", 'array');
	$search_affecteduser 	= GETPOST("search_affecteduser", 'array');
	$debutyear				= GETPOST("debutyear", 'int');
	$debutmonth				= GETPOST("debutmonth", 'int');
	$finyear				= GETPOST("finyear", 'int');
	$finmonth				= GETPOST("finmonth", 'int');

	global $selectallornone, $projectstoselectafterrefresh;

	$selectallornone = GETPOST("selectallornone", 'int');
	$projectstoselectafterrefresh = array();
	// ------------------------------------------------------------------ Project Select

	$search_debut = dol_mktime(0, 0, 0, $debutmonth, 1, $debutyear);
	$search_fin = dol_get_last_day($finyear, $finmonth);
	$returned = $object->selectProjectsdigikanbanAuthorized($search_projects, $search_category, $search_status, false, 1, $search_debut, $search_fin);
	$result['selectprojects'] = $returned;
	// d($result);

	// ------------------------------------------------------------------ Affect User Select
	$tmpprojects = ($projectstoselectafterrefresh) ? $projectstoselectafterrefresh : ([-1]);
	$sql_proj = implode(",", $tmpprojects);
	$tmptasktypes = implode('","', $search_tasktype);
	$sql_tasktypes = '"'.$tmptasktypes.'"';
	$returned = $object->selectdigikanbanUsersThatSignedAsTasksContacts($sql_proj, $sql_tasktypes, $search_affecteduser);
	
	$result['selectusers'] = $returned['html'];

	echo json_encode($result);

}