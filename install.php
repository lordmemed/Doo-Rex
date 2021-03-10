<?php

require_once 'autoloader.php';

/**
 * Miscellenaous functions
**/

function remove_newline(string $input):string {
	return trim(str_replace("\r\n", ' ', $input));
}

function sequence_string(string $input, string $delimiter):array {
	$output = explode($delimiter, $input);
	//$output = implode(" ", $output);
	
	return $output;
}
//print_r(sequence_string(drop_tables(), "\r\n"));


/**
 * Installer
**/

function drop_tables(){
return "
DROP TABLE IF EXISTS `member_stat`;
DROP TABLE IF EXISTS `group_stat`;
DROP TABLE IF EXISTS `manga_stat`;
DROP TABLE IF EXISTS `group`;
DROP TABLE IF EXISTS `chapter_stat`;
DROP TABLE IF EXISTS `member`;
DROP TABLE IF EXISTS `manga`;
DROP TABLE IF EXISTS `chapter`;
";
}

function table_manga(){
return "
CREATE TABLE IF NOT EXISTS `manga` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    `url` TEXT NOT NULL UNIQUE,
    `lang` TEXT NOT NULL,
    `cover` TEXT NOT NULL,
    `title` TEXT NOT NULL,
    `alt_title` TEXT,
    `artist` TEXT DEFAULT 'unknown',
    `author` TEXT DEFAULT 'unknown',
    `type` TEXT CHECK(`type` IN ('comic', 'manga', 'manhua', 'manhwa', 'webcomic')) DEFAULT manga,
    `rate` TEXT CHECK(`rate` IN ('e', 'r13', 'r15', 'r18')) DEFAULT e,
    `status` TEXT CHECK(`status` IN ('unknown', 'ongoing', 'completed')) DEFAULT unknown,
    `read_mode` TEXT CHECK(`read_mode` IN ('hr', 'hl', 'v', 'w')) DEFAULT hl,
    `uploader_type` TEXT CHECK(`uploader_type` IN ('member', 'group')) DEFAULT member,
    `uploader_id` INTEGER NOT NULL,
    `upload_date` TEXT NOT NULL
);
";
}

function table_chapter(){
return "
CREATE TABLE IF NOT EXISTS `chapter` (
    `mid` INTEGER NOT NULL,
    `cid` INTEGER NOT NULL,
    `url` TEXT PRIMARY KEY NOT NULL UNIQUE,
    `title` TEXT NOT NULL,
    `upload_date` DATETIME NOT NULL,
    FOREIGN KEY (`mid`) REFERENCES `manga`(`id`)
)WITHOUT ROWID;
";
}

function table_member(){
return "
CREATE TABLE IF NOT EXISTS `member` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    `username` TEXT NOT NULL UNIQUE,
    `password` TEXT NOT NULL,
    `email` TEXT NOT NULL,
    `nickname` TEXT NOT NULL,
    `fullname` TEXT NOT NULL,
    `description` TEXT,
    `birth_date` DATETIME NOT NULL,
    `join_date` DATETIME NOT NULL
);
";
}

function table_group(){
return "
CREATE TABLE IF NOT EXISTS `group` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    `owner` INTEGER NOT NULL UNIQUE,
    `name` TEXT NOT NULL,
    `description` TEXT,
    `created_at` DATETIME NOT NULL,
    FOREIGN KEY (`owner`) REFERENCES `member`(`id`)
);
";
}

function table_manga_stat(){
return "
CREATE TABLE IF NOT EXISTS `manga_stat` (
    `mid` INTEGER PRIMARY KEY NOT NULL UNIQUE,
    `rating` INTEGER NOT NULL,
    `view` INTEGER NOT NULL,
    FOREIGN KEY (`mid`) REFERENCES `manga`(`id`)
)WITHOUT ROWID;
";
}

function table_chapter_stat(){
return "
CREATE TABLE IF NOT EXISTS `chapter_stat` (
	`id` INTEGER PRIMARY KEY,
    `mid` INTEGER NOT NULL,
    `cid` INTEGER NOT NULL,
    `view` INTEGER,
    FOREIGN KEY (`mid`) REFERENCES `manga`(`id`),
    FOREIGN KEY (`cid`) REFERENCES `chapter`(`id`)
)WITHOUT ROWID;
";
}
function table_member_stat(){
return "
CREATE TABLE IF NOT EXISTS `member_stat` (
    `uid` INTEGER PRIMARY KEY NOT NULL,
    `gid` INTEGER NOT NULL,
    `last_login` DATETIME NOT NULL,
    FOREIGN KEY (`uid`) REFERENCES `member`(`id`),
    FOREIGN KEY (`gid`) REFERENCES `group`(`id`)
)WITHOUT ROWID;
";
}

function table_group_stat(){
return "
CREATE TABLE IF NOT EXISTS `group_stat` (
    `uid` INTEGER NOT NULL,
    `gid` INTEGER PRIMARY KEY NOT NULL,
    FOREIGN KEY (`uid`) REFERENCES `member`(`id`),
    FOREIGN KEY (`gid`) REFERENCES `group`(`id`)
)WITHOUT ROWID;
";
}

function insert_data(){
return "
INSERT INTO `member` VALUES (`admin`,`123456`,`admin123456@gmail.com`,`Administrator`,`Hasan Al-Rauf`,`Deskripsi`,`15-10-1996`,`19-09-2020`);
";
}

// init install
use MangaReader\Database;

$db = new Database;
$db->connect('sqlite', 'mangareader'); //driver, dbname, dbhost, dbuser, dbpasswd

$db->query_sql( remove_newline(drop_tables()) ); //drop tables first/flush db
$db->query_sql( remove_newline(table_manga()) ); //CREATE TABLE IF NOT EXISTS manga
$db->query_sql( remove_newline(table_chapter()) ); //CREATE TABLE IF NOT EXISTS chapter
$db->query_sql( remove_newline(table_member()) ); //CREATE TABLE IF NOT EXISTS member
$db->query_sql( remove_newline(table_group()) ); //CREATE TABLE IF NOT EXISTS group
$db->query_sql( remove_newline(table_manga_stat()) ); //CREATE TABLE IF NOT EXISTS manga stats
$db->query_sql( remove_newline(table_chapter_stat()) ); //CREATE TABLE IF NOT EXISTS chapter stats
$db->query_sql( remove_newline(table_member_stat()) ); //CREATE TABLE IF NOT EXISTS member stats
$db->query_sql( remove_newline(table_group_stat()) ); //CREATE TABLE IF NOT EXISTS group stats
//$db->query_sql(insert_data()); //insert initial data to member table

echo $db->get_message();