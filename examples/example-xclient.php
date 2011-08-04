<?php

/**
* @ignore
* 
* @package OFAPI
* @version v 1.0 2008/06/28
* @copyright (c) 2008 Roberto Beretta & Daniele Margutti
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* @link http://code.google.com/p/ofapi
*
*/

define('IN_OFAPI', true);

include_once('../xmlrpc.php');

// Create new XML-RPC client
$client = new IXR_Client('http://localhost/ofapi/ofapi.php');
$client->debug=true;

$loginInfo = array('username','password');

if (!$client->query('logging.login',array('data' => $loginInfo))) {
	print_r($client);
    die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
}

/*
if (!$client->query('members.getMembersList',array('auth' => $loginInfo, 'data' => array(0,10,'a')))) {
         die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
  }
*/
if (!$client->query('msgs.getActiveTopics',array('auth' => $loginInfo, 'data' => array(100,0,10)))) {
	print_r($client);
         die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
  }

// Try the sayHello function
//if (!$client->query('msgs.searchInsideBoard', array('auth' => $loginInfo, 'data' => array('posts',0,100,0,array(),'ciao','',false,'all',true,'t','d',300,30) ))) {
  //  die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
//}

// Show response
//echo "LOGIN ". $client->getResponse().".";

?>
