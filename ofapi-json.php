<?php

/**
* 
* @package OFAPI
* @version v 1.1 2008/07/22 (first draft 2008/06/28)
* @copyright (c) 2008 Roberto Beretta (roberto.alpha@gmail.com) & Daniele Margutti (malcom.mac@gmail.com)
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* @link http://code.google.com/p/ofapi
*
*/

define('IN_OFAPI', true);

// import rpc server
include_once('jsonRPCServer.php');

// import ofapi core
include_once('headers.php');

_init();

// get list of methods
$methods = array(
	// logging module
	'logging.login'													=>	'login',
	'logging.loginProtectedForumWithPassword'						=>	'loginProtectedForumWithPassword',
	
	// common module
	'common.isUserAdmin'											=>	'isUserAdmin',
	
	// members module
	'members.searchForMember'										=>	'searchForMember',
	'members.getMembersList'										=>	'getMembersList',
	'members.getMembersListWithAdvancedSearch'						=>	'getMembersListWithAdvancedSearch',
	
	// miscs module
	'miscs.getFAQGuide'												=>	'getFAQGuide',
	'miscs.getBoardConfigurationData'								=>	'getBoardConfigurationData',
	
	// msgs module
	'msgs.getActiveTopics'											=>	'getActiveTopics',
	'msgs.getUnansweredPosts'										=>	'getUnansweredPosts',
	'msgs.getNewPostedMessagesFilteredBy'							=>	'getNewPostedMessagesFilteredBy',
	'msgs.getRecentsHotTopics'										=>	'getRecentsHotTopics',
	'msgs.getRecentsAnnouncements'									=>	'getRecentsAnnouncements',
	'msgs.getTopicsMessages'										=>	'getTopicsMessages',
	'msgs.getTopicsFromForum'										=>	'getTopicsFromForum',
	'msgs.getForumsTree'											=>	'getForumsTree',
	'msgs.getForumInfo'											=>	'getForumInfo',
	'msgs.getPostsWithIDs'											=>	'getPostsWithIDs',
	'msgs.getPostWithID'											=>	'getPostWithID',
	'msgs.getTopicWithID'											=>	'getTopicWithID',
	'msgs.getPageIndexForPostWithID'								=>	'getPageIndexForPostWithID',
	'msgs.getPageToLookForPostsAfterDate'							=>	'getPageToLookForPostsAfterDate',
	'msgs.searchInsideBoard'										=>	'searchInsideBoard',
	
	// stats module
	'stats.getNumberOfOnlineGuests'									=>	'getNumberOfOnlineGuests',
	'stats.getOnlineUsers'											=>	'getOnlineUsers',
	'stats.getTopPosters'											=>	'getTopPosters',
	'stats.getGeneralForumStatistics'								=>	'getGeneralForumStatistics',
	'stats.getLastSubscribedMembers'								=>	'getLastSubscribedMembers',
	'stats.getRandomMemberInfo'										=>	'getRandomMemberInfo',
	'stats.getWordsgraphList'										=>	'getWordsgraphList',
	'stats.getTodaysBirthdays'										=>	'getTodaysBirthdays',
	'stats.getBoardAdmins'											=>	'getBoardAdmins',
	'stats.getModsList'												=>	'getModsList',
	'stats.getModsFromForumID'										=>	'getModsFromForumID',
	'stats.getLastBots'												=>	'getLastBots',
	'stats.getBoardName'											=>	'getBoardName',
		
	// user module
	'user.getOnlineFriends'											=>	'getOnlineFriends',
	// bookmarks
	'user.getBookmarkedTopics'										=>	'getBookmarkedTopics',
	'user.isTopicIDBookmarked'										=>	'isTopicIDBookmarked',
	'user.toogleBookmarkSetting'									=>	'toogleBookmarkSetting',
		
	'user.getMessagesFolders'										=>	'getMessagesFolders',
	'user.getOutboxFolder'											=>	'getOutboxFolder',
	'user.getInboxFolder'											=>	'getInboxFolder',
	'user.getContentOfFolderWithID'									=>	'getContentOfFolderWithID',
	'user.getUserProfileFrom'										=>	'getUserProfileFrom',
	'user.sendEmailToUser'											=>	'sendEmailToUser',
	'user.getMeProfile'												=>	'getMeProfile',
	// private messaging
	'user.getUnreadPrivateMessages'									=>	'getUnreadPrivateMessages',
	'user.setPrivateMessageAsRead'									=>	'setPrivateMessageAsRead',
	'user.countUnreadPrivateMessages'								=>	'countUnreadPrivateMessages',
	'user.sendPrivateMessage'										=>	'sendPrivateMessage',
		
	// posting module
	'msg_sending.replyToPost'										=>	'replyToPost',
	'msg_sending.deletePost'										=>	'deletePost',
	'msg_sending.postNewTopic'										=>	'postNewTopic'

);

//create the server
jsonRPCServer::handle($methods);
?>
