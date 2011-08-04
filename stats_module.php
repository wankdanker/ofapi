<?php

/**
* @package OFAPI
* @version v 1.0 2008/06/28
* @version 1.1 (2008/07/29) Added: <i>getBoardAdmins(),getModsList(),getModsFromForumID()</i>
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
* Return number of online guests based upon data from last 5 minutes
* <b>PARAMETERS:</b> NOT REQUIRED
* @since 1.0
* @version 1.0
* @return integer number of online guests
* @see FUNCTION DOES NOT REQUIRE LOGIN
*/

function getNumberOfOnlineGuests() {
    global $user,$db,$config;
    $reading_sql = _getSQLEscapeCharacterAccordingToLayer();

    $sql = 'SELECT COUNT(DISTINCT s.session_ip) as num_guests
                FROM ' . SESSIONS_TABLE . ' s
		WHERE s.session_user_id = ' . ANONYMOUS . '
		    AND s.session_time >= ' . (time() - ($config['load_online_time'] * 60)) . $reading_sql;
    $result = $db->sql_query($sql);
    $guests_online = (int) $db->sql_fetchfield('num_guests');
    $db->sql_freeresult($result);
    return $guests_online;
}

/**
* Return several informations about connected users (based upon data from last 5 minutes).
* <b>PARAMETERS:</b> NOT REQUIRED
* @since 1.0
* @version 1.0
* @see FUNCTION REQUIRE LOGIN (RETURN GENERAL_ERR_MUSTLOGGED)
* @return array This function return an array with these keys: <br /><br />
*				- <b>'connected_users'</b> 		<i>[array]</i>		this array contains logged users. Each item is an array with these keys: <br /><br/>
*						'connected_users' objects are dictionaries with these keys:<br/>
*								<b>'user_link'</b> 	link to user's profile page <br/>
*								<b>'user_id'</b> 	user's id <br/>
*								<b>'user_name'</b> 	clear string with the username <br/>
*								<b>'user_type'</b> 	define type of user (0=USER_NORMAL,1=USER_INACTIVE,3=USER_FOUNDER)<br/>
*
*				- <b>'logged_visibles'</b> 		<i>[integer]</i> 	number of logged an visible users <br />
*				- <b>'logged_invisible'</b> 	<i>[integer]</i> 	number of logged but invisible users <br />
*				- <b>'guests_online'</b> 		<i>[integer]</i> 	return the number of online guests connected to the board
*/

function getOnlineUsers() {
    global $user,$db,$config,$auth,$phpEx;

    if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;

    // something about fucking multiple db layers support
    $reading_sql = _getSQLEscapeCharacterAccordingToLayer();
    
    // grab the online users according to the load time of the forum    
    $sql = 'SELECT u.username, u.user_id, u.user_type, u.user_allow_viewonline, u.user_colour, s.session_ip, s.session_viewonline
		FROM ' . USERS_TABLE . ' u, ' . SESSIONS_TABLE . ' s
		WHERE s.session_time >= ' . (time() - (intval($config['load_online_time']) * 60)) . 
			$reading_sql .
			((!$config['load_online_guests']) ? ' AND s.session_user_id <> ' . ANONYMOUS : '') . '
			AND u.user_id = s.session_user_id 
		ORDER BY u.username ASC, s.session_ip ASC';

    $result = $db->sql_query($sql);

    $user_online_list = array(); // list of online users
    $logged_visible_online = 0; // visible users number
    $logged_hidden_online = 0; // invisible users number
    
    while ($row = $db->sql_fetchrow($result)) {
	// User is logged in and therefore not a guest
	if ($row['user_id'] != ANONYMOUS) {
	    // Skip multiple sessions for one user
	    if ($row['user_id'] != $prev_user_id) {
		// user is visible in online lists
                if ($row['user_allow_viewonline'] && $row['session_viewonline']) {
                    $user_online_link = $row['username'];
		    $logged_visible_online++;
		} else {
		    // a new 007 users is now online, we will keep it secure!
                    $logged_hidden_online++;
                }

		if (($row['user_allow_viewonline'] && $row['session_viewonline']) || $auth->acl_get('u_viewonline')) {
                    if ($row['user_type'] <> USER_IGNORE) {
			$cUser = array('user_link' => append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=viewprofile&amp;u=' . $row['user_id']),
                                       'user_id' => $row['user_id'],
                                       'user_name' => $row['username'],
                                       'user_type' => $row['user_type']);
                        $user_online_list[] = $cUser;
                    }
                }
	    }
	    $prev_user_id = $row['user_id'];
	} else {
	    // Skip multiple sessions for one user
            if ($row['session_ip'] != $prev_session_ip) {
		$guests_online++;
	    }
	}

	$prev_session_ip = $row['session_ip'];
    }
    $db->sql_freeresult($result);
    $final = array('connected_users' => $user_online_list,
                   'logged_visibles' => $logged_visible_online,
                   'logged_invisible' => $logged_hidden_online,
                   'guests_online' => getNumberOfOnlineGuests()
                   );
    return $final;
}


/**
* Return a list with the board's top posters<br/><br/>
* <b>PARAMETERS:</b><br/>
* - [integer] 	<b>$limitNumberTo</b> 		[integer]	max items to show
* @since 1.0
* @version 1.0
* @see FUNCTION REQUIRE LOGIN (RETURN GENERAL_ERR_MUSTLOGGED)
* @return array See <i>getOnlineUsers()</i> for results type.
*/
function getTopPosters($args) {
    global $user,$phpEx,$db;
   
    if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;

    if (sizeof($args) == 0) $limitNumberTo = 5;
    $limitNumberTo = $args[0];
    
    $sql = 'SELECT user_id, username, user_posts, user_colour
	FROM ' . USERS_TABLE . '
	WHERE user_type <> ' . USER_IGNORE . '
		AND user_posts <> 0
	ORDER BY user_posts DESC';
    $result = $db->sql_query_limit($sql, $limitNumberTo);

    $list = array();
    while( ($row = $db->sql_fetchrow($result)) && ($row['username']) ) {
        $cUser = array('link_to_userspost' => append_sid("{$phpbb_root_path}search.$phpEx", 'author_id=' . $row['user_id'] . '&amp;sr=posts'),
                       'user_name' => $row['username'],
                       'user_id' => $row['user_id'],
                       'number_of_posts' => $row['user_posts']);
        $list[] = $cUser;
    }
    $db->sql_freeresult($result);
    
    return $list;
}

/**
* Return board statistics informations.
* <b>PARAMETERS:</b> NOT REQUIRED
* @since 1.0
* @version 1.0
* @see FUNCTION DOES NOT REQUIRE LOGIN
* @return array This function return an array with these keys: <br /><br />
*				- <b>'announcment_total'</b> 	<i>[integer]</i> 	number of announcements published on board <br />
*				- <b>'sticky_total'</b> 		<i>[string]</i> 	number of stickies published on board<br />
*				- <b>'attachments_total'</b> 	<i>[integer]</i> 	number of attachments published on board <br />
*				- <b>'total_posts'</b> 			<i>[integer]</i> 	number of posts <br />
*				- <b>'total_topics'</b> 		<i>[integer]</i> 	number of topics <br />
*				- <b>'total_users'</b> 			<i>[integer]</i> 	number of registered users on board <br />
*				- <b>'board_days'</b> 			<i>[integer]</i> 	days elapsed since board born date <br />
*				- <b>'topics_per_day'</b> 		<i>[integer]</i>	average number of topics published each day <br />
*				- <b>'posts_per_day'</b> 		<i>[integer]</i> 	average number of posts published each day <br />
*				- <b>'users_per_day'</b> 		<i>[integer]</i> 	average number of registrations each day <br />
*				- <b>'topics_per_users'</b> 	<i>[integer]</i> 	average new topics published per users <br />
*				- <b>'posts_per_user'</b> 		<i>[integer]</i> 	average number of posts per user <br />
*				- <b>'posts_per_topic'</b> 		<i>[integer]</i> 	average number of posts per topic <br />
*/


function getGeneralForumStatistics() {
    global $db, $user, $config;
   
    $total_ann = 0;
    // GET TOTAL ANOUNCEMENT
    $sql = 'SELECT COUNT(distinct t.topic_id) AS announcment_total
		FROM ' . TOPICS_TABLE . ' t, ' . POSTS_TABLE . ' p
		WHERE t.topic_type = ' . POST_ANNOUNCE . '
		AND p.post_id = t.topic_first_post_id';
    $result = $db->sql_query($sql);
    $total_ann = (int) $db->sql_fetchfield('announcment_total');
    $db->sql_freeresult($result);
    
    
    // GET TOTAL STICKIES
    $total_sticky = 0;
	$sql = 'SELECT COUNT(distinct t.topic_id) AS sticky_total
			FROM ' . TOPICS_TABLE . ' t, ' . POSTS_TABLE . ' p
			WHERE t.topic_type = ' . POST_STICKY . '
		    	AND p.post_id = t.topic_first_post_id';
    $result = $db->sql_query($sql);
    $total_sticky = (int) $db->sql_fetchfield('sticky_total');
    $db->sql_freeresult($result);

    // GET TOTAL ATTACHMENTS
    $sql = 'SELECT COUNT(attach_id) AS attachments_total
		FROM ' . ATTACHMENTS_TABLE;
    $result = $db->sql_query($sql);
    $total_attach = (int) $db->sql_fetchfield('attachments_total');
    $db->sql_freeresult($result);

    // Set some stats, get posts count from forums data if we... hum... retrieve all forums data
    $total_posts		= $config['num_posts'];
    $total_topics		= $config['num_topics'];
    $total_users		= $config['num_users'];

    // avarage stat
    $board_days = ( time() - $config['board_startdate'] ) / 86400;

    $topics_per_day		= ($total_topics) ? round($total_topics / $board_days, 0) : 0;
    $posts_per_day		= ($total_posts) ? round($total_posts / $board_days, 0) : 0;
    $users_per_day		= round($total_users / $board_days, 0);
    $topics_per_user	        = ($total_topics) ? round($total_topics / $total_users, 0) : 0;
    $posts_per_user		= ($total_posts) ? round($total_posts / $total_users, 0) : 0;
    $posts_per_topic	        = ($total_topics) ? round($total_posts / $total_topics, 0) : 0;

    if ($topics_per_day > $total_topics) { $topics_per_day = $total_topics; }
    if ($posts_per_day > $total_posts) { $posts_per_day = $total_posts; }
    if ($users_per_day > $total_users) { $users_per_day = $total_users; }
    if ($topics_per_user > $total_topics) { $topics_per_user = $total_topics; }
    if ($posts_per_user > $total_posts) { $posts_per_user = $total_posts; }
    if ($posts_per_topic > $total_posts) { $posts_per_topic = $total_posts; }
    
    return array('announcment_total' => $total_ann,
                 'sticky_total' => $total_sticky,
                 'attachments_total' => $total_attach,
                 'total_posts' =>       $total_posts,
                 'total_topics' =>      $total_topics,
                 'total_users' =>       $total_users,
                 'board_days'  =>       $board_days,
                 'topics_per_day' =>    $topics_per_day,
                 'posts_per_day' =>     $posts_per_day,
                 'users_per_day' =>     $users_per_day,
                 'topics_per_user' =>   $topics_per_user,
                 'posts_per_user' =>    $posts_per_user,
                 'posts_per_topic' =>   $posts_per_topic);
}

/**
* Return last subscribed members of the board. You must be logged in order to use this function.<br/>
* <b>PARAMETERS:</b> <br />
* 	<i>$limitNumberTo:</i> 	[integer]	limit results to this number
* @since 1.0
* @version 1.0
* @see FUNCTION REQUIRE LOGIN (RETURN GENERAL_ERR_MUSTLOGGED)
* @return array This function return an array with these keys: <br /><br />
*				- <b>'username'</b> 	<i>[string]</i> 		subcribed member username <br />
*				- <b>'user_id'</b> 		<i>[integer]</i> 		id of member<br />
*				- <b>'joined_on'</b> 	<i>[long integer]</i> 	member join date (in unix format) <br />
*/

function getLastSubscribedMembers($args) {
    global $db;
   
    if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;

    if (sizeof($args) == 0) $limitNumberTo = 5;
    $limitNumberTo = $args[0];
    
    $sql = 'SELECT user_id, username, user_regdate, user_colour
	FROM ' . USERS_TABLE . '
	WHERE user_type <> ' . USER_IGNORE . '
		AND user_inactive_time = 0
	ORDER BY user_regdate DESC';
    $result = $db->sql_query_limit($sql, $limitNumberTo);

    $list = array();
    while( ($row = $db->sql_fetchrow($result)) && ($row['username']) ) {
        $cUser = array('username' => $row['username'],
                       'user_id' => $row['user_id'],
                       'joined_on' => $row['user_regdate']);
        $list[] = $cUser;
    }
    
    return $list;
}

/**
* Return a random member of the board with it's info (include email according to user preferences or only if you are logged as admin).
* <b>PARAMETERS:</b> NOT REQUIRED
* @since 1.0
* @version 1.0
* @see FUNCTION REQUIRE LOGIN (ONLY REGISTERED USERS) (return GENERAL_ERR_MUSTLOGGED)
* @return array This function return an array with these keys: <br /><br />
*				- <b>'username'</b> 				<i>[string]</i> 			subcribed member username <br />
*				- <b>'user_id'</b> 					<i>[integer]</i> 			id of member<br />
*				- <b>'user_regdate'</b> 			<i>[long integer]</i> 		member join date (in unix format) <br />
*				- <b>'user_lastvisit'</b> 			<i>[long integer]</i> 		member last visit date (in unix format) <br />
*				- <b>'user_lastpost_time'</b> 		<i>[long integer]</i> 		date of last post from user (in unix format) <br />
*				- <b>'user_inactive_time'</b> 		<i>[long integer]</i> 		user inactivity time (in unix format) <br />
*				- <b>'user_avatar'</b> 				<i>[string]</i> 			html link to avatar picture <br />
*/

function getRandomMemberInfo() {
    global $db;

    if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;    

    switch ($db->sql_layer) {
	case 'postgres':
	    $sql = 'SELECT *
                        FROM ' . USERS_TABLE . '
			WHERE user_type <> ' . USER_IGNORE . '
			AND user_type <> ' . USER_INACTIVE . '
			ORDER BY RANDOM()';
	    break;
	
	case 'mssql':
	case 'mssql_odbc':
	    $sql = 'SELECT *
                        FROM ' . USERS_TABLE . '
                    	WHERE user_type <> ' . USER_IGNORE . '
			AND user_type <> ' . USER_INACTIVE . '
			ORDER BY NEWID()';
	    break;
	
	default:
	    $sql = 'SELECT *
			FROM ' . USERS_TABLE . '
			WHERE user_type <> ' . USER_IGNORE . '
			AND user_type <> ' . USER_INACTIVE . '
			ORDER BY RAND()';
	    break;
    }

    $result = $db->sql_query_limit($sql, 1);
    $row = $db->sql_fetchrow($result);

    return _getPrintableUsersInfoFromRow(&$row);
}


/**
* Return a list with the most used words into the board with descending sort
* <b>PARAMETERS:</b> <i>$maxWordsToList</i> number of words to show
* @since 1.0
* @version 1.0
* @todo THIS METHOD DOES NOT WORK WELL AND IT'S TOO SLOW. WE SHOULD REMOVE IT.
* @see FUNCTION REQUIRE LOGIN (ONLY REGISTERED USERS) (return GENERAL_ERR_MUSTLOGGED)
* @return array This function return an array of strings
*/

function getWordsgraphList($args) {
    global $db,$user;

    if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;    

    if (sizeof($args) == 0) {
    	$maxWordsToList = 20;
    } else {
    	$maxWordsToList = $args[0];
    }
    
    $words_array = array();
    
    // Get words and number of those words
    $sql = 'SELECT l.word_text, COUNT(*) AS word_count  
	FROM ' . SEARCH_WORDLIST_TABLE . ' AS l, ' . SEARCH_WORDMATCH_TABLE . ' AS m
	WHERE m.word_id = l.word_id 
	GROUP BY m.word_id 
	ORDER BY word_count DESC';
    $result = $db->sql_query_limit($sql, $maxWordsToList);

    while ($row = $db->sql_fetchrow($result)) {
	$word = strtolower($row['word_text']);
	$words_array[$word] = $row['word_count'];
    }
    $db->sql_freeresult($result);

    $minimum = 1000000;
    $maximum = -1000000;

    foreach ( array_keys($words_array) as $word ){
	if ( $words_array[$word] > $maximum ) {
		$maximum = $words_array[$word];
	}
	if ( $words_array[$word] < $minimum ) {
		$minimum = $words_array[$word];
	}
    }

    $words = array_keys($words_array);
    sort($words);
    return $words;
}

/**
* Return users birthdays today and if $includeBirthsNextDays > 0 into the next $includeBirthsNextDays days
* <b>PARAMETERS:</b> <i>$includeBirthsNextDays</i> if > 0 includes birthdays into the next specified days
* @since 1.0
* @version 1.0
* @see FUNCTION REQUIRE LOGIN (ONLY REGISTERED USERS) (return GENERAL_ERR_MUSTLOGGED)
* @return array return users birthdays today and if $includeBirthsNextDays > 0 into the next $includeBirthsNextDays days
*				<br/> The result is an array with two keys: <br/>
*				- <b>'todays_birthdays'</b> <i>[array]</i>		an array with today's birthdays <br/>
*				- <b>'head_birthdays'</b> 	<i>[array]</i>		an array with birthdays into the next given days <br/><br/>
*				
*				Each object of these array is a dictionary array with these keys: <br/>
*				- <b>'username'</b> 		<i>[string]</i> 	full username <br/>
*				- <b>'user_id'</b> 			<i>[integer]</i> 	user id <br/>
*				- <b>'age'</b> 				<i>[integer]</i> 	future age <br/>
*				- <b>'born_year'</b> 		<i>[integer]</i> 	born year date <br/>
*				- <b>'user_birthday'</b> 	<i>[string]</i> 	born full date in unix epoch time format (DD-M-YYYY) <br/>
*/


function getTodaysBirthdays($args) {
	global $user,$db,$auth,$config;
	
	if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;    
    
    $includeBirthsNextDays = $args[0];
    if ($includeBirthsNextDays == null) $includeBirthsNextDays = 0;
    
    global $config,$db,$user;
    
    $birthday_list = array(); // today's birthdays
    $birthdays_head_list = array(); // next $includeBirthsNextDays birthdays
    
    // board supports birthdays?
    if ($config['allow_birthdays']) {
        // make a today date
	$now = getdate(time() + $user->timezone + $user->dst - date('Z'));
	$today = (mktime(0, 0, 0, $now['mon'], $now['mday'], $now['year']));
	
        // different query for each db sublayer
	switch ($db->sql_layer) {
		case 'mssql':
		case 'mssql_odbc':
			$sql = 'SELECT user_id, username, user_colour, user_birthday
			FROM ' . USERS_TABLE . "
			WHERE user_birthday <> ''
			AND user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ') ORDER BY user_birthday ASC';
		break;
	
		default:
			$sql = 'SELECT user_id, username, user_colour, user_birthday
			FROM ' . USERS_TABLE . "
			WHERE user_birthday <> ''
			AND user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ') ORDER BY SUBSTRING(user_birthday FROM 4 FOR 2) ASC, SUBSTRING(user_birthday FROM 1 FOR 2) ASC, username_clean ASC';
		break;
	}
	 
	$result = $db->sql_query($sql);
    
        // fetch results
	while ($row = $db->sql_fetchrow($result)) {
	    $birthdaydate = (gmdate('Y') . '-' . trim(substr($row['user_birthday'],3,-5)) . '-' . trim(substr($row['user_birthday'],0,-8) ));
            $user_birthday = strtotime($birthdaydate);
            
            $age = (int) substr($row['user_birthday'], -4);
            $cUser = array('username' => $row['username'],
                               'user_id' => $row['user_id'],
                               'age' => $now['year'] - $age,
                               'born_year' => $age,
                               'user_birthday' => $row['user_birthday']);
                
	    // wow a birthday today!
            if($user_birthday == $today)
		// compile informations    
                $birthday_list[]= $cUser;
		
            // should we include birthdays into the next few days?
	    if( $includeBirthsNextDays > 0 ) {
		if ( $user_birthday >= ($today + 86400) && $user_birthday <= ($today + ($includeBirthsNextDays * 86400) ) )
                    $birthdays_head_list[] = $cUser;
	    }
	}
    }
    $db->sql_freeresult($result);
    return array('todays_birthdays' => $birthday_list,
                 'head_birthdays' => $birthdays_head_list);
}


/*
* @ignore
*/
function _getAdminsGroupID() {
         global $db;   
        // Admin group id...
    // THIS QUERY RETURN THE GROUP ID FOR ADMINS
    $sql = 'SELECT group_id
	FROM ' . GROUPS_TABLE . "
	WHERE group_name = 'ADMINISTRATORS'";
    $result = $db->sql_query($sql);
    $admin_group_id = (int) $db->sql_fetchfield('group_id');
    $db->sql_freeresult($result);
    return $admin_group_id;
}


/**
* Return the list of board leaders
* @since 1.0
* @version 1.0
* @see FUNCTION REQUIRE LOGIN (ONLY REGISTERED USERS) (return GENERAL_ERR_MUSTLOGGED)
* @return array return two array (admins & mods) with object that contains these keys:
*				- <b>'username'</b> 	<i>[string]</i> 	full username <br/>
*				- <b>'user_id'</b> 		<i>[integer]</i> 	user id <br/>
*/

function getBoardAdmins() {
	if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;    
	
        $list = _getAdminAndModsInfos(0);
        return  _parseUsersIdsAndGetUsernames(&$list);
}


/**
* Return the list of moderators
* @since 1.0
* @version 1.0
* @see FUNCTION REQUIRE LOGIN (ONLY REGISTERED USERS) (return GENERAL_ERR_MUSTLOGGED)
* @return array see getBoardAdmins()
*/

function getModsList() {
	if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;    
	
        $list = _getAdminAndModsInfos(1);
        return _parseUsersIdsAndGetUsernames(&$list);
}


/**
* Return the list of moderators of a given forum id
* @since 1.0
* @version 1.0
* @see FUNCTION REQUIRE LOGIN (ONLY REGISTERED USERS) (return GENERAL_ERR_MUSTLOGGED)
* @return array see getBoardAdmins()
*/

function getModsFromForumID($args) {
	if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;    
	
        $forum_id = $args[0];
        $list = _getAdminAndModsInfos(2);
        $forum_mods = array();
	foreach ($list as $key => $value) {
                $cUser = $list[$key];
                if (in_array($forum_id,$cUser)) // is this user the mod for given forum?
                  $forum_mods[] = $key; // if yes we will add to the list
        }
        return _parseUsersIdsAndGetUsernames(&$forum_mods); // return both user id and usernames
}

function _parseUsersIdsAndGetUsernames($list) {
        global $db;
        $sql = 'SELECT username, user_id FROM '.USERS_TABLE.' WHERE user_id IN ('.implode(',',$list).')';
        $result = $db->sql_query($sql);
        // make an array
        $finalList = array();
        while ($row = $db->sql_fetchrow($result)) {
                $cUser = array('username' => $row['username'],'user_id'=>$row['user_id']);
                $finalList[] = $cUser;
        }

        $db->sql_freeresult($result);
        return $finalList;        
}

/*
* @ignore
*/
function _getAdminAndModsInfos($whatReturn) {
        global $phpbb_root_path,$db,$config,$user,$phpEx,$auth;

        // Display a listing of board admins, moderators
		include_once($phpbb_root_path . 'includes/functions_user.' . $phpEx);
		$user_ary = $auth->acl_get_list(false, array('a_', 'm_'), false);
		$admin_id_ary = $global_mod_id_ary = $mod_id_ary = $forum_id_ary = array();
		foreach ($user_ary as $forum_id => $forum_ary) {
			foreach ($forum_ary as $auth_option => $id_ary) {
				if (!$forum_id) {
					if ($auth_option == 'a_') {
						$admin_id_ary = array_merge($admin_id_ary, $id_ary);
					} else {
						$global_mod_id_ary = array_merge($global_mod_id_ary, $id_ary);
					}
					continue;
				} else {
                                        $mod_id_ary = array_merge($mod_id_ary, $id_ary);
				}

				if ($forum_id) {
					foreach ($id_ary as $id) {
			
                        			$forum_id_ary[$id][] = $forum_id;
					}
				}
			}
		}

		$admin_id_ary = array_unique($admin_id_ary);
		$global_mod_id_ary = array_unique($global_mod_id_ary);

		$mod_id_ary = array_merge($mod_id_ary, $global_mod_id_ary);
		$mod_id_ary = array_unique($mod_id_ary);

        switch ($whatReturn) {
                case 0:
                        return $admin_id_ary; break;
                case 1:
                        return $mod_id_ary; break;
                case 2:
                        return $forum_id_ary; break;
                
        }
        return nil;
}

/**
* Return the last search engines bots who visited the board
* <b>PARAMETERS:</b>
*	<b>$maxNumbersToShow</b> 	[integer]	max number of bots to show
*
* @since 1.0
* @version 1.0
* @see FUNCTION DOES NOT REQUIRE LOGIN
* @return array return an array of search bots, each object with these keys:<br/>
*				- <b>'bot_name'</b> 		<i>[string]</i> 		bot display name <br/>
*				- <b>'last_visit'</b> 		<i>[long integer]</i> 	last visit (in unix time format)<br/>
*/

function getLastBots($args) {
    global $db,$config;
    
    $maxNumbersToShow = $args[0];
    if ($maxNumbersToShow == 0) $maxNumbersToShow = 5;
    
    // Last x visited bots
    $sql = 'SELECT username, user_colour, user_lastvisit
	FROM ' . USERS_TABLE . '
	WHERE user_type = ' . USER_IGNORE . '
	ORDER BY user_lastvisit DESC';
    $result = $db->sql_query_limit($sql, $maxNumbersToShow);
    $first = true;
    
    $list = array();
    while ($row = $db->sql_fetchrow($result)) {
	if (!$row['user_lastvisit'] && $first == TRUE) {
	} else {
            if( $row['user_lastvisit'] > 0 ) {
                $cItem = array('bot_name' => $row['username'],
                               'last_visit' => $row['user_lastvisit']);
                $list[] = $cItem;
	    }
	}
        $first = false;
    }
    $db->sql_freeresult($result);
    return $list;
}


?>