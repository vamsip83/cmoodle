<?php

////////////////////////////////////////////////////////////////////////////////
/// Moodle Theme by 3rd Wave Media Ltd. (http://elearning.3rdwavemedia.com)
////////////////////////////////////////////////////////////////////////////////

$THEME->name = 'brizzle';
$THEME->parents = array('base');
$THEME->sheets = array('general');
$THEME->parents_exclude_sheets = array('base'=>array('pagelayout')); 

////////////////////////////////////////////////////
// An array of stylesheets not to inherit from the
// themes parents
////////////////////////////////////////////////////

$THEME->layouts = array(
    // Most pages - if we encounter an unknown or a missing page type, this one is used.
    'base' => array(
        'file' => 'general.php',
        'regions' => array()
    ),
    'standard' => array(
        'file' => 'general.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',       
    ),
    // Course page
    'course' => array(
        'file' => 'general.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
      
    ),
    // Course page
    'coursecategory' => array(
        'file' => 'general.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
      
    ),
    'incourse' => array(
        'file' => 'general.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        
    ),
    'frontpage' => array(
        'file' => 'general.php',
        'regions' => array( 'side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('langmenu'=>true),

    ),
    'admin' => array(
        'file' => 'general.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
  
    ),
    'mydashboard' => array(
        'file' => 'general.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('langmenu'=>true),
    
    ),
    'mypublic' => array(
        'file' => 'general.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',	    
    ),
	
    'login' => array(
        'file' => 'general.php',
        'regions' => array(),
        'options' => array('langmenu'=>true),    
    ),
	
    // Pages that appear in pop-up windows - no navigation, no blocks, no header.
    'popup' => array(
        'file' => 'general.php',
        'regions' => array(),
        'options' => array('nofooter'=>true, 'nonavbar'=>true, 'noblocks'=>true),
    ),
    // No blocks and minimal footer - used for legacy frame layouts only!
    'frametop' => array(
        'file' => 'general.php',
        'regions' => array(),
        'options' => array('nofooter', 'noblocks'=>true),
    ),
    // Embeded pages, like iframe embeded in moodleform
    'embedded' => array(
        'file' => 'general.php',
        'regions' => array(),
        'options' => array('nofooter'=>true, 'nonavbar'=>true, 'noblocks'=>true),
    ),
    // Used during upgrade and install, and for the 'This site is undergoing maintenance' message.
    // This must not have any blocks, and it is good idea if it does not have links to
    // other plbrizzles - for example there should not be a home link in the footer...
    'maintenance' => array(
        'file' => 'general.php',
        'regions' => array(),
        'options' => array('nofooter'=>true, 'nonavbar'=>true, 'noblocks'=>true,'nocustommenu'=>true),
    ),
    // Should display the content and basic headers only.
    'print' => array(
        'file' => 'general.php',
        'regions' => array(),
        'options' => array('nofooter'=>true, 'nonavbar'=>false, 'noblocks'=>true),
    ),
   
    // The pagelayout used for reports
    'report' => array(
        'file' => 'general.php',
        'regions' => array(),
		//'options' => array('nofooter'=>true, 'nonavbar'=>true, 'noblocks'=>true,'nocustommenu'=>true),
    ),
);
////////////////////////////////////////////////////
// An array setting the layouts for the theme.
// These are all of the possible layouts in Moodle.
////////////////////////////////////////////////////


//$THEME->csspostprocess = 'mytheme_process_css';
////////////////////////////////////////////////////
// Allows the user to provide the name of a function
// that all CSS should be passed to before being
// delivered.
////////////////////////////////////////////////////

$THEME->enable_dock = true;

////////////////////////////////////////////////////
// An array containing the names of JavaScript files
// located in /javascript/ to include in the theme.
// (gets included in the head)
//$THEME->javascripts = array('js');
////////////////////////////////////////////////////

//$THEME->javascripts_footer = array('js');

////////////////////////////////////////////////////
// As above but will be included in the page footer.
////////////////////////////////////////////////////

$THEME->larrow    = '&lt;';
////////////////////////////////////////////////////
// Overrides the left arrow image used throughout
// Moodle
////////////////////////////////////////////////////

$THEME->rarrow    = '&gt;'; 

////////////////////////////////////////////////////
// Overrides the right arrow image used throughout Moodle
////////////////////////////////////////////////////

// $THEME->parents_exclude_javascripts

////////////////////////////////////////////////////
// An array of JavaScript files NOT to inherit from
// the themes parents
////////////////////////////////////////////////////

// $THEME->plugins_exclude_sheets

////////////////////////////////////////////////////
// An array of plugin sheets to ignore and not
// include.
////////////////////////////////////////////////////
//$THEME->rendererfactory = 'theme_overridden_renderer_factory';

////////////////////////////////////////////////////
// Sets a custom render factory to use with the
// theme, used when working with custom renderers.
////////////////////////////////////////////////////



