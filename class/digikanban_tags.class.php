<?php 
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php'; 

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';


class digikanban_tags extends Commonobject{ 

    public $errors = array();

    public $element='digikanban_tags';
    public $table_element='digikanban_tags';
    public $table_element_task='digikanban_tagstask';
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

        if($insert['fk_task']){

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

    public function update_tag($id, $data)
    {
        $sql = 'UPDATE ' . MAIN_DB_PREFIX .$this->table_element_task. ' SET ';

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
                $this->color               =  $obj->color;
                $this->entity              =  isset($obj->entity) ? $obj->entity : '';
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

    public function fetchAllTagsOfTask($fk_task=0)
    {
        $data = array();
        $sql = 'SELECT tg.rowid, g.label, g.color FROM '.MAIN_DB_PREFIX.$this->table_element_task.' as tg';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.$this->table_element.' as g ON g.rowid = tg.fk_tag';
        $sql .= ' WHERE tg.fk_task = '.$fk_task.' AND tg.checked=1';
        $sql .= ' GROUP BY tg.rowid';
        $sql .= ' ORDER BY tg.numtag ASC';
        $resql = $this->db->query($sql);
        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $data[$obj->rowid]['label']=$obj->label;
                $data[$obj->rowid]['color']=$obj->color;
            }
        }
        return $data;
    }

    public function fetchTag($id)
    {
        $data = array();
        $sql = 'SELECT tg.rowid, g.label, g.color FROM '.MAIN_DB_PREFIX.$this->table_element_task.' as tg';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.$this->table_element.' as g ON g.rowid = tg.fk_tag';
        $sql .= ' WHERE tg.rowid = '.$id;
        $sql .= ' GROUP BY tg.rowid';
        $resql = $this->db->query($sql);
        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $data[$obj->rowid]['label']=$obj->label;
                $data[$obj->rowid]['color']=$obj->color;
            }
        }
        return $data;
    }

    public function fetchTagsOfTask($fk_task=0)
    {
        $data = array();
        $sql = 'SELECT tg.rowid, tg.numtag, tg.checked, g.rowid as id_tag, g.label, g.color FROM '.MAIN_DB_PREFIX.$this->table_element_task.' as tg';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.$this->table_element.' as g ON g.rowid = tg.fk_tag';
        $sql .= ' WHERE tg.fk_task = '.$fk_task.' AND tg.fk_task>0';
        $sql .= ' GROUP BY tg.rowid';
        $sql .= ' ORDER BY tg.numtag ASC';
        // d($sql,0);
        $resql = $this->db->query($sql);
        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $data[$obj->id_tag]['id']=$obj->rowid;
                $data[$obj->id_tag]['num']=$obj->numtag;
                $data[$obj->id_tag]['checked']=$obj->checked;
            }
        }
        return $data;
    }

    public function fetchTagsOfModal($id_modal=0)
    {
        $data = array();
        $sql = 'SELECT m.rowid, m.etiquettes FROM '.MAIN_DB_PREFIX.'digikanban_modeles as m';
        $sql .= ' WHERE m.rowid = '.$id_modal;
        $sql .= ' GROUP BY m.rowid';

        $resql = $this->db->query($sql);
        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $etiquettes = ($obj->etiquettes ? unserialize($obj->etiquettes) : []);
                if($etiquettes){
                    foreach ($etiquettes as $key => $value) {
                        $data[$key]=$value;
                    }
                }
            }
        }
        return $data;
    }

    public function selectTags($id_task='', $id_modal='')
    {
        global $langs;

        $tags       = '';
        $data       = array();
        $arr_tags   = array();


        $htmlname = 'digikanban_tags';
        $dt_tags = $this->fetchTagsOfTask($id_task);
        if($id_modal){
            $dt_tags = $this->fetchTagsOfModal($id_modal);
        }

        $sql = 'SELECT t.rowid, t.label, t.color FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
        if($id_task){
            $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.$this->table_element_task.' as tg ON tg.fk_tag = t.rowid';
            $sql .= ' WHERE tg.fk_tag >0 ';
            $sql .= ' GROUP BY t.rowid';
            $sql .= ' ORDER BY tg.numtag ASC';
        }
        $resql = $this->db->query($sql);
        if($resql){
            $i=0;
            while ($obj = $this->db->fetch_object($resql)) {
                $arr_color = colorStringToArray($obj->color);
                $clr = '';
                if($arr_color){
                    foreach ($arr_color as $key => $value) {
                        $clr .= $value.', ';
                    }
                }
                $bgcolor = $clr ? 'rgb('.$clr.'0.3)' : $color;

                $i++;
                $id_tag =isset($dt_tags[$obj->rowid]['id']) ?  $dt_tags[$obj->rowid]['id'] : '';
                $numtag =isset($dt_tags[$obj->rowid]['num']) ?  $dt_tags[$obj->rowid]['num'] : $i;

                $tag = '<li class="tagstask" id="tag_'.$obj->rowid.'">';
                    $tag .= '<table class="tags_task"><tr>';

                        $tag .= '<td class="width20px">';    
                            $tag .= '<input type="hidden" name="tagstask[numtag]['.$obj->rowid.']" class="numtag" id="numtag_'.$obj->rowid.'" value="'.$numtag.'">';
                            $tag .= '<input type="hidden" name="tagsdeleted" class="tagsdeleted">';
                            $checked = ($id_tag && $dt_tags[$obj->rowid]['checked']) ? 'checked' : '';
                            $tag .= '<input class="cursorpointer_task" type="checkbox" '.$checked.' data-id="'.(int)$obj->rowid.'" data-tag="'.$id_tag.'" id="checkbox'.$obj->rowid.'" name="tagstask[checked]['.$obj->rowid.'] value="'.(int)$obj->rowid.'" />';
                            $tag .= '<input type="hidden" id="label_tagstask_'.$obj->rowid.'" name="tagstask[label]['.$obj->rowid.']" value="'.$obj->label.'" >';
                            $tag .= '<input type="hidden" id="color_tagstask_'.$obj->rowid.'" name="tagstask[color]['.$obj->rowid.']" value="'.$obj->color.'" >';
                        $tag .= '</td>';

                        $tag .= '<td>';
                            $tag .= '<label class="cursormove_task" for="checkbox'.$obj->rowid.'">';
                                $tag .= '<div style="background: '.$bgcolor.'" class="tagstask">';
                                    $tag .= '<span style="background:'.$obj->color.';"></span>';
                                    $tag .= '  <span class="lbl_tag">'.$obj->label.'</span>';
                                $tag .= '</div>';
                            $tag .= '</label>';
                        $tag .= '</td>';

                        $tag .= '<td class="width50px center">';
                            $tag .= '<a class="deteletetag" data-id="'.$obj->rowid.'" data-tag="'.$id_tag.'" onclick="deletetag(this)">'.img_delete();
                            $tag .= '<a class="edittag" data-id="'.$obj->rowid.'" data-tag="'.$id_tag.'" data-num="'.$numtag.'" onclick="edittag(this)">'.img_edit();
                        $tag .= '</td>';

                    $tag .= '</tr></table>';
                $tag .= '</li>';

                $arr_tags[$numtag] = $tag;
            }
        }

        if(is_array($arr_tags))
            ksort($arr_tags, SORT_REGULAR);

        $html = '<div class="searchtag" >';
            $html .= '<table class="tags_task">';
                $html .= '<tr>';
                    $html .= '<td class="width20px"></td>';
                    $html .= '<td><input type="text" id="txt_searchtag" name="searchtag" onkeyup="inSearchTag(this)" value="" style="border-radius:4px !important;" placeholder="'.$langs->trans('Search').'" /></td>';
                    $html .= '<td class="width50px center"> <a class="removesearch cursorpointer_task" onclick="removesearch(this)"><span class="fas fa-times-circle"></span></a></td>';
                $html .= '</tr>';
            $html .= '</table>';
        $html .= '</div>';
        $html .= '<div class="multiselectcheckboxtags">';
            $html .= '<ul class="list_tags">';
                if($arr_tags)
                foreach ($arr_tags as $key => $ttag) {
                    $html .= $ttag;
                }
            $html .= '</ul>';
        $html .= '</div>';

        $html .= '<div class="createtag" >';
            $html .= '<table class="tags_task">';
                $html .= '<tr>';
                    $html .= '<td class="width20px"></td>';
                    $html .= '<td><a class="cursorpointer_task" onclick="createtag(this)"><span class="fas fa-plus"></span> '.$langs->trans('createtag').'</a></td>';
                    $html .= '<td class="width50px"></td>';
                $html .= '</tr>';
            $html .= '</table>';
        $html .= '</div>';

        // $html .= '<table width="85%"><tr>';
        //     $html .= '<td style="width:80%">';
        //         $html .= '<input type="color" id="colortag" value="#dddddd" >';
        //     $html .= '</td>';
        //     $html .= '<td style="width:12%">';
        //         $html .= '<input type="text" id="newtag" name="newtag" value="" style="border-radius:4px !important;" placeholder="'.$langs->trans('NewTag').'" />';
        //     $html .= '</td>';
        //     $html .= '<td style="width:8%" align="center">';
        //         $html .= '<a class="addtags" onclick="NewTags(this)" title="'.$langs->trans('Save').'"><span class="fas fa-plus"></span></a>';
        //     $html .='</td>';
        // $html .= '</tr></table>';



        // $select = '<select class="selecttags minwidth500" name="selecttags[]" multiple>';
        //     $select .= $opts;
        // $select .= '</select>';
        return $html;
    }



    public function selectAllTags($name='', $tag=[], $moreparam='')
    {
        global $langs;

        $data = array();
        $opts='';

        $sql = 'SELECT t.rowid, t.label, t.color FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
        $resql = $this->db->query($sql);
        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $data[$obj->rowid]=$obj->label;
                // $opts .= '<option value="'.$obj->rowid.'"><span class="grey_color" style="background-color: '.$obj->color.'">'.$obj->label.'</span></option>';
            }
        }
        // $select = '<select id="digikanban_tags" name="'.$name.'[]" multiple class="minwidth150 maxwidth200">';
        // $select .= '<option value="0"></option>';
        // $select .= $opts;
        // $select .= '</select>';
        // return $select;
        // return Form::selectarray($name, $data, $tag, 1, 0, 0, '', 0, 0, 0, '', 'minwidth150 maxwidth200 selectarrowonleft');
        return Form::multiselectarray($name, $data, $tag, 0, 0, 'minwidth150 maxwidth200', 0, 0, $moreparam);
    }

} 

?>