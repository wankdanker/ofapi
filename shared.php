<?

/**
* 
* OFAPI (Opensource Forum API)
* XML-RPC Server Comunication Layer
*
*
* Written by:
* Daniele Margutti (http://www.malcom-mac.com - malcom.mac@gmail.com)
* Roberto Beretta (http://www.rietiforum.com - roberto.alpha@gmail.com)
*
* 
* @package OFAPI
* @version v 1.0 2008/06/28
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


function _init() {
	global $user, $auth;
	$user->session_begin();
	$auth->acl($user->data);
	$user->setup();
}

/*
* @ignore
*/
function _isUserLogged() {
        global $user,$auth,$db;

 	//$user->session_begin(); (sta roba pare che resetti tutto se ti logghhhhi!)
	$auth->acl($user->data);
	$user->setup();
        
       // return $user->data['username'];
	//echo('LOGGED AS: {'.print_r($user->data['username'].'}  '));    
	return !($user->data['user_id'] == ANONYMOUS || $user->data['is_bot']);
}

/*
* @ignore
*/
function _getSQLEscapeCharacterAccordingToLayer() {
    // Do not change this (it is defined as _f_={forum_id}x within session.php)
    $reading_sql = '';

    // Specify escape character for MSSQL
    if ($db->sql_layer == 'mssql' || $db->sql_layer == 'mssql_odbc') {
    	$reading_sql .= " ESCAPE '\\'";
    }
    return $reading_sql;
}

/*
* @ignore
*/
function _getPrintableUsersInfoFromRow($row) {
    global $user;    
    $userInfo = array('username' =>  $row['username'],
                 'user_id'  =>  $row['user_id'],
                 'user_regdate' =>  $row['user_regdate'],
                 'user_lastvisit' => $row['user_lastvisit'],
                 'user_lastpost_time' => $row['user_lastpost_time'],
                 'user_inactive_time' =>    $row['user_inactive_time'],
                 'user_avatar' =>     $avatar_img = get_user_avatar($row['user_avatar'], $row['user_avatar_type'], $row['user_avatar_width'], $row['user_avatar_height']));
    
    return $userInfo;
}

/*
* @ignore
*/
function _compileExcludeForumsPredicateFromList($exclude_forumsIdsList) {
    $sql_where = '';
    $exclude_forums = explode(',',$exclude_forumsIdsList);
    foreach ($exclude_forums as $i => $id) {
	if ($id > 0) {
	    $sql_where .= ' AND forum_id <> ' . trim($id);
    	}
    }
    return $sql_where;
}

/**
* Get 
*
*
*/
function getArgsAndLogin($args) {
print $args;
	if (count($args) == 1){
		return $args[0];
	}

	if (sizeof($args['auth']) > 0) {
		$authData = $args['auth'];
		$log = login(array($authData[0], $authData[1], true, true));
		if ($log != OP_LOGIN_SUCCESS) return $log;
	}

	return $args['data'];
}

/**
* Return the list of usernames by giving an array of user ids
* <b>PARAMETERS:</b> <i>$listOfIds</i> [array] list of user ids
* @since 1.0
* @version 1.0
* @see FUNCTION DOES NOT REQUIRE LOGIN
* @return array an array of usernames
*/

function getUsernamesFromIds($args) {
    $listOfIds = $args[0];
    if (sizeof($listOfIds) == 0) return null;
    
    $user_names_foFill = array();
    user_get_id_name(&$listOfIds,$user_names_foFill);
    return $user_names_foFill;
}


/**
* Return the list of user ids by giving an array of usernames
* <b>PARAMETERS:</b> <i>$listOfNames</i> [array] list of user ids
* @since 1.0
* @version 1.0
* @see FUNCTION DOES NOT REQUIRE LOGIN
* @return array an array of user ids
*/
function getUserIdsFromNames($args) {
    $listOfNames = $args[0];
    if (sizeof($listOfNames) == 0) return null;
    
    $user_ids_foFill = array();
    user_get_id_name($user_ids_foFill,&$listOfNames);
    return $user_ids_foFill; 
}

/**
* Check if an user is an administrator (*NOT FINISHED*)
* @since 1.0
* @version 1.0
* @see FUNCTION DOES NOT REQUIRE LOGIN
* @todo QUESTA FUNZIONE NON FUNZIONA PER UN CAZZO
* @return boolean
*/
function isUserAdmin($user) {
    return false;
}

/*
* @ignore
*/
function _parseResultsFromType($result,$show_results) {
    global $db;
    $finalList = array();
    if ($show_results == 'posts') {
         while ($row = $db->sql_fetchrow($result)) {
            //$id_ary[] = $row[$field];
            // 	$whatReturn = "p.post_id,p.forum_id,p.post_time,p.post_subject,p.post_edit_time,p.post_edit_user";
            $finalList[] = array(
                                'post_id' =>  $row['post_id'],
                                'forum_id' =>  $row['forum_id'],
                                'post_time' =>  $row['post_time'],
                                'post_subject' =>  $row['post_subject'],
                                'post_edit_time' =>  $row['post_edit_time'],
                                'post_edit_user' =>  $row['post_edit_user']
            );
        }
    } else {
        while ($row = $db->sql_fetchrow($result)) {
           // $id_ary[] = $row[$field];
            // 	$whatReturn = "t.topic_id,t.forum_id,t_topic_title,t_topic_poster,t.topic_time,t.topic_views,t.topic_replies_real,t.topic_first_post_id,
            //                 t.topic_first_poster_name,t.topic_last_post_id,t.topic_last_poster_id,t.topic_last_poster_name,t.topic_last_post_time";
            $prova = 'merda';
            $finalList[] = array(
                                'topic_id' =>  $row['topic_id'],
                                'forum_id' =>  $row['forum_id'],
                                'topic_title' =>  $row['topic_title'],
                                'topic_poster' =>  $row['topic_poster'],
                                'topic_time' =>  $row['topic_time'],
                                'topic_views' =>  $row['topic_views'],
                                'topic_replies_real' =>  $row['topic_replies_real'],
                                'topic_first_post_id' =>  $row['topic_first_post_id'],
                                'topic_first_poster_name' =>  $row['topic_first_poster_name'],
                                'topic_last_post_id' =>  $row['topic_last_post_id'],
                                'topic_last_poster_id' =>  $row['topic_last_poster_id'],
                                'topic_last_poster_name' =>  $row['topic_last_poster_name'],
                                'topic_last_post_time' =>  $row['topic_last_post_time'],
                                'topic_poster' => $row['topic_poster']
            );

        }
    }
    return $finalList;
}

?>
