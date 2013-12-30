<?php
$hasheading = ($PAGE->heading);
$hasnavbar = (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());
$hasfooter = (empty($PAGE->layout_options['nofooter']));
$hassidepre = (empty($PAGE->layout_options['noblocks']) && $PAGE->blocks->region_has_content('side-pre', $OUTPUT));
$hassidepost = (empty($PAGE->layout_options['noblocks']) && $PAGE->blocks->region_has_content('side-post', $OUTPUT));
$haslogininfo = (empty($PAGE->layout_options['nologininfo']));
$showsidepre = ($hassidepre && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT));
$showsidepost = ($hassidepost && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT));
$haslangmenu = (!empty($PAGE->layout_options['langmenu']));
$custommenu = $OUTPUT->custom_menu();
$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) && !empty($custommenu));

$bodyclasses = array();
if ($showsidepre && !$showsidepost) {
    $bodyclasses[] = 'side-pre-only';
} else if ($showsidepost && !$showsidepre) {
    $bodyclasses[] = 'side-post-only';
} else if (!$showsidepost && !$showsidepre) {
    $bodyclasses[] = 'content-only';
}
if ($hascustommenu) {
    $bodyclasses[] = 'has_custom_menu';
}

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes() ?>>
<head>
    <title><?php echo $PAGE->title ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <link rel="shortcut icon" href="<?php echo $OUTPUT->pix_url('favicon', 'theme')?>" />
    <meta name="description" content="" />
    <meta name="author" content="Moodle Theme by 3rd Wave Media Ltd, UK | elearning.3rdwavemedia.com" />   
    <style type="text/css">
        @font-face {
        font-family: 'CandelaBook';
        src: url('<?php echo $CFG->wwwroot ?>/theme/brizzle/fonts/CandelaBook-webfont.eot');
        src: url('<?php echo $CFG->wwwroot ?>/theme/brizzle/fonts/CandelaBook-webfont.eot?#iefix') format('embedded-opentype'),
             url('<?php echo $CFG->wwwroot ?>/theme/brizzle/fonts/CandelaBook-webfont.woff') format('woff'),
             url('<?php echo $CFG->wwwroot ?>/theme/brizzle/fonts/CandelaBook-webfont.ttf') format('truetype'),
             url('<?php echo $CFG->wwwroot ?>/theme/brizzle/fonts/CandelaBook-webfont.svg#CandelaBook') format('svg');
        font-weight: normal;
        font-style: normal;
         }    
    </style>
    <?php echo $OUTPUT->standard_head_html() ?>
</head>
<body id="<?php p($PAGE->bodyid) ?>" class="<?php p($PAGE->bodyclasses.' '.join(' ', $bodyclasses)) ?>">
<?php echo $OUTPUT->standard_top_of_body_html() ?>
<div id="page">    
    
    <?php if ($hasheading || $hasnavbar) { ?>
        
    <div id="page-header">
    
        
           
        <div id="page-header-inner">
            
            <?php //THEME SETTING - DEFINE LOGO URL   
            if (!empty($PAGE->theme->settings->logo)) {
                $logourl = $PAGE->theme->settings->logo;
            } else {
                $logourl = $OUTPUT->pix_url('logo', 'theme');
            }
            ?>        
        
            <?php if ($PAGE->heading) { ?>     
            <h1 id="logo"><a href="<?php echo $CFG->wwwroot ?>"><img src="<?php echo $logourl;?>" alt="Logo" /></a></h1>                
            <?php } ?>          
           
            
            
            <div class="headermenu">                      
            
                <?php  
                
                if ($haslogininfo) {
                    
                    echo "<div id='toplogin-wrapper'>";
                                        
                    //Print login info below
                    echo $OUTPUT->login_info();
                    
                    //Print user profile image below                   
                    echo  "<div id='profilepic'>";
            		if (!isloggedin() or isguestuser()) {
            			echo '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&amp;course='.$COURSE->id.'"><img src="'.$CFG->wwwroot.'/user/pix.php?file=/'.$USER->id.'/f2.jpg" width="20px" height="20px" title="Guest" alt="Guest" /></a>';
            		}else{
            			echo '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&amp;course='.$COURSE->id.'"><img src="'.$CFG->wwwroot.'/user/pix.php?file=/'.$USER->id.'/f2.jpg" width="20px" height="20px" title="'.$USER->firstname.' '.$USER->lastname.'" alt="'.$USER->firstname.' '.$USER->lastname.'" /></a>';
            		}
            		echo "</div>";  
                    
                    echo "</div>"; //end of toplogin-wrapper
                }
                
                if ($haslangmenu) {
                    echo $OUTPUT->lang_menu();
                     }                 
                /*$PAGE->headingmenu:This heading menu is special HTML that the page you are viewing wants to add. It can be anything from drop down boxes to buttons and any number of each.*/
                echo $PAGE->headingmenu
                ?>            
            </div><!--//headermenu-->       
            
            
            <?php //THEME SETTING - DEFINE HEADER WIDGET CONTENT
            if (!empty($PAGE->theme->settings->headerwidget)) {
            $headerwidget = $PAGE->theme->settings->headerwidget;
            } else {
            $headerwidget = '<!-- There was no custom headerwidget content set -->';
            }
            ?>            
            <div class="headerwidget"><?php echo $headerwidget; ?></div>     
            
              
                      
        </div><!--//#page-header-inner-->  
        
        <div id="topnav">
            <div id="topnav-inner">             
                <?php if ($hascustommenu) { ?>
                <div id="custommenu">                  
                    <?php echo $custommenu; ?>               
                </div><!--//custommenu-->
                <?php }  ?>              
                   
                
                
            </div><!--//topnav-inner-->
        </div><!--//topnav-->
            
           
                       
        
    </div><!-- END OF HEADER -->
    <?php } ?>     
        
                 
        
   <div class="content-wrapper">    
        
    <div id="page-content"> 
    
        <?php if ($hasnavbar) { ?>
        <div class="navbar clearfix">               
            <div class="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>
            <div class="navbutton"> <?php echo $PAGE->button; ?></div>
        </div><!--//navbar-->          
        <?php } ?>      
       
              
        <div id="region-main-box" class="clearfix">
            <div id="region-post-box">
                <div id="region-main-wrap">
                    <div id="region-main">
                        <div class="region-content">
                            <?php echo core_renderer::MAIN_CONTENT_TOKEN ?>
                        </div>
                    </div>
                </div>

                <?php if ($hassidepre) { ?>
                <div id="region-pre" class="block-region clearfix">
                    <div class="region-content">
                        <?php echo $OUTPUT->blocks_for_region('side-pre') ?>
                    </div>
                </div>
                <?php } ?>                
                
                  
                <?php if ($hassidepost) { ?>
                <div id="region-post" class="block-region">
                    <div class="region-content">
                        <?php echo $OUTPUT->blocks_for_region('side-post') ?>
                    </div>
                </div>
                <?php } ?>
               
            </div>
        </div>
    </div>
</div><!--content-wrapper-->
<!-- START OF FOOTER -->
<?php if ($hasfooter) { ?>
    <div id="page-footer" class="clearfix"> 
    <?php
    echo $OUTPUT->login_info();
    echo $OUTPUT->standard_footer_html();
    ?>      
    </div>
    
    <?php //THEME SETTING - DEFINE FOOTER WIDGET CONTENT
            if (!empty($PAGE->theme->settings->footerwidget)) {
            $footerwidget = $PAGE->theme->settings->footerwidget;
            } else {
            $footerwidget = '<!-- There was no custom footerwidget content set -->';
            }
    ?>
    <div class="footerwidget"><div class="footerwidget-inner"><?php echo $footerwidget; ?></div></div>  
      
<?php } ?>
</div>
<?php echo $OUTPUT->standard_end_of_body_html() ?>

</body>
</html>