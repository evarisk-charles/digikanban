<?php 
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php'; 

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';


class digikanban_commnts extends Commonobject{ 

	public $errors = array();

    public $element='digikanban_commnts';
    public $table_element='digikanban_commnts';
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

    public function delete($id="")
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql    = 'DELETE FROM ' . MAIN_DB_PREFIX .$this->table_element.' WHERE rowid ='.$id;
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

        $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX .$this->table_element. ' WHERE fk_task = ' . $id;

        $resql = $this->db->query($sql);
        if ($resql) {
            $numrows = $this->db->num_rows($resql);
            
            if ($numrows) {
                $obj                       =  $this->db->fetch_object($resql);
                $this->id                  =  $obj->rowid;
                $this->rowid               =  $obj->rowid;
                $this->comment             =  $obj->comment;
                $this->date                =  $this->db->jdate($obj->date);
                $this->fk_user             =  $obj->fk_user;
                $this->fk_task             =  $obj->fk_task;
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

    public function fetchAll($filter = '')
    {
        global $conf, $user, $array_count_object_comments;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $sql = "SELECT * FROM ";
        $sql .= MAIN_DB_PREFIX .get_class($this);
        $sql .= " WHERE  1>0";

        if (!empty($filter)) {
            $sql .= " ".$filter;
        }

        $this->rows = array();
        $resql = $this->db->query($sql);

        $testexist = 0;

        if ($resql) {
            $num = $this->db->num_rows($resql);

            
            $this->db->free($resql);

            return $num;
        } 
    }

    public function getcomments($fk_task='')
    {
        global $langs, $db, $user;
        $data = array();
        $comments = array();
        $sql = ' SELECT * FROM '.MAIN_DB_PREFIX.'digikanban_commnts';
        $sql .= ' WHERE fk_task='.$fk_task;
        $sql .= ' ORDER BY rowid DESC';

        $commenthover = '';
        $html = '';

        $nbrcomm=0;
        $resql = $this->db->query($sql);
        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $us = new User($db);
                $us->fetch($obj->fk_user);
                $date = $db->jdate($obj->date);
                $comments[]['user'] = $us->getNomUrl(-2);
                $comments[]['comment'] = nl2br($obj->comment);
                $comments[]['date'] = dol_print_date($date, 'dayhour');
            
                $commenthover .= '<tr>';
                    $commenthover .= '<td class="nowrap valigntop">';
                        $commenthover .= Form::showphoto('userphoto', $us, 0, 0, 0, 'userphoto logo_user_comment', 'mini', 0, 1);
                        $commenthover .= '&nbsp;<strong>'.$us->getFullname($langs).'</strong> ';
                    $commenthover .= '</td>';
                    $commenthover .= '<td class="nowrap valigntop">';
                        $commenthover .= '<span class="show_date_comment">'.$langs->trans('Le').' '.dol_print_date($date, 'day').' '.$langs->trans('at').' '.dol_print_date($date, 'hour').': </span>';
                    $commenthover .= '</td>';
                    $commenthover .= '<td class="valigntop">';
                        $commenthover .= '<span class="show_comment">'.nl2br($obj->comment).'</span>';
                    $commenthover .= '</td>';
                $commenthover .= '</tr>';

                $html .= '<div id="kanban_comment_'.$obj->rowid.'">';
                    $html .= '<div class="kanban_user_comment">';
                        $html .= $us->getNomUrl(-2);
                    $html .= '</div>';
                    $html .= '<div class="kanban_show_comment" id="kanban_comment_'.$obj->rowid.'">';
                        $html .= '<div class="kanban_comment_infouser">';
                            $html .= '<strong>'.$us->getFullname($langs).'</strong> ';
                            $html .= $langs->trans('Le').' '.dol_print_date($date, 'day').' '.$langs->trans('at').' '.dol_print_date($date, 'hour');
                        $html .= '</div>';
                        $html .= '<div class="kanban_comment_value">';
                            $html .= '<span class="show_comment">'.nl2br($obj->comment).'</span>';
                            $html .= '<textarea placeholder="'.$langs->trans('writecomment').'" class="update_comment comment_'.$obj->rowid.'" id="txt_comment">'.nl2br($obj->comment).'</textarea>';
                        $html .= '</div>';
                        $html .= '<div class="btn_comment">';

                            if($user->id == $obj->fk_user){

                                $html .= '<div class="update_comment">';
                                    $html .= '<a class="butAction savecomment" data-id="'.$obj->rowid.'" onclick="updatecomment(this)">'.$langs->trans('Save').'</a>';
                                    $html .= '<a class="butAction cancelupdatecomment" onclick="cancelupdatecomment(this)">'.$langs->trans('Cancel').'</a>';
                                $html .= '</div>';
                                $html .= '<div class="show_comment">';
                                    $html .= '<a class="edit_comment cursorpointer_task" data-id="'.$obj->rowid.'" onclick="editcomment(this)">'.img_edit().' '.$langs->trans('Modify').'</a>';
                                    $html .= '<a class="delete_comment cursorpointer_task" data-id="'.$obj->rowid.'" onclick="deletecomment(this)">'.img_delete().' '.$langs->trans('Delete').'</a>';
                                $html .= '</div>';
                            }


                        $html .= '</div>';
                    $html .= '</div>';
                $html .= '</div>';
                $nbrcomm++;
            }
        }

        $htmlhover = '';
        if($commenthover) {
            $htmlhover = '<u><strong>'.$langs->trans('Comments').' '.$nbrcomm.': </strong></u>';
            $htmlhover .= '<table class="hover_comment">';
                $htmlhover .= $commenthover;
            $htmlhover .= '</table>';
        }

            // return $htmlhover;
        $data['html'] = $html;
        $data['htmlhover'] = $htmlhover;
        $data['nbcomment'] = $nbrcomm;
        return $data;
    }

} 

?>