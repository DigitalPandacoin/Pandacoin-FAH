# Pandacoin Folding at Home #

Automated LAMP web application for monitoring a Folding @ Home team's folder stats and paying out Pandacoin rewards based on folding points.

### Status ###

This system has been running live for a couple years. Refinement of web content is the current focus, as live usage is monitored for long term testing.

### Requirements ###

Linux, MySql, PHP, a web server, and a Pandacoin wallet accessible via RPC.

The system makes use of Linux's cron scheduling system and a few command line features for executing scripts. Currently there is no support for alternatives, which leaves it dependent to Linux servers for now.

### Installation ###

Create a MySql database and user with privileges to it.

Prepare a Pandacoin client with Pandacoin.conf set to run an RPC server and note the connection details and credentials.

Copy all of the www folder to your published web server folder. 

In this www folder, copy ./include/Init.php.default to ./include/Init.php. Modify ./include/Init.php to set the wallet RPC and MySQL connection details and credentials.


Copy ./Install.php.default to ./Install.php. Modify ./Install.php to set the security password for executing the install script and the default web user credentials.

Prepare web server write permissions for the following files/folders relative to the PandacoinFah's www root.

 * ./admin/log
 * ./admin/log/archive
 * ./PublicData.json
 * ./PublicRoundData.json

Open ./Install.php in your browser. Enter the Installer password you configured and click install.

Disable ./Install.php from being accessed through your web server for security.

Open Admin.php in your browser. Enter the admin web user credentials you provided in the Install configuration.

In the "Users" admin section, change your administration user password, set your local time zone, and give yourself the desired privileges and/or create more administration users.