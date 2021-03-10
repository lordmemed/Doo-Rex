<?php

//print_r($_SERVER);

define('APP_MODE', 'debug'); //set web app mode debug or live

define('APP_HOST', /* $_SERVER['REQUEST_SCHEME']. */'://'.$_SERVER['HTTP_HOST']); //name/location/domain of www-hosting, default with http:// or https// or just the hostname if not specified
define('APP_BASE', ''); //default is empty ->'' if installed in www-root directory

define('APP_LOGO', 'favicon.png'); //the logo of app
define('APP_TITLE', 'Doo-Rex'); //the title of app
define('APP_SLOGAN', 'Collections of manga, by member for member.'); //the slogan of app
define('APP_DESC', 'Doo-Rex is the place to read various comic/manga/manhua/manhwa/doujinshi provided by members and for members.'); //the description of app

define('APP_ASSET', 'assets'); //where the asset files of the app is located
define('APP_THEMES', 'default'); //themes for the app, it is 'default' if not specified

define('APP_SERVER', $_SERVER['HTTP_HOST']); //default server name for app
define('APP_ICON', 'favicon.png'); //default favicon filename for app

define('APP_ADMIN', 'admin'); //default username for super admin access, the password setup in installer
define('APP_OWNER', 'Mz Admin'); //default name for the owner of app
define('APP_EMAIL', 'admin@mail.localo.id'); //contact email address for app super admin

?>