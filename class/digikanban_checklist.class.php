<?php 
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php'; 

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';


class digikanban_checklist extends Commonobject{ 

    public $errors = array();

    public $element='digikanban_checklist';
    public $table_element='digikanban_checklist';
    public $picto = 'cog';
    public $color;
    public $label;


    public function __construct($db){ 
        $this->db = $db;
        return 1;
    }

    public function create($insert)
    {
        global $conf;

        $this->entity = ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);

        $sql  = "INSERT INTO " . MAIN_DB_PREFIX .$this->table_element." ( ";

        $sql_column = '';$sql_value = '';
        foreach ($insert as $column => $value) {
            $alias = (is_numeric($value)) ? "" : "'";
            if($value != ""){
                $sql_column .= " , `".$column."`";
                $sql_value .= " , ".$alias.$value.$alias;
            }
        }

        $sql .= substr($sql_column, 2)." ) VALUES ( ".substr($sql_value, 2)." )";
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->db->rollback();
            $this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
            
            return 0;
        } 
        return $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
    }
    public function create_tag($insert)
    {
        global $conf;

        $this->entity = ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);

        $sql  = "INSERT INTO " . MAIN_DB_PREFIX .$this->table_element_task." ( ";

        $sql_column = '';$sql_value = '';
        foreach ($insert as $column => $value) {
            $alias = (is_numeric($value)) ? "" : "'";
            if($value != ""){
                $sql_column .= " , `".$column."`";
                $sql_value .= " , ".$alias.$value.$alias;
            }
        }

        $sql .= substr($sql_column, 2)." ) VALUES ( ".substr($sql_value, 2)." )";
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->db->rollback();
            $this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
            
            return 0;
        } 
        return $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
    }

    public function update($id, array $data,$echo_sql=0)
    {
        global $conf;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $this->entity = ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);

        if (!$id || $id <= 0)
            return false;

        $sql = 'UPDATE ' . MAIN_DB_PREFIX .$this->table_element. ' SET ';

        if (count($data) && is_array($data))
            foreach ($data as $key => $val) {
                $val = is_numeric($val) ? $val : '"'. $val .'"';
                $val = ($val == '') ? 'NULL' : $val;
                $sql .= '`'. $key. '` = '. $val .',';
            }

        $sql  = substr($sql, 0, -1);
        $sql .= ' WHERE rowid = ' . $id;
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->db->rollback();
            $this->errors[] = 'Error '.get_class($this).' : '. $this->db->lasterror();
            return -1;
        } 
        return 1;
    }

    public function delete($echo_sql=0)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql    = 'DELETE FROM ' . MAIN_DB_PREFIX .$this->table_element.' WHERE rowid = ' . $this->rowid;
        $resql  = $this->db->query($sql);
        
        if (!$resql) {
            $this->db->rollback();
            $this->errors[] = 'Error '.get_class($this).' : '.$this->db->lasterror();
            return -1;
        } 

        return 1;
    }


    public function delete_tag($id, $filter="")
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = 'DELETE FROM ' . MAIN_DB_PREFIX .$this->table_element_task.' WHERE rowid IN ('.$id.')';
        $sql .= $filter ? $filter : '';
        $resql  = $this->db->query($sql);
        
        if (!$resql) {
            $this->db->rollback();
            $this->errors[] = 'Error '.get_class($this).' : '.$this->db->lasterror();
            return -1;
        } 

        return 1;
    }

    public function fetch($id)
    {
        global $conf;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX .$this->table_element. ' WHERE rowid = ' . $id;

        $resql = $this->db->query($sql);
        if ($resql) {
            $numrows = $this->db->num_rows($resql);
            
            if ($numrows) {
                $obj                       =  $this->db->fetch_object($resql);
                $this->id                  =  $obj->rowid;
                $this->rowid               =  $obj->rowid;
                $this->label               =  $obj->label;
                $this->fk_task             =  $obj->fk_task;
                $this->checked             =  $obj->checked;
            }

            $this->db->free($resql);

            if ($numrows) {
                return 1 ;
            } else {
                return 0;
            }
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
            return -1;
        }
    }


    public function selectCheck($id_task='', $show='')
    {
        global $langs;

        $data = array();
        $tags='';
        $html='';
        $htmlname = 'digikanban_checklist';

        $sql = 'SELECT c.rowid, c.label, c.checked, c.numcheck, c.fk_task FROM '.MAIN_DB_PREFIX.$this->table_element.' as c';
        $sql .= ' WHERE c.fk_task = '.$id_task;
        $sql .= ' ORDER BY c.numcheck ASC ';
        $resql = $this->db->query($sql);

        $html ='<ul class="fexwidthchecklist">';

        $containlist = false;

        if($resql){
            $i=0;
            while ($obj = $this->db->fetch_object($resql)) {
                $i++;
                $checked = $obj->checked ? 'checked' : '';
                $numcheck = $obj->numcheck ? (int)$obj->numcheck : $i;
                if($show){
                    $html .= '<li class="getchecklist checklist'.$checked.'" id="check_'.$obj->rowid.'">';
                        if($checked) $html .= '<span class="far fa-check-square"> </span>&nbsp;';
                        else $html .= '<span class="far fa-square"> </span>&nbsp;';
                        $html .= $obj->label;
                    $html .= '</li>';

                    $containlist = true;

                }else{

                    $tags .= '<li class="checklist checkli_'.$checked.'" id="check_'.$obj->rowid.'">';
                        $tags .= '<table class="checklist_task"><tr>';
                            $tags .= '<td class="width30px center" >';    
                                $tags .= '<input type="hidden" class="numcheck" id="numcheck_'.$obj->rowid.'" data-id="'.$obj->rowid.'" name="checklist[numcheck]['.$obj->rowid.']" value="'.$numcheck.'" />';
                                $tags .= '<input type="checkbox" class="cursorpointer_task check_list" '.$checked.' data-id="'.$obj->rowid.'" id="checkbox'.$obj->rowid.'" onchange="calcProgress(this)" name="checklist[checked]['.$obj->rowid.'] value="'.$obj->rowid.'" />';
                            $tags .= '</td>';
                            $tags .= '<td class="cursormove_task">';    
                                $tags .= '<input type="hidden" id="label_check_'.$obj->rowid.'" name="checklist[label]['.$obj->rowid.']" value="'.$obj->label.'" >';
                                $tags .= '<label class="cursormove_task" for="checkbox'.$obj->rowid.'">'.$obj->label.'</label>';
                            $tags .= '</td>';
                                $tags .= '<td class="width50px center">';
                                    $tags .= '<a class="deletecheck cursorpointer_task" data-id="'.$obj->rowid.'" onclick="deletecheck(this)">'.img_delete().'</a>';
                                    $tags .= '<a class="editcheck cursorpointer_task" data-id="'.$obj->rowid.'" data-num="'.$numcheck.'" onclick="editcheck(this)">'.img_edit().'</a>';
                                $tags .= '</td>';
                        $tags .= '</tr></table>';
                    $tags .= '</li>';
                }
            }
        }
        $html .='</ul>';

        if($show && !$containlist) $html = '';
        
        if($show) return $html;

        $html = '<div class="multiselectcheckboxtags">';
            $html .= '<input type="hidden" name="checkdeleted" class="checkdeleted">';
            $html .= '<ul class="list_checklist">';
                $html .= $tags;
            $html .= '</ul>';
        $html .= '</div>';

        $html .= '<div class="createtag" >';
            $html .= '<td><a class="cursorpointer_task" onclick="createcheck(this)"><span class="fas fa-plus"></span> '.$langs->trans('createcheck').'</a></td>';
        $html .= '</div>';

        return $html;
    }

    public function calcProgressCheckTask($id)
    {
        $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.$this->table_element;
        $sql .= ' WHERE fk_task='.$id;
        $resql = $this->db->query($sql);
        $total = 0;
        $percent = 0;
        $checked = 0;

        if($resql){
            $total = $this->db->num_rows($resql);
            while ($obj = $this->db->fetch_object($resql)) {
                if($obj->checked) $checked++;
            }
        }
        if ($total>0 && $checked) {
            $percent = (int)(($checked/$total)*100);
        }

        return ['percent'=>$percent ,'checked'=>$checked, 'total'=>$total];
    }

} 

?>