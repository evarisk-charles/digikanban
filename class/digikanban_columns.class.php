<?php 
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php'; 


class digikanban_columns extends Commonobject{ 

    public $errors = array();

    public $element='digikanban_columns';
    public $table_element='digikanban_columns';
    public $picto = 'cog';
    public $color;
    public $label;


    public function __construct($db){ 
        $this->db = $db;
        return 1;
    }


    public function create($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        $now = dol_now();

        $this->db->begin();


        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql .= " label, datec, fk_user_author, entity";
        $sql .= ")";
        $sql .= " VALUES (";

        $sql .= " '".$this->db->escape($this->label)."'";
        $sql .= ", '".$this->db->idate($now, 'tzuserrel')."'";
        $sql .= ", ".($user->id > 0 ? (int) $user->id : "null");
        $sql .= ", '".$conf->entity."'";
        
        $sql .= ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
            if ($this->id) {
                $this->db->commit();

                return $this->id;
            }
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            $this->error = "Error ".$this->db->lasterror();
            // dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *      Update database
     *
     *      @param      User    $user           User that modify
     *      @param      int     $notrigger      0=launch triggers after, 1=disable triggers
     *      @return     int                     <0 if KO, >0 if OK
     */
    public function update(User $user, $notrigger = 0)
    {
        global $conf;

        $now = dol_now();

        $error = 0;

        // Clean parameters
        if (isset($this->label)) {
            $this->label = trim($this->label);
        }

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";

        $sql .= " label = '".$this->db->escape($this->label)."'";
        $sql .= ", tms = '".$this->db->idate($now, 'tzuserrel')."'";
        $sql .= " WHERE rowid=".((int) $this->id);
        // d($sql);

        $this->db->begin();

        dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++; $this->errors[] = "Error ".$this->db->lasterror();
        }

        // Commit or rollback
        if ($error) {
            $this->db->rollback();
            dol_print_error($this->db);
            return -1 * $error;
        } else {
            
            $this->db->commit();
            return 1;
        }
    }

    /**
     *  Delete the object
     *
     *  @param  User    $user       User object
     *  @param  int     $notrigger  1=Does not execute triggers, 0= execute triggers
     *  @return int                 <=0 if KO, >0 if OK
     */
    public function delete($user, $notrigger = 0)
    {
        global $conf, $langs;

        $error = 0;
        $id = $this->id;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".((int) $this->id);
        $res = $this->db->query($sql);
        if (!$res) {
            $error++;
            $this->error = $this->db->lasterror();
            $this->errors[] = $this->error;
            dol_syslog(get_class($this)."::delete error ".$this->error, LOG_ERR);
        }

        if (!$error) {
            $result = $this->db->query("UPDATE ".MAIN_DB_PREFIX."projet_task_extrafields SET digikanban_colomn = 0 WHERE digikanban_colomn = ".(int) $id);
            dol_syslog(get_class($this)."::delete ".$this->id." by ".$user->id, LOG_DEBUG);
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *  Get object from database.
     *
     *  @param      int         $id             Id of object to load
     *  @param      string      $ref            Ref of object
     *  @param      string      $ref_ext        External reference of object
     *  @param      string      $notused        Internal reference of other object
     *  @return     int                         >0 if OK, <0 if KO, 0 if not found
     */
    public function fetch($id, $ref = '', $ref_ext = '', $notused = '')
    {
        global $conf;

        $sql = 'SELECT o.*';

        $sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as o';
        $sql .= " WHERE o.rowid=".((int) $id);
       
        // echo $sql;


        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            $obj = $this->db->fetch_object($result);
            if ($obj) {

                $this->id               = $obj->rowid;
                $this->rowid            = $obj->rowid;
                $this->label            = $obj->label;
                $this->tms              = $obj->tms;
                $this->datec            = $this->db->jdate($obj->datec);
                $this->fk_user_author   = $obj->fk_user_author;
                $this->entity           = $obj->entity;

                $this->db->free($result);

                return 1;
            } else {
                $this->error = 'Object with id '.$id.' not found sql='.$sql;
                return 0;
            }
        } else {
            $this->error = $this->db->error();
            return -1;
        }
    }


    /**
     *  Return clickable name (with picto eventually)
     *
     *  @param  int     $withpicto                0=No picto, 1=Include picto into link, 2=Only picto
     *  @param  string  $option                   Variant where the link point to ('', 'nolink')
     *  @param  int     $addlabel                 0=Default, 1=Add label into string, >1=Add first chars into string
     *  @param  string  $moreinpopup              Text to add into popup
     *  @param  string  $sep                      Separator between ref and label if option addlabel is set
     *  @param  int     $notooltip                1=Disable tooltip
     *  @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *  @param  string  $morecss                  More css on a link
     *  @return string                            String with URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $addlabel = 0, $moreinpopup = '', $sep = ' - ', $notooltip = 0, $save_lastsearch_value = -1, $morecss = '')
    {
        global $conf, $langs, $user, $hookmanager;


        $formfile = new FormFile($this->db);

        if (!empty($conf->dol_no_mouse_hover)) {
            $notooltip = 1; // Force disable tooltips
        }

        $result = '';

        $label = '<b><u>'.$langs->trans('TypeBien').'</u>: </b>';
  
        $label .= ($label ? '<br>' : '').'<b>'.$langs->trans('Label').': </b>'.$this->label;

        $url = '';
        $url = dol_buildpath('/digikanban/columns/card.php?id='.$this->id, 1);
    
        $linkclose = '';
        if (empty($notooltip) && isset($user->rights->scm->lire)) {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
                $label = $langs->trans("Show");
                $linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose .= ' class="paddingright classfortooltip'.($morecss ? ' '.$morecss : '').'"';
        } else {
            $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
        }

        $picto = $this->picto;

        $linkclose .= ($option == 'target') ? ' target="_blank" ' : '';
        $linkstart = '<a href="'.$url.'" ';
        $linkstart .= $linkclose.'>';
        $linkend = '</a>';

        $result .= $linkstart;
        if ($withpicto) {
            $result .= img_object(($notooltip ? '' : $label), $picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip "'), 0, 0, $notooltip ? 0 : 1);
        }
        if ($withpicto != 2) {
            $result .= ($this->label ? $this->label : $this->rowid);
        }
        $result .= $linkend;

        return $result;
    }

} 

?>