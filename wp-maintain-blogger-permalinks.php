<?php
/*
Plugin Name: Maintain Blogger Permalinks
Version: 2.0
Plugin URI: http://justinsomnia.org/2006/10/maintain-permalinks-moving-from-blogger-to-wordpress/
Description: Update your newly imported Blogger posts with their old Blogger generated URL "slugs". This is a utility plugin that only needs to be run once. After than you can deactivate and delete it.
Author: Justin Watt
Author URI: http://justinsomnia.org/

2.0
Add fallback algorithm to generate Blogger-link permalink in the absense of an import-derived meta_key

1.1
Improved success and failure output
tested with wordpress v2.8.4 and Blogger circa September 2009

1.0
initial version

LICENSE

wp-maintain-blogger-permalinks.php
Copyright (C) 2012 Justin Watt
justincwatt@gmail.com
http://justinsomnia.org/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

function manage_maintain_blogger_permalinks() {
  // Add a new menu under Manage:
  add_management_page('Maintain Blogger Permalinks', 'Maintain Blogger Permalinks', 10, __FILE__, 'maintain_blogger_permalinks');
}

function maintain_blogger_permalinks() 
{
  global $wpdb;

  print "<div class='wrap'>";
  print "<div class='icon32' id='icon-tools'><br/></div><h2>Maintain Blogger Permalinks</h2>";

  if (isset($_POST['function'])) {
    $records = $wpdb->get_results("select ID, post_title, post_name from $wpdb->posts where post_type='post' and post_status = 'publish'");

    if (count($records) == 0) {
      print "Sorry I couldn't find any posts to fix";

    } else {
      $results = '';
      foreach ($records as $record) {
        
        // first, lets look for a blogger_permalink meta_key for this post
        $meta_records = $wpdb->get_results("select * from $wpdb->postmeta where meta_key = 'blogger_permalink' and post_id = $record->ID");
        if (count($meta_records) == 1) {
          $matches = array();
          if (preg_match('#/[0-9]{4}/[0-9]{2}/(.*?)\.html$#', trim($meta_records[0]->meta_value), $matches)) {
            $blogger_permalink = $matches[1];
          }
        
        // if we didn't find a blogger_permalink meta_key, fallback on our best-effort algorithm
        } else {
          $blogger_permalink = title_to_blogger_style_permalink($record->post_title);
        }
        
        if ($record->post_name != $blogger_permalink && $blogger_permalink != '') {
        
          $sql = "update $wpdb->posts set post_name = '$blogger_permalink' WHERE ID = '$record->ID';";
          $wpdb->query($sql); 
          $results .= "Updated \"$record->post_name\" to \"$blogger_permalink\"<br/>";
        }       
      }      
      
      print "<strong>Done!</strong> This is what happened:<br/>";
      //print "<textarea rows='10' cols='80' wrap='off' readonly='readonly'>";
      print $results;
      //print "</textarea>";
    }

  
  } else { 

    ?>
    
    <form action='' method='post'>
    <input type='submit' class='button' name='function' value='Maintain Blogger Permalinks' />
    </form>

    <?php
      
  }

  print "</div>";
}


function title_to_blogger_style_permalink($title) 
{
  $blogger_permalink = $title;
  
  //strip potential entities (overzealously)
  $blogger_permalink = preg_replace("/&[^ ]+/", "", $blogger_permalink);
  
  //replace potential tags with a space
  $blogger_permalink = preg_replace("/<\/?[^>]>/", " ", $blogger_permalink);
  
  //replace dashes with spaces
  $blogger_permalink = str_replace("-", " ", $blogger_permalink);
  
  //replace latin characters with ascii equivalents
  //technically this should be date dependent, older versions of Blogger stripped these characters
  $blogger_permalink = remove_accents($blogger_permalink);

  //strip out non a-z, 0-9 (over simplification, they latinize accented chars now)
  $blogger_permalink = preg_replace("/[^a-zA-Z0-9 ]/", "", $blogger_permalink);

  //lowercase everything
  $blogger_permalink = strtolower($blogger_permalink);

  //compress whitespace
  $blogger_permalink = preg_replace("/\s+/", " ", $blogger_permalink);

  //trim
  $blogger_permalink = trim($blogger_permalink);
  
  //split into words
  $blogger_permalink = explode(" ", $blogger_permalink);

  //remove a, an, the
  $temp = array();
  foreach ($blogger_permalink as $key => $value) {
    if ($value != 'the' && $value != 'a' && $value != 'an') {
      $temp[] = $value;
    }
  }
  $blogger_permalink = $temp;

  //truncate before word that crosses boundary of 39th character!
  $character_count = 0;
  foreach ($blogger_permalink as $key => $value) {
    
    $word_length = strlen($value);
    
    //if first word is longer than 39 characters, truncate
    if ($key == 0 && $word_length > 39) {
        $word = substr($value, 0, 39);
        $blogger_permalink = array($word);
        break;
    }   

    $character_count += $word_length;
    
    if ($character_count < 39) {
      $character_count++; // add count for space
    } elseif ($character_count == 39) {
      $blogger_permalink = array_slice($blogger_permalink, 0, $key+1);
      break;
    } else { // we've gone over
      $blogger_permalink = array_slice($blogger_permalink, 0, $key);
      break;
    }
  }
  
  // put the title back together
  $blogger_permalink = implode(" ", $blogger_permalink);

  //replace spaces with dashes
  $blogger_permalink = str_replace(" ", "-", $blogger_permalink);

  return $blogger_permalink;
}


add_action('admin_menu', 'manage_maintain_blogger_permalinks');
