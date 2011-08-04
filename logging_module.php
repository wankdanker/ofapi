<?php

/**
* @package OFAPI
* @version v 1.0 2008/06/28
* @version v 1.1 build 4 (2008-09-03): Added GENERAL_WRONG_PARAMETERS alert
* @copyright (c) 2008 Roberto Beretta (roberto.alpha@gmail.com) & Daniele Margutti (malcom.mac@gmail.com)
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
* Allows a registered user to login into the board.<br/>
* You need to provide 4 parameters inside the $args array. At this time we don't support new user registration directly via API.<br/>
* To create a new account you need to browse the board using a browser.<br/><br/>
* <b>PARAMETERS</b>:
* - <b>$username</b> 	<i>[string]</i>		registered username 
* - <b>$password</b> 	<i>[string]</i>		username associated password (in clean format) 
* @see FUNCTION DOES NOT REQUIRE LOGIN (uh uh, that's a login function baby!)
* @since 1.0
* @version 1.0
* @return integer   <b>LOGIN_SUCCESS</b> (login succeded)<br/>
*                   <b>LOGIN_ERROR_PASSWORD</b> (wrong given password),<br/>
*                   <b>LOGIN_ERROR_ATTEMPTS</b> (you have reached the maximum login attempts, your account was blocked)<br/>
*/ 

function login($args) {
	// if number of parameters is lower than requested from headers this function return GENERAL_WRONG_PARAMETERS
	if (count($args) < 2) return GENERAL_WRONG_PARAMETERS;
	
	// Prende i parametri dall'array di input
	$username   = $args[0];
	$password   = $args[1];

	global $user, $auth;
	$user->session_begin();
	$auth->acl($user->data);
	$user->setup();

	if ($user->data['user_id'] == ANONYMOUS || $user->data['is_bot']) {
	// il parametro $viewonline della funzione $auth->login vuole 1 | 0, quindi traduco
	$results = $auth->login($username, $password, true, true ? 1 : 0, 0);
	   switch ($results['status']) {
		case 3:
			return OP_LOGIN_SUCCESS;
			break;
		case 10:
			return OP_LOGIN_ERROR_PASSWORD;
			break;
		case 11:
		default:
			return OP_LOGIN_ERROR_ATTEMPTS;
			break;
	    }
	}
	return OP_LOGIN_ERROR_PASSWORD; // this should never happend
}

/**
* This function is used to login in a password-protected forum.
* You should not use it directly but it's called when you use getTopicsFromForum function (to return the list of topics into the forum)
* when you give a $forumPassword. <br/><br/>
* 
* <b>PARAMETERS:</b><br/>
* - <b>$forum_id</b> 	<i>[string]</i> 	the id of the forum you want to login<br/>
* - <b>$password</b> 	<i>[string]</i> 	given password to access<br/>
* @since 1.0
* @version 1.0
* @return integer   
*                   <b>LOGIN_FPSWD_OK</b> you are successfully logged into the forum<br/>
*                   <b>LOGIN_FPSWD_ERR</b> given password is wrong<br/>
*                   <b>LOGIN_PSWD_FORUM_ALREADYLOGGED</b> you are already logged into the forum<br/>
*                   <b>LOGIN_FPSWD_WRONGFORUMID</b> given forum id does not exist                
*/

function loginProtectedForumWithPassword($forum_id,$password) {
    global $db, $config, $user, $phpEx;
	
    // get info from forum id
    $sql_from .= ' LEFT JOIN ' . FORUMS_WATCH_TABLE . ' fw ON (fw.forum_id = f.forum_id AND fw.user_id = ' . $user->data['user_id'] . ')';
    $lastread_select .= ', fw.notify_status';
    $sql = "SELECT f.* $lastread_select
	FROM $sql_from
	WHERE f.forum_id = $forum_id";
    $result = $db->sql_query($sql);
    $forum_data = $db->sql_fetchrow($result);  
    $db->sql_freeresult($result);
    
    // wrong forum id
    if ($forum_data == null || !isset($forum_data))
        return LOGIN_FPSWD_WRONGFORUMID;
    
    $sql = 'SELECT forum_id
		FROM ' . FORUMS_ACCESS_TABLE . '
		WHERE forum_id = ' . $forum_data['forum_id'] . '
			AND user_id = ' . $user->data['user_id'] . "
			AND session_id = '" . $db->sql_escape($user->session_id) . "'";
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);

    // you are already logged
    if ($row)
	return LOGIN_FPSWD_ALREADYLOGGED;

    if ($password) {
	// Remove expired authorised sessions
	$sql = 'SELECT f.session_id
			FROM ' . FORUMS_ACCESS_TABLE . ' f
			LEFT JOIN ' . SESSIONS_TABLE . ' s ON (f.session_id = s.session_id)
			WHERE s.session_id IS NULL';
	$result = $db->sql_query($sql);

	if ($row = $db->sql_fetchrow($result)) {
	    $sql_in = array();
	    do {
	    	$sql_in[] = (string) $row['session_id'];
	    }
	    while ($row = $db->sql_fetchrow($result));

	    // Remove expired sessions
	    $sql = 'DELETE FROM ' . FORUMS_ACCESS_TABLE . '
		    	WHERE ' . $db->sql_in_set('session_id', $sql_in);
	    $db->sql_query($sql);
	}
	
        $db->sql_freeresult($result);

	if (phpbb_check_hash($password, $forum_data['forum_password'])) {
	    $sql_ary = array(
			'forum_id'		=> (int) $forum_data['forum_id'],
			'user_id'		=> (int) $user->data['user_id'],
			'session_id'	=> (string) $user->session_id,
	    );

	    $db->sql_query('INSERT INTO ' . FORUMS_ACCESS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary));
            
            // you are now logged
	    return LOGIN_FPSWD_OK;
	}
        
        // error
        return LOGIN_FPSWD_ERR;
    }
    // where is the password?
    return LOGIN_FPSWD_ERR;
}


/**
* @ignore
* This function return the license agreement text of the board.	<br/>
* The function is labeled with DRAFT_ prefix because other OFAPI functions does not use it and it's a not an exported function.
* In fact without the register feature via OFAPI this function is not useful.<br/><br/>
* 
* <b>PARAMETERS:</b><br/>
* - <b>$mode</b> 	<i>[string]</i> 	<i>'terms'</i> to show terms of license or <i>'privacy'</i> to show the privacy legacy infos<br/>
*
* @since 1.1
* @version 1.0
* @todo Implement a register function via OFAPI and enable this function to display the license and privacy agreement to end user
* @return string the end user agreement to join into the board              
*/

function DRAFT_getLicenseAgreement($args) {
        global $user,$auth,$db,$config;
        $auth->acl($user->data);
        
        $mode = $args[0]; if (!isSet($mode)) $mode = 'terms';
		$message = ($mode == 'terms') ? 'TERMS_OF_USE_CONTENT' : 'PRIVACY_POLICY';
		$title = ($mode == 'terms') ? 'TERMS_USE' : 'PRIVACY';

        $title = $user->lang[$title];
        $message = sprintf($user->lang[$message], $config['sitename'], generate_board_url());
        return array ('title' => $title, 'text' => $message);
}


/**
* This function is used to logout an user.
* <br/><br/>
* <b>PARAMETERS:</b><br/>
* - <b>Nothing</b><br/>
* @since 1.0
* @version 1.0
* @return void
*/

function logout() {
	global $user;
	if ($user->data['user_id'] != ANONYMOUS) //&& isset($_GET['sid']) && !is_array($_GET['sid']) && $_GET['sid'] === $user->session_id)
	{
		$user->session_kill();
		$user->session_begin();
		//$message = $user->lang['LOGOUT_REDIRECT'];
	}
}

?>
