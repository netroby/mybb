<?php
/**
 * MyBB 1.2
 * Copyright � 2007 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybboard.net
 * License: http://www.mybboard.net/license.php
 *
 * $Id$
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item($lang->system_email_log, "index.php?".SID."&amp;module=tools/mailerrors");

if($mybb->input['action'] == "view")
{
	$query = $db->simple_select("mailerrors", "*", "eid='".intval($mybb->input['eid'])."'");
	$log = $db->fetch_array($query);

	if(!$log['eid'])
	{
		exit;
	}

	$log['toaddress'] = htmlspecialchars_uni($log['toaddress']);
	$log['fromaddress'] = htmlspecialchars_uni($log['fromaddress']);
	$log['subject'] = htmlspecialchars_uni($log['subject']);
	$log['error'] = htmlspecialchars_uni($log['error']);
	$log['smtperror'] = htmlspecialchars_uni($log['smtpcode']);
	$log['dateline'] = date($mybb->settings['dateformat'], $log['dateline']).", ".date($mybb->settings['timeformat'], $log['dateline']);
	$log['message'] = nl2br(htmlspecialchars_uni($log['message']));

	?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://gmpg.org/xfn/1">
	<title><?php echo $lang->user_email_log_viewer; ?></title>
	<link rel="stylesheet" href="styles/<?php echo $page->style; ?>/main.css" type="text/css" />
	<link rel="stylesheet" href="styles/<?php echo $page->style; ?>/popup.css" type="text/css" />
</head>
<body id="popup">
	<div id="popup_container">
	<div class="popup_title"><a href="#" onClick="window.close();" class="close_link"><?php echo $lang->close_window; ?></a><?php echo $lang->user_email_log_viewer; ?></div>

	<div id="content">
	<?php
	$table = new Table();

	$table->construct_cell($log['error'], array("colspan" => 2));
	$table->construct_row();

	if($log['smtpcode'])
	{
		$table->construct_cell($lang->smtp_code);
		$table->construct_cell($log['smtpcode']);
		$table->construct_row();
	}
	
	if($log['smtperror'])
	{
		$table->construct_cell($lang->smtp_server_response);
		$table->construct_cell($log['smtperror']);
		$table->construct_row();
	}
	$table->output($lang->error);

	$table = new Table();

	$table->construct_cell($lang->to.":");
	$table->construct_cell("<a href=\"mailto:{$log['toaddress']}\">{$log['toaddress']}</a>");
	$table->construct_row();

	$table->construct_cell($lang->from.":");
	$table->construct_cell("<a href=\"mailto:{$log['fromaddress']}\">{$log['fromaddress']}</a>");
	$table->construct_row();

	$table->construct_cell($lang->subject.":");
	$table->construct_cell($log['subject']);
	$table->construct_row();

	$table->construct_cell($lang->date.":");
	$table->construct_cell($log['dateline']);
	$table->construct_row();

	$table->construct_cell($log['message'], array("colspan" => 2));
	$table->construct_row();

	$table->output($lang->email);

	?>
	</div>
</div>
</body>
</html>
	<?php
}

if(!$mybb->input['action'])
{
	$per_page = 20;

	if($mybb->input['page'] && $mybb->input['page'] > 1)
	{
		$mybb->input['page'] = intval($mybb->input['page']);
		$start = ($mybb->input['page']*$per_page)-$per_page;
	}
	else
	{
		$mybb->input['page'] = 1;
		$start = 0;
	}

	$additional_criteria = array();


	// Begin criteria filtering
	if($mybb->input['subject'])
	{
		$additional_sql_criteria .= " AND subject LIKE '%".$db->escape_string($mybb->input['subject'])."%'";
		$additional_criteria[] = "subject='".htmlspecialchars_uni($mybb->input['subject'])."'";
	}

	if($mybb->input['fromaddress'])
	{
		$additional_sql_criteria .= " AND fromaddress LIKE '%".$db->escape_string($mybb->input['fromaddress'])."%'";
		$additional_criteria[] = "fromaddress='".urlencode($mybb->input['fromaddress'])."'";
	}

	if($mybb->input['toaddress'])
	{
		$additional_sql_criteria .= " AND toaddress LIKE '%".$db->escape_string($mybb->input['toaddress'])."%'";
		$additional_criteria[] = "toaddress='".urlencode($mybb->input['toaddress'])."'";
	}

	if($mybb->input['error'])
	{
		$additional_sql_criteria .= " AND error LIKE '%".$db->escape_string($mybb->input['error'])."%'";
		$additional_criteria[] = "error='".urlencode($mybb->input['error'])."'";
	}

	$additional_criteria = implode("&amp;", $additional_criteria);	

	$page->output_header($lang->system_email_log);
	
	$sub_tabs['mailerrors'] = array(
		'title' => $lang->system_email_log,
		'description' => $lang->system_email_log_desc
	);
	
	$sub_tabs['prune_mailerrors'] = array(
		'title' => $lang->prune_system_email_log,
		'link' => "index.php?".SID."&amp;module=tools/mailerrors&amp;action=prune"
	);

	$page->output_nav_tabs($sub_tabs, 'mailerrors');

	$table = new Table;
	$table->construct_header($lang->subject);
	$table->construct_header($lang->to, array("class" => "align_center", "width" => "20%"));
	$table->construct_header($lang->error_message, array("class" => "align_center", "width" => "30%"));
	$table->construct_header($lang->date_sent, array("class" => "align_center", "width" => "20%"));

	$query = $db->query("
		SELECT *
		FROM ".TABLE_PREFIX."mailerrors
		WHERE 1=1 {$additional_sql_criteria}
		ORDER BY dateline DESC
		LIMIT {$start}, {$per_page}
	");
	while($log = $db->fetch_array($query))
	{
		$log['subject'] = htmlspecialchars_uni($log['subject']);
		$log['toemail'] = htmlspecialchars_uni($log['toemail']);
		$log['error'] = htmlspecialchars_uni($log['error']);
		$log['dateline'] = date($mybb->settings['dateformat'], $log['dateline']).", ".date($mybb->settings['timeformat'], $log['dateline']);

		$table->construct_cell("<a href=\"javascript:MyBB.popupWindow('index.php?".SID."&amp;module=tools/mailerrors&action=view&eid={$log['eid']}', 'log_entry', 450, 450);\">{$log['subject']}</a>");
		$find_from = "<div class=\"float_right\"><a href=\"index.php?".SID."&amp;module=tools/mailerrors&amp;toaddress={$log['toaddress']}\"><img src=\"styles/{$page->style}/images/icons/find.gif\" title=\"{$lang->fine_emails_to_addr}\" alt=\"{$lang->find}\" /></a></div>";
		$table->construct_cell("{$find_from}<div>{$log['toaddress']}</div>");
		$table->construct_cell($log['error']);
		$table->construct_cell($log['dateline'], array("class" => "align_center"));
		$table->construct_row();
	}
	
	if(count($table->rows) == 0)
	{
		$table->construct_cell($lang->no_logs, array("colspan" => "4"));
		$table->construct_row();
	}
	
	$table->output($lang->system_email_log);
	
	$query = $db->simple_select("mailerrors l", "COUNT(eid) AS logs", "1=1 {$additional_sql_criteria}");
	$total_rows = $db->fetch_field($query, "logs");

	echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?".SID."&amp;module=tools/mailerrors&amp;page={page}{$additional_criteria}");
	
	$form = new Form("index.php?".SID."&amp;module=tools/mailerrors", "post");
	$form_container = new FormContainer($lang->filter_system_email_log);
	$form_container->output_row($lang->subject_contains, "", $form->generate_text_box('subject', $mybb->input['subject'], array('id' => 'subject')), 'subject');	
	$form_container->output_row($lang->error_message_contains, "", $form->generate_text_box('error', $mybb->input['error'], array('id' => 'error')), 'error');	
	$form_container->output_row($lang->to_address_contains, "", $form->generate_text_box('toaddress', $mybb->input['toaddress'], array('id' => 'toaddress')), 'toaddress');	
	$form_container->output_row($lang->from_address_contains, "", $form->generate_text_box('fromaddress', $mybb->input['fromaddress'], array('id' => 'fromaddress')), 'fromaddress');	

	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->filter_system_email_log);
	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}
