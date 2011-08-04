<?

/*
* it should be used to mantain api secure from hacker attack
*/

if (!defined('IN_OFAPI')) {
	exit;
}

/**
* @package OFAPI
* @version headers.php, v 1.0 2008/06/05 22:30:00
* @copyright (c) 2008 Roberto Beretta & Daniele Margutti
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

define('IN_PHPBB', true);
// This path is very important: it's a relative path to the phpBB board
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);


// phpBB related
include_once($phpbb_root_path . 'common.' . $phpEx);
include_once($phpbb_root_path . 'includes/constants.' . $phpEx);
include_once($phpbb_root_path . 'includes/functions_display.' . $phpEx);
include_once($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
include_once($phpbb_root_path . 'includes/bbcode.' . $phpEx);
include_once($phpbb_root_path . 'includes/functions_user.' . $phpEx);
include_once($phpbb_root_path . 'includes/message_parser.'.$phpEx);
include_once($phpbb_root_path . 'includes/ucp/ucp_pm_viewfolder.'.$phpEx);

// ofapi related files
include_once('shared.php');
include_once('costants.php');
include_once('members_module.php');
include_once('miscs_module.php');
include_once('msgs_module.php');
include_once('user_module.php');
include_once('stats_module.php');
include_once('msg_sending.php');
include_once('logging_module.php');

?>
