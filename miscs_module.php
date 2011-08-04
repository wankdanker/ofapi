<?

/**
* @package OFAPI
* @version v 1.0 2008/06/28
* @version 1.1 (2008/07/29) Added getBoardName(), getBoardConfigurationData()
* @version 1.1.3 (2008/09/03) Added number of parameters check
* @copyright (c) 2008 Roberto Beretta & Daniele Margutti
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* @link http://code.google.com/p/ofapi
*
*/

/*
* it should be used to mantain api secure from hacker attack
*/
if (!defined('IN_OFAPI')) {
	exit;
}



/**
* This function return a brief guide to forum based upon the content of FAQ sections<br/>
* You can use this function only if you are logged and it returns FAQ in your choosed language
*
* <br/><b>PARAMETERS:</b><br/>
* - <b>$type</b> 	<i>[string]</i> 	<b>'bbcode'</b> to get BBCodes guide or <b>'faq'</b> to get the faq complete guide
*
* @since 1.0
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return array the returned array contains a list of blocks (categories) with a specified argument. Each object contains:<br/><br/>
*			- <b>'title'</b> 		<i>[string]</i> 	The title of the section, the argument of the help<br/>
*			- <b>'list'</b> 		<i>[array]</i> 		Author of message
*			<br/>
*			'list' is an array with these keys: <br/>
*			<b>'question'</b> 		<i>[string]</i> 	The question<br/>
*			<b>'answer'</b> 		<i>[string]</i> 	The answer (woah!)<br/>
*/

function getFAQGuide($args) {
    // if number of parameters is lower than requested from headers this function return GENERAL_WRONG_PARAMETERS
    if (count($args) < 1) return GENERAL_WRONG_PARAMETERS;


    $type = $args[0];
    if ($type == null) $type = 'faq';
    
    global $user;
    if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;
    
    // Load the appropriate faq file
    switch ($type) {
	case 'bbcode':
		$user->add_lang('bbcode', false, true); break;
	default:
		$user->add_lang('faq', false, true); break;
    }    

    $lastFAQItem = null;
    $listFAQs = array();
    foreach ($user->help as $help_ary) {
        if ($help_ary[0] == '--') {
            $lastFAQItem = array('title' => $help_ary[1], 'list' => array());
	    continue;
	}
        
        $lastFAQItem['list'][] = array('question' => $help_ary[0],
                                       'answer' => $help_ary[1]);
        $listFAQs[]=$lastFAQItem;
    }
    return $listFAQs;
}

/**
* This function return board's name<br/>
* @since 1.0
* @version 1.0
* @see THIS FUNCTION DON'T REQUIRE LOGIN
* @return string The board name
*/

function getBoardName() {
        global $config;
        return $config['sitename'];
}

/**
* This function return some board informations <br/>
* @since 1.0
* @version 1.0
* @see THIS FUNCTION DON'T REQUIRE LOGIN
* @return array This function return an array with these keys:<br/>
* 			<b>'board_contact'</b>			board email contact <br/>
* 			<b>'sitename'</b>				the name of the board <br/>
* 			<b>'site_desc'</b>				description of the board <br/>
* 			<b>'phpbb_version'</b>			currently installed phpbb version <br/>
* 			<b>'num_files'</b>				number of uploaded files into the board <br/>
* 			<b>'num_posts'</b>				number of posts of the board <br/>
* 			<b>'num_topics'</b>				number of topics posted into the board <br/>
* 			<b>'num_users'</b>				number of subscrbed users <br/>
* 			<b>'record_online_date'</b>		the date where the board was most visited (in unix format) <br/>
* 			<b>'record_online_users'</b>	the max online users <br/>
* 			<b>'load_online_time'</b>		stats about online users is refererred by the last previous x minutes <br/>
* 			<b>'online_guests'</b>			number of currently online guests <br/>
* 			<b>'online'</b>					number of currently online users <br/>
*/
function getBoardConfigurationData() {
	global $config;
	$res =  array( 	'board_contact'		=> $config['board_email'],
			'sitename'	        		=> $config['sitename'],
			'site_desc'	       		 	=> $config['site_desc'],
			'phpbb_version'	        	=> $config['version'],
			'num_files'	        		=> $config['num_files'],
			'num_posts'	        		=> $config['num_posts'],
			'num_topics'	       		=> $config['num_topics'],
			'num_users'	        		=> $config['num_users'],
			'record_online_date'		=> $config['record_online_date'],
			'record_online_users'		=> $config['record_online_users'],
			'load_online_time'			=> $config['load_online_time'],
			'online_guests'	        	=> $config['load_online_guests'],
			'online'	        		=> $config['load_online'],
			'topics_per_page'			=> $config['topics_per_page'],
			'posts_per_page'			=> $config['posts_per_page']);
	return $res;
}

?>