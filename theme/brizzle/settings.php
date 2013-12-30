<?php
 
/**
 * Theme Settings
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
 
// Logo file setting
$name = 'theme_brizzle/logo';
$title = get_string('logo','theme_brizzle');
$description = get_string('logodesc', 'theme_brizzle');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$settings->add($setting); 

// Header widget setting
$name = 'theme_brizzle/headerwidget';
$title = get_string('headerwidget','theme_brizzle');
$description = get_string('headerwidgetdesc', 'theme_brizzle');
$setting = new admin_setting_confightmleditor($name, $title, $description, '');
$settings->add($setting); 

// Footer widget setting
$name = 'theme_brizzle/footerwidget';
$title = get_string('footerwidget','theme_brizzle');
$description = get_string('footerwidgetdesc', 'theme_brizzle');
$setting = new admin_setting_confightmleditor($name, $title, $description, '');
$settings->add($setting);
 
}
