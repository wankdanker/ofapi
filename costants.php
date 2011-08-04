<?

/**
* 
* @package OFAPI
* @version v 1.1 2008/07/22 (first draft 2008/06/28)
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

/* MESSAGES COSTANTS */

/**
 * you must to be logged in order to use this function
 */
define('GENERAL_ERR_MUSTLOGGED',-1);

/**
 * general error???
 */
define('GENERAL_ERROR',-100);

/**
 * forum, topic or something else was protected with password
 */
define('GENERAL_PASSWORDPROTECTED_ITEM',-101);

/* 
* LOGGING_MODULE.PHP 
*/

// used by login function
/**
 * login succeded
 */
define('OP_LOGIN_SUCCESS',-2); 

/**
 * wrong password, cannot logging in
 */
define('OP_LOGIN_ERROR_PASSWORD',-3); 

/**
 * you have reached the maximum login attempts, your account was blocked
 */
define('OP_LOGIN_ERROR_ATTEMPTS',-4);

// used by loginProtectedForumWithPassword function

/**
 * logged into the forum successfully
 */
define('LOGIN_FPSWD_OK',-5);

/**
 * given password is wrong
 */
define('LOGIN_FPSWD_ERR',-6);

/**
 * you are already logged into given forum
 */
define('LOGIN_FPSWD_ALREADYLOGGED',-7);	

/**
 * given forum id does not exist
 */
define('LOGIN_FPSWD_WRONGFORUMID',-8);

/*
*	USER_MODULE.PHP
*/

// used in sendEmailToUser function

/**
 * mail feature was disabled in configuration
 */
define('SENDMAIL_ERR_MAILDISABLED',-9);

/**
 * mail not allowed to user
 */
define('SENDMAIL_ERR_MAILNOTALLOWED',-10);

/**
 * email flood limit reached (are you a fucking spammer???)
 */
define('SENDMAIL_ERR_FLOODLIMIT',-11);

/**
 * ok sent!
 */
define('SENDMAIL_OK',-12);

define('SENDMAIL_ERR',-124);

/**
 * you have specified an unexisting user id as destination field
 */
define('SENDMAIL_ERR_NODESTINATION',-13);

// used in getUserProfileFrom function

/**
 * you cannot view profile due to board restrictions
 */
define('USERPROFILE_VIEW_NOTALLOWED',-14);

/**
 * you cannot view profile without logging in
 */
define('USERPROFILE_VIEW_NOTALLOWEDANONYMOUS',-15);

/**
 * specified member does not exist
 */
define('USERPROFILE_VIEW_NONEXISTINGMEMBER',-16);


/*
* MSGS_MODULE.PHP
*/

// used in getTopicMessages function
// -> GENERAL_PASSWORDPROTECTED_ITEM
// -> LOGIN_FPSWD_OK
// -> LOGIN_FPSWD_ERR
// -> LOGIN_FPSWD_ALREADYLOGGED
// -> LOGIN_FPSWD_WRONGFORUMID

/**
 * given topic id does not exist here
 */
define('GET_TOPICMSGS_NOTOPIC',-17);

/**
 * no newer topics after current selection
 */
define('GET_TOPICMSGS_NONEWERTOPICS',-18);

/**
 * no older topics before current selection
 */
define('GET_TOPICMSGS_NOOLDERTOPICS',-19);

/**
 * no data for this topic id
 */
define('GET_TOPICMSGS_NOTOPICDATA',-20);

/**
 * you have not authorization
 */
define('GET_TOPICMSGS_AUTHREAD',-21);

/**
 * time range does not contains posts
 */
define('GET_TOPICMSGS_NOPOSTINTIMEFRAME',-22);

/* msg_sending module */


$constval = -22;

/**
 * invalid mode specified for sending core method (you should not use core function)
 */
define('SUBMIT_INVALID_MODE', --$constval);

/**
 * no post specified in sending function
 */
define('NO_POST_SPECIFIED', --$constval);

/**
 * no topic specified in sending function
 */
define('NO_TOPIC_SPECIFIED',--$constval);

/**
 * invalid post mode specified
 */
define('NO_POST_MODE', --$constval);

/**
 * forum is protected
 */
define('PROTECTED_FORUM_NEED_PASSWORD',--$constval);

/**
 * bots not allowed
 */
define('BOT_GO_HOME', --$constval);

/**
 * you cannot read messages
 */
define('USER_CANNOT_READ', --$constval);

/**
 * you cannot posts messages
 */
define('USER_CANNOT_POST', --$constval);

/**
 * you cannot reply
 */
define('USER_CANNOT_REPLY', --$constval);

/**
 * you cannot delete
 */
define('USER_CANNOT_DELETE', --$constval);

/**
 * you cannot delete replied message (???)
 */
define('CANNOT_DELETE_REPLIED', --$constval);

/**
 * topic is locked
 */
define('TOPIC_LOCKED', --$constval);

/**
 * forum is locked
 */
define('FORUM_LOCKED', --$constval);

/**
 * cannot edit post time (???)
 */
define('CANNOT_EDIT_TIME', --$constval);

/**
 * cannot edit a locked post
 */
define('CANNOT_EDIT_POST_LOCKED', --$constval);

/**
 * you're not authorized
 */
define('NOT_AUTHORISED', --$constval);

/**
 * you have not enough rights to post an announce
 */
define('CANNOT_POST_ANNOUNCE', --$constval);

/**
 * you have not enough rights to post a sticky
 */
define('CANNOT_POST_STICKY', --$constval);

/**
 * choosed post was deleted
 */
define ('POST_DELETED', --$constval);

/**
 * ...?
 */
define ('DELETED_OWN_POST', --$constval);






/** ************************************
*		MEMBERS LIST NUMERIC RESULTS
*   ************************************ */

/**
 * Anonymous memberlist task is not allowed in this board
 */
define('MEMBERSLIST_ANONYMOUS_NOT_ALLOWED',-42);

/**
 * You need to be logged into the board to use this feature
 */
define('MEMBERSLIST_NOT_AUTHORIZED',-43);





/** ************************************
*		SEARCH MODULE NUMERIC RESULTS
*   ************************************ */

/**
 * Search feature is not available in this board
 */
define('SEARCH_NOT_ALLOWED',-44);

/**
 * Search feature is not available at this time, try again later
 */
define('SEARCH_NOT_CURRENTLY_AVAILABLE',-45);

/**
 * You have made lots of search, are you flooding this server?
 */
define('SEARCH_DISABLED_FLOODED',-46);

/**
 * Given author name is too short (wildcards + short name given?)
 */
define('SEARCH_AUTHORFIELD_TOOSHORT',-47);

/**
 * Choosed author name does not exist in this board
 */
define('SEARCH_AUTHORFIELD_NOTVALIDNAMES',-48);

/**
 * Cannot found search module. contact the administrator
 */
define('SEARCH_NO_SEARCHMODULE',-49);

/**
 * General error has occurred. Contact the administrator.
 */
define('SEARCH_GENERAL_ERROR',-50);

/**
 * You should really search for something... give me your f* strings
 */
define('SEARCH_NO_KEYWORDS_TOSEARCH',-51);

/**
 * No results found during the search task
 */
define('SEARCH_NO_RESULTS',-52);

/**
 * Bad number of given parameters, read the doc
 * */
define('GENERAL_WRONG_PARAMETERS',-53);

?>