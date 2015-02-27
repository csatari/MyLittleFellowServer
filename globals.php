<?php
global $table;

global $tilesize;
//user tábla
global $table_user_title;
global $table_user_id;
global $table_user_username;
global $table_user_password;
global $table_user_email;
global $table_user_sessionid;
global $table_user_sessionidExpiration;
//knownTiles tábla
global $table_knowntiles_title;
global $table_knowntiles_userid;
global $table_knowntiles_tileid;
global $table_knowntiles_examined;
//tile tábla
global $table_tile_title;
global $table_tile_id;
global $table_tile_lat;
global $table_tile_long;
global $table_tile_type;
global $table_tile_resource1;
global $table_tile_resource2;
global $table_tile_resource3;
global $table_tile_owner;
global $table_tile_tax;
global $other_tile_population;
//userdetails tábla
global $table_userdetails_title;
global $table_userdetails_userid;
global $table_userdetails_placeid;
global $table_userdetails_newplaceid;
global $table_userdetails_storage;
global $table_userdetails_storagelimit;
global $table_userdetails_homeid;
global $table_userdetails_ipo;
global $table_userdetails_buildingdeveloped;
global $table_userdetails_tooldeveloped;
global $table_userdetails_tax;
global $table_userdetails_taxtime;
global $return_userdetails_time;
global $return_userdetails_oldtime;
global $return_userdetails_goal;
global $return_userdetails_goal2;
//timedaction tábla
global $table_timedaction_title;
global $table_timedaction_userid;
global $table_timedaction_oldtime;
global $table_timedaction_newtime;
global $table_timedaction_actionid;
global $table_timedaction_goal;
global $table_timedaction_goal2;
//buildings tábla
global $table_buildings_title;
global $table_buildings_userid;
global $table_buildings_tileid;
global $table_buildings_tilesliceid;
global $table_buildings_buildingtype;
global $table_buildings_buildinglevel;
global $table_buildings_finished;
//buildingresources tábla
global $table_buildingresources_title;
global $table_buildingresources_id;
global $table_buildingresources_level;
global $table_buildingresources_resources;
global $table_buildingresources_name;
global $table_buildingresources_ipo;
global $table_buildingresources_minlevel;
//intelligencepoints tábla
global $table_ipo_title;
global $table_ipo_id;
global $table_ipo_lat;
global $table_ipo_lon;
global $table_ipo_farlat;
global $table_ipo_farlon;
global $table_ipo_name;
global $table_ipo_url;
global $other_ipo_known;
//knownintelligencepoints tábla
global $table_knownipo_title;
global $table_knownipo_userid;
global $table_knownipo_ipoid;
//toolresources tábla
global $table_toolresources_title;
global $table_toolresources_toolid;
global $table_toolresources_name;
global $table_toolresources_resources;
global $table_toolresources_ipo;
global $table_toolresources_minlevel;
//crash tábla
global $table_crash_title;
global $table_crash_crash;

//egyéb
$tilesize = 0.005;
$app_version = 6;
//user tábla
$table_user_title = "user";
$table_user_id = "id";
$table_user_username = "username";
$table_user_password = "password";
$table_user_email = "email";
$table_user_sessionid = "sessionid";
$table_user_sessionidExpiration = "sessionidExpiration";
//knownTiles tábla
$table_knowntiles_title = "knowntiles";
$table_knowntiles_userid = "userid";
$table_knowntiles_tileid = "tileid";
$table_knowntiles_examined = "examined";
//tile tábla
$table_tile_title = "tile";
$table_tile_id = "id";
$table_tile_lat = "latitude";
$table_tile_long = "longitude";
$table_tile_type = "type";
$table_tile_resource1 = "resource1";
$table_tile_resource2 = "resource2";
$table_tile_resource3 = "resource3";
$table_tile_owner = "owner";
$table_tile_lastgrown = "lastgrown";
$table_tile_tax = "tax";
$other_tile_population = "population";
//userdetails tábla
$table_userdetails_title = "userdetails";
$table_userdetails_userid = "userid";
$table_userdetails_placeid = "placeid";
$table_userdetails_newplaceid = "placeid_new";
$table_userdetails_storage = "storage";
$table_userdetails_storagelimit = "storagelimit";
$table_userdetails_homeid = "homeid";
$table_userdetails_type = "type";
$table_userdetails_ipo = "ipo";
$table_userdetails_buildingdeveloped = "buildingdeveloped";
$table_userdetails_tooldeveloped = "tooldeveloped";
$table_userdetails_tax = "taxresources";
$table_userdetails_taxtime = "taxpayed";
$return_userdetails_time = "time";
$return_userdetails_oldtime = "oldtime";
$return_userdetails_goal = "goal";
$return_userdetails_goal2 = "goal_2";

//timedaction tábla
$table_timedaction_title = "userdetails";
$table_timedaction_userid = "userid";
$table_timedaction_oldtime = "oldtime";
$table_timedaction_newtime = "newtime";
$table_timedaction_actionid = "actionid";
$table_timedaction_goal = "goal";
$table_timedaction_goal2 = "goal_2";
$return_timedaction_result = "result";
//building tábla
$table_buildings_title = "buildings";
$table_buildings_userid = "userid";
$table_buildings_tileid = "tileid";
$table_buildings_tilesliceid = "tilesliceid";
$table_buildings_buildingtype = "buildingtype";
$table_buildings_buildinglevel = "buildinglevel";
$table_buildings_finished = "finished";
//buildingresources tábla
$table_buildingresources_title = "buildingresources";
$table_buildingresources_id = "buildingid";
$table_buildingresources_level = "level";
$table_buildingresources_resources = "resources";
$table_buildingresources_name = "name";
$table_buildingresources_ipo = "ipo";
$table_buildingresources_minlevel = "minlevel";
//intelligencepoints tábla
$table_ipo_title = "intelligencepoints";
$table_ipo_id = "id";
$table_ipo_lat = "lat";
$table_ipo_lon = "lon";
$table_ipo_farlat = "farlat";
$table_ipo_farlon = "farlon";
$table_ipo_name = "name";
$table_ipo_url = "url";
$other_ipo_known = "known";
//knownintelligencepoints tábla
$table_knownipo_title = "knownintelligencepoints";
$table_knownipo_userid = "userid";
$table_knownipo_ipoid = "ipoid";
//toolresources tábla
$table_toolresources_title = "toolresources";
$table_toolresources_toolid = "toolid";
$table_toolresources_name = "name";
$table_toolresources_resources = "resources";
$table_toolresources_ipo = "ipo";
$table_toolresources_minlevel = "minlevel";
//crash tábla
$table_crash_title = "crash";
$table_crash_crash = "crash";



?>