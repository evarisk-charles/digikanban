<?php 
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php'; 

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';


class digikanban_modeles extends Commonobject{ 

    public $errors = array();

    public $element='digikanban_modeles';
    public $table_element='digikanban_modeles';
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

                $obj   = $this->db->fetch_object($resql);

                $contenu = $obj->contenu ? unserialize($obj->contenu) : [];
                $modal = new stdClass;

                // d($contenu);

                $modal->id                  = $obj->rowid;
                $modal->rowid               = $obj->rowid;
                $modal->contenu             = $obj->contenu;
                $modal->checklist           = $obj->checklist;
                $modal->etiquettes           = $obj->etiquettes;

                $modal->title               = $contenu['title'];
                $modal->userid              = $contenu['userid'];
                $modal->fk_project          = $contenu['fk_project'];
                $modal->fk_task_parent      = !empty($contenu['fk_task_parent']) ? $contenu['fk_task_parent'] : '';
                $modal->label               = $contenu['label'];
                $modal->description         = $contenu['description'];
                $modal->duration_effective  = !empty($contenu['duration_effective']) ? $contenu['duration_effective'] : '';
                $modal->planned_workload    = $contenu['planned_workload'];
                $modal->date_c              = $this->db->jdate($contenu['date_c']);
                $modal->date_start          = $this->db->jdate($contenu['date_start']);
                $modal->date_end            = $this->db->jdate($contenu['date_end']);
                $modal->fk_statut           = !empty($contenu['fk_statut']) ? $contenu['fk_statut'] : '';
                $modal->progress            = $contenu['progress'];
                $modal->budget_amount       = $contenu['budget_amount'];
                $modal->usercontact         = $contenu['usercontact'];
                $modal->array_options       = $contenu['array_options'];
                // $modal->priority            = $contenu['priority'];
                // $modal->note_private        = $contenu['note_private'];
                // $modal->note_public         = $contenu['note_public'];

                // if (isset($extrafields->attributes[$tasks->table_element]['label']) && is_array($extrafields->attributes[$tasks->table_element]['label'])) {
                //     $extralabels = $extrafields->attributes[$tasks->table_element]['label'];
                //     if (is_array($extralabels)) {
                //         // Get extra fields
                //         foreach ($extralabels as $key => $value) {
                //         }
                //     }
                // }

            }

            $this->db->free($resql);

            if ($numrows) {
                return $modal ;
            } else {
                return 0;
            }
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
            return -1;
        }
    }




    public function getAllModals($name='modalkanban', $selected='', $moreparam='')
    {
        global $langs;

        $data = array();
        $opts='';

        $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.$this->table_element.' as o';
        $resql = $this->db->query($sql);
        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                if($obj->contenu){
                    $contenu = unserialize($obj->contenu);
                    $data[$obj->rowid]=$contenu['title'];
                }
            }
        }


        return Form::selectarray($name, $data, $selected, 0, 0, 0, 'onchange="showactionmodal(this)"', 0, 0, 0, '', 'width400 minwidth400', 0);
    }

} 

?>