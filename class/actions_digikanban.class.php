<?php
/* Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *      \file       htdocs/core/class/antivir.class.php
 *      \brief      File of class to scan viruses
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

/**
 * Class Actionsdigikanban
 */
class Actionsdigikanban
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * Constructor
	 */
	public function __construct()
	{

	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $db, $user, $sldprogress;


	}

	public function completeTabsHead($parameters, &$object, &$action, $hookmanager){
		global $langs, $db;
		dol_include_once('/digikanban/class/digikanban_commnts.class.php');
		$currentpage = explode(':', $parameters['context']);
		if(in_array('projecttaskcard', $currentpage)) {

			$comment = new digikanban_commnts($db);
			if(isset($parameters['object']) && empty($object->id))
				$object = $parameters['object'];
			$nbComment = $comment->fetchAll(' AND fk_task ='.$object->id);

			$withproject = GETPOST('withproject', 'int');
			// --------------------------------------------------------------------------------------------------------------
			?>
			<script type="text/javascript">
				$(document).ready(function() {
			    if ($('#tab_commentaire').length){
			    	var title = '<?php echo $langs->trans("Comments") ?>';
			        $('#tab_commentaire').html(title+' <span class="badge marginleftonlyshort"><?php echo dol_escape_js($nbComment); ?></span>').addClass('loaded');

			        <?php if($withproject) { ?>
			        	var tmpurl = window.location.protocol + "//" + window.location.host;
				        tabcomm = $('#tab_commentaire').attr('href');
						var url = new URL(tmpurl+tabcomm);
						url.searchParams.set("withproject", 1);
						var newUrl = url.href;
						newUrl = newUrl.replace(tmpurl, "");
						$('#tab_commentaire').attr('href', newUrl);
			        <?php } ?>

			    }
			   });
			</script>
		    <?php
		}
	    return 0;

	}

	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $db, $user, $sldprogress;

		$currentpage = explode(':', $parameters['context']);
		
		if((in_array('projecttaskcard', $currentpage) || in_array('projecttaskscard', $currentpage)) && empty($conf->ganttproadvanced->enabled)) {

			$sldprogress = 0;

			$action = GETPOST('action', 'alpha');
			$id 	= GETPOST('id', 'int');

			if($action == 'edit' && $id > 0) {
				$task = new Task($db);
				$task->fetch($id);
				$sldprogress = $task->progress;
			}

			$formother = new FormOther($db);
			$selectprogress = $formother->select_percent($sldprogress, 'progress', 0, 1, 0, 100);

			?>
			<script type="text/javascript">
				$(document).ready(function(){
					var progress = $('form select[name="progress"]');
					if(progress.length > 0) {
						progress.parent('td').html('<?php echo $selectprogress; ?>');
					}
				});
			</script>
			<?php

		}
	}

}
