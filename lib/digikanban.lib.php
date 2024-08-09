<?php


function digikanbanTasksAdminPrepareHead($tosendinurl='')
{
    global $langs, $conf, $db, $user;
    
    $langs->load("digikanban@digikanban");
    
    $h = 0;
    $head = array();
    
    if($conf->ganttproadvanced->enabled){
        $head[$h][0] = dol_buildpath("/ganttproadvanced/index.php?mode=gantt".$tosendinurl, 1);
        $head[$h][1] = $langs->trans("viewgantt");
        $head[$h][2] = 'gantt';
        $h++;
        
        $search_scale   = GETPOST("scale", 'alpha');
        if($search_scale) {
            $resusrs = dol_set_user_param($db, $conf, $user, ['GANTTPROADVANCED_DEFAULT_ZOOM_BY' => $search_scale]);
        }
    }
    

    $head[$h][0] = dol_buildpath("/digikanban/index.php?mode=kanban".$tosendinurl, 1);
    $head[$h][1] = $langs->trans("viewkanban");
    $head[$h][2] = 'kanban';
    $h++;
  
    return $head;
}



function getTaskelement($object, $task, $status=[], $currentColomn='')
{
    global $db, $langs, $conf;

    require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
    dol_include_once('/digikanban/class/digikanban_tags.class.php');
    dol_include_once('/digikanban/class/digikanban_checklist.class.php');
    dol_include_once('/digikanban/class/digikanban_commnts.class.php');


    $form       = new Form($db);
    $kanban     = new digikanban($db);
    $digikanban_tags = new digikanban_tags($db);
    $checklist  = new digikanban_checklist($db);
    $getchecklist  = new digikanban_checklist($db);
    $kanbancommnts  = new digikanban_commnts($db);


    // $colorgantt = $conf->ganttproadvanced->enabled ? $ganttproadvanced->p_projectcolor : '';
    $colorgantt = '';
    $color = $object->color ? $object->color : ($colorgantt ? $colorgantt : '#00ffff');
    $clr='';
    $arr_color = colorStringToArray($color);
    if($arr_color){
        foreach ($arr_color as $key => $value) {
            $clr .= $value.', ';
        }
    }
    $bgcolor = $clr ? 'rgb('.$clr.'0.3)' : $color;
    $descrptask = '<span class="fas fa-align-left" ></span>';
    $descrptask .= '<u>'.$langs->trans('Description').'</u>: ';
    $descrptask .= '<strong>'.$object->description.':</strong> ';

    $debutday = $task->date_start ? date('d', $task->date_start) : '';
    $debutmonth = $task->date_start ? date('m', $task->date_start) : '';
    $debutyear = $task->date_start ? date('Y', $task->date_start) : '';

    $finday = $task->date_end ? date('d', $task->date_end) : '';
    $finmonth = $task->date_end ? date('m', $task->date_end) : '';
    $finyear = $task->date_end ? date('Y', $task->date_end) : '';

    $dd = dol_getdate(dol_now());
    $now = dol_mktime(0, 0, 0, $dd['mon'], $dd['mday'], $dd['year']);

    $tags = $digikanban_tags->fetchAllTagsOfTask($task->id);
    $checklist = $checklist->calcProgressCheckTask($task->id);
    $html = '<div class="list-card tabtask" id="task_'.$task->id.'"  data-rowid="'.$task->id.'">';


        $html .= '<input type="hidden" id="debutday" value="'.$debutday.'">';
        $html .= '<input type="hidden" id="debutmonth" value="'.$debutmonth.'">';
        $html .= '<input type="hidden" id="debutyear" value="'.$debutyear.'">';


        $html .= '<input type="hidden" id="finday" value="'.$finday.'">';
        $html .= '<input type="hidden" id="finmonth" value="'.$finmonth.'">';
        $html .= '<input type="hidden" id="finyear" value="'.$finyear.'">';

        $titletask = kanbanGetTitleOfCurrentTask($task);

        // $html .= '<div class="headertask classfortooltip" title="'.dol_escape_htmltag($titletask, 0, 1).'">';
        $html .= '<div class="headertask">';

            $titledescp ='<div>';
                $titledescp .= '<div class="kanbanpophover ">';
                $titledescp .= '<span class="gTtTitle descriptionhovertitle"><b>'.$langs->trans("Description").': </b></span>';
                $titledescp .= '<span class="gTILine">'.$task->description.'</span>';
                $titledescp .= '</div>';
            $titledescp .= '</div>';

            // $html .= '<span class="lbl_task classfortooltip" style="background: '.$bgcolor.'" title="'.dol_escape_htmltag($titletask, 0, 1).'">';
            //     $html .= '<span class="tagtask" style="background: '.$color.'"></span>';
            //     $html .= '<span class="txt" >';
            //         $html .= $task->ref;
            //     $html .= '</span>';
            // $html .= '</span>';
            // $html .= '<span class="lbl_task " style="background: '.$bgcolor.'">';
            $html .= '<div class="pull-left width80p">';
                $alltag = '';
                if($tags && count($tags)>0){
                    $i=0;
                    $alltag='';
                    $txt_tag='';
                    $div_tag='';
                    foreach ($tags as $key => $value) {

                        $color = $value['color'] ? $value['color'] : '#00ffff';
                        $clr='';
                        $arr_color = colorStringToArray($color);
                        if($arr_color){
                            foreach ($arr_color as $key1 => $value1) {
                                $clr .= $value1.', ';
                            }
                        }
                        $bgcolor = $clr ? 'rgb('.$clr.'0.3)' : $color;
                        if($i < 2){
                            $html .= '<span class="lbl_task helpcursor" style="background: '.$bgcolor.'">';
                                $html .= '<span class="tagtask" style="background: '.$color.'"></span>';
                                $html .= '<span class="txt " title="'.$value['label'].'">';
                                    $html .= $value['label'];
                                $html .= '</span>';
                            $html .= '</span>';
                        }else{
                            $txt_tag .= '<span class="lbl_task helpcursor" style="background: '.$bgcolor.'">';
                                $txt_tag .= '<span class="tagtask" style="padding: 0px 4px;border-radius: 50%;font-size: 8px;margin-right: 3px;background: '.$color.';"></span>';
                                $txt_tag .= '<span class="txt " title="'.$value['label'].'">'.$value['label'].'</span>';
                            $txt_tag .= '</span>';

                        }

                        $i++;

                    }
                    $div_tag .= '<div class="tooltip_tags">';
                        $div_tag .= $txt_tag;
                    $div_tag .= '</div>';
                    if(count($tags)>2)
                        $alltag = '<span class="classfortooltip alltags" title="'.dol_escape_htmltag($div_tag, 0, 1).'" >...</span>';
                }

                $html .= $alltag;
                $html .= '<a class="addtags" onclick="addtags(this)" data-id="'.$task->id.'" title="'.$langs->trans('addtags').'"><span class="center"><b>+</b></span></a>';

            $html .= '</div>';
            $html .= '<div class="pull-right width20p right">';
                $html .= '<a onclick="clonertask(this)" title="'.$langs->trans('ToClone').'" class="animation_kanban_hover edittask classfortooltip" data-id="'.$task->id.'" data-colomn="'.$task->array_options['options_digikanban_colomn'].'"><i class="fas fa-clone"></i></a>';
                $html .= '<a onclick="edittask(this)" data-colomn="'.$task->array_options['options_digikanban_colomn'].'" data-id="'.$task->id.'" class="animation_kanban_hover onlyedittask edittask classfortooltip">'.img_edit().'</a>';
            $html .= '</div>';
        $html .= '</div>';


        $fields_hover_popup = $kanban->fields_hover_popup ? explode(',', $kanban->fields_hover_popup) : [];
        $hover_popup = $fields_hover_popup ? array_flip($fields_hover_popup) : [];


        // $html .= '<div class="bodytask task_data_title_hover classfortooltip" title="'.dol_escape_htmltag($titletask, 0, 1).'">';
        $html .= '<div class="bodytask">';
            $html .= '<span class="lbl_projet " >';
                $html .= '<span class="lbltaskname task_data_title_hover classfortooltip" title="'.dol_escape_htmltag($titletask, 0, 1).'">';
                    $html .= img_picto('', $task->picto, 'class="pictofixedwidth"');

                    if (isset($hover_popup['Ref'])) {
                        $html .= $task->ref.($task->label ? ' - ' : '');
                    }
                $html .= '</span>';
                $html .= $task->label;
            $html .= '</span>';
        $html .= '</div>';


        $html .= '<div class="bodytask">';
            $html .= img_picto('', 'project', 'class="pictofixedwidth"');
            $html .= '<span class="lbl_projet">';
                if (isset($hover_popup['NumProjet'])) {
                    $html .= $object->ref_proj.($object->label_proj ? ' - ' : '');
                }
                $html .= $object->label_proj;
            $html .= '</span>';
        $html .= '</div>';

        $html .= '<div class="bodytask">';
            $html .= img_picto('', 'calendar', 'class="pictofixedwidth"');
            $html .= '<span class="lbl_projet">';
                $html .= $task->date_start ? dol_print_date($task->date_start, 'dayhour') : '';
                $html .= $task->date_start && $task->date_end ? ' - ' : ''; 
                $html .= $task->date_end ? dol_print_date($task->date_end, 'dayhour') : '';
            $html .= '</span>';
        $html .= '</div>';

        $html .= '<div class="footertask">';
            $html .= '<div class="pull-left">';
            
                $date_jalon = (!empty($task->array_options['options_ganttproadvanceddatejalon']) ? $task->array_options['options_ganttproadvanceddatejalon'] : '');
                // $name_color = isset($task->array_options['options_color_datejalon']) ? $task->array_options['options_color_datejalon'] : ( ($date_jalon && $db->jdate($date_jalon) <= $now ) ? 'red' : $name_color);
                $name_color = (!empty($task->array_options['options_color_datejalon']) ? $task->array_options['options_color_datejalon'] : 'grey');

                if($currentColomn == 'enattent'){
                    $name_color = (($date_jalon <= $now ) ? 'red' : $name_color);
                }

                $color = $status[$name_color];
                if($date_jalon){
                    $datejalontitle = $langs->trans("Progress").': '.($task->progress ? $task->progress : 0).' %';

                    $html .= '<span class="datetask " style="background: '.$color.'" data-id="'.$task->id.'" onclick="changeSatusdate(this)" title="'.$datejalontitle.'">';
                        $html .= '<i class="far fa-clock"></i> '.dol_print_date($date_jalon, '%d %b');
                    $html .= '</span>';

                    $tobetransferedto = 0;
                    $updatetask = false;
                    checkIfNeedToBeTransfered($task, $updatetask, $tobetransferedto, $option_taskurgent = 0);

                    $html .= '<span class="helpcursor alertwarningicon">';
                    if($tobetransferedto == 1) {
                        $html .= img_error($langs->trans('DELEY_ALERTE_DATEJALON'));
                    } else {
                        $html .= img_warning($langs->trans('urgentskanban'));
                    }
                    $html .= '</span>';
                }
                $html .= ' <a class="viewtask animation_kanban" title="'.$langs->trans('Show').'" target="_blank" href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$task->id.'"><span class="fas fa-eye"></span></a>';
                if($task->description) {
                    $html .= '<a class="descptask classfortooltip animation_kanban marginrightonly helpcursor" title="'.dol_escape_htmltag($titledescp, 0, 1).'"><span class="fas fa-align-left" ></span>';
                }
                $cl_check = $checklist['percent'] == 100 ? 'checklist100' : '';


                $hover_checklist = $getchecklist->selectCheck($task->id, 1);
                $tmptitle = $hover_checklist ? 'title="'.dol_escape_htmltag($hover_checklist, 0, 1).'"' : '';

                $html .= '<a class="classfortooltip statusdate animation_kanban '.$cl_check.'" data-id="'.$task->id.'" '.$tmptitle.' onclick="checklisttask(this)" data-title="" >';
                    $html .='<span class="far fa-check-square" fas=""></span>';
                    $html .= $checklist['total'] ? ' <span class="kanbancountcheck">'.$checklist['checked'].'/'.$checklist['total'].'</span>' : '';
                $html .='</a>';

                $nbrcomm = $object->nb_comments ? '('.$object->nb_comments.')' : '';

                $spntxtcmt = '';

                $spntxtcmt .= '<span class="kanbancountcomments">';
                if($object->nb_comments) {
                    $spntxtcmt .= ($object->nb_comments < 9) ? $object->nb_comments : '+9';
                }
                $spntxtcmt .= '</span>';

                $hover_comments = $kanbancommnts->getcomments($task->id);
                $tmptitlecmt = $hover_comments['htmlhover'] ? 'title="'.dol_escape_htmltag($hover_comments['htmlhover'], 0, 1).'"' : '';

                $html .= '<a class="classfortooltip comments animation_kanban" data-id="'.$task->id.'" '.$tmptitlecmt.' onclick="popcomments(this)"><i class="fas fa-comment"></i>'.$spntxtcmt.'</a>';
            $html .= '</div>';

            $maxnumbercontactstodisplay = $kanban->maxnumbercontactstodisplay;

            $html .= '<div class="pull-right">';


            ########################################################################################################################################## Contact Principal
            $i = 0;
            $affctedto = '';
            $tab = $task->liste_contact(-1, 'internal', 0, $kanban->t_typecontact);
            $userstatic = new User($db);
            if(isset($tab[$i]) && $tab[$i]['id']){
                $userstatic->id             = $tab[$i]['id'];
                $userstatic->lastname       = $tab[$i]['lastname'];
                $userstatic->firstname      = $tab[$i]['firstname'];
                $userstatic->email          = $tab[$i]['email'];
                $userstatic->photo          = $tab[$i]['photo'];
                $userstatic->statut         = 1;
                $affctedto_id = $userstatic->id;
                $affctedto = $userstatic->getNomUrl(-2);
            }

            ########################################################################################################################################## Contact Contributor

                $jscontact='';
                // $userstatics = new User($db);

                // $userstatics->id             = $object->fk_user_creat;
                // $userstatics->lastname       = $object->lastname;
                // $userstatics->firstname      = $object->firstname;
                // $userstatics->email          = $object->email;
                // $userstatics->photo          = $object->photo;
                // $userstatics->statut         = 1;

                // $createur = $userstatics->getNomUrl(-2);
                // // $html .= '<span> <b></b> <span class="nopadding userimg">'.$form->showphoto('userphoto', $userstatic, 0, 0, 0, 'userphoto', 'mini', 0, 1).'</span> '.dol_string_nohtmltag($userstatic->getFullName($langs, '')).'</span>';
                // $html .= '<span class="kanbancontactuser">'.$createur.'</span>';


            if($maxnumbercontactstodisplay > 0) {

                $tab = $task->liste_contact(-1, 'internal', 0, 'TASKCONTRIBUTOR');
                $num = count($tab);
                $i = 0;
                $htmlcontact = '';


                if($num){
                    while ($i < $num) {

                        if(isset($tab[$i]) && $tab[$i]['id']) {
                            $userstatic = new User($db);

                            if($affctedto_id != $tab[$i]['id'] || ($affctedto_id == $tab[$i]['id'] && $kanban->t_typecontact != 'TASKCONTRIBUTOR')){
                                $userstatic->id             = $tab[$i]['id'];
                                // $userstatic->login          = $tab[$i]['login'];
                                $userstatic->lastname       = $tab[$i]['lastname'];
                                $userstatic->firstname      = $tab[$i]['firstname'];
                                $userstatic->email          = $tab[$i]['email'];
                                $userstatic->photo          = $tab[$i]['photo'];
                                $userstatic->statut         = 1;

                                if($i<$maxnumbercontactstodisplay){
                                    $htmlcontact .= '<span class="kanbancontactuser">'.$userstatic->getNomUrl(-2).'</span>';
                                }else{
                                    // $jscontact .= '<span> <b>-</b> <span class="nopadding userimg">'.$form->showphoto('userphoto', $us, 0, 0, 0, 'userphoto', 'mini', 0, 1).'</span> '.dol_string_nohtmltag($us->getFullName($langs, '')).' '.$us->getLibStatut(4).'</span><br>';
                                    $jscontact .= '<span> <b>-</b> <span class="nopadding userimg">'.$form->showphoto('userphoto', $userstatic, 0, 0, 0, 'userphoto', 'mini', 0, 1).'</span> '.dol_string_nohtmltag($userstatic->getFullName($langs, '')).'</span><br>';
                                }

                            }

                        }
                        $i++;
                    }
                    if($jscontact) {
                        $jscontact = '<strong><u>'.$langs->trans('OtherContact').'</u>:</strong></br>'.$jscontact;
                        $htmlcontact = '<span class="otherusersicon classfortooltip helpcursor" title="'.dol_escape_htmltag($jscontact, 0, 1).'">...</span> '.$htmlcontact;
                    }
                    
                    $html .= $htmlcontact;
                }
            }

          
            $html .= $affctedto ? '<span class="kanbancontactuser affected">'.$affctedto.'</span>' : '';

            $html .= '</div>';
        $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function kanbanGetTitleOfCurrentTask($task) {

    global $db, $langs, $conf;

    $kanban = new digikanban($db);
    $extrafields    = new ExtraFields($db);
    $extrafields->fetch_name_optionals_label($task->table_element);

    $fields_hover_popup = $kanban->fields_hover_popup ? explode(',', $kanban->fields_hover_popup) : [];
    
    $hover_popup = $fields_hover_popup ? array_flip($fields_hover_popup) : [];



    $hiddenperiode = ($hover_popup && isset($hover_popup['Period'])) ? '' : 'digikanbanhidden';

    $titletask = '<div class="digikanbanpophover">';
    $titletask .= '<div class="kanbanpophover title '.$hiddenperiode.' ">';
        $titletask .= '<span class="gTILine currentdaterange">';
        // $titletask .= '<span class="gTILine">'.dol_print_date($task->date_start, "%d %B %Y");
        $titletask .= $task->date_start ? dol_print_date($task->date_start, "%d %B %Y") : '?';
        $titletask .= $task->date_end ? ' - '.dol_print_date($task->date_end, "%d %B %Y") : ' - ?';
        $titletask .= '</span>';
    $titletask .= '</div>';

    $hiddenlabel = ($hover_popup && isset($hover_popup['Label'])) ? '' : 'digikanbanhidden';

    $titletask .= '<div class="kanbanpophover'.' '.$hiddenlabel.'">';
        // $titletask .= '<span class="gTtTitle">'. img_picto('', $task->picto).' <b>'.$task->ref.' - '.$task->label.' <span class="fas fa-external-link-alt"></span>&nbsp</b> <a href="'.dol_buildpath('/projet/tasks/task.php?id='.$task->id,1).'"><i class="opacitymedium">'.dol_escape_js($langs->trans("Show")).'</i></a></span>';
        $titletask .= '<span class="gTtTitle">';

        $titletask .= img_picto('', $task->picto).' ';

        if (isset($hover_popup['Ref'])) {
            $titletask .= $task->ref.(($task->label && isset($hover_popup['Label'])) ? ' - ' : '');

        }

        $titletask .= $task->label;
        $titletask .= '</span>';

    $titletask .= '</div>';

    if($task->fk_task_parent){
        $taskparent = new Task($db);
        $taskparent->fetch($task->fk_task_parent);
        $titletask .= '<div class="kanbanpophover">';
            $titletask .= '<span class="gTtTitle"><span class="bold_">'.$langs->trans('ChildOfTask').'</span>: '.$taskparent->ref.' - '.$taskparent->label.' </span>';
            // $titletask .= '<a href="'.dol_buildpath('/projet/tasks/task.php?id='.$taskparent->id,1).'"><i class="opacitymedium">'.dol_escape_js($langs->trans("Show")).'</i></a>';
            $titletask .= '</span>';
        $titletask .= '</div>';
    }

    $duration=0;
    if($task->date_start && $task->date_end){
        $_start     = $task->date_start;
        $_end       = $task->date_end;
        $datediff   = $_end-$_start;
        $duration   = round($datediff / (60 * 60 * 24));
    }

    $hiddenduree = ($hover_popup && isset($hover_popup['Duration'])) ? '' : 'digikanbanhidden';

    $titletask .= '<div class="kanbanpophover'.' '.$hiddenduree.'">';
        $titletask .= '<span class="gTtTitle"><span class="bold_">'.$langs->trans('Duration').'</span>: '.$duration.' '.strtolower($langs->trans("Days")).' </span>';
    $titletask .= '</div>';


    $hiddenplannedwork = ($hover_popup && isset($hover_popup['PlannedWorkload'])) ? '' : 'digikanbanhidden';

    $titletask .= '<div class="kanbanpophover'.' '.$hiddenplannedwork.'">';
        $planned_workload   = ($task->planned_workload != '') ? convertSecondToTime($task->planned_workload, 'allhourmin') : '--:--';   
        $titletask .= '<span class="gTtTitle"><span class="bold_">'.$langs->trans('PlannedWorkload').'</span>: '.$planned_workload.' </span>';
    $titletask .= '</div>';



    $timespent = ($task->duration_effective) ? convertSecondToTime($task->duration_effective, 'allhourmin') : '--:--';
    $hiddentimespent = ($hover_popup && isset($hover_popup['TimeSpent'])) ? '' : 'digikanbanhidden';

    $titletask .= '<div class="kanbanpophover'.' '.$hiddentimespent.'">';
        $titletask .= '<span class="gTtTitle"><span class="bold_">'.$langs->trans("TimeSpent").'</span>: '.$timespent.'</span>';
    $titletask .= '</div>';


    $hiddenprogress = ($hover_popup && isset($hover_popup['ProgressDeclared'])) ? '' : 'digikanbanhidden';

    $titletask .= '<div class="kanbanpophover'.' '.$hiddenprogress.'">';
        $titletask .= '<span class="gTtTitle"><span class="bold_">'.$langs->trans("Progress").'</span>: '.($task->progress ? $task->progress.' %' : '').'</span>';
    $titletask .= '</div>';

    $tab = $task->liste_contact(-1, 'internal', 0, $kanban->t_typecontact);

    $userstatic = new User($db);

    $affctedto='';
    $i = 0;
    if(isset($tab[$i]) && $tab[$i]['id'] ){
        $userstatic->id             = $tab[$i]['id'];
        $userstatic->lastname       = $tab[$i]['lastname'];
        $userstatic->firstname      = $tab[$i]['firstname'];
        $userstatic->email          = $tab[$i]['email'];
        $userstatic->photo          = $tab[$i]['photo'];
        $userstatic->statut         = 1;
        $affctedto = $userstatic->getNomUrl(-1, 0);
    }
    
    $hiddenaffectedt = ($hover_popup && isset($hover_popup['AffectedTo'])) ? '' : 'digikanbanhidden';
    $titletask .= '<div class="kanbanpophover'.' '.$hiddenaffectedt.'">';
        $titletask .= '<span class="gTtTitle"><span class="bold_">'.$langs->trans("AffectedTo").'</span>:  '.$affctedto.'</span>';
    $titletask .= '</div>';


    $hiddenjalondate = ($hover_popup && isset($hover_popup['JalonDate'])) ? '' : 'digikanbanhidden';

    $titletask .= '<div class="kanbanpophover'.' '.$hiddenjalondate.'">';
        $date_jalon = (!empty($task->array_options['options_ganttproadvanceddatejalon']) ? dol_print_date($task->array_options['options_ganttproadvanceddatejalon'], 'day') : '');
        $titletask .= '<span class="gTtTitle"><span class="bold_">'.$langs->trans("ganttproadvanceddatejalon").'</span>: '.$date_jalon.'</span>';
    $titletask .= '</div>';



    $hiddendescription = ($hover_popup && isset($hover_popup['Description'])) ? '' : 'digikanbanhidden';
    // if($task->description){
        $titletask .= '<div class="kanbanpophover'.' '.$hiddendescription.'">';
            $titletask .= '<span class="gTtTitle"><span class="bold_">'.$langs->trans("Description").'</span>:  '.$task->description.'</span>';
        $titletask .= '</div>';
    // }

    $hiddenbudgets = ($hover_popup && isset($hover_popup['Budget'])) ? '' : 'digikanbanhidden';
    // if($task->budget_amount){
        $titletask .= '<div class="kanbanpophover'.' '.$hiddenbudgets.'">';
            $titletask .= '<span class="gTtTitle"><span class="bold_">'.$langs->trans("Budget").'</span>:  '.price($task->budget_amount).'</span>';
        $titletask .= '</div>';
    // }

    $coutstemepconsomme = $kanban->coutstemepconsomme($task->id);
    $hiddentotalcoutstemp = ($hover_popup && isset($hover_popup['totalcoutstemp'])) ? '' : 'digikanbanhidden';
    // if($coutstemepconsomme){
        $titletask .= '<div class="kanbanpophover'.' '.$hiddentotalcoutstemp.'">';
            $titletask .= '<span class="gTtTitle"><span class="bold_">'.$langs->trans("totalcoutstemp").'</span>:  '.price($coutstemepconsomme).'</span>';
        $titletask .= '</div>';
    // }

    if($extrafields->attributes[$task->table_element]['label']){
        foreach ($extrafields->attributes[$task->table_element]['label'] as $key => $label) {
            if(($extrafields->attributes[$task->table_element]['list'][$key] == 1 || $extrafields->attributes[$task->table_element]['list'][$key] == 3)){
                $hiddenextrafield = ($hover_popup && isset($hover_popup[$key])) ? '' : 'digikanbanhidden';
                
                if($key != 'ganttproadvancedcolor' && $key != 'ganttproadvanceddatejalon'&& $key != 'ganttproadvancedtyperelation'){
                    $titletask .= '<div class="kanbanpophover'.' '.$hiddenextrafield.'">';
                        if(!isset($task->array_options['options_'.$key])){
                            $task->array_options['options_'.$key] = "";
                        }
                        $titletask .= '<span class="gTtTitle"><span class="bold_">'.$langs->trans($label).'</span>:  '.$extrafields->showOutputField($key, $task->array_options['options_'.$key], '', $task->table_element).'</span>';
                    $titletask .= '</div>';
                }
            }
        }
    }
    $titletask .= '</div>';

    return $titletask;
}

function checkIfNeedToBeTransfered($task, &$updatetask, &$tobetransferedto, $option_taskurgent) {

    global $conf;

    $alertbeforedays = ($conf->global->DELEY_ALERTE_DATEJALON > 0) ? $conf->global->DELEY_ALERTE_DATEJALON : 0;
    
    $datejalon          = !empty($task->array_options['options_ganttproadvanceddatejalon']) ? $task->array_options['options_ganttproadvanceddatejalon'] : '';
    // $option_taskurgent  = $task->array_options['options_digikanban_taskurgent'];


    if($datejalon && $task->date_start && $task->date_end) {

        $currentday = strtotime(date('Y-m-d'));

        $coljal = $task->array_options['options_color_datejalon'];

        if((!$coljal || $coljal == 'grey') && $datejalon < $currentday && $task->progress < 100) {
            $task->array_options['options_color_datejalon'] = 'red';
            $updatetask = true;
        }
        elseif($coljal == 'red' && $datejalon >= $currentday && $task->array_options['options_digikanban_taskurgent'] !=1) {
            $task->array_options['options_color_datejalon'] = 'grey';
            $updatetask = true;
        }

        if($alertbeforedays) {
            $datediff   = $currentday-$datejalon;
            $duration   = round($datediff / (60 * 60 * 24));

            
            // if($task->progress < 100) {
            if($task->array_options['options_color_datejalon'] != 'green') {
                if($alertbeforedays && ($duration >= 0 || ($duration < 0 && abs($duration) <= $alertbeforedays)) && !$option_taskurgent && $task->progress < 100) {
                    $tobetransferedto = 1;
                }
            } elseif($task->array_options['options_color_datejalon'] == 'green') {
                $tobetransferedto = 2;
            }
            // if($task->progress == 100 || ($alertbeforedays && ($duration < 0 && abs($duration) > $alertbeforedays) && $option_taskurgent)) {
            // }
        }

    }

}

function contentmodaltask($action, $task, $debut, $fin, $search_projects, $search_status, $type='task')
{
    global $conf, $db, $langs, $user;

    require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
    require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
    dol_include_once('/digikanban/class/digikanban.class.php');

    $form = new Form($db);

    $extrafields = new ExtraFields($db);
    $formother = new FormOther($db);
    $kanban = new digikanban($db);

    $tasks = new Task($db);

    $fields_edit_popup = $kanban->fields_edit_popup ? explode(',', $kanban->fields_edit_popup) : [];
    
    $edit_popup = $fields_edit_popup ? array_flip($fields_edit_popup) : [];

    $html = '';
    $html .= '<div class="kanban_bodymodal_task">';

        if($type == 'modal'){
            $html .= '<div class="kanban_wrap_section">';
                $html .= '<div class="kanban_title_section">';
                    $html .= '<label>'.$langs->trans('titlemodal').': </label>';
                $html .= '</div>';
                $html .= '<div class="kanban_value_section">';
                    $html .= '<input style="width: 100%" name="title" id="title" value="'.(isset($task->title) ? $task->title : '').'">';
                $html .= '</div>';
            $html .= '</div>';
        }

        $hiddenlabel = ($edit_popup && isset($edit_popup['Label'])) ? '' : 'digikanbanhidden';

        $html .= '<div class="kanban_wrap_section'.' '.$hiddenlabel.'">';
            $html .= '<div class="kanban_title_section">';
                $html .= '<label>'.$langs->trans('Label').': </label>';
            $html .= '</div>';
            $html .= '<div class="kanban_value_section">';
                $html .= '<input style="width: 100%" name="label" id="label" value="'.(isset($task->label) ? $task->label : '').'">';
            $html .= '</div>';
        $html .= '</div>';

        $hiddenproject = ($edit_popup && isset($edit_popup['Project'])) ? '' : 'digikanbanhidden';

        if($action == 'addtask' || $type == 'modal'){
            $fk_project = ($type == 'modal') ? ((isset($task->fk_project) && $task->fk_project) ? $task->fk_project : $search_projects) : '';
            $html .= '<div class="kanban_wrap_section'.' '.$hiddenproject.'">';
                $html .= '<div class="kanban_title_section">';
                    $html .= '<label>'.$langs->trans('Project').': </label>';
                $html .= '</div>';
                $html .= '<div class="kanban_value_section">';
                    $html .= img_picto($langs->trans('Projects'), 'project', '');
                    $html .= $kanban->selectProjectsdigikanbanAuthorized($fk_project, '', $search_status, false, 0, $start="", $end="", $_shownumbertotal = false);
                $html .= '</div>';
            $html .= '</div>';
        }


        $hiddenaffectedto = ($edit_popup && isset($edit_popup['AffectedTo'])) ? '' : 'digikanbanhidden';

        // if($action == 'addtask' || $action == 'clonertask' || $type == 'modal'){
        $html .= '<div class="kanban_wrap_section'.' '.$hiddenaffectedto.'">';
            $html .= '<div class="kanban_title_section">';
                $html .= '<label>'.$langs->trans('AffectedTo').': </label>';
            $html .= '</div>';
            $html .= '<div class="kanban_value_section">';
                if(isset($task->id) && $task->id && $type == 'task'){
                    $tab = $task->liste_contact(-1, 'internal', 0, $kanban->t_typecontact);
                    $user_id = isset($tab[0]) ? $tab[0]['id'] : 0;

                }
                $html .= $form->select_dolusers((!empty($user_id) ? $user_id : (!$task->id ? $user->id : [-1])), 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'minwidth300 maxwidth300', 1);
            $html .= '</div>';
        $html .= '</div>';

        $hiddencontact_tache = ($edit_popup && isset($edit_popup['contact_tache'])) ? '' : 'digikanbanhidden';

        $html .= '<div class="kanban_wrap_section'.' '.$hiddencontact_tache.'">';
            $html .= '<div class="kanban_title_section">';
                $html .= '<label>'.$langs->trans('contact_tache').': </label>';
            $html .= '</div>';
            $html .= '<div class="kanban_value_section">';
                $usercontact[] ='';

                if($type == 'task' && ($action == 'edittask' || $action == 'clonertask')){
                    $tab = $task->liste_contact(-1, 'internal', 0, 'TASKCONTRIBUTOR');
                    // d($tab);
                    $num = count($tab);
                    $i = 0;
                    while ($i < $num) {
                        $usercontact[]= $tab[$i]['id'];
                        $i++;
                    }
                }elseif($type == 'modal'){
                    $usercontact = !empty($task->usercontact) ? explode(',', $task->usercontact) : '';
                }
            
                $html .= $form->select_dolusers($usercontact, 'usercontact', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'minwidth300 maxwidth300',1,'',true);
            $html .= '</div>';
        $html .= '</div>';

        $hiddenperiod = ($edit_popup && isset($edit_popup['Period'])) ? '' : 'digikanbanhidden';

        $html .= '<div class="kanban_wrap_section datestartend'.' '.$hiddenperiod.'">';
            $html .= '<div class="kanban_title_section">';
                $html .= '<label>'.$langs->trans('Period').': </label>';
            $html .= '</div>';
            $html .= '<div class="kanban_value_section">';
                // $html .= $langs->trans('From').' <input type="text" class="hasDatepicker">';
                // $html .= ' '.$langs->trans('to').' <input type="text" class="hasDatepicker">';
                $html .= $langs->trans('From').' '.$form->selectDate((!empty($task->date_start) ? $task->date_start : $debut), 'date_start', 1, 1, 0);
                $html .= '<br>'.$langs->trans('to').' '.$form->selectDate((!empty($task->date_end) ? $task->date_end : $fin), 'date_end', -1, -1, 0);

            $html .= '</div>';
        $html .= '</div>';

        $hiddenplannedWorkload = ($edit_popup && isset($edit_popup['PlannedWorkload'])) ? '' : 'digikanbanhidden';

        $html .= '<div class="kanban_wrap_section'.' '.$hiddenplannedWorkload.'">';
            $html .= '<div class="kanban_title_section">';
                $html .= '<label>'.$langs->trans('PlannedWorkload').': </label>';
            $html .= '</div>';
            $html .= '<div class="kanban_value_section">';
                $html .= $kanban->select_duration_kanabn('planned_workload', (!empty($task->planned_workload) ? $task->planned_workload : ''), 0, 'text');

            $html .= '</div>';
        $html .= '</div>';

        $hiddenprogressdeclared = ($edit_popup && isset($edit_popup['ProgressDeclared'])) ? '' : 'digikanbanhidden';

        $html .= '<div class="kanban_wrap_section'.' '.$hiddenprogressdeclared.'">';
            $html .= '<div class="kanban_title_section">';
                $html .= '<label>'.$langs->trans('ProgressDeclared').': </label>';
            $html .= '</div>';
            $html .= '<div class="kanban_value_section">';
                $html .= $formother->select_percent((!empty($task->progress) ? $task->progress : ''), 'progress', 0, 1, 0, 100);
            $html .= '</div>';
        $html .= '</div>';

        $hiddendescription = ($edit_popup && isset($edit_popup['Description'])) ? '' : 'digikanbanhidden';

        $html .= '<div class="kanban_wrap_section'.' '.$hiddendescription.'">';
            $html .= '<div class="kanban_title_section">';
                $html .= '<label>'.$langs->trans('Description').': </label>';
            $html .= '</div>';
            $html .= '<div class="kanban_value_section">';
                $html .= '<textarea class="descptask" name="description" rows="5" spellcheck="false">'.(!empty($task->description) ? $task->description : '').'</textarea>';
            $html .= '</div>';
        $html .= '</div>';

        $hiddenbudget = ($edit_popup && isset($edit_popup['Budget'])) ? '' : 'digikanbanhidden';

        $html .= '<div class="kanban_wrap_section'.' '.$hiddenbudget.'">';
            $html .= '<div class="kanban_title_section">';
                $html .= '<label>'.$langs->trans('Budget').': </label>';
            $html .= '</div>';
            $html .= '<div class="kanban_value_section">';
                $html .= '<input style="width: 50%" name="budget" value="'.(!empty($task->budget_amount) ? price2num($task->budget_amount) : '').'">';
            $html .= '</div>';
        $html .= '</div>';

        $extrafields->fetch_name_optionals_label($tasks->table_element);
        // d($extrafields, false);


        if($extrafields->attributes[$tasks->table_element]['label']){
            foreach ($extrafields->attributes[$tasks->table_element]['label'] as $key => $value) {

                $hiddenextrafields = ($edit_popup && isset($edit_popup[$key])) ? '' : 'digikanbanhidden';

                $visibi = $extrafields->attributes[$tasks->table_element]['list'][$key];
                if(!$visibi) continue;

                if($key == 'ganttproadvancedcolor'){
                    $html .= '<div class="kanban_wrap_section'.' '.$hiddenextrafields.'">';
                        $html .= '<div class="kanban_title_section">';
                            $html .= '<label>'.$langs->trans($value).': </label>';
                        $html .= '</div>';
                        $html .= '<div class="kanban_value_section">';
                            $html .= '<input type="color" name="options_ganttproadvancedcolor" value="'.(!empty($task->array_options['options_ganttproadvancedcolor']) ? $task->array_options['options_ganttproadvancedcolor'] : $kanban->defaultcolortask).'" id="options_ganttproadvancedcolor">';
                        $html .= '</div>';
                    $html .= '</div>';
                }elseif($key != 'color_datejalon' && $key != 'ganttproadvancedtyperelation' && $key != 'digikanban_colomn'){
                    $html .= '<div class="kanban_wrap_section'.' '.$hiddenextrafields.'">';
                        $html .= '<div class="kanban_title_section">';
                            $html .= '<label>'.$langs->trans($value).': </label>';
                        $html .= '</div>';
                        $html .= '<div class="kanban_value_section">';
                            $html .= $extrafields->showInputField($key, (!empty($task->array_options["options_".$key]) ? $task->array_options["options_".$key] : ''), '', $keysuffix = '', '', 0, '', $tasks->table_element);
                        $html .= '</div>';
                    $html .= '</div>';

                }
            }   

        }
    $html .= '</div>';
    return $html;
}

function selectCheckModal($id_modal)
{
    global $langs, $db;

    $tags='';
    $html='';
    $data = array();
    $htmlname = 'digikanban_checklist';

    $sql = 'SELECT o.checklist FROM '.MAIN_DB_PREFIX.'digikanban_modeles as o';
    $sql .= ' WHERE o.rowid = '.$id_modal;
    $resql = $db->query($sql);
    $html ='<ul class="fexwidthchecklist">';
    $arr_check = array();

    $checked = '';
    
    if($resql){
        while ($obj = $db->fetch_object($resql)) {
            
            $checklist = unserialize($obj->checklist);
            if($checklist && count($checklist)>0){
                foreach ($checklist as $key => $check) {
                    $id = $key+1;
                    $label = (is_array($check) && isset($check['label'])) ? $check['label'] : $check;
                    $numcheck = (is_array($check) && isset($check['numcheck'])) ? $check['numcheck'] : $id;

                    $tag = '<li class="checklist" id="check_'.$id.'">';
                        $tag .= '<table class="checklist_task_pop"><tr>';
                            $tag .= '<td class="width30px center" >';    
                                $tag .= '<span class="far fa-square"> </span>';
                                $tag .= '<input type="hidden" class="numcheck" id="numcheck_'.$id.'" data-id="'.$id.'" name="checklist[numcheck]['.$id.']" value="'.$numcheck.'" />';

                                $tag .= '<input type="hidden" class="cursorpointer_task check_list" '.$checked.' data-id="'.$id.'" id="checkbox'.$id.'" onchange="calcProgress(this)" name="checklist[checked]['.$id.'] value="'.$id.'" />';
                            $tag .= '</td>';
                            $tag .= '<td class="cursormove_task">';    
                                $tag .= '<input type="hidden" id="label_check_'.$id.'" name="checklist['.$id.']" value="'.$label.'" >';
                                $tag .= '<label class="cursormove_task">'.$label.'</label>';
                            $tag .= '</td>';
                            $tag .= '<td class="width50px center">';
                                $tag .= '<a class="deletecheck cursorpointer_task" data-id="'.$id.'" data-label="'.$label.'" data-modal="'.$id_modal.'" onclick="deletecheck(this)">'.img_delete().'</a>';
                                $tag .= '<a class="editcheck cursorpointer_task" data-id="'.$id.'" data-label="'.$label.'" data-modal="'.$id_modal.'" onclick="editcheck(this)">'.img_edit().'</a>';
                            $tag .= '</td>';
                        $tag .= '</tr></table>';
                    $tag .= '</li>';
                    
                    $arr_check[$numcheck] = $tag;
                }
            }
            ksort($arr_check, SORT_REGULAR);
        }
    }
    $html .='</ul>';

    $html = '<div class="multiselectcheckboxtags">';
        $html .= '<input type="hidden" name="checkdeleted" class="checkdeleted">';
        $html .= '<ul class="list_checklist">';
        foreach ($arr_check as $key => $ttag) {
                $html .= $ttag;
            }
        $html .= '</ul>';
    $html .= '</div>';

    $html .= '<div class="createtag" >';
        $html .= '<td><a class="cursorpointer_task" data-modal="'.$id_modal.'" onclick="createcheck(this)"><span class="fas fa-plus"></span> '.$langs->trans('createcheck').'</a></td>';
    $html .= '</div>';

    return $html;
}


function digikanbanPrepareAdminHead($active, $linkback, $picto)
{
    global $langs, $conf, $db, $user;

    $langs->load('digikanban@digikanban');
    $h = 0;
    $head = array();
    $assets = new digikanban($db);

    $head[$h][0] = dol_buildpath("/digikanban/admin/admin.php", 1);
    $head[$h][1] = $langs->trans("General");
    $head[$h][2] = 'general';
    $h++;

    $head[$h][0] = dol_buildpath("/digikanban/columns/list.php?mainmenu=project", 1);
    $head[$h][1] = $langs->trans('columns');
    $head[$h][2] = 'columns';
    $h++;



    print load_fiche_titre($langs->trans("config_vue_kanban"), $linkback, $picto);

    dol_fiche_head($head, $active, $langs->trans('config_vue_kanban'), -1,  'list');

}