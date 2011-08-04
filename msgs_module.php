<?php

/**
* 
* @package OFAPI
* @version v 1.0 2008/06/28
* @version 1.1 (2008/07/29).<br/>Added Methods:
*											- getTopicWithID()
*											- getPageIndexForPostWithID()<br/>
*											- _getFirstNewPostIDInTopicAfterADate()<br/>
*											- _getNumberOfPostsOfTopic()<br/>
*											- _getNumberOfPostsAfterADateFromTopic()<br/>
*											- getPageToLookForPostsAfterDate()
*											- getPostWithID()<br/>
*											- getPostsWithID()<br/>
*											- countTopicsInForum()<br/>
*											- searchInsideBoard()
* @version 1.2 (2008/07/31) <br/>Added Methods:                                         - countNewTopicsAfterADate()
*                                                                                       - countUpdatedTopicsAfterADate()
*                                                                                       - getNewTopicsAfterADate()
*                                                                                       - getUpdatedTopicsAfterADate()
*                                                                                       - getForumInfo()
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

function countNewTopicsAfterADate($args) {
    return _countTopicsWithKeyDateAfter('topic_time',$args[0],$args[1]);
}

function countUpdatedTopicsAfterADate($args) {
    return _countTopicsWithKeyDateAfter('topic_last_post_time',$args[0],$args[1]);
}

/** @ignore */
function _countTopicsWithKeyDateAfter($timeKeyToCheck,$forum_id,$date) {
    global $db;
    if ($date == null) return 0;
    
    $sql = 'SELECT COUNT(topic_id) as total_items FROM '.TOPICS_TABLE.' WHERE forum_id = '.$forum_id.' AND '.$timeKeyToCheck.' >= '.$date;

    $resultQ = $db->sql_query($sql);
    $row = $db->sql_fetchrow($resultQ);
    $db->sql_freeresult($resultQ);
     return $row['total_items'];
}


/**
* This method return the number of page where a specific post id is contained.<br/>
* In order to get the correct number you need to specify the number of posts per page (you can obtain it via <i>getBoardConfigurationData()</i> method in miscs_module.php)
* <br/><b>PARAMETERS:</b><br/>
* - <b>$post_id</b> 		<i>[integer]</i> 	Target post id
* - <b>$topic_id</b> 		<i>[integer]</i> 	Topic id of target post
* - <b>$forum_id</b> 		<i>[integer]</i> 	Forum id of target post
* - <b>$postsPerPage</b> 	<i>[string]</i> 	Number of posts per page
*
* @since 1.1
* @see THIS FUNCTION DON'T REQUIRE LOGIN
* @return array  the page index you need to load to get this post id
*/

function getPageIndexForPostWithID($args) {
		global $db,$auth,$user;
		$post_id = $args[0];
		$topic_id = $args[1];
		$forum_id = $args[2];
		$postsPerPage = $args[3];
		
		$sort_dir = null;

		
		$sql = 'SELECT COUNT(p1.post_id) AS prev_posts
			FROM ' . POSTS_TABLE . ' p1, ' . POSTS_TABLE . " p2
			WHERE p1.topic_id = {$topic_id}
				AND p2.post_id = {$post_id}
				" . ((!$auth->acl_get('m_approve', $forum_id)) ? 'AND p1.post_approved = 1' : '') . '
				AND ' . (($sort_dir == 'd') ? 'p1.post_time >= p2.post_time' : 'p1.post_time <= p2.post_time');

		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		$itemsBeforePost = $row['prev_posts'] - 1;
		$idxP =	 ceil($itemsBeforePost / $postsPerPage);
		return $idxP;
}


/**
* @ignore
* <b>INTERNAL METHOD</b><BR/>
* This method return the number posts of a specified topic id.<br/>
* <br/><b>PARAMETERS:</b><br/>
* - <b>$topic_id</b> 		<i>[integer]</i> 	Topic id of target post
*
* @since 1.1
* @see THIS FUNCTION DON'T REQUIRE LOGIN
* @return integer  numbr of posts of the topic
*/

function _getNumberOfPostsOfTopic($args) {
        global $db;
        $sql = 'SELECT COUNT(p1.post_id) AS totalpost FROM phpbb_posts p1 WHERE p1.topic_id = '.$args[0];
        $result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
        return $row['totalpost'];
}

/**
* @ignore
* <b>INTERNAL METHOD</b><BR/>
* This method return the first new post id posted into a specified topic after a reference date.
* <br/><b>PARAMETERS:</b><br/>
* - <b>$topic_id</b> 	<i>[integer]</i> 	Our topic id
* - <b>$date</b> 		<i>[integer]</i> 	The reference date in unix format (time interval since 1970)
*
* @since 1.1
* @see THIS FUNCTION DON'T REQUIRE LOGIN
* @return integer  the id of the first post created after ref date
*/

function _getFirstNewPostIDInTopicAfterADate($args) {
        global $db;
        $topic_id = $args[0];
        $date = $args[1];
        
        if ($date == null || $date == -1) return -1;
        
        $sql = 'SELECT p1.post_id, p1.post_time
			FROM '.POSTS_TABLE.' p1, phpbb_posts p2
			WHERE p1.topic_id = '.$topic_id.' AND p1.post_approved = 1 AND p2.post_id =p1.post_id
                        AND p1.post_time > '.$date.' 
                        ORDER BY p1.post_time ASC';
        $result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
        $firstPostID = $row['post_id'];
        if ($firstPostID == null) return -1;
        else return $firstPostID;
}


/**
* @ignore
* <b>INTERNAL METHOD</b><BR/>
* This method return the number of posts created after a reference date in a topic.
* <br/><b>PARAMETERS:</b><br/>
* - <b>$topic_id</b> 	<i>[integer]</i> 	Our topic id
* - <b>$date</b> 		<i>[integer]</i> 	The reference date in unix format (time interval since 1970)
*
* @since 1.1
* @see THIS FUNCTION DON'T REQUIRE LOGIN
* @return integer  the number of posts created after ref date in a specified topic
*/

function _getNumberOfPostsAfterADateFromTopic($args) {
        global $db;
        $topic_id = $args[0];
        $date = $args[1];

        $sql = 'SELECT COUNT(p1.post_id) AS next_posts FROM '.POSTS_TABLE.' p1, '.POSTS_TABLE.' p2 '.' WHERE p1.topic_id = '.$topic_id.' AND p2.post_id = p1.post_id AND p1.post_approved =1 AND p1.post_time > '.$date;
        $result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
        $itemsNextPosts = $row['next_posts'];
        return $itemsNextPosts;
}


/**
* This method return the page you need to load to get the first new post created after a reference date (generally the last user logging date)
* <br/><b>PARAMETERS:</b><br/>
* - <b>$topic_id</b> 		<i>[integer]</i> 	Our topic id
* - <b>$date</b> 			<i>[integer]</i> 	The reference date in unix format (time interval since 1970)
* - <b>$itemsPerPage</b> 	<i>[integer]</i> 	The number of posts per page (you can obtain it via <i>getBoardConfigurationData()</i> method in miscs_module.php)
*
* @since 1.1
* @see THIS FUNCTION DON'T REQUIRE LOGIN
* @return integer  page index to load
*/
function getPageToLookForPostsAfterDate($args) {
        global $db;
        
        $topic_id = $args[0];
        $date = $args[1];
        $itemsPerPage = $args[2];

        if ($date == null || $date == -1) return -1;

        // count the topic next a specific date from the topic
        $newPosts = _getNumberOfPostsAfterADateFromTopic(array($topic_id,$date));      
        // count total posts of the topic
        $totalPosts = _getNumberOfPostsOfTopic(array($topic_id));
        
        if ($totalPosts < $itemsPerPage) return 0;
        
        $x = $totalPosts - $newPosts;
        
        $idxP =  ceil( ($totalPosts-$newPosts) / $itemsPerPage);
        return $idxP-1; // -1 = zero based index
}

/**
* This method return a specific post id.
* <br/><b>PARAMETERS:</b><br/>
* - <b>$post_id</b> 		<i>[integer]</i> 	The post id you want to load
*
* @since 1.1
* @see THIS FUNCTION DON'T REQUIRE LOGIN
* @return dictionary a dictionary with these keys:
*					- <b>'post_time'</b>	<i>[integer]</i>	the creation date in unix format <br/>
*					- <b>'post_text'</b>	<i>[string]</i>		the post's text <br/>
*					- <b>'poster_id'</b>	<i>[integer]</i>	the author id of the post <br/>
*					- <b>'forum_id'</b>		<i>[integer]</i>	the forum id of the post <br/>
*					- <b>'topic_id'</b>		<i>[integer]</i>	the parent topic id of the post <br/>
*/
function getPostWithID($args) {
        return getPostsWithIDs( array( array($args[0])));
}


/**
* This method return a specific post id.
* <br/><b>PARAMETERS:</b><br/>
* - <b>$post_id</b> 		<i>[integer]</i> 	The post id you want to load
*
* @since 1.1
* @see THIS FUNCTION DON'T REQUIRE LOGIN
* @return array an array with loaded post. See <i>getPostWithID</i> to get the elements printed in each object dictionary
*/
function getPostsWithIDs($args) {
	$posts_id = $args[0];
	
	 global $user,$db,$auth;
    // setup user for search mode (???)
    $user->setup('search');
    
    $show_results = $args[0]; // $show_results = request_var('sr', 'topics');
    $show_results = ($show_results == 'posts') ? 'posts' : 'topics';
    $sort_days = $args[1]; //== null ? 7 : $args[1]); // default value is 7

    $sort_key = 't';
    $sort_dir = 'd';  
    $sort_by_sql['t'] = ($show_results == 'posts') ? 'p.post_time' : 't.topic_last_post_time';
    $sort_by_sql['s'] = ($show_results == 'posts') ? 'p.post_subject' : 't.topic_title';
    
    $sql_sort = 'ORDER BY ' . $sort_by_sql[$sort_key] . (($sort_dir == 'a') ? ' ASC' : ' DESC');
    $sort_join = ($sort_key == 'f') ? FORUMS_TABLE . ' f, ' : '';
    $sql_sort = ($sort_key == 'f') ? ' AND f.forum_id = p.forum_id ' . $sql_sort : $sql_sort;

    if ($sort_days) {
	$last_post_time = 'AND p.post_time > ' . (time() - ($sort_days * 24 * 3600));
    } else {
	$last_post_time = '';
    }

    if ($sort_key == 'a') {
	$sort_join = USERS_TABLE . ' u, ';
	$sql_sort = ' AND u.user_id = p.poster_id ' . $sql_sort;
    }
    
    $whatReturn = null;
    
    $countWhatElement = '';
    $sqlSelect = '';
    $sqlExecute = '';
    
	$whatReturn = "p.post_id,p.forum_id,p.topic_id,p.post_text,poster_id,p.post_username,p.post_time,p.post_subject,p.post_edit_time,p.post_edit_user";
	
	$countWhatElement = 'p.post_id';
	
		$ex_fid_ary = array_unique(array_merge(array_keys($auth->acl_getf('!f_read', true)), array_keys($auth->acl_getf('!f_search', true))));
	
		// find out in which forums the user is allowed to view approved posts
	if ($auth->acl_get('m_approve'))
	{
		$m_approve_fid_ary = array(-1);
		$m_approve_fid_sql = '';
	}
	else if ($auth->acl_getf_global('m_approve'))
	{
		$m_approve_fid_ary = array_diff(array_keys($auth->acl_getf('!m_approve', true)), $ex_fid_ary);
		$m_approve_fid_sql = ' AND (p.post_approved = 1' . ((sizeof($m_approve_fid_ary)) ? ' OR ' . $db->sql_in_set('p.forum_id', $m_approve_fid_ary, true) : '') . ')';
	}
	else
	{
		$m_approve_fid_ary = array();
		$m_approve_fid_sql = ' AND p.post_approved = 1';
	}
        
        $postsList = '';
        for ($k=0; $k < sizeof($posts_id); $k++) {
                $postsList = $postsList.''.$posts_id[$k];
                if ( !($k == sizeof($posts_id)-1))
                        $postsList = $postsList.',';
        }
        
	$sqlSelect = "SELECT ".$whatReturn;
        $sql = $sqlSelect." FROM $sort_join" . POSTS_TABLE . ' p, ' . TOPICS_TABLE . " t
		    WHERE p.topic_id = t.topic_id AND p.post_id IN (  $postsList )
		    $last_post_time
		    $m_approve_fid_sql
		    " . ((sizeof($ex_fid_ary)) ? ' AND ' . $db->sql_in_set('p.forum_id', $ex_fid_ary, true) : '') . "
		    $sql_sort";
		    $field = 'post_id';
	$result = $db->sql_query($sql);
	$db->sql_freeresult($result);
	
	$list = array();
	while( ($row = $db->sql_fetchrow($result))) {
                
		$list[] = array('post_time' => $row['post_time'],
				'post_text' => censor_text($row['post_text']),
				'post_username' => $row['post_username'],
				'poster_id' => $row['poster_id'],
                                'forum_id' => $row['forum_id'],
                                'topic_id' => $row['topic_id']);
	}
	return $list[0];
}

/**
* Return the number of topics inside a forum
* @see IT DOES NOT REQUIRE LOGIN
* @since 1.0
* @version 1.0
* @return integer   This function return the number of topics of a forum.<br/>
*/
function countTopicsInForum($args) {
	global  $db,$auth;	
	
	$forum_id = $args[0];
	$sqlGetTopics = 'SELECT COUNT(topic_id) AS num_topics
			FROM ' . TOPICS_TABLE . "
			WHERE forum_id = $forum_id
				AND topic_type <> " . POST_GLOBAL . "
					OR topic_type = " . POST_ANNOUNCE . "
			" . (($auth->acl_get('m_approve', $forum_id)) ? '' : 'AND topic_approved = 1');

	$getTopicsResult = $db->sql_query($sqlGetTopics);
	$topics_count = (int) $db->sql_fetchfield('num_topics');

	$db->sql_freeresult($getTopicsResult);
	return $topics_count;
}


function getForumInfo($args) {
    $res = _getForumsInfo($args);
    $forum = $res[0];
    $logging_date = $args[1];

    $topics_count = countTopicsInForum(array($forum['forum_id']));
		
    $newTopics =  ($logging_date == null ? 0 : countNewTopicsAfterADate(array($forum['forum_id'],$logging_date)));
    $updatedTopics = ($logging_date == null ? 0: countUpdatedTopicsAfterADate(array($forum['forum_id'],$logging_date)));
    
    $forumComplete = array(
			'topics_number' => $topics_count,
			'forum_name'=> $forum['forum_name'],
			'forum_id' => $forum['forum_id'],
			'parent_id' => $forum['parent_id'],
			'forum_type'=> $forum['forum_type'],
			'forum_image' => $forum['forum_image'],
			'forum_posts' => $forum['forum_posts'],
			'forum_topics' => $forum['forum_topics'],
			'forum_last_post_time' => $forum['forum_last_post_time'],
			'forum_last_poster_name' => $forum['forum_last_poster_name'],
			'forum_last_post_id' => $forum['forum_last_post_id'],
			'forum_last_poster_id' => $forum['forum_last_poster_id'],
			'forum_last_post_subject' => $forum['forum_last_post_subject'],
                        'forum_newtopics_sincedate' => $newTopics,
                        'forum_updatedtopics_sincedate' => $updatedTopics
			);
    return $forumComplete;
}

function _getForumsInfo($args) {
    global $db,$auth,$user,$config;
    $forum_id = $args[0];
	

    $specificForumIdQuery = ($forum_id == null ? '' : 'WHERE forum_id = '.$forum_id);
    $sql = 'SELECT forum_id, forum_name, parent_id, forum_type, left_id, right_id, forum_image,
			forum_posts, forum_topics, forum_last_post_time, forum_last_poster_name, forum_last_poster_id, forum_last_post_id, forum_last_post_subject
		FROM ' . FORUMS_TABLE . ' '. $specificForumIdQuery.' ORDER BY left_id ASC';
	

    $result = $db->sql_query($sql, 600);
    
    // Voglio creare una lista lineare, prima
	
	//////////////////////////////// 
	// 
	//        6           5 
	//      /   \       /   \ 
	//     2     7     9     3 
	//   / | \ 
	//  1  4  8 
	// 
	//////////////////////////////// 

	
	// Sometimes it could happen that forums will be displayed here not be displayed within the index page
	// This is the result of forums not displayed at index, having list permissions and a parent of a forum with no permissions.
	// If this happens, the padding could be "broken"
	
	$auth->acl($user->data);
	$acl_list=false;
	
        $listaTemp = array();
        while ($row = $db->sql_fetchrow($result))
            $listaTemp[] = $row;
	$db->sql_freeresult($result);
        return $listaTemp;
}

/**
* Return user's visible forums tree with some additional informations.<br/>
* <b>PARAMETERS:</b><br/>
* <b>$logging_date</b> [integer] your logging date (used to get updated infos about your last login). If not specified 'forum_newtopics_sincedate','forum_updatedtopics_sincedate' = 0.
*
* @see FUNCTION CAN RETURN DIFFERENT INFORMATIONS BASED UPON YOUR LOGIN PERMISSIONS
* @since 1.0
* @version 1.0
* @return array   This function return an array of arrays (an hierarchical list) that represent the forum tree structure.<br/>
* 		  Some fields are 0 or empty if forum is a category (FORUM_CAT) or link (FORUM_LINK)<br/>
* 		  Each object contains these keys:	
* 		  		  - <b>'forum_id'</b> 					[integer] 			the id of the forum<br/>
*                 - <b>'forum_name'</b> 				[string] 			the name of the forum<br/>
*                 - <b>'forum_type'</b> 				[integer] 			<i>FORUM_CAT</i>(0) = category, <i>FORUM_POST</i>(1) = normal forum, <i>FORUM_LINK</i>(2) = link<br/>
*                 - <b>'forum_image'</b> 				[string] 			local path to forum image<br/>
*                 - <b>'forum_posts'</b> 				[integer] 			the number of posts contained into the forum<br/>
*                 - <b>'forum_topics'</b> 				[integer] 			the number of topics into the forum<br/>
*                 - <b>'forum_last_post_time'</b> 		[long integer] 		last post time (last update to the forum in unix format)<br/>
*                 - <b>'forum_last_poster_name'</b> 	[string] 			the author of the last post published into the forum<br/>
*                 - <b>'forum_last_post_id'</b> 		[integer] 			the id of the last post published into the forum<br/>
*                 - <b>'forum_last_post_subject'</b> 	[string] 			the subject of the last post published into this forum<br/>
*                 - <b>'count_topics'</b> 				[integer] 			the number of posts inside the topic
*                 - <b>'childs'</b> 					[array] 			an array with the same type of objects just described
*                 - <b>'forum_newtopics_sincedate'</b>    [integer] number of new topics since reference date (login date)<br/>
*                 - <b>'forum_updatedtopics_sincedate'</b> [integer] number of updated topics since reference date (login date<br/>
*                 - <b>'topics_number'</b> [integer] number of topics inside the forum
*/

function getForumsTree($args) {
	global $auth, $user, $db, $phpbb_root_path;	
        $logging_date = $args[0];

	$lista = array();
	
        $listaTemp = _getForumsInfo(array());

	foreach ($listaTemp as $row)
        {
		
		
		//if ($row['left_id'] < $right)
		//{
		//	$padding++;
		//	$padding_store[$row['parent_id']] = $padding;
		//}
		//else if ($row['left_id'] > $right + 1)
		//{
		//	// Ok, if the $padding_store for this parent is empty there is something wrong. For now we will skip over it.
		//	// @todo digging deep to find out "how" this can happen.
		//	$padding = (isset($padding_store[$row['parent_id']])) ? $padding_store[$row['parent_id']] : $padding;
		//}

		//$right = $row['right_id'];
//		print_r ($row);

		if ($row['forum_type'] == FORUM_CAT && ($row['left_id'] + 1 == $row['right_id']))
		{
			// Non-postable forum with no subforums, don't display
			continue;
		}

		if (!$auth->acl_get('f_list', $row['forum_id']))
		{
			// if the user does not have permissions to list this forum skip
			continue;
		}

		if ($acl_list && !$auth->acl_gets($acl_list, $row['forum_id']))
		{
			continue;
		}
		
		$topics_count = countTopicsInForum(array($row['forum_id']));
		
                $newTopics =  ($logging_date == null ? 0 : countNewTopicsAfterADate(array($row['forum_id'],$logging_date)));
                $updatedTopics = ($logging_date == null ? 0: countUpdatedTopicsAfterADate(array($row['forum_id'],$logging_date)));
    
		$lista[] = array(
			'topics_number' => $topics_count,
			'forum_name'=> $row['forum_name'],
			'forum_id' => $row['forum_id'],
			'parent_id' => $row['parent_id'],
			'forum_type'=> $row['forum_type'],
			'forum_image' => $row['forum_image'],
			'forum_posts' => $row['forum_posts'],
			'forum_topics' => $row['forum_topics'],
			'forum_last_post_time' => $row['forum_last_post_time'],
			'forum_last_poster_name' => $row['forum_last_poster_name'],
			'forum_last_post_id' => $row['forum_last_post_id'],
			'forum_last_post_subject' => $row['forum_last_post_subject'],
                        'forum_newtopics_sincedate' => $newTopics,
                        'forum_updatedtopics_sincedate' => $updatedTopics,
			'forum_last_poster_id' => $forum['forum_last_poster_id']
			);
			
		/*if (!$display_jumpbox)
		{
			$template->assign_block_vars('jumpbox_forums', array(
				'FORUM_ID'		=> ($select_all) ? 0 : -1,
				'FORUM_NAME'	=> ($select_all) ? $user->lang['ALL_FORUMS'] : $user->lang['SELECT_FORUM'],
				'S_FORUM_COUNT'	=> $iteration)
			);

			$iteration++;
			$display_jumpbox = true;
		}*/
		 
		//$aa	= ($row['forum_type'] == FORUM_CAT) ? 'S' : 'N';
		//$bb = ($row['forum_type'] == FORUM_LINK) ? 'S' : 'N';
		//$cc	= ($row['forum_type'] == FORUM_POST) ? 'S' : 'N';
		
		//echo $row['forum_name'] . $aa . $bb . $cc . $padding . ', id=' . $row['forum_id']. '<br><br>';
		
	/*	$template->assign_block_vars('jumpbox_forums', array(
			'FORUM_ID'		=> $row['forum_id'],
			'FORUM_NAME'	=> $row['forum_name'],
			'SELECTED'		=> ($row['forum_id'] == $forum_id) ? ' selected="selected"' : '',
			'S_FORUM_COUNT'	=> $iteration,
			'S_IS_CAT'		=> ($row['forum_type'] == FORUM_CAT) ? true : false,
			'S_IS_LINK'		=> ($row['forum_type'] == FORUM_LINK) ? true : false,
			'S_IS_POST'		=> ($row['forum_type'] == FORUM_POST) ? true : false)
		);

		for ($i = 0; $i < $padding; $i++) {
			$template->assign_block_vars('jumpbox_forums.level', array());
		}*/
	
	//	$iteration++;
	}

	// 
	// Imposta gli indici della lista costruita (nel caso non sia indicizzata)
	// 
	$lookup = array(); 
	foreach ($lista as $item) { 
		//crea una nuova chiave "childs"
    	$item['childs'] = array(); 
    	$lookup[$item['forum_id']] = $item; 
	} 

	// 
	// Adesso costruiamo il cazzo di albero
	// 
	$tree = array();
	foreach ($lookup as $f_id => $temp) { 
    	$item = &$lookup[$f_id]; 
    	
	// conta il numero di post
	$item['count_topics'] = countTopicsInForum(array($item['forum_id']));
	
    	if ($item['parent_id'] == 0) { 
        	$tree[$f_id] = &$item; 
    	} 
    	else if (isset($lookup[$item['parent_id']])) { 
        	$lookup[$item['parent_id']]['childs'][$f_id] = &$item; 
    	} 
    	else // non trova il genitore
    	{ 
        	$tree['_orfano_'][$f_id] = &$item; 
    	}
	} 

	// 
	// Pijalo nel CULO!!! 
	// 
	//print_r( $tree ); 

	return $tree;

}

/**
* This method returns the most recents active topics on board<br/>
* <br/><b>PARAMETERS:</b><br/>
* - <b>$sort_days</b> <i>[integer]</i> return articles since last specified days (null mean 1 (one) day)
* - <b>$pageIdx</b> <i>[integer]</i> the page you want to show
* - <b>$itemsPerPage</b> <i>[integer]</i> items per page
* @since 1.0
* @version 1.0
* @see THIS FUNCTION REQUIRE LOGIN (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return array If valid it returns a dictionary with two keys:
* 	- <b>'page_showed'</b> the page you have showed
* 	- <b>'total_pages'</b> the number of pages
* 	- <b>'total_items'</b> the number of items in all pages
* 	- <b>'items'</b> an array of topics <br/>
*
*	'items' objects are dictionaries with these keys:<br/>
*			<b>'forum_id'</b>					<i>[integer]</i> 		parent forum id of topic<br/>
*			<b>'topic_title'</b>				<i>[string]</i> 		title of the topic<br/>
*			<b>'topic_id'</b>					<i>[string]</i> 		id of the topic<br/>
*			<b>'topic_poster'</b>				<i>[integer]</i> 		author id of topic<br/>
*			<b>'topic_time'</b>					<i>[long integer]</i> 	posting date of the first message (unix time)<br/>
*			<b>'topic_views'</b>				<i>[integer]</i> 		views of the topic<br/>
*			<b>'topic_replies_real'</b>			<i>[integer]</i> 		number of replies into the topic<br/>
*			<b>'topic_first_post_id'</b>		<i>[integer]</i> 		the post id of the first post<br/>
*			<b>'topic_first_poster_name'</b>	<i>[string]</i> 		author of the topic (first post author)<br/>
*			<b>'topic_last_post_id'</b>			<i>[integer]</i>		the id of the last post into the topic<br/>
*			<b>'topic_last_poster_id'</b>		<i>[integer]</i> 		the author id of the last reply message of the topic<br/>
*			<b>'topic_last_poster_name'</b>		<i>[string]</i> 		the author name of the last reply message of the topic<br/>
*			<b>'topic_last_post_time'</b>		<i>[long integer]</i> 	the last post date in unix time<br/>
*			<b>'topic_poster'</b>				<i>[integer]</i> 		the id of the author of the topic<br/> */

function getActiveTopics($args) {
    global $user,$db,$auth;
    // nah nah you can't get these infos without logging in
    if (_isUserLogged() == false) return GENERAL_ERR_MUSTLOGGED;
    
    // setup user for search mode (???)
    $user->setup('search');
    
    $show_results = 'topics';
    $sort_days = $args[0];
    if ($sort_days == null)
	$sort_days = 1;

    $sort_key = 't';
    $sort_dir = 'd';
    $sort_by_sql['t'] = 't.topic_last_post_time';

$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
$sort_by_text	= array('a' => $user->lang['SORT_AUTHOR'], 't' => $user->lang['SORT_TIME'], 'f' => $user->lang['SORT_FORUM'], 'i' => $user->lang['SORT_TOPIC_TITLE'], 's' => $user->lang['SORT_POST_SUBJECT']);
$limit_days		= array(0 => $user->lang['ALL_RESULTS'], 1 => $user->lang['1_DAY'], 7 => $user->lang['7_DAYS'], 14 => $user->lang['2_WEEKS'], 30 => $user->lang['1_MONTH'], 90 => $user->lang['3_MONTHS'], 180 => $user->lang['6_MONTHS'], 365 => $user->lang['1_YEAR']);
   // gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);
    $s_sort_key = $s_sort_dir = '';

    $last_post_time_sql = ($sort_days > 0) ? ' AND t.topic_last_post_time > ' . (time() - ($sort_days * 24 * 3600)) : '';
    $whatReturn = "t.topic_id,t.forum_id,t.topic_title,t.topic_poster,t.topic_time,t.topic_views,t.topic_replies_real,t.topic_first_post_id,t.topic_first_poster_name,t.topic_last_post_id,t.topic_last_poster_id,t.topic_last_poster_name,t.topic_last_post_time";
	
	$sqlSelect = 'SELECT '.$whatReturn;
	

	$ex_fid_ary = array_unique(array_merge(array_keys($auth->acl_getf('!f_read', true)), array_keys($auth->acl_getf('!f_search', true))));
	
		// find out in which forums the user is allowed to view approved posts
	if ($auth->acl_get('m_approve'))
	{
		$m_approve_fid_ary = array(-1);
		$m_approve_fid_sql = '';
	}
	else if ($auth->acl_getf_global('m_approve'))
	{
		$m_approve_fid_ary = array_diff(array_keys($auth->acl_getf('!m_approve', true)), $ex_fid_ary);
		$m_approve_fid_sql = ' AND (p.post_approved = 1' . ((sizeof($m_approve_fid_ary)) ? ' OR ' . $db->sql_in_set('p.forum_id', $m_approve_fid_ary, true) : '') . ')';
	}
	else
	{
		$m_approve_fid_ary = array();
		$m_approve_fid_sql = ' AND p.post_approved = 1';
	}

    $sqlToExecute = ' FROM ' . TOPICS_TABLE . " t
		WHERE t.topic_moved_id = 0
	    	$last_post_time_sql
		" . str_replace(array('p.', 'post_'), array('t.', 'topic_'), $m_approve_fid_sql) . '
		' . ((sizeof($ex_fid_ary)) ? ' AND ' . $db->sql_in_set('t.forum_id', $ex_fid_ary, true) : '') . '
		ORDER BY t.topic_last_post_time DESC';
		$field = 'topic_id';

$sql = $sql = $sqlSelect . $sqlToExecute;

    $pageIdx = $args[1];
    $itemsPerPage = $args[2];
    
    // we want to count the number of items to elaborate the total pages
    $total = _countTopicInQuery($sqlToExecute,'topic_id');
    // we used limit sql function to browse between pages so limit is [startindex,numberofresults]
    $queryLimit = " LIMIT ".$pageIdx*$itemsPerPage.",".$itemsPerPage;
    $sql = $sql . $queryLimit;
    
    $result = $db->sql_query($sql);

    $finalList = _parseResultsFromType(&$result,&$show_results);
    $db->sql_freeresult($result);
    return array('page_showed' => $pageIdx,'items' => $finalList, 'total_pages' => ceil($total/$itemsPerPage), 'total_items' => $total);
}

/*
* @ignore
*/
function _countTopicInQuery($sqlConditionPart,$countWhatKey) {
	global $db,$auth,$config,$user;
	
	$topic_id = $args[0];
	$sql = 'SELECT COUNT('.$countWhatKey.') AS total_elements ' . $sqlConditionPart;

	$result = $db->sql_query($sql);
	$totalItems = (int) $db->sql_fetchfield('total_elements');
	$db->sql_freeresult($result);
	return $totalItems;
}

/**
* Returns unanswered posts or topics into the board since specified number of days.<br>
* You need to setup $args array with this list of ordered parameters:
* - <b>$show_results</b> <i>[string]</i> can be <i>'posts'</i> to return flat list of unanswered posts or <i>'topics'</i> to get the list of unanswered topics
* - <b>$sort_days</b> <i>[integer]</i> return articles since last n days (null to get all the list since board found... it's a very expensive query)
* - <b>$pageIdx</b> <i>[integer]</i> the page you want to show
* - <b>$itemsPerPage</b> <i>[integer]</i> items per page
* @since 1.0
* @version 1.0
* @see THIS FUNCTION REQUIRE YOU ARE LOGGED (OTHERWISE IT WILL RETURN GENERAL_ERR_MUSTLOGGED)
* @return dictionary This function return a dictionary with these keys:
* 	- <b>'page_showed'</b> the page you have showed
* 	- <b>'total_pages'</b> the number of pages
* 	- <b>'total_items'</b> the number of items in all pages
* 	- <b>'items'</b> an array where each object is a topic with these keys based upon $show_results:
* 	
*                   <b>IF $show_results = 'POSTS'</b><br/>
*                   ----------------------------------------------<br/>
*                   <b>'post_id'</b> [integer] target post id<br/>
*                   <b>'forum_id'</b> [integer] parent forum id of the topics<br/>
*                   <b>'post_time'</b> [long integer] message posting date in unix format<br/>
*                   <b>'post_subject'</b> [string] subject of post<br/>
*                   <b>'post_edit_time'</b> [long integer] message editing date (if available) in unix format<br/>
*                   <b>'post_edit_user'</b> [integer] id of the editor (if available)<br/>
*                   <br/>
*                   <br/>
*                   
*                   <b>IF $show_results = 'TOPICS'</b><br/>
*                   ----------------------------------------------<br/>
*                   If valid it returns an array where each object is a topic with these keys:
*                   <br/>
*                   <b>'forum_id'</b> [integer] parent forum id of topic<br/>
*                   <b>'topic_id'</b> [integer] topic id <br/>
*                   <b>'topic_title'</b> [string] title of the topic<br/>
*                   <b>'topic_poster'</b> [integer] author id of topic<br/>
*                   <b>'topic_time'</b> [long integer] posting date of the first message (unix time)<br/>
*                   <b>'topic_views'</b> [integer] views of the topic<br/>
*                   <b>'topic_replies_real'</b> [integer] number of replies into the topic<br/>
*                   <b>'topic_first_post_id'</b> [integer] the post id of the first post<br/>
*                   <b>'topic_first_poster_name'</b> [string] author of the topic (first post author)<br/>
*                   <b>'topic_last_post_id'</b> [integer] the id of the last post into the topic<br/>
*                   <b>'topic_last_poster_id'</b> [integer] the author id of the last reply message of the topic<br/>
*                   <b>'topic_last_poster_name'</b> [string] the author name of the last reply message of the topic<br/>
*                   <b>'topic_last_post_time'</b> [long integer] the last post date in unix time<br/>
*                   
*/
function getUnansweredPosts($args) {
    global $user,$db;
    // setup user for search mode (???)
    $user->setup('search');
    
    $show_results = $args[0]; // $show_results = request_var('sr', 'topics');
    $show_results = ($show_results == 'posts') ? 'posts' : 'topics';
    $sort_days = $args[1]; //== null ? 7 : $args[1]); // default value is 7

    $sort_key = 't';
    $sort_dir = 'd';  
    $sort_by_sql['t'] = ($show_results == 'posts') ? 'p.post_time' : 't.topic_last_post_time';
    $sort_by_sql['s'] = ($show_results == 'posts') ? 'p.post_subject' : 't.topic_title';
    
    $sql_sort = 'ORDER BY ' . $sort_by_sql[$sort_key] . (($sort_dir == 'a') ? ' ASC' : ' DESC');
    $sort_join = ($sort_key == 'f') ? FORUMS_TABLE . ' f, ' : '';
    $sql_sort = ($sort_key == 'f') ? ' AND f.forum_id = p.forum_id ' . $sql_sort : $sql_sort;

    if ($sort_days) {
	$last_post_time = 'AND p.post_time > ' . (time() - ($sort_days * 24 * 3600));
    } else {
	$last_post_time = '';
    }

    if ($sort_key == 'a') {
	$sort_join = USERS_TABLE . ' u, ';
	$sql_sort = ' AND u.user_id = p.poster_id ' . $sql_sort;
    }
    
    $whatReturn = null;
    
    $countWhatElement = '';
    $sqlSelect = '';
    $sqlExecute = '';
    
    if ($show_results == 'posts') {
	$whatReturn = "p.post_id,p.forum_id,p.post_time,p.post_subject,p.post_edit_time,p.post_edit_user";
	
	$countWhatElement = 'p.post_id';
	
	$sqlSelect = "SELECT ".$whatReturn;
        $sql = " FROM $sort_join" . POSTS_TABLE . ' p, ' . TOPICS_TABLE . " t
		    WHERE t.topic_replies = 0
		    AND p.topic_id = t.topic_id
		    $last_post_time
		    $m_approve_fid_sql
		    " . ((sizeof($ex_fid_ary)) ? ' AND ' . $db->sql_in_set('p.forum_id', $ex_fid_ary, true) : '') . "
		    $sql_sort";
		    $field = 'post_id';
    } else {
	$countWhatElement = 't.topic_id';
	
    	$whatReturn = "t.topic_id,t.forum_id,t.topic_title,t.topic_poster,t.topic_time,t.topic_views,t.topic_replies_real,t.topic_first_post_id,t.topic_poster,t.topic_first_poster_name,t.topic_last_post_id,t.topic_last_poster_id,t.topic_last_poster_name,t.topic_last_post_time";
	
	$sqlSelect = 'SELECT DISTINCT ' . $sort_by_sql[$sort_key] . ", ".$whatReturn;
	
        $sqlExecute =" FROM $sort_join" . POSTS_TABLE . ' p, ' . TOPICS_TABLE . " t
                    WHERE t.topic_replies = 0
		    AND t.topic_moved_id = 0
		    AND p.topic_id = t.topic_id
		    $last_post_time
		    $m_approve_fid_sql
		    " . ((sizeof($ex_fid_ary)) ? ' AND ' . $db->sql_in_set('p.forum_id', $ex_fid_ary, true) : '') . "
		    $sql_sort";
		    $field = 'topic_id';
    }
	
	$sql = $sqlSelect.' '.$sqlExecute;
    // we want to count the number of items to elaborate the total pages
    $totalItems = _countTopicInQuery($sqlExecute,$countWhatElement);
    
    $pageIdx = $args[2];
    $itemsPerPage = $args[3];
    // we used limit sql function to browse between pages so limit is [startindex,numberofresults]
    $queryLimit = " LIMIT ".$pageIdx*$itemsPerPage.",".$itemsPerPage;
    $sql = $sql . $queryLimit;
    
    $result = $db->sql_query($sql);
    $finalList = _parseResultsFromType(&$result,&$show_results);
    $db->sql_freeresult($result);

    return array('page_showed' => $pageIdx,'items' => $finalList, 'total_pages' => ceil($totalItems/$itemsPerPage), 'total_items' => $totalItems);

}

/**
* Get new posts/topics since a gived period. This method return paginated results, so you can display huge data in separated blocks.
* You need to login before using this method if you want to get items since your last login.
* You need to provide 4 parameters inside the $args array.
* To create a new account you need to browse the board using a browser.
* - <b>$show_results</b> 	[string] 	<i>'posts'</i> to return flat list of posts or <i>'topics'</i> to return topics list 
* - <b>$itemsPerPage</b> 	[integer] 	number of posts per page
* - <b>$pageIdx</b> 		[integer] 	number of page to show
* - <b>$range</b> 			[multiple values] can be: <br/>
*                         						  	<i>'uLastVisitDate'</i> 			[string] 		show new messages since last (currently logged) user visit date <br/>
*                           						<i>'last7Days'</i> 					[string] 		show new messages posted between 7 days ago to today <br/>
*                           						<i>array($_dateStart,$_dateEnd)</i> [array 2itms] 	show new messages in given range (start,end) 
* @since 1.0
* @return integer   <b>ERRORS:</b><br>
*                   --------------<br> 
*                   <b>NEWPOSTED_MSG_REQUIRELOGIN</b> the method with this configuration require login first<br/>
*                   <b>NEWPOSTED_MSG_DATERANGEINVALID</b> given range is invalid<br/>
*                   <br>
*                   <b>VALID RESULTS:</b><br>
*                   --------------<br>
*                   see results for function <i>getUnansweredPosts()</i>
*/

function getNewPostedMessagesFilteredBy($args) {
    global $user,$db;
    
    // take variables
    $show_results = $args[0];
    $itemsPerPage = $args[1];
    $pageIdx = $args[2];
    $range = $args[3];
    
    // I'll use it only if I need to parse messages in a range
    $_dt_dateStart = null;
    $_dt_endRange = null;
    
    // if you have requested to show the number of messages since last user date visit and you are not logged
    // we will return 'last7Days' messages (anonymous)
    if ((is_string($range) && $range == 'uLastVisitDate') && ($user->data['user_id'] == ANONYMOUS || $user->data['is_bot'])) {
	return NEWPOSTED_MSG_REQUIRELOGIN;
        //return getNewPostedMessagesFilteredBy(array($show_results,$itemsPerPage,$pageIdx,'last7Days'));
    } else if (is_array($range)) {
        // we need to parse the given date in USA format and check if are valid (otherwise we will return null)
        if ( (($_dt_dateStart = strtotime($range[0])) === -1) || (($_dt_endRange = strtotime($range[1])) === -1) ) {
            // given data format is invalid
            return NEWPOSTED_MSG_DATERANGEINVALID;
        }
    }        
    
    // setup user for search mode (???)
    $user->setup('search');

    // Define initial vars
    $search_id		= 'newposts';//request_var('search_id', '');
    $show_results 	= ($show_results == 'posts') ? 'posts' : 'topics';
    
    // sorting parameters
    $sort_days		= 0;
    // force sorting
    $sort_key = 't';
    $sort_dir = 'd';
    $sort_by_sql['t'] = ($show_results == 'posts') ? 'p.post_time' : 't.topic_last_post_time';
    $sql_sort = 'ORDER BY ' . $sort_by_sql[$sort_key] . (($sort_dir == 'a') ? ' ASC' : ' DESC');
    
   // gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);
    $s_sort_key = $s_sort_dir = $u_sort_param = $s_limit_days = '';
    
    // we used limit sql function to browse between pages so limit is [startindex,numberofresults]
    $queryLimit = "LIMIT ".$pageIdx*$itemsPerPage.",".$itemsPerPage;
    
    // what's the key to check in order to filter results by time?
    $whatTimeColumnCheck = ($show_results == 'posts' ? 'p.post_time' : 't.topic_last_post_time');
    $_timeQueryPart = null;
    
    if (is_string($range)) {
        if ($range == 'uLastVisitDate') { // USERS LAST VISIT DATE
            $_timeQueryPart = ' '.$whatTimeColumnCheck.' >= '.$user->data['user_lastvisit'];
        }
        
        if ($range == 'last7Days') { // LAST SEVEN DAYS (SINCE 7 DAYS AGO TO TODAY)
            $_dt_sevenDaysAgo = mktime()-604800; //86400 secondi in un giorno * 7
            $_dt_today = mktime();
            $_timeQueryPart = ' '.$whatTimeColumnCheck.' >= '.$_dt_sevenDaysAgo.' AND '.$whatTimeColumnCheck.' <= '.$_dt_today;
        }
        
    } else if (is_array($range)) { // DATE RANGE (we have parsed range before at the start)
        $_timeQueryPart = ' '.$whatTimeColumnCheck.' >= '.$_dt_dateStart.' AND '.$whatTimeColumnCheck.' <= '.$_dt_endRange;
    }
    
    $whatCount = '';
    if ($show_results == 'posts') {
	$whatCount = 'p.post_id';
	$sqlSelect = 'SELECT '.$whatReturn;
	// we want to show posts and not the entire topic reference
        // <id del post>,<id del forum origine>,<data del post>,<subject del post>,<data edit>,<utente che ha editato>
	$whatReturn = "p.post_id,p.forum_id,p.post_time,p.post_subject,p.post_edit_time,p.post_edit_user";
        /*$sql = 'SELECT '.$whatReturn.' FROM ' . POSTS_TABLE . ' p WHERE p.post_time > ' . '1212501197'. "
		    $m_approve_fid_sql" . ((sizeof($ex_fid_ary)) ? ' AND ' . $db->sql_in_set('p.forum_id', $ex_fid_ary, true) : '') . "$sql_sort ".$queryLimit;
       */
	$sqlToExecute = ' FROM ' . POSTS_TABLE . ' p WHERE '.$_timeQueryPart.' '."
		    $m_approve_fid_sql" . ((sizeof($ex_fid_ary)) ? ' AND ' . $db->sql_in_set('p.forum_id', $ex_fid_ary, true) : '') . "$sql_sort ".$queryLimit;
        
	$field = 'post_id';
    } else {
	$whatCount = 't.topic_id';
	
	// we want to list the topics
        // <topic id>,<titolo topic>,<autore topic>,<data creazione>,<visite>,<risposte>,<id primo post>,<nome autore primo post>,<id ultimo post>,<nome ultimo poster>,<data ultimo messaggio>
	$whatReturn = "t.topic_id,t.forum_id,t.topic_title,t.topic_poster,t.topic_time,t.topic_views,t.topic_replies_real,t.topic_first_post_id,t.topic_first_poster_name,t.topic_last_post_id,t.topic_last_poster_id,t.topic_last_poster_name,t.topic_last_post_time";
	/*$sql = 'SELECT '.$whatReturn.' FROM ' . TOPICS_TABLE . ' t WHERE t.topic_last_post_time > 1212501197
		    AND t.topic_moved_id = 0' . str_replace(array('p.', 'post_'), array('t.', 'topic_'), $m_approve_fid_sql) . '
		    ' . ((sizeof($ex_fid_ary)) ? 'AND ' . $db->sql_in_set('t.forum_id', $ex_fid_ary, true) : '') . "$sql_sort ".$queryLimit;
        */
        
	$sqlSelect = 'SELECT '.$whatReturn;
        $sqlToExecute = ' FROM ' . TOPICS_TABLE . ' t WHERE '.$_timeQueryPart.' 
		    AND t.topic_moved_id = 0' . str_replace(array('p.', 'post_'), array('t.', 'topic_'), $m_approve_fid_sql) . '
		    ' . ((sizeof($ex_fid_ary)) ? 'AND ' . $db->sql_in_set('t.forum_id', $ex_fid_ary, true) : '') . "$sql_sort ".$queryLimit;
      
        $field = 'topic_id';
    }    

$sql = $sqlSelect. ' '.$sqlToExecute;
    // we want to count the number of items to elaborate the total pages
    $totalItems = _countTopicInQuery($sqlToExecute,$whatCount);
    
     // limit our results to 1000
     //$result = $db->sql_query_limit($sql, 1001,0); //- $start, $start);
    $result = $db->sql_query($sql);
    $finalList = _parseResultsFromType(&$result,&$show_results);
    $db->sql_freeresult($result);

    return array('page_showed' => $pageIdx,'items' => $finalList, 'total_pages' => ceil($totalItems/$itemsPerPage), 'total_items' => $totalItems);

}

/**
* Return most visited recents topics.<br/>
* You can provide a maxTopicsToList as integer (the max number of items to show) or<br/>
* $excludeForumIdsList to exclude some forums (as array of forum's IDs) to exclude from fetch
* - <b>$maxTopicsToList</b> 		<i>[integer]</i> 	max topics to list 
* - <b>$excludeForumIdsList</b> 	<i>[array]</i> 		lists of forum-ids to exclude
*
* @since 1.0
* @return array return an array with topics with these keys:<br/>
*				- <b>'topic_id'</b>						[integer]		topic unique id 
*				- <b>'forum_id'</b>						[integer]		parent topic forum id
*				- <b>'topic_title'</b>					[string]		title of the topic
*				- <b>'topic_poster'</b>					[integer]		topic's author id
*				- <b>'topic_time'</b>					[integer]		topic creation date as unix time format
*				- <b>'topic_views'</b>					[integer]		number of views of the topic
*				- <b>'topic_replies_real'</b>			[integer]		number of replies of the topic
*				- <b>'topic_first_post_id'</b>			[integer]		first post id of the topic
*				- <b>'topic_first_poster_name'</b> 		[string]		author name of the topic
*				- <b>'topic_last_post_id'</b>			[integer]		last post id
*				- <b>'topic_last_poster_id'</b>			[integer]		last post author id
*				- <b>'topic_last_poster_name'</b>		[string]		last post author name
*				- <b>'topic_last_post_time'</b>			[integer]		last post creation date into the topic as unix time format
*				- <b>'topic_poster'</b>					[string]		author of the topic (duplicate?)
*/

function getRecentsHotTopics($args) {
    global $db,$config,$auth;
    
    $maxTopicsToList = $args[0];
    $excludeForumIdsList = $args[1];
    if ($maxTopicsToList == null || $excludeForumIdsList == 0) $excludeForumIdsList = 5;
    
    $sql_where = _compileExcludeForumsPredicateFromList($excludeForumIdsList);
   $whatReturn = "topic_id,forum_id,topic_title,topic_poster,topic_time,topic_views,topic_replies_real,topic_first_post_id,topic_poster,topic_first_poster_name,topic_last_post_id,topic_last_poster_id,topic_last_poster_name,topic_last_post_time";

////topic_title, forum_id, topic_id
    $sql = 'SELECT '.$whatReturn.'  
	FROM ' . TOPICS_TABLE . '
	WHERE topic_approved = 1 
		AND topic_replies >=' . $config['hot_threshold'] . '
		AND topic_moved_id = 0
		' . $sql_where . '
	ORDER BY topic_time DESC';

    $result = $db->sql_query_limit($sql, $maxTopicsToList);
    $list = _createListFromGivenTopics(&$result);
    return $list;
}

/*
* @ignore
*/
function _createListFromGivenTopics($result) {
    global $auth,$db,$config;
    
    $list = array();
    while( ($row = $db->sql_fetchrow($result)) && ($row['topic_title'])) {
    	if ( ($auth->acl_get('f_read', $row['forum_id'])) || ($row['forum_id'] == '0') ) {
           /* $cTopic = array('topic_title' => $row['topic_title'],
                            'forum_id' => $row['forum_id'],
                            'topic_id' => $row['topic_id'],
                            'direct_link' => append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $row['forum_id'] . '&amp;t=' . $row['topic_id']));;
	   */
	            $cTopic = array(
                                'topic_id' =>  $row['topic_id'],
                                'forum_id' =>  $row['forum_id'],
                                'topic_title' =>  censor_text($row['topic_title']),
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
                                'topic_poster' => $row['topic_poster']);
            $list[] = $cTopic;
        }
    }
    return $list;
}

/**
* Return recents announcements. You can provide a maxTopicsToList as integer (the max number of items to show) or<br/>
* - <b>$excludeForumIdsList</b>		[array] 	to exclude some forums (as array of forum's IDs) to exclude from fetch
* - <b>$maxToList</b> 				[integer]	max announcements to list
* - <b>$excludeForumIdsList</b> 	[array] 	lists of forum-ids to exclude
*
* @since 1.0
* @return array see items dictionary keys of <i>getRecentsHotTopics()</i> method.
*/

function getRecentsAnnouncements($args) {
    $maxToList = $args[0];
    $excludeForumIdsList = $args[1];
    if ($maxToList == null || $maxToList == 0) $maxToList = 5;
    
    
    global $db,$config,$auth;
    $sql_where = _compileExcludeForumsPredicateFromList($excludeForumIdsList);
       $whatReturn = "topic_id,forum_id,topic_title,topic_poster,topic_time,topic_views,topic_replies_real,topic_first_post_id,topic_poster,topic_first_poster_name,topic_last_post_id,topic_last_poster_id,topic_last_poster_name,topic_last_post_time";

    
	$sql = 'SELECT ' .$whatReturn.' FROM ' . TOPICS_TABLE . '
	WHERE topic_status <> ' . FORUM_LINK . '
		AND topic_approved = 1 
		AND ( topic_type = ' . POST_ANNOUNCE . ' OR topic_type = ' . POST_GLOBAL . ' )
		AND topic_moved_id = 0
		' . $sql_where . '
	ORDER BY topic_time DESC LIMIT 0,'.$maxToList;


//    $result = $db->sql_query_limit($sql, $maxToList);
    $result = $db->sql_query($sql);
    $list = _createListFromGivenTopics(&$result);
    return array('page_showed' => 0,'items' => $list, 'total_pages' => 1, 'total_items' => count($list));
}

/**
* Return the number of posts in a specified topic
* - <b>$topic_id</b>		[integer]	target topic unique id
*
* @since 1.1
* @return integer the number of posts
*/
function countPostsInTopic($args) {
	global $db,$auth,$config,$user;
	
	$topic_id = $args[0];
	$sql = 'SELECT COUNT(post_id) AS num_posts
		FROM ' . POSTS_TABLE . "
		WHERE topic_id = $topic_id
			AND post_time >= $min_post_time
		" . (($auth->acl_get('m_approve', $forum_id)) ? '' : 'AND post_approved = 1');
	$result = $db->sql_query($sql);
	$total_posts = (int) $db->sql_fetchfield('num_posts');
	$db->sql_freeresult($result);
	return $total_posts;
}
								
/**
* Use this function to read messages from a specified topic idr<br/>
*   <br/><b>PARAMETERS:</b><br/>
* - <b>$forum_id</b> 		<i>[integer]</i> 	forum id where your message is located <br/>
* - <b>$topic_id</b> 		<i>[integer]</i> 	topic id you want to see<br/>
* - <b>$post_id</b> 		<i>[integer]</i> 	post id you want to show (leave null to show the entire topic messages paginated)<br/>
* - <b>$voted_id</b> 		<i>[array]</i> 		<i>**to see** (leave it as array())</i><br/>
* - <b>$start</b> 			<i>[integer]</i> 	start page (default 0)<br/>
* - <b>$view</b> 			<i>[string]</i> 	can be <b>''</b> (default), <b>'unread'</b> (unread msgs), or <b>'next'/'previous'</b> (next/p thread? we should see what it mean)<br/>
* - <b>$sort_days</b> 		<i>[integer]</i> 	show messages from last x days (default is 0)<br/>
* - <b>$sort_key</b> 		<i>[string]</i> 	sort options by <b>'t'</b> time, <b>'a'</b> author or <b>'s'</b> message's title<br/>
* - <b>$sort_dir</b> 		<i>[string]</i> 	sort options by <b>'a'</b> ascending, <b>'d'</b> descending<br/>
* - <b>$update</b> 			<i>[boolean]</i> 	<i>**to see** (leave it as false)</i><br/>
* - <b>$hilit_words</b> 	<i>[string]</i> 	if you want to highlight some words you should fill it with your choosed words separated by space char<br/>
* - <b>$password</b> 		<i>[string]</i> 	password (set it if forum is protected)<br/>
* - <b>$itemsPerPage</b> 	<i>[integer]</b> 	messages per page<br/>
*
* @since 1.0
* @version 1.0
* @return <b>ERROR MESSAGES</b><br/>
*			<b>GENERAL_PASSWORDPROTECTED_ITEM</b> password protected item<br/>
*			<b>LOGIN_FPSWD_ERR</b> given password is wrong<br/>
*			<b>LOGIN_FPSWD_WRONGFORUMID</b> wrong forum id<br/>
*			<b>GET_TOPICMSGS_NOTOPIC</b> given topic id does not exist here<br/>
*			<b>GET_TOPICMSGS_NONEWERTOPICS</b> no newer topics after current selection<br/>
*			<b>GET_TOPICMSGS_NOOLDERTOPICS</b> no older topics before current selection<br/>
*			<b>GET_TOPICMSGS_NOTOPICDATA</b> no data for this topic id<br/>
*			<b>GET_TOPICMSGS_AUTHREAD</b> you have not authorization<br/>
*			<b>GET_TOPICMSGS_NOPOSTINTIMEFRAME</b> time range does not contains posts<br/>
*			<br/>-----------------------------------------------------------<br/><br/>
*			<b>CORRECT VALUES</b><br/>
*			it returns an array with three main attributes:
*			<br/><br/>
*			- <b>'current_page'</b> 	[integer] current showed page of messages<br/>
*			- <b>'pages_number'</b> 	[integer] number of pages of the topic<br/>
*			- <b>'topic_infos'</b> 	[array] information about the topic and it's parent, contains these keys:<br/><br/>
*					<b>'FORUM_ID'</b> 		[integer] parent forum id<br/>
*					<b>'FORUM_NAME'</b>		[string] parent forum name<br/>
*					<b>'FORUM_DESC'</b>		[string] parent forum description<br/>
*					<b>'TOPIC_ID'</b> 		[integer] parent topic id<br/>
*					<b>'TOPIC_TITLE'</b> 	[string] topic title<br/>
*					<b>'TOPIC_POSTER'</b> 	[integer] topic author id<br/>
*					<b>'TOPIC_AUTHOR'</b> 	[string] topic author name<br/>
*					<b>'TOTAL_POSTS'</b> 	[integer] total posts in topic<br/>
*				<br/>
*			- <b>'topic_messages'</b> Each object (a message) contains these relevant keys:<br/><br/>
*				<b>'post_time'</b>			[integer]	the post creation date <br/>
*				<b>'post_text'</b>			[string]	the post message <br/>
*				<b>'post_username'</b>		[string]	the post author name <br/>
*				<b>'poster_id'</b>			[integer]	the post author id <br/>
*				<b>'post_id'</b>			[integer]	the post id <br/>
*				<b>'forum_id'</b>			[integer]	parent forum id <br/>
*				<b>'topic_id'</b>			[integer]	parent topic id <br/>
*				<b>'poster_avatar'</b>		[string]	link to avatar img <br/>
*				<b>'poster_joined'</b>		[integer]	poster joined date as unix time format <br/>
*				<b>'poster_posts'</b>		[integer]	poster number of posts <br/>
*				<b>'signature'</b>			[string]	poster signature <br/>
*				<b>'post_subject'</b>		[string]	post subject <br/>
*/

function getTopicsMessages($args) {
	return _getTopicMessages($args[0],$args[1],$args[2],$args[3],$args[4],$args[5],$args[6],$args[7],$args[8],$args[9],$args[10],$args[11],$args[12]);
}

/*
* @ignore
*/
function _getTopicMessages(	$forum_id = 0, 
							$topic_id = 0, 
							$post_id  = 0, 
							$voted_id = array('' => 0),
							
							$start = 0, 
							$view  = '',
							
							$sort_days = 0,
							$sort_key  = 't',
							$sort_dir  = 'a',
							
							$update = false, 
							$hilit_words = '',
							$password = '',
							$itemsPerPage = 10) {
	
	
	global $user, $auth, $config, $db, $cache;
	
	// Start session management
	//$user->session_begin();
	$auth->acl($user->data);
	
	if ($itemsPerPage == 0) $itemsPerPage = 10;
	
        $pageToShow = $start;
	// se si vuole ad esempio pagina x, scrivere $start = (x-1) * $config['posts_per_page'] <---- NO, INIZIAMO COME PAGINA 1 = 0
	//$start = ($start-1)*$itemsPerPage;
	$start = ($start)*$itemsPerPage;
	/*
	// Initial var setup
	$forum_id	= request_var('f', 0);
	$topic_id	= request_var('t', 0);
	$post_id	= request_var('p', 0);
	$voted_id	= request_var('vote_id', array('' => 0));
	
	$start		= request_var('start', 0);
	$view		= request_var('view', '');
	
	$sort_days	= request_var('st', ((!empty($user->data['user_post_show_days'])) ? $user->data['user_post_show_days'] : 0));
	$sort_key	= request_var('sk', ((!empty($user->data['user_post_sortby_type'])) ? $user->data['user_post_sortby_type'] : 't'));
	$sort_dir	= request_var('sd', ((!empty($user->data['user_post_sortby_dir'])) ? $user->data['user_post_sortby_dir'] : 'a'));
	
	$update		= request_var('update', false);

/**
* @todo normalize?
*/
//$hilit_words	= request_var('hilit', '', true);

// Do we have a topic or post id?
if (!$topic_id && !$post_id)
{
	return GET_TOPICMSGS_NOTOPIC;
}

// Find topic id if user requested a newer or older topic
if ($view && !$post_id)
{
	if (!$forum_id)
	{
		$sql = 'SELECT forum_id
			FROM ' . TOPICS_TABLE . "
			WHERE topic_id = $topic_id";
		$result = $db->sql_query($sql);
		$forum_id = (int) $db->sql_fetchfield('forum_id');
		$db->sql_freeresult($result);

		if (!$forum_id)
		{
			return GET_TOPICMSGS_NOTOPIC;
		}
	}

	if ($view == 'unread')
	{
		// Get topic tracking info
		$topic_tracking_info = get_complete_topic_tracking($forum_id, $topic_id);

		$topic_last_read = (isset($topic_tracking_info[$topic_id])) ? $topic_tracking_info[$topic_id] : 0;

		$sql = 'SELECT post_id, topic_id, forum_id
			FROM ' . POSTS_TABLE . "
			WHERE topic_id = $topic_id
				" . (($auth->acl_get('m_approve', $forum_id)) ? '' : 'AND post_approved = 1') . "
				AND post_time > $topic_last_read
			ORDER BY post_time ASC";
		$result = $db->sql_query_limit($sql, 1);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			$sql = 'SELECT topic_last_post_id as post_id, topic_id, forum_id
				FROM ' . TOPICS_TABLE . '
				WHERE topic_id = ' . $topic_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
		}

		if (!$row)
		{
			// Setup user environment so we can process lang string
			//$user->setup('viewtopic');

			return GET_TOPICMSGS_NOTOPIC;
		}

		$post_id = $row['post_id'];
		$topic_id = $row['topic_id'];
	}
	else if ($view == 'next' || $view == 'previous')
	{
		$sql_condition = ($view == 'next') ? '>' : '<';
		$sql_ordering = ($view == 'next') ? 'ASC' : 'DESC';

		$sql = 'SELECT forum_id, topic_last_post_time
			FROM ' . TOPICS_TABLE . '
			WHERE topic_id = ' . $topic_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			$user->setup('viewtopic');
			// OK, the topic doesn't exist. This error message is not helpful, but technically correct.
			//trigger_error(($view == 'next') ? 'NO_NEWER_TOPICS' : 'NO_OLDER_TOPICS');
			return (($view == 'next') ? GET_TOPICMSGS_NONEWERTOPICS : GET_TOPICMSGS_NOOLDERTOPICS);
		}
		else
		{
			$sql = 'SELECT topic_id, forum_id
				FROM ' . TOPICS_TABLE . '
				WHERE forum_id = ' . $row['forum_id'] . "
					AND topic_moved_id = 0
					AND topic_last_post_time $sql_condition {$row['topic_last_post_time']}
					" . (($auth->acl_get('m_approve', $row['forum_id'])) ? '' : 'AND topic_approved = 1') . "
				ORDER BY topic_last_post_time $sql_ordering";
			$result = $db->sql_query_limit($sql, 1);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$row)
			{
				$user->setup('viewtopic');
				//trigger_error(($view == 'next') ? 'NO_NEWER_TOPICS' : 'NO_OLDER_TOPICS');
				return (($view == 'next') ? GET_TOPICMSGS_NONEWERTOPICS : GET_TOPICMSGS_NOOLDERTOPICS);
			}
			else
			{
				$topic_id = $row['topic_id'];

				// Check for global announcement correctness?
				if (!$row['forum_id'] && !$forum_id)
				{
					return GET_TOPICMSGS_NOTOPIC;
				}
				else if ($row['forum_id'])
				{
					$forum_id = $row['forum_id'];
				}
			}
		}
	}

	// Check for global announcement correctness?
	if ((!isset($row) || !$row['forum_id']) && !$forum_id)
	{
		return GET_TOPICMSGS_NOTOPIC;
	}
	else if (isset($row) && $row['forum_id'])
	{
		$forum_id = $row['forum_id'];
	}
}

// This rather complex gaggle of code handles querying for topics but
// also allows for direct linking to a post (and the calculation of which
// page the post is on and the correct display of viewtopic)
$sql_array = array(
	'SELECT'	=> 't.*, f.*',

	'FROM'		=> array(
		FORUMS_TABLE	=> 'f',
	)
);

if ($user->data['is_registered'])
{
	$sql_array['SELECT'] .= ', tw.notify_status';
	$sql_array['LEFT_JOIN'] = array();

	$sql_array['LEFT_JOIN'][] = array(
		'FROM'	=> array(TOPICS_WATCH_TABLE => 'tw'),
		'ON'	=> 'tw.user_id = ' . $user->data['user_id'] . ' AND t.topic_id = tw.topic_id'
	);

	if ($config['allow_bookmarks'])
	{
		$sql_array['SELECT'] .= ', bm.topic_id as bookmarked';
		$sql_array['LEFT_JOIN'][] = array(
			'FROM'	=> array(BOOKMARKS_TABLE => 'bm'),
			'ON'	=> 'bm.user_id = ' . $user->data['user_id'] . ' AND t.topic_id = bm.topic_id'
		);
	}

	if ($config['load_db_lastread'])
	{
		$sql_array['SELECT'] .= ', tt.mark_time, ft.mark_time as forum_mark_time';

		$sql_array['LEFT_JOIN'][] = array(
			'FROM'	=> array(TOPICS_TRACK_TABLE => 'tt'),
			'ON'	=> 'tt.user_id = ' . $user->data['user_id'] . ' AND t.topic_id = tt.topic_id'
		);

		$sql_array['LEFT_JOIN'][] = array(
			'FROM'	=> array(FORUMS_TRACK_TABLE => 'ft'),
			'ON'	=> 'ft.user_id = ' . $user->data['user_id'] . ' AND t.forum_id = ft.forum_id'
		);
	}
}

if (!$post_id)
{
	$sql_array['WHERE'] = "t.topic_id = $topic_id";
}
else
{
	$sql_array['WHERE'] = "p.post_id = $post_id AND t.topic_id = p.topic_id" . ((!$auth->acl_get('m_approve', $forum_id)) ? ' AND p.post_approved = 1' : '');
	$sql_array['FROM'][POSTS_TABLE] = 'p';
}

$sql_array['WHERE'] .= ' AND (f.forum_id = t.forum_id';

if (!$forum_id)
{
	// If it is a global announcement make sure to set the forum id to a postable forum
	$sql_array['WHERE'] .= ' OR (t.topic_type = ' . POST_GLOBAL . '
		AND f.forum_type = ' . FORUM_POST . ')';
}
else
{
	$sql_array['WHERE'] .= ' OR (t.topic_type = ' . POST_GLOBAL . "
		AND f.forum_id = $forum_id)";
}

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

if (!$topic_data)
{
	// If post_id was submitted, we try at least to display the topic as a last resort...
	if ($post_id && $forum_id && $topic_id)
	{
		//redirect(append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id"));
		return GET_TOPICMSGS_NOTOPICDATA;
	}

	return GET_TOPICMSGS_NOTOPIC;
}

// This is for determining where we are (page)
if ($post_id)
{
	if ($post_id == $topic_data['topic_first_post_id'] || $post_id == $topic_data['topic_last_post_id'])
	{
		$check_sort = ($post_id == $topic_data['topic_first_post_id']) ? 'd' : 'a';

		if ($sort_dir == $check_sort)
		{
			$topic_data['prev_posts'] = ($auth->acl_get('m_approve', $forum_id)) ? $topic_data['topic_replies_real'] : $topic_data['topic_replies'];
		}
		else
		{
			$topic_data['prev_posts'] = 0;
		}
	}
	else
	{
		$sql = 'SELECT COUNT(p1.post_id) AS prev_posts
			FROM ' . POSTS_TABLE . ' p1, ' . POSTS_TABLE . " p2
			WHERE p1.topic_id = {$topic_data['topic_id']}
				AND p2.post_id = {$post_id}
				" . ((!$auth->acl_get('m_approve', $forum_id)) ? 'AND p1.post_approved = 1' : '') . '
				AND ' . (($sort_dir == 'd') ? 'p1.post_time >= p2.post_time' : 'p1.post_time <= p2.post_time');

		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		$topic_data['prev_posts'] = $row['prev_posts'] - 1;
	}
}

$forum_id = (int) $topic_data['forum_id'];
$topic_id = (int) $topic_data['topic_id'];

//
$topic_replies = ($auth->acl_get('m_approve', $forum_id)) ? $topic_data['topic_replies_real'] : $topic_data['topic_replies'];

// Check sticky/announcement time limit
if (($topic_data['topic_type'] == POST_STICKY || $topic_data['topic_type'] == POST_ANNOUNCE) && $topic_data['topic_time_limit'] && ($topic_data['topic_time'] + $topic_data['topic_time_limit']) < time())
{
	$sql = 'UPDATE ' . TOPICS_TABLE . '
		SET topic_type = ' . POST_NORMAL . ', topic_time_limit = 0
		WHERE topic_id = ' . $topic_id;
	$db->sql_query($sql);

	$topic_data['topic_type'] = POST_NORMAL;
	$topic_data['topic_time_limit'] = 0;
}

// Setup look and feel
//$user->setup('viewtopic', $topic_data['forum_style']);

if (!$topic_data['topic_approved'] && !$auth->acl_get('m_approve', $forum_id))
{
	return GET_TOPICMSGS_NOTOPIC;
}

// Start auth check
if (!$auth->acl_get('f_read', $forum_id))
{
	if ($user->data['user_id'] != ANONYMOUS)
	{
		return GENERAL_ERR_MUSTLOGGED;
	}

	// PASSWORD!!
	//login_box('', $user->lang['LOGIN_VIEWFORUM']);
}

// Forum is passworded ... check whether access has been granted to this
// user this session, if not show login box
/*if ($topic_data['forum_password'])
{
	return SORRY_AUTH_READ;
	//login_forum_box($topic_data);
}*/
// Forum is passworded ... check whether access has been granted to this
// user this session, if not show login box
if ($topic_data['forum_password']) {
    if (!isset($password)) return MSG_FORUM_ERR_FORUMPASSWORDED; // forum is passworded, you have not given it
    else {
        $returnLogin = loginProtectedForumWithPassword($forum_id,$password);
        switch ($returnLogin) {
            case LOGIN_FPSWD_OK:
                return getTopicMessages($args); // we call it again but we will password ok!
                break;
            case LOGIN_FPSWD_ALREADYLOGGED:
                // nothing to do
                break;
            case LOGIN_FPSWD_WRONGFORUMID:
            case LOGIN_FPSWD_ERR:
                return GENERAL_PASSWORDPROTECTED_ITEM;
                break;
                // wrong password
        }
    }
}


/*
// Redirect to login or to the correct post upon emailed notification links
if (isset($_GET['e']))
{
	$jump_to = request_var('e', 0);

	$redirect_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id");

	if ($user->data['user_id'] == ANONYMOUS)
	{
		login_box($redirect_url . "&amp;p=$post_id&amp;e=$jump_to", $user->lang['LOGIN_NOTIFY_TOPIC']);
	}

	if ($jump_to > 0)
	{
		// We direct the already logged in user to the correct post...
		redirect($redirect_url . ((!$post_id) ? "&amp;p=$jump_to" : "&amp;p=$post_id") . "#p$jump_to");
	}
}
*/

// What is start equal to?
if ($post_id)
{
	//$start = floor(($topic_data['prev_posts']) / $config['posts_per_page']) * $config['posts_per_page'];
	$start = floor(($topic_data['prev_posts']) / $itemsPerPage) * $itemsPerPage;

}

// Get topic tracking info
if (!isset($topic_tracking_info))
{
	$topic_tracking_info = array();

	// Get topic tracking info
	if ($config['load_db_lastread'] && $user->data['is_registered'])
	{
		$tmp_topic_data = array($topic_id => $topic_data);
		$topic_tracking_info = get_topic_tracking($forum_id, $topic_id, $tmp_topic_data, array($forum_id => $topic_data['forum_mark_time']));
		unset($tmp_topic_data);
	}
	else if ($config['load_anon_lastread'] || $user->data['is_registered'])
	{
		$topic_tracking_info = get_complete_topic_tracking($forum_id, $topic_id);
	}
}

// Post ordering options
$limit_days = array(0 => $user->lang['ALL_POSTS'], 1 => $user->lang['1_DAY'], 7 => $user->lang['7_DAYS'], 14 => $user->lang['2_WEEKS'], 30 => $user->lang['1_MONTH'], 90 => $user->lang['3_MONTHS'], 180 => $user->lang['6_MONTHS'], 365 => $user->lang['1_YEAR']);

$sort_by_text = array('a' => $user->lang['AUTHOR'], 't' => $user->lang['POST_TIME'], 's' => $user->lang['SUBJECT']);
$sort_by_sql = array('a' => 'u.username_clean', 't' => 'p.post_time', 's' => 'p.post_subject');

$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

// Obtain correct post count and ordering SQL if user has
// requested anything different
if ($sort_days)
{
	$min_post_time = time() - ($sort_days * 86400);

	$sql = 'SELECT COUNT(post_id) AS num_posts
		FROM ' . POSTS_TABLE . "
		WHERE topic_id = $topic_id
			AND post_time >= $min_post_time
		" . (($auth->acl_get('m_approve', $forum_id)) ? '' : 'AND post_approved = 1');
	$result = $db->sql_query($sql);
	$total_posts = (int) $db->sql_fetchfield('num_posts');
	$db->sql_freeresult($result);

	$limit_posts_time = "AND p.post_time >= $min_post_time ";

	if (isset($_POST['sort']))
	{
		$start = 0;
	}
}
else
{
	$total_posts = $topic_replies + 1;
	$limit_posts_time = '';
}

// Was a highlight request part of the URI?
$highlight_match = $highlight = '';
if ($hilit_words)
{
	foreach (explode(' ', trim($hilit_words)) as $word)
	{
		if (trim($word))
		{
			$word = str_replace('\*', '\w+?', preg_quote($word, '#'));
			$word = preg_replace('#(^|\s)\\\\w\*\?(\s|$)#', '$1\w+?$2', $word);
			$highlight_match .= (($highlight_match != '') ? '|' : '') . $word;
		}
	}

	$highlight = urlencode($hilit_words);
}

// Make sure $start is set to the last page if it exceeds the amount
if ($start < 0 || $start > $total_posts)
{
	//$start = ($start < 0) ? 0 : floor(($total_posts - 1) / $config['posts_per_page']) * $config['posts_per_page'];
	$start = ($start < 0) ? 0 : floor(($total_posts - 1) / $itemsPerPage) * $itemsPerPage;
}

// General Viewtopic URL for return links
//$viewtopic_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;start=$start&amp;$u_sort_param" . (($highlight_match) ? "&amp;hilit=$highlight" : ''));

// Are we watching this topic?
$s_watching_topic = array(
	'link'			=> '',
	'title'			=> '',
	'is_watching'	=> false,
);


if ($config['email_enable'] && $config['allow_topic_notify'] && $user->data['is_registered'])
{
	//???
	watch_topic_forum('topic', $s_watching_topic, $user->data['user_id'], $forum_id, $topic_id, $topic_data['notify_status'], $start);
}


/*
// Bookmarks ???
if ($config['allow_bookmarks'] && $user->data['is_registered'] && request_var('bookmark', 0))
{
	if (!$topic_data['bookmarked'])
	{
		$sql = 'INSERT INTO ' . BOOKMARKS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
			'user_id'	=> $user->data['user_id'],
			'topic_id'	=> $topic_id,
		));
		$db->sql_query($sql);
	}
	else
	{
		$sql = 'DELETE FROM ' . BOOKMARKS_TABLE . "
			WHERE user_id = {$user->data['user_id']}
				AND topic_id = $topic_id";
		$db->sql_query($sql);
	}

	//meta_refresh(3, $viewtopic_url);

	$message = (($topic_data['bookmarked']) ? $user->lang['BOOKMARK_REMOVED'] : $user->lang['BOOKMARK_ADDED']) . '<br /><br />' . sprintf($user->lang['RETURN_TOPIC'], '<a href="' . $viewtopic_url . '">', '</a>');
	//trigger_error($message);
	return array
}
*/

// Grab ranks
$ranks = $cache->obtain_ranks();

// Grab icons
$icons = $cache->obtain_icons();

// Grab extensions
$extensions = array();
if ($topic_data['topic_attachment'])
{
	$extensions = $cache->obtain_attach_extensions($forum_id);
}

// Forum rules listing
$s_forum_rules = '';
gen_forum_auth_level('topic', $forum_id, $topic_data['forum_status']);

// Quick mod tools
$allow_change_type = ($auth->acl_get('m_', $forum_id) || ($user->data['is_registered'] && $user->data['user_id'] == $topic_data['topic_poster'])) ? true : false;

$topic_mod = '';
$topic_mod .= ($auth->acl_get('m_lock', $forum_id) || ($auth->acl_get('f_user_lock', $forum_id) && $user->data['is_registered'] && $user->data['user_id'] == $topic_data['topic_poster'] && $topic_data['topic_status'] == ITEM_UNLOCKED)) ? (($topic_data['topic_status'] == ITEM_UNLOCKED) ? '<option value="lock">' . $user->lang['LOCK_TOPIC'] . '</option>' : '<option value="unlock">' . $user->lang['UNLOCK_TOPIC'] . '</option>') : '';
$topic_mod .= ($auth->acl_get('m_delete', $forum_id)) ? '<option value="delete_topic">' . $user->lang['DELETE_TOPIC'] . '</option>' : '';
$topic_mod .= ($auth->acl_get('m_move', $forum_id) && $topic_data['topic_status'] != ITEM_MOVED) ? '<option value="move">' . $user->lang['MOVE_TOPIC'] . '</option>' : '';
$topic_mod .= ($auth->acl_get('m_split', $forum_id)) ? '<option value="split">' . $user->lang['SPLIT_TOPIC'] . '</option>' : '';
$topic_mod .= ($auth->acl_get('m_merge', $forum_id)) ? '<option value="merge">' . $user->lang['MERGE_POSTS'] . '</option>' : '';
$topic_mod .= ($auth->acl_get('m_merge', $forum_id)) ? '<option value="merge_topic">' . $user->lang['MERGE_TOPIC'] . '</option>' : '';
$topic_mod .= ($auth->acl_get('m_move', $forum_id)) ? '<option value="fork">' . $user->lang['FORK_TOPIC'] . '</option>' : '';
$topic_mod .= ($allow_change_type && $auth->acl_gets('f_sticky', 'f_announce', $forum_id) && $topic_data['topic_type'] != POST_NORMAL) ? '<option value="make_normal">' . $user->lang['MAKE_NORMAL'] . '</option>' : '';
$topic_mod .= ($allow_change_type && $auth->acl_get('f_sticky', $forum_id) && $topic_data['topic_type'] != POST_STICKY) ? '<option value="make_sticky">' . $user->lang['MAKE_STICKY'] . '</option>' : '';
$topic_mod .= ($allow_change_type && $auth->acl_get('f_announce', $forum_id) && $topic_data['topic_type'] != POST_ANNOUNCE) ? '<option value="make_announce">' . $user->lang['MAKE_ANNOUNCE'] . '</option>' : '';
$topic_mod .= ($allow_change_type && $auth->acl_get('f_announce', $forum_id) && $topic_data['topic_type'] != POST_GLOBAL) ? '<option value="make_global">' . $user->lang['MAKE_GLOBAL'] . '</option>' : '';
$topic_mod .= ($auth->acl_get('m_', $forum_id)) ? '<option value="topic_logs">' . $user->lang['VIEW_TOPIC_LOGS'] . '</option>' : '';

// If we've got a hightlight set pass it on to pagination.
//$pagination = generate_pagination(append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;$u_sort_param" . (($highlight_match) ? "&amp;hilit=$highlight" : '')), $total_posts, $config['posts_per_page'], $start);

// Navigation links
//generate_forum_nav($topic_data);

// Forum Rules
//generate_forum_rules($topic_data);

// Moderators
$forum_moderators = array();
get_moderators($forum_moderators, $forum_id);

// This is only used for print view so ...
$server_path = (!$view) ? $phpbb_root_path : generate_board_url() . '/';

// Replace naughty words in title
$topic_data['topic_title'] = censor_text($topic_data['topic_title']);

/*
// Send vars to template
$template->assign_vars(array(
	'FORUM_ID' 		=> $forum_id,
	'FORUM_NAME' 	=> $topic_data['forum_name'],
	'FORUM_DESC'	=> generate_text_for_display($topic_data['forum_desc'], $topic_data['forum_desc_uid'], $topic_data['forum_desc_bitfield'], $topic_data['forum_desc_options']),
	'TOPIC_ID' 		=> $topic_id,
	'TOPIC_TITLE' 	=> $topic_data['topic_title'],
	'TOPIC_POSTER'	=> $topic_data['topic_poster'],

	'TOPIC_AUTHOR_FULL'		=> get_username_string('full', $topic_data['topic_poster'], $topic_data['topic_first_poster_name'], $topic_data['topic_first_poster_colour']),
	'TOPIC_AUTHOR_COLOUR'	=> get_username_string('colour', $topic_data['topic_poster'], $topic_data['topic_first_poster_name'], $topic_data['topic_first_poster_colour']),
	'TOPIC_AUTHOR'			=> get_username_string('username', $topic_data['topic_poster'], $topic_data['topic_first_poster_name'], $topic_data['topic_first_poster_colour']),

	'PAGINATION' 	=> $pagination,
	'PAGE_NUMBER' 	=> on_page($total_posts, $config['posts_per_page'], $start),
	'TOTAL_POSTS'	=> ($total_posts == 1) ? $user->lang['VIEW_TOPIC_POST'] : sprintf($user->lang['VIEW_TOPIC_POSTS'], $total_posts),
	'U_MCP' 		=> ($auth->acl_get('m_', $forum_id)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", "i=main&amp;mode=topic_view&amp;f=$forum_id&amp;t=$topic_id&amp;start=$start&amp;$u_sort_param", true, $user->session_id) : '',
	'MODERATORS'	=> (isset($forum_moderators[$forum_id]) && sizeof($forum_moderators[$forum_id])) ? implode(', ', $forum_moderators[$forum_id]) : '',

	'POST_IMG' 			=> ($topic_data['forum_status'] == ITEM_LOCKED) ? $user->img('button_topic_locked', 'FORUM_LOCKED') : $user->img('button_topic_new', 'POST_NEW_TOPIC'),
	'QUOTE_IMG' 		=> $user->img('icon_post_quote', 'REPLY_WITH_QUOTE'),
	'REPLY_IMG'			=> ($topic_data['forum_status'] == ITEM_LOCKED || $topic_data['topic_status'] == ITEM_LOCKED) ? $user->img('button_topic_locked', 'TOPIC_LOCKED') : $user->img('button_topic_reply', 'REPLY_TO_TOPIC'),
	'EDIT_IMG' 			=> $user->img('icon_post_edit', 'EDIT_POST'),
	'DELETE_IMG' 		=> $user->img('icon_post_delete', 'DELETE_POST'),
	'INFO_IMG' 			=> $user->img('icon_post_info', 'VIEW_INFO'),
	'PROFILE_IMG'		=> $user->img('icon_user_profile', 'READ_PROFILE'),
	'SEARCH_IMG' 		=> $user->img('icon_user_search', 'SEARCH_USER_POSTS'),
	'PM_IMG' 			=> $user->img('icon_contact_pm', 'SEND_PRIVATE_MESSAGE'),
	'EMAIL_IMG' 		=> $user->img('icon_contact_email', 'SEND_EMAIL'),
	'WWW_IMG' 			=> $user->img('icon_contact_www', 'VISIT_WEBSITE'),
	'ICQ_IMG' 			=> $user->img('icon_contact_icq', 'ICQ'),
	'AIM_IMG' 			=> $user->img('icon_contact_aim', 'AIM'),
	'MSN_IMG' 			=> $user->img('icon_contact_msnm', 'MSNM'),
	'YIM_IMG' 			=> $user->img('icon_contact_yahoo', 'YIM'),
	'JABBER_IMG'		=> $user->img('icon_contact_jabber', 'JABBER') ,
	'REPORT_IMG'		=> $user->img('icon_post_report', 'REPORT_POST'),
	'REPORTED_IMG'		=> $user->img('icon_topic_reported', 'POST_REPORTED'),
	'UNAPPROVED_IMG'	=> $user->img('icon_topic_unapproved', 'POST_UNAPPROVED'),
	'WARN_IMG'			=> $user->img('icon_user_warn', 'WARN_USER'),

	'S_IS_LOCKED'			=>($topic_data['topic_status'] == ITEM_UNLOCKED) ? false : true,
	'S_SELECT_SORT_DIR' 	=> $s_sort_dir,
	'S_SELECT_SORT_KEY' 	=> $s_sort_key,
	'S_SELECT_SORT_DAYS' 	=> $s_limit_days,
	'S_SINGLE_MODERATOR'	=> (!empty($forum_moderators[$forum_id]) && sizeof($forum_moderators[$forum_id]) > 1) ? false : true,
	'S_TOPIC_ACTION' 		=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;start=$start"),
	'S_TOPIC_MOD' 			=> ($topic_mod != '') ? '<select name="action" id="quick-mod-select">' . $topic_mod . '</select>' : '',
	'S_MOD_ACTION' 			=> append_sid("{$phpbb_root_path}mcp.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;quickmod=1&amp;redirect=" . urlencode(str_replace('&amp;', '&', $viewtopic_url)), true, $user->session_id),

	'S_VIEWTOPIC'			=> true,
	'S_DISPLAY_SEARCHBOX'	=> ($auth->acl_get('u_search') && $auth->acl_get('f_search', $forum_id) && $config['load_search']) ? true : false,
	'S_SEARCHBOX_ACTION'	=> append_sid("{$phpbb_root_path}search.$phpEx", 't=' . $topic_id),

	'S_DISPLAY_POST_INFO'	=> ($topic_data['forum_type'] == FORUM_POST && ($auth->acl_get('f_post', $forum_id) || $user->data['user_id'] == ANONYMOUS)) ? true : false,
	'S_DISPLAY_REPLY_INFO'	=> ($topic_data['forum_type'] == FORUM_POST && ($auth->acl_get('f_reply', $forum_id) || $user->data['user_id'] == ANONYMOUS)) ? true : false,

	'U_TOPIC'				=> "{$server_path}viewtopic.$phpEx?f=$forum_id&amp;t=$topic_id",
	'U_FORUM'				=> $server_path,
	'U_VIEW_TOPIC' 			=> $viewtopic_url,
	'U_VIEW_FORUM' 			=> append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $forum_id),
	'U_VIEW_OLDER_TOPIC'	=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;view=previous"),
	'U_VIEW_NEWER_TOPIC'	=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;view=next"),
	'U_PRINT_TOPIC'			=> ($auth->acl_get('f_print', $forum_id)) ? $viewtopic_url . '&amp;view=print' : '',
	'U_EMAIL_TOPIC'			=> ($auth->acl_get('f_email', $forum_id) && $config['email_enable']) ? append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=email&amp;t=$topic_id") : '',

	'U_WATCH_TOPIC' 		=> $s_watching_topic['link'],
	'L_WATCH_TOPIC' 		=> $s_watching_topic['title'],
	'S_WATCHING_TOPIC'		=> $s_watching_topic['is_watching'],

	'U_BOOKMARK_TOPIC'		=> ($user->data['is_registered'] && $config['allow_bookmarks']) ? $viewtopic_url . '&amp;bookmark=1' : '',
	'L_BOOKMARK_TOPIC'		=> ($user->data['is_registered'] && $config['allow_bookmarks'] && $topic_data['bookmarked']) ? $user->lang['BOOKMARK_TOPIC_REMOVE'] : $user->lang['BOOKMARK_TOPIC'],

	'U_POST_NEW_TOPIC' 		=> ($auth->acl_get('f_post', $forum_id) || $user->data['user_id'] == ANONYMOUS) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=post&amp;f=$forum_id") : '',
	'U_POST_REPLY_TOPIC' 	=> ($auth->acl_get('f_reply', $forum_id) || $user->data['user_id'] == ANONYMOUS) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=reply&amp;f=$forum_id&amp;t=$topic_id") : '',
	'U_BUMP_TOPIC'			=> (bump_topic_allowed($forum_id, $topic_data['topic_bumped'], $topic_data['topic_last_post_time'], $topic_data['topic_poster'], $topic_data['topic_last_poster_id'])) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=bump&amp;f=$forum_id&amp;t=$topic_id") : '')
);
*/

$informazioni = array(
	'FORUM_ID' 		=> $forum_id,
	'FORUM_NAME' 	=> $topic_data['forum_name'],
	'FORUM_DESC'	=> generate_text_for_display($topic_data['forum_desc'], $topic_data['forum_desc_uid'], $topic_data['forum_desc_bitfield'], $topic_data['forum_desc_options']),
	'TOPIC_ID' 		=> $topic_id,
	'TOPIC_TITLE' 	=> $topic_data['topic_title'],
	'TOPIC_POSTER'	=> $topic_data['topic_poster'],

	'TOPIC_AUTHOR_FULL'		=> get_username_string('full', $topic_data['topic_poster'], $topic_data['topic_first_poster_name'], $topic_data['topic_first_poster_colour']),
	'TOPIC_AUTHOR_COLOUR'	=> get_username_string('colour', $topic_data['topic_poster'], $topic_data['topic_first_poster_name'], $topic_data['topic_first_poster_colour']),
	'TOPIC_AUTHOR'			=> get_username_string('username', $topic_data['topic_poster'], $topic_data['topic_first_poster_name'], $topic_data['topic_first_poster_colour']),

	//'PAGINATION' 	=> $pagination, non si può usare, contiene link a pagine del forum stesso
	'PAGE_NUMBER' 	=> on_page($total_posts, $config['posts_per_page'], $start),
	'TOTAL_POSTS'	=> $total_posts, //($total_posts == 1) ? $user->lang['VIEW_TOPIC_POST'] : sprintf($user->lang['VIEW_TOPIC_POSTS'], $total_posts),
	
	'MODERATORS'	=> (isset($forum_moderators[$forum_id]) && sizeof($forum_moderators[$forum_id])) ? implode(', ', $forum_moderators[$forum_id]) : '',

	//'POST_IMG' 			=> ($topic_data['forum_status'] == ITEM_LOCKED) ? $user->img('button_topic_locked', 'FORUM_LOCKED') : $user->img('button_topic_new', 'POST_NEW_TOPIC'),
	//'QUOTE_IMG' 		=> $user->img('icon_post_quote', 'REPLY_WITH_QUOTE'),
	//'REPLY_IMG'			=> ($topic_data['forum_status'] == ITEM_LOCKED || $topic_data['topic_status'] == ITEM_LOCKED) ? $user->img('button_topic_locked', 'TOPIC_LOCKED') : $user->img('button_topic_reply', 'REPLY_TO_TOPIC'),
	//'EDIT_IMG' 			=> $user->img('icon_post_edit', 'EDIT_POST'),
	//'DELETE_IMG' 		=> $user->img('icon_post_delete', 'DELETE_POST'),
	//'INFO_IMG' 			=> $user->img('icon_post_info', 'VIEW_INFO'),
	//'PROFILE_IMG'		=> $user->img('icon_user_profile', 'READ_PROFILE'),
	//'SEARCH_IMG' 		=> $user->img('icon_user_search', 'SEARCH_USER_POSTS'),
//	'PM_IMG' 			=> $user->img('icon_contact_pm', 'SEND_PRIVATE_MESSAGE'),
//	'EMAIL_IMG' 		=> $user->img('icon_contact_email', 'SEND_EMAIL'),
//	'WWW_IMG' 			=> $user->img('icon_contact_www', 'VISIT_WEBSITE'),
//	'ICQ_IMG' 			=> $user->img('icon_contact_icq', 'ICQ'),
//	'AIM_IMG' 			=> $user->img('icon_contact_aim', 'AIM'),
//	'MSN_IMG' 			=> $user->img('icon_contact_msnm', 'MSNM'),
//	'YIM_IMG' 			=> $user->img('icon_contact_yahoo', 'YIM'),
//	'JABBER_IMG'		=> $user->img('icon_contact_jabber', 'JABBER') ,
//	'REPORT_IMG'		=> $user->img('icon_post_report', 'REPORT_POST'),
//	'REPORTED_IMG'		=> $user->img('icon_topic_reported', 'POST_REPORTED'),
//	'UNAPPROVED_IMG'	=> $user->img('icon_topic_unapproved', 'POST_UNAPPROVED'),
//	'WARN_IMG'			=> $user->img('icon_user_warn', 'WARN_USER'),

	'S_IS_LOCKED'			=>($topic_data['topic_status'] == ITEM_UNLOCKED) ? false : true,
	'S_SELECT_SORT_DIR' 	=> $s_sort_dir,
	'S_SELECT_SORT_KEY' 	=> $s_sort_key,
	'S_SELECT_SORT_DAYS' 	=> $s_limit_days,
	'S_SINGLE_MODERATOR'	=> (!empty($forum_moderators[$forum_id]) && sizeof($forum_moderators[$forum_id]) > 1) ? false : true,
//	'S_TOPIC_ACTION' 		=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;start=$start"),
//	'S_TOPIC_MOD' 			=> ($topic_mod != '') ? '<select name="action" id="quick-mod-select">' . $topic_mod . '</select>' : '',
//	'S_MOD_ACTION' 			=> append_sid("{$phpbb_root_path}mcp.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;quickmod=1&amp;redirect=" . urlencode(str_replace('&amp;', '&', $viewtopic_url)), true, $user->session_id),

//	'S_VIEWTOPIC'			=> true,
//	'S_DISPLAY_SEARCHBOX'	=> ($auth->acl_get('u_search') && $auth->acl_get('f_search', $forum_id) && $config['load_search']) ? true : false,
//	'S_SEARCHBOX_ACTION'	=> append_sid("{$phpbb_root_path}search.$phpEx", 't=' . $topic_id),

	'S_DISPLAY_POST_INFO'	=> ($topic_data['forum_type'] == FORUM_POST && ($auth->acl_get('f_post', $forum_id) || $user->data['user_id'] == ANONYMOUS)) ? true : false,
	'S_DISPLAY_REPLY_INFO'	=> ($topic_data['forum_type'] == FORUM_POST && ($auth->acl_get('f_reply', $forum_id) || $user->data['user_id'] == ANONYMOUS)) ? true : false,

//	'U_TOPIC'				=> "{$server_path}viewtopic.$phpEx?f=$forum_id&amp;t=$topic_id",
//	'U_FORUM'				=> $server_path,
	'U_VIEW_TOPIC' 			=> $viewtopic_url,
//	'U_VIEW_FORUM' 			=> append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $forum_id),
//	'U_VIEW_OLDER_TOPIC'	=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;view=previous"),
//	'U_VIEW_NEWER_TOPIC'	=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;view=next"),
//	'U_PRINT_TOPIC'			=> ($auth->acl_get('f_print', $forum_id)) ? $viewtopic_url . '&amp;view=print' : '',
//	'U_EMAIL_TOPIC'			=> ($auth->acl_get('f_email', $forum_id) && $config['email_enable']) ? append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=email&amp;t=$topic_id") : '',

	//'U_WATCH_TOPIC' 		=> $s_watching_topic['link'],
	//'L_WATCH_TOPIC' 		=> $s_watching_topic['title'],
	//'S_WATCHING_TOPIC'		=> $s_watching_topic['is_watching'],

//	'U_BOOKMARK_TOPIC'		=> ($user->data['is_registered'] && $config['allow_bookmarks']) ? $viewtopic_url . '&amp;bookmark=1' : '',
//	'L_BOOKMARK_TOPIC'		=> ($user->data['is_registered'] && $config['allow_bookmarks'] && $topic_data['bookmarked']) ? $user->lang['BOOKMARK_TOPIC_REMOVE'] : $user->lang['BOOKMARK_TOPIC'],

//	'U_POST_NEW_TOPIC' 		=> ($auth->acl_get('f_post', $forum_id) || $user->data['user_id'] == ANONYMOUS) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=post&amp;f=$forum_id") : '',
//	'U_POST_REPLY_TOPIC' 	=> ($auth->acl_get('f_reply', $forum_id) || $user->data['user_id'] == ANONYMOUS) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=reply&amp;f=$forum_id&amp;t=$topic_id") : '',
//	'U_BUMP_TOPIC'			=> (bump_topic_allowed($forum_id, $topic_data['topic_bumped'], $topic_data['topic_last_post_time'], $topic_data['topic_poster'], $topic_data['topic_last_poster_id'])) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=bump&amp;f=$forum_id&amp;t=$topic_id") : ''))
);



// Does this topic contain a poll?
if (!empty($topic_data['poll_start']))
{
	$sql = 'SELECT o.*, p.bbcode_bitfield, p.bbcode_uid
		FROM ' . POLL_OPTIONS_TABLE . ' o, ' . POSTS_TABLE . " p
		WHERE o.topic_id = $topic_id
			AND p.post_id = {$topic_data['topic_first_post_id']}
			AND p.topic_id = o.topic_id
		ORDER BY o.poll_option_id";
	$result = $db->sql_query($sql);

	$poll_info = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$poll_info[] = $row;
	}
	$db->sql_freeresult($result);

	$cur_voted_id = array();
	if ($user->data['is_registered'])
	{
		$sql = 'SELECT poll_option_id
			FROM ' . POLL_VOTES_TABLE . '
			WHERE topic_id = ' . $topic_id . '
				AND vote_user_id = ' . $user->data['user_id'];
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$cur_voted_id[] = $row['poll_option_id'];
		}
		$db->sql_freeresult($result);
	}
	else
	{
		// Cookie based guest tracking ... I don't like this but hum ho
		// it's oft requested. This relies on "nice" users who don't feel
		// the need to delete cookies to mess with results.
		if (isset($_COOKIE[$config['cookie_name'] . '_poll_' . $topic_id]))
		{
			$cur_voted_id = explode(',', $_COOKIE[$config['cookie_name'] . '_poll_' . $topic_id]);
			$cur_voted_id = array_map('intval', $cur_voted_id);
		}
	}

	$s_can_vote = (((!sizeof($cur_voted_id) && $auth->acl_get('f_vote', $forum_id)) ||
		($auth->acl_get('f_votechg', $forum_id) && $topic_data['poll_vote_change'])) &&
		(($topic_data['poll_length'] != 0 && $topic_data['poll_start'] + $topic_data['poll_length'] > time()) || $topic_data['poll_length'] == 0) &&
		$topic_data['topic_status'] != ITEM_LOCKED &&
		$topic_data['forum_status'] != ITEM_LOCKED) ? true : false;
	$s_display_results = (!$s_can_vote || ($s_can_vote && sizeof($cur_voted_id)) || $view == 'viewpoll') ? true : false;

	if ($update && $s_can_vote)
	{

		if (!sizeof($voted_id) || sizeof($voted_id) > $topic_data['poll_max_options'] || in_array(VOTE_CONVERTED, $cur_voted_id))
		{
			$redirect_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;start=$start");

			meta_refresh(5, $redirect_url);
			if (!sizeof($voted_id))
			{
				$message = 'NO_VOTE_OPTION';
			}
			else if (sizeof($voted_id) > $topic_data['poll_max_options'])
			{
				$message = 'TOO_MANY_VOTE_OPTIONS';
			}
			else
			{
				$message = 'VOTE_CONVERTED';
			}

			$message = $user->lang[$message] . '<br /><br />' . sprintf($user->lang['RETURN_TOPIC'], '<a href="' . $redirect_url . '">', '</a>');
			trigger_error($message);
		}

		foreach ($voted_id as $option)
		{
			if (in_array($option, $cur_voted_id))
			{
				continue;
			}

			$sql = 'UPDATE ' . POLL_OPTIONS_TABLE . '
				SET poll_option_total = poll_option_total + 1
				WHERE poll_option_id = ' . (int) $option . '
					AND topic_id = ' . (int) $topic_id;
			$db->sql_query($sql);

			if ($user->data['is_registered'])
			{
				$sql_ary = array(
					'topic_id'			=> (int) $topic_id,
					'poll_option_id'	=> (int) $option,
					'vote_user_id'		=> (int) $user->data['user_id'],
					'vote_user_ip'		=> (string) $user->ip,
				);

				$sql = 'INSERT INTO ' . POLL_VOTES_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
				$db->sql_query($sql);
			}
		}

		foreach ($cur_voted_id as $option)
		{
			if (!in_array($option, $voted_id))
			{
				$sql = 'UPDATE ' . POLL_OPTIONS_TABLE . '
					SET poll_option_total = poll_option_total - 1
					WHERE poll_option_id = ' . (int) $option . '
						AND topic_id = ' . (int) $topic_id;
				$db->sql_query($sql);

				if ($user->data['is_registered'])
				{
					$sql = 'DELETE FROM ' . POLL_VOTES_TABLE . '
						WHERE topic_id = ' . (int) $topic_id . '
							AND poll_option_id = ' . (int) $option . '
							AND vote_user_id = ' . (int) $user->data['user_id'];
					$db->sql_query($sql);
				}
			}
		}

		if ($user->data['user_id'] == ANONYMOUS && !$user->data['is_bot'])
		{
			$user->set_cookie('poll_' . $topic_id, implode(',', $voted_id), time() + 31536000);
		}

		$sql = 'UPDATE ' . TOPICS_TABLE . '
			SET poll_last_vote = ' . time() . "
			WHERE topic_id = $topic_id";
		//, topic_last_post_time = ' . time() . " -- for bumping topics with new votes, ignore for now
		$db->sql_query($sql);

		$redirect_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;start=$start");

		meta_refresh(5, $redirect_url);
		trigger_error($user->lang['VOTE_SUBMITTED'] . '<br /><br />' . sprintf($user->lang['RETURN_TOPIC'], '<a href="' . $redirect_url . '">', '</a>'));
	}

	$poll_total = 0;
	foreach ($poll_info as $poll_option)
	{
		$poll_total += $poll_option['poll_option_total'];
	}

	if ($poll_info[0]['bbcode_bitfield'])
	{
		$poll_bbcode = new bbcode();
	}
	else
	{
		$poll_bbcode = false;
	}

	for ($i = 0, $size = sizeof($poll_info); $i < $size; $i++)
	{
		$poll_info[$i]['poll_option_text'] = censor_text($poll_info[$i]['poll_option_text']);

		if ($poll_bbcode !== false)
		{
			$poll_bbcode->bbcode_second_pass($poll_info[$i]['poll_option_text'], $poll_info[$i]['bbcode_uid'], $poll_option['bbcode_bitfield']);
		}

		$poll_info[$i]['poll_option_text'] = bbcode_nl2br($poll_info[$i]['poll_option_text']);
		$poll_info[$i]['poll_option_text'] = smiley_text($poll_info[$i]['poll_option_text']);
	}

	$topic_data['poll_title'] = censor_text($topic_data['poll_title']);

	if ($poll_bbcode !== false)
	{
		$poll_bbcode->bbcode_second_pass($topic_data['poll_title'], $poll_info[0]['bbcode_uid'], $poll_info[0]['bbcode_bitfield']);
	}

	$topic_data['poll_title'] = bbcode_nl2br($topic_data['poll_title']);
	$topic_data['poll_title'] = smiley_text($topic_data['poll_title']);

	unset($poll_bbcode);

	foreach ($poll_info as $poll_option)
	{
		$option_pct = ($poll_total > 0) ? $poll_option['poll_option_total'] / $poll_total : 0;
		$option_pct_txt = sprintf("%.1d%%", ($option_pct * 100));

		//$template->assign_block_vars('poll_option', array(
		//	'POLL_OPTION_ID' 		=> $poll_option['poll_option_id'],
		//	'POLL_OPTION_CAPTION' 	=> $poll_option['poll_option_text'],
		//	'POLL_OPTION_RESULT' 	=> $poll_option['poll_option_total'],
		//	'POLL_OPTION_PERCENT' 	=> $option_pct_txt,
		//	'POLL_OPTION_PCT'		=> round($option_pct * 100),
		//	'POLL_OPTION_IMG' 		=> $user->img('poll_center', $option_pct_txt, round($option_pct * 250)),
		//	'POLL_OPTION_VOTED'		=> (in_array($poll_option['poll_option_id'], $cur_voted_id)) ? true : false)
		//);
	}

	$poll_end = $topic_data['poll_length'] + $topic_data['poll_start'];

	/*
	$template->assign_vars(array(
		'POLL_QUESTION'		=> $topic_data['poll_title'],
		'TOTAL_VOTES' 		=> $poll_total,
		'POLL_LEFT_CAP_IMG'	=> $user->img('poll_left'),
		'POLL_RIGHT_CAP_IMG'=> $user->img('poll_right'),

		'L_MAX_VOTES'		=> ($topic_data['poll_max_options'] == 1) ? $user->lang['MAX_OPTION_SELECT'] : sprintf($user->lang['MAX_OPTIONS_SELECT'], $topic_data['poll_max_options']),
		'L_POLL_LENGTH'		=> ($topic_data['poll_length']) ? sprintf($user->lang[($poll_end > time()) ? 'POLL_RUN_TILL' : 'POLL_ENDED_AT'], $user->format_date($poll_end)) : '',

		'S_HAS_POLL'		=> true,
		'S_CAN_VOTE'		=> $s_can_vote,
		'S_DISPLAY_RESULTS'	=> $s_display_results,
		'S_IS_MULTI_CHOICE'	=> ($topic_data['poll_max_options'] > 1) ? true : false,
		'S_POLL_ACTION'		=> $viewtopic_url,

		'U_VIEW_RESULTS'	=> $viewtopic_url . '&amp;view=viewpoll')
	);

	*/
	unset($poll_end, $poll_info, $voted_id);
}

// If the user is trying to reach the second half of the topic, fetch it starting from the end
$store_reverse = false;
$sql_limit = $config['posts_per_page'];

if ($start > $total_posts / 2)
{
	$store_reverse = true;

	if ($start + $config['posts_per_page'] > $total_posts)
	{
		$sql_limit = min($config['posts_per_page'], max(1, $total_posts - $start));
	}

	// Select the sort order
	$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'ASC' : 'DESC');
	$sql_start = max(0, $total_posts - $sql_limit - $start);
}
else
{
	// Select the sort order
	$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');
	$sql_start = $start;
}

// Container for user details, only process once
$post_list = $user_cache = $id_cache = $attachments = $attach_list = $rowset = $update_count = $post_edit_list = array();
$has_attachments = $display_notice = false;
$bbcode_bitfield = '';
$i = $i_total = 0;

// Go ahead and pull all data for this topic
$sql = 'SELECT p.post_id
	FROM ' . POSTS_TABLE . ' p' . (($sort_by_sql[$sort_key][0] == 'u') ? ', ' . USERS_TABLE . ' u': '') . "
	WHERE p.topic_id = $topic_id
		" . ((!$auth->acl_get('m_approve', $forum_id)) ? 'AND p.post_approved = 1' : '') . "
		" . (($sort_by_sql[$sort_key][0] == 'u') ? 'AND u.user_id = p.poster_id': '') . "
		$limit_posts_time
	ORDER BY $sql_sort_order";
$result = $db->sql_query_limit($sql, $sql_limit, $sql_start);

$i = ($store_reverse) ? $sql_limit - 1 : 0;
while ($row = $db->sql_fetchrow($result))
{
	$post_list[$i] = $row['post_id'];
	($store_reverse) ? $i-- : $i++;
}
$db->sql_freeresult($result);

if (!sizeof($post_list))
{
	if ($sort_days)
	{
		return GET_TOPICMSGS_NOPOSTINTIMEFRAME;
	}
	else
	{
		return GET_TOPICMSGS_NOTOPIC;
	}
}

// Holding maximum post time for marking topic read
// We need to grab it because we do reverse ordering sometimes
$max_post_time = 0;

$sql = $db->sql_build_query('SELECT', array(
	'SELECT'	=> 'u.*, z.friend, z.foe, p.*',

	'FROM'		=> array(
		USERS_TABLE		=> 'u',
		POSTS_TABLE		=> 'p',
	),

	'LEFT_JOIN'	=> array(
		array(
			'FROM'	=> array(ZEBRA_TABLE => 'z'),
			'ON'	=> 'z.user_id = ' . $user->data['user_id'] . ' AND z.zebra_id = p.poster_id'
		)
	),

	'WHERE'		=> $db->sql_in_set('p.post_id', $post_list) . '
		AND u.user_id = p.poster_id'
));

$result = $db->sql_query($sql);

$now = getdate(time() + $user->timezone + $user->dst - date('Z'));

// Posts are stored in the $rowset array while $attach_list, $user_cache
// and the global bbcode_bitfield are built
while ($row = $db->sql_fetchrow($result))
{
	// Set max_post_time
	if ($row['post_time'] > $max_post_time)
	{
		$max_post_time = $row['post_time'];
	}

	$poster_id = $row['poster_id'];

	// Does post have an attachment? If so, add it to the list
	if ($row['post_attachment'] && $config['allow_attachments'])
	{
		$attach_list[] = $row['post_id'];

		if ($row['post_approved'])
		{
			$has_attachments = true;
		}
	}

	$rowset[$row['post_id']] = array(
		'hide_post'			=> ($row['foe'] && ($view != 'show' || $post_id != $row['post_id'])) ? true : false,

		'post_id'			=> $row['post_id'],
		'post_time'			=> $row['post_time'],
		'user_id'			=> $row['user_id'],
		'username'			=> $row['username'],
		'user_colour'		=> $row['user_colour'],
		'topic_id'			=> $row['topic_id'],
		'forum_id'			=> $row['forum_id'],
		'post_subject'		=> $row['post_subject'],
		'post_edit_count'	=> $row['post_edit_count'],
		'post_edit_time'	=> $row['post_edit_time'],
		'post_edit_reason'	=> $row['post_edit_reason'],
		'post_edit_user'	=> $row['post_edit_user'],

		// Make sure the icon actually exists
		'icon_id'			=> (isset($icons[$row['icon_id']]['img'], $icons[$row['icon_id']]['height'], $icons[$row['icon_id']]['width'])) ? $row['icon_id'] : 0,
		'post_attachment'	=> $row['post_attachment'],
		'post_approved'		=> $row['post_approved'],
		'post_reported'		=> $row['post_reported'],
		'post_username'		=> $row['post_username'],
		'post_text'			=> censor_text($row['post_text']),
		'bbcode_uid'		=> $row['bbcode_uid'],
		'bbcode_bitfield'	=> $row['bbcode_bitfield'],
		'enable_smilies'	=> $row['enable_smilies'],
		'enable_sig'		=> $row['enable_sig'],
		'friend'			=> $row['friend'],
		'foe'				=> $row['foe'],
	);

	// Define the global bbcode bitfield, will be used to load bbcodes
	$bbcode_bitfield = $bbcode_bitfield | base64_decode($row['bbcode_bitfield']);

	// Is a signature attached? Are we going to display it?
	if ($row['enable_sig'] && $config['allow_sig'] && $user->optionget('viewsigs'))
	{
		$bbcode_bitfield = $bbcode_bitfield | base64_decode($row['user_sig_bbcode_bitfield']);
	}

	// Cache various user specific data ... so we don't have to recompute
	// this each time the same user appears on this page
	if (!isset($user_cache[$poster_id]))
	{
		if ($poster_id == ANONYMOUS)
		{
			$user_cache[$poster_id] = array(
				'joined'		=> '',
				'posts'			=> '',
				'from'			=> '',

				'sig'					=> '',
				'sig_bbcode_uid'		=> '',
				'sig_bbcode_bitfield'	=> '',

				'online'			=> false,
				'avatar'			=> '',
				'rank_title'		=> '',
				'rank_image'		=> '',
				'rank_image_src'	=> '',
				'sig'				=> '',
				'posts'				=> '',
				'profile'			=> '',
				'pm'				=> '',
				'email'				=> '',
				'www'				=> '',
				'icq_status_img'	=> '',
				'icq'				=> '',
				'aim'				=> '',
				'msn'				=> '',
				'yim'				=> '',
				'jabber'			=> '',
				'search'			=> '',
				'age'				=> '',

				'username'			=> $row['username'],
				'user_colour'		=> $row['user_colour'],

				'warnings'			=> 0,
				'allow_pm'			=> 0,
			);
		}
		else
		{
			$user_sig = '';

			// We add the signature to every posters entry because enable_sig is post dependant
			if ($row['user_sig'] && $config['allow_sig'] && $user->optionget('viewsigs'))
			{
				$user_sig = $row['user_sig'];
			}

			$id_cache[] = $poster_id;

			$user_cache[$poster_id] = array(
				'joined'		=> $user->format_date($row['user_regdate']),
				'posts'			=> $row['user_posts'],
				'warnings'		=> (isset($row['user_warnings'])) ? $row['user_warnings'] : 0,
				'from'			=> (!empty($row['user_from'])) ? $row['user_from'] : '',

				'sig'					=> $user_sig,
				'sig_bbcode_uid'		=> (!empty($row['user_sig_bbcode_uid'])) ? $row['user_sig_bbcode_uid'] : '',
				'sig_bbcode_bitfield'	=> (!empty($row['user_sig_bbcode_bitfield'])) ? $row['user_sig_bbcode_bitfield'] : '',

				'viewonline'	=> $row['user_allow_viewonline'],
				'allow_pm'		=> $row['user_allow_pm'],

				'avatar'		=> ($user->optionget('viewavatars')) ? get_user_avatar($row['user_avatar'], $row['user_avatar_type'], $row['user_avatar_width'], $row['user_avatar_height']) : '',
				'age'			=> '',

				'rank_title'		=> '',
				'rank_image'		=> '',
				'rank_image_src'	=> '',

				'username'			=> $row['username'],
				'user_colour'		=> $row['user_colour'],

				'online'		=> false,
				'profile'		=> append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=viewprofile&amp;u=$poster_id"),
				'www'			=> $row['user_website'],
				'aim'			=> ($row['user_aim'] && $auth->acl_get('u_sendim')) ? append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=contact&amp;action=aim&amp;u=$poster_id") : '',
				'msn'			=> ($row['user_msnm'] && $auth->acl_get('u_sendim')) ? append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=contact&amp;action=msnm&amp;u=$poster_id") : '',
				'yim'			=> ($row['user_yim']) ? 'http://edit.yahoo.com/config/send_webmesg?.target=' . urlencode($row['user_yim']) . '&amp;.src=pg' : '',
				'jabber'		=> ($row['user_jabber'] && $auth->acl_get('u_sendim')) ? append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=contact&amp;action=jabber&amp;u=$poster_id") : '',
				'search'		=> ($auth->acl_get('u_search')) ? append_sid("{$phpbb_root_path}search.$phpEx", 'search_author=' . urlencode($row['username']) .'&amp;showresults=posts') : '',
			);

			get_user_rank($row['user_rank'], $row['user_posts'], $user_cache[$poster_id]['rank_title'], $user_cache[$poster_id]['rank_image'], $user_cache[$poster_id]['rank_image_src']);

			if (!empty($row['user_allow_viewemail']) || $auth->acl_get('a_email'))
			{
				$user_cache[$poster_id]['email'] = ($config['board_email_form'] && $config['email_enable']) ? append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=email&amp;u=$poster_id") : (($config['board_hide_emails'] && !$auth->acl_get('a_email')) ? '' : 'mailto:' . $row['user_email']);
			}
			else
			{
				$user_cache[$poster_id]['email'] = '';
			}

			if (!empty($row['user_icq']))
			{
				$user_cache[$poster_id]['icq'] = 'http://www.icq.com/people/webmsg.php?to=' . $row['user_icq'];
				$user_cache[$poster_id]['icq_status_img'] = '<img src="http://web.icq.com/whitepages/online?icq=' . $row['user_icq'] . '&amp;img=5" width="18" height="18" alt="" />';
			}
			else
			{
				$user_cache[$poster_id]['icq_status_img'] = '';
				$user_cache[$poster_id]['icq'] = '';
			}

			if ($config['allow_birthdays'] && !empty($row['user_birthday']))
			{
				list($bday_day, $bday_month, $bday_year) = array_map('intval', explode('-', $row['user_birthday']));

				if ($bday_year)
				{
					$diff = $now['mon'] - $bday_month;
					if ($diff == 0)
					{
						$diff = ($now['mday'] - $bday_day < 0) ? 1 : 0;
					}
					else
					{
						$diff = ($diff < 0) ? 1 : 0;
					}

					$user_cache[$poster_id]['age'] = (int) ($now['year'] - $bday_year - $diff);
				}
			}
		}
	}
}
$db->sql_freeresult($result);

// Load custom profile fields
if ($config['load_cpf_viewtopic'])
{
	include($phpbb_root_path . 'includes/functions_profile_fields.' . $phpEx);
	$cp = new custom_profile();

	// Grab all profile fields from users in id cache for later use - similar to the poster cache
	$profile_fields_cache = $cp->generate_profile_fields_template('grab', $id_cache);
}

// Generate online information for user
if ($config['load_onlinetrack'] && sizeof($id_cache))
{
	$sql = 'SELECT session_user_id, MAX(session_time) as online_time, MIN(session_viewonline) AS viewonline
		FROM ' . SESSIONS_TABLE . '
		WHERE ' . $db->sql_in_set('session_user_id', $id_cache) . '
		GROUP BY session_user_id';
	$result = $db->sql_query($sql);

	$update_time = $config['load_online_time'] * 60;
	while ($row = $db->sql_fetchrow($result))
	{
		$user_cache[$row['session_user_id']]['online'] = (time() - $update_time < $row['online_time'] && (($row['viewonline']) || $auth->acl_get('u_viewonline'))) ? true : false;
	}
	$db->sql_freeresult($result);
}
unset($id_cache);

// Pull attachment data
if (sizeof($attach_list))
{
	if ($auth->acl_get('u_download') && $auth->acl_get('f_download', $forum_id))
	{
		$sql = 'SELECT *
			FROM ' . ATTACHMENTS_TABLE . '
			WHERE ' . $db->sql_in_set('post_msg_id', $attach_list) . '
				AND in_message = 0
			ORDER BY filetime DESC, post_msg_id ASC';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$attachments[$row['post_msg_id']][] = $row;
		}
		$db->sql_freeresult($result);

		// No attachments exist, but post table thinks they do so go ahead and reset post_attach flags
		if (!sizeof($attachments))
		{
			$sql = 'UPDATE ' . POSTS_TABLE . '
				SET post_attachment = 0
				WHERE ' . $db->sql_in_set('post_id', $attach_list);
			$db->sql_query($sql);

			// We need to update the topic indicator too if the complete topic is now without an attachment
			if (sizeof($rowset) != $total_posts)
			{
				// Not all posts are displayed so we query the db to find if there's any attachment for this topic
				$sql = 'SELECT a.post_msg_id as post_id
					FROM ' . ATTACHMENTS_TABLE . ' a, ' . POSTS_TABLE . " p
					WHERE p.topic_id = $topic_id
						AND p.post_approved = 1
						AND p.topic_id = a.topic_id";
				$result = $db->sql_query_limit($sql, 1);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);

				if (!$row)
				{
					$sql = 'UPDATE ' . TOPICS_TABLE . "
						SET topic_attachment = 0
						WHERE topic_id = $topic_id";
					$db->sql_query($sql);
				}
			}
			else
			{
				$sql = 'UPDATE ' . TOPICS_TABLE . "
					SET topic_attachment = 0
					WHERE topic_id = $topic_id";
				$db->sql_query($sql);
			}
		}
		else if ($has_attachments && !$topic_data['topic_attachment'])
		{
			// Topic has approved attachments but its flag is wrong
			$sql = 'UPDATE ' . TOPICS_TABLE . "
				SET topic_attachment = 1
				WHERE topic_id = $topic_id";
			$db->sql_query($sql);

			$topic_data['topic_attachment'] = 1;
		}
	}
	else
	{
		$display_notice = true;
	}
}

// Instantiate BBCode if need be
if ($bbcode_bitfield !== '')
{
	$bbcode = new bbcode(base64_encode($bbcode_bitfield));
}

$i_total = sizeof($rowset) - 1;
$prev_post_id = '';


// NUMERO DI POST
//$template->assign_vars(array(
//	'S_NUM_POSTS' => sizeof($post_list))
//);

// Output the posts
// Questo è uno dei for più lunghi che uomo abbia mai visto... :O

$listone = array();

$first_unread = $post_unread = false;
for ($i = 0, $end = sizeof($post_list); $i < $end; ++$i)
{
	// A non-existing rowset only happens if there was no user present for the entered poster_id
	// This could be a broken posts table.
	if (!isset($rowset[$post_list[$i]]))
	{
		continue;
	}

	$row =& $rowset[$post_list[$i]];
	$poster_id = $row['user_id'];

	// End signature parsing, only if needed
	if ($user_cache[$poster_id]['sig'] && $row['enable_sig'] && empty($user_cache[$poster_id]['sig_parsed']))
	{
		$user_cache[$poster_id]['sig'] = censor_text($user_cache[$poster_id]['sig']);

		if ($user_cache[$poster_id]['sig_bbcode_bitfield'])
		{
			$bbcode->bbcode_second_pass($user_cache[$poster_id]['sig'], $user_cache[$poster_id]['sig_bbcode_uid'], $user_cache[$poster_id]['sig_bbcode_bitfield']);
		}

		$user_cache[$poster_id]['sig'] = bbcode_nl2br($user_cache[$poster_id]['sig']);
		$user_cache[$poster_id]['sig'] = smiley_text($user_cache[$poster_id]['sig']);
		$user_cache[$poster_id]['sig_parsed'] = true;
	}

	// Parse the message and subject
	$message = censor_text($row['post_text']);

	// Second parse bbcode here
	if ($row['bbcode_bitfield'])
	{
		$bbcode->bbcode_second_pass($message, $row['bbcode_uid'], $row['bbcode_bitfield']);
	}

	$message = bbcode_nl2br($message);
	$message = smiley_text($message);

	if (!empty($attachments[$row['post_id']]))
	{
		parse_attachments($forum_id, $message, $attachments[$row['post_id']], $update_count);
	}

	// Replace naughty words such as farty pants
	$row['post_subject'] = censor_text($row['post_subject']);

	// Highlight active words (primarily for search)
	if ($highlight_match)
	{
		$message = preg_replace('#(?!<.*)(?<!\w)(' . $highlight_match . ')(?!\w|[^<>]*(?:</s(?:cript|tyle))?>)#is', '<span class="posthilit">\1</span>', $message);
		$row['post_subject'] = preg_replace('#(?!<.*)(?<!\w)(' . $highlight_match . ')(?!\w|[^<>]*(?:</s(?:cript|tyle))?>)#is', '<span class="posthilit">\1</span>', $row['post_subject']);
	}

	// Editing information
	if (($row['post_edit_count'] && $config['display_last_edited']) || $row['post_edit_reason'])
	{
		// Get usernames for all following posts if not already stored
		if (!sizeof($post_edit_list) && ($row['post_edit_reason'] || ($row['post_edit_user'] && !isset($user_cache[$row['post_edit_user']]))))
		{
			// Remove all post_ids already parsed (we do not have to check them)
			$post_storage_list = (!$store_reverse) ? array_slice($post_list, $i) : array_slice(array_reverse($post_list), $i);

			$sql = 'SELECT DISTINCT u.user_id, u.username, u.user_colour
				FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
				WHERE ' . $db->sql_in_set('p.post_id', $post_storage_list) . '
					AND p.post_edit_count <> 0
					AND p.post_edit_user <> 0
					AND p.post_edit_user = u.user_id';
			$result2 = $db->sql_query($sql);
			while ($user_edit_row = $db->sql_fetchrow($result2))
			{
				$post_edit_list[$user_edit_row['user_id']] = $user_edit_row;
			}
			$db->sql_freeresult($result2);

			unset($post_storage_list);
		}

		$l_edit_time_total = ($row['post_edit_count'] == 1) ? $user->lang['EDITED_TIME_TOTAL'] : $user->lang['EDITED_TIMES_TOTAL'];

		if ($row['post_edit_reason'])
		{
			// User having edited the post also being the post author?
			if (!$row['post_edit_user'] || $row['post_edit_user'] == $poster_id)
			{
				$display_username = get_username_string('full', $poster_id, $row['username'], $row['user_colour'], $row['post_username']);
			}
			else
			{
				$display_username = get_username_string('full', $row['post_edit_user'], $post_edit_list[$row['post_edit_user']]['username'], $post_edit_list[$row['post_edit_user']]['user_colour']);
			}

			$l_edited_by = sprintf($l_edit_time_total, $display_username, $user->format_date($row['post_edit_time']), $row['post_edit_count']);
		}
		else
		{
			if ($row['post_edit_user'] && !isset($user_cache[$row['post_edit_user']]))
			{
				$user_cache[$row['post_edit_user']] = $post_edit_list[$row['post_edit_user']];
			}

			// User having edited the post also being the post author?
			if (!$row['post_edit_user'] || $row['post_edit_user'] == $poster_id)
			{
				$display_username = get_username_string('full', $poster_id, $row['username'], $row['user_colour'], $row['post_username']);
			}
			else
			{
				$display_username = get_username_string('full', $row['post_edit_user'], $user_cache[$row['post_edit_user']]['username'], $user_cache[$row['post_edit_user']]['user_colour']);
			}

			$l_edited_by = sprintf($l_edit_time_total, $display_username, $user->format_date($row['post_edit_time']), $row['post_edit_count']);
		}
	}
	else
	{
		$l_edited_by = '';
	}

	// Bump information
	if ($topic_data['topic_bumped'] && $row['post_id'] == $topic_data['topic_last_post_id'] && isset($user_cache[$topic_data['topic_bumper']]) )
	{
		// It is safe to grab the username from the user cache array, we are at the last
		// post and only the topic poster and last poster are allowed to bump.
		// Admins and mods are bound to the above rules too...
		$l_bumped_by = '<br /><br />' . sprintf($user->lang['BUMPED_BY'], $user_cache[$topic_data['topic_bumper']]['username'], $user->format_date($topic_data['topic_last_post_time']));
	}
	else
	{
		$l_bumped_by = '';
	}

	$cp_row = array();

	//
	if ($config['load_cpf_viewtopic'])
	{
		$cp_row = (isset($profile_fields_cache[$poster_id])) ? $cp->generate_profile_fields_template('show', false, $profile_fields_cache[$poster_id]) : array();
	}

	$post_unread = (isset($topic_tracking_info[$topic_id]) && $row['post_time'] > $topic_tracking_info[$topic_id]) ? true : false;

	$s_first_unread = false;
	if (!$first_unread && $post_unread)
	{
		$s_first_unread = $first_unread = true;
	}

	
	//
        $u_sign = ($row['enable_sig']) ? $user_cache[$poster_id]['sig'] : '';
        $postrow = array('post_time' => $row['post_time'],
                         'post_text' => $message,
                         'post_username' => get_username_string('username', $poster_id, $row['username'], $row['user_colour'], $row['post_username']),
                         'poster_id' => $poster_id,
                         'post_id' => $row['post_id'],
                         'forum_id' => $forum_id,
                         'topic_id' => $topic_id,
                         'poster_avatar' => $user_cache[$poster_id]['avatar'],
                         'poster_joined' => $user_cache[$poster_id]['joined'],
                         'poster_posts' => $user_cache[$poster_id]['posts'],
                         'signature' => $u_sign,
                         'post_subject' => $row['post_subject']);
	/*$postrow = array(
		'POST_AUTHOR_FULL'		=> get_username_string('full', $poster_id, $row['username'], $row['user_colour'], $row['post_username']),
		'POST_AUTHOR_COLOUR'	=> get_username_string('colour', $poster_id, $row['username'], $row['user_colour'], $row['post_username']),
		'POST_AUTHOR'			=> get_username_string('username', $poster_id, $row['username'], $row['user_colour'], $row['post_username']),
		'U_POST_AUTHOR'			=> get_username_string('profile', $poster_id, $row['username'], $row['user_colour'], $row['post_username']),

		'RANK_TITLE'		=> $user_cache[$poster_id]['rank_title'],
		'RANK_IMG'			=> $user_cache[$poster_id]['rank_image'],
		'RANK_IMG_SRC'		=> $user_cache[$poster_id]['rank_image_src'],
		'POSTER_JOINED'		=> $user_cache[$poster_id]['joined'],
		'POSTER_POSTS'		=> $user_cache[$poster_id]['posts'],
		'POSTER_FROM'		=> $user_cache[$poster_id]['from'],
		'POSTER_AVATAR'		=> $user_cache[$poster_id]['avatar'],
		'POSTER_WARNINGS'	=> $user_cache[$poster_id]['warnings'],
		'POSTER_AGE'		=> $user_cache[$poster_id]['age'],

		'POST_DATE'			=> $row['post_time'],
		'POST_SUBJECT'		=> $row['post_subject'],
		'MESSAGE'			=> $message,
		'SIGNATURE'			=> ($row['enable_sig']) ? $user_cache[$poster_id]['sig'] : '',
		'EDITED_MESSAGE'	=> $l_edited_by,
		'EDIT_REASON'		=> $row['post_edit_reason'],
		'BUMPED_MESSAGE'	=> $l_bumped_by,

		'MINI_POST_IMG'			=> ($post_unread) ? $user->img('icon_post_target_unread', 'NEW_POST') : $user->img('icon_post_target', 'POST'),
		'POST_ICON_IMG'			=> ($topic_data['enable_icons'] && !empty($row['icon_id'])) ? $icons[$row['icon_id']]['img'] : '',
		'POST_ICON_IMG_WIDTH'	=> ($topic_data['enable_icons'] && !empty($row['icon_id'])) ? $icons[$row['icon_id']]['width'] : '',
		'POST_ICON_IMG_HEIGHT'	=> ($topic_data['enable_icons'] && !empty($row['icon_id'])) ? $icons[$row['icon_id']]['height'] : '',
		'ICQ_STATUS_IMG'		=> $user_cache[$poster_id]['icq_status_img'],
		'ONLINE_IMG'			=> ($poster_id == ANONYMOUS || !$config['load_onlinetrack']) ? '' : (($user_cache[$poster_id]['online']) ? $user->img('icon_user_online', 'ONLINE') : $user->img('icon_user_offline', 'OFFLINE')),
		'S_ONLINE'				=> ($poster_id == ANONYMOUS || !$config['load_onlinetrack']) ? false : (($user_cache[$poster_id]['online']) ? true : false),

		'U_EDIT'			=> (!$user->data['is_registered']) ? '' : ((($user->data['user_id'] == $poster_id && $auth->acl_get('f_edit', $forum_id) && ($row['post_time'] > time() - ($config['edit_time'] * 60) || !$config['edit_time'])) || $auth->acl_get('m_edit', $forum_id)) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=edit&amp;f=$forum_id&amp;p={$row['post_id']}") : ''),
		'U_QUOTE'			=> ($auth->acl_get('f_reply', $forum_id)) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=quote&amp;f=$forum_id&amp;p={$row['post_id']}") : '',
		'U_INFO'			=> ($auth->acl_get('m_info', $forum_id)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", "i=main&amp;mode=post_details&amp;f=$forum_id&amp;p=" . $row['post_id'], true, $user->session_id) : '',
		'U_DELETE'			=> (!$user->data['is_registered']) ? '' : ((($user->data['user_id'] == $poster_id && $auth->acl_get('f_delete', $forum_id) && $topic_data['topic_last_post_id'] == $row['post_id'] && ($row['post_time'] > time() - ($config['edit_time'] * 60) || !$config['edit_time'])) || $auth->acl_get('m_delete', $forum_id)) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=delete&amp;f=$forum_id&amp;p={$row['post_id']}") : ''),

		'U_PROFILE'		=> $user_cache[$poster_id]['profile'],
		'U_SEARCH'		=> $user_cache[$poster_id]['search'],
		'U_PM'			=> ($poster_id != ANONYMOUS && $config['allow_privmsg'] && $auth->acl_get('u_sendpm') && ($user_cache[$poster_id]['allow_pm'] || $auth->acl_gets('a_', 'm_') || $auth->acl_getf_global('m_'))) ? append_sid("{$phpbb_root_path}ucp.$phpEx", 'i=pm&amp;mode=compose&amp;action=quotepost&amp;p=' . $row['post_id']) : '',
		'U_EMAIL'		=> $user_cache[$poster_id]['email'],
		'U_WWW'			=> $user_cache[$poster_id]['www'],
		'U_ICQ'			=> $user_cache[$poster_id]['icq'],
		'U_AIM'			=> $user_cache[$poster_id]['aim'],
		'U_MSN'			=> $user_cache[$poster_id]['msn'],
		'U_YIM'			=> $user_cache[$poster_id]['yim'],
		'U_JABBER'		=> $user_cache[$poster_id]['jabber'],

		'U_REPORT'			=> ($auth->acl_get('f_report', $forum_id)) ? append_sid("{$phpbb_root_path}report.$phpEx", 'f=' . $forum_id . '&amp;p=' . $row['post_id']) : '',
		'U_MCP_REPORT'		=> ($auth->acl_get('m_report', $forum_id)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=reports&amp;mode=report_details&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $user->session_id) : '',
		'U_MCP_APPROVE'		=> ($auth->acl_get('m_approve', $forum_id)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=queue&amp;mode=approve_details&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $user->session_id) : '',
		'U_MINI_POST'		=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'p=' . $row['post_id']) . (($topic_data['topic_type'] == POST_GLOBAL) ? '&amp;f=' . $forum_id : '') . '#p' . $row['post_id'],
		'U_NEXT_POST_ID'	=> ($i < $i_total && isset($rowset[$post_list[$i + 1]])) ? $rowset[$post_list[$i + 1]]['post_id'] : '',
		'U_PREV_POST_ID'	=> $prev_post_id,
		'U_NOTES'			=> ($auth->acl_getf_global('m_')) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=notes&amp;mode=user_notes&amp;u=' . $poster_id, true, $user->session_id) : '',
		'U_WARN'			=> ($auth->acl_get('m_warn') && $poster_id != $user->data['user_id'] && $poster_id != ANONYMOUS) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=warn&amp;mode=warn_post&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $user->session_id) : '',

		'POST_ID'			=> $row['post_id'],
		'POSTER_ID'			=> $poster_id,

		'S_HAS_ATTACHMENTS'	=> (!empty($attachments[$row['post_id']])) ? true : false,
		'S_POST_UNAPPROVED'	=> ($row['post_approved']) ? false : true,
		'S_POST_REPORTED'	=> ($row['post_reported'] && $auth->acl_get('m_report', $forum_id)) ? true : false,
		'S_DISPLAY_NOTICE'	=> $display_notice && $row['post_attachment'],
		'S_FRIEND'			=> ($row['friend']) ? true : false,
		'S_UNREAD_POST'		=> $post_unread,
		'S_FIRST_UNREAD'	=> $s_first_unread,
		'S_CUSTOM_FIELDS'	=> (isset($cp_row['row']) && sizeof($cp_row['row'])) ? true : false,
		'S_TOPIC_POSTER'	=> ($topic_data['topic_poster'] == $poster_id) ? true : false,

		'S_IGNORE_POST'		=> ($row['hide_post']) ? true : false,
		'L_IGNORE_POST'		=> ($row['hide_post']) ? sprintf($user->lang['POST_BY_FOE'], get_username_string('full', $poster_id, $row['username'], $row['user_colour'], $row['post_username']), '<a href="' . $viewtopic_url . "&amp;p={$row['post_id']}&amp;view=show#p{$row['post_id']}" . '">', '</a>') : '',
	);*/

	if (isset($cp_row['row']) && sizeof($cp_row['row']))
	{
		$postrow = array_merge($postrow, $cp_row['row']);
	}

	// Dump vars into template
	//$template->assign_block_vars('postrow', $postrow);

	if (!empty($cp_row['blockrow']))
	{
		foreach ($cp_row['blockrow'] as $field_data)
		{
			//$template->assign_block_vars('postrow.custom_fields', $field_data);
		}
	}

	// Display not already displayed Attachments for this post, we already parsed them. ;)
	if (!empty($attachments[$row['post_id']]))
	{
		foreach ($attachments[$row['post_id']] as $attachment)
		{
			//$template->assign_block_vars('postrow.attachment', array(
			//	'DISPLAY_ATTACHMENT'	=> $attachment)
			//);
		}
	}
	
	// Aggiunge l'elemento alla lista dei messaggi da restituire
	$listone[] = $postrow;
	

	$prev_post_id = $row['post_id'];

	unset($rowset[$post_list[$i]]);
	unset($attachments[$row['post_id']]);
}
unset($rowset, $user_cache);

// Update topic view and if necessary attachment view counters ... but only if this is the first 'page view'
if (isset($user->data['session_page']) && strpos($user->data['session_page'], '&t=' . $topic_id) === false)
{
	$sql = 'UPDATE ' . TOPICS_TABLE . '
		SET topic_views = topic_views + 1, topic_last_view_time = ' . time() . "
		WHERE topic_id = $topic_id";
	$db->sql_query($sql);

	// Update the attachment download counts
	if (sizeof($update_count))
	{
		$sql = 'UPDATE ' . ATTACHMENTS_TABLE . '
			SET download_count = download_count + 1
			WHERE ' . $db->sql_in_set('attach_id', array_unique($update_count));
		$db->sql_query($sql);
	}
}

// Only mark topic if it's currently unread. Also make sure we do not set topic tracking back if earlier pages are viewed.
if (isset($topic_tracking_info[$topic_id]) && $topic_data['topic_last_post_time'] > $topic_tracking_info[$topic_id] && $max_post_time > $topic_tracking_info[$topic_id])
{
	markread('topic', $forum_id, $topic_id, $max_post_time);

	// Update forum info
	$all_marked_read = update_forum_tracking_info($forum_id, $topic_data['forum_last_post_time'], (isset($topic_data['forum_mark_time'])) ? $topic_data['forum_mark_time'] : false, false);
}
else
{
	$all_marked_read = true;
}

// If there are absolutely no more unread posts in this forum and unread posts shown, we can savely show the #unread link
if ($all_marked_read)
{
	if ($post_unread)
	{
		//$template->assign_vars(array(
		//	'U_VIEW_UNREAD_POST'	=> '#unread',
		//));
	}
	else if (isset($topic_tracking_info[$topic_id]) && $topic_data['topic_last_post_time'] > $topic_tracking_info[$topic_id])
	{
		//$template->assign_vars(array(
		//	'U_VIEW_UNREAD_POST'	=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;view=unread") . '#unread',
		//));
	}
}
else if (!$all_marked_read)
{
	$last_page = ((floor($start / $config['posts_per_page']) + 1) == max(ceil($total_posts / $config['posts_per_page']), 1)) ? true : false;

	// What can happen is that we are at the last displayed page. If so, we also display the #unread link based in $post_unread
	if ($last_page && $post_unread)
	{
		//$template->assign_vars(array(
		//	'U_VIEW_UNREAD_POST'	=> '#unread',
		//));
	}
	else if (!$last_page)
	{
		//$template->assign_vars(array(
		//	'U_VIEW_UNREAD_POST'	=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;view=unread") . '#unread',
		//));
	}
}
	
	$numberOfPages = ceil($total_posts/$itemsPerPage);
	
	// MA STA FUNZIONE COME CAZZO FUNZIONA? DOVREBBE RITORNARE UN SOLO MESSAGGIO SE SPECIFICHI
	// IL POST_ID MA RITORNA SEMPRE TUTTO
	$block = array( 'current_page' => $pageToShow,
		      'pages_number' => $numberOfPages,
		      'topic_infos' => $informazioni,
		      'topic_messages' => $listone);
	return $block;
}


/**
* This method return a specif topic information.
* <br/><b>PARAMETERS:</b><br/>
* - <b>$forum_id</b> 	<i>[integer]</i> 	Forum id of target topic
* - <b>$topic_id</b> 	<i>[integer]</i> 	Target topic id
* - <b>$password</b> 	<i>[string]</i> 	Password if the topic is password protected (leave it '' if no password is required)
* - <b>logging_date</b> <i>[integer]</i> 	Reference date to get the first new page/new post id (since last visit specified by logging_date)
* - <b>$postsPerPage</b>    <i>[integer]</i>            number of posts per page
*
* @since 1.1
* @see THIS FUNCTION DON'T REQUIRE LOGIN
* @return array  see <i>getTopicsFromForum</i> method to read the result format
*/
                                         
function getTopicWithID($args) {
        $forum_id = $args[0];
        $topic_id = $args[1];
        $password = $args[2];
        $logging_date = $args[3];
        $postsPerPage = $args[4];
     return getTopicsFromForum(array($forum_id,0,10,$postsPerPage,$password,$topic_id,$logging_date));
}

/**
* Get new posts/topics since a gived period. This method return paginated results, so you can display huge data in separated blocks.<br/>
* You need to login before using this method if you want to get items since your last login.<br/>
* You need to provide 4 parameters inside the $args array.<br/>
* Sorting options are taken from users options, when not logged it will take standard config board options.<br/>
* To create a new account you need to browse the board using a browser.<br/>
* - [integer] 	<b>$forum_id</b> 			the id of the forum you want to list<br/>
* - [integer] 	<b>$pageIdx</b> 			the number of page you want to see (default is 0)<br/>
* - [integer] 	<b>$topicsPerPage</b> 		number of topics per page (default is 30)<br/>
* - [integer]   <b>$postsPerPage</b>            number of posts per page
* - [string]	<b>$forumPassword</b> 		you need to specify a password if the forum is protected<br/>
* - [string] 	<b>$specificTopic</b> 		specify a topic id to get only it (leave it null to get all forum's topics)
* - [integer] 	<b>$logging_date</b> 		specify user's logging date to get correct values for results key 'first_new_page','first_new_postid'
*                                  			(these values indicate first new post id, and first new post into the topic since specified date). Leave -1 to ignore.
* @since 1.0
* @version 1.0
* @return integer   <b>ERRORS:</b><br>
*                   --------------<br> 
*                   <b>MSG_FORUM_ERRNOFORUM</b> 			you have not specified a forum id<br/>
*                   <b>MSG_FORUM_ERR_UNEXISTINGFORUM</b> 	given forum id does not exist<br/>
*                   <b>MSG_FORUM_ERR_LOGINREQUIRED</b> 		login is required to show this forum<br/>
*                   <b>MSG_FORUM_ERR_CANNOTREAD</b> 		you have not enough privilegies to show this forum</br>
*                   <b>MSG_FORUM_ERR_FORUMPASSWORDED</b> 	forum is passworded you need to recall this function with pass
*                   <br>
*                   <b>VALID RESULTS:</b><br>
*                   --------------<br>
*                   If the forum is a link it will return a simple string with the link
*                   Otherwise this function return an array with two keys<br />
*                   - <b>'forum_info'</b> 	[array] 	contains some infos about choosed forum with these keys:<br /><br/>
*
*						Keys:<br/>
*                       <b>'total_topics'</b> 		number of topics into the forum (total topics includes other pages)<br />
*                       <b>'pages_number'</b> 		number of pages<br />
*                       <b>'page_showed'</b> 		number of page showed<br />
*                       <b>'forum_id'</b> 			id of showed forum<br />
*                       <b>'parent_id'</b> 			id of parent forum (container)<br />
*                       <b>'forum_name'</b> 		name of showed forum<br />
*                       <b>'forum_desc'</b> 		description of the forum<br />
*                       <b>'forum_image'</b> 		icon image of the forum<br />
*                       <b>'forum_newtopics_sincedate'</b>  number of new topics created since given ref date
*                       <b>'forum_updatedtopics_sincedate'</b> number of updated topics since given ref date
*                   <br />
*
*                   - <b>'topics'</b> 		[array] 	list of displayed topics. Each row is an object with these keys:<br />
*						Keys:<br/>
*                       <b>'bookmarked'</b> 				is topic bookmarked by logged user? (boolean) (anonymous is obvisiuly false)
*                       <b>'topic_id'</b> 					id of the topic<br />
*                       <b>'topic_title'</b> 				title of the topic<br />
*                       <b>'topic_poster'</b> 				id of the author<br />
*                       <b>'topic_time'</b> 				date of creation in unix format<br />
*                       <b>'topic_views'</b> 				number of views<br />
*                       <b>'topic_replies'</b> 				number of replies to topic<br />
*                       <b>'topic_status'</b> 				status of topic<br />
*                       <b>'topic_first_post_id'</b> 		id of the first message of the topic<br />
*                       <b>'topic_first_poster_name'</b>	name of the author of first message of the topic<br />
*                       <b>'topic_last_post_id'</b> 		id of the last post in topic<br />
*                       <b>'topic_last_poster_name'</b> 	name of the author of last reply on topic<br />
*                       <b>'topic_last_post_time'</b> 		send date in unix format of last reply<br />
*                       <b>'topic_last_view_time'</b> 		the time when last people has visited the topic in unix format<br />
*                       <b>'first_new_page'</b> 		the first page you need to load the first new message (IF YOU HAVE SPECIFIED $logging_date)<br/>
*                       <b>'first_new_postid'</b> 		this the id of the first unread new post from the topic (IF YOU HAVE SPECIFIED $logging_date)<br/>           
*/

function getTopicsFromForum($args) {
    global $user,$db,$config,$auth;
    
    // setup data parameters
    $forum_id = $args[0];
    $pageIdx = $args[1]; if (!isset($pageIdx)) $pageIdx = 0;
    $topicsPerPage = $args[2]; if (!isset($topicsPerPage)) $topicsPerPage = 30;
    $postsPerPage = $args[3]; if (!isset($postsPerPage)) $postsPerPage = 25;
    $password = $args[4];
    $specificTopic = $args[5];
    $logging_date = $args[6];
    
    $forumPassword = $password;
    $_config_sortKey = 't'; //$args[4]; // optional (default is 't' for time)
    $_config_sortDays = null; //$args[5]; // optional (default is taken from user data)
    $_config_sortDirection = 'd';//$args[6]; // optional (default is 'a' for ascending)

    // setup data for currently logged user
    $auth->acl($user->data);
    
    // Start initial var setup
    $mark_read	= ''; //request_var('mark', '');
   // $start	= $pageIdx; //request_var('start', 0);
    $start = ($pageIdx)*$topicsPerPage;

    // start session managment
    $auth->acl($user->data);
    
    // if specified we take the param, otherwise we will search for user settings, otherwise will be 0
    $sort_days = (isset($_config_sortDays) ? $_config_sortDays :
                        ((!empty($user->data['user_post_show_days'])) ? $user->data['user_post_show_days'] : 0));  
    $sort_key = (isset($_config_sortKey) ? $_config_sortKey :
                        ((!empty($user->data['user_post_sortby_type'])) ? $user->data['user_post_sortby_type'] : 't'));
    $sort_dir = (isset($_config_sortDirection) ? $_config_sortDirection :
                        ((!empty($user->data['user_post_sortby_dir'])) ? $user->data['user_post_sortby_dir'] : 'a'));

    
    // get some ordering options from user configuration
   // $sort_days	= request_var('st', ((!empty($user->data['user_topic_show_days'])) ? $user->data['user_topic_show_days'] : 0));
   // $sort_key	= request_var('sk', ((!empty($user->data['user_topic_sortby_type'])) ? $user->data['user_topic_sortby_type'] : 't'));
   // $sort_dir	= request_var('sd', ((!empty($user->data['user_topic_sortby_dir'])) ? $user->data['user_topic_sortby_dir'] : 'd'));

    // Check if the user has actually sent a forum ID with his/her request
    // If not give them an error message
    if (!$forum_id) return MSG_FORUM_ERRNOFORUM;

    $sql_from = FORUMS_TABLE . ' f';
    $lastread_select = '';

    // Grab appropriate forum data
    if ($config['load_db_lastread'] && $user->data['is_registered']) {
	$sql_from .= ' LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.user_id = ' . $user->data['user_id'] . '
		AND ft.forum_id = f.forum_id)';
	$lastread_select .= ', ft.mark_time';
    }
    
    // user is registered on board
    if ($user->data['is_registered']) {
	$sql_from .= ' LEFT JOIN ' . FORUMS_WATCH_TABLE . ' fw ON (fw.forum_id = f.forum_id AND fw.user_id = ' . $user->data['user_id'] . ')';
	$lastread_select .= ', fw.notify_status';
    }
    
    $sql = "SELECT f.* $lastread_select
	FROM $sql_from
	WHERE f.forum_id = $forum_id";
    $result = $db->sql_query($sql);
    $forum_data = $db->sql_fetchrow($result);  
    $db->sql_freeresult($result);
    
    // choosed forum_id does not exist
    if (!$forum_data) return MSG_FORUM_ERR_UNEXISTINGFORUM;
    
    // Configure style, language, etc.
    $user->setup('viewforum', $forum_data['forum_style']);

    // Redirect to login upon emailed notification links
    if (isset($_GET['e']) && !$user->data['is_registered'])
	return MSG_FORUM_ERR_LOGINREQUIRED;
    
    // Permissions check, can we read this forum?
    if (!$auth->acl_gets('f_list', 'f_read', $forum_id) || ($forum_data['forum_type'] == FORUM_LINK && $forum_data['forum_link'] && !$auth->acl_get('f_read', $forum_id))) {
	if ($user->data['user_id'] != ANONYMOUS)
            return MSG_FORUM_ERR_CANNOTREAD; // you cannot read this forum
        return MSG_FORUM_ERR_LOGINREQUIRED; // you need to login before
    }
    
    // Forum is passworded ... check whether access has been granted to this
    // user this session, if not show login box
    if ($forum_data['forum_password']) {
        if (!isset($password)) return MSG_FORUM_ERR_FORUMPASSWORDED; // forum is passworded, you have not given it
        else {
            $returnLogin = loginProtectedForumWithPassword($forum_id,$password);
            switch ($returnLogin) {
                case LOGIN_PSWD_FORUM_OK:
                    return getTopicsFromForum($args); // we call it again but we will password ok!
                    break;
                case LOGIN_PSWD_FORUM_ALREADYLOGGED:
                    // nothing to do
                    break;
                case LOGIN_PSWD_FORUM_WRONGFORUMID:
                case LOGIN_PSWD_FORUM_ERR:
                    return MSG_FORUM_ERR_FORUMPASSWORDED;
                    break;
                    // wrong password
            }
        }
    }
    // Is this forum a link? ... User got here either because the
    // number of clicks is being tracked or they guessed the id
    if ($forum_data['forum_type'] == FORUM_LINK && $forum_data['forum_link']) {
	// Does it have click tracking enabled?
        // bah??? we keep it to mantain compatibility checks
	if ($forum_data['forum_flags'] & FORUM_FLAG_LINK_TRACK) {
		$sql = 'UPDATE ' . FORUMS_TABLE . '
			SET forum_posts = forum_posts + 1
			WHERE forum_id = ' . $forum_id;
		$db->sql_query($sql);
	}
        // return a string with the link //redirect($forum_data['forum_link']);
        return $forum_data['forum_link'];    
    }
    
    // we can perform our query
    // Topic ordering options
    $limit_days = array(0 => $user->lang['ALL_TOPICS'], 1 => $user->lang['1_DAY'], 7 => $user->lang['7_DAYS'], 14 => $user->lang['2_WEEKS'], 30 => $user->lang['1_MONTH'], 90 => $user->lang['3_MONTHS'], 180 => $user->lang['6_MONTHS'], 365 => $user->lang['1_YEAR']);

    $sort_by_text = array('a' => $user->lang['AUTHOR'], 't' => $user->lang['POST_TIME'], 'r' => $user->lang['REPLIES'], 's' => $user->lang['SUBJECT'], 'v' => $user->lang['VIEWS']);
    $sort_by_sql = array('a' => 't.topic_first_poster_name', 't' => 't.topic_last_post_time', 'r' => 't.topic_replies', 's' => 't.topic_title', 'v' => 't.topic_views');

    $s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
   // gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

    // Limit topics to certain time frame, obtain correct topic count
    // global announcements must not be counted, normal announcements have to
    // be counted, as forum_topics(_real) includes them
    
    // MIA!
    $sql_specificTopicID = '';
    if ($specificTopic) $sql_specificTopicID = ' AND topic_id = '.$specificTopic.' ';
    
    if ($sort_days) {
	$min_post_time = time() - ($sort_days * 86400);

	$sql = 'SELECT COUNT(topic_id) AS num_topics
		FROM ' . TOPICS_TABLE . "
		WHERE forum_id = $forum_id $sql_specificTopicID
			AND ((topic_type <> " . POST_GLOBAL . " AND topic_last_post_time >= $min_post_time)
				OR topic_type = " . POST_ANNOUNCE . ")
		" . (($auth->acl_get('m_approve', $forum_id)) ? '' : 'AND topic_approved = 1');
	$result = $db->sql_query($sql);
	$topics_count = (int) $db->sql_fetchfield('num_topics');
	$db->sql_freeresult($result);

	$sql_limit_time = "AND t.topic_last_post_time >= $min_post_time";
    } else {
	$topics_count = ($auth->acl_get('m_approve', $forum_id)) ? $forum_data['forum_topics_real'] : $forum_data['forum_topics'];
	$sql_limit_time = '';
    }


    // Make sure $start is set to the last page if it exceeds the amount
    if ($start < 0 || $start > $topics_count)
	$start = ($start < 0) ? 0 : floor(($topics_count - 1) / $topicsPerPage) * $topicsPerPage;

    // Grab all topic data
    $rowset = $announcement_list = $topic_list = $global_announce_list = array();

    $sql_array = array(
	'SELECT'	=> 't.*',
	'FROM'		=> array(
		TOPICS_TABLE		=> 't'
	),
	'LEFT_JOIN'	=> array(),
    );

    $sql_approved = ($auth->acl_get('m_approve', $forum_id)) ? '' : 'AND t.topic_approved = 1';

    if ($user->data['is_registered']) {
	if ($config['load_db_track']) {
		$sql_array['LEFT_JOIN'][] = array('FROM' => array(TOPICS_POSTED_TABLE => 'tp'), 'ON' => 'tp.topic_id = t.topic_id AND tp.user_id = ' . $user->data['user_id']);
		$sql_array['SELECT'] .= ', tp.topic_posted';
	}

	if ($config['load_db_lastread'])
	{
		$sql_array['LEFT_JOIN'][] = array('FROM' => array(TOPICS_TRACK_TABLE => 'tt'), 'ON' => 'tt.topic_id = t.topic_id AND tt.user_id = ' . $user->data['user_id']);
		$sql_array['SELECT'] .= ', tt.mark_time';

		if ($s_display_active && sizeof($active_forum_ary)) {
			$sql_array['LEFT_JOIN'][] = array('FROM' => array(FORUMS_TRACK_TABLE => 'ft'), 'ON' => 'ft.forum_id = t.forum_id AND ft.user_id = ' . $user->data['user_id']);
			$sql_array['SELECT'] .= ', ft.mark_time AS forum_mark_time';
		}
	}
    }

    if ($forum_data['forum_type'] == FORUM_POST) {
	// Obtain announcements ... removed sort ordering, sort by time in all cases
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> $sql_array['SELECT'],
		'FROM'		=> $sql_array['FROM'],
		'LEFT_JOIN'	=> $sql_array['LEFT_JOIN'],

		'WHERE'		=> 't.forum_id IN (' . $forum_id . ', 0)
			AND t.topic_type IN (' . POST_ANNOUNCE . ', ' . POST_GLOBAL . ')',

		'ORDER_BY'	=> 't.topic_time DESC',
	));
	$result = $db->sql_query($sql);

	while ($row = $db->sql_fetchrow($result)) {
	    $rowset[$row['topic_id']] = $row;
	    $announcement_list[] = $row['topic_id'];

	    if ($row['topic_type'] == POST_GLOBAL) {
	        $global_announce_list[$row['topic_id']] = true;
	    } else {
	        $topics_count--;
	    }
	}
	$db->sql_freeresult($result);
    }


    // If the user is trying to reach late pages, start searching from the end
    $store_reverse = false;
    $sql_limit = $topicsPerPage;//$config['topics_per_page'];
    if ($start > $topics_count / 2) {
	$store_reverse = true;

	//if ($start + $config['topics_per_page'] > $topics_count)
	if ($start + $topicsPerPage > $topics_count) {
		$sql_limit = min($topicsPerPage, max(1, $topics_count - $start));
	}

	// Select the sort order
	$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'ASC' : 'DESC');
	$sql_start = max(0, $topics_count - $sql_limit - $start);
    } else {
	// Select the sort order
	$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');
	$sql_start = $start;
    }

    if ($forum_data['forum_type'] == FORUM_POST || !sizeof($active_forum_ary)) {
	$sql_where = 't.forum_id = ' . $forum_id;
    } else if (empty($active_forum_ary['exclude_forum_id'])) {
	$sql_where = $db->sql_in_set('t.forum_id', $active_forum_ary['forum_id']);
    } else {
	$get_forum_ids = array_diff($active_forum_ary['forum_id'], $active_forum_ary['exclude_forum_id']);
	$sql_where = (sizeof($get_forum_ids)) ? $db->sql_in_set('t.forum_id', $get_forum_ids) : 't.forum_id = ' . $forum_id;
    }

    // Grab just the sorted topic ids
    $sql = 'SELECT t.topic_id
	FROM ' . TOPICS_TABLE . " t
	WHERE $sql_where
		AND t.topic_type IN (" . POST_NORMAL . ', ' . POST_STICKY . ', '. POST_GLOBAL.', '.POST_ANNOUNCE.") $sql_specificTopicID
		$sql_approved
		$sql_limit_time
	ORDER BY t.topic_type " . ((!$store_reverse) ? 'DESC' : 'ASC') . ', ' . $sql_sort_order;
    $result = $db->sql_query_limit($sql, $sql_limit, $sql_start);

    while ($row = $db->sql_fetchrow($result)) {
	$topic_list[] = (int) $row['topic_id'];
    }
    $db->sql_freeresult($result);

    // For storing shadow topics
    $shadow_topic_list = array();

    if (sizeof($topic_list)) {
	// SQL array for obtaining topics/stickies
	$sql_array = array(
		'SELECT'		=> $sql_array['SELECT'],
		'FROM'			=> $sql_array['FROM'],
		'LEFT_JOIN'		=> $sql_array['LEFT_JOIN'],

		'WHERE'			=> $db->sql_in_set('t.topic_id', $topic_list),
	);

	// If store_reverse, then first obtain topics, then stickies, else the other way around...
	// Funnily enough you typically save one query if going from the last page to the middle (store_reverse) because
	// the number of stickies are not known
	$sql = $db->sql_build_query('SELECT', $sql_array);
	$result = $db->sql_query($sql);

	while ($row = $db->sql_fetchrow($result))
	{
		if ($row['topic_status'] == ITEM_MOVED)
		{
			$shadow_topic_list[$row['topic_moved_id']] = $row['topic_id'];
		}

		$rowset[$row['topic_id']] = $row;
	}
	$db->sql_freeresult($result);
    }

    // If we have some shadow topics, update the rowset to reflect their topic information
    if (sizeof($shadow_topic_list)) {
	$sql = 'SELECT *
		FROM ' . TOPICS_TABLE . '
		WHERE ' . $db->sql_in_set('topic_id', array_keys($shadow_topic_list));
	$result = $db->sql_query($sql);

	while ($row = $db->sql_fetchrow($result)) {
		$orig_topic_id = $shadow_topic_list[$row['topic_id']];

		// If the shadow topic is already listed within the rowset (happens for active topics for example), then do not include it...
		if (isset($rowset[$row['topic_id']])) {
			// We need to remove any trace regarding this topic. :)
			unset($rowset[$orig_topic_id]);
			unset($topic_list[array_search($orig_topic_id, $topic_list)]);
			$topics_count--;
			continue;
		}

		// Do not include those topics the user has no permission to access
		if (!$auth->acl_get('f_read', $row['forum_id'])) {
		    // We need to remove any trace regarding this topic. :)
		    unset($rowset[$orig_topic_id]);
		    unset($topic_list[array_search($orig_topic_id, $topic_list)]);
		    $topics_count--;

		    continue;
		}

		// We want to retain some values
		$row = array_merge($row, array(
			'topic_moved_id'	=> $rowset[$orig_topic_id]['topic_moved_id'],
			'topic_status'		=> $rowset[$orig_topic_id]['topic_status'],
			'topic_type'		=> $rowset[$orig_topic_id]['topic_type'],
		));

		$rowset[$orig_topic_id] = $row;
	}
	$db->sql_freeresult($result);
    }
    unset($shadow_topic_list);
        
    //if ($specificTopic == null)
        // we will add announcements and stickies
         //   $topic_list = ($store_reverse) ? array_merge($announcement_list, array_reverse($topic_list)) : array_merge($announcement_list, $topic_list);
      // removed due to some problems with duplicates...
      
    $topic_tracking_info = $tracking_topics = array();
    
    $list = array();
    // NOW WE HAVE OUR LIST...
    foreach ($topic_list as $topic_id) {
        $row = &$rowset[$topic_id];
        // check if the topic is bookmarked
        $isBookmarked = isTopicIDBookmarked(array($topic_id));
        $newPage = getPageToLookForPostsAfterDate(array($row['topic_id'],$logging_date,$postsPerPage));
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
                        'bookmarked' => $isBookmarked,
                        'first_new_page' => $newPage,
                        'first_new_postid' => $newPostID);
        $list[] = $cTopic;
    }
    
    $newTopics = countNewTopicsAfterADate(array($forum_id,$logging_date));
    $updatedTopics = countUpdatedTopicsAfterADate(array($forum_id,$logging_date));
    
    // collect forum info
    $forum_info = array('total_topics' => $topics_count,
             'pages_number' => ceil($topics_count / $topicsPerPage),
             'page_showed'  => $pageIdx,
             'forum_id'     => $forum_id,
             'parent_id'    => $forum_data['parent_id'],
             'forum_desc'   => $forum_data['forum_desc'],
             'forum_image'  => $forum_data['forum_image'],
             'forum_type'   => $forum_data['forum_type'],
             'forum_last_poster_id' => $forum_data['forum_last_poster_id'],
             'forum_last_poster_time' => $forum_data['forum_last_poster_time'],
             'forum_last_poster_name' => $forum_data['forum_last_poster_name'],
             'forum_last_poster_subject' => $forum_data['forum_last_poster_subject'],
             'forum_newtopics_sincedate' => $newTopics,
             'forum_updatedtopics_sincedate' => $updatedTopics);
    
    return array('topics' => $list,
                 'forum_info' => $forum_info);
}


/**
* This method allows you to search inside the board messages and topics.<br/>
* <br/><b>PARAMETERS:</b><br/>
* - [integer] 	<b>$resultsType</b> 		results object: <i>'topics'</i> or <i>'posts'</i>
* - [integer] 	<b>$pgIdx</b> 				the number of page you want to see (default is 0)<br/>
* - [integer] 	<b>$itemsPerPage</b> 		number of topics/posts per page (default is 100)<br/>
* - [integer]	<b>$searchInTopicID</b> 	specify a topic id if you want to limit search inside a specific topic<br/>
* - [array] 	<b>$searchInForumsIDs</b> 	specify an array of forum ids objects if you want to limit the search inside a range of forums (array() to search in all forums)<br/>
* - [string] 	<b>$keywordsToSearch</b> 	words to search<br/>
* - [boolean] 	<b>$searchAnyWord</b> 		search any term (false) or all words (true)<br/>
* - [string] 	<b>$searchWhere</b> 		<i>'all'</i> (both title and message's text), <i>'firstpost'</i> (only in first topic post), <i>'titleonly'</i> (only in topic's titles), <i>'msgonly'</i> (only in post's body)<br/>
* - [boolean] 	<b>$searchInSubForums</b> 	search in sub forums?<br/>
* - [string] 	<b>$orderResultsBy</b> 		<i>'t'</i> (post's time), <i>'a'</i> (post's author name), <i>'f'</i> (by forum group), <i>'i'</i> (argument title), <i>'s'</i> (message's title) <br/>
* - [string] 	<b>$orderDirection</b> 		<i>'a'</i> (ascending order), <i>'d'</i> (descending) <br/>
* - [integer] 	<b>$resultsToReturn</b> 	number of max results to return (-1 to unlimited) <br/>
* - [integer] 	<b>$limitDays</b> 			limit results search (30 = one month is the default value) <br/>
*
* @since 1.0
* @version 1.0
* @return integer   <b>ERRORS:</b><br>
*                   --------------<br> 
*					SEARCH_NOT_ALLOWED SEARCH_NOT_CURRENTLY_AVAILABLE,SEARCH_DISABLED_FLOODED,SEARCH_AUTHORFIELD_TOOSHORT,
*					SEARCH_AUTHORFIELD_NOTVALIDNAMES,SEARCH_NO_SEARCHMODULE,SEARCH_GENERAL_ERROR
*					SEARCH_NO_KEYWORDS_TOSEARCH,SEARCH_NO_RESULTS
*                   <br>
*                   <b>VALID RESULTS:</b><br>
*                   --------------<br>
*					if 'resultsType' is 'TOPICS' it will return an array with these keys:<br/>
*                       <b>'bookmarked'</b> 				is topic bookmarked by logged user? (boolean) (anonymous is obvisiuly false)
*                       <b>'topic_id'</b> 					id of the topic<br />
*                       <b>'topic_title'</b> 				title of the topic<br />
*                       <b>'topic_poster'</b> 				id of the author<br />
*                       <b>'topic_time'</b> 				date of creation in unix format<br />
*                       <b>'topic_views'</b> 				number of views<br />
*                       <b>'topic_replies_real'</b> 		number of replies to topic<br />
*                       <b>'topic_status'</b> 				status of topic<br />
*                       <b>'topic_first_post_id'</b> 		id of the first message of the topic<br />
*                       <b>'topic_first_poster_name'</b>	name of the author of first message of the topic<br />
*                       <b>'topic_last_post_id'</b> 		id of the last post in topic<br />
*                       <b>'topic_last_poster_name'</b> 	name of the author of last reply on topic<br />
*                       <b>'topic_last_post_time'</b> 		send date in unix format of last reply<br />
*                   --------------<br>
*					if 'resultsType' is 'POSTS' it will return an array with these keys:<br/>
*                       <b>'post_time'</b> 					post creation date<br />
*                       <b>'post_text'</b> 					post text<br />
*                       <b>'post_username'</b> 				post username<br />
*                       <b>'poster_id'</b> 					post user id<br />
*                       <b>'forum_id'</b> 					parent forum id<br />
*                       <b>'post_subject'</b> 				post subject<br />
*                       <b>'post_id'</b> 					post id<br />
*/

function searchInsideBoard($args) {
        return _searchInsideBoard($args[0],$args[1],$args[2],$args[3],$args[4],$args[5],$args[6],$args[7],$args[8],$args[9],$args[10],$args[11],$args[12],$args[13]);
}

/*
* @ignore
*/
function _searchInsideBoard($resultsType = 'topics',$pgIdx = 0, $itemsPerPage = 100, $searchInTopicID = 0, $searchInForumsIDs = array(0), $keywordsToSearch = '', $searchForAuthorName = '',
                            $searchAnyWord = false, $searchWhere = 'all', $searchInSubForums = true, $orderResultsBy = 't', $orderDirection = 'd',
                            $resultsToReturn = 300, $limitDays = 30) {
        global $auth,$user,$config,$db,$phpEx,$phpbb_root_path;
        // setup search mode
        $auth->acl($user->data);
        $user->setup('search');
        
        // Define initial vars
        $mode		= '';//request_var('mode', '');
        $search_id	= '';//request_var('search_id', ''); USED TO MAKE PRE-MADE SEARCHES (we need to leave it empty)
        $start		= $pgIdx;//max(request_var('start', 0), 0);
        $post_id	= 0;//request_var('p', 0);
        $topic_id	= $searchInTopicID; //request_var('t', 0);
        $view		= '';//request_var('view', '');

        $submit		= false;//request_var('submit', false);
        $keywords	= utf8_normalize_nfc($keywordsToSearch); //request_var('keywords', '', true));
        
        $add_keywords	= '';//utf8_normalize_nfc(request_var('add_keywords', '', true));
        // if we search in an existing search result just add the additional keywords. But we need to use "all search terms"-mode
	// so we can keep the old keywords in their old mode, but add the new ones as required words
        // THIS IS NOT CURRENTLY SUPPORTED
        
        $author		= $searchForAuthorName;//request_var('author', '', true);
        $author_id	= '';//request_var('author_id', 0);
        $show_results	= ($topic_id) ? 'posts' : $resultsType;//request_var('sr', 'posts');
        //$show_results	= ($show_results == 'posts') ? 'posts' : 'topics';
        $search_terms	= ($searchAnyWord ? 'terms' : 'all' );//request_var('terms', 'all');
        $search_fields	= $searchWhere;//request_var('sf', 'all');
        $search_child	= $searchInSubForums;//request_var('sc', true);

        $sort_days	= 0;//request_var('st', 0);
        $sort_key	= $orderResultsBy;//request_var('sk', 't');
        $sort_dir	= $orderDirection;//request_var('sd', 'd');

        $return_chars	= $resultsToReturn;//request_var('ch', ($topic_id) ? -1 : 300);
        $search_forum	= $searchInForumsIDs;//request_var('fid', array(0));
        
        // Is user able to search? Has search been disabled?
        if (!$auth->acl_get('u_search') || !$auth->acl_getf_global('f_search') || !$config['load_search'])
                return SEARCH_NOT_ALLOWED;
        
        // Check search load limit
        // Sorry but you cannot use search at this time. Please try again in a few minutes.
        if ($user->load && $config['limit_search_load'] && ($user->load > doubleval($config['limit_search_load'])))
                return SEARCH_NOT_CURRENTLY_AVAILABLE;

        // Check flood limit ... if applicable
        $interval = ($user->data['user_id'] == ANONYMOUS) ? $config['search_anonymous_interval'] : $config['search_interval'];
        if ($interval && !$auth->acl_get('u_ignoreflood')) {
                if ($user->data['user_last_search'] > time() - $interval) {
                        return SEARCH_DISABLED_FLOODED;
                }
        }
        
        $s_limit_days = $limitDays;
        
        // compile author field
        if ($author) {
		if ((strpos($author, '*') !== false) && (utf8_strlen(str_replace(array('*', '%'), '', $author)) < $config['min_search_author_chars']))  {
			//trigger_error(sprintf($user->lang['TOO_FEW_AUTHOR_CHARS'], $config['min_search_author_chars']));
                        return SEARCH_AUTHORFIELD_TOOSHORT;
                }

		$sql_where = (strpos($author, '*') !== false) ? ' username_clean ' . $db->sql_like_expression(str_replace('*', $db->any_char, utf8_clean_string($author))) : " username_clean = '" . $db->sql_escape(utf8_clean_string($author)) . "'";

		$sql = 'SELECT user_id
			FROM ' . USERS_TABLE . "
			WHERE $sql_where
				AND user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')';
		$result = $db->sql_query_limit($sql, 100);

		while ($row = $db->sql_fetchrow($result)) {
			$author_id_ary[] = (int) $row['user_id'];
		}
		$db->sql_freeresult($result);
		if (!sizeof($author_id_ary)) {
			//trigger_error('NO_SEARCH_RESULTS');
                        return SEARCH_AUTHORFIELD_NOTVALIDNAMES;
                }
	}
        
        // Which forums should not be searched? Author searches are also carried out in unindexed forums
	if (empty($keywords) && sizeof($author_id_ary)) {
		$ex_fid_ary = array_keys($auth->acl_getf('!f_read', true));
	} else {
		$ex_fid_ary = array_unique(array_merge(array_keys($auth->acl_getf('!f_read', true)), array_keys($auth->acl_getf('!f_search', true))));
	}
        
        $not_in_fid = (sizeof($ex_fid_ary)) ? 'WHERE ' . $db->sql_in_set('f.forum_id', $ex_fid_ary, true) . " OR (f.forum_password <> '' AND fa.user_id <> " . (int) $user->data['user_id'] . ')' : "";

	$sql = 'SELECT f.forum_id, f.forum_name, f.parent_id, f.forum_type, f.right_id, f.forum_password, fa.user_id
		FROM ' . FORUMS_TABLE . ' f
		LEFT JOIN ' . FORUMS_ACCESS_TABLE . " fa ON (fa.forum_id = f.forum_id
			AND fa.session_id = '" . $db->sql_escape($user->session_id) . "')
		$not_in_fid
		ORDER BY f.left_id";
	$result = $db->sql_query($sql);

	$right_id = 0;
	$reset_search_forum = true;
	while ($row = $db->sql_fetchrow($result)) {
		if ($row['forum_password'] && $row['user_id'] != $user->data['user_id']) {
			$ex_fid_ary[] = (int) $row['forum_id'];
			continue;
		}

		if (sizeof($search_forum)) {
			if ($search_child) {
				if (in_array($row['forum_id'], $search_forum) && $row['right_id'] > $right_id) {
					$right_id = (int) $row['right_id'];
				} else if ($row['right_id'] < $right_id) {
					continue;
				}
			}

			if (!in_array($row['forum_id'], $search_forum)) {
				$ex_fid_ary[] = (int) $row['forum_id'];
				$reset_search_forum = false;
			}
		}
	}
	$db->sql_freeresult($result);
        
        
        // find out in which forums the user is allowed to view approved posts
	if ($auth->acl_get('m_approve')) {
		$m_approve_fid_ary = array(-1);
		$m_approve_fid_sql = '';
	} else if ($auth->acl_getf_global('m_approve')) {
		$m_approve_fid_ary = array_diff(array_keys($auth->acl_getf('!m_approve', true)), $ex_fid_ary);
		$m_approve_fid_sql = ' AND (p.post_approved = 1' . ((sizeof($m_approve_fid_ary)) ? ' OR ' . $db->sql_in_set('p.forum_id', $m_approve_fid_ary, true) : '') . ')';
	} else {
		$m_approve_fid_ary = array();
		$m_approve_fid_sql = ' AND p.post_approved = 1';
	}

	if ($reset_search_forum) {
		$search_forum = array();
	}
        
        
        // Select which method we'll use to obtain the post_id or topic_id information
	$search_type = basename($config['search_type']);

	if (!file_exists($phpbb_root_path . 'includes/search/' . $search_type . '.' . $phpEx)){
		//trigger_error('NO_SUCH_SEARCH_MODULE');
                return SEARCH_NO_SEARCHMODULE;
        }

	require("{$phpbb_root_path}includes/search/$search_type.$phpEx");

	// We do some additional checks in the module to ensure it can actually be utilised
	$error = false;
	$search = new $search_type($error);

	if ($error) {
		//trigger_error($error);
                return SEARCH_GENERAL_ERROR;
        }
        
        	// let the search module split up the keywords
	if ($keywords) {
		$correct_query = $search->split_keywords($keywords, $search_terms);
		if (!$correct_query || (empty($search->search_query) && !sizeof($author_id_ary) && !$search_id)) {
			$ignored = (sizeof($search->common_words)) ? sprintf($user->lang['IGNORED_TERMS_EXPLAIN'], implode(' ', $search->common_words)) . '<br />' : '';
			//trigger_error($ignored . sprintf($user->lang['NO_KEYWORDS'], $search->word_length['min'], $search->word_length['max']));
                        return SEARCH_NO_KEYWORDS_TOSEARCH;
                }
	}

	if (!$keywords && sizeof($author_id_ary)) {
		// if it is an author search we want to show topics by default
		$show_results = ($topic_id) ? 'posts' : request_var('sr', ($search_id == 'egosearch') ? 'topics' : 'posts');
		$show_results = ($show_results == 'posts') ? 'posts' : 'topics';
	}

        // define some variables needed for retrieving post_id/topic_id information
	$sort_by_sql = array('a' => 'u.username_clean', 't' => (($show_results == 'posts') ? 'p.post_time' : 't.topic_last_post_time'), 'f' => 'f.forum_id', 'i' => 't.topic_title', 's' => (($show_results == 'posts') ? 'p.post_subject' : 't.topic_title'));
        
        // WE HAVE REMOVED FROM THE ORIGINAL FUNCTION THE PART ABOUT PRE MADE SEARCHES
        
        // show_results should not change after this
	$per_page = $itemsPerPage;//($show_results == 'posts') ? $config['posts_per_page'] : $config['topics_per_page'];
	$total_match_count = 0;
        $author_id_ary = array(); // leave empty (we are not searching for user)
        
        // make sure that some arrays are always in the same order
	sort($ex_fid_ary);
	sort($m_approve_fid_ary);
	sort($author_id_ary);
        
        if (!empty($search->search_query)) {
		$total_match_count = $search->keyword_search($show_results, $search_fields, $search_terms, $sort_by_sql, $sort_key, $sort_dir, $sort_days, $ex_fid_ary, $m_approve_fid_ary, $topic_id, $author_id_ary, $id_ary, $start, $per_page);
	} else if (sizeof($author_id_ary)) {
		$firstpost_only = ($search_fields === 'firstpost') ? true : false;
		$total_match_count = $search->author_search($show_results, $firstpost_only, $sort_by_sql, $sort_key, $sort_dir, $sort_days, $ex_fid_ary, $m_approve_fid_ary, $topic_id, $author_id_ary, $id_ary, $start, $per_page);
	}

	// For some searches we need to print out the "no results" page directly to allow re-sorting/refining the search options.
	if (!sizeof($id_ary) && !$search_id) {
		//trigger_error('NO_SEARCH_RESULTS');
                return SEARCH_NO_RESULTS;
        }

	$sql_where = '';
        
        if (sizeof($id_ary)) {
		$sql_where .= $db->sql_in_set(($show_results == 'posts') ? 'p.post_id' : 't.topic_id', $id_ary);
		$sql_where .= (sizeof($ex_fid_ary)) ? ' AND (' . $db->sql_in_set('f.forum_id', $ex_fid_ary, true) . ' OR f.forum_id IS NULL)' : '';
		$sql_where .= ($show_results == 'posts') ? $m_approve_fid_sql : str_replace(array('p.post_approved', 'p.forum_id'), array('t.topic_approved', 't.forum_id'), $m_approve_fid_sql);
	}

	if ($show_results == 'posts') {
		include_once($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
	} else {
		include_once($phpbb_root_path . 'includes/functions_display.' . $phpEx);
        }
        
        $lista = array();
        if ($sql_where) {
		if ($show_results == 'posts') {
			// @todo Joining this query to the one below?
			$sql = 'SELECT zebra_id, friend, foe
				FROM ' . ZEBRA_TABLE . '
				WHERE user_id = ' . $user->data['user_id'];
			$result = $db->sql_query($sql);

			$zebra = array();
			while ($row = $db->sql_fetchrow($result))
			{
				$zebra[($row['friend']) ? 'friend' : 'foe'][] = $row['zebra_id'];
			}
			$db->sql_freeresult($result);

			$sql = 'SELECT p.*, f.forum_id, f.forum_name, t.*, u.username, u.username_clean, u.user_sig, u.user_sig_bbcode_uid, u.user_colour
				FROM ' . POSTS_TABLE . ' p
					LEFT JOIN ' . TOPICS_TABLE . ' t ON (p.topic_id = t.topic_id)
					LEFT JOIN ' . FORUMS_TABLE . ' f ON (p.forum_id = f.forum_id)
					LEFT JOIN ' . USERS_TABLE . " u ON (p.poster_id = u.user_id)
				WHERE $sql_where";
		}
		else
		{
			$sql_from = TOPICS_TABLE . ' t
				LEFT JOIN ' . FORUMS_TABLE . ' f ON (f.forum_id = t.forum_id)
				' . (($sort_key == 'a') ? ' LEFT JOIN ' . USERS_TABLE . ' u ON (u.user_id = t.topic_poster) ' : '');
			$sql_select = 't.*, f.forum_id, f.forum_name';

			if ($user->data['is_registered'])
			{
				if ($config['load_db_track'])
				{
					$sql_from .= ' LEFT JOIN ' . TOPICS_POSTED_TABLE . ' tp ON (tp.user_id = ' . $user->data['user_id'] . '
						AND t.topic_id = tp.topic_id)';
					$sql_select .= ', tp.topic_posted';
				}

				if ($config['load_db_lastread'])
				{
					$sql_from .= ' LEFT JOIN ' . TOPICS_TRACK_TABLE . ' tt ON (tt.user_id = ' . $user->data['user_id'] . '
							AND t.topic_id = tt.topic_id)
						LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.user_id = ' . $user->data['user_id'] . '
							AND ft.forum_id = f.forum_id)';
					$sql_select .= ', tt.mark_time, ft.mark_time as f_mark_time';
				}
			}

			if ($config['load_anon_lastread'] || ($user->data['is_registered'] && !$config['load_db_lastread']))
			{
				$tracking_topics = (isset($_COOKIE[$config['cookie_name'] . '_track'])) ? ((STRIP) ? stripslashes($_COOKIE[$config['cookie_name'] . '_track']) : $_COOKIE[$config['cookie_name'] . '_track']) : '';
				$tracking_topics = ($tracking_topics) ? tracking_unserialize($tracking_topics) : array();
			}

			$sql = "SELECT $sql_select
				FROM $sql_from
				WHERE $sql_where";
		}
		$sql .= ' ORDER BY ' . $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');
		$result = $db->sql_query($sql);
		$result_topic_id = 0;

		$rowset = array();

		if ($show_results == 'topics')
		{
			$forums = $rowset = $shadow_topic_list = array();
			while ($row = $db->sql_fetchrow($result))
			{
				if ($row['topic_status'] == ITEM_MOVED)
				{
					$shadow_topic_list[$row['topic_moved_id']] = $row['topic_id'];
				}

				$rowset[$row['topic_id']] = $row;

				if (!isset($forums[$row['forum_id']]) && $user->data['is_registered'] && $config['load_db_lastread'])
				{
					$forums[$row['forum_id']]['mark_time'] = $row['f_mark_time'];
				}
				$forums[$row['forum_id']]['topic_list'][] = $row['topic_id'];
				$forums[$row['forum_id']]['rowset'][$row['topic_id']] = &$rowset[$row['topic_id']];
			}
			$db->sql_freeresult($result);

			// If we have some shadow topics, update the rowset to reflect their topic information
			if (sizeof($shadow_topic_list))
			{
				$sql = 'SELECT *
					FROM ' . TOPICS_TABLE . '
					WHERE ' . $db->sql_in_set('topic_id', array_keys($shadow_topic_list));
				$result = $db->sql_query($sql);
			
				while ($row = $db->sql_fetchrow($result))
				{
					$orig_topic_id = $shadow_topic_list[$row['topic_id']];
			
					// We want to retain some values
					$row = array_merge($row, array(
						'topic_moved_id'	=> $rowset[$orig_topic_id]['topic_moved_id'],
						'topic_status'		=> $rowset[$orig_topic_id]['topic_status'],
						'forum_name'		=> $rowset[$orig_topic_id]['forum_name'])
					);
			
					$rowset[$orig_topic_id] = $row;
				}
				$db->sql_freeresult($result);
			}
			unset($shadow_topic_list);

			foreach ($forums as $forum_id => $forum)
			{
				if ($user->data['is_registered'] && $config['load_db_lastread'])
				{
					$topic_tracking_info[$forum_id] = get_topic_tracking($forum_id, $forum['topic_list'], $forum['rowset'], array($forum_id => $forum['mark_time']), ($forum_id) ? false : $forum['topic_list']);
				}
				else if ($config['load_anon_lastread'] || $user->data['is_registered'])
				{
					$topic_tracking_info[$forum_id] = get_complete_topic_tracking($forum_id, $forum['topic_list'], ($forum_id) ? false : $forum['topic_list']);
		
					if (!$user->data['is_registered'])
					{
						$user->data['user_lastmark'] = (isset($tracking_topics['l'])) ? (int) (base_convert($tracking_topics['l'], 36, 10) + $config['board_startdate']) : 0;
					}
				}
			}
			unset($forums);
		}
		else
		{
			$bbcode_bitfield = $text_only_message = '';
			$attach_list = array();

			while ($row = $db->sql_fetchrow($result))
			{
				// We pre-process some variables here for later usage
				$row['post_text'] = censor_text($row['post_text']);

				$text_only_message = $row['post_text'];
				// make list items visible as such
				if ($row['bbcode_uid'])
				{
					$text_only_message = str_replace('[*:' . $row['bbcode_uid'] . ']', '&sdot;&nbsp;', $text_only_message);
					// no BBCode in text only message
					strip_bbcode($text_only_message, $row['bbcode_uid']);
				}

				if ($return_chars == -1 || utf8_strlen($text_only_message) < ($return_chars + 3))
				{
					$row['display_text_only'] = false;
					$bbcode_bitfield = $bbcode_bitfield | base64_decode($row['bbcode_bitfield']);

					// Does this post have an attachment? If so, add it to the list
					if ($row['post_attachment'] && $config['allow_attachments'])
					{
						$attach_list[$row['forum_id']][] = $row['post_id'];
					}
				}
				else
				{
					$row['post_text'] = $text_only_message;
					$row['display_text_only'] = true;
				}

				$rowset[] = $row;
			}
			$db->sql_freeresult($result);

			unset($text_only_message);

			// Instantiate BBCode if needed
			if ($bbcode_bitfield !== '')
			{
				include_once($phpbb_root_path . 'includes/bbcode.' . $phpEx);
				$bbcode = new bbcode(base64_encode($bbcode_bitfield));
			}

			// Pull attachment data
			if (sizeof($attach_list))
			{
				$use_attach_list = $attach_list;
				$attach_list = array();

				foreach ($use_attach_list as $forum_id => $_list)
				{
					if ($auth->acl_get('u_download') && $auth->acl_get('f_download', $forum_id))
					{
						$attach_list = array_merge($attach_list, $_list);
					}
				}
			}

			if (sizeof($attach_list))
			{
				$sql = 'SELECT *
					FROM ' . ATTACHMENTS_TABLE . '
					WHERE ' . $db->sql_in_set('post_msg_id', $attach_list) . '
						AND in_message = 0
					ORDER BY filetime DESC, post_msg_id ASC';
				$result = $db->sql_query($sql);
		
				while ($row = $db->sql_fetchrow($result))
				{
					$attachments[$row['post_msg_id']][] = $row;
				}
				$db->sql_freeresult($result);
			}
		}
        
        
                 foreach ($rowset as $row) {
                        if ($show_results == 'topics') {
                                $cTopic = array(
                                        'topic_id' =>  $row['topic_id'],
                                        'forum_id' =>  $row['forum_id'],
                                        'topic_title' =>  censor_text($row['topic_title']),
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
                                        'topic_poster' => $row['topic_poster']);
                                 $lista[] = $cTopic;
                        } else {
                                $cPost = array('post_time' => $row['post_time'],
                                        'post_text' => censor_text($row['post_text']),
                                        'post_username' => $row['username'],
                                        'poster_id' => $row['poster_id'],
                                        'forum_id' => $row['forum_id'],
                                        'topic_id' => $row['topic_id'],
                                        'post_subject' => censor_text($row['post_subject']),
                                        'post_id' => $row['post_id']);
                                 $lista[] = $cPost;
                        }
                }
        }
        // Parse the message and subject
        $pagesNo = ceil($total_match_count/$itemsPerPage);
        return array('results' => $lista,'total_items' => $total_match_count,'total_pages' => $pagesNo, 'current_page' => $pgIdx);
}

?>
