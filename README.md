# ezForum
<img src="https://i.ibb.co/0VKTqDXx/ezforum.png" alt="ezforum" border="0" width="300">

**ezForum** is a simple forum script based on the discontinued `SEO-Board 1.1.0`, which was abandoned around the turn of 2012 and 2013.
This script is being developed for my own use and as a form of practice.

* [Webarchive: seo-board.com](https://web.archive.org/web/20131123042959/http://www.seo-board.com/)
* [Webarchive: Community](https://web.archive.org/web/20131124051954/http://forums.seo-board.com/)
* [Webarchive: Manual](https://web.archive.org/web/20131124051954/http://forums.seo-board.com/)

## System requirements

- Webserver with PHP >= `8.4`
- MySQL >= `8.4`
- Required PHP extension: `mysqli`. `zlib` is required only if gzip compression is enabled.

## Features

### General

- Written in PHP and uses MySQL as a back-end
- Does not take too many server resources
- PHP and HTML are separated for ultra easy customization
- Anti-spam protection. Admin can ban IP/IP ranges/Users/Emails
- Full moderator support
- Message preview; locked and sticky topics; new topics highlight
- Search Engine Friendly: comes with Apache mod_rewrite support; cookie-based sessions (no SessionIDs in the URLs)
- Multi-language pack support
- Support for Private, Read-Only, Post-Only, Guest, HTML-Allowed, Topic Creation-Time Sorted Forums
- Basic BBCode, Smilie, Signatures and Avatars support
- Forever 100% free for both personal and commercial use

## Installation

1. Create a MySQL database. Use the web based MySQL admin provided by your hosting provider.
Or use the plain SQL command: create database DatabaseName;
2. Unzip the ezForum zip file and upload all files and subdirectories.
3. Set up all options in the file `ez_options.php`. 
4. Run the installation script from your browser (install.php). Example: https://www.yoursite.com/forum/install.php
If you have set all user/password fields above correctly, you should get *Forum Installed Successfully!* message. If something is not configured, you'll get the specific MySQL error.
5. Delete `install.php` from your site! If you don't someone can run it.
6. Access your forum from a web browser. Use the admin username and password to login. You will see a link to the admin panel.
