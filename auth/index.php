<?php

/*
* @version    2010 05 22
* @author     mxrgus.pxrt <margus@tione.eu>
**/

// include dokuwiki ness things (sets session.id also)
if(!defined('DOKU_INC')) define('DOKU_INC',dirname(__FILE__).'/../../../../');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/common.php');
require_once(DOKU_INC.'inc/events.php');
require_once(DOKU_INC.'inc/pageutils.php');

// if smartcard auth was success, write SSL_CLIENT_CERT to session (it will be used from auth_smartcard later on)
if(isset($_SERVER['SSL_CLIENT_CERT'])){
  session_start();
  $_SESSION['SSL_CLIENT_CERT']=$_SERVER['SSL_CLIENT_CERT'];
  session_write_close();
}

// redirect back to dokuwiki main url
$gotowikiurl = "../../../../doku.php?u=smartcard&p=smartcard&id=" . getID();
header("Location: $gotowikiurl");

