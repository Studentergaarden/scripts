#!/usr/bin/php
<?php

/*
 * Dette script læser en mail fra stdin og indsætter indholdet i phpBBs
 * forum/database. Husk at installere php5-imap. PlancakeEmailParser bruger
 * denne til at decode from, subject mv.
 *
 * Brug:
 * ./mail2forum.php 'forum'
 * De forskellige forums er defineret længere nede i scriptet. $forum_id kan
 * findes i phpBBs mysql-database -> phpbb_forums. Eller med følgende mysql kald
 *
 * $ mysql -u root -p
 * mysql> use phpbb
 * mysql> SELECT forum_id, forum_name FROM phpbb_forums ORDER BY forum_id ASC;
 *
 * Se også /etc/postfix/aliases/aliases
 * Skrevet af Paw
 */

// https://wiki.phpbb.com/Database_Abstraction_Layer

$__DEBUG = false;
// $__DEBUG = true;

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ?
    PHPBB_ROOT_PATH : '/share/sites/vvv.studentergaarden.dk/DocumentRoot/forum/';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
include($phpbb_root_path . 'includes/bbcode.' . $phpEx);
include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
// Start session management - Needed for some reason...
$user->session_begin();
$auth->acl($user->data);

require_once("PlancakeEmailParser.php");

// $argv[0] is name of the script
if (isset($argv[1]))
  $arg = $argv[1];
else{
  echo "-------------ERROR------------------\n";
  echo "in " . $argv[0] . "\n";
  echo "forgot to pass forum to script\n";
  echo "------------------------------------\n";
  exit;
}


// read from stdin
if (!$__DEBUG){
  $fd = fopen("php://stdin", "r");
  $email = "";
  while (!feof($fd)) {
    $line = fread($fd, 1024);
    $email .= $line;
  }
}else{
  $emailPath = "/home/pawse/scripts/test_egern.txt";
  $emailPath = "/home/pawse/scripts/test_egern_svar.txt";
  //$emailPath = "/home/pawse/scripts/mail_sara.txt";
  $emailPath = "/home/pawse/scripts/test_ny.txt";
  $emailPath = "/home/pawse/scripts/mail_genie.txt";
  $email = file_get_contents($emailPath);
}
$emailParser = new PlancakeEmailParser(($email));

$subject     = $emailParser->getSubject();
$from = $emailParser->getFrom();
// get email-adr: match between 'Paw <pawse@gmail.com>'
preg_match("'<(.*?)>'si", $from, $from_email);
// get name: match before  'Paw <pawse@gmail.com>'
preg_match("'(.*?)<'si", $from, $from_name);
preg_match("'<(.*?)>'si", $emailParser->getMessageID(), $message_id);
preg_match("'<(.*?)>'si", $emailParser->getInReplyTo(), $reply_id);
$reply_id = $reply_id[1];
$message_id = $message_id[1];
$from_name = $from_name[1];
$from_email  = $from_email[1];
// remove any tabs or whitespace
$from_name = trim($from_name);
$from_email = trim($from_email);
// remove <>, since phpBB does not like them in the post
$from = str_replace(array("<",">"),"-",$from);
if ($from_name == '')
  $from_name = $from;
if ($from_email == '')
  $from_email= $from;

// add email adr and message id to post - the latter is used to figure out where
// to put reply's
$body = $emailParser->getBody() .
    "\n----\nSent af: $from\n";


switch($arg){
  case "fremleje":
  case "fremlejer":
    $forum_id = 7;
    $subject = "[ UDEFRA ] " . $subject;
    break;
  case "sgr-aaben":
    $forum_id = 10;
    break;
  case "gs-aaben":
    $forum_id = 9;
    break;
  case "pawse":
    $forum_id = 7;
    break;
  default:
    echo "-------------ERROR----------------------------------\n";
    echo "in " . $argv[0] . " arg: " . $arg . "\n";
    echo "wrong value of argument(forum name) passed to script\n";
    echo "----------------------------------------------------\n";
    exit;
}


print "-----------------------------------------------------\n";
echo "to         : " . print_r($emailParser->getTo()) . "\n";
echo "liste      : " . $arg . "\n";
echo "from_name  : " . $from_name . "\n";
echo "from_email : " . $from_email . "\n";
echo "subject    : " . $subject . "\n";
echo "message_id : " . $message_id . "\n";
if ($reply_id){
  echo "reply id   : " . $reply_id . "\n";
}
echo "body       :\n" . $body . "\n";

if ($reply_id){
  $phpbbQuery = "select topic_id from " .  POSTS_TABLE .
      " where email_message_id = '".
      $db->sql_escape($reply_id) . "';";
  echo $phpbbQuery . "\n";
  $phpbbResult = $db->sql_query($phpbbQuery);
  $topic_id = (int) $db->sql_fetchfield('topic_id');
  $db->sql_freeresult($result);
}else
  $topic_id = 0;

// not a reply to already existing post
if ($reply_id and ($topic_id != 0 )){
  $post_type = "reply";
}else{
  $post_type = "post";
}


// post_type can be 'post' or 'reply'. 'post' create new topic_id no matter what.
// post_time is unix eppoch time

// doesn't matter - phpBB sets the time to system time when this script is
// executed
$post_time = 0;

// note that multibyte support is enabled here
$subject     = utf8_normalize_nfc($subject);
$body           = utf8_normalize_nfc($body);

// variables to hold the parameters for submit_post
$poll = $uid = $bitfield = $options = '';
generate_text_for_storage($my_subject, $uid, $bitfield, $options, false, false, false);
generate_text_for_storage($my_text, $uid, $bitfield, $options, true, true, true);

$data = array( 
	      'forum_id'          => $forum_id,
	      // Post a new topic or in an existing one? Set to 0 to create a
	      // new one, if not, specify your topic ID here instead
	      'topic_id'          => $topic_id,
	      'icon_id'           => false,

	      'enable_bbcode'     => true,
	      'enable_smilies'    => true,
	      'enable_urls'       => true,
	      'enable_sig'        => true,

	      'message'       => $body,
	      'message_md5'   => md5($body),

	      // Values from generate_text_for_storage()
	      'bbcode_bitfield'   => $bitfield,
	      'bbcode_uid'        => $uid,

	      'post_edit_locked'  => 0,
	      'topic_title'       => $subject,
	      'notify_set'        => false,
	      'notify'            => false,
	      'post_time'         => $post_time,
	      'forum_name'        => '', // Only used for notify mails
	      'enable_indexing'   => true,
          'email_message_id'  => $message_id,
	       );


// username of the poster is Only valid for guest posters.
if (!$__DEBUG)
  submit_post($post_type, $subject, $from_name, POST_NORMAL, $poll, $data);

// set email-message_id for the new post
// Array with the data to insert
$sqldata = array(
    'email_message_id'     => $message_id,
);
$phpbbQuery = "update " .  POSTS_TABLE . " set " .
    $db->sql_build_array('UPDATE', $sqldata) .
    " where post_id = ". (int)$data['post_id'];
if (!$__DEBUG)
  $phpbbResult = $db->sql_query($phpbbQuery);


// echo print_r($data);
// some return values
echo "post_id: " . $data['post_id'] . "\n";
echo "topic_id: " . $data['topic_id'] . "\n";

return 0;
?>