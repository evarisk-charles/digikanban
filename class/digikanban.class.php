<?php 
dol_include_once('/core/lib/admin.lib.php');
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

class digikanban
{

    const STATUS_UNLASTSEARCH = 1;
    const STATUS_LASTSEARCH = 2;
    const STATUS_ALL = 3;

    public $columnstoshow;
    public $status_date = array();

    public $arr_fields_edit_popup;
    public $arr_fields_hover_popup;

    public function __construct($db)
    {   
        global $langs, $conf;

        $langsLoad=array('projects', 'users', 'companies', 'other', 'holiday');
        $langs->loadLangs($langsLoad);

        $this->db = $db;
        $this->columnstoshow = [
            // 'text'          => $langs->trans('Tasks'),
            'duration'      => $langs->trans('Duration').' ('.strtolower(substr($langs->trans("Day"),0,1)).')',
            'start_date'    => $langs->trans('DateDebCP'),
            'end_date'      => $langs->trans('DateFinCP'),
            'progress'      => $langs->trans('Progress').' (%)',
        ];
        
        $this->status_date = array('grey'=> '#a3a7b1', 'red'=> '#ef5858', 'green'=> '#88CC2E');

        $this->arr_fields_edit_popup = array('Ref','Label','NumProjet','Project','AffectedTo','contact_tache','Period','PlannedWorkload','ProgressDeclared','Description','Budget');
        $this->arr_fields_hover_popup = array('Ref','Label','NumProjet','AffectedTo','Period','PlannedWorkload','ProgressDeclared','Description','Duration','Budget','TimeSpent','totalcoutstemp' ,'JalonDate');


        $objtask = new Task($this->db);

        $this->showallprojet = isset($conf->global->DIGIKANBAN_SHOW_ALL_PROJETS) ? $conf->global->DIGIKANBAN_SHOW_ALL_PROJETS : 0;
        $this->showtaskinfirstcolomn = isset($conf->global->DIGIKANBAN_SHOW_TASKNOSTATUS_IN_FIRSTCOLOMN) ? $conf->global->DIGIKANBAN_SHOW_TASKNOSTATUS_IN_FIRSTCOLOMN : 1;
        $this->t_typecontact = isset($conf->global->KANBAN_TYPE_CONTACT_TO_BASE_ON) ? $conf->global->KANBAN_TYPE_CONTACT_TO_BASE_ON : 0;
        $this->searchbycontacttype = isset($conf->global->KANBAN_SEARCH_BY_CONTACT_TYPE) ? $conf->global->KANBAN_SEARCH_BY_CONTACT_TYPE : '';
        $this->maxnumbercontactstodisplay = isset($conf->global->KANBAN_MAXIMUM_NUMBER_OF_CONTACTS_TO_DISPLAY) ? (int) $conf->global->KANBAN_MAXIMUM_NUMBER_OF_CONTACTS_TO_DISPLAY : 1;
        $this->hidetaskisprogress100 = isset($conf->global->DIGIKANBAN_HIDE_TASKISPROGRESS100) ? $conf->global->DIGIKANBAN_HIDE_TASKISPROGRESS100 : '';

        $this->fields_edit_popup = isset($conf->global->DIGIKANBAN_FIELDS_TO_SHOW_IN_EDIT_POPUP) ? $conf->global->DIGIKANBAN_FIELDS_TO_SHOW_IN_EDIT_POPUP : '';
        $this->fields_hover_popup = isset($conf->global->DIGIKANBAN_FIELDS_TO_SHOW_IN_HOVER_POPUP) ? $conf->global->DIGIKANBAN_FIELDS_TO_SHOW_IN_HOVER_POPUP : '';

        
        // $this->coloredbyuser = ($this->t_colortaskbyuser && $this->t_typecontact > 0) ? 1 : 0;
        $this->coloredbyuser = 0;

        $this->nametypecontact = $langs->trans("AffectedTo");

        $this->defaultcolortask = '#16a085';
        // $this->colorgristask = '#dcdcdc';
        $this->colorgristask = '#cbcbcb';

        $this->_data_affecteduser = '';
        $this->_data_typecontact = array();

        if($this->coloredbyuser) {
            $this->_data_affecteduser = $this->selectForaffecteduserection();
            $this->_data_typecontact = $objtask->liste_type_contact('internal', 'position', 0, 1);
            // d($_data_typecontact);
        }


    }
    
    public function SelectColumnsToShow($selected='')
    {
        global $langs, $conf, $form;

        $default_columns = ($conf->global->GANTTPROADVANCED_COLUMS_TO_SHOW != -1) ? array('duration','start_date','end_date') : [];
        $selected_columns = $conf->global->GANTTPROADVANCED_COLUMS_TO_SHOW ? json_decode($conf->global->GANTTPROADVANCED_COLUMS_TO_SHOW) : $default_columns;
        // d($selected_columns);

        $html = '<select class="selected_columns minwidth500imp " name="selected_columns[]" multiple onchange="HideShowColumns(this)">';
        // $html .= '<option value="text" disabled selected>'.$langs->trans('Tasks').'</option>';
        foreach ($this->columnstoshow as $key => $name) {
            $slctd = (is_array($selected_columns) && in_array($key, $selected_columns)) ? 'selected' : '';
            $html .= '<option value="'.$key.'" '.$slctd.'>'.$name.'</option>';
        }
        $html .= '</select>';
        
        return $html;
    }

    public function Columnsdigikanban($selected='')
    {
        global $langs, $conf, $form;

        $data = array();

        $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'digikanban_columns';
        $sql .= ' WHERE entity='.$conf->entity;
        $resql = $this->db->query($sql);

        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $data[$obj->rowid] = $obj->label;
            }
        }

        return $data;
    }
    
    public function SelectFilterCategory($selected='')
    {
        global $langs, $form;
        
        $categoryArray = $form->select_all_categories(Categorie::TYPE_PROJECT, "", "", 64, 0, 1);

        $html = '<select class="search_category minwidth75imp maxwidth150" name="search_category" onchange="this.form.submit()">';
        $html .= '<option value="-1">&nbsp;&nbsp;</option>';

        if($categoryArray) {
            foreach ($categoryArray as $idcateg => $labelcateg) {
                $html .= '<option value="'.$idcateg.'"' ;
                if($selected == $idcateg) $html .= 'selected';
                $html .= '>';
                $html .= dol_trunc($labelcateg,100);
                $html .= '</option>';
            }
        }
        
        $html .= '</select>';
        
        return $html;
    }

    public function getTasksOfProjectPdf(&$sortedtasks, &$indextasks, &$inc, $parent, &$lines, &$level, $var, $showproject, $projectsListId = '', $addordertick = 0, $projectidfortotallink = 0, $filterprogresscalc = '', $showbilltime = 0, $arrayfields = array())
    {
        global $user, $langs, $conf, $db, $hookmanager;
        global $projectstatic, $taskstatic, $extrafields;

        $lastprojectid = 0;

        $projectsArrayId = explode(',', $projectsListId);
        // if ($filterprogresscalc !== '') {
        //     foreach ($lines as $key => $line) {
        //         if (!empty($line->planned_workload) && !empty($line->duration)) {
        //             $filterprogresscalc = str_replace(' = ', ' == ', $filterprogresscalc);
        //             if (!eval($filterprogresscalc)) {
        //                 unset($lines[$key]);
        //             }
        //         }
        //     }
        //     $lines = array_values($lines);
        // }
        $numlines = count($lines);

        $colorproject   = $this->p_projectcolor;
        $colordefaulttask = $this->coloredbyuser ? $this->colorgristask : $this->defaultcolortask;

        $format        = GETPOST('format', 'alpha');

        $caradays = '';

        for ($i = 0; $i < $numlines; $i++) {
            if ($parent == 0 && $level >= 0) {
                $level = 0; // if $level = -1, we dont' use sublevel recursion, we show all lines
            }

            if ($lines[$i]->fk_parent == $parent || $level < 0) {       // if $level = -1, we dont' use sublevel recursion, we show all lines
                // Show task line.
                $showline = 1;
                $showlineingray = 0;

                if ($showline) {
                    // Break on a new project
                    if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid) {
                        $var = !$var;
                        $lastprojectid = $lines[$i]->fk_project;
                    }

                    $indextasks++;

                    $projectstatic->id = $lines[$i]->fk_project;
                    $projectstatic->ref = $lines[$i]->projectref;
                    $projectstatic->public = $lines[$i]->public;
                    $projectstatic->title = $lines[$i]->projectlabel;
                    $projectstatic->usage_bill_time = $lines[$i]->usage_bill_time;
                    $projectstatic->status = $lines[$i]->projectstatus;

                    $taskstatic->id = $lines[$i]->id;
                    $taskstatic->ref = $lines[$i]->ref;
                    $taskstatic->label = '';
                    $taskstatic->projectstatus = $lines[$i]->projectstatus;
                    $taskstatic->progress = $lines[$i]->progress;
                    $taskstatic->fk_statut = $lines[$i]->status;
                    $taskstatic->date_start = $lines[$i]->date_start;
                    $taskstatic->date_end = $lines[$i]->date_end;
                    $taskstatic->datee = $lines[$i]->date_end; // deprecated
                    $taskstatic->planned_workload = $lines[$i]->planned_workload;
                    $taskstatic->duration_effective = $lines[$i]->duration;
                    $taskstatic->budget_amount = $lines[$i]->budget_amount;


                    // --------------------------------------------------------------------------------------------------------------------------------------

                    $obj = $lines[$i];
                    $noformatstart = (int) ($obj->date_start ? ($obj->date_start) : ($projectstatic->date_start));
                    $noformatend = (int) ($obj->date_end ? ($obj->date_end) : $obj->date_start);
                    $dstart = $noformatstart; $dend = $noformatend;
                    $dend = ($dend ? $dend : $dstart);

                    
                    $datediff = $noformatend - $noformatstart;
                    $duration = round($datediff / (60 * 60 * 24));
                    if($duration <= 1)  $Duration = '1'.$caradays;
                    else  $Duration = $duration . $caradays;

                    $nameinpdf = '';

                    $totcar = 45;
                    if($format == 'A3') $totcar = 54;

                    $label = dol_htmlentitiesbr_decode($obj->label);
                    if(strlen($label) > $totcar) $label = substr($label, 0, $totcar).'...';
                    // if($level == 0) $nameinpdf .= '-';
                    for ($k = 0; $k < $level; $k++) {
                        if($k > 4) break;
                        $nameinpdf .= '&nbsp;';
                    }
                    $nameinpdf .= dol_htmlentities($label);

                    $arr = array();
                    $arr['task_name_pdf'] = $nameinpdf;
                    $arr['task_name'] = $obj->ref.' - '.$obj->label;
                    $arr['task_start_date'] = $dstart;
                    $arr['task_end_date'] = $dend;
                    $arr['task_duration'] = $Duration;
                    $arr['task_percent'] = ($obj->progress ? number_format($obj->progress,0) : 0);
                    $arr['task_ref'] = $obj->ref;

                    $color = (($obj->options_digikanbancolor) ? $obj->options_digikanbancolor : $colordefaulttask);

                    if($this->coloredbyuser) {
                    }

                    $arr['task_color'] = $color;
                   
                    $sortedtasks[$indextasks] = $arr;
                    // --------------------------------------------------------------------------------------------------------------------------------------




                    if (!$showlineingray) {
                        $inc++;
                    }

                    if ($level >= 0) { // Call sublevels
                        $level++;
                        if ($lines[$i]->id) {
                            $this->getTasksOfProjectPdf($sortedtasks, $indextasks, $inc, $lines[$i]->id, $lines, $level, $var, $showproject, $projectsListId, $addordertick, $projectidfortotallink, $filterprogresscalc, $showbilltime, $arrayfields);
                        }
                        $level--;
                    }

                }
            } else {
                //$level--;
            }
        }

        return $sortedtasks;
    }

    public function selectMultipleTypeContact($selected = [], $htmlname = 'type', $source = 'internal', $sortorder = 'position', $showempty = 0, $multiple = false)
    {
        global $user, $langs;

        $out = '';

        $objtask = new Task($this->db);

        if (is_object($objtask) && method_exists($objtask, 'liste_type_contact')) {
            $lesTypes = $objtask->liste_type_contact($source, $sortorder, 1, 1);

            // $out .= '<select class="flat width150 maxwidth150 valignmiddle'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'[]" id="'.$htmlname.'" multiple onchange="this.form.submit()">';

            $morecss = '';
            
            $out .= '<select class="flat width100 maxwidth100 valignmiddle'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.''.($multiple ? '[]' : '').'" id="'.$htmlname.'" '. ($multiple ? 'multiple' : '') .'>';
            if ($showempty) {
                $out .= '<option value="0">&nbsp;</option>';
            }
            foreach ($lesTypes as $key => $value) {

                $out .= '<option value="'.$key.'"';

                if($multiple && is_array($selected) && in_array($key, $selected) || (!$multiple && $key == $selected)) {
                    $out .= 'selected';  
                } 

                $out .= '>'.$value.'</option>';

            }
            $out .= "</select>";

            // if ($user->admin) {
            //     $out .= ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
            // }

            // $out .= ajax_combobox($htmlname);

            $out .= "\n";
        }
        if (empty($output)) {
            return $out;
        } else {
            print $out;
        }
    }

    
    public function selectForaffecteduserection()
    {
        global $conf, $user, $langs;

        $_data_affecteduser = '';

        $sql = "SELECT DISTINCT u.rowid, u.lastname as lastname, u.firstname, u.statut as status, u.login, u.admin, u.entity, u.photo";
        $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
        if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entity as e ON e.rowid = u.entity";
            if ($force_entity) {
                $sql .= " WHERE u.entity IN (0, ".$this->db->sanitize($force_entity).")";
            } else {
                $sql .= " WHERE u.entity IS NOT NULL";
            }
        } else {
            if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
                $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ug";
                $sql .= " ON ug.fk_user = u.rowid";
                $sql .= " WHERE ug.entity = ".$conf->entity;
            } else {
                $sql .= " WHERE u.entity IN (0, ".$conf->entity.")";
            }
        }
        if (!empty($user->socid)) {
            $sql .= " AND u.fk_soc = ".((int) $user->socid);
        }

        $sql .= " AND u.statut <> 0";

        $resql = $this->db->query($sql);
        if ($resql)
        {
            while ($obj = $this->db->fetch_object($resql))
            {
                $data = array();
                $data['key'] = $obj->rowid;
                $data['label'] = $obj->lastname.' '.$obj->firstname;

                $_data_affecteduser  .= json_encode($data).',';
            }
        }

        return $_data_affecteduser;
    }
    
    public function selectdigikanbanUsersThatSignedAsTasksContacts($sql_proj = '', $sql_tasktypes = '', $search_affecteduser = array())
    {
        global $conf, $langs;

        $returned = array();
        $arr_users = array();

        $sql = 'SELECT DISTINCT(elem.fk_socpeople) ';
        $sql .= ', u.rowid, u.lastname as lastname, u.firstname, u.statut as status, u.login, u.admin, u.entity, u.photo';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'element_contact as elem';
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON (u.rowid = elem.fk_socpeople) ";
        $sql .= ' WHERE fk_c_type_contact IN (';
            $sql .= ' SELECT rowid FROM '.MAIN_DB_PREFIX.'c_type_contact WHERE element = "project_task"';
            if($sql_tasktypes && $sql_tasktypes != '""') {
                $sql .= '  AND code IN ('.$sql_tasktypes.')';
            }
        $sql .= ')';
        
        if($sql_proj) {
            $sql .= ' AND element_id IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'projet_task WHERE fk_projet IN ('.$sql_proj.'))';
        }
        // if($sql_tasktypes) {
        //     $sql .= ' AND fk_c_type_contact IN ('.$sql_tasktypes.')';
        // }

        // echo $sql;

        $sql .= " AND u.statut <> 0";

        $resql = $this->db->query($sql);

        $userstatic = new User($this->db);
        $fullNameMode = 0;
        if (empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)) {
            $fullNameMode = 1; //Firstname+lastname
        }

        // $html = '<select class="select_affecteduser minwidth75imp width300 maxwidth300" name="search_affecteduser[]" multiple onchange="this.form.submit()">';
        $html = '<select class="select_affecteduser minwidth75imp width150 maxwidth150" name="search_affecteduser[]" multiple onchange="submitFormWhenChange()">';
        if ($resql)
        {
            while ($obj = $this->db->fetch_object($resql))
            {
                $arr_users[$obj->fk_socpeople] = $obj->fk_socpeople;

                $userstatic->id = $obj->rowid;
                $userstatic->lastname = $obj->lastname;
                $userstatic->firstname = $obj->firstname;
                $userstatic->photo = $obj->photo;
                $userstatic->statut = $obj->status;
                $userstatic->entity = $obj->entity;
                $userstatic->admin = $obj->admin;

                $html .= '<option value="'.$obj->fk_socpeople.'"' ;
                if(in_array($obj->fk_socpeople, $search_affecteduser)) $html .= 'selected';
                $html .= '>';

                $html .= $userstatic->getFullName($langs, $fullNameMode, -1, $maxlength = 0);
                if (empty($obj->firstname) && empty($obj->lastname)) {
                    $html .= $obj->login;
                }

                $html .= '</option>';
            }
        }
        $html .= '</select>';

        $returned['html'] = $html;
        $returned['array'] = $arr_users;

        // d($arr_users);
        return $returned;
    }
    
    public function selectProjectsdigikanbanAuthorized($selected = array(), $search_category = 0, $search_status = 0, $return_only_one = false, $multiple=1, $start='', $end='', $shownumbertotal = true)
    {
        global $langs, $user, $conf, $selectallornone, $projectstoselectafterrefresh;

        $project = new Project($this->db);

        $sortfield = isset($conf->global->GANTTPROADVANCED_PROJECT_SORTFIELD) ? $conf->global->GANTTPROADVANCED_PROJECT_SORTFIELD : 'p.ref';
        $sortorder = isset($conf->global->GANTTPROADVANCED_PROJECT_SORTORDER) ? $conf->global->GANTTPROADVANCED_PROJECT_SORTORDER : 'ASC';

        $sql = "SELECT DISTINCT p.rowid, p.ref, p.title";

        $sql .= " FROM ".MAIN_DB_PREFIX."projet as p";

        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_project as cp ON cp.fk_project = p.rowid ';

        $sql .= " WHERE";
        $sql .= " p.entity IN (".getEntity('project').")";


        if (!$user->rights->projet->all->lire) {
            $projectsListId = $project->getProjectsAuthorizedForUser($user, 0, 1);
            $sql .= " AND p.rowid IN (".$projectsListId.")";
        }
        // $sql .= ' AND p.fk_statut != '.Project::STATUS_CLOSED;

        if($search_status != 100){
            if ($search_status == 99) {
                $sql .= " AND p.fk_statut <> 2";
            } else {
                $sql .= " AND p.fk_statut = ".((int) $search_status);
            }
        }

        if($search_category > 0){
            $sql .= (!$search_projects && $search_category > 0) ? ' AND cp.fk_categorie = '.$search_category : '';
        }


        if($start && $end){
            $sql .= ' AND (';
            $sql .= ' (CAST(p.dateo as date) BETWEEN "'.$this->db->idate($start).'" AND "'.$this->db->idate($end).'")';
            $sql .= ' OR ';
            $sql .= ' (CAST(p.datee as date) BETWEEN "'.$this->db->idate($start).'" AND "'.$this->db->idate($end).'")';
            $sql .= ')';
        }

        $sql .= $this->db->order($sortfield, $sortorder);

        if($return_only_one) {
            // $sql .= ' LIMIT 1 ';
        }
        // echo $sql;

        $returns = array();

        $resql = $this->db->query($sql);
        $html='';
        // $html = '<select class="select_proj_visibl minwidth75imp width300 maxwidth300" name="search_projects[]" multiple onchange="this.form.submit()">';
        $name = $multiple ? 'search_projects[]' : 'fk_projet';
        $txtmultipl = $multiple ? 'multiple' : '';
        $select = '<select class="select_proj_visibl minwidth75imp width300 maxwidth300" name="'.$name.'" '.$txtmultipl.' onchange="digikanban_refreshfilter()">';
        // $select .= '<option value="-1">'.$langs->Trans("All").'</option>';


        $fields_edit_popup = $this->fields_edit_popup ? explode(',', $this->fields_edit_popup) : [];
        $edit_popup = $fields_edit_popup ? array_flip($fields_edit_popup) : [];

        $totproject = 0;
        $totslctdproject = 0;

        if ($resql)
        {
            while ($obj = $this->db->fetch_object($resql))
            {
                if($return_only_one) { 
                    // $returns = array($obj->rowid => $obj->ref); 
                    // break;
                    
                    $returns[$obj->rowid] = $obj->ref; 
                }

                $select .= '<option value="'.$obj->rowid.'"' ;

                $optionselected = '';

                if($selectallornone) {
                    if($selectallornone == 1)
                        $optionselected = 'selected'; // 2 : None
                } else {
                    if(!is_array($selected) && $selected == $obj->rowid) $optionselected = 'selected';
                    elseif(is_array($selected) && in_array($obj->rowid, $selected)) $optionselected = 'selected';
                }

                $select .= $optionselected;

                $select .= '>';


                if (isset($edit_popup['NumProjet'])) {
                    $select .= $obj->ref.($obj->title ? ' - ' : '');
                }

                // if($obj->ref && $obj->title) $select .= ' - ';

                // $select .= $obj->title;
                $select .= dol_trunc($obj->title,100);

                $select .= '</option>';

                $totproject++;
                if($optionselected) {
                    $projectstoselectafterrefresh[$obj->rowid] = $obj->rowid;
                    $totslctdproject++;
                }
            }
        }
        $select .= '</select>';
        

        if($shownumbertotal) {
            $html .= '<span class="small" title="'.$langs->trans('Projects').'">';
                $html .= $totslctdproject;
                $html .= '/';
                $html .= '<span class="opacitymedium">';
                $html .= $totproject;
                $html .= '</span>';
            $html .= '</span>';
        }

        $html .= $select;

        if($return_only_one) { 
            return $returns;
        }

        return $html;
    }

    
    public function select_duration_kanabn($prefix, $iSecond = '', $disabled = 0, $typehour = 'select', $minunderhours = 0, $nooutput = 0)
    {
        // phpcs:enable
        global $langs;

        $retstring = '<span class="nowraponall">';

        $hourSelected = 0;
        $minSelected = 0;

        // Hours
        if ($iSecond != '') {
            require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

            $hourSelected = convertSecondToTime($iSecond, 'allhour');
            $minSelected = convertSecondToTime($iSecond, 'min');
        }

        if ($typehour == 'select') {
            $retstring .= '<select class="flat" id="select_'.$prefix.'hour" name="'.$prefix.'hour"'.($disabled ? ' disabled' : '').'>';
            for ($hour = 0; $hour < 25; $hour++) {  // For a duration, we allow 24 hours
                $retstring .= '<option value="'.$hour.'"';
                if ($hourSelected == $hour) {
                    $retstring .= " selected";
                }
                $retstring .= ">".$hour."</option>";
            }
            $retstring .= "</select>";
        } elseif ($typehour == 'text' || $typehour == 'textselect') {
            $retstring .= '<input placeholder="'.$langs->trans('HourShort').'" type="number" min="0" name="'.$prefix.'hour"'.($disabled ? ' disabled' : '').' class="flat maxwidth50 inputhour" value="'.(($hourSelected != '') ? ((int) $hourSelected) : '').'">';
        } else {
            return 'BadValueForParameterTypeHour';
        }

        if ($typehour != 'text') {
            $retstring .= ' '.$langs->trans('HourShort');
        } else {
            $retstring .= '<span class="">:</span>';
        }

        // Minutes
        if ($minunderhours) {
            $retstring .= '<br>';
        } else {
            $retstring .= '<span class="hideonsmartphone">&nbsp;</span>';
        }

        if ($typehour == 'select' || $typehour == 'textselect') {
            $retstring .= '<select class="flat" id="select_'.$prefix.'min" name="'.$prefix.'min"'.($disabled ? ' disabled' : '').'>';
            for ($min = 0; $min <= 55; $min = $min + 5) {
                $retstring .= '<option value="'.$min.'"';
                if ($minSelected == $min) {
                    $retstring .= ' selected';
                }
                $retstring .= '>'.$min.'</option>';
            }
            $retstring .= "</select>";
        } elseif ($typehour == 'text') {
            $retstring .= '<input placeholder="'.$langs->trans('MinuteShort').'" type="number" min="0" name="'.$prefix.'min"'.($disabled ? ' disabled' : '').' class="flat maxwidth50 inputminute" value="'.(($minSelected != '') ? ((int) $minSelected) : '').'">';
        }

        if ($typehour != 'text') {
            $retstring .= ' '.$langs->trans('MinuteShort');
        }

        $retstring.="</span>";

        if (!empty($nooutput)) {
            return $retstring;
        }

        return $retstring;
    }
    
    public function coutstemepconsomme($id)
    {


        $table_task_time          = 'projet_task_time';
        $column_fk_task           = 'fk_task';
        $column_task_duration     = 'task_duration';
        $column_elementtype       = 'elementtype';
        $filtertype = '';
        if(floatval(DOL_VERSION) >= 18) {
            $table_task_time      = 'element_time';
            $column_fk_task       = 'fk_element';
            $column_task_duration = 'element_duration';
            $filtertype = ' AND elementtype="task"';
        }

        $sql ='SELECT SUM(tm.thm * tm.task_duration/3600) as couttotal FROM '.MAIN_DB_PREFIX.$table_task_time.' as tm';
        $sql .= ' WHERE  tm.'.$column_fk_task.' ='.$id;
        $sql .= $filtertype;
        $total=0;
        $res = $this->db->query($sql);
        if($res){
            while ($obj = $this->db->fetch_object($res)) {
                $total += $obj->couttotal;
            }
        }

        return $total;
    }


    public function selectFieldsToShowInTaskPopup($selected=0, $name='select_', $multiple=0, $showempty=1, $select_hoverpopup = false){
        global $conf,$langs;
        $html = '';
        $nodatarole = '';
        $multi= '';
        $objet = "label";

        $id = $name;

        if($multiple){
            $multi = 'multiple';
            $name = $name.'[]';
        }

        if (!is_array($selected)) {
            $selected = array($selected);
        }

        $html.='<select class="flat minwidth400 width80p" id="'.$id.'" name="'.$name.'" '.$nodatarole.' '.$multi.'>';
        if ($showempty) $html.='<option value="-1">&nbsp;</option>';
        if(!empty($this->arr_fields_edit_popup) && empty($select_hoverpopup)) {
            foreach ($this->arr_fields_edit_popup as $key => $type) {
                $html .= '<option value="'.$type.'"' ;
                if(!empty($selected) && in_array($type, $selected)) $html .= 'selected';
                $html .= '>';
                $html .= $langs->trans($type);
                $html .= '</option>';
            }
        }

        if(!empty($this->arr_fields_hover_popup) && !empty($select_hoverpopup)) {
            foreach ($this->arr_fields_hover_popup as $key => $value) {
                $html .= '<option value="'.$value.'"' ;
                if(!empty($selected) && in_array($value, $selected)) $html .= 'selected';
                $html .= '>';
                $html .= $langs->trans($value);
                $html .= '</option>';
            }
        }


        $tasks = new Task($this->db);
        $extrafields = new ExtraFields($this->db);
            
        $extrafields->fetch_name_optionals_label($tasks->table_element);

        if(!empty($extrafields->attributes[$tasks->table_element]['label'])){

            foreach ($extrafields->attributes[$tasks->table_element]['label'] as $key => $value) {

                if(($extrafields->attributes[$tasks->table_element]['list'][$key] == 1 || $extrafields->attributes[$tasks->table_element]['list'][$key] == 3)){

                    $htmlextrafield = '';

                    if(!empty($select_hoverpopup)) {
                        if ($key == 'ganttproadvancedcolor' || $key == 'ganttproadvanceddatejalon' || $key == 'ganttproadvancedtyperelation') {
                            continue;
                        }

                    }
                    elseif (empty($select_hoverpopup)) {
                        if ($key == 'color_datejalon' || $key == 'ganttproadvancedtyperelation' || $key == 'digikanban_colomn') {
                            continue;
                        }
                    }

                    $htmlextrafield = !empty($value) ? $langs->trans($value) : '';

                    if(!empty($htmlextrafield)) {
                        $html .= '<option value="'.$key.'"' ;

                        if(!empty($selected) && in_array($key, $selected)) $html .= 'selected';

                        $html .= '>';
                        $html .= $htmlextrafield;
                        $html .= '</option>';
                    }
                }
            }
        }

        $html.='</select>';
        $html.='<style>#s2id_select_'.$name.'{ width: 100% !important;}</style>';
        // $html.='<script>';
        //     $html.='$(document).ready(function(){';
        //         $html.='$("#fields_edit_popup").select2();';
        //         $html.='$("#fields_hover_popup").select2();';
        //     $html.='});';
        // $html.='</script>';

        return $html;
    }

    public function upgradeThedigikanbanModule()
    {
        global $conf, $langs;

        dol_include_once('/digikanban/core/modules/moddigikanban.class.php');
        $moddigikanban = new moddigikanban($this->db);

        $lastversion    = $moddigikanban->version;
        $currentversion = dolibarr_get_const($this->db, 'DIGIKANBAN_LAST_VERSION_OF_MODULE', 0);

        if (!$currentversion || ($currentversion && $lastversion != $currentversion)){
            $res = $this->initThedigikanbanModule($lastversion);
            if($res)
                dolibarr_set_const($this->db, 'DIGIKANBAN_LAST_VERSION_OF_MODULE', $lastversion, 'chaine', 0, '', 0);
            return 1;
        }
        return 0;
    }
    
    public function initThedigikanbanModule($lastversion = '')
    {
        global $conf, $langs, $user;

        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);

        if(dolibarr_get_const($this->db,'KANBAN_STATUT_DATE_GREY',$conf->entity) == '')
            dolibarr_set_const($this->db,'KANBAN_STATUT_DATE_GREY', $langs->trans('Grey'),'chaine',0,'',$conf->entity);
        if(dolibarr_get_const($this->db,'KANBAN_STATUT_DATE_GREEN',$conf->entity) == '')
            dolibarr_set_const($this->db,'KANBAN_STATUT_DATE_GREEN',$langs->trans('Green'),'chaine',0,'',$conf->entity);
        if(dolibarr_get_const($this->db,'KANBAN_STATUT_DATE_RED',$conf->entity) == '')
            dolibarr_set_const($this->db,'KANBAN_STATUT_DATE_RED', $langs->trans('Red'),'chaine',0,'',$conf->entity);


        // $extrafields->addExtraField('digikanbancolor', $langs->trans('Color'), "varchar", "27", 100, "projet_task", 0, 0, '#16a085');
        // $extrafields->addExtraField('digikanbanprojectprogress', $langs->trans('ProgressProject'), "int", "27", 100, "projet", 0, 0, '0');


        $sql = 'CREATE TABLE IF NOT EXISTS '.MAIN_DB_PREFIX.'digikanban_commnts (
            rowid int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            comment text NULL,
            fk_task int NULL ,
            fk_user int NULL ,
            date datetime NULL
        );';
        $resql = $this->db->query($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS '.MAIN_DB_PREFIX.'digikanban_columns (
            rowid int NOT NULL AUTO_INCREMENT PRIMARY KEY
            ,label varchar(550) NULL
            ,datec datetime NULL
            ,tms datetime NULL
            ,fk_user_author int NULL
            ,fk_user_modif int NULL
            ,entity int NOT NULL DEFAULT '.$conf->entity.'
        );';
        $resql = $this->db->query($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS '.MAIN_DB_PREFIX.'digikanban_tags (
            rowid int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            label varchar(355) NULL,
            color varchar(355) NULL
        );';
        $resql = $this->db->query($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS '.MAIN_DB_PREFIX.'digikanban_checklist (
            rowid int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            numcheck int NULL,
            label varchar(355) NULL,
            fk_task int NULL,
            checked boolean NULL
        );';
        $resql = $this->db->query($sql);
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."digikanban_checklist ADD numcheck int NULL AFTER rowid");

        $sql = 'CREATE TABLE IF NOT EXISTS '.MAIN_DB_PREFIX.'digikanban_tagstask (
            rowid int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            numtag int NULL,
            fk_tag int NULL,
            fk_task int NULL,
            checked boolean NULL DEFAULT 1
        );';
        $resql = $this->db->query($sql);

        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."digikanban_tagstask ADD numtag int NULL AFTER rowid");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."digikanban_tagstask ADD checked boolean NULL DEFAULT 1");

        $sql = 'CREATE TABLE IF NOT EXISTS '.MAIN_DB_PREFIX.'digikanban_modeles (
            rowid int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            contenu text NULL,
            etiquettes text NULL,
            checklist text NULL
        );';
        $resql = $this->db->query($sql);

        $datec = $this->db->idate(dol_now());
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."digikanban_columns (rowid, label, datec, fk_user_author) VALUES
            (1, '".$langs->trans('EnAttent')."', '".$datec."', ".$user->id."),
            (2, '".$langs->trans('ALivrer')."', '".$datec."', ".$user->id."),
            (3, '".$langs->trans('EnCours')."', '".$datec."', ".$user->id."),
            (4, '".$langs->trans('Chronique')."', '".$datec."', ".$user->id."),
            (5, '".$langs->trans('Cloturer')."', '".$datec."', ".$user->id.");
        ";
        $resql = $this->db->query($sql);

        if(!$resql){
            // die($this->db->lasterror());
        }
        

        $params = serialize(array('options' => array('grey'=>$langs->trans('Grey'),'green'=>$langs->trans('Green'),'red'=>$langs->trans('Red'))));
        $extrafields->addExtraField('color_datejalon', $langs->trans('color_datejalon'), "select", "27", 100, "projet_task", 0, 0, '', $params, 1, '', '', '','', $conf->entity);
        $extrafields->addExtraField('ganttproadvanceddatejalon', 'JalonDate', "date", "27", 100, "projet_task", 0, 0, '', '', 0, '', 3, '','', $conf->entity);
        $extrafields->addExtraField('ganttproadvancedcolor', 'Color', "varchar", "27", 100, "projet_task", 0, 0, '#16a085', '', 0, '', 3, '','', $conf->entity);

        $params = serialize(array('options' => array('digikanban_columns:label:rowid' => null)));
        $extrafields->addExtraField('digikanban_colomn', 'tagsdigikanban', "sellist", "27", 100, "projet_task", 0, 0, '', $params, 1, '', 1, '','', $conf->entity);

        if(dolibarr_get_const($this->db,'DELEY_ALERTE_DATEJALON',$conf->entity) == '')
            dolibarr_set_const($this->db,'DELEY_ALERTE_DATEJALON', 2,'chaine',0,'',$conf->entity);

        if(dolibarr_get_const($this->db,'KANBAN_NOMBRE_HEURES_DE_TRAVAIL_PAR_JOUR',$conf->entity) == '')
            dolibarr_set_const($this->db,'KANBAN_NOMBRE_HEURES_DE_TRAVAIL_PAR_JOUR', 7,'chaine',0,'',$conf->entity);



        $tasks = new Task($this->db);
        $extrafields->fetch_name_optionals_label($tasks->table_element);

        if(dolibarr_get_const($this->db,'DIGIKANBAN_FIELDS_TO_SHOW_IN_EDIT_POPUP',$conf->entity) == '' || dolibarr_get_const($this->db,'DIGIKANBAN_FIELDS_TO_SHOW_IN_HOVER_POPUP',$conf->entity) == ''){
            

            // $this->arr_fields_edit_popup = array('Label','Project','AffectedTo','Description');
            // $this->arr_fields_hover_popup = array('Label','AffectedTo','Description','JalonDate');


            $fields_edit_popup = !empty($this->arr_fields_edit_popup) ? implode(',', $this->arr_fields_edit_popup) : [];
            $fields_hover_popup = !empty($this->arr_fields_hover_popup) ? implode(',', $this->arr_fields_hover_popup) : [];

            if(!empty($extrafields->attributes[$tasks->table_element]['label'])){
                foreach ($extrafields->attributes[$tasks->table_element]['label'] as $key => $value) {
                    if(($extrafields->attributes[$tasks->table_element]['list'][$key] == 1 || $extrafields->attributes[$tasks->table_element]['list'][$key] == 3)){

                        // if($key != 'ganttproadvancedcolor' && $key != 'color_datejalon' && $key != 'ganttproadvancedtyperelation' && $key != 'digikanban_colomn'){
                        
                        if($key != 'color_datejalon' && $key != 'ganttproadvancedtyperelation' && $key != 'digikanban_colomn'){
                            $fields_edit_popup .= !empty($key) ? ','.$key : '';
                        }

                        if($key != 'ganttproadvancedcolor' && $key != 'ganttproadvanceddatejalon' && $key != 'ganttproadvancedtyperelation'){
                            $fields_hover_popup .= !empty($key) ? ','.$key : '';
                        }
                    }

                }
            }

            // d($fields_edit_popup,0);
            // d($fields_hover_popup,0);
            // die;

            if(dolibarr_get_const($this->db,'DIGIKANBAN_FIELDS_TO_SHOW_IN_EDIT_POPUP',$conf->entity) == ''){
                dolibarr_set_const($this->db, 'DIGIKANBAN_FIELDS_TO_SHOW_IN_EDIT_POPUP', $fields_edit_popup, 'chaine', 0, '', $conf->entity); 
            }

            if(dolibarr_get_const($this->db,'DIGIKANBAN_FIELDS_TO_SHOW_IN_HOVER_POPUP',$conf->entity) == ''){
                dolibarr_set_const($this->db, 'DIGIKANBAN_FIELDS_TO_SHOW_IN_HOVER_POPUP', $fields_hover_popup, 'chaine', 0, '', $conf->entity);
            }
        }


        return 1;
    }

}
?>