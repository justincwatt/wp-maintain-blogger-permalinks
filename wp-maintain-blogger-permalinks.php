<?php
/*
Plugin Name: Maintain Blogger Permalinks
Version: 1.1
Plugin URI: http://justinsomnia.org/2006/10/maintain-permalinks-moving-from-blogger-to-wordpress/
Description: Update your newly imported Blogger posts with their old Blogger generated URL "slugs"
Author: Justin Watt
Author URI: http://justinsomnia.org/

1.1
Improved success and failure output
tested with wordpress v2.8.4 and Blogger circa September 2009

1.0
initial version

LICENSE

wp-maintain-blogger-permalinks.php
Copyright (C) 2009 Justin Watt
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
  print "<h2>Maintain Blogger Permalinks</h2>";


  if (isset($_POST['function'])) {
    print "<p><strong>Progress:</strong></p>";

    $meta_records = $wpdb->get_results("select * from $wpdb->postmeta where meta_key = 'blogger_permalink'");
    
    if (count($meta_records) == 0) {
      
      print "Sorry I couldn't find any blogger_permalink custom fields, did you run <a href='import.php'>the Blogger importer</a> yet?";

    } else {
      $results = '';
      foreach ($meta_records as $meta_record) {
        $blogger_permalink = $meta_record->meta_value;
        $matches = array();
        if (preg_match('#/[0-9]{4}/[0-9]{2}/(.*?)\.html$#', trim($blogger_permalink), $matches)) {
          $blogger_permalink = $matches[1];
          $sql = "update $wpdb->posts set post_name = '$blogger_permalink' WHERE ID = '$meta_record->post_id';";
          $results .= $sql . "\n";
          $wpdb->query($sql);
        }
      }
      print "Done!<br/>";

      print "<textarea rows='10' cols='80' wrap='off' readonly='readonly'>";
      print htmlspecialchars($results);
      print "</textarea>";
    }

  
  } else { 

    ?>
    
    <form action='' method='post'>
    <input type='submit' name='function' value='Maintain Blogger Permalinks' />
    </form>

    <?php
      
  }

  print "</div>";
}


add_action('admin_menu', 'manage_maintain_blogger_permalinks');
