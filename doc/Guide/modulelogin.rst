############
Login Module
############

The login module is used by sites that provide protected or personalized experience for their modules.
It provides a unified interface to log into the site. 

=============
Configuration
=============

There are several configuration values that affect the display of the login module. They are all
in the *strings* section of *SITE_DIR/config/login/module.ini*

* *LOGIN_MESSAGE* - A message shown at the header of the login page
* *LOGIN_LABEL* - The label used for the user name field of the login form. This only shows up for logins to direct authorities.
* *PASSWORD_LABEL* - The label used for the password field of the login form. This only shows up for logins to direct authorities.
* *FORGET_PASSWORD_URL* - If specified, a url that is included in the footer of the login form. This only shows up for logins to direct authorities.
* *FORGET_PASSWORD_TEXT* - If specified, text that is included in the footer of the login form. This only shows up for logins to direct authorities.


