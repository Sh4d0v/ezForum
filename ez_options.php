<?php
/**
 *----------------------------------------------------------------------
 * ezForum Options File
 *----------------------------------------------------------------------
 * 
 * 
 *----------------------------------------------------------------------
 * Author: Plague Studio <plagues.pl>
 * Contact: contact@plagues.pl
 *----------------------------------------------------------------------
 */

/*
 * Database connection settings
 * $dbhost  - database host (e.g. 'localhost')
 * $dbname  - name of the database used by the forum
 * $dbuser  - database username
 * $dbpass  - database password
 * $dbpref  - table name prefix used for forum tables
 */
$dbhost = 'localhost';
$dbname = 'seo_board';
$dbuser = 'username';
$dbpass = 'password';
$dbpref = 'ez_';

/*
 * Admin account and admin script settings
 * $adminfile - filename of the admin panel script
 * $adminuser - administrator username (set during install)
 * $adminpass - administrator password (set during install)
 * $adminemail - administrator contact email
 */
$adminfile = 'admin.php';
$adminuser = 'adminusername';
$adminpass = 'adminpassword';
$adminemail = 'admin@yoursite.com';

// don't change this AFTER installing the board. You can change it before installing.
// $shaprefix is used as a prefix/salt for password hashing (SHA)
$shaprefix = 'This is the default sha hashing prefix.';

/*
 * Forum URL/path settings
 * $forumhome   - public URL to the forum home (displayed in templates)
 * $forumdir    - directory path of the forum (must end with a '/')
 * $forumscript - URL or path to the main forum script file
 */
$forumhome = 'link to forum home'; // link to forum home
$forumdir = 'forum directory'; // forum dir without the script file, must end with a '/'
$forumscript = 'link to main forum script file'; // forum dir + script file

/*
 * Cookie settings
 * $cookiedomain - domain for which cookie is valid (leave empty for auto)
 * $cookiename   - name of the cookie used for sessions
 * $cookiepath   - path for which cookie is valid (leave empty for '/')
 * $cookiesecure - true if cookies should only be sent over HTTPS
 */
$cookiedomain = '';
$cookiename = 'seo-board';
$cookiepath = '';
$cookiesecure = FALSE;

// Enable gzip output compression to reduce bandwidth (0 = off, 1 = on)
$enablegzip = 0; // 0 - no; 1 - yes

// Timeouts (in seconds)
// $visittimeout - how long a visitor is considered 'online'
// $usereditposttimeout - time allowed for editing a post
$visittimeout = 600;
$usereditposttimeout = 1200;

// Default forum timezone (offset in hours)
$forumtimezone = 2; // default forum zone

// Registration mode
// 1 = register user immediately, 0 = send e-mail with generated password
$registermode = 1; // 1 - register user immediately; 0 - send an e-mail with generated password


// Basic forum settings
$forumtitle = 'ezForum Software';

// Default language (file in /lang directory)
$lang = 'eng';

// Usernames not allowed for registration
$invalidusernames = array('Anonymous', 'Guest');


// Content limits and pagination
$maxwordlength = 60;
$maxpostlength = 20000;

$postsperpage = 30;
$topicsperpage = 20;
$searchresultsperpage = 30;

// How long search results stay cached/valid (in seconds)
$searchexpiretime = 600;

// Display options
$showforumstats = 1; // 0 - no; 1 - yes
$showlastposts = 1;  // 0 - no; 1 - yes
$shownumlastposts = 30;
//exclude forums from showing their topics in last discussions
//array(forumexcludeid1, forumexcludeid2..); or empty -> array();
// Forums to exclude from the "last posts" list
$lastpostsexclude = [];

// Date/time formats (PHP date() format)
$datetimeformat = 'd M Y H:i';
$dateformat = 'd M Y';
$shortdateformat = 'M Y';
//if you use long months (January), then change $engmonths to long English months for multi-lang support
// Short English month names (used if lang is 'eng')
$engmonths = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');


// Anti-spam settings (seconds between posts allowed from same IP)
$antispam = 5;

// URL rewriting
// 0 = no mod_rewrite, 1 = enable friendly URLs (requires correct .htaccess)
$modrewrite = 0; // 0- no mod_rewrite; 1- search engine friendly urls, requires .htaccess ...

/*
 * Special forum options
 * $privateforums - associative array mapping forumID => array(allowedUserIDs)
 * $readonlyforums - forum IDs where only admin can post
 * $postonlyforums - forums where users cannot create new topics
 * $guestscanpostforums - forums where guests are allowed to post
 * $allowhtmlforums - forums which allow raw HTML in posts
 * $articleforums - forums shown sorted by creation time (newest first)
 */
$privateforums = [];
$readonlyforums = [];
$postonlyforums = [];
$guestscanpostforums = [];
$allowhtmlforums = [];
$articleforums = [];

// Forum moderators configuration
// Example: $moderators = array(1 => array(2 => 'Alice', 3 => 'Bob'));
$moderators = [];
$showmoderators = 0; // 0 = hide moderators, 1 = show

/*
 * RSS/Atom feed options
 * $feedexcludeforums - forum IDs to exclude from feeds
 * $feednumtopics - number of topics to include in feeds
 * $signaturesandavatars - enable signatures and avatars in posts (0/1)
 */
$feedexcludeforums = [];
$feednumtopics = 10;
$signaturesandavatars = 1;

// Signature and avatar settings
$maxsignaturesize = 350; // maximal signature size in chars
$maxavatarsize = 100000; // maximal avatar size in bytes
$maxavatarheight = 80; // maximal avatar height in pixels
$maxavatarwidth = 80;  // maximal avatar width in pixels
// Directory where avatars will be uploaded. Must end with '/'
$avatardirectory = './avatars/';
// Outgoing links behavior: 0 = normal, 1 = add rel="nofollow"
$nofollow = 0;
?>