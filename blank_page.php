<?php

require_once('config.php');

$PAGE->set_context(get_system_context());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Your title");
$PAGE->set_heading("Blank page");
$PAGE->set_url($CFG->wwwroot.'/blank_page.php');

echo $OUTPUT->header();

//Put your html content here in the place of hello world
echo "Hello World";



echo $OUTPUT->footer();
?>
