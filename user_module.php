<?php

/**
* 
* @package OFAPI
* @version v 1.0 2008/06/28
* @version 1.1 (2008/07/29) <b>Fixed:</b> countUnreadPrivateMessages() - <b>Added</b>: setPrivateMessageAsRead(),getUnreadPrivateMessages(),sendPrivateMessage(),sendEmailToUser()
* @version 1.1.3 (2009/09/03) Added number of parameters check
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

include_once('headers.php');

global $phpbb_root_path;
include_once($phpbb_root_path.'/includes/functions_privmsgs.php');

/**
* Return the number of unread private messages about currently logged user
* @since 1.0
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return integer the number of unread private messages
*/

function countUnreadPrivateMessages() {
    global $user;
    if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;
    update_pm_counts(); // refresh cache
	$num = $user->data['user_new_privmsg'];
	return $num;
}

/**
* Mark a private message as read<br/><br/>
* <b>PARAMETERS:</b><br/>
* - [integer] 	<b>$msg_id</b> 		[integer]	target message id
* - [integer] 	<b>$folder_id</b> 	[integer]	parent folder id (where the message is located)
*
* @since 1.1
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return integer the number of unread private messages
*/
function setPrivateMessageAsRead($args) {
        // if number of parameters is lower than requested from headers this function return GENERAL_WRONG_PARAMETERS
	if (count($args) < 2) return GENERAL_WRONG_PARAMETERS;
        
        global $user;
        $msg_id = $args[0];
        $folder_id = $args[1];
        if (_isUserLogged() == false)
                return GENERAL_ERR_MUSTLOGGED;
        update_unread_status(true,$msg_id,$user->data['user_id'],$folder_id);
        echo 'ok';
}

/**
* Return the list of unread private messages in your inboxes<br/><br/>
* <b>PARAMETERS:</b><br/>
* - [integer] 	<b>$msg_id</b> 		[integer]	target message id
* - [integer] 	<b>$folder_id</b> 	[integer]	parent folder id (where the message is located)
*
* @since 1.1
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return array a list with private messages. Each object is a dictionary with these keys:<br/>
*				- <b>'msg_id'</b>			[integer]		the message unique id<br/>
*				- <b>'user_id'</b>			[integer]		the author user id<br/>
*				- <b>'username'</b>			[string]		the author username<br/>
*				- <b>'message_time'</b>		[integer]		the message creation date in unix format (time interval '70)<br/>
*				- <b>'message_subject'</b> 	[string]		subject of the message<br/>
*				- <b>'folder_id'</b>		[integer]		parent folder unique id
*/
function getUnreadPrivateMessages() {
        global $user,$db;
        if (_isUserLogged() == false)
                return GENERAL_ERR_MUSTLOGGED;
        $user_id = $user->data['user_id'];
        
        $sql = 'SELECT t.*, p.*, u.*
		FROM phpbb_privmsgs p, phpbb_privmsgs_to  t,  phpbb_users u
		WHERE t.msg_id = p.msg_id
			AND p.author_id = u.user_id
			AND t.folder_id NOT IN ('. PRIVMSGS_HOLD_BOX . ")
			AND t.user_id = $user_id
			AND pm_unread = 1";
                        // ... REMOVED: AND t.folder_id NOT IN (' . PRIVMSGS_NO_BOX . ', ' . PRIVMSGS_HOLD_BOX . ")
                        // but sometimes inbox messages are marked as NO_BOX so it will be not visible. Why? Really don't know...        
          $result = $db->sql_query($sql);
        // and put the results in a fantastic array
        $privateMsgs = array();
        while ($row = $db->sql_fetchrow($result)) {
            $pMsg = array('msg_id' => $row['msg_id'],
                          'user_id' => $row['user_id'],
                          'username' => $row['username_clean'],
                          'message_time' => $row['message_time'],
                          'message_subject' => $row['message_subject'],
                          'message_text' => _applyCensorsAndBBCode($row['message_text'],$row['bbcode_bitfield'],$row['enable_smilies'],false,$row['bbcode_uid']),
                          'message_subject' => _applyCensorsAndBBCode($row['message_subject'],$row['bbcode_bitfield'],$row['enable_smilies'],true,$row['bbcode_uid']),
                          'folder_id' => $row['folder_id']);
            $privateMsgs[] = $pMsg;
            
        }
        return $privateMsgs;
}

/*
* @ignore
*/
function _applyCensorsAndBBCode($message,$bbcodebitfield,$passSmiles,$isSubject,$bbcodeUID) {
        $message = censor_text($message);
        
        if ($isSubject) return $message; // stop here...
        // otherwise it's a text...
	if ($bbcodebitfield) {
		$bbcode->bbcode_second_pass($message, $bbcodeUID, $bbcodebitfield);
	}

	$message = bbcode_nl2br($message);
	$message = smiley_text($message, !$passSmiles);
        return $message;
}

/**
* Allows to send a private message using the currently logged user informations<br/><br/>
* <b>PARAMETERS:</b><br/>
* - <b>$mode</b> 				[string]	<i>'reply'</i> (if you want to reply to an existing message, see $replyToMsgID param) or <i>'post'</i> to post a new message<br/>
* - <b>$destinationUserID</b> 	[integer]	destination user id<br/>
* - <b>$subject</b> 			[string]	subject of the message<br/>
* - <b>$message_text</b> 		[string]	text of the messager<br/>
* - <b>$replyToMsgID</b> 		[string]	if you have specified $mode as 'reply' you need to specify the origin message id you want to quote<br/>
* - <b>$enableSign</b> 			[boolean]	enable your signature?<br/>
* - <b>$enableBBCode</b> 		[boolean]	enable BBCode into the message text?<br/>
* - <b>$enableSmiles</b> 		[boolean]	enable graphical smiles?<br/>
* - <b>$enableURLs</b> 			[boolean]	enable URLs?<br/>
* - <b>$putInOutbox</b> 		[boolean]	put your sent message in 'outbox' folder automatically?<br/>
*
* @since 1.1
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return integer GENERAL_ERR_MUSTLOGGED or null if sent successfully.
*/
function sendPrivateMessage($args) {
        // if number of parameters is lower than requested from headers this function return GENERAL_WRONG_PARAMETERS
	if (count($args) < 4) return GENERAL_WRONG_PARAMETERS;
        
        global $user,$db;
        if (_isUserLogged() == false)
                return GENERAL_ERR_MUSTLOGGED;

        $mode = $args[0]; // 'reply', 'post'; (se reply devi specificare $replyToMsgID, altrimenti null)
        $destinationUserID = $args[1];
        $subject = $args[2];
        $message_text = $args[3];
        $replyToMsgID = $args[4];
        $enableSign = $args[5];
        $enableBBCode = $args[6];
        $enableSmiles = $args[7];
        $enableURLs = $args[8];
        $putInOutbox = $args[9];

        $rootLevel = 1;
        if (isset($replyToMsgID)) $rootLevel = 0;
        
        $message_parser = new parse_message();
	$message_parser->message = $message_text;
        
        $pm_data = array(
			'from_user_id'			=> $user->data['user_id'],
                        'reply_from_root_level'         => $rootLevel, // what should it mean??? quote level?
                        'reply_from_msg_id'             => $replyToMsgID,
			'from_user_ip'			=> $user->ip,
			'from_username'			=> $user->data['username'],
			'enable_sig'			=> $enableSign,
			'enable_bbcode'			=> $enableBBCode,
			'enable_smilies'		=> $enableSmiles,
			'enable_urls'			=> $enableURLs,
			'icon_id'			=> 0,
			'bbcode_bitfield'		=> $message_parser->bbcode_bitfield,
			'bbcode_uid'			=> $message_parser->bbcode_uid,
			'message'			=> $message_parser->message,
			'address_list'			=> array('u' => array($destinationUserID => 'to')),
		);
        submit_pm($mode, $subject, &$pm_data, $putInOutbox);
}

/**
* Return the list of online friends of currently logged user
* <br/><b>PARAMETERS:</b><br/>
* - <i>$limitNumberTo</i> [integer] limit number of results
*
* @since 1.0
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return array it will return an array of friends; each object contains:<br/><br/>
*				<b>'user_id'</b> 		<i>[integer]</i> 		user id of the friend<br/>
*				<b>'username'</b> 		<i>[string]</i> 		user name<br/>
*				<b>'online_time'</b> 	<i>[long integer]</i> 	nline time (in unix format)
*/

function getOnlineFriends($args) {
    global $db,$user,$phpEx;

    if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;
    
    if (sizeof($args) == 0) $limitNumberTo = 5;
    $limitNumberTo = $args[0];
    
    // Output listing of friends online
    $update_time = 60;//$config['load_online_time'] * 60;

    switch ($db->sql_layer) {
	case 'mssql':
	case 'mssql_odbc':
	    $sql = $db->sql_build_query('SELECT_DISTINCT', array(
				'SELECT'	=> 'u.user_id, u.username, u.user_colour, u.user_allow_viewonline, MAX(s.session_time) as online_time, MIN(s.session_viewonline) AS viewonline',
				'FROM'		=> array(   USERS_TABLE	=> 'u',
                                                            ZEBRA_TABLE	=> 'z'
							),

				'LEFT_JOIN'	=> array(
					array(
				'FROM'	=> array(SESSIONS_TABLE => 's'),
				'ON'	=> 's.session_user_id = z.zebra_id'
					)
				),

				'WHERE'		=> 'z.user_id = ' . $user->data['user_id'] . '
				AND z.friend = 1
				AND u.user_id = z.zebra_id',
				'GROUP_BY'	=> 'z.zebra_id, u.user_id, u.username, u.user_allow_viewonline, u.user_colour',
				'ORDER_BY'   => 'u.username ASC',
			));
	    break;
	
	    default:
	    	$sql = $db->sql_build_query('SELECT_DISTINCT', array(
				'SELECT'	=> 'u.user_id, u.username, u.user_colour, u.user_allow_viewonline, MAX(s.session_time) as online_time, MIN(s.session_viewonline) AS viewonline',
				'FROM'		=> array(
										USERS_TABLE	=> 'u',
										ZEBRA_TABLE	=> 'z'
								),

				'LEFT_JOIN'	=> array(
					array(
				'FROM'	=> array(SESSIONS_TABLE => 's'),
				'ON'	=> 's.session_user_id = z.zebra_id'
					)
				),

				'WHERE'		=> 'z.user_id = ' . $user->data['user_id'] . '
				AND z.friend = 1
				AND u.user_id = z.zebra_id',
				'GROUP_BY'	=> 'z.zebra_id, u.user_id, u.username, u.user_allow_viewonline, u.user_colour',
				'ORDER_BY'	=> 'u.username_clean ASC',
			));
	    break;
    }

    $result = $db->sql_query_limit($sql,$limitNumberTo);
    
    $list = array();
    while ($row = $db->sql_fetchrow($result)) {
        $friend = array('user_id' => $row['user_id'],
                        'username' => $row['username'],
                        'online_time' => $row['online_time']);
        $list[] = $friend;
    }
    return $list;
}

/**
* Return the bookmarked topic of currently logged user.
* - <i>$logging_date</i> 	[integer] 	unix date since user login (it will be used to get first new post id and first page index with new posts)
* - <i>$postPerPage</i> 	[integer] 	number of posts per page
* @since 1.0
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return dictionary Results include the list of bookmarked topics. Each object include these keys:<br/><br/>
*       - <b>'items'</b> an array with object with these keys:<br/><br/>
*                       <b>'bookmarked'</b> 				[boolean] 	is topic bookmarked by logged user? (anonymous is obvisiuly false)
*                       <b>'topic_id'</b> 					[integer]	id of the topic<br />
*                       <b>'topic_title'</b> 				[string]	title of the topic<br />
*                       <b>'topic_poster'</b> 				[integer]	id of the author<br />
*                       <b>'topic_time'</b> 				[integer]	date of creation in unix format<br />
*                       <b>'topic_views'</b> 				[integer]	number of views<br />
*                       <b>'topic_replies'</b> 				[integer]	number of replies to topic<br />
*                       <b>'topic_status'</b> 				[integer]	status of topic<br />
*                       <b>'topic_first_post_id'</b> 		[integer]	id of the first message of the topic<br />
*                       <b>'topic_first_poster_name'</b> 	[string]	name of the author of first message of the topic<br />
*                       <b>'topic_last_post_id'</b> 		[integer]	id of the last post in topic<br />
*                       <b>'topic_last_poster_name'</b> 	[string]	name of the author of last reply on topic<br />
*                       <b>'topic_last_post_time'</b> 		[integer]	send date in unix format of last reply<br />
*                       <b>'topic_last_view_time'</b> 		[integer]	the time when last people has visited the topic in unix format<br />
*                       <b>'first_new_page'</b> 			[integer]	the first page you need to load the first new message<br/>
*                       <b>'first_new_postid'</b> 			[integer]	this the id of the first unread new post from the topic<br/>
*/
function getBookmarkedTopics($args) {
    // if number of parameters is lower than requested from headers this function return GENERAL_WRONG_PARAMETERS
	if (count($args) < 2) return GENERAL_WRONG_PARAMETERS;
        
        $logging_date = $args[0];
        $postPerPage = $args[1];
        
    // this forum does not allow bookmarks
    global $user,$config,$db;
    if (_isUserLogged() == false || $config['allow_bookmarks'] == false) {
         return GENERAL_ERR_MUSTLOGGED;
    }
    
    // our nice query to get the topic informations and bookmarked topics from our author
    $sql = 'SELECT * FROM '. TOPICS_TABLE .' T JOIN '.BOOKMARKS_TABLE.' B WHERE T.topic_id = B.topic_id AND B.user_id = '.$user->data['user_id'];
    // ... we will execute it...
    $result = $db->sql_query($sql);
   // and put the results in a fantastic array
    $followedTopics = array();
    while ($row = $db->sql_fetchrow($result)) {
        //$followedTopics[] = $row;
                //$isBookmarked = isTopicIDBookmarked($row['topic_id']);
                $newPage = getPageToLookForPostsAfterDate(array($row['topic_id'],$logging_date,$postPerPage));
                $newPostID = _getFirstNewPostIDInTopicAfterADate(array($row['topic_id'],$logging_date));
                
                $cTopic = array('topic_title' => $row['topic_title'],
			'topic_id' => $row['topic_id'],
                        'topic_poster' => $row['topic_poster'],
                        'topic_time' => $row['topic_time'],
                        'topic_views' => $row['topic_views'],
                        'topic_replies' => $row['topic_replies'],
                        'topic_status' => $row['topic_status'],
                        'topic_first_post_id' => $row['topic_first_post_id'],
                        'topic_first_poster_name' => $row['topic_first_poster_name'],
                        'topic_last_post_id' => $row['topic_last_post_id'],
			'topic_last_poster_id' => $row['topic_last_poster_id'],
                        'topic_last_poster_name' => $row['topic_last_poster_name'],
                        'topic_last_post_time' => $row['topic_last_post_time'],
                        'topic_last_view_time' => $row['topic_last_view_time'],
                        'bookmarked' => 1,
                        'first_new_page' => $newPage,
                        'first_new_postid' => $newPostID,
                        'forum_id' => $row['forum_id']);
                $followedTopics[] = $cTopic;
    }
    $db->sql_freeresult($result);
    // give me the results now!
    return array('items'=>$followedTopics,'page_showed' => 0);
}


/**
* Return if a topic id is bookmarked by the currently logged user
* - <i>$topic_id</i> 	[integer] 	topic id you want to check
*
* @since 1.1
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return boolean true or false
*/
function isTopicIDBookmarked($args) {
    // if number of parameters is lower than requested from headers this function return GENERAL_WRONG_PARAMETERS
	if (count($args) < 1) return GENERAL_WRONG_PARAMETERS;
        
        global $user,$db,$config,$auth;
		$topic_id = $args[0];
		
        if (_isUserLogged() == false || $config['allow_bookmarks'] == false)
                return false;
        // This rather complex gaggle of code handles querying for topics but
        // also allows for direct linking to a post (and the calculation of which
        // page the post is on and the correct display of viewtopic)
        $sql_array = array(
                        	'SELECT'	=> '',//'t.*, f.*',
                        	'FROM'		=> array(
                		FORUMS_TABLE	=> 'f',
                                	)
                                );

        if ($user->data['is_registered']) {
              /*  $sql_array['SELECT'] .= ' tw.notify_status';// ', tw.notify_status';
                $sql_array['LEFT_JOIN'] = array();

                $sql_array['LEFT_JOIN'][] = array(
                        'FROM'	=> array(TOPICS_WATCH_TABLE => 'tw'),
                        'ON'	=> 'tw.user_id = ' . $user->data['user_id'] . ' AND t.topic_id = tw.topic_id'
                 );
              */
                //if ($config['allow_bookmarks']) {
                        $sql_array['SELECT'] .=' bm.topic_id as bookmarked'; //', bm.topic_id as bookmarked';
                        $sql_array['LEFT_JOIN'][] = array(
			'FROM'	=> array(BOOKMARKS_TABLE => 'bm'),
                	'ON'	=> 'bm.user_id = ' . $user->data['user_id'] . ' AND t.topic_id = bm.topic_id'
                        );
                //}

               /* if ($config['load_db_lastread']) {
                        $sql_array['SELECT'] .= ', tt.mark_time, ft.mark_time as forum_mark_time';

                        $sql_array['LEFT_JOIN'][] = array(
			'FROM'	=> array(TOPICS_TRACK_TABLE => 'tt'),
			'ON'	=> 'tt.user_id = ' . $user->data['user_id'] . ' AND t.topic_id = tt.topic_id'
                        );

                        $sql_array['LEFT_JOIN'][] = array(
                        	'FROM'	=> array(FORUMS_TRACK_TABLE => 'ft'),
                        	'ON'	=> 'ft.user_id = ' . $user->data['user_id'] . ' AND t.forum_id = ft.forum_id'
                        );
                }*/
        }

//if (!$post_id)
//{
	$sql_array['WHERE'] = "t.topic_id = $topic_id";
//}
//else
//{
//	$sql_array['WHERE'] = "p.post_id = $post_id AND t.topic_id = p.topic_id" . ((!$auth->acl_get('m_approve', $forum_id)) ? ' AND p.post_approved = 1' : '');
//	$sql_array['FROM'][POSTS_TABLE] = 'p';
//}

        $sql_array['WHERE'] .= ' AND (f.forum_id = t.forum_id';

//if (!$forum_id)
//{
	// If it is a global announcement make sure to set the forum id to a postable forum
	$sql_array['WHERE'] .= ' OR (t.topic_type = ' . POST_GLOBAL . '
		AND f.forum_type = ' . FORUM_POST . ')';
//}
//else
//{
//	$sql_array['WHERE'] .= ' OR (t.topic_type = ' . POST_GLOBAL . "
//		AND f.forum_id = $forum_id)";
//}

        $sql_array['WHERE'] .= ')';
        $sql_array['FROM'][TOPICS_TABLE] = 't';

        // Join to forum table on topic forum_id unless topic forum_id is zero
        // whereupon we join on the forum_id passed as a parameter ... this
        // is done so navigation, forum name, etc. remain consistent with where
        // user clicked to view a global topic
        $sql = $db->sql_build_query('SELECT', $sql_array);
        $result = $db->sql_query($sql);
        $topic_data = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);

        if ($topic_data['bookmarked'] != null) return true;
        else return false;
}

/**
* Allows to toggle bookmark status (true/false) of a topic
* - <i>$topic_id</i> 	[integer] 	topic id you want to check
*
* @since 1.1
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return null no return
*/
function toogleBookmarkSetting($topic_id) {
    // if number of parameters is lower than requested from headers this function return GENERAL_WRONG_PARAMETERS
	if (count($args) < 1) return GENERAL_WRONG_PARAMETERS;
        
        global $db,$auth,$user,$config;
        
        if ($config['allow_bookmarks'] && $user->data['is_registered']) {
                // verify if it's bookmarked
                $valoreBk = isTopicIDBookmarked(array($topic_id));
                if (!$valoreBk) { // no, we can bookmark it
                        $sql = 'INSERT INTO ' . BOOKMARKS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
			'user_id'	=> $user->data['user_id'],
			'topic_id'	=> $topic_id,
                        ));
                        $db->sql_query($sql);
                        return 1;
                } else { // yes, we will remove bookmark
                        $sql = 'DELETE FROM ' . BOOKMARKS_TABLE . "
			WHERE user_id = {$user->data['user_id']}
				AND topic_id = $topic_id";
                        $db->sql_query($sql);
                        return 0;
                }
        } else
                return 0;
}

/**
* This function return a list with custom user defined private messages folders.<br/>
* From this list you can't see inbox, outbox and sent messages folders (use related methods)<br/>
* Use getContentOfFolderWithID function to get it's contents.
* @since 1.0
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return an array of folders. Each object (it's a folder) contains these keys:<br/><br/>
*			- <b>'folder_id'</b> 		<i>[integer]</i> 	id of the folder<br/>
*			- <b>'folder_name'</b> 		<i>[string]</i> 	name of the folder<br/>
*			- <b>'num_messages'</b> 	<i>[integer]</i> 	number of messages in folder<br/>
*			- <b>'unread_messages'</b> 	<i>[integer]</i> 	number of unread messages into that folder
*/
function getMessagesFolders() {
    global $user;
    if (_isUserLogged()) { // if you are logged we can list inbox folder
        $foldersList = get_folder($user->data['user_id']);
        
        $list = array();
        $foldersKeys = array_keys($foldersList);
        foreach ($foldersKeys  as $currentFolderKey) {
            if ($currentFolderKey > 1) { // we will exclude folders between PRIVMSGS_HOLD_BOX (=-4)and PRIVMSGS_INBOX (=0)
                                        // and SENT_MESSAGES (=1)
                $cFolder = $foldersList[$currentFolderKey];
                $folderItem = array('folder_id' => $currentFolderKey,
                                    'folder_name' => $cFolder['folder_name'],
                                    'num_messages' => $cFolder['num_messages'],
                                    'unread_messages' => $cFolder['unread_messages']);
                $list[] = $folderItem;

            }
        }
        return $list;
    } return GENERAL_ERR_MUSTLOGGED;
}

/**
* This function return the list of elements in outbox folder.<br>
* See getContentOfFolderWithID function to know what it return.
* @since 1.0
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
*/
function getOutboxFolder() {
    global $user;
    if (_isUserLogged()) // if you are logged we can list inbox folder
        return _getUserFolderWithID(PRIVMSGS_OUTBOX,$user->data['user_id']);
    return GENERAL_ERR_MUSTLOGGED; // return null
}

/**
* This function return the list of elements in inbox folder.<br>
* See getContentOfFolderWithID function to know what it return.
* @since 1.0
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
*/
function getInboxFolder() {
    global $user;
    if (_isUserLogged()) // if you are logged we can list inbox folder
        return _getUserFolderWithID(PRIVMSGS_INBOX,$user->data['user_id']);
    return GENERAL_ERR_MUSTLOGGED; // return null
}

/**
* This function return a list of messages contained in a specified folder given by it's $folder_id.<br/>
* <br/><b>PARAMETERS:</b><br/>
* - <b>$folderId</b> <i>[integer]</i> the ID of target folder we want to see<br/>
* - <b>$userId</b> <i>[integer]</i> the ID of target folder we want to see<br/>
* @since 1.0
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return array an array with pvt messages object that contains keys:<br/><br/>
*			- <b>'msg_id'</b> 			<i>[integer]</i> 		id of the message<br/>
*			- <b>'author_id'</b> 		<i>[integer]</i> 		author of message<br/>
*			- <b>'to_address'</b> 		<i>[string]</i> 		destination users (in form u_<USERID>:u_<USERID_2>)<br/>
*			- <b>'unread_messages'</b> 	<i>[integer]</i> 		number of unread messages into that folder<br/>
*			- <b>'message_time'</b> 	<i>[long integer]</i> 	sender time (in unix format)<br/>
*			- <b>'message_subject'</b> 	<i>[string]</i> 		subject of the message<br/>
*			- <b>'pm_unread'</b> 		<i>[integer]</i> 		number of unread private messages into the folder<br/>
*			- <b>'pm_new'</b> 			<i>[integer]</i> 		number of new private messages into the folder<br/>
*/

function getContentOfFolderWithID($args) {
    global $user;
    if (_isUserLogged()) { // if you are logged we can list inbox folder
		if (sizeof($args) == 0) return null;
        return _getUserFolderWithID($args[0],$user->data['user_id']);
	}
    return GENERAL_ERR_MUSTLOGGED; // return null	
}

/*
* @ignore
*/
function _getUserFolderWithID($folderId,$userId) {
    // we take the list of all user's folders
    $foldersList = get_folder($userId);
    // we need to take the target folder from the list
    $targetFolder = $foldersList[$folderID];
    // we take take the list of private messages
    $folder_info = get_pm_from($folderID,$foldersList, $userId);
    
    return $folder_info['rowset'];
}


/**
* This function return the profile of currently logged user
* @since 1.0
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return array see getUserProfileFrom() method
*/

function getMeProfile() {
  global $user,$db,$auth,$phpEx,$config;
        
     //   login(array('malcom','carbon',true,true));
    $auth->acl($user->data);
    $user->setup();   
 
    $logged = _isUserLogged();
      if ($logged == false)
        return USERPROFILE_VIEW_NOTALLOWED;
     $risultato = getUserProfileFrom(array(1,$user->data['username']));
     return $risultato;
    
}

/**
* Return some informations about gived user id or username
* <br/><b>PARAMETERS:</b><br/>
* - <b>$data_type</b> <i>[integer]</i> <b>0</b> if you have it's user id and you wnat to know more, <b>1</b> if you have it's username
* - <b>$dataValue</b> <i>[integer]</i> you need to put here user id or username accorsind to $dataType<br/>
* @since 1.0
* @version 1.0
* @see THE RESULT OF THIS FUNCTION DEPENDS ACCESS RIGHTS ON BOARD
* @return array with infos about profile<br/>
*			- <b>'user_id'</b> 				<i>[integer]</i> id of the user<br/>
*			- <b>'user_type'</b> 				<i>[integer]</i> type of user (USER_NORMAL = 0, USER_INACTIVE = 1, USER_IGNORE = 2, USER_FOUNDER = 3)<br/>
*			- <b>'user_regdate'</b> 			<i>[long integer]</i> user registration date in unix format<br/>
*			- <b>'user_birthday'</b> 			<i>[string]</i> birthday in MM-DD-YYYY format<br/>
*			- <b>'user_lastvisit'</b> 		<i>[long integer]</i> last visit time in unix format<br/>
*			- <b>'user_lastpost_time'</b> 	<i>[long integer]</i> time of last post from the user in unix format<br/>
*			- <b>'user_posts'</b> 			<i>[integer]</i> number of user's posts<br/>
*			- <b>'user_lang'</b> 				<i>[string]</i> user language (it,en...)<br/>
*			- <b>'user_rank'</b> 				<i>[integer]</i> the rank value of the user<br/>
*			- <b>'user_allow_pm'</b> 			<i>[boolean]</i> user allow private messages<br/>
*			- <b>'user_avatar'</b> 			<i>[string]</i> avatar local path<br/>
*			- <b>'user_avatar_width'</b> 		<i>[integer]</i> width of the avatar picture<br/>
*			- <b>'user_avatar_height'</b> 	<i>[integer]</i> height of the avatar picture<br/>
*			- <b>'user_sig'</b> 				<i>[string]</i> signature string if available<br/>
*			- <b>'user_icq'</b> 				<i>[string]</i> icq contact<br/>
*			- <b>'user_aim'</b> 				<i>[string]</i> aim contact<br/>
*			- <b>'user_yim'</b> 				<i>[string]</i> yim contact<br/>
*			- <b>'user_msnm'</b> 				<i>[string]</i> msn contact<br/>
*			- <b>'user_jabber'</b> 			<i>[string]</i> jabber contact<br/>
*			- <b>'user_interests'</b> 		<i>[string]</i> users interests<br/>
*			- <b>'user_website'</b> 			<i>[string]</i> user web site<br/>
*			- <b>'posts_per_day'</b> 			<i>[integer]</i> average number of posts per day<br/>
*			- <b>'inactive_reason'</b> 		<i>[string]</i> if the user is inactive this string contain the reason<br/>
*			- <b>'memberdays'</b> 			<i>[integer]</i> days passed since subscription<br/>
*			- <b>'html_avatar'</b> 			<i>[string]</i> html string for avatar picture<br/>
*			---------------------------
*			<br/><b>ERRORS:</b><br/>
*			- <b>USERPROFILE_VIEW_NOTALLOWED</b> 				you cannot view profiles because board leaders don't allow you to do it<br/>
*			- <b>USERPROFILE_VIEW_NOTALLOWEDANONYMOUS</b> 		you can't view profiles without logging in<br/>
*			- <b>GENERAL_ERROR</b> 								general error<br/>
*			- <b>USERPROFILE_VIEW_NONEXISTINGMEMBER</b> 		member does not exists (or you can't see it for some reason)<br/>
*/

function getUserProfileFrom($args) {
    global $user,$db,$auth,$phpEx,$config;
    
    // if number of parameters is lower than requested from headers this function return GENERAL_WRONG_PARAMETERS
    if (count($args) < 2) return GENERAL_WRONG_PARAMETERS;
        
    $data_type = $args[0];
    $dataValue = $args[1];

    // load some interesting things about authentication permissions
    $auth->acl($user->data);
    $user->setup(array('memberlist', 'groups'));

    // check for permissions
    if (!$auth->acl_gets('u_viewprofile', 'a_user', 'a_useradd', 'a_userdel')) {
	if ($user->data['user_id'] != ANONYMOUS)
	    return USERPROFILE_VIEW_NOTALLOWED; // BOARD STAFF DON'T WANT YOU CAN SEE PROFILES
        return USERPROFILE_VIEW_NOTALLOWEDANONYMOUS; // YOU ARE ANONYMOUS AND YOU CAN'T SEE PROFILES
    }
                        
    $user_id = $dataValue;
    if ($data_type == 1) {// convert it to id
        $listFound = getUserIdsFromNames(array($dataValue));
        if (sizeof($listFound) == 0) $user_id = ANONYMOUS; // not found
        else $user_id = $listFound[0]; // first element
    }
    
    // if you are searching for John Doe we can't help you!
    if ($user_id == ANONYMOUS)
	return GENERAL_ERROR; // ERRORE???
    
    // Get user...
    $sql = 'SELECT *
			FROM ' . USERS_TABLE . '
			WHERE user_id = '. $user_id; 
    $result = $db->sql_query($sql);
    $member = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);

    // nothing found!
    if (!$member) return USERPROFILE_VIEW_NONEXISTINGMEMBER;
    
    // a_user admins and founder are able to view inactive users and bots to be able to manage them more easily
    // Normal users are able to see at least users having only changed their profile settings but not yet reactivated.
    if (!$auth->acl_get('a_user') && $user->data['user_type'] != USER_FOUNDER) {
	if ($member['user_type'] == USER_IGNORE) {
	    return USERPROFILE_VIEW_NONEXISTINGMEMBER;//trigger_error('NO_USER');
	} else if ($member['user_type'] == USER_INACTIVE && $member['user_inactive_reason'] != INACTIVE_PROFILE) {
	    return USERPROFILE_VIEW_NONEXISTINGMEMBER; //trigger_error('NO_USER');
	}
    }
    
    
    // getting some other infos
    $user_id = (int) $member['user_id'];

    // Do the relevant calculations
    // subscribed from days...
    $memberdays = max(1, round((time() - $member['user_regdate']) / 86400));
    // post per day
    $posts_per_day = $member['user_posts'] / $memberdays;
    // activity percentage into the board (his fucking weight!)
    $percentage = ($config['num_posts']) ? min(100, ($member['user_posts'] / $config['num_posts']) * 100) : 0;

    // getting signature
    if ($member['user_sig']) { // he have a stupid signature?
	$member['user_sig'] = censor_text($member['user_sig']); //... of course!

	if ($member['user_sig_bbcode_bitfield']) {
	   // include_once($phpbb_root_path . 'includes/bbcode.' . $phpEx);
	    $bbcode = new bbcode();
	    $bbcode->bbcode_second_pass($member['user_sig'], $member['user_sig_bbcode_uid'], $member['user_sig_bbcode_bitfield']);
	}
        
        // we render it and put in user data
	$member['user_sig'] = bbcode_nl2br($member['user_sig']);
	$member['user_sig'] = smiley_text($member['user_sig']);
    }
    
    // getting avatar
    $poster_avatar = get_user_avatar($member['user_avatar'], $member['user_avatar_type'], $member['user_avatar_width'], $member['user_avatar_height']);

    // is he an active user?
    if ($member['user_type'] == USER_INACTIVE) {
        $user->add_lang('acp/common'); // load language pack strings for inactive reasons
	$inactive_reason = $user->lang['INACTIVE_REASON_UNKNOWN'];
	switch ($member['user_inactive_reason']) {
	    case INACTIVE_REGISTER:
		$inactive_reason = $user->lang['INACTIVE_REASON_REGISTER']; break;
	    case INACTIVE_PROFILE:
	    	$inactive_reason = $user->lang['INACTIVE_REASON_PROFILE']; break;
	    case INACTIVE_MANUAL:
	    	$inactive_reason = $user->lang['INACTIVE_REASON_MANUAL']; break;
            case INACTIVE_REMIND:
		$inactive_reason = $user->lang['INACTIVE_REASON_REMIND']; break;
        }
    }
    
    $cUser = array('username' => $member['username'],
                   'user_id' => $member['user_id'],
                   'user_type' => $member['user_type'],
                   'user_regdate' => $member['user_regdate'],
                   'user_birthday' => $member['user_birthday'],
                   'user_lastvisit' => $member['user_lastvisit'],
                   'user_lastpost_time' => $member['user_lastpost_time'],
                   'user_posts' => $member['user_posts'],
                   'user_lang' => $member['user_lang'],
                   'user_rank' => $member['user_rank'],
                   'user_allow_pm' => $member['user_allow_pm'],
                   'user_avatar' => $member['user_avatar'],
                   'user_avatar_width' => $member['user_avatar_width'],
                   'user_avatar_height' => $member['user_avatar_height'],
                   'user_sig' => $member['user_sig'],
                   'user_icq' => $member['user_icq'],
                   'user_aim' => $member['user_aim'],
                   'user_yim' => $member['user_yim'],
                   'user_msnm' => $member['user_msnm'],
                   'user_jabber' => $member['user_jabber'],
                   'user_interests' => $member['user_interests'],
                   'user_website' => $member['user_website'],
                   'posts_per_day' => $posts_per_day,
                   'inactive_reason' => $inactive_reason,
                   'memberdays' => $memberdays,
                   'html_avatar' => '/download/file.php?avatar='. $member['user_avatar']);//$poster_avatar);
    return $cUser;                  
}

/**
* This function allows to send a mail to a member of the board (if allowed)<br/>
* <br/><b>PARAMETERS:</b><br/>
* - <b>$user_id</b> 	<i>[integer]</i> 	the ID of target user<br/>
* - <b>$subject</b> 	<i>[string]</i> 	subject of mail<br/>
* - <b>$message</b> 	<i>[string]</i> 	the content of mail (both in txt and html format)<br/>
* - <b>$cc_email</b> 	<i>[string]</i>		cc destinations separated by comma<br/>
* @since 1.0
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return integer can be:
*			<b>GENERAL_ERR_MUSTLOGGED</b> 			you must to be logged in order to use this function<br/>
*			<b>'SENDMAIL_ERR_MAILDISABLED'</b> 		mail is disabled in board configuration<br/>
*			<b>'SENDMAIL_ERR_MAILNOTALLOWED'</b> 	mail not allowed <br/>
*			<b>'SENDMAIL_ERR_FLOODLIMIT'</b> 		you're a spammer?<br/>
*			<b>'SENDMAIL_OK'</b> 					sent successfully<br/>
*			<b>'SENDMAIL_ERR'</b> 					sent error
*/

function sendEmailToUser($args) {
    global $user,$db,$auth,$config,$phpEx,$phpbb_root_path;
    
    // if number of parameters is lower than requested from headers this function return GENERAL_WRONG_PARAMETERS
    if (count($args) < 4) return GENERAL_WRONG_PARAMETERS;
        
    $user_id = $args[0];
    $subject = $args[1];
    $message = $args[2];
    $cc_email = $args[3];
    
    // auth
    $auth->acl($user->data);

    // you can't send email from anonymous account
    if (_isUserLogged() == false)
        return GENERAL_ERR_MUSTLOGGED;
   
    // email disabled
    if (!$config['email_enable'])
	return SENDMAIL_ERR_MAILDISABLED; //trigger_error('EMAIL_DISABLED');


    if (!$auth->acl_get('u_sendemail'))
        return SENDMAIL_ERR_MAILNOTALLOWED; //trigger_error('NO_EMAIL');

    // Are we trying to abuse the facility?
    if (time() - $user->data['user_emailtime'] < $config['flood_interval'])
	return SENDMAIL_ERR_FLOODLIMIT; //trigger_error('FLOOD_EMAIL_LIMIT');
    
    // you can't send email to anonymous... or??
    if ($user_id == ANONYMOUS || !$config['board_email_form'])
	return SENDMAIL_ERR_MAILNOTALLOWED;//trigger_error('NO_EMAIL');
    
    // Get the appropriate username, etc.
    $sql = 'SELECT username, user_email, user_allow_viewemail, user_lang, user_jabber, user_notify_type
		FROM ' . USERS_TABLE . "
		WHERE user_id = $user_id
	    	AND user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')';
    
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);

    if (!$row) // user not found
	return SENDMAIL_ERR_NODESTINATION; //trigger_error('NO_USER');

    // Can we send email to this user?
    if (!$row['user_allow_viewemail'] && !$auth->acl_get('a_user'))
	return SENDMAIL_ERR_MAILNOTALLOWED; //trigger_error('NO_EMAIL');
    
    // prepare email
    $subject	= utf8_normalize_nfc($subject);
    $message	= utf8_normalize_nfc($message);
    $cc		= (isset($cc_email)) ? true : false;

    $name = $row['username'];
    $email_lang = $row['user_lang'];
    $email = $row['user_email'];
                                
    $sql = 'UPDATE ' . USERS_TABLE . '
	    	SET user_emailtime = ' . time() . '
		WHERE user_id = ' . $user->data['user_id'];
    $result = $db->sql_query($sql);

    include_once($phpbb_root_path . 'includes/functions_messenger.' . $phpEx);
    $messenger = new messenger(false);
    $email_tpl = ($user_id) ? 'profile_send_email' : 'email_notify';

    $mail_to_users = array();

    $mail_to_users[] = array(   'email_lang'		=> $email_lang,
                                'email'			=> $email,
				'name'			=> $name,
				'username'		=> ($user_id) ? $row['username'] : '',
				'to_name'		=> $name,
				'user_jabber'		=> ($user_id) ? $row['user_jabber'] : '',
			        'user_notify_type'	=> ($user_id) ? $row['user_notify_type'] : NOTIFY_EMAIL,
				'topic_title'		=> (!$user_id) ? $row['topic_title'] : '',
                                'forum_id'		=> (!$user_id) ? $row['forum_id'] : 0,
				);
    // Ok, now the same email if CC specified, but without exposing the users email address
    if ($cc) {
	$mail_to_users[] = array(   'email_lang'	=> $user->data['user_lang'],
				    'email'		=> $user->data['user_email'],
				    'name'	        => $user->data['username'],
				    'username'		=> $user->data['username'],
				    'to_name'	        => $name,
				    'user_jabber'       => $user->data['user_jabber'],
				    'user_notify_type'	=> ($user_id) ? $user->data['user_notify_type'] : NOTIFY_EMAIL,
				    'topic_title'	=> (!$user_id) ? $row['topic_title'] : '',
				    'forum_id'		=> (!$user_id) ? $row['forum_id'] : 0,
				);
    }
    
    foreach ($mail_to_users as $row) {
	$messenger->template($email_tpl, $row['email_lang']);
	$messenger->replyto($user->data['user_email']);
	$messenger->to($row['email'], $row['name']);

	if ($user_id) {
	    $messenger->subject(htmlspecialchars_decode($subject));
	    $messenger->im($row['user_jabber'], $row['username']);
	    $notify_type = $row['user_notify_type'];
	} else {
	    $notify_type = NOTIFY_EMAIL;
	}

	$messenger->headers('X-AntiAbuse: Board servername - ' . $config['server_name']);
	$messenger->headers('X-AntiAbuse: User_id - ' . $user->data['user_id']);
	$messenger->headers('X-AntiAbuse: Username - ' . $user->data['username']);
	$messenger->headers('X-AntiAbuse: User IP - ' . $user->ip);

	$messenger->assign_vars(array(  'BOARD_CONTACT'	=> $config['board_contact'],
					'TO_USERNAME'	=> htmlspecialchars_decode($row['to_name']),
					'FROM_USERNAME'	=> htmlspecialchars_decode($user->data['username']),
					'MESSAGE'       => htmlspecialchars_decode($message))
					);

	if ($topic_id) {
	    $messenger->assign_vars(array(  'TOPIC_NAME'	=> htmlspecialchars_decode($row['topic_title']),
					    'U_TOPIC'		=> generate_board_url() . "/viewtopic.$phpEx?f=" . $row['forum_id'] . "&t=$topic_id")
					);
	}

	$messenger->send($notify_type);
    }

	return SENDMAIL_OK;

}

/*  
 
 notify_status == 0 or == 1 what mean? (0 should be 'no notification send yet' but the topic is watched by the user ? )
function isUserWatchingTopicID($args) {
        $topic_id = $args[0];
        
        global $db,$user,$auth,$config;
        if ($config['email_enable'] && $config['allow_topic_notify'] && $user->data['is_registered']) {

                $sql = "SELECT notify_status
				FROM ".TOPICS_WATCH_TABLE."
				WHERE topic_id = $topic_id
					AND user_id = ".$user->data['user_id'];
                $result = $db->sql_query($sql);

                $notify_status = ($row = $db->sql_fetchrow($result)) ? $row['notify_status'] : NULL;
                $db->sql_freeresult($result);
                if (is_null($notify_status) && $notify_status == 0)
                        return false;
                else
                        return true;
        } else
                return false;
}
*/
?>