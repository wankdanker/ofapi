<?php

/**
* @ignore
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
* fuck
*/


// your source code must include this line in order to import all ofapi core

// import ofapi core
define('IN_OFAPI', true);
include_once('headers.php');

global $config;
//echo(print_r($config));
// login function (required by lots of methods)
$log = login(array('malcom', 'carbon', true, true));

//echo '<pre>';
echo (int)$log;

//if ($log != OP_LOGIN_SUCCESS) die("Devi effettuare il login!");

//echo print_r(getUnansweredPosts(array('topics',400)));

//echo print_r(getNewPostedMessagesFilteredBy(array('topics',5,0,array('2008-01-01','2008-01-10'))));
echo print_r(getActiveTopics(array(100,0,10)));
//echo print_r(getUnansweredPosts(array('topics',100,0,10)));

//echo(print_r(getForumsTree(array(1211593260))));
//echo(print_r(getForumInfo(array(144,1211593260))));

//echo(print_r(getTopicWithID(array(144,2152,"",1211593260))));

//getMessagesFolders();
//echo(print_r(getOnlineUsers()));
//echo(print_r(getTopPosters(null)));
//echo(print_r(getOnlineFriends(null)));
//echo(print_r(getGeneralForumStatistics()));
//echo(print_r(getLastSubscribedMembers(null)));
//echo(print_r(getRandomMemberInfo())); // VEDERE IL CODICE IN CANSHOWMAIL
//echo(print_r(getWordsgraphList(array(5))));
//echo(print_r(getRecentsHotTopics(array(5))));
//echo(print_r(getRecentsAnnouncements(array(6))));
//echo(print_r(getTodaysBirthdays(array(20))));
//echo(print_r(getBoardLeaders()));
//echo(print_r(getModsFromForumID(array(144))));
//echo(print_r(isUserWatchingTopicID(array(4627))));
//echo(print_r(getForumName()));
//echo(print_r(getLastBots(null)));
//echo(print_r(getUsernamesFromIds(array(array(2,4)))));
//echo(print_r(getUserIdsFromNames(array('malcom'))));
//echo(print_r(getFAQGuide(array('bbcode'))));
//echo(print_r(getMembersListWithAdvancedSearch(array('',0,30,'','c','a',array('username' => 'malc*','icq' => 'malc*')))));


//echo(print_r(searchInsideBoard(array('posts',0,100,0,array(),'ciao','',false,'all',true,'t','d',300,30))));

//echo(print_r(toogleBookmarkSetting(4616)));
//echo(print_r(isTopicIDBookmarked(4716)));

//echo(print_r(searchForMember(array('username' => 'malc*'),0,30)));
//echo(print_r(getUserProfileFrom(array(1,'malcom'))));
//echo(print_r(getUserProfileFrom(array(0,2))));
//echo print_r(getPostWithID(array( 126403) ));
//echo print_r(deletePost( array(6,4768,132577,'')));
//echo(print_r(sendEmailToUser(array(2,'un cazzo','merda',true,''))));
//echo(print_r(getTopicsFromForum(array(3))));
//echo(print_r(_getTopicMessages(3,4349,null,null)));
//$risultato = ofapi_submit('post','soggetto del cazzo','body del cazzo [b] berda[/b]',144, 0,0,POST_NORMAL,true,true,true,true);
//$risultato = deletePost(144,4775,-1);
//getPostText(144,4772,132622);
//echo(print_r($risultato));
//echo $risultato;
//echo(print_r($risultato));
//getLicenseAgreement();
//echo(print_r(getForumsTree()));
//echo(print_r(getMeProfile($log)));
//echo(print_r(getBoardConfigurationData()));

//echo(print_r(getTopicsMessages(array(144,2152,"",array(),1,"",0,"t","a",false,"","",25))).']');

//echo( '[PAGE: '.print_r(getPageIndexForPostWithID(array(132527,4763,144,25))) .']');
 //echo(countUnreadPrivateMessages());
 //echo(print_r(markPrivMessageAsRead(array(24040,0))));
 //echo(print_r(getUnreadPrivateMessages()));
  //echo (print_r(sendPrivateMessage(array('reply',4,'merdoso','caccoloso',24041,true,true,true,true,true))));
 // echo (print_r(sendPrivateMessage(array('post',4,'merdoso','caccoloso',null,true,true,true,true,true))));
//echo(print_r(getMembersList(array(0,10,'a'))));

     /*   $post_id = $args[0];
        $topic_id = $args[1];
        $forum_id = $args[2];
        $postsPerPage = $args[2];*/
     
//echo print_r(getForumsTree());
//echo(print_r(getTopicsFromForum(array(144,0,30,25,'','',1211593260))));
//echo(print_r(getBookmarkedTopics(array(1211593260,25))));
//echo(print_r(getPageToLookForPostsAfterDate(array(4732,1211593260,25))));
//echo(print_r(_getFirstNewPostIDInTopicAfterADate(array(4732,12115932605,25))));

?>