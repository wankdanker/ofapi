<?php

/**
* @package OFAPI
* @version 1.0 first draft 2008/06/28
* @version 1.1 (2008/07/29) Added searchForMember()
* @version 1.1.3 (2008/09/03) Added parameters number check
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
* This function allows you to search for a member of the board.
* You can also specify lots of options.<br/>
* <br><b>PARAMETERS:</b><br/>
* - <b>$fieldsToSearch</b> 	<i>[array]</i> 		This array specify some custom criteria. See $fieldsToSearch in getMembersListWithAdvancedSearch function to know how to use it.<br/>
* - <b>$pageIdx</b> 		<i>[integer]</i> 	The number of page (of results) you want to show (0 is default)<br/>
* - <b>$itemsPerPage</b> 	<i>[integer]</i> 	Number of members to show in a single page (30 is default)<br/>
* - <b>$first_char</b> 		<i>[string]</i>		Specify an alphanumeric char to view only items with this prefix (or leave it '')
* @since 1.1
* @version 1.1 - Added $first_char parameter to specify an alphanumeric letter that filter results (ex. to make a list for 'A','B','C'... pages)
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return array see _getMembersListWithAdvancedSearch for results info
*/

function searchForMember($args) {
    // if number of parameters is lower than requested from headers this function return GENERAL_WRONG_PARAMETERS
    if (count($args) < 1) return GENERAL_WRONG_PARAMETERS; // min request parameters is 1
        
    if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;
    
    $fieldsToSearch = $args[0];
    $pageIdx = $args[1]; if (!isset($pageIdx)) $pageIdx = 0;
    $itemsPerPage = $args[2]; if (!isset($itemsPerPage)) $itemsPerPage = 30;
        $first_char = $args[3];
        
    return _getMembersListWithAdvancedSearch('searchuser',$pageIdx,$itemsPerPage,$first_char,'c','a',$fieldsToSearch);
}

/**
* This function simply return the list of subscribed members of the board
* <br><b>PARAMETERS:</b><br/>
* - <b>$pageIdx</b> 		<i>[integer]</i> 	The number of page (of results) you want to show (0 is default)<br/>
* - <b>$itemsPerPage</b>	<i>[integer]</i> 	Number of members to show in a single page (30 is default)<br/>
* - <b>$first_char</b> 		<i>[string]</i>		Specify an alphanumeric char to view only items with this prefix (or leave it '')
* @since 1.0
* @version 1.1 - Added $first_char parameter to specify an alphanumeric letter that filter results (ex. to make a list for 'A','B','C'... pages)
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return array see getMembersListWithAdvancedSearch for results info
*/

function getMembersList($args) {
    // if number of parameters is lower than requested from headers this function return GENERAL_WRONG_PARAMETERS
    if (count($args) < 3) return GENERAL_WRONG_PARAMETERS;
        
    $pageIdx = $args[0];
    $itemsPerPage = $args[1];
    $first_char = $args[2];
    return _getMembersListWithAdvancedSearch('',$pageIdx,$itemsPerPage,$first_char);
}

/**
* This function simply return the list of subscribed members of the board
* <br><b>PARAMETERS:</b><br/>
* - <b>$mode</b> 			<i>[string]</i> 	<b>''</b> standard members list (default), <b>'searchuser'</b> for search member feature<br/>
* - <b>$pageIdx</b> 		<i>[integer]</i> 	Number of page you want to show<br/>
* - <b>$itemsPerPage</b>	<i>[integer]</i> 	Number of members to show in a single page (30 is default)<br/>
* - <b>$first_char</b> 		<i>[string]</i>		Specify an alphanumeric char to view only items with this prefix (or leave it '')
* - <b>$sort_key</b> 		<i>[string]</i> 	Sorting options by: <b>'a'</b>= username, <b>'b'</b>= user from, <b>'c'</b>= user registration date, <b>'d'</b>= users posts, 
*												 <b>'f'</b>= website, <b>'g'</b>= user ICQ contact, <b>'h'</b>= user AIM contact,  <b>'i'</b>= user MSN contact, <b>'j'</b>= user YIM 
*												 contact, <b>'k'</b>=  user JABBER contact<br/>     
* - <b>$sort_direction</b> 	<i>[string]</i> 	<b>'a'</b> ascending or <b>'d'</b> descending
* - <b>$fieldsToSearch</b> 	<i>[array]</i>		You need to insert key/value items to perform deep search inside user properties.<br/>Use * to perform a partial search<br/>
*		$fieldsToSearch available keys are:<br/>
* 		  <b>'username'</b> 	   	search inside username (for given key value as string)<br/>
* 		  <b>'mail'</b>             search inside mail<br/>
*    	  <b>'icq'</b>              search inside icq (for given key value as string)<br/>
*   	  <b>'aim'</b>              search inside aim (for given key value as string)<br/>
*  		  <b>'yahoo'</b>            search inside yahoo (for given key value as string)<br/>
*		  <b>'msn'</b>              search inside msn (for given key value as string)<br/>
*		  <b>'jabber'</b>           search inside jabber (for given key value as string)<br/>
*		  <b>'search_group_id'</b>  search inside group id (as integer)<br/>
*		  <b>'joined_select'</b>    could be <b>'lt'</b> (lower than), <b>'eq'</b> (equal to) or <b>'gt'</b> (grather than). You need to fill 'joined' (see below)<br/>
*		  <b>'joined'</b>           _put here your data in format "MM-DD-YYYY"<br/>
*		  <b>'active_select'</b>    see <i>'joined_selected'</i>. Accomplish it with 'active' (see below)<br/>
*		  <b>'active'</b>           _put here your data in format "MM-DD-YYYY"<br/>
*		  <b>'count_select'</b>     see <i>'joined_select'</i>. Accomplish it with 'count'<br/>
*		  <b>'count'</b>            put the number of messages to check as integer (default is 0)<br/>
*		  <b>'ip'</b>               ip domain to search<br/>
* - <b>$validateAll</b> 			if you specify it as true all proprerties to search must be validated to get result (AND) (default is false = OR)
* @since 1.0
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return array an array of members with these keys:<br/><br/>
*				- <b>'total_users'</b> number of total users on board<br/>
*				- <b>'users_list'</b> users array<br/><br/>
*						'users_list' array contains dictionaries with these keys:<br/>
*                  	        	<b>'username'</b> 			name of the user<br/>
*                               <b>'user_id'</b> 			id of the user<br/>
*                               <b>'user_lastvisit'</b> 	user's last visit<br/>
*                               <b>'user_avatar'</b> 		user avatar link<br/><br/>
*				- <b>'current_page'</b> current showed page<br/>
*				- <b>'total_pages'</b> number of total pages<br/>
*/

function getMembersListWithAdvancedSearch($args) {
    // if number of parameters is lower than requested from headers this function return GENERAL_WRONG_PARAMETERS
    if (count($args) < 3) return GENERAL_WRONG_PARAMETERS; // mode,pageIdx and usersPerPage

    $mode = $args[0];
    $pageIdx = $args[1];
    $usersPerPage = $args[2]; //if (!isset($usersPerPage)) $usersPerPage = 30;
    $first_char = $args[3];

    $sort_key = $args[4]; if (!isset($sort_key)) $sort_key = 'c';
    $sort_direction = $args[5]; if (!isset($sort_direction)) $sort_direction = 'a';
    $fieldsToSearch= $args[6]; if (!isset($fieldsToSearch)) $fieldsToSearch = array();
    $validateAll = $args[7]; if (!isset($validateAll)) $validateAll = false;
    
         
    return _getMembersListWithAdvancedSearch($mode,$pageIdx,$usersPerPage,$first_char,$sort_key,$sort_direction,$fieldsToSearch,$validateAll);
}

/**
*	<b> ** INTERNAL METHOD: DO NOT USE IT **</b>
*/

function _getMembersListWithAdvancedSearch($mode = '', $pageIdx = 0, $usersPerPage = 30,$first_char = '', $sort_key = 'a', $sort_direction = 'a',
                                            $fieldsToSearch = array(),$validateAll = false) {
    global $user,$db,$config,$auth,$cache;
    
    
    $validateConj = ' OR ';
    if ($validateAll) $validateConj = ' AND ';
    
    // we need to load authorizations    
    $auth->acl($user->data);

    // Check our mode...
    if (!in_array($mode, array('', 'group', 'viewprofile', 'email', 'contact', 'searchuser', 'leaders'))) {
	trigger_error('NO_MODE');
    }
    
    // Can this user view profiles/memberlist?
    if (!$auth->acl_gets('u_viewprofile', 'a_user', 'a_useradd', 'a_userdel')) {
        // you are logged but you are not authorized to view list or search within it
        if ($user->data['user_id'] != ANONYMOUS) {
            return MEMBERSLIST_NOT_AUTHORIZED; // FUNZIONE ERRORE: NON SEI AUTORIZZATO A VEDERE LA LISTA
	} else
            return MEMBERSLIST_ANONYMOUS_NOT_ALLOWED; // FUNZIONE ERRORE: DOVRESTI AUTENTICARTI PRIMA
      }

    $start  = $pageIdx; // the page to view
    $sort_dir = $sort_direction; // sort direction

    // The basic memberlist
    // Sorting
    $sort_key_text = array('a' => $user->lang['SORT_USERNAME'], 'b' => $user->lang['SORT_LOCATION'], 'c' => $user->lang['SORT_JOINED'], 'd' => $user->lang['SORT_POST_COUNT'], 'f' => $user->lang['WEBSITE'], 'g' => $user->lang['ICQ'], 'h' => $user->lang['AIM'], 'i' => $user->lang['MSNM'], 'j' => $user->lang['YIM'], 'k' => $user->lang['JABBER']);
    $sort_key_sql = array('a' => 'u.username_clean', 'b' => 'u.user_from', 'c' => 'u.user_regdate', 'd' => 'u.user_posts',
                          'f' => 'u.user_website', 'g' => 'u.user_icq', 'h' => 'u.user_aim', 'i' => 'u.user_msnm',
                          'j' => 'u.user_yim', 'k' => 'u.user_jabber');

    if ($auth->acl_get('a_user'))
    	$sort_key_sql['e'] = 'u.user_email';

    if ($auth->acl_get('u_viewonline'))
        $sort_key_sql['l'] = 'u.user_lastvisit';
    
    $sort_key_sql['m'] = 'u.user_rank DESC, u.user_posts';
    $s_sort_key = '';
    // Additional sorting options for user search ... if search is enabled, if not
    // then only admins can make use of this (for ACP functionality)
    $sql_select = $sql_where_data = $sql_from = $sql_where = $order_by = '';

    // we are in searchuser mode
    if ($mode == 'searchuser' || $auth->acl_get('a_')) {
        $username	= $fieldsToSearch['username'];//request_var('username', '', true);
	$email		= strtolower($fieldsToSearch['email']); // strtolower(request_var('email', ''));
	$icq		= $fieldsToSearch['icq']; //request_var('icq', '');
	$aim		= $fieldsToSearch['aim']; //request_var('aim', '');
	$yahoo		= $fieldsToSearch['yahoo']; //request_var('yahoo', '');
	$msn		= $fieldsToSearch['msn']; // request_var('msn', '');
	$jabber		= $fieldsToSearch['jabber']; //request_var('jabber', '');
	$search_group_id = $fieldsToSearch['seach_group_id']; //request_var('search_group_id', 0);

	$joined_select	= $fieldsToSearch['joined_select']; //request_var('joined_select', 'lt');
	$active_select	= $fieldsToSearch['active_select']; //request_var('active_select', 'lt');
	$count_select	= $fieldsToSearch['count_select']; //request_var('count_select', 'eq');
	
        $joined	        = explode('-', $fieldsToSearch['joined']); //request_var('joined', ''));
	$active		= explode('-', $fieldsToSearch['active']);//request_var('active', ''));
	$count		= $fieldsToSearch['count'];//(request_var('count', '') !== '') ? request_var('count', 0) : '';
	$ipdomain	= $fieldsToSearch['ip']; //request_var('ip', '');

	$find_key_match = array('lt' => '<', 'gt' => '>', 'eq' => '=');
        
	$sql_where .= ($username) ? $validateConj.' u.username_clean ' . $db->sql_like_expression(str_replace('*', $db->any_char, utf8_clean_string($username))) : '';
	$sql_where .= ($auth->acl_get('a_user') && $email) ? $validateConj.' u.user_email ' . $db->sql_like_expression(str_replace('*', $db->any_char, $email)) . ' ' : '';
	$sql_where .= ($icq) ? $validateConj.' u.user_icq ' . $db->sql_like_expression(str_replace('*', $db->any_char, $icq)) . ' ' : '';
    	$sql_where .= ($aim) ? $validateConj.'  u.user_aim ' . $db->sql_like_expression(str_replace('*', $db->any_char, $aim)) . ' ' : '';
	$sql_where .= ($yahoo) ? $validateConj.' u.user_yim ' . $db->sql_like_expression(str_replace('*', $db->any_char, $yahoo)) . ' ' : '';
	$sql_where .= ($msn) ? $validateConj.' u.user_msnm ' . $db->sql_like_expression(str_replace('*', $db->any_char, $msn)) . ' ' : '';
	$sql_where .= ($jabber) ? $validateConj.' u.user_jabber ' . $db->sql_like_expression(str_replace('*', $db->any_char, $jabber)) . ' ' : '';
	$sql_where .= (is_numeric($count)) ? $validateConj.' u.user_posts ' . $find_key_match[$count_select] . ' ' . (int) $count . ' ' : '';
	$sql_where .= (sizeof($joined) > 1) ? $validateConj." u.user_regdate " . $find_key_match[$joined_select] . ' ' . gmmktime(0, 0, 0, intval($joined[1]), intval($joined[2]), intval($joined[0])) : '';
	$sql_where .= ($auth->acl_get('u_viewonline') && sizeof($active) > 1) ? $validateConj." u.user_lastvisit " . $find_key_match[$active_select] . ' ' . gmmktime(0, 0, 0, $active[1], intval($active[2]), intval($active[0])) : '';
	$sql_where .= ($search_group_id) ? $validateConj." u.user_id = ug.user_id '.$validateConj.' ug.group_id = $search_group_id .$validateConj . ' ug.user_pending = 0 " : '';

	if ($search_group_id) {
	    $sql_from = ', ' . USER_GROUP_TABLE . ' ug ';
    	}

	if ($ipdomain && $auth->acl_getf_global('m_info')) {
	    if (strspn($ipdomain, 'abcdefghijklmnopqrstuvwxyz')) {
		$hostnames = gethostbynamel($ipdomain);

		if ($hostnames !== false) {
		    $ips = "'" . implode('\', \'', array_map(array($db, 'sql_escape'), preg_replace('#([0-9]{1,3}\.[0-9]{1,3}[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#', "\\1", gethostbynamel($ipdomain)))) . "'";
		} else {
		    $ips = false;
		}
	    } else {
		$ips = "'" . str_replace('*', '%', $db->sql_escape($ipdomain)) . "'";
	    }

	    if ($ips === false) {
		// A minor fudge but it does the job :D
		$sql_where .= " AND u.user_id = 0";
	    }else {
		$ip_forums = array_keys($auth->acl_getf('m_info', true));

		$sql = 'SELECT DISTINCT poster_id
			    FROM ' . POSTS_TABLE . '
			    WHERE poster_ip ' . ((strpos($ips, '%') !== false) ? 'LIKE' : 'IN') . " ($ips)
			    AND forum_id IN (0, " . implode(', ', $ip_forums) . ')';
		$result = $db->sql_query($sql);

                if ($row = $db->sql_fetchrow($result)) {
		    $ip_sql = array();
		    do {
			$ip_sql[] = $row['poster_id'];
		    }
		    while ($row = $db->sql_fetchrow($result));

		    $sql_where .= ' AND ' . $db->sql_in_set('u.user_id', $ip_sql);
		} else {
		    // A minor fudge but it does the job :D
		    $sql_where .= " AND u.user_id = 0";
		}
		unset($ip_forums);

		$db->sql_freeresult($result);
	    }
	}
    }

 //   $first_char = //request_var('first_char', '');
 $sql_where = substr($sql_where,3);
// $sql_where = ' AND '.$sql_where;
        
    if ($first_char == 'other') {
	for ($i = 97; $i < 123; $i++) {
	    $sql_where .= ' AND u.username_clean NOT ' . $db->sql_like_expression(chr($i) . $db->any_char);
    	}
    } else if ($first_char) {
	$sql_where .= ' AND u.username_clean ' . $db->sql_like_expression(substr($first_char, 0, 1) . $db->any_char);
    }

		
    // Sorting and order
    if (!isset($sort_key_sql[$sort_key])) {
        $sort_key = $default_key;
    }

    $order_by .= $sort_key_sql[$sort_key] . ' ' . (($sort_dir == 'a') ? 'ASC' : 'DESC');

    // Count the users ...
    if ($sql_where) {
	$sql = 'SELECT COUNT(u.user_id) AS total_users
				FROM ' . USERS_TABLE . " u$sql_from
				WHERE u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ")
				$sql_where";
        $result = $db->sql_query($sql);
    	$total_users = (int) $db->sql_fetchfield('total_users');
	$db->sql_freeresult($result);
    } else {
	$total_users = $config['num_users'];
    }

    // Some search user specific data
    if ($mode == 'searchuser' || $auth->acl_get('a_')) {
	if ($auth->acl_gets('a_group', 'a_groupadd', 'a_groupdel')) {
	    $sql = 'SELECT group_id, group_name, group_type
					FROM ' . GROUPS_TABLE . '
					ORDER BY group_name ASC';
	} else {
            $sql = 'SELECT g.group_id, g.group_name, g.group_type
					FROM ' . GROUPS_TABLE . ' g
					LEFT JOIN ' . USER_GROUP_TABLE . ' ug
						ON (
							g.group_id = ug.group_id
							AND ug.user_id = ' . $user->data['user_id'] . '
							AND ug.user_pending = 0
						)
					WHERE (g.group_type <> ' . GROUP_HIDDEN . ' OR ug.user_id = ' . $user->data['user_id'] . ')
					ORDER BY g.group_name ASC';
	}
        $result = $db->sql_query($sql);

	// Get us some users :D
	$sql = "SELECT *
			FROM " . USERS_TABLE . " u
				$sql_from
			WHERE u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ")
				$sql_where
			ORDER BY $order_by";
                        
        // we will take the $usersPerPage at page index $start
	$result = $db->sql_query_limit($sql, $usersPerPage, $start);

	$user_list = array();
	while ($row = $db->sql_fetchrow($result)) {
            $cUser = array('username' => $row['username'],
                           'user_id' => $row['user_id'],
                        //   'user_regdate' => $row['user_regdate'],
                      //     'user_email' => (isUserAdmin($user) == true ? $row['user_email'] : ''),
                         //  'user_lastvisit' => $row['user_lastvisit'],
                           'user_avatar' => $row['user_avatar']);
	    $user_list[] = $cUser;
	}
	$db->sql_freeresult($result);
        return array('total_users' => $total_users,
                     'users_list' => $user_list,
                     'current_page' => $pageIdx,
                     'total_pages' => ceil($total_users / $usersPerPage));
    }
}

?>