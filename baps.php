<?php

/**
 * Plugin Name: BEST Application System
 * Description: Application System for beWANTED and CO
 * Version: 0.1
 * Author: Franz Papst
 * Author URI: http://www.bestvienna.at
 * License: MIT
 */

# Change this to True if you want to initialize new database data when the plugin is activated
# This includes adding companies, timeslots and study fields
# But it will not delete existing data, instead only append to the tables, so you may have duplicates if there is already data in the tables
# So if you want to delete all data, you have to do this manually via phpMyAdmin
# Change this to False if you don't want to initialize new data
$bool_initialize_db = False;

require("baps-admin.php");
require("baps-ui.php");

add_action("admin_menu", "baps_menu");
add_action('init', 'baps_init');

function baps_menu()
{
  add_menu_page("BEST Application System", "BEST Application System", "publish_posts", "baps-admin", "baps_admin_page");
  add_submenu_page("baps-admin", "Export CVs", "Export CVs", "activate_plugins", "baps_export", "baps_export_page");
  add_submenu_page("baps-admin", "Companies", "Companies", "activate_plugins", "baps_edit_companies", "baps_edit_companies");
  //    add_submenu_page("baps-admin", "Timeslots", "Timeslots", "activate_plugins", "baps_edit_timeslots", "baps_edit_timeslots");
  // add_submenu_page("baps-admin", "Settings", "Settings", "activate_plugins", "baps_settings", "baps_settings_page");
  // add_submenu_page("applications", "Settings", "Settings", "activate_plugins", "applications_settings", "aps_settings_page");
  // add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', int $position = null )
  // add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', string $icon_url = '', int $position = null )
}

function baps_init()
{
}

function baps_activation()
{

global $wpdb;
$wp = $wpdb->prefix;

global $bool_initialize_db;
if ($bool_initialize_db) {

  //   $sql = "INSERT INTO `wp_baps_companies` (`id`, `name`) VALUES 
  // (NULL, 'BearingPoint'), (NULL, 'DXC'), (NULL, 'Erste'), 
  // (NULL, 'OeBB'), (NULL, 'Siemens Mobility'), (NULL, 'STEINER-HITECH'), 
  // (NULL, 'Wiener Stadtwerke'), (NULL, 'WKO')";

    /*
  $sql = "INSERT INTO `wp_baps_timeslots_companies` (`id`, `company_id`, `timeslot_id`) VALUES  
  (NULL, '1', '1'), (NULL, '1', '2'), (NULL, '1', '3'), (NULL, '1', '4'), (NULL, '1', '5'), (NULL, '1', '6'),
  (NULL, '1', '7'), (NULL, '1', '8'), (NULL, '1', '9'), (NULL, '1', '10'),
  (NULL, '2', '1'), (NULL, '2', '2'), (NULL, '2', '3'), (NULL, '2', '4'), (NULL, '2', '5'), (NULL, '2', '6'),
  (NULL, '2', '7'), (NULL, '2', '8'), (NULL, '2', '9'), (NULL, '2', '10'),
  (NULL, '3', '1'), (NULL, '3', '2'), (NULL, '3', '3'), (NULL, '3', '4'), (NULL, '3', '5'), (NULL, '3', '6'),
  (NULL, '3', '7'), (NULL, '3', '8'), (NULL, '3', '9'), (NULL, '3', '10'),
  (NULL, '4', '1'), (NULL, '4', '2'), (NULL, '4', '3'), (NULL, '4', '4'), (NULL, '4', '5'), (NULL, '4', '6'), 
  (NULL, '4', '7'), (NULL, '4', '8'), (NULL, '4', '9'), (NULL, '4', '10')";


  $sql = "INSERT INTO `wp_baps_study_fields` (`id`, `name`) VALUES 
  (NULL, 'Architektur'), (NULL, 'Bauingenieurwesen'), (NULL, 'Biomedical Engineering'), (NULL, 'Computational Science and Engineering'),
  (NULL, 'Elektrotechnik'), (NULL, 'Geodäsie und Geoinformation'), (NULL, 'Informatik'), (NULL, 'Maschinenbau'), 
  (NULL, 'Materialwissenschaften'), (NULL, 'Raumplanung und Raumordnung'), (NULL, 'Technische Mathematik'),
  (NULL, 'Technische Physik'), (NULL, 'Umweltingenieurwesen'), (NULL, 'Verfahrenstechnik'), (NULL, 'Wirtschaftsingenieurwesen - Maschinenbau')";

  $sql = "INSERT INTO `wp_baps_timeslots` (`id`, `slot`) VALUES
  (NULL, '09:30'), (NULL, '10:00'), (NULL, '10:30'), (NULL, '11:30'), (NULL, '12:00'), (NULL, '13:00'),
  (NULL, '13:30'), (NULL, '14:00')";
  */


  $query = "CREATE TABLE IF NOT EXISTS`{$wp}baps_companies` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(255),
      PRIMARY KEY (`id`),
      UNIQUE (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;";
  $wpdb->query($query);

  $query = "CREATE TABLE IF NOT EXISTS`{$wp}baps_timeslots` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `slot` varchar(30),
      PRIMARY KEY (`id`),
      UNIQUE (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;";
  $wpdb->query($query);

  $query = "CREATE TABLE IF NOT EXISTS`{$wp}baps_timeslots_companies` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `company_id` int(11),
    `timeslot_id` int(11),
    UNIQUE (id),
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;";
  $wpdb->query($query);

  $query = "CREATE TABLE IF NOT EXISTS `{$wp}baps_study_fields` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `field` varchar(255),
    UNIQUE (id),
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;";
  $wpdb->query($query);


  $query = "CREATE TABLE IF NOT EXISTS`{$wp}baps_applicants` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255),
    `email` varchar(255),
    `phone` varchar(50),
    `student_id` varchar(255),
    `uuid` varchar(50),
    `study_field` int(11),
    `semester` varchar(10),
    PRIMARY KEY (`id`),
    UNIQUE (id, uuid),
    CONSTRAINT fk_study_field FOREIGN KEY (study_field) REFERENCES {$wp}baps_study_fields(id) ON UPDATE CASCADE ON DELETE RESTRICT
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;";
  $wpdb->query($query);

  $query = "CREATE TABLE IF NOT EXISTS `{$wp}baps_timeslots_applicants` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `applicant_id` int(11),
      `company_id` int(11),
      `timeslot_id` int(11),
      `timestamp` timestamp,
      UNIQUE (id),
      PRIMARY KEY (`id`),
      CONSTRAINT fk_applicant_id FOREIGN KEY (applicant_id) REFERENCES {$wp}baps_applicants(id) ON UPDATE CASCADE ON DELETE RESTRICT,
      CONSTRAINT fk_company_id FOREIGN KEY (company_id) REFERENCES {$wp}baps_companies(id) ON UPDATE CASCADE ON DELETE RESTRICT,
      CONSTRAINT fk_timeslot FOREIGN KEY (timeslot_id) REFERENCES {$wp}baps_timeslots(id) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;";
  echo ($query);
  $wpdb->query($query);


  $query = "INSERT INTO {$wp}baps_study_fields (id, field) VALUES 
      (NULL, 'Architektur'), 
      (NULL, 'Bauingenieurwesen'), 
      (NULL, 'Biomedical Engineering'), 
      (NULL, 'Computational Science and Engineering'), 
      (NULL, 'Elektrotechnik'), 
      (NULL, 'Geodäsie und Geoinformation'), 
      (NULL, 'Informatik'), 
      (NULL, 'Maschinenbau'), 
      (NULL, 'Materialwissenschaften'), 
      (NULL, 'Raumplanung und Raumordnung'), 
      (NULL, 'Technische Mathematik'), 
      (NULL, 'Technische Physik'), 
      (NULL, 'Umweltingenieurwesen'), 
      (NULL, 'Verfahrenstechnik'), 
      (NULL, 'Wirtschaftsingenieurwesen - Maschinenbau'),
      (NULL, 'Sonstige')";
  $wpdb->query($query);

  // TODO: diese 3 INSERTs nicht hardcoden sondern sachen im Backend dafür schreiben
  $query = "INSERT IGNORE INTO {$wp}baps_companies (id, name) VALUES 
      (NULL, 'BearingPoint'), (NULL, 'DXC'), (NULL, 'Erste'), 
      (NULL, 'OeBB'), (NULL, 'Siemens Mobility'), (NULL, 'STEINER-HITECH'), 
      (NULL, 'Wiener Stadtwerke'), (NULL, 'WKO')";
  $wpdb->query($query);

  $query = "INSERT IGNORE INTO {$wp}baps_timeslots (`id`, `slot`) VALUES
	  (NULL, 'Mi. 29.3. 09:00'),
    (NULL, 'Mi. 29.3. 09:30'),
    (NULL, 'Mi. 29.3. 10:00'),
    (NULL, 'Mi. 29.3. 10:30'),
    (NULL, 'Mi. 29.3. 11:00'),
    (NULL, 'Mi. 29.3. 11:30'),
    (NULL, 'Mi. 29.3. 14:00'),
    (NULL, 'Mi. 29.3. 14:30'),
    (NULL, 'Mi. 29.3. 15:00'),
    (NULL, 'Mi. 29.3. 15:30'),
    (NULL, 'Mi. 29.3. 16:00'),
    (NULL, 'Mi. 29.3. 16:30')";
  $wpdb->query($query);


  $query = "SELECT id, name FROM {$wp}baps_companies";
  $wpdb->query($query);
  $companies = $wpdb->get_results($query);
  foreach ($companies as $c) {
    // Silver morning
    if ($c->name == 'WKO')
      $query = "SELECT id FROM {$wp}baps_timeslots WHERE `slot` IN (
              'Mi. 29.3. 09:00',
              'Mi. 29.3. 09:30',
              'Mi. 29.3. 10:00',
              'Mi. 29.3. 10:30',
              'Mi. 29.3. 11:00',
              'Mi. 29.3. 11:30'
            )";
    // Silver afternoon
    elseif ($c->name == 'Erste')
      $query = "SELECT id FROM {$wp}baps_timeslots WHERE `slot` IN (
              'Mi. 29.3. 14:00',
              'Mi. 29.3. 14:30',
              'Mi. 29.3. 15:00',
              'Mi. 29.3. 15:30',
              'Mi. 29.3. 16:00',
              'Mi. 29.3. 16:30'
            )";
    // Gold
    elseif ($c->name == 'BearingPoint')
      $query = "SELECT id FROM {$wp}baps_timeslots WHERE `slot` IN (
              'Mi. 29.3. 09:00',
              'Mi. 29.3. 09:30',
              'Mi. 29.3. 10:00',
              'Mi. 29.3. 10:30',
              'Mi. 29.3. 11:00',
              'Mi. 29.3. 11:30',
              'Mi. 29.3. 14:00',
              'Mi. 29.3. 14:30',
              'Mi. 29.3. 15:00',
              'Mi. 29.3. 15:30',
              'Mi. 29.3. 16:00',
              'Mi. 29.3. 16:30'
			  )";
    elseif ($c->name == 'DXC')
      $query = "SELECT id FROM {$wp}baps_timeslots WHERE `slot` IN (
              'Mi. 29.3. 09:00',
              'Mi. 29.3. 09:30',
              'Mi. 29.3. 10:00',
              'Mi. 29.3. 10:30',
              'Mi. 29.3. 11:00',
              'Mi. 29.3. 11:30',
              'Mi. 29.3. 14:00',
              'Mi. 29.3. 14:30',
              'Mi. 29.3. 15:00',
              'Mi. 29.3. 15:30',
              'Mi. 29.3. 16:00',
              'Mi. 29.3. 16:30'
			  )";
    elseif ($c->name == 'OeBB')
      $query = "SELECT id FROM {$wp}baps_timeslots WHERE `slot` IN (
              'Mi. 29.3. 09:00',
              'Mi. 29.3. 09:30',
              'Mi. 29.3. 10:00',
              'Mi. 29.3. 10:30',
              'Mi. 29.3. 11:00',
              'Mi. 29.3. 11:30',
              'Mi. 29.3. 14:00',
              'Mi. 29.3. 14:30',
              'Mi. 29.3. 15:00',
              'Mi. 29.3. 15:30',
              'Mi. 29.3. 16:00',
              'Mi. 29.3. 16:30'
			  )";
    elseif ($c->name == 'Siemens Mobility')
      $query = "SELECT id FROM {$wp}baps_timeslots WHERE `slot` IN (
              'Mi. 29.3. 09:00',
              'Mi. 29.3. 09:30',
              'Mi. 29.3. 10:00',
              'Mi. 29.3. 10:30',
              'Mi. 29.3. 11:00',
              'Mi. 29.3. 11:30',
              'Mi. 29.3. 14:00',
              'Mi. 29.3. 14:30',
              'Mi. 29.3. 15:00',
              'Mi. 29.3. 15:30',
              'Mi. 29.3. 16:00',
              'Mi. 29.3. 16:30'
			  )";
    elseif ($c->name == 'Wiener Stadtwerke')
    $query = "SELECT id FROM {$wp}baps_timeslots WHERE `slot` IN (
            'Mi. 29.3. 09:00',
            'Mi. 29.3. 09:30',
            'Mi. 29.3. 10:00',
            'Mi. 29.3. 10:30',
            'Mi. 29.3. 11:00',
            'Mi. 29.3. 11:30',
            'Mi. 29.3. 14:00',
            'Mi. 29.3. 14:30',
            'Mi. 29.3. 15:00',
            'Mi. 29.3. 15:30',
            'Mi. 29.3. 16:00',
            'Mi. 29.3. 16:30'
      )";
    elseif ($c->name == 'WKO')
    $query = "SELECT id FROM {$wp}baps_timeslots WHERE `slot` IN (
            'Mi. 29.3. 09:00',
            'Mi. 29.3. 09:30',
            'Mi. 29.3. 10:00',
            'Mi. 29.3. 10:30',
            'Mi. 29.3. 11:00',
            'Mi. 29.3. 11:30',
            'Mi. 29.3. 14:00',
            'Mi. 29.3. 14:30',
            'Mi. 29.3. 15:00',
            'Mi. 29.3. 15:30',
            'Mi. 29.3. 16:00',
            'Mi. 29.3. 16:30'
      )";
    $slots = $wpdb->get_results($query);
    $query2 = "INSERT INTO {$wp}baps_timeslots_companies (id, company_id, timeslot_id) VALUES ";
    foreach ($slots as $s) {
      $query2 = $query2 . sprintf('(NULL, "%d", "%d"),', $c->id, $s->id);
    }
    $query2 = substr($query2, 0, -1);
    $wpdb->query($query2);
  }

  if (!is_dir(BAPS_UPLOAD_DIR)) {
    mkdir(BAPS_UPLOAD_DIR);
  }

}
}

function baps_deactivation()
{
}

register_activation_hook(__FILE__, 'baps_activation');
register_deactivation_hook(__FILE__, 'baps_deactivation');
