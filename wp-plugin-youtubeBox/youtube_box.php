<?php
/**
 * Plugin Name: Youtube Box
 * Plugin URI: http://ondrejp.cz
 * Description: Boxík pro přidání videa z Youtubu na příspěvek
 * Version: 0.1a
 * Author: Ondřej Pešek
 * Author URI: http://ondrejp.cz
 * License: GPLv2
*/

require_once("functions.php");

add_action("add_meta_boxes", "youtube_box_add", 10, 1);

add_action("save_post", "youtube_box_save");

add_action("admin_menu", "youtube_box_page");

// pouze pokud je uživatel na stránce post.php s ?action=edit nebo na stránce s videi
if (($_GET["action"] == "edit" AND basename($_SERVER["PHP_SELF"], ".php") == "post") OR basename($_SERVER["PHP_SELF"], ".php") == "post-new" OR $_GET["page"] == "slug_yt_box_page") {
  add_action("admin_enqueue_scripts", "loadCssYT");
  add_action("admin_enqueue_scripts", "loadJSYT");
}

// přidání videí na konec příspěvků
add_filter("the_content", "contentAlterYT");
