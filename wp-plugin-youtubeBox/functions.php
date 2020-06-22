<?php

// přidání odkazu na stránku do menu
function youtube_box_page() {
  $title = "Youtube Box";
  $user = "administrator";

  add_menu_page($title, $title, $user, "slug_yt_box_page", "youtube_box_page_content", "dashicons-video-alt3");
}

// přidání boxu s inputy na editovací stránku
function youtube_box_add() {
  add_meta_box(
    'my-meta-box',
    __('Odkaz na YT videa'),
    'youtube_box_render',
    'post',
    'side',
    'default'
  );
}

// obsah boxu, který se přidává na editovací stránku
function youtube_box_render($post) {
  echo("<div id='youtube_box_inputs'>");
  echo("<p>Vložit videa jako:</p>");
  $type = get_post_meta($post->ID, 'yt_link_type')[0];

  if ($type == "playlist") {
    echo("<label><input type='radio' name='yt_link_type' value='single'>Jednotlivá videa</label><br>");
    echo("<label><input type='radio' name='yt_link_type' value='playlist' checked>Playlist</label><br>");
  } else {
    echo("<label><input type='radio' name='yt_link_type' value='single' checked>Jednotlivá videa</label><br>");
    echo("<label><input type='radio' name='yt_link_type' value='playlist'>Playlist</label><br>");
  }

  echo("<button id='addInputYT'><span class='dashicons dashicons-plus-alt2'></span></button>");
  echo("<button id='removeInputYT'><span class='dashicons dashicons-minus'></span></button>");

  echo("<div>");
  $links = get_post_meta($post->ID, 'yt_links', false)[0];
  $links = explode(";", $links);
  $i = 0;
  foreach ($links as $key => $value) {
    if (isset($value) AND !empty($value)) {
      echo("<div data-remove>");
      echo("<input type='url' name='yt_links[]' placeholder='https://www.youtube.com/watch?v=123456' value='"."https://www.youtube.com/watch?v=".$value."'>");
      echo("</div>");
      $i++;
    }
  }
  if ($i < 5) {
    echo("<div data-remove><input type='url' name='yt_links[]' placeholder='https://www.youtube.com/watch?v=123456' value=''></div>");
  } else {
    echo("<script>addButton.disabled = true;</script>");
  }
  echo("</div></div>");
}

// funkce, která se provádí při uložení/aktualizování článku - kontrola, ukládání do DB
function youtube_box_save($post_ID) {
  global $wpdb;
  $links = $_POST["yt_links"];

  $matches = [];
  if (isset($links) AND !empty($links)) {
    foreach ($links as $key => $value) {
      // regex z https://gist.github.com/rodrigoborgesdeoliveira/987683cfbfcc8d800192da1e73adc486#gistcomment-3263133
      $pattern = '/(?:\/|%3D|v=|vi=)([0-9A-z-_]{11})(?:[%#?&]|$)/';
      preg_match($pattern, $value, $match);

      // pokud není url validní, neuloží se do proměnné
      if (isset($match) AND !empty($match)) {
        $matches[] = $match[1];
      }
      unset($match);
    }
  }

  if (is_array($matches) AND !empty($matches)) {
    // vymaže z arraye duplicitní záznamy
    $matches = array_unique($matches);

    $type = $_POST["yt_link_type"];
    if ($type == "playlist") {
      update_post_meta($post_ID, "yt_link_type", $type);

    } else {
      // žádný záznam = list videí pod sebou
      delete_post_meta($post_ID, "yt_link_type");
    }

    $result = implode(";", $matches);
    update_post_meta($post_ID, "yt_links", $result);

  } else {
    delete_post_meta($post_ID, "yt_links");

    // žádný záznam = list videí pod sebou
    delete_post_meta($post_ID, "yt_link_type");
  }

}

function loadCssYT() {
  wp_enqueue_style("yt_box_style", plugins_url()."/youtube-box/style.css", false);
}

function loadJSYT() {
  wp_enqueue_script("yt_box_js", plugins_url()."/youtube-box/main.js", false);
}

function contentAlterYT($content) {
  if (get_post_type() != "post" OR !is_single()) {
    return($content);
  }

  $postID = get_the_ID();

  $links = get_post_meta($postID, 'yt_links')[0];
  // video(a) nebo playlist
  $type = get_post_meta($postID, 'yt_link_type')[0];

  $links = explode(";", $links);

  // vymaže z arraye duplicitní záznamy
  $linksUnique = array_unique($links);

  $linksFinal = [];
  foreach ($linksUnique as $key => $value) {
    if (isset($value) AND !empty($value)) {
      $linksFinal[] = $value;
    }
  }

  $iframe = "";
  if (count($linksFinal) > 1 AND $type == "playlist") {
    $playlist = implode(",", $linksFinal);

    $iframe = '<iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/?playlist='.$playlist.'" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
  } else {
    foreach ($linksFinal as $key => $value) {
      $iframe .= '<iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/'.$value.'" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    }
  }

  return($content.$iframe);
}

function youtube_box_page_content() {
  global $wpdb;

  // dotaz na získání dat k příspěvkům - ID, přezdívka autora, status, datum zveřejnění a titulek
  $sqlQuery = "
  SELECT p.*, u.user_nicename as author FROM
  (
    SELECT p.ID, p.post_author as authorID, p.post_title as title, p.post_status as status, p.post_date, pm.meta_value as yt_links
    FROM wp_posts as p
    INNER JOIN wp_postmeta as pm ON pm.post_id = p.ID WHERE pm.meta_key = 'yt_links'
  ) as p
  INNER JOIN wp_users as u ON u.ID = p.authorID
  ";
  $sql = $wpdb->prepare($sqlQuery);
  $sqlResult = $wpdb->get_results($sql);
  if (!isset($sqlResult) OR empty($sqlResult)) {
    echo("<p>Na této stránce se zobrazují příspěvky, u kterých jsou připnuta Youtube videa.<br>Žádné takové příspěvky zatím nemáte.</p>");
    return;
  }
  echo("<div id='youtube_box_page'>");
  echo("<table>");
  echo("
    <thead>
      <tr>
        <td colspan='5'>Příspěvky, u kterých jsou připnuta Youtube videa</td>
      </tr>
      <tr>
        <th>ID příspěvku</th>
        <th>Autor</th>
        <th>Status</th>
        <th>Datum zveřejnění</th>
        <th>Titulek</th>
      </tr>
    </thead>
  ");
  foreach ($sqlResult as $key => $value) {
    $value = (array) $value;

    echo("
      <tr>
        <td>
          {$value['ID']}
        </td>
        <td>
          {$value['author']}
        </td>
        <td>
          {$value['status']}
        </td>
        <td>
          {$value['post_date']}
        </td>
        <td>
          <a href='".get_edit_post_link($value['ID'])."'>{$value['title']}</a>
        </td>
    ");
  }
  echo("</table></div>");
}
