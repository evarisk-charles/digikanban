<?php 

if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

// require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
// $extrafields        = new ExtraFields($db);
// $extrafields->fetch_name_optionals_label($tasks->table_element);

require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
// dol_include_once('/digikanban/class/digikanban.class.php');


$extrafields        = new ExtraFields($db);
$tasks = new Task($db);
$extrafields->fetch_name_optionals_label($tasks->table_element);

// $kanban   = new digikanban($db);
// $status_date;

top_httphead('text/javascript; charset=UTF-8');

global $langs;

$langs->load('digikanban@digikanban');
$langs->loadLangs(array('projects','mails'));

$nbrheurstravail = ($conf->global->KANBAN_NOMBRE_HEURES_DE_TRAVAIL_PAR_JOUR > 0) ? $conf->global->KANBAN_NOMBRE_HEURES_DE_TRAVAIL_PAR_JOUR : 0;
$refreshpageautomatically = isset($conf->global->DIGIKANBAN_REFRESH_PAGE_AUTOMATICALLY) ? $conf->global->DIGIKANBAN_REFRESH_PAGE_AUTOMATICALLY : 1;
?>

jQuery(document).ready(function() {

	var refreshpageautomatically = <?php echo (int) $refreshpageautomatically; ?>;

	$(".jsselect2 select").select2();
	$("#search_year").select2();
	$("#modele").select2();
	$('.opensearch').click(function(){
		$('#kabantask').removeClass('kanbanclosedsearchdiv');
		$('.kanbanfilterdiv').show();
		$('.opensearch').addClass('unvisible');
		$('.closesearch').removeClass('unvisible');
	});

	$('.closesearch').click(function(){
		$('#kabantask').addClass('kanbanclosedsearchdiv');
		$('.kanbanfilterdiv').hide();
		$('.closesearch').addClass('unvisible');
		$('.opensearch').removeClass('unvisible');
	});


	$('#select_months').select2();
	$('.select_proj_visibl').select2();
	$('.select_affecteduser').select2();
	$('#search_tasktype').select2();
	

	$('#search_status, .search_category, #search_customer, #search_userid, .select_proj_visibl, #search_tasktype').on('change', function() {
    	digikanban_refreshfilter();
	});
	$('.select_affecteduser').on('change', function() {
		if(refreshpageautomatically) {
			$('.digikanbanformindex').submit();
		}
	});
	$('#selectallprojects').click(function(event) {
		event.preventDefault();
		digikanban_refreshfilter(selectallornone = 1);
	});
	$('#selectnoneprojects').click(function(event) {
		event.preventDefault();
		digikanban_refreshfilter(selectallornone = 2);
	});



	$("form#form_progress_tasks").submit(function(e) {
	    e.preventDefault();
	});
	$(".kanban_txt_comment textarea").keypress(function() {
		$('.cancelcomment').show();
	});


	$("body").click(function(e) {
		// console.log(e.target);
		if(!$(e.target).parents('.kanban_tooltipstatus').length){
			$('.kanban_tooltipstatus').remove();
		}
		
	});

});

function dropdrag_tags() {
	
	console.log("dropdrag_tags");

	$( "ul.list_tags").sortable({
		connectWith: ".list_tags", 
		placeholder: "ui-state-highlight", 
		items: "li.tagstask", 
		stop: function( event, ui ) {
			updatenumtags();
		}
	});

}

function updatenumtags() {
	$('.multiselectcheckboxtags li.tagstask').each(function(e, v){
		var i = e+1;
		console.log(i);
		$(this).find('.numtag').val(i);
		$(this).find('a.edittag').attr('data-num', i);
		$(this).find('a.updatetag').attr('data-num', i);
		$(this).find('a.canceltag').attr('data-num', i);
	});
}

function dropdrag_checklist() {

	console.log("dropdrag_checklist");
	$( "ul.list_checklist").sortable({
		connectWith: ".list_checklist", 
		placeholder: "ui-state-highlight", 
		items: "li.checklist", 
		stop: function( event, ui ) {
			updatenumcheck();
		}
	});

}


function updatenumcheck() {
	$('.multiselectcheckboxtags li.checklist').each(function(e, v){
		var i = e+1;
		console.log(i);
		$(this).find('.numcheck').val(i);
		$(this).find('a.editcheck').attr('data-num', i);
		$(this).find('a.updatecheck').attr('data-num', i);
		$(this).find('a.cancelcheck').attr('data-num', i);
	
	});
}

function getalltagkanban() {
	var selectedtags = $('#search_tags').val();
	var debutmonth = $('#debutmonth').val();
	var debutyear = $('#debutyear').val();
	var finmonth = $('#finmonth').val();
	var finyear = $('#finyear').val();
	var search_projects = $('select[name="search_projects[]"]').val();

	$.ajax({
        data:{
        	'action': 'getalltag'
        	,'selectedtags': selectedtags
        	,'search_projects': search_projects
        	,'debutmonth': debutmonth
        	,'debutyear': debutyear
        	,'finmonth': finmonth
        	,'finyear': finyear
        },
		url: "<?php echo dol_buildpath('/digikanban/check.php',1); ?>",

        type:'POST',
        // dataType:'json',
        success:function(returned){
        	// console.log(returned);
            if(returned) {
				$('#filtertags').html(returned);
            }
        }
    });
}

function changeInputDatePickerData(that) {
	$(that).addClass('donotsubmit');

	var content = $(that).val().trim().split('/');

	month = content[0]; year = content[1];

	if(month && month.length == 2 && year && year.length == 4) {

		$(that).datepicker('option', 'defaultDate', new Date(year, month-1, 1));
        $(that).datepicker('setDate', new Date(year, month-1, 1));

        $('#'+($(that).attr('id'))+'month').val(month);
        $('#'+($(that).attr('id'))+'year').val(year);

        // console.log('month : '+month);
        // console.log('year : '+year);
	}
}

function submitFormWhenChange(wait = 0) {

	var refreshpageautomatically = <?php echo (int) $refreshpageautomatically; ?>;

	if(!refreshpageautomatically) return 0;
	
	var timeout = 0;
	if(wait > 0) {
		timeout = 200;
	}

	if($('fieldset .date_picker').hasClass('donotsubmit')) return 0;

	setTimeout(
  		function(){ 
			$('.digikanbanformindex').submit();
  		}, timeout
  	);
}

function digikanban_refreshfilter(selectallornone = 0) {
	var search_customer 	= $('#search_customer').val();
	var search_userid 		= $('#search_userid').val();
	var search_category 	= $('#search_category').val();
	var search_status 		= $('#search_status').val();
	var search_projects 	= $('select[name="search_projects[]"]').val();
	var search_tasktype 	= $('#search_tasktype').val();
	var search_affecteduser	= $('#search_affecteduser').val();
	var debutyear 			= $('#debutyear').val();
	var debutmonth 			= $('#debutmonth').val();
	var finyear 			= $('#finyear').val();
	var finmonth 			= $('#finmonth').val();

	var refreshpageautomatically = <?php echo (int) $refreshpageautomatically; ?>;

    // $('select[name="search_projects[]"]').select2("val", "");

	$.ajax({
        data:{
        	'action': 'refreshfilter'
        	,'search_customer': search_customer
			,'search_userid': search_userid
			,'search_category': search_category
			,'search_status': search_status
			,'search_projects': search_projects
			,'search_tasktype': search_tasktype
			,'search_affecteduser': search_affecteduser
			,'debutyear': debutyear
			,'debutmonth': debutmonth
			,'finyear': finyear
			,'finmonth': finmonth
			,'selectallornone': selectallornone
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
    	dataType:'json',
        success:function(returned){
            if(returned) {

            	// console.log(returned);
            	
				// ------------------------------------------------------------------ Project Select
            	if(returned['selectprojects'] !== undefined) {
            		$('#digikanbanselectprojectsauthorized').html(returned['selectprojects']);
            		$('#digikanbanselectprojectsauthorized select').select2();
            	}

				// ------------------------------------------------------------------ Affect User Select
            	if(returned['selectusers'] !== undefined) {
            		$('#digikanban_users_as_taskcontact').html(returned['selectusers']);
            		$('#digikanban_users_as_taskcontact select').select2();
            	}

				// ------------------------------------------------------------------ Refresh page automatically
            	if(refreshpageautomatically) {
			    	$('.digikanbanformindex').submit();
			    }

            }
        }
    });
}

function projet_choose_change(id_tache = ''){

	if(!id_tache) {
		pleaseBePatientJs();
		$('.filter_in_tasks').val('');
		$('#nbr_task_parent>b>span').html('0');
		$('.todo_content .contents .scroll_div').html('');
	}

	// get_contacts_users_project();

	var search_status = $('#search_status').val();
	var search_affecteduser = $('select[name="search_affecteduser[]"]').val();
	var search_projects = $('select[name="search_projects[]"]').val();

	var debutmonth = $('#debutmonth').val();
	var debutyear = $('#debutyear').val();
	var finmonth = $('#finmonth').val();
	var finyear = $('#finyear').val();
	var search_tags = $('#search_tags').val();
	var search_tags = $('#search_tags').val();
	var search_tasktype = $('#search_tasktype').val();

	var search_all = $('#search_all').val();
	var sortfield = $('#sortfield').val();
	var sortorder = $('#sortorder').val();
	var progressless100 = 0;

	if($('#progressless100').is(':checked')){
		progressless100=1;
	}
	
	var data = {
		'search_status' : search_status,
		'search_affecteduser' : search_affecteduser,
		'search_projects' : search_projects,
		'search_tasktype' : search_tasktype,
		'debutmonth' : debutmonth,
		'debutyear' : debutyear,
		'finmonth' : finmonth,
		'finyear' : finyear,
		'id_tache' : id_tache,
		'search_tags' : search_tags,
		'search_all' : search_all,
		'sortfield' : sortfield,
		'sortorder' : sortorder,
		'progressless100' : progressless100,
		'action' : "getallTasks"
	};
	$.ajax({
		type: "POST",
		url: "<?php echo dol_buildpath('/digikanban/check.php',1); ?>",
		data: data, 
		dataType: 'json',
		success: function(found){
			if (found != '') {

				$.each( found, function( key, value ) {
					var k = key.replace(/"/g,'');

					if(id_tache) {
						// $('.todo_content .contents .scroll_div .list-card[id="task_'+id_tache+'"]').remove();
						// $('.todo_content #'+k+' .contents .scroll_div').prepend(value);
						if($('.todo_content .contents .scroll_div .list-card[id="task_'+id_tache+'"]').length>0){
							$('.todo_content .contents .scroll_div .list-card[id="task_'+id_tache+'"]').replaceWith(value);
						}else
							$('.todo_content #'+k+' .contents .scroll_div').prepend(value);
						countEachColumnNumbers();
					} else {
						$('.todo_content #'+k+' .contents .scroll_div').html(value);
						var nb = $('#'+k+' .list-card').length;
						$('#nbr_'+k).html('<?php echo trim(addslashes($langs->trans("nbrelements"))); ?> '+nb);
						$('.todo_content #'+k+' .contents .scroll_div').scrollTop(0);
					}


				})


				// $('#nbr_task_parent>b>span').html(found['tototot']);
				// var ToDo = $('#ToDo .list-card').length;
				// var EnCours = $('#EnCours .list-card').length;
				// var AValider = $('#AValider .list-card').length;
				// var Validé = $('#Validé .list-card').length;
				// $('#nbr_todo').html('<?php echo trim(addslashes($langs->trans("nbrelements"))); ?> '+ToDo);
				// $('#nbr_encours').html('<?php echo trim(addslashes($langs->trans("nbrelements"))); ?> '+EnCours);
				// $('#nbr_avalider').html('<?php echo trim(addslashes($langs->trans("nbrelements"))); ?> '+AValider);
				// $('#nbr_valider').html('<?php echo trim(addslashes($langs->trans("nbrelements"))); ?> '+Validé);
			}else{
			  //   	console.log('Error');
			  //   $(".todo_content .columns_").each(function(){
			  //   	var id = $(this).data('etat');
			  //   	console.log('id'+id);
					// $('#'+id+' .contents .scroll_div').append('<div> <a id="addtask" onclick="addtask(this)"><?php echo '<span class="fas fa-plus"></span> '.$langs->trans("Addtask")?></a></div>');

			  //   })
				// $('#nbr_todo').html('<?php echo trim(addslashes($langs->trans("nbrelements"))); ?> 0');

			}

			$(".todo_content .columns_").each(function(){
		    	var id = $(this).data('etat');
				// $('#'+id+' .contents .scroll_div').append('<div> <a id="addtask" onclick="addtask(this)"><?php echo '<span class="fas fa-plus"></span> '.$langs->trans("Addtask")?></a></div>');

		    });
			// getalltagkanban();
			applydraggable();
			applyJsToolTipForOneContent(id_tache);
			$('.todo_content .tabtask').mousedown(function(){$(".actif_onitem").removeClass("actif_onitem");$(this).addClass("actif_onitem");});

			countEachColumnNumbers();
			

			// $('.descptask').on({
			//     mouseenter: function () {
			//     	title = $(this).data('title');
					
			// 		var x = $(this).offset();

			// 		html ='<div class="kanban_tooltip" style="top: '+x.top+'px;left: '+x.left+'px">';
			// 			html += '<div class="kanbanpophover">';
			// 			html += '<span class="gTtTitle"><b><?php echo $langs->trans('Description') ?>:</b></span>';
			// 			html += '<span class="gTILine">'+title+'</span>';
			// 			html += '</div>';
			// 		html += '</div>';

			// 		$('body').append(html);
			// 	        //stuff to do on mouse enter

			//     },
			//     mouseleave: function () {
			//         $('.kanban_tooltip').remove();
			//         //stuff to do on mouse leave
			//     }
			// });

			$('.list-card').on({
			    mouseenter: function () {
			        $(this).find('.edittask').show();

			    },
			    mouseleave: function () {
			        $(this).find('.edittask').hide();
			    }
			});


			// $('.lbl_task').on({
			//     mouseenter: function () {
			// 		console.log('Tessssst');
			// 		showinfotask($(this));

			//     },
			//     mouseleave: function () {
			//         $('.kanban_tooltip').remove();
			//         //stuff to do on mouse leave
			//     }
			// });

			// $(".lbl_task").hover(function() {
			// var current_task = $(this);
			// timeouthover = setTimeout(function(){
			// showinfotask(current_task);
			// },100);

			// }, function() {
			// clearTimeout(timeouthover);
			// $('.kanban_tooltip').remove();
			// });

			$(".classfortooltip").tooltip({
				show: { collision: "flipfit", effect:"toggle", delay:50, duration: 20 },
				hide: { delay: 50, duration: 20 },
				tooltipClass: "mytooltip",
				content: function () {
		    		// console.log("Return title for popup");
		            return $(this).prop("title");		/* To force to get title as is */
		   		}
			});

			if(id_tache) {
				$('#task_'+id_tache).addClass('kanbancurrenttaskactive');
			}
			$('.blockUI').remove();
		}
		,error: function(XMLHttpRequest, textStatus, errorThrown) { 
	        $('.blockUI').remove();
	    }    
	});
}

function tooglekanbancurrenttaskactive(element) {
	$("#kabantask .list-card").removeClass('kanbancurrenttaskactive');
	$(element).addClass('kanbancurrenttaskactive');
}

function clonertask(that) {
	// console.log('clonertask');
	var x = $(that).offset();
	var id_tache = $(that).data('id');
	var colomn = $(that).data('colomn');
	var search_affecteduser = $('select[name="search_affecteduser[]"]').val();

    // $('#task_'+id_tache).css('background', '#c4c4d5 !important');
    // $('#task_'+id_tache).attr("style", "background: #c4c4d5 !important");
    $("#kabantask .list-card").removeClass('kanbancurrenttaskactive');

	$.ajax({
        data:{
        	'action': 'clonertask'
        	,'colomn': colomn
        	,'id_tache': id_tache
        	,'search_affecteduser': search_affecteduser
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        // dataType:'json',
        success:function(returned){
            if(returned) {
            	$('body').append('<div class="window-overlay" id="poptasks"><div id="kanban_new_task">'+returned+'</div></div>');
            	$('#kanban_new_task select').select2();

    			tooglekanbancurrenttaskactive(returned['taskid']);
            	datepickerStartDateEndDate();
            }
        }
    });

	$('#poptasks').show();
	$('#ui-datepicker-div').removeClass('month_year_datepicker');
}

function createtask(that) {
	checkPeriodWithPreviousDays();
	kanbanActionTask('createtask');
}

function update_task(that) {
	checkPeriodWithPreviousDays();
	kanbanActionTask('update_task');
}

function cloner_task(that) {
	checkPeriodWithPreviousDays();
	kanbanActionTask('cloner_task');
}

function kanbanActionTask(action, task=[]) {

	var search_affecteduser = $('select[name="search_affecteduser[]"]').val();
	var users_tasks = $('input#users_tasks').val();
	var fk_projet = $('select[name="fk_projet"]').val();
	var colomn    = $('#idcolomn').val();

	var id_tache = $('input[name="fk_task"]').val();
	var label = $('input[name="label"]').val();
	var budget = $('input[name="budget"]').val();
	var durehour = $('input[name="planned_workloadhour"]').val();
	var duremin = $('input[name="planned_workloadmin"]').val();
	var descp = $('textarea[name="description"]').val();
	var progress = $('select[name="progress"]').val();

	var userid = $('select[name="userid"]').val();
	var usercontact = $('select[name="usercontact[]"]').val();

	var startmin = $('#date_startmin').val();
	var starthour = $('#date_starthour').val();
	var startday = $('#date_startday').val();
	var startyear = $('#date_startyear').val();
	var startmonth = $('#date_startmonth').val();

	var endmin = $('#date_endmin').val();
	var endhour = $('#date_endhour').val();
	var endday = $('#date_endday').val();
	var endyear = $('#date_endyear').val();
	var endmonth = $('#date_endmonth').val();

	var jalonday = $('#options_ganttproadvanceddatejalonday').val();
	var jalonmonth = $('#options_ganttproadvanceddatejalonmonth').val();
	var jalonyear = $('#options_ganttproadvanceddatejalonyear').val();
	// console.log('datejalon'+datejalon);

	$('#kanban_new_task .butAction:not(#cancel_task)').removeClass('butAction').addClass('butActionRefused');

	$.ajax({
        data:{
        	'action': action
        	,'colomn': colomn
        	,'label': label
        	,'budget': budget
        	,'id_tache': id_tache
        	,'search_affecteduser': search_affecteduser
        	,'fk_projet': fk_projet
        	,'users_tasks': users_tasks
        	,'durehour': durehour
        	,'duremin': duremin
        	,'progress': progress
        	,'userid': userid
        	,'usercontact': usercontact
        	,'endmin': endmin
        	,'endhour': endhour
        	,'endday': endday
        	,'endmonth': endmonth
        	,'endyear': endyear
        	,'startmin': startmin
        	,'starthour': starthour
        	,'startday': startday
        	,'startmonth': startmonth
        	,'startyear': startyear
        	,'description': descp
        	,'jalonday': jalonday
        	,'jalonmonth': jalonmonth
        	,'jalonyear': jalonyear


        	<?php
	        	if($extrafields->attributes[$tasks->table_element]['label']){

					foreach ($extrafields->attributes[$tasks->table_element]['label'] as $key => $value) {

						$visibi = $extrafields->attributes[$tasks->table_element]['list'][$key];
						if(!$visibi) continue;

						if($extrafields->attributes[$tasks->table_element]['type'][$key] == 'boolean'){
							?>
							,'options_<?php echo $key; ?>': ($('#options_<?php echo $key; ?>').is(":checked")) ? 1 : 'NULL'
							<?php
						}
						elseif($extrafields->attributes[$tasks->table_element]['type'][$key] == 'date' || $extrafields->attributes[$tasks->table_element]['type'][$key] == 'datetime'){
							?>
							,'options_<?php echo $key; ?>day': $('#options_<?php echo $key; ?>day').val()
							,'options_<?php echo $key; ?>month': $('#options_<?php echo $key; ?>month').val()
							,'options_<?php echo $key; ?>year': $('#options_<?php echo $key; ?>year').val()
							<?php
							if($extrafields->attributes[$tasks->table_element]['type'][$key] == 'datetime') {
								?>
								,'options_<?php echo $key; ?>hour': $('#options_<?php echo $key; ?>hour').val()
								,'options_<?php echo $key; ?>min': $('#options_<?php echo $key; ?>min').val()
								<?php
							}
						}else{
							?>
							,'options_<?php echo $key; ?>': $('#options_<?php echo $key; ?>').val()
							<?php

						}
					}
	        	}

			?>

        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        dataType:'json',
        success:function(returned){
            if(returned) {

            	if(returned['errormsg']) {
            		$.jnotify(returned['errormsg'], "error", false );
            	}else{
					$('#kanban_new_task').find('textarea').val('');
					$('#kanban_new_task').find('input').val('');
					$('#kanban_new_task').find('select').val('');
					
					// console.log(returned);
					if(action == 'createtask' || action == 'cloner_task') {
						id_tache = returned['taskid'];
					}
					$('#poptasks').remove();
					projet_choose_change(id_tache);
            	}

            	if(returned['msg']) {
            		$.jnotify(returned['msg'], "500", false, { remove: function (){} } );
            	}

            }
        }
    });
}

function canceladdtask(that) {
	$('#poptasks').remove();
	// var id_tache = $(that).data('id');

	// $('#kanban_new_task').find('textarea').val('');
	// $('#kanban_new_task').find('input').val('');
	// // $('#kanban_new_task').find('select').select2('destroy');
	// $('#kanban_new_task').find('select').val('');
	// // $('#kanban_new_task').find('select').select2();
	// // $('#task_'+id_tache).attr("style", "background: #fff !important");
	// // console.log('#task_'+id_tache);
}

function countEachColumnNumbers(){
    $(".todo_content .columns_").each(function(){
        var numbers = $(this).find('.list-card').length
        if (numbers > 0){
            $(this).find('.filter_in_etat').html('<?php echo trim(addslashes($langs->trans("nbrelements"))); ?> '+numbers);
        }else{
            $(this).find('.filter_in_etat').html("");
        } 
        numbers = 0;
    });
}

function showinfotask(that) {
	var id_task = $(that).parents('.list-card').data('rowid');
	var x = $(that).offset();

	$.ajax({
		data:{
        	'action': 'getinfotask'
        	,'id_task': id_task
        	,'top': x.top+30
        	,'left': x.left+30
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
            	$('body').append(returned);
            }
        }
	})
}

function addtask(that) {

	$("#kabantask .list-card").removeClass('kanbancurrenttaskactive');

	$('body').click();
	var year = $('#search_year').val();
	// console.log(year);
	var month = $(that).data('month');
	var colomn = $(that).data('colomn');
	var search_affecteduser = $('select[name="search_affecteduser[]"]').val();
	var search_status = $('#search_status').val();
	var search_projects = $('select[name="search_projects[]"]').val();

	if($('#poptasks').length > 0) return;

	// $('#poptasks').remove();

	$.ajax({
        data:{
        	'action': 'addtask',
        	'colomn': colomn,
        	'month': month,
        	'year': year,
        	'search_projects': search_projects,
        	'search_affecteduser': search_affecteduser,
        	'search_status': search_status
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        // dataType:'json',
        success:function(returned){
            
            if(returned) {
            	$('#poptasks').remove();
            	$('body').append('<div class="window-overlay" id="poptasks"><div id="kanban_new_task">'+returned+'</div></div>');
            	$('#kanban_new_task select').select2();

            	datepickerStartDateEndDate();


            }
        }
    });
	
	$('#poptasks').show();	// $('body').append('<div class="window-overlay"></div>');




	// var x = $(that).offset();
	// var lastdate = new Date(year, month, 0);
	// lastday = lastdate.getDay();

	// $('#date_start').datepicker('setDate', new Date(year, month-1, 1));
	// $('#date_end').datepicker('setDate', new Date(year, month, 0));

	// $('#date_startday').val('1');
	// $('#date_startmonth').val(month);
	// $('#date_startyear').val(year);

	// $('#date_endday').val(lastday);
	// $('#date_endmonth').val(month);
	// $('#date_endyear').val(year);
	// $('kanban_title').text('<?php echo $langs->trans("NewTask") ?>');

	$('#ui-datepicker-div').removeClass('month_year_datepicker');
}

function edittask(that) {
	var x = $(that).offset();
	var id_tache = $(that).data('id');
	var colomn = $(that).data('colomn');
	var search_affecteduser = $('select[name="search_affecteduser[]"]').val();

    // $('#task_'+id_tache).css('background', '#c4c4d5 !important');
    // $('#task_'+id_tache).attr("style", "background: #c4c4d5 !important");
    tooglekanbancurrenttaskactive('#task_'+id_tache);

	$.ajax({
        data:{
        	'action': 'edittask'
        	,'id_tache': id_tache
        	,'colomn': colomn
        	,'search_affecteduser': search_affecteduser
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        // dataType:'json',
        success:function(returned){
            if(returned) {
            	$('body').append('<div class="window-overlay" id="poptasks"><div id="kanban_new_task">'+returned+'</div></div>');
            	$('#kanban_new_task select').select2();

            	datepickerStartDateEndDate();
            }
        }
    });

	$('#poptasks').show();
	// $('body').append('<div class="window-overlay"></div>');
	// $('#kanban_new_task').css({'top': 80, 'left': 320});

	$('#ui-datepicker-div').removeClass('month_year_datepicker');
}

// Mise a jour commentaires

function popcomments(that) {
	var id_task = $(that).data('id');
	$('#id_task').val(id_task);
	$.ajax({
		data:{
        	'action': 'addcomment'
        	,'id_task': id_task
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
            	$('body').append(returned);
            }
			loadcomments();
        }
	})
}
	
function countCurrentComments() {

	var id_tache = $('#id_task').val();


	var txtcomm = '';
}

function keypressComment(that) {
	$(that).parent('.kanban_txt_comment').find('.cancelcomment').show();
}

function editcomment (that) {
	$('.update_comment').hide();
	$('.show_comment').show();
	$(that).parents('.kanban_show_comment').find('.update_comment').show();
	$(that).parents('.kanban_show_comment').find('.show_comment').hide();
}

function cancelcomment(that) {
	$('.kanban_txt_comment textarea').val('');
	$('.cancelcomment').hide();
}

function closecomments(that) {
	countCurrentComments();

	$('.kanban_txt_comment textarea').val('');
	$('.cancelcomment').hide();
	$('#popcomments').remove();
}

function loadcomments(that) {
	var id_task = $('#id_task').val();

	tooglekanbancurrenttaskactive('#task_'+id_task);
	console.log('load');
	$.ajax({
        data:{
        	'action': 'loadcomment'
        	,'id_task': id_task
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        dataType: 'Json',
        success:function(returned){
            if(returned) {
            	// console.log(returned);
            	$('.kanban_list_comments').html(returned['html']);
				if(returned['title']) $('#task_'+id_task+' .comments.animation_kanban').attr('title', returned['title']);
				txtcomm = (returned['nbcomment'] < 9) ? returned['nbcomment'] : '+9';
				$('#task_'+id_task+' .kanbancountcomments').html(txtcomm);

            }else{
            	$('.kanban_list_comments').html('');
            }
        }
    });
	$('.cancelcomment').hide();
}

function savecomment(that) {
	var comment = $('#txt_comment').val();
	var id_task = $('#id_task').val();

	if(!comment) return;

	$.ajax({
        data:{
        	'action': 'savecomment'
        	,'id_task': id_task
        	,'comment': comment
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
				$('#txt_comment').val('');
				$('#txt_comment').text('');
				cancelcomment();
				if(returned['msg']) {
					$.jnotify(returned['msg'],
							"500",
							false,
							{ remove: function (){} } );

					
            	}
            }
        	// closecomments();
        	loadcomments();
        }
    });
	$('.cancelcomment').hide();
}

function deletecomment(that) {
	var id_comment = $(that).data('id');
	$.ajax({
        data:{
        	'action': 'deletecomment'
        	,'id_comment': id_comment
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
				if(returned['msg']) {
					
					$.jnotify(returned['msg'],
							"500",
							false,
							{ remove: function (){} } );
            	}
            }
			loadcomments();
        }
    });
	// $('.cancelcomment').hide();
}

function cancelupdatecomment(that) {
	$('.update_comment').hide();
	$(that).parents('.kanban_show_comment').find('.show_comment').show();
}

function updatecomment(that) {
	var id_comment = $(that).data('id');
	var comment = $('.comment_'+id_comment).val();
	$.ajax({
        data:{
        	'action': 'updatecomment'
        	,'id_comment': id_comment
        	,'comment': comment
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
				if(returned['msg']) {
					
					$.jnotify(returned['msg'],
							"500",
							false,
							{ remove: function (){} } );
            	}
            }
        	loadcomments();
        }
    });
	$('.cancelcomment').hide();
}

function checkPeriodWithPreviousDays() {

	<?php if(!$nbrheurstravail) { ?>
		return;
	<?php } ?>

	labeltask = $('input[name="label"]').val();
	dure_h = $('input.inputhour').val();
    dure_min = ($('input.inputminute').val()/60);

    previousdure = parseInt(dure_h) + parseInt(dure_min);

    // console.log(previousdure);
    date_start = $('input#date_start').val();
	date_end = $('input#date_end').val();

	dDate1 = new Date($('#date_startyear').val()+'-'+$('#date_startmonth').val()+'-'+$('#date_startday').val());
    dDate2 = new Date($('#date_endyear').val()+'-'+$('#date_endmonth').val()+'-'+$('#date_endday').val());

	var iWeeks, iDateDiff, iAdjust = 0;

	if(date_start == date_end) {
		totdays = 1;
	}

 	else if (dDate2 < dDate1) {
		totdays = -1; // error code if dates transposed
	}

	else {
		var iWeekday1 = dDate1.getDay(); // day of week
		var iWeekday2 = dDate2.getDay();
		iWeekday1 = (iWeekday1 == 0) ? 7 : iWeekday1; // change Sunday from 0 to 7
		iWeekday2 = (iWeekday2 == 0) ? 7 : iWeekday2;
		if ((iWeekday1 > 5) && (iWeekday2 > 5)) iAdjust = 1; // adjustment if both days on weekend
		iWeekday1 = (iWeekday1 > 5) ? 5 : iWeekday1; // only count weekdays
		iWeekday2 = (iWeekday2 > 5) ? 5 : iWeekday2;

		// calculate differnece in weeks (1000mS * 60sec * 60min * 24hrs * 7 days = 604800000)
		iWeeks = Math.floor((dDate2.getTime() - dDate1.getTime()) / 604800000)

		if (iWeekday1 < iWeekday2) { //Equal to makes it reduce 5 days
		iDateDiff = (iWeeks * 5) + (iWeekday2 - iWeekday1)
		} else {
		iDateDiff = ((iWeeks + 1) * 5) - (iWeekday1 - iWeekday2)
		}

		iDateDiff -= iAdjust // take into account both days on weekend

		totdays = (iDateDiff + 1); // add 1 because dates are inclusive
	}

	if(totdays > 0 && previousdure > 0) {
		var tothours = totdays*<?php echo (int) $nbrheurstravail; ?>;

		if(tothours == previousdure) return;

		var msgtoshow = '';
		var condimsg = '';

		if(tothours > previousdure) {
			// condimsg += '<?php echo dol_escape_js($langs->transnoentities("alert_depasse_dure")); ?> : ';
		}
		else if(tothours < previousdure) {
			condimsg += '<?php echo dol_escape_js($langs->transnoentities("SelectedHoursLessThanPlannedWorkload")); ?> : ';
		}

		if(condimsg) {
			msgtoshow += '<div class="smallsizetext">';
			if(labeltask) {
				msgtoshow += '<b>'+labeltask+'</b>';
				msgtoshow += '<br>';
			}
			msgtoshow += condimsg;
			msgtoshow += '<div><?php echo dol_escape_js($langs->transnoentities("PlannedWorkload")); ?> : '+previousdure + ' <?php echo dol_escape_js($langs->transnoentities("Hours")); ?></div>';
			msgtoshow += '<div><?php echo dol_escape_js($langs->transnoentities("SelectedPeriod")); ?> : '+tothours + ' <?php echo dol_escape_js($langs->transnoentities("Hours")); ?></div>';
			msgtoshow += '</div>';

			$(".jnotify-notification").remove();
			$.jnotify(msgtoshow, 'warning', true);
		}
	}
}

// Mise a jour etiquettes

function addtags(that) {
	var id_tache = $(that).data('id');
	var namemodal = $('#modalkanban option:selected').text();
	var id_modal = $(that).data('modal');
	
	var id_colomn = $(that).data('colomn');
	var cl = '';
		
	tooglekanbancurrenttaskactive('#task_'+id_tache);

	$.ajax({
        data:{
        	'action': 'addtags'
        	,'id_tache': id_tache
        	,'id_modal': id_modal
        	,'namemodal': namemodal
        	,'id_colomn': id_colomn
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        // dataType:'json', 
        success:function(returned){
        	$(this).parents('.list-card').addClass("actif_onitem");
            if(returned) {
            	if(id_modal>0){
            		cl ='popmodal';
            	}else{
					$('#poptasks').remove();
            	}

            	$('body').append('<div class="window-overlay '+cl+'" id="poptasks"><div id="kanban_new_task"><input type="hidden" id="id_tache" name="id_tache" value="'+id_tache+'">'+returned+'</div></div>');
            }
            $('input#txt_searchtag').focus();
			dropdrag_tags();
        }
    });

	$('#poptasks').show();
}


function inSearchTag(that) {

    var searchword = $(that).val().toLowerCase();
    // console.log('inSearchTag: '+searchword);
    $('.multiselectcheckboxtags li.tagstask').addClass('hidden');
    if($(that).val() === '') $('.multiselectcheckboxtags li.tagstask').removeClass('hidden');
    
    $('.multiselectcheckboxtags li.tagstask').each(function(){
        var txtsrch = $(this).find('.lbl_tag').text().toLowerCase();
        var arrtxt = searchword.split(' ');

        var onetxtexist = 1;
        var showtag = 0;
      
        for(var i=0; i< arrtxt.length; i++){
           
            if(onetxtexist > 0 && txtsrch.indexOf(arrtxt[i]) > -1){
                showtag = 1;
            }else{
                onetxtexist = 0;
                showtag = 0;
                break;
            }
          
        }
      
        if(showtag > 0){
          $(this).removeClass('hidden');
        }
        
    });

}

function removesearch(that) {
	$('#txt_searchtag').val('');
	$('li.tagstask.hidden').removeClass('hidden');
}
	
function createtag(that) {
	var newtag = '<div class="add_tags">';
	newtag += '<table class="tags_task"><tr>';
            newtag += '<td style="width:12%">';
                newtag += '<input type="color" id="colortag" value="#dddddd" >';
            newtag += '</td>';
            newtag += '<td>';
                newtag += '<input type="text" id="newtag" name="newtag" value="" style="border-radius:4px !important;" placeholder="<?php echo $langs->trans('NewTag');?>" />';
            newtag += '</td>';
            newtag += '<td style="width:5%" align="center">';
                newtag += '<a class="cursorpointer_task addtags" onclick="NewTags(this)" title="<?php echo $langs->trans('Save'); ?>"><span class="fas fa-plus"></span></a>';
            newtag += '</td>';
            newtag += '<td style="width:5%" align="center">';
                newtag += '<a class="cursorpointer_task cancelnewtag" onclick="CancelNewTags()" title="<?php echo $langs->trans('Cancel'); ?>"><span class="fas fa-times-circle"></span></a>';
            newtag += '</td>';
        newtag += '</tr></table>';
	newtag += '</div>';
	$('.createtag').addClass('hidden');
	$('.kanban_btn_set button').addClass('hidden');
	$('.multiselectcheckboxtags').after(newtag);
	$('#newtag').focus();
}
	
function NewTags(that) {
	var label = $('#newtag').val();
	var color = $('#colortag').val();
	var numtag = parseInt($('ul.list_tags li').length)+1;

	if(!label) return '';
	
	c= color.substring(1).split('');
    if(c.length== 3){
        c= [c[0], c[0], c[1], c[1], c[2], c[2]];
    }
    c= '0x'+c.join('');
    var bgcolor = 'rgba('+[(c>>16)&255, (c>>8)&255, c&255].join(',')+',0.3)';

	var id_tache = $('#id_tache').val();
	var nb = $('.list_tags li.newtags').length;

	newid = 'new_'+nb;

	var tag = '<li class="tagstask newtags" id="tag_'+newid+'">';
        tag += '<table class="tags_task"><tr>';
            tag += '<td class="width20px">';
				tag += '<input type="hidden" class="numtag" id="numtag_'+newid+'" data-id="'+newid+'" name="newtags[numtag]['+newid+']" value="'+numtag+'" />';
            	tag += '<input type="checkbox" class="cursorpointer_task check_list" id="checkbox_newtag_'+newid+'" data-id="'+newid+'" name="newtags[checked]['+newid+']" value="1" />';
            tag += '</td>';
            tag += '<td>';
                tag += '<input type="hidden" id="label_tagstask_'+newid+'" name="newtags[label]['+newid+']" value="'+label+'" >';
                tag += '<input type="hidden" id="color_tagstask_'+newid+'" name="newtags[color]['+newid+']" value="'+color+'" >';
            	tag += '<label class="cursorpointer_task" for="checkbox_newtag_'+newid+'">';
                    tag += '<div style="background: '+bgcolor+'" class="tagstask">';
                        tag += '<span style="background:'+color+';"></span>';
                        tag += '  <span class="lbl_tag">'+label+'</span>';
                    tag += '</div>';
                tag += '</label>';
            tag += '</td>';
            tag += '<td class="width50px center">';
            tag += '<a class="removetags" onClick="removetags(this)"><?php echo img_delete(); ?>';
            tag += '<a class="edittag" data-id="'+newid+'" data-num="'+numtag+'" onClick="edittag(this,1)"><?php echo img_edit(); ?>';
            tag += '</td>';
        tag += '</tr></table>';
    tag += '</li>';
    $('#newtag').val('');
    $('#colortag').val('#dddddd');
	$('.list_tags').append(tag);
	$('.add_tags').remove();
	$('.kanban_btn_set button').removeClass('hidden');

	$('.createtag.hidden').removeClass('hidden');
}

function CancelNewTags() {
	$('.add_tags').remove();
	$('.createtag.hidden').removeClass('hidden');
	$('.kanban_btn_set button').removeClass('hidden');
}

function cancelchangetags(that) {

	var id_modal = $(that).data('modal');

	$('.newtags').remove;
	$('#newtag').val('');
	$('#colortag').val('#dddddd');
	if(id_modal > 0){
		$('.popmodal').remove();
	}else{
		$('#poptasks').remove();
	}
}

function removetags(that) {
	$(that).parents('.newtags').remove();
	updatenumtags();
}
function editnewtags(that) {
	$(that).parents('.newtags').remove();
}

function deletetag(that) {
	var id = $(that).data('id');
	var tagsdeleted = $('.tagsdeleted').val();
	if(id)
	tagsdeleted=tagsdeleted+','+id;
	$('.tagsdeleted').val(tagsdeleted);
	$(that).parents('li.tagstask').remove();
	updatenumtags();
}

function savetags(that) {

	var id_colomn = $(that).data('colomn');

	var data=[];
	var datatask=[];
	var id_tache = $('#id_tache').val();
	var id_modal = $('#id_modal').val();
	var checked = 0;
	var tagstodelete = $('.tagsdeleted').val();

	zzn = 0;
	$('input[name*="newtags[checked]"]').each(function(e, v){

		var currid = $(v).data('id');

		var color = $('#color_tagstask_'+currid).val();
		var numtag = $('#numtag_'+currid).val();
		var label = $('#label_tagstask_'+currid).val();
		if($(this).is(':checked')){
			checked = 1;
		}else
			checked = 0;

		var arr = {'color': color, 'label': label, 'numtag': numtag, 'checked': checked};

		data[zzn]=arr;
		zzn++;
	});

	var tagsdeleted = '';
	var dt_addnew = data;

	$('input[name*="tagstask[checked]"]').each(function(){
		var id = $(this).data('id');
		var id_tag = $(this).data('tag');

		var color  = $('#color_tagstask_'+id).val();
		var label  = $('#label_tagstask_'+id).val();
		var numtag = $('#numtag_'+id).val();

		if($(this).is(':checked')){
			$checked = 1;
		}else{
			$checked = 0;
		}
		var arr2   = {'color': color, 'label': label, 'fk_tag': id_tag, 'numtag': numtag, 'checked': $checked};
		datatask[id]=arr2;
	});
	
	var dt_add = datatask;
	

	$.ajax({
        data:{
        	'action': 'savetags'
        	,'id_tache': id_tache
        	,'id_modal': id_modal
        	,'tagstodelete': tagstodelete
        	,'tagsdeleted': tagsdeleted
        	,'dt_add': dt_add
        	,'dt_addnew': dt_addnew
        	
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        dataType:'json',
        success:function(returned){
            if(returned) {
            	if(id_modal > 0){
					$('.popmodal').remove();
            	}else{
					$('#poptasks').remove();
					projet_choose_change(id_tache);
					if(returned['msg']) {
	            		$.jnotify(returned['msg'], "500", false, { remove: function (){} } );
	            	}
            	}
            }
			$('.actif_onitem').removeClass("actif_onitem");
			getalltagkanban();
        }
    });
}

function edittag(that, newtag = 0) {
	$('.canceltag').each(function(){
		$(this).click();
	});

	var id_tache = $('#id_tache').val();
	var id = $(that).data('id');
	var id_tag = $(that).data('tag');
	var numtag = $(that).data('num');

	var label = $('#label_tagstask_'+id).val();
	var color = $('#color_tagstask_'+id).val();

	var tag = '<table class="tags_task"><tr>';
            tag += '<td style="width:12%">';
                tag += '<input type="color" id="colortag" name="color_tagstask_'+id+'" value="'+color+'" >';
            tag += '</td>';
            tag += '<td>';
               	tag += '<input type="text" id="newtag" name="label_tagstask_'+id+'" value="'+label+'" style="border-radius:4px !important;" placeholder="<?php echo $langs->trans('NewTag')?>" />';
            tag += '</td>';
            tag += '<td class="width50px">';
            	tag += '<a class="updatetag" data-tag="'+id_tag+'" data-id="'+id+'" data-num="'+numtag+'"onClick="updatetag(this, '+newtag+')"><i class="fas fa-check"></i></a>';
            	tag += '<a class="canceltag" data-tag="'+id_tag+'" data-id="'+id+'" data-num="'+numtag+'" onClick="canceltag(this)"><i class="fas fa-undo"></i></a>';
            tag += '</td>';
        tag += '</tr></table>';
    $('li#tag_'+id).html(tag);
}

function updatetag(that, newtag = 0) {

	var id_tache = $('#id_tache').val();
	var id = $(that).data('id');
	var id_tag = $(that).data('tag');
	var numtag = $(that).data('num');

	var label = $('input[name="label_tagstask_'+id+'"]').val();
	var color = $('input[name="color_tagstask_'+id+'"]').val();

	// console.log($('input[name="color_tagstask_'+id+'"]'));
	// console.log($('input[name="label_tagstask_'+id+'"]'));

	if(!newtag) {

		$.ajax({
	        data:{
	        	'action': 'updatetag'
	        	,'id': id
	        	,'color': color
	        	,'label': label
	        	,'id_tag': id_tag
	        	,'numtag': numtag
	        	,'id_tache': id_tache
	        	
	        },
	        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
	        type:'POST',
	        success:function(returned){
	            if(returned) {
				    $('li#tag_'+id).html(returned);
	            }
	            getalltagkanban();
	        }
	    });
	} else {

		c= color.substring(1).split('');
	    if(c.length== 3){
	        c= [c[0], c[0], c[1], c[1], c[2], c[2]];
	    }
	    c= '0x'+c.join('');
	    var bgcolor = 'rgba('+[(c>>16)&255, (c>>8)&255, c&255].join(',')+',0.3)';

		var tag = '';
		
		tag += '';
		tag += '<table class="tags_task"><tr>';
		tag += '<td class="width20px">';
			tag += '<input type="hidden" class="numtag" id="numtag_'+id+'" data-id="'+id+'" name="newtags[numtag]['+id+']" value="'+numtag+'" />';
			tag += '<input type="checkbox" class="cursorpointer_task check_list" id="checkbox_newtag_'+id+'" data-id="'+id+'" checked name="newtags[checked]['+id+']" value="1" />';
		tag += '</td>';
		tag += '<td>';
		    tag += '<input type="hidden" id="label_tagstask_'+id+'" name="newtags[label]['+id+']" value="'+label+'" >';
		    tag += '<input type="hidden" id="color_tagstask_'+id+'" name="newtags[color]['+id+']" value="'+color+'" >';
			tag += '<label class="cursorpointer_task" for="checkbox_newtag">';
		        tag += '<div style="background: '+bgcolor+'" class="tagstask">';
		            tag += '<span style="background:'+color+';"></span>';
		            tag += '  <span class="lbl_tag">'+label+'</span>';
		        tag += '</div>';
		    tag += '</label>';
		tag += '</td>';
		tag += '<td class="width50px center">';
		tag += '<a class="removetags" onClick="removetags(this)"><?php echo img_delete(); ?>';
		tag += '<a class="edittag" data-id="'+id+'" data-num="'+numtag+'" onClick="edittag(this,1)"><?php echo img_edit(); ?>';
		tag += '</td>';
		tag += '</tr></table>';

		$('li#tag_'+id).html(tag);
	}
}

function canceltag(that) {
	var id_tache = $('#id_tache').val();
	var id = $(that).data('id');
	var id_tag = $(that).data('tag');
	var numtag = $(that).data('num');
	// console.log('cancel');
	$.ajax({
        data:{
        	'action': 'gettag'
        	,'id': id
        	,'id_tag': id_tag
        	,'numtag': numtag
        	,'id_tache': id_tache
        	
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
			    $('li#tag_'+id).html(returned);
            }
        }
    });
}

// Mise a jour checklist

function checklisttask(that) {
	var id_tache = $(that).data('id');
	var id_modal = $(that).data('modal');
	var namemodal = $('#modalkanban option:selected').text();

	tooglekanbancurrenttaskactive('#task_'+id_tache);
	
	var cl = '';

	$.ajax({
        data:{
        	'action': 'checklisttask'
        	,'id_tache': id_tache
        	,'id_modal': id_modal
        	,'namemodal': namemodal
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        // dataType:'json',
        success:function(returned){
        	$(this).parents('.list-card').addClass("actif_onitem");
            if(returned) {
            	if(id_modal>0){
            		cl ='popmodal';
            	}else{
					$('#poptasks').remove();
            	}
            	$('body').append('<div class="window-overlay '+cl+'" id="poptasks"><div id="kanban_new_task" class="kanban_checklist"><input type="hidden" id="id_tache" name="id_tache" value="'+id_tache+'">'+returned+'</div></div>');
				dropdrag_checklist();

            }
        }
    });

	$('#poptasks').show();
	// dropdrag_checklist();
}

function createcheck(that) {

	var id_modal = $(that).data('modal');
	var newcheck = '<div class="newchecklist">';
	
	newcheck += '<table class="checklist_task_pop"><tr>';
            newcheck += '<td class="width30px"></td>';
            newcheck += '<td>';
            	// if(id_modal)
            	// 	newcheck += '<span class="far fa-square"> </span>';
            	// else
                	newcheck += '<input type="text" id="newcheck" name="newcheck" value="" style="border-radius:4px !important;" placeholder="<?php echo $langs->trans('createcheck');?>" />';
            newcheck += '</td>';
            newcheck += '<td class="width50px center">';
                newcheck += '<a class="cursorpointer_task addtags" data-modal="'+id_modal+'" onclick="NewCheck(this)" title="<?php echo $langs->trans('Save'); ?>"><span class="fas fa-plus"></span></a>';
                newcheck += '<a class="cursorpointer_task cancelnewcheck" onclick="Cancelnewcheck()" title="<?php echo $langs->trans('Cancel'); ?>"><span class="fas fa-times-circle"></span></a>';
            newcheck += '</td>';
        newcheck += '</tr></table>';
	newcheck += '</div>';

	$('.createtag').addClass('hidden');
	$('.kanban_btn_set button').addClass('hidden');
	$('.multiselectcheckboxtags').after(newcheck);
	$('#newcheck').focus();
}

function Cancelnewcheck() {
	$('.newchecklist').remove();
	$('.createtag.hidden').removeClass('hidden');
	$('.kanban_btn_set button').removeClass('hidden');
}

function NewCheck(that) {
	var label = $('#newcheck').val();
	var color = $('#colortag').val();
	var id_modal = $(that).data('modal');

	if(!label) return '';

	var id_tache = $('#id_tache').val();
	var nb = $('.list_checklist li.newcheck').length;
	
	var numcheck = parseInt($('ul.list_checklist li').length)+1;

	newid = 'new_'+nb;

	var tag = '<li class="checklist newcheck" id="check_'+newid+'">';
        tag += '<table class="checklist_task_pop"><tr>';
            tag += '<td class="width30px center">';
            	if(id_modal > 0){
            		tag += '<span class="far fa-square"> </span>';
            		tag += '<input name="newcheck[checked]['+newid+']" data-id="'+newid+'" type="hidden" value="1">';
            	}else
	            	tag += '<input type="checkbox" class="cursorpointer_task check_list" id="checkbox_newcheck_'+newid+'" data-id="'+newid+'" onchange="calcProgress(this)" name="newcheck[checked]['+newid+']" value="1" />';
                tag += '<input type="hidden" id="label_check_'+newid+'" name="newcheck[label]['+newid+']" value="'+label+'" >';
                tag += '<input type="hidden" class="numcheck" id="numcheck_'+newid+'" name="newcheck[numcheck]['+newid+']" value="'+numcheck+'" >';
            tag += '</td>';
            tag += '<td>';
            	tag += '<label class="cursormove_task" for="checkbox_newcheck_'+newid+'">';
                    tag += ' <span class="lbl_tag">'+label+'</span>';
	            tag += '</label>';
            tag += '</td>';
            tag += '<td class="width50px center">';
            tag += '<a class="deletecheck cursorpointer_task" onClick="removecheck(this)">';
            tag += '<?php echo img_delete(); ?>';
            tag += '</a>';
            tag += '<a class="editcheck cursorpointer_task" data-id="'+newid+'" data-modal="'+id_modal+'" onClick="editcheck(this,1)"><?php echo img_edit(); ?>';
            tag += '</td>';
        tag += '</tr></table>';
    tag += '</li>';
    $('#newcheck').val('');
	$('.list_checklist').append(tag);
	$('.newchecklist').remove();
	$('.createtag.hidden').removeClass('hidden');
	$('.kanban_btn_set button').removeClass('hidden');
	calcProgress();
}

function saveckecklist(that) {
	var data=[];
	var datacheck=[];
	var id_tache = $('#id_tache').val();
	var id_modal = $(that).data('modal');
	var checked = 0;
	var checkdeleted = $('.checkdeleted').val();

	zzn = 0;
	$('input[name*="newcheck[checked]"]').each(function(e, v){

		var currid = $(v).data('id');

		var label = $('#label_check_'+currid).val();
		var numcheck = $('#numcheck_'+currid).val();

		if($(this).is(':checked')){
			checked = 1;
		}else
			checked = 0;
		var arr = {'label': label, 'checked': checked, 'numcheck':numcheck};
		data[zzn]=arr;

		zzn++;
	});

	$('input[name*="checklist[checked]"]').each(function(){
		var id = $(this).data('id');
		var label = $('#label_check_'+id).val();
		var numcheck = $('#numcheck_'+id).val();

		if($(this).is(':checked')){
			checked = 1;
		}else{
			checked = 0;
		}

		var arr2 = {'label': label, 'checked': checked, 'numcheck':numcheck};
		datacheck[id]=arr2;
		
	});
	
	var dt_newcheck = data;
	var dt_editcheck = datacheck;

	$.ajax({
        data:{
        	'action': 'saveckecklist'
        	,'id_modal': id_modal
        	,'id_tache': id_tache
        	,'checkdeleted': checkdeleted
        	,'dt_newcheck': dt_newcheck
        	,'dt_editcheck': dt_editcheck
        	
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        dataType:'json',
        success:function(returned){
            if(returned) {
            	if(id_modal>0){
            		$('.popmodal').remove();
            	}else{
					$('#poptasks').remove();
					projet_choose_change(id_tache);
					if(returned['msg']) {
	            		$.jnotify(returned['msg'], "500", false, { remove: function (){} } );
	            	}
					$('.actif_onitem').removeClass("actif_onitem");
            	}

            }
        }
    });
}

function calcProgress(that) {
	var id = $(that).data('id');
	var count = $("input.check_list").length;
	var checked = $("input.check_list:checked").length;
    var percentage = parseInt(((checked / count) * 100));
    var percent = percentage ? percentage : 0;
    // console.log(checked);
    $('.valprogress').text(percent);
    $(".progress").animate({
	    width: percent+"%",
	    opacity: 1
	}, 500 );
    if(count == checked){
	    $(".progress").css("background", "#61bd4f");

    }else
	    $(".progress").css("background", "#679fcb");

	if($(that).is(':checked')){
		check = 1;
		if(!$('li#check_'+id).hasClass('checkli_checked'))
			$('li#check_'+id).addClass('checkli_checked');
	}else{
		$('li#check_'+id).removeClass('checkli_checked');
		check = 0;
	}
}

function editcheck(that, newcheck = 0) {
	$('.cancelcheck').each(function(){
		$(this).click();
	});

	var id_modal = $(that).data('modal');
	var id_tache = $('#id_tache').val();
	var id = $(that).data('id');
	var numcheck = $(that).data('num');
	var label = $('#label_check_'+id).val();

	var tag = '<table class="checklist_task_pop"><tr>';
        tag += '<td class="width30px"></td>';
        tag += '<td>';
           	tag += '<input type="text" id="newcheck" name="label_check_'+id+'" value="'+label+'" style="border-radius:4px !important;" placeholder="<?php echo $langs->trans('Check')?>" />';
        tag += '</td>';
       
        tag += '<td class="width50px center">';
        	if(id_modal > 0){
        		tag += '<a class="updatecheck" data-id="'+id+'" data-modal="'+id_modal+'" onClick="updatecheckmodal(this)"><i class="fas fa-check"></i></a>';
        		tag += '<a class="cancelcheck" data-id="'+id+'" data-modal="'+id_modal+'" data-label="'+label+'"  onClick="cancelcheckmodal(this)"><i class="fas fa-undo"></i></a>';
        	}
        	else{
        		tag += '<a class="updatecheck" data-id="'+id+'" data-num="'+numcheck+'" onClick="updatecheck(this, '+newcheck+')"><i class="fas fa-check"></i></a>';
        		tag += '<a class="cancelcheck" data-id="'+id+'" data-num="'+numcheck+'" onClick="cancelcheck(this)"><i class="fas fa-undo"></i></a>';
        	}
        tag += '</td>';
    tag += '</tr></table>';

    $('li#check_'+id).html(tag);
    $('input[name="label_check_'+id+'"]').focus();
}

function updatecheckmodal(that){

	var id = $(that).data('id');
	var id_modal = $(that).data('modal');

	var label = $('input[name="label_check_'+id+'"]').val();

	var tag = '<li class="checklist newcheck" id="check_'+id+'">';
        tag += '<table class="checklist_task_pop"><tr>';
            tag += '<td class="width30px center">';
        		tag += '<span class="far fa-square"> </span>';
        		tag += '<input name="newcheck[checked]['+id+']" data-id="'+id+'" type="hidden" value="1">';
                tag += '<input type="hidden" id="label_check_'+id+'" name="newcheck[label]['+id+']" value="'+label+'" >';
            tag += '</td>';
            tag += '<td>';
            	tag += '<label class="cursormove_task" for="checkbox_newcheck_'+id+'">';
                    tag += ' <span class="lbl_tag">'+label+'</span>';
	            tag += '</label>';
            tag += '</td>';
            tag += '<td class="width50px center">';
            tag += '<a class="deletecheck cursorpointer_task" onClick="removecheck(this)">';
            tag += '<?php echo img_delete(); ?>';
            tag += '</a>';
            tag += '<a class="editcheck cursorpointer_task" data-id="'+id+'" data-modal="'+id_modal+'" onClick="editcheck(this,1)"><?php echo img_edit(); ?>';
            tag += '</td>';
        tag += '</tr></table>';
    tag += '</li>';

    $('li#check_'+id).html(tag);

}

function cancelcheckmodal(that) {
	var id_tache = $('#id_tache').val();
	var id = $(that).data('id');
	var label = $(that).data('label');
	var id_modal = $(that).data('modal');

	var tag = '<li class="checklist newcheck" id="check_'+id+'">';
        tag += '<table class="checklist_task_pop"><tr>';
            tag += '<td class="width30px center">';
        		tag += '<span class="far fa-square"> </span>';
        		tag += '<input name="newcheck[checked]['+id+']" data-id="'+id+'" type="hidden" value="1">';
                tag += '<input type="hidden" id="label_check_'+id+'" name="newcheck[label]['+id+']" value="'+label+'" >';
            tag += '</td>';
            tag += '<td>';
            	tag += '<label class="cursormove_task" for="checkbox_newcheck_'+id+'">';
                    tag += ' <span class="lbl_tag">'+label+'</span>';
	            tag += '</label>';
            tag += '</td>';
            tag += '<td class="width50px center">';
            tag += '<a class="deletecheck cursorpointer_task" onClick="removecheck(this)">';
            tag += '<?php echo img_delete(); ?>';
            tag += '</a>';
            tag += '<a class="editcheck cursorpointer_task" data-id="'+id+'" data-modal="'+id_modal+'" onClick="editcheck(this,1)"><?php echo img_edit(); ?>';
            tag += '</td>';
        tag += '</tr></table>';
    tag += '</li>';

    $('li#check_'+id).html(tag);

}

function updatecheck(that, newcheck = 0) {

	var id_tache = $('#id_tache').val();
	var id = $(that).data('id');

	var numcheck = $(that).data('num');

	var label = $('input[name="label_check_'+id+'"]').val();

	if(!newcheck) {

		$.ajax({
	        data:{
	        	'action': 'updatecheck'
	        	,'id': id
	        	,'label': label
	        	,'id_tache': id_tache
	        	,'numcheck': numcheck
	        	
	        },
	        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
	        type:'POST',
	        success:function(returned){
	            if(returned) {
				    $('li#check_'+id).html(returned);
	            }
	        }
	    });

	} else {

		newcheck = '';
		newcheck += '<table class="checklist_task_pop"><tr>';
            newcheck += '<td class="width30px center">';
                newcheck += '<input type="hidden" class="numcheck" id="numcheck_'+id+'" name="newcheck[numcheck]['+id+']" value="'+numcheck+'" >';
            	newcheck += '<input type="checkbox" class="check_list" id="checkbox_newcheck_'+id+'" data-id="'+id+'" onchange="calcProgress(this)" name="newcheck[checked]['+id+']" value="1" />';
                newcheck += '<input type="hidden" id="label_check_'+id+'" name="newcheck[label]['+id+']" value="'+label+'" >';
            newcheck += '</td>';
            newcheck += '<td>';
            	newcheck += '<label class="cursormove_task" for="checkbox_newcheck_'+id+'">';
                    newcheck += ' <span class="lbl_tag">'+label+'</span>';
	            newcheck += '</label>';
            newcheck += '</td>';
            newcheck += '<td class="width50px center">';
            newcheck += '<a class="deletecheck cursorpointer_task" onClick="removecheck(this)">';
            newcheck += '<?php echo img_delete(); ?>';
            newcheck += '</a>';
            newcheck += '<a class="editcheck cursorpointer_task" data-id="'+id+'" data-num="'+numcheck+'" onClick="editcheck(this,1)"><?php echo img_edit(); ?>';
            newcheck += '</td>';
        newcheck += '</tr></table>';

        $('li#check_'+id).html(newcheck);

	}
}

function cancelcheck(that) {
	var id_tache = $('#id_tache').val();
	var id = $(that).data('id');
	var numcheck = $(that).data('num');

	$.ajax({
        data:{
        	'action': 'getchecklist'
        	,'id': id
        	,'id_tache': id_tache
        	,'numcheck': numcheck
        	
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
			    $('li#check_'+id).html(returned);
            }
        }
    });
}

function removecheck(that) {
	$(that).parents('.newcheck').remove();
	calcProgress();
	updatenumcheck();
}

function deletecheck(that) {
	var id = $(that).data('id');
	var checkdeleted = $('.checkdeleted').val();
	if(id)
	checkdeleted=checkdeleted+','+id;
	$('.checkdeleted').val(checkdeleted);
	$(that).parents('li.checklist').remove();
	calcProgress();
	updatenumcheck();
}

// Autre function de kanban

function datepickerStartDateEndDate() {

	formatdate2 = '<?php echo dol_escape_js($langs->trans("FormatDateShortJQueryInput")); ?>';

	$('#date_start, #date_end').removeAttr('onChange').attr('autocomplete', 'off');

	var day = $('#date_startday').val();
	var year = $('#date_startyear').val();
	var month = $('#date_startmonth').val();
	var newmonth = parseInt(month);
	var date = new Date(year, month-1, day);
	var d = new Date(year, newmonth, 0);

	$('#date_end').datepicker({
		dateFormat: formatdate2,
	    minDate: date,
	    maxDate: d,
	    onSelect: function (dateStr) {
	    	 $(this).change();
	    }
	});

	dateFieldID = 'date_start';

	$('#date_start').datepicker({
		dateFormat: formatdate2,
	    // maxDate: d,
	    onSelect: function (dateStr) {
			var ddate = $(this).datepicker('getDate');
			dd_year = ddate.getFullYear();
			dd_month = ddate.getMonth();
			dd_day = ddate.getDate();

			var d_date = new Date(dd_year, dd_month, dd_day);
			var dd = new Date(dd_year, dd_month+1, 0);

		 	$(this).change();

			$('#date_end').datepicker('destroy');

	        $('#date_end').datepicker({
	        	dateFormat: formatdate2,
	            minDate: d_date,
	            maxDate: dd,
	            onSelect: function (dateStr) {
	            	$(this).change();
	            }
	        });

			$('#date_end').datepicker('setDate', dd).change();
			// $('#date_end').val('');
	    }
	});

	kanbanDpChangeDay('date_start');
	kanbanDpChangeDay('date_end');
}

function kanbanDpChangeDay(dateFieldID) {

	$('#'+dateFieldID).on('change', function() {
		formatdate = '<?php echo dol_escape_js($langs->trans("FormatDateShortJavaInput")); ?>';

		var thefield=getObjectFromID(dateFieldID);
		var thefieldday=getObjectFromID(dateFieldID+"day");
		var thefieldmonth=getObjectFromID(dateFieldID+"month");
		var thefieldyear=getObjectFromID(dateFieldID+"year");

		var thedate=getDateFromFormat(thefield.value, formatdate);

		if(thedate) {
			thefieldday.value=thedate.getDate();
			// if(thefieldday.onchange) thefieldday.onchange.call(thefieldday);
			thefieldmonth.value=thedate.getMonth()+1;
			// if(thefieldmonth.onchange) thefieldmonth.onchange.call(thefieldmonth);
			thefieldyear.value=thedate.getFullYear();
			// if(thefieldyear.onchange) thefieldyear.onchange.call(thefieldyear);
		}
	});
}

function changeSatusdate(that) {
	var id_tache = $(that).data('id');
	// console.log('id_tache: '+id_tache);
	$('.kanban_tooltipstatus').remove();
	
	var x = $(that).offset();
	var top=x.top+10;
	var left=x.left+10;
	$.ajax({
        data:{
        	'action': 'changeSatusdate'
        	,'id_tache': id_tache

        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
            	$('body').append('<div class="kanban_tooltipstatus" style="top: '+top+'px;left: '+left+'px">'+returned+'</div>');
            }
        }
    });
}

function checkstatuscolor(that) {
	var id_tache = $(that).data('id');
	var color = $(that).val();
	
	$("#kabantask .list-card").removeClass('kanbancurrenttaskactive');

	// console.log('id_tache: '+id_tache);
	$.ajax({
        data:{
        	'action': 'checkstatuscolor'
        	,'id_tache': id_tache
        	,'color': color
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        dataType:'Json',
        success:function(returned){
            if(returned) {
				projet_choose_change(id_tache);
            	
            	$('#task_'+id_tache).find('.datetask').attr("style", "background:"+ returned['color']);
				$('.kanban_tooltipstatus').remove();
            }
        }
    });
}

function applyJsToolTipForOneContent(id_tache){

	var clsspec = '';
	if(id_tache) clsspec += '#task_'+id_tache;

    $(document).tooltip({
        items: ".list-card"+clsspec,
        tooltipClass: "arrow",
        content: function () {
            var $this = $(this),
            random, html = "";
            return $(this).data("title");
        },
        position: {
            my: "center bottom-4", // the "anchor point" in the tooltip element
            at: "center top", // the position of that anchor point relative to selected element
        }
        ,
        animation: true,
        delay: { "show": 500, "hide": 100 }
    });
}

function applydraggable(){
	$(".tabtask").draggable({
		containment: ".todo_content",
      	// revert: true,
      	revert : function(droppableContainer) {
            if(droppableContainer) {

            }else {
                // console.log(droppableContainer);
                var from = $(this).parent().parent().attr("id");
                $(this).css("position","unset");
                $("#"+from+" .scroll_div").prepend($(this));
            }
            return(!droppableContainer) //returns the draggable to its original position
        },
      	refreshPositions: false,
      	start: function (event, ui) {
      		pos =$(this).offset();
      		// console.log('Top: '+pos.top);

      		// $("#kabantask .list-card").removeClass('kanbancurrenttaskactive');
      		// ui.helper.addClass('kanbancurrenttaskactive');
      		tooglekanbancurrenttaskactive(ui.helper);
      	},
      	drag: function (event, ui) {
      		pos =$(this).offset();

      		var divPos = ui.position.top;

			if (divPos < 85) {
			 	ui.position.top = 85;
			}

      		// console.log('Left: '+pos.left);
      		// console.log('Top: '+pos.top);
			ui.helper.css("position","absolute");
			ui.helper.parents('.contents').append(ui.helper);
			
			ui.helper.css("top",pos.top);
			ui.helper.css("left",pos.left);
          	$(".actif_onitem").removeClass("actif_onitem");
          	ui.helper.addClass("actif_onitem");
          	// $('.ui-tooltip').hide();
          	// if ($('.scroll_div.ui-droppable.ui-droppable-active.ui-droppable-hover').length > 0)
          	// 	$('.scroll_div.ui-droppable.ui-droppable-active.ui-droppable-hover').css("border-style","dashed");
          	// else
          	// 	$('.scroll_div.ui-droppable.ui-droppable-active.ui-droppable-hover').css("border-style","none");
      	},
      	stop: function (event, ui) {
          	ui.helper.removeClass("draggable");
          	// setTimeout(
          		// function(){ 
          		// 	ui.helper.removeClass("actif_onitem");
          		// }, 3000
          	// );
      }
  	});

  	$(".scroll_div").droppable({

  		hoverClass: "drop-hover",
	    tolerance: "pointer",
	    greedy: true,
		accept: function(dropElem) {
			return true;
		},
		drop: function (event, ui) {

			// ui.draggable.css("left","0");
			// ui.draggable.css("top","auto");
			ui.draggable.css("position","unset");

			var from_etat = ui.draggable.parent().parent().data("etat");
			var to_etat = $(this).parent().parent().data("etat");
			var fk_opp_status = $(this).parent().parent().data("opp_status");

			var from = ui.draggable.parent().parent().attr("id");
			var to = $(this).parent().parent().attr("id");
			var id_tache = ui.draggable.data("rowid");

			// var arr = [];
			// if (to == "JC") arr = ["NPC","AC","CEC","CR","DESA"];

			var permis = 1;

			<?php 
			if (!$user->rights->projet->creer) {
				echo 'permis = 0;';
			}
			?>
			
			$("#"+to+" .scroll_div").prepend(ui.draggable);

			if($(".jnotify-container>div").length >= 2) $(".jnotify-notification").remove();

			if (to !== from && permis == 1) {
				
				var data = {
					'id_tache' : id_tache,
					'from_etat' : from_etat,
					'to_etat' : to_etat,
					'fk_opp_status' : fk_opp_status,
					'action' : "updattask"
				};
				$.ajax({
					type: "POST",
					url: "<?php echo dol_buildpath('/digikanban/check.php',1); ?>",
					data: data, 
					dataType: 'json',
					success: function(returned){
						if(returned['error']) { 
							$("#"+from+" .scroll_div").prepend(ui.draggable);
							$.jnotify(returned['msg'], 'error', false);
							return 0;
						} else {

							$.jnotify('<?php echo trim($langs->trans("RecordModifiedSuccessfully")); ?>');

							// if (from == "DRAFT") load_all_projets();

							if(from_etat == 'urgents') {
								ui.draggable.find('.alertwarningicon').remove();
							}

							if(returned['titletask']) {
								var titletask = returned['titletask'];

								ui.draggable.find('.task_data_title_hover ').removeAttr('title');
								var titletask = ui.draggable.find('.task_data_title_hover').attr('title', titletask);
								applyJsToolTipForOneContent(id_tache);
							}

							// console.log(titletask);

							countEachColumnNumbers();
						}
					}
					
				});

			}

			else {

				$("#"+from+" .scroll_div").prepend(ui.draggable);

				if (permis == 0) {
					$.jnotify('<?php echo trim(addslashes($langs->trans("permissiondenied"))); ?>', "error");
				}

			}

			// $("#"+from+" .scroll_div").prepend(ui.draggable);
			// ui.draggable.addClass('kanbancurrenttaskactive');

			$("#"+to+" .scroll_div").scrollTop(0);
		}
  	});

}

//  Modal kanban

// add modele
function managemodels(that) {
	var id_colomn = $(that).data('colomn');

	$("#kabantask .list-card").removeClass('kanbancurrenttaskactive');
	
	$.ajax({
        data:{
        	'action': 'managemodels',
        	'id_colomn': id_colomn,
        	
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        // dataType:'json',
        success:function(returned){
            if(returned) {
            	$('.kanban_modal').parent().remove();
            	$('body').append('<div class="window-overlay" id="poptasks"><div id="kanban_new_task" class="kanban_modal">'+returned+'</div></div>');
            	// $('body').append('<div class="window-overlay" id="poptasks"><div id="kanban_new_task" class="kanban_checklist"><input type="hidden" id="id_tache" name="id_tache" value="'+id_tache+'">'+returned+'</div></div>');
            	$('#modalkanban').trigger('change');
            	$('#modalkanban').select2();
            }
        }
    });
}

function closemodele(that) {
	countCurrentComments();

	// $('.kanban_txt_comment textarea').val('');
	// $('.cancelcomment').hide();
	$('.kanban_modal').parent().remove();
}

function createmodal(that) {
	var id_colomn = $(that).data('colomn');
	// console.log('createmodal');
	var year = $('#search_year').val();
	var month = $(that).data('colomn');
	var search_affecteduser = $('select[name="search_affecteduser[]"]').val();
	var search_status = $('#search_status').val();
	var search_projects = $('select[name="search_projects[]"]').val();

	$.ajax({
        data:{
        	'action': 'createmodal',
        	'id_colomn': id_colomn,
        	'month': month,
        	'year': year,
        	'search_projects': search_projects,
        	'search_affecteduser': search_affecteduser,
        	'search_status': search_status
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        // dataType:'json',
        success:function(returned){
            if(returned) {
            	$('body').append('<div class="window-overlay popmodal" id="poptasks"><div id="kanban_new_task">'+returned+'</div></div>');
            	datepickerStartDateEndDate();
            	$('#kanban_new_task select').select2();
            }
        }
    });

	$('#poptasks').show();
}

function editmodal(that) {
	var id_modal = $('#modalkanban').val();
	var id_colomn = $(that).data('colomn');
	var year = $('#search_year').val();
	var month = $(that).data('colomn');
	var search_affecteduser = $('select[name="search_affecteduser[]"]').val();
	var search_status = $('#search_status').val();
	var search_projects = $('select[name="search_projects[]"]').val();

	$.ajax({
        data:{
        	'action': 'editmodal',
        	'id_modal': id_modal,
        	'id_colomn': id_colomn,
        	'month': month,
        	'year': year,
        	'search_projects': search_projects,
        	'search_affecteduser': search_affecteduser,
        	'search_status': search_status
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        // dataType:'json',
        success:function(returned){
            if(returned) {
            	$('body').append('<div class="window-overlay popmodal" id="poptasks"><div id="kanban_new_task">'+returned+'</div></div>');
				$('#poptasks').show();
            }
        }
    });
}

function canceladdmodal(that) {
	$('.popmodal').remove();
}

function savemodal(that) {

	var id_modal = $('#id_modal').val();
	var id_colomn = $(that).data('colomn');

	var search_affecteduser = $('select[name="search_affecteduser[]"]').val();
	var title = $('input#title').val();
	var users_tasks = $('input#users_tasks').val();
	var fk_projet = $('select[name="fk_projet"]').val();

	var id_tache = $('input[name="fk_task"]').val();
	var label = $('input[name="label"]').val();
	var budget = $('input[name="budget"]').val();
	var durehour = $('input[name="planned_workloadhour"]').val();
	var duremin = $('input[name="planned_workloadmin"]').val();
	var descp = $('textarea[name="description"]').val();
	var progress = $('select[name="progress"]').val();

	var userid = $('select[name="userid"]').val();
	var usercontact = $('select[name="usercontact[]"]').val();

	var startmin = $('#date_startmin').val();
	var starthour = $('#date_starthour').val();
	var startday = $('#date_startday').val();
	var startyear = $('#date_startyear').val();
	var startmonth = $('#date_startmonth').val();

	var endmin = $('#date_endmin').val();
	var endhour = $('#date_endhour').val();
	var endday = $('#date_endday').val();
	var endyear = $('#date_endyear').val();
	var endmonth = $('#date_endmonth').val();


	$.ajax({
        data:{
        	'action': 'savemodal'
        	,'id_modal': id_modal
        	,'title': title
        	,'label': label
        	,'budget': budget
        	,'search_affecteduser': search_affecteduser
        	,'fk_projet': fk_projet
        	,'users_tasks': users_tasks
        	,'durehour': durehour
        	,'duremin': duremin
        	,'progress': progress
        	,'userid': userid
        	,'usercontact': usercontact
        	,'endmin': endmin
        	,'endhour': endhour
        	,'endday': endday
        	,'endmonth': endmonth
        	,'endyear': endyear
        	,'startmin': startmin
        	,'starthour': starthour
        	,'startday': startday
        	,'startmonth': startmonth
        	,'startyear': startyear
        	,'description': descp
        	<?php
	        	if($extrafields->attributes[$tasks->table_element]['label']){

					foreach ($extrafields->attributes[$tasks->table_element]['label'] as $key => $value) {

						$visibi = $extrafields->attributes[$tasks->table_element]['list'][$key];
						if(!$visibi) continue;

						if($extrafields->attributes[$tasks->table_element]['type'][$key] == 'boolean'){
							?>
							,'options_<?php echo $key; ?>': ($('#options_<?php echo $key; ?>').is(":checked")) ? 1 : 'NULL'
							<?php
						}
						elseif($extrafields->attributes[$tasks->table_element]['type'][$key] == 'date' || $extrafields->attributes[$tasks->table_element]['type'][$key] == 'datetime'){
							?>
							,'options_<?php echo $key; ?>day': $('#options_<?php echo $key; ?>day').val()
							,'options_<?php echo $key; ?>month': $('#options_<?php echo $key; ?>month').val()
							,'options_<?php echo $key; ?>year': $('#options_<?php echo $key; ?>year').val()
							<?php
							if($extrafields->attributes[$tasks->table_element]['type'][$key] == 'datetime') {
								?>
								,'options_<?php echo $key; ?>hour': $('#options_<?php echo $key; ?>hour').val()
								,'options_<?php echo $key; ?>min': $('#options_<?php echo $key; ?>min').val()
								<?php
							}

						}else{
							?>
							,'options_<?php echo $key; ?>': $('#options_<?php echo $key; ?>').val()
							<?php

						}
					}
	        	}

			?>

        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        dataType:'Json',
        success:function(returned){
        	console.log(returned);
            if(returned) {
				$('.popmodal').remove();
            	$('#poptasks').find('.list_modal').html(returned['list_modal']);
            	$('#modalkanban').val(returned['id_modal']);
            	$('#modalkanban').trigger('change');
            	$('#modalkanban').select2();
            }
        }
    });
}

function showactionmodal(that) {
	if($(that).val() > 0){

		$('#editmodal').data('modal', $(that).val());
		$('#updatetags').data('modal', $(that).val());
		$('#updatechecklist').data('modal', $(that).val());
		$('#createtaskbymodal').data('modal', $(that).val());

		$('#editmodal').removeClass('hidden');
		$('#updatechecklist').removeClass('hidden');
		$('#updatetags').removeClass('hidden');
		$('#createtaskbymodal').removeClass('hidden');
		
	}else{
		if(!$('#editmodal').hasClass('hidden'))
			$('#editmodal').addClass('hidden');

		if(!$('#updatechecklist').hasClass('hidden'))
			$('#updatechecklist').addClass('hidden');

		if(!$('#updatetags').hasClass('hidden'))
			$('#updatetags').addClass('hidden');

		if(!$('#createtaskbymodal').hasClass('hidden'))
			$('#createtaskbymodal').addClass('hidden');

		$('#editmodal').attr('data-modal', '');
		$('#updatetags').attr('data-modal', '');
		$('#updatechecklist').attr('data-modal', '');
		$('#createtaskbymodal').attr('data-modal', '');
	}
}

function deletemodal(that) {
	
	var id_modal = $(that).data('id');
	var id_colomn = $(that).data('colomn');

	if (confirm("<?php echo dol_escape_js(dol_htmlentitiesbr_decode($langs->trans('digikanbanmsgconfiraction'))); ?>")) {
		$.ajax({
	        data:{
	        	'action': 'deletemodal'
	        	,'id_modal': id_modal
	        },
	        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
	        type:'POST',
	        dataType:'json',
	        success:function(returned){
	            if(returned['result']>0) {
					$('.popmodal').remove();
		        	$('#poptasks').find('.list_modal').html(returned['list_modal']);
		        	$('#modalkanban').trigger('change');
		        	$('#modalkanban').select2();
	            }
	        }
	    });
    }

    return false;
}

function createtaskbymodal(that) {
	var id_modal = $('#modalkanban').val();
	var id_colomn = $(that).data('colomn');

	if (confirm("<?php echo dol_escape_js(dol_htmlentitiesbr_decode($langs->trans('digikanbanmsgconfiraction'))); ?>")) {
		$.ajax({
	        data:{
	        	'action': 'createtaskbymodal',
	        	'id_colomn': id_colomn,
	        	'id_modal': id_modal
	        },
	        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
	        type:'POST',
	        dataType:'json',
	        success:function(returned){
	            if(returned) {
	            	$('#poptasks').remove();
					projet_choose_change(returned['taskid']);
	            	if(returned['msg']) {
	            		$.jnotify(returned['msg'], "500", false, { remove: function (){} } );
	            	}
	            }
	        }
	    });
    }

    return false;
}


function addcolomn(that) {
	$(that).addClass('hidden');
	$('.printtitle').removeClass('hidden');
	$('.titlenewcolomn').focus();
}

function createnewcolomn(that) {
	var title = $('.titlenewcolomn').val();
		
	$.ajax({
        data:{
        	'action': 'createnewcolomn',
        	'title': title
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/digikanban/check.php',1)); ?>",
        type:'POST',
        dataType:'json',
        success:function(returned){
            if(returned) {
				location.reload(true);
            }
        }
    });
}

function closecolomn() {
	$('.printtitle').find('input').val('');
	$('.printtitle').addClass('hidden');
	$('.newcolomn .sp_title').removeClass('hidden');
}