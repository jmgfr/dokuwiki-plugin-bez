<?php
$inc = realpath(__DIR__.'/../../..');

define('DOKU_INC', $inc.'/');
define('DOKU_PLUGIN', $inc.'/lib/plugins/');
define('DOKU_CONF', $inc.'/conf/');

if (count($argv) < 2)
	die("podaj URI wiki dla którego odpalasz tego crona\n");
$URI = $argv[1];

if (isset($argv[2]) && $argv[2] == 'http')
	$http = 'http';
else
	$http = 'https';

$errors = array();
require_once DOKU_PLUGIN.'bez/lang/pl/lang.php';
$bezlang = $lang;

require_once DOKU_PLUGIN.'bez/models/issues.php';
require_once DOKU_PLUGIN.'bez/models/tasks.php';
$config_cascade = array();
require_once DOKU_INC.'inc/config_cascade.php';
$conf = array();
// load the global config file(s)
foreach (array('default','local','protected') as $config_group) {
	if (empty($config_cascade['main'][$config_group])) continue;
	foreach ($config_cascade['main'][$config_group] as $config_file) {
		if (file_exists($config_file)) {
		include($config_file);
		}
	}
}

require_once DOKU_INC.'inc/plugin.php';
require_once DOKU_PLUGIN.'auth.php';
require_once DOKU_PLUGIN.'authplain/auth.php';

require_once DOKU_PLUGIN.'bez/helper.php';
$helper = new helper_plugin_bez();

function log_errors() {
	global $errors;
	foreach ($errors as $error) {
		error_log($error);
	}
	if (count($errors) > 0)
		exit(1);
}
function msg($message,$lvl=0,$line='',$file='',$allow=0){
	if ($lvl == -1)
		error_log($message);
}

//email => array('user' => array('issues' => array(), 'tasks' => array()))
$msg = array();

try {
	$isso = new Issues();
	$tasko = new Tasks();
} catch (Exception $e) {
	error_log($e->getMessage().': '.$e->getFile());
}

$issues = $isso->cron_get_unsolved();
log_errors();
foreach ($issues as $issue) {
	$key = $issue['coordinator'];
	if (!isset($msg[$key]))
		$msg[$key] = array('issues' => array(), 'coming_tasks' => array(),
							'open_tasks' => array(), 'outdated_tasks' => array());

	$msg[$key]['issues'][] = $issue;
}

$coming_tasks_all  = $tasko->cron_get_coming_tasks();
log_errors();
foreach ($coming_tasks_all as $task) {
	$key = $task['executor'];
	if (!isset($msg[$key]))
		$msg[$key] = array('issues' => array(), 'coming_tasks' => array(),
							'open_tasks' => array(), 'outdated_tasks' => array());

	$msg[$key]['coming_tasks'][] = $task;
}

$outdated_tasks_all  = $tasko->cron_get_outdated_tasks();
log_errors();
foreach ($outdated_tasks_all as $task) {
	$key = $task['executor'];
	if (!isset($msg[$key]))
		$msg[$key] = array('issues' => array(), 'coming_tasks' => array(),
							'open_tasks' => array(), 'outdated_tasks' => array());

	$msg[$key]['outdated_tasks'][] = $task;
}

$open_tasks_all  = $tasko->cron_get_open_tasks();
log_errors();
foreach ($open_tasks_all as $task) {
	$key = $task['executor'];
	if (!isset($msg[$key]))
		$msg[$key] = array('issues' => array(), 'coming_tasks' => array(),
							'open_tasks' => array(), 'outdated_tasks' => array());

	$msg[$key]['open_tasks'][] = $task;
}

//outdated_tasks, coming_tasks, open_tasks

$auth = new auth_plugin_authplain();
foreach ($msg as $user => $data) {
	$udata = $auth->getUserData($user);
	
	$his_issues = $data['issues'];
	$outdated_tasks = $data['outdated_tasks'];
	$coming_tasks = $data['coming_tasks'];
	$open_tasks = $data['open_tasks'];
	
	if (count($his_issues) + count($outdated_tasks) + count($coming_tasks)
		+ count($open_tasks) == 0)
		continue;

	$to = $udata['name'].' <'.$udata['mail'].'>';
	$title = trim($conf['title']);
	if ($title == '')
		$title = $URI;
	$subject = "[BEZ][$title] Termin rozwiązania problemu";
	ob_start();

	include "cron-message-tpl.php";
	$body = ob_get_clean();

	$helper->mail($to, $subject, $body, $URI, "text/html");

}

