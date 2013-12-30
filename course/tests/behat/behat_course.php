<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Behat course-related steps definitions.
 *
 * @package    core_course
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Course-related steps definitions.
 *
 * @package    core_course
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_course extends behat_base {

    /**
     * Turns editing mode on.
     * @Given /^I turn editing mode on$/
     */
    public function i_turn_editing_mode_on() {
        return new Given('I press "' . get_string('turneditingon') . '"');
    }

    /**
     * Turns editing mode off.
     * @Given /^I turn editing mode off$/
     */
    public function i_turn_editing_mode_off() {
        return new Given('I press "' . get_string('turneditingoff') . '"');
    }

    /**
     * Creates a new course with the provided table data matching course settings names with the desired values.
     *
     * @Given /^I create a course with:$/
     * @param TableNode $table The course data
     * @return Given[]
     */
    public function i_create_a_course_with(TableNode $table) {

        $steps = array(
            new Given('I go to the courses management page'),
            new Given('I should see the "'.get_string('categories').'" management page'),
            new Given('I click on category "'.get_string('miscellaneous').'" in the management interface'),
            new Given('I should see the "'.get_string('categoriesandcoures').'" management page'),
            new Given('I click on "'.get_string('createnewcourse').'" "link" in the "#course-listing" "css_element"')
        );

        // If the course format is one of the fields we change how we
        // fill the form as we need to wait for the form to be set.
        $rowshash = $table->getRowsHash();
        $formatfieldrefs = array(get_string('format'), 'format', 'id_format');
        foreach ($formatfieldrefs as $fieldref) {
            if (!empty($rowshash[$fieldref])) {
                $formatfield = $fieldref;
            }
        }

        // Setting the format separately.
        if (!empty($formatfield)) {

            // Removing the format field from the TableNode.
            $rows = $table->getRows();
            $formatvalue = $rowshash[$formatfield];
            foreach ($rows as $key => $row) {
                if ($row[0] == $formatfield) {
                    unset($rows[$key]);
                }
            }
            $table->setRows($rows);

            // Adding a forced wait until editors are loaded as otherwise selenium sometimes tries clicks on the
            // format field when the editor is being rendered and the click misses the field coordinates.
            $steps[] = new Given('I expand all fieldsets');
            $steps[] = new Given('I select "' . $formatvalue . '" from "' . $formatfield . '"');
            $steps[] = new Given('I fill the moodle form with:', $table);
        } else {
            $steps[] = new Given('I fill the moodle form with:', $table);
        }

        $steps[] = new Given('I press "' . get_string('savechanges') . '"');

        return $steps;
    }

    /**
     * Goes to the system courses/categories management page.
     *
     * @Given /^I go to the courses management page$/
     * @return Given[]
     */
    public function i_go_to_the_courses_management_page() {
        return array(
            new Given('I am on homepage'),
            new Given('I expand "' . get_string('administrationsite') . '" node'),
            new Given('I expand "' . get_string('courses', 'admin') . '" node'),
            new Given('I follow "' . get_string('coursemgmt', 'admin') . '"')
        );
    }

    /**
     * Adds the selected activity/resource filling the form data with the specified field/value pairs. Sections 0 and 1 are also allowed on frontpage.
     *
     * @When /^I add a "(?P<activity_or_resource_name_string>(?:[^"]|\\")*)" to section "(?P<section_number>\d+)" and I fill the form with:$/
     * @param string $activity The activity name
     * @param int $section The section number
     * @param TableNode $data The activity field/value data
     * @return Given[]
     */
    public function i_add_to_section_and_i_fill_the_form_with($activity, $section, TableNode $data) {

        return array(
            new Given('I add a "' . $this->escape($activity) . '" to section "' . $this->escape($section) . '"'),
            new Given('I fill the moodle form with:', $data),
            new Given('I press "' . get_string('savechangesandreturntocourse') . '"')
        );
    }

    /**
     * Opens the activity chooser and opens the activity/resource form page. Sections 0 and 1 are also allowed on frontpage.
     *
     * @Given /^I add a "(?P<activity_or_resource_name_string>(?:[^"]|\\")*)" to section "(?P<section_number>\d+)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $activity
     * @param int $section
     */
    public function i_add_to_section($activity, $section) {

        if ($this->getSession()->getPage()->find('css', 'body#page-site-index') && (int)$section <= 1) {
            // We are on the frontpage.
            if ($section) {
                // Section 1 represents the contents on the frontpage.
                $sectionxpath = "//body[@id='page-site-index']/descendant::div[contains(concat(' ',normalize-space(@class),' '),' sitetopic ')]";
            } else {
                // Section 0 represents "Site main menu" block.
                $sectionxpath = "//div[contains(concat(' ',normalize-space(@class),' '),' block_site_main_menu ')]";
            }
        } else {
            // We are inside the course.
            $sectionxpath = "//li[@id='section-" . $section . "']";
        }

        $activityliteral = $this->getSession()->getSelectorsHandler()->xpathLiteral(ucfirst($activity));

        if ($this->running_javascript()) {

            // Clicks add activity or resource section link.
            $sectionxpath = $sectionxpath . "/descendant::div[@class='section-modchooser']/span/a";
            $sectionnode = $this->find('xpath', $sectionxpath);
            $sectionnode->click();

            // Clicks the selected activity if it exists.
            $activityxpath = "//div[@id='chooseform']/descendant::label" .
                "/descendant::span[contains(concat(' ', normalize-space(@class), ' '), ' typename ')]" .
                "[contains(., $activityliteral)]" .
                "/parent::label/child::input";
            $activitynode = $this->find('xpath', $activityxpath);
            $activitynode->doubleClick();

        } else {
            // Without Javascript.

            // Selecting the option from the select box which contains the option.
            $selectxpath = $sectionxpath . "/descendant::div[contains(concat(' ', normalize-space(@class), ' '), ' section_add_menus ')]" .
                "/descendant::select[contains(., $activityliteral)]";
            $selectnode = $this->find('xpath', $selectxpath);
            $selectnode->selectOption($activity);

            // Go button.
            $gobuttonxpath = $selectxpath . "/ancestor::form/descendant::input[@type='submit']";
            $gobutton = $this->find('xpath', $gobuttonxpath);
            $gobutton->click();
        }

    }

    /**
     * Turns course section highlighting on.
     *
     * @Given /^I turn section "(?P<section_number>\d+)" highlighting on$/
     * @param int $sectionnumber The section number
     * @return Given[]
     */
    public function i_turn_section_highlighting_on($sectionnumber) {

        // Ensures the section exists.
        $xpath = $this->section_exists($sectionnumber);

        return new Given('I click on "' . get_string('markthistopic') . '" "link" in the "' . $this->escape($xpath) . '" "xpath_element"');
    }

    /**
     * Turns course section highlighting off.
     *
     * @Given /^I turn section "(?P<section_number>\d+)" highlighting off$/
     * @param int $sectionnumber The section number
     * @return Given[]
     */
    public function i_turn_section_highlighting_off($sectionnumber) {

        // Ensures the section exists.
        $xpath = $this->section_exists($sectionnumber);

        return new Given('I click on "' . get_string('markedthistopic') . '" "link" in the "' . $this->escape($xpath) . '" "xpath_element"');
    }

    /**
     * Shows the specified hidden section. You need to be in the course page and on editing mode.
     *
     * @Given /^I show section "(?P<section_number>\d+)"$/
     * @param int $sectionnumber
     */
    public function i_show_section($sectionnumber) {
        $showlink = $this->show_section_icon_exists($sectionnumber);
        $showlink->click();

        if ($this->running_javascript()) {
            $this->getSession()->wait(self::TIMEOUT * 1000, self::PAGE_READY_JS);
            $this->i_wait_until_section_is_available($sectionnumber);
        }
    }

    /**
     * Hides the specified visible section. You need to be in the course page and on editing mode.
     *
     * @Given /^I hide section "(?P<section_number>\d+)"$/
     * @param int $sectionnumber
     */
    public function i_hide_section($sectionnumber) {
        $hidelink = $this->hide_section_icon_exists($sectionnumber);
        $hidelink->click();

        if ($this->running_javascript()) {
            $this->getSession()->wait(self::TIMEOUT * 1000, self::PAGE_READY_JS);
            $this->i_wait_until_section_is_available($sectionnumber);
        }
    }

    /**
     * Go to editing section page for specified section number. You need to be in the course page and on editing mode.
     *
     * @Given /^I edit the section "(?P<section_number>\d+)"$/
     * @param int $sectionnumber
     */
    public function i_edit_the_section($sectionnumber) {
        return new Given('I click on "' . get_string('editsummary') . '" "link" in the "#section-' . $sectionnumber . '" "css_element"');
    }

    /**
     * Edit specified section and fill the form data with the specified field/value pairs.
     *
     * @When /^I edit the section "(?P<section_number>\d+)" and I fill the form with:$/
     * @param int $sectionnumber The section number
     * @param TableNode $data The activity field/value data
     * @return Given[]
     */
    public function i_edit_the_section_and_i_fill_the_form_with($sectionnumber, TableNode $data) {

        return array(
            new Given('I edit the section "' . $sectionnumber . '"'),
            new Given('I fill the moodle form with:', $data),
            new Given('I press "' . get_string('savechanges') . '"')
        );
    }

    /**
     * Checks if the specified course section hightlighting is turned on. You need to be in the course page on editing mode.
     *
     * @Then /^section "(?P<section_number>\d+)" should be highlighted$/
     * @throws ExpectationException
     * @param int $sectionnumber The section number
     */
    public function section_should_be_highlighted($sectionnumber) {

        // Ensures the section exists.
        $xpath = $this->section_exists($sectionnumber);

        // The important checking, we can not check the img.
        $xpath = $xpath . "/descendant::img[@alt='" . get_string('markedthistopic') . "'][contains(@src, 'marked')]";
        $exception = new ExpectationException('The "' . $sectionnumber . '" section is not highlighted', $this->getSession());
        $this->find('xpath', $xpath, $exception);
    }

    /**
     * Checks if the specified course section highlighting is turned off. You need to be in the course page on editing mode.
     *
     * @Then /^section "(?P<section_number>\d+)" should not be highlighted$/
     * @throws ExpectationException
     * @param int $sectionnumber The section number
     */
    public function section_should_not_be_highlighted($sectionnumber) {

        // We only catch ExpectationException, ElementNotFoundException should be thrown if the specified section does not exist.
        try {
            $this->section_should_be_highlighted($sectionnumber);
        } catch (ExpectationException $e) {
            // ExpectedException means that it is not highlighted.
            return;
        }

        throw new ExpectationException('The "' . $sectionnumber . '" section is highlighted', $this->getSession());
    }

    /**
     * Checks that the specified section is visible. You need to be in the course page. It can be used being logged as a student and as a teacher on editing mode.
     *
     * @Then /^section "(?P<section_number>\d+)" should be hidden$/
     * @throws ExpectationException
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param int $sectionnumber
     */
    public function section_should_be_hidden($sectionnumber) {

        $sectionxpath = $this->section_exists($sectionnumber);

        // Preventive in case there is any action in progress.
        // Adding it here because we are interacting (click) with
        // the elements, not necessary when we just find().
        $this->i_wait_until_section_is_available($sectionnumber);

        // Section should be hidden.
        $exception = new ExpectationException('The section is not hidden', $this->getSession());
        $this->find('xpath', $sectionxpath . "[contains(concat(' ', normalize-space(@class), ' '), ' hidden ')]", $exception);

        // The checking are different depending on user permissions.
        if ($this->is_course_editor()) {

            // The section must be hidden.
            $this->show_section_icon_exists($sectionnumber);

            // If there are activities they should be hidden and the visibility icon should not be available.
            if ($activities = $this->get_section_activities($sectionxpath)) {

                $dimmedexception = new ExpectationException('There are activities that are not dimmed', $this->getSession());
                $visibilityexception = new ExpectationException('There are activities which visibility icons are clickable', $this->getSession());
                foreach ($activities as $activity) {

                    // Dimmed.
                    $this->find('xpath', "//div[contains(concat(' ', normalize-space(@class), ' '), ' activityinstance ')]" .
                        "/a[contains(concat(' ', normalize-space(@class), ' '), ' dimmed ')]", $dimmedexception, $activity);

                    // Non-JS browsers can not click on img elements.
                    if ($this->running_javascript()) {

                        // Expanding the actions menu if it is not shown.
                        $classes = array_flip(explode(' ', $activity->getAttribute('class')));
                        if (empty($classes['action-menu-shown'])) {
                            $actionsmenu = $this->find('css', "a[role='menuitem']", false, $activity);
                            $actionsmenu->click();
                        }

                        // To check that the visibility is not clickable we check the funcionality rather than the applied style.
                        $visibilityiconnode = $this->find('css', 'a.editing_show img', false, $activity);
                        $visibilityiconnode->click();
                    }

                    // We ensure that we still see the show icon.
                    $visibilityiconnode = $this->find('css', 'a.editing_show img', $visibilityexception, $activity);

                    // It is there only when running JS scenarios.
                    if ($this->running_javascript()) {

                        // Collapse the actions menu if it is displayed.
                        $classes = array_flip(explode(' ', $activity->getAttribute('class')));
                        if (!empty($classes['action-menu-shown'])) {
                            $actionsmenu = $this->find('css', "a[role='menuitem']", false, $activity);
                            $actionsmenu->click();
                        }
                    }
                }
            }

        } else {
            // There shouldn't be activities.
            if ($this->get_section_activities($sectionxpath)) {
                throw new ExpectationException('There are activities in the section and they should be hidden', $this->getSession());
            }
        }
    }

    /**
     * Checks that the specified section is visible. You need to be in the course page. It can be used being logged as a student and as a teacher on editing mode.
     *
     * @Then /^section "(?P<section_number>\d+)" should be visible$/
     * @throws ExpectationException
     * @param int $sectionnumber
     */
    public function section_should_be_visible($sectionnumber) {

        $sectionxpath = $this->section_exists($sectionnumber);

        // Section should not be hidden.
        $xpath = $sectionxpath . "[not(contains(concat(' ', normalize-space(@class), ' '), ' hidden '))]";
        if (!$this->getSession()->getPage()->find('xpath', $xpath)) {
            throw new ExpectationException('The section is hidden', $this->getSession());
        }

        // Hide section button should be visible.
        if ($this->is_course_editor()) {
            $this->hide_section_icon_exists($sectionnumber);
        }
    }

    /**
     * Moves up the specified section, this step only works with Javascript disabled. Editing mode should be on.
     *
     * @Given /^I move up section "(?P<section_number>\d+)"$/
     * @throws DriverException Step not available when Javascript is enabled
     * @param int $sectionnumber
     */
    public function i_move_up_section($sectionnumber) {

        if ($this->running_javascript()) {
            throw new DriverException('Move a section up step is not available with Javascript enabled');
        }

        // Ensures the section exists.
        $sectionxpath = $this->section_exists($sectionnumber);

        // Follows the link
        $moveuplink = $this->get_node_in_container('link', get_string('moveup'), 'xpath_element', $sectionxpath);
        $moveuplink->click();
    }

    /**
     * Moves down the specified section, this step only works with Javascript disabled. Editing mode should be on.
     *
     * @Given /^I move down section "(?P<section_number>\d+)"$/
     * @throws DriverException Step not available when Javascript is enabled
     * @param int $sectionnumber
     */
    public function i_move_down_section($sectionnumber) {

        if ($this->running_javascript()) {
            throw new DriverException('Move a section down step is not available with Javascript enabled');
        }

        // Ensures the section exists.
        $sectionxpath = $this->section_exists($sectionnumber);

        // Follows the link
        $movedownlink = $this->get_node_in_container('link', get_string('movedown'), 'xpath_element', $sectionxpath);
        $movedownlink->click();
    }

    /**
     * Checks that the specified activity is visible. You need to be in the course page. It can be used being logged as a student and as a teacher on editing mode.
     *
     * @Then /^"(?P<activity_or_resource_string>(?:[^"]|\\")*)" activity should be visible$/
     * @param string $activityname
     * @throws ExpectationException
     */
    public function activity_should_be_visible($activityname) {

        // The activity must exists and be visible.
        $activitynode = $this->get_activity_node($activityname);

        if ($this->is_course_editor()) {

            // The activity should not be dimmed.
            try {
                $this->find('css', 'a.dimmed', false, $activitynode);
                throw new ExpectationException('"' . $activityname . '" is hidden', $this->getSession());
            } catch (ElementNotFoundException $e) {
                // All ok.
            }

            // The 'Hide' button should be available.
            $nohideexception = new ExpectationException('"' . $activityname . '" don\'t have a "' . get_string('hide') . '" icon', $this->getSession());
            $this->find('named', array('link', get_string('hide')), $nohideexception, $activitynode);
        }
    }

    /**
     * Checks that the specified activity is hidden. You need to be in the course page. It can be used being logged as a student and as a teacher on editing mode.
     *
     * @Then /^"(?P<activity_or_resource_string>(?:[^"]|\\")*)" activity should be hidden$/
     * @param string $activityname
     * @throws ExpectationException
     */
    public function activity_should_be_hidden($activityname) {

        if ($this->is_course_editor()) {

            // The activity should exists.
            $activitynode = $this->get_activity_node($activityname);

            // Should be hidden.
            $exception = new ExpectationException('"' . $activityname . '" is not dimmed', $this->getSession());
            $this->find('css', 'a.dimmed', $exception, $activitynode);

            // Also 'Show' icon.
            $noshowexception = new ExpectationException('"' . $activityname . '" don\'t have a "' . get_string('show') . '" icon', $this->getSession());
            $this->find('named', array('link', get_string('show')), $noshowexception, $activitynode);

        } else {

            // It should not exists at all.
            try {
                $this->find_link($activityname);
                throw new ExpectationException('The "' . $activityname . '" should not appear');
            } catch (ElementNotFoundException $e) {
                // This is good, the activity should not be there.
            }
        }

    }

    /**
     * Moves the specified activity to the first slot of a section. This step is experimental when using it in Javascript tests. Editing mode should be on.
     *
     * @Given /^I move "(?P<activity_name_string>(?:[^"]|\\")*)" activity to section "(?P<section_number>\d+)"$/
     * @param string $activityname The activity name
     * @param int $sectionnumber The number of section
     * @return Given[]
     */
    public function i_move_activity_to_section($activityname, $sectionnumber) {

        // Ensure the destination is valid.
        $sectionxpath = $this->section_exists($sectionnumber);

        $activitynode = $this->get_activity_element('.editing_move img', 'css_element', $activityname);

        // JS enabled.
        if ($this->running_javascript()) {

            $destinationxpath = $sectionxpath . "/descendant::ul[contains(concat(' ', normalize-space(@class), ' '), ' yui3-dd-drop ')]";

            return array(
                new Given('I drag "' . $this->escape($activitynode->getXpath()) . '" "xpath_element" ' .
                    'and I drop it in "' . $this->escape($destinationxpath) . '" "xpath_element"'),
            );

        } else {
            // Following links with no-JS.

            // Moving to the fist spot of the section (before all other section's activities).
            return array(
                new Given('I click on "a.editing_move" "css_element" in the "' . $this->escape($activityname) . '" activity'),
                new Given('I click on "li.movehere a" "css_element" in the "' . $this->escape($sectionxpath) . '" "xpath_element"'),
            );
        }
    }

    /**
     * Edits the activity name through the edit activity; this step only works with Javascript enabled. Editing mode should be on.
     *
     * @Given /^I change "(?P<activity_name_string>(?:[^"]|\\")*)" activity name to "(?P<new_name_string>(?:[^"]|\\")*)"$/
     * @throws DriverException Step not available when Javascript is disabled
     * @param string $activityname
     * @param string $newactivityname
     * @return Given[]
     */
    public function i_change_activity_name_to($activityname, $newactivityname) {

        if (!$this->running_javascript()) {
            throw new DriverException('Change activity name step is not available with Javascript disabled');
        }

        // Adding chr(10) to save changes.
        $activity = $this->escape($activityname);
        return array(
            new Given('I click on "' . get_string('edittitle') . '" "link" in the "' . $activity .'" activity'),
            new Given('I fill in "title" with "' . $this->escape($newactivityname) . chr(10) . '"')
        );
    }

    /**
     * Opens an activity actions menu if it is not already opened.
     *
     * @Given /^I open "(?P<activity_name_string>(?:[^"]|\\")*)" actions menu$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $activityname
     * @return Given
     */
    public function i_open_actions_menu($activityname) {

        if (!$this->running_javascript()) {
            throw new DriverException('Activities actions menu not available when Javascript is disabled');
        }

        // If it is already opened we do nothing.
        $activitynode = $this->get_activity_node($activityname);
        $classes = array_flip(explode(' ', $activitynode->getAttribute('class')));
        if (!empty($classes['action-menu-shown'])) {
            return;
        }

        return new Given('I click on "a[role=\'menuitem\']" "css_element" in the "' . $this->escape($activityname) . '" activity');
    }

    /**
     * Closes an activity actions menu if it is not already closed.
     *
     * @Given /^I close "(?P<activity_name_string>(?:[^"]|\\")*)" actions menu$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $activityname
     * @return Given
     */
    public function i_close_actions_menu($activityname) {

        if (!$this->running_javascript()) {
            throw new DriverException('Activities actions menu not available when Javascript is disabled');
        }

        // If it is already closed we do nothing.
        $activitynode = $this->get_activity_node($activityname);
        $classes = array_flip(explode(' ', $activitynode->getAttribute('class')));
        if (empty($classes['action-menu-shown'])) {
            return;
        }

        return new Given('I click on "a[role=\'menuitem\']" "css_element" in the "' . $this->escape($activityname) . '" activity');
    }

    /**
     * Indents to the right the activity or resource specified by it's name. Editing mode should be on.
     *
     * @Given /^I indent right "(?P<activity_name_string>(?:[^"]|\\")*)" activity$/
     * @param string $activityname
     * @return Given[]
     */
    public function i_indent_right_activity($activityname) {

        $steps = array();
        $activity = $this->escape($activityname);
        if ($this->running_javascript()) {
            $steps[] = new Given('I open "' . $activity . '" actions menu');
        }
        $steps[] = new Given('I click on "' . get_string('moveright') . '" "link" in the "' . $activity . '" activity');

        return $steps;
    }

    /**
     * Indents to the left the activity or resource specified by it's name. Editing mode should be on.
     *
     * @Given /^I indent left "(?P<activity_name_string>(?:[^"]|\\")*)" activity$/
     * @param string $activityname
     * @return Given[]
     */
    public function i_indent_left_activity($activityname) {

        $steps = array();
        $activity = $this->escape($activityname);
        if ($this->running_javascript()) {
            $steps[] = new Given('I open "' . $activity . '" actions menu');
        }
        $steps[] = new Given('I click on "' . get_string('moveleft') . '" "link" in the "' . $activity . '" activity');

        return $steps;

    }

    /**
     * Deletes the activity or resource specified by it's name. This step is experimental when using it in Javascript tests. You should be in the course page with editing mode on.
     *
     * @Given /^I delete "(?P<activity_name_string>(?:[^"]|\\")*)" activity$/
     * @param string $activityname
     * @return Given[]
     */
    public function i_delete_activity($activityname) {

        $deletestring = get_string('delete');

        // JS enabled.
        // Not using chain steps here because the exceptions catcher have problems detecting
        // JS modal windows and avoiding interacting them at the same time.
        if ($this->running_javascript()) {

            $element = $this->get_activity_element($deletestring, 'link', $activityname);
            $element->click();

            $this->getSession()->getDriver()->getWebDriverSession()->accept_alert();

        } else {

            // With JS disabled.
            $steps = array(
                new Given('I click on "' . $this->escape($deletestring) . '" "link" in the "' . $this->escape($activityname) . '" activity'),
                new Given('I press "' . get_string('yes') . '"')
            );

            return $steps;
        }
    }

    /**
     * Duplicates the activity or resource specified by it's name. You should be in the course page with editing mode on.
     *
     * @Given /^I duplicate "(?P<activity_name_string>(?:[^"]|\\")*)" activity$/
     * @param string $activityname
     * @return Given[]
     */
    public function i_duplicate_activity($activityname) {
        $steps = array();
        $activity = $this->escape($activityname);
        if ($this->running_javascript()) {
            $steps[] = new Given('I open "' . $activity . '" actions menu');
        }
        $steps[] = new Given('I click on "' . get_string('duplicate') . '" "link" in the "' . $activity . '" activity');
        if (!$this->running_javascript()) {
            $steps[] = new Given('I press "' . get_string('continue') .'"');
            $steps[] = new Given('I press "' . get_string('duplicatecontcourse') .'"');
        }
        return $steps;
    }

    /**
     * Duplicates the activity or resource and modifies the new activity with the provided data. You should be in the course page with editing mode on.
     *
     * @Given /^I duplicate "(?P<activity_name_string>(?:[^"]|\\")*)" activity editing the new copy with:$/
     * @param string $activityname
     * @param TableNode $data
     * @return Given[]
     */
    public function i_duplicate_activity_editing_the_new_copy_with($activityname, TableNode $data) {

        $steps = array();

        $activity = $this->escape($activityname);
        $activityliteral = $this->getSession()->getSelectorsHandler()->xpathLiteral($activityname);

        if ($this->running_javascript()) {
            $steps[] = new Given('I duplicate "' . $activity . '" activity');

            // We wait until the AJAX request finishes and the section is visible again.
            $hiddenlightboxxpath = "//li[contains(concat(' ', normalize-space(@class), ' '), ' activity ')][contains(., $activityliteral)]" .
                "/ancestor::li[contains(concat(' ', normalize-space(@class), ' '), ' section ')]" .
                "/descendant::div[contains(concat(' ', @class, ' '), ' lightbox ')][contains(@style, 'display: none')]";
            $steps[] = new Given('I wait until the page is ready');
            $steps[] = new Given('I wait until "' . $this->escape($hiddenlightboxxpath) .'" "xpath_element" exists');

            // Close the original activity actions menu.
            $steps[] = new Given('I close "' . $activity . '" actions menu');

            // Determine the future new activity xpath from the former one.
            $duplicatedxpath = "//li[contains(concat(' ', normalize-space(@class), ' '), ' activity ')][contains(., $activityliteral)]" .
                "/following-sibling::li";
            $duplicatedactionsmenuxpath = $duplicatedxpath . "/descendant::a[@role='menuitem']";

            // The next sibling of the former activity will be the duplicated one, so we click on it from it's xpath as, at
            // this point, it don't even exists in the DOM (the steps are executed when we return them).
            $steps[] = new Given('I click on "' . $this->escape($duplicatedactionsmenuxpath) . '" "xpath_element"');

            // We force the xpath as otherwise mink tries to interact with the former one.
            $steps[] = new Given('I click on "' . get_string('editsettings') . '" "link" in the "' . $this->escape($duplicatedxpath) . '" "xpath_element"');
        } else {
            $steps[] = new Given('I click on "' . get_string('duplicate') . '" "link" in the "' . $activity . '" activity');
            $steps[] = new Given('I press "' . get_string('continue') .'"');
            $steps[] = new Given('I press "' . get_string('duplicatecontedit') . '"');
        }
        $steps[] = new Given('I fill the moodle form with:', $data);
        $steps[] = new Given('I press "' . get_string('savechangesandreturntocourse') . '"');
        return $steps;
    }

    /**
     * Waits until the section is available to interact with it. Useful when the section is performing an action and the section is overlayed with a loading layout.
     *
     * Using the protected method as this method will be usually
     * called by other methods which are not returning a set of
     * steps and performs the actions directly, so it would not
     * be executed if it returns another step.
     *
     * Hopefully we would not require test writers to use this step
     * and we will manage it from other step definitions.
     *
     * @Given /^I wait until section "(?P<section_number>\d+)" is available$/
     * @param int $sectionnumber
     * @return void
     */
    public function i_wait_until_section_is_available($sectionnumber) {

        // Looks for a hidden lightbox or a non-existent lightbox in that section.
        $sectionxpath = $this->section_exists($sectionnumber);
        $hiddenlightboxxpath = $sectionxpath . "/descendant::div[contains(concat(' ', @class, ' '), ' lightbox ')][contains(@style, 'display: none')]" .
            " | " .
            $sectionxpath . "[count(child::div[contains(@class, 'lightbox')]) = 0]";

        $this->ensure_element_exists($hiddenlightboxxpath, 'xpath_element');
    }

    /**
     * Clicks on the specified element of the activity. You should be in the course page with editing mode turned on.
     *
     * @Given /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in the "(?P<activity_name_string>[^"]*)" activity$/
     * @param string $element
     * @param string $selectortype
     * @param string $activityname
     */
    public function i_click_on_in_the_activity($element, $selectortype, $activityname) {
        $element = $this->get_activity_element($element, $selectortype, $activityname);
        $element->click();
    }

    /**
     * Clicks on the specified element inside the activity container.
     *
     * @throws ElementNotFoundException
     * @param string $element
     * @param string $selectortype
     * @param string $activityname
     * @return NodeElement
     */
    protected function get_activity_element($element, $selectortype, $activityname) {
        $activitynode = $this->get_activity_node($activityname);

        // Transforming to Behat selector/locator.
        list($selector, $locator) = $this->transform_selector($selectortype, $element);
        $exception = new ElementNotFoundException($this->getSession(), '"' . $element . '" "' . $selectortype . '" in "' . $activityname . '" ');

        return $this->find($selector, $locator, $exception, $activitynode);
    }

    /**
     * Checks if the course section exists.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param int $sectionnumber
     * @return string The xpath of the section.
     */
    protected function section_exists($sectionnumber) {

        // Just to give more info in case it does not exist.
        $xpath = "//li[@id='section-" . $sectionnumber . "']";
        $exception = new ElementNotFoundException($this->getSession(), "Section $sectionnumber ");
        $this->find('xpath', $xpath, $exception);

        return $xpath;
    }

    /**
     * Returns the show section icon or throws an exception.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param int $sectionnumber
     * @return NodeElement
     */
    protected function show_section_icon_exists($sectionnumber) {

        // Gets the section xpath and ensure it exists.
        $xpath = $this->section_exists($sectionnumber);

        // We need to know the course format as the text strings depends on them.
        $courseformat = $this->get_course_format();

        // Checking the show button alt text and show icon.
        $showtext = $this->getSession()->getSelectorsHandler()->xpathLiteral(get_string('showfromothers', $courseformat));
        $linkxpath = $xpath . "/descendant::a[@title=$showtext]";
        $imgxpath = $linkxpath . "/descendant::img[@alt=$showtext][contains(@src, 'show')]";

        $exception = new ElementNotFoundException($this->getSession(), 'Show section icon ');
        $this->find('xpath', $imgxpath, $exception);

        // Returing the link so both Non-JS and JS browsers can interact with it.
        return $this->find('xpath', $linkxpath, $exception);
    }

    /**
     * Returns the hide section icon link if it exists or throws exception.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param int $sectionnumber
     * @return NodeElement
     */
    protected function hide_section_icon_exists($sectionnumber) {

        // Gets the section xpath and ensure it exists.
        $xpath = $this->section_exists($sectionnumber);

        // We need to know the course format as the text strings depends on them.
        $courseformat = $this->get_course_format();

        // Checking the hide button alt text and hide icon.
        $hidetext = $this->getSession()->getSelectorsHandler()->xpathLiteral(get_string('hidefromothers', $courseformat));
        $linkxpath = $xpath . "/descendant::a[@title=$hidetext]";
        $imgxpath = $linkxpath . "/descendant::img[@alt=$hidetext][contains(@src, 'hide')]";

        $exception = new ElementNotFoundException($this->getSession(), 'Hide section icon ');
        $this->find('xpath', $imgxpath, $exception);

        // Returing the link so both Non-JS and JS browsers can interact with it.
        return $this->find('xpath', $linkxpath, $exception);
    }

    /**
     * Gets the current course format.
     *
     * @throws ExpectationException If we are not in the course view page.
     * @return string The course format in a frankenstyled name.
     */
    protected function get_course_format() {

        $exception = new ExpectationException('You are not in a course page', $this->getSession());

        // The moodle body's id attribute contains the course format.
        $node = $this->getSession()->getPage()->find('css', 'body');
        if (!$node) {
            throw $exception;
        }

        if (!$bodyid = $node->getAttribute('id')) {
            throw $exception;
        }

        if (strstr($bodyid, 'page-course-view-') === false) {
            throw $exception;
        }

        return 'format_' . str_replace('page-course-view-', '', $bodyid);
    }

    /**
     * Gets the section's activites DOM nodes.
     *
     * @param string $sectionxpath
     * @return array NodeElement instances
     */
    protected function get_section_activities($sectionxpath) {

        $xpath = $sectionxpath . "/descendant::li[contains(concat(' ', normalize-space(@class), ' '), ' activity ')]";

        // We spin here, as activities usually require a lot of time to load.
        try {
            $activities = $this->find_all('xpath', $xpath);
        } catch (ElementNotFoundException $e) {
            return false;
        }

        return $activities;
    }

    /**
     * Returns the DOM node of the activity from <li>.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $activityname The activity name
     * @return NodeElement
     */
    protected function get_activity_node($activityname) {

        $activityname = $this->getSession()->getSelectorsHandler()->xpathLiteral($activityname);
        $xpath = "//li[contains(concat(' ', normalize-space(@class), ' '), ' activity ')][contains(., $activityname)]";

        return $this->find('xpath', $xpath);
    }

    /**
     * Gets the activity instance name from the activity node.
     *
     * @throws ElementNotFoundException
     * @param NodeElement $activitynode
     * @return string
     */
    protected function get_activity_name($activitynode) {
        $instancenamenode = $this->find('xpath', "//span[contains(concat(' ', normalize-space(@class), ' '), ' instancename ')]", false, $activitynode);
        return $instancenamenode->getText();
    }

    /**
     * Returns whether the user can edit the course contents or not.
     *
     * @return bool
     */
    protected function is_course_editor() {

        // We don't need to behat_base::spin() here as all is already loaded.
        if (!$this->getSession()->getPage()->findButton(get_string('turneditingoff')) &&
                !$this->getSession()->getPage()->findButton(get_string('turneditingon'))) {
            return false;
        }

        return true;
    }

    /**
     * Returns the id of the category with the given idnumber.
     *
     * Please note that this function requires the category to exist. If it does not exist an ExpectationException is thrown.
     *
     * @param string $idnumber
     * @return string
     * @throws ExpectationException
     */
    protected function get_category_id($idnumber) {
        global $DB;
        try {
            return $DB->get_field('course_categories', 'id', array('idnumber' => $idnumber), MUST_EXIST);
        } catch (dml_missing_record_exception $ex) {
            throw new ExpectationException(sprintf("There is no category in the database with the idnumber '%s'", $idnumber));
        }
    }

    /**
     * Returns the id of the course with the given idnumber.
     *
     * Please note that this function requires the category to exist. If it does not exist an ExpectationException is thrown.
     *
     * @param string $idnumber
     * @return string
     * @throws ExpectationException
     */
    protected function get_course_id($idnumber) {
        global $DB;
        try {
            return $DB->get_field('course', 'id', array('idnumber' => $idnumber), MUST_EXIST);
        } catch (dml_missing_record_exception $ex) {
            throw new ExpectationException(sprintf("There is no course in the database with the idnumber '%s'", $idnumber));
        }
    }

    /**
     * Returns the category node from within the listing on the management page.
     *
     * @param string $idnumber
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function get_management_category_listing_node_by_idnumber($idnumber) {
        $id = $this->get_category_id($idnumber);
        $selector = sprintf('#category-listing .listitem-category[data-id="%d"] > div', $id);
        return $this->find('css', $selector);
    }

    /**
     * Returns a category node from within the management interface.
     *
     * @param string $name The name of the category.
     * @param bool $link If set to true we'll resolve to the link rather than just the node.
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function get_management_category_listing_node_by_name($name, $link = false) {
        $selector = "//div[@id='category-listing']//li[contains(concat(' ', normalize-space(@class), ' '), ' listitem-category ')]//a[text()='{$name}']";
        if ($link === false) {
            $selector .= "/ancestor::li[@data-id][1]";
        }
        return $this->find('xpath', $selector);
    }

    /**
     * Returns a course node from within the management interface.
     *
     * @param string $name The name of the course.
     * @param bool $link If set to true we'll resolve to the link rather than just the node.
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function get_management_course_listing_node_by_name($name, $link = false) {
        $selector = "//div[@id='course-listing']//li[contains(concat(' ', @class, ' '), ' listitem-course ')]//a[text()='{$name}']";
        if ($link === false) {
            $selector .= "/ancestor::li[@data-id]";
        }
        return $this->find('xpath', $selector);
    }

    /**
     * Returns the course node from within the listing on the management page.
     *
     * @param string $idnumber
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function get_management_course_listing_node_by_idnumber($idnumber) {
        $id = $this->get_course_id($idnumber);
        $selector = sprintf('#course-listing .listitem-course[data-id="%d"] > div', $id);
        return $this->find('css', $selector);
    }

    /**
     * Clicks on a category in the management interface.
     *
     * @Given /^I click on category "(?P<name>[^"]*)" in the management interface$/
     * @param string $name
     */
    public function i_click_on_category_in_the_management_interface($name) {
        $node = $this->get_management_category_listing_node_by_name($name, true);
        $node->click();
    }

    /**
     * Clicks on a course in the management interface.
     *
     * @Given /^I click on course "(?P<name>[^"]*)" in the management interface$/
     * @param string $name
     */
    public function i_click_on_course_in_the_management_interface($name) {
        $node = $this->get_management_course_listing_node_by_name($name, true);
        $node->click();
    }

    /**
     * Clicks on a category checkbox in the management interface, if not checked.
     *
     * @Given /^I select category "(?P<name>[^"]*)" in the management interface$/
     * @param string $name
     */
    public function i_select_category_in_the_management_interface($name) {
        $node = $this->get_management_category_listing_node_by_name($name);
        $node = $node->findField('bcat[]');
        if (!$node->isChecked()) {
            $node->click();
        }
    }

    /**
     * Clicks on a category checkbox in the management interface, if checked.
     *
     * @Given /^I unselect category "(?P<name>[^"]*)" in the management interface$/
     * @param string $name
     */
    public function i_unselect_category_in_the_management_interface($name) {
        $node = $this->get_management_category_listing_node_by_name($name);
        $node = $node->findField('bcat[]');
        if ($node->isChecked()) {
            $node->click();
        }
    }

    /**
     * Clicks course checkbox in the management interface, if not checked.
     *
     * @Given /^I select course "(?P<name>[^"]*)" in the management interface$/
     * @param string $name
     */
    public function i_select_course_in_the_management_interface($name) {
        $node = $this->get_management_course_listing_node_by_name($name);
        $node = $node->findField('bc[]');
        if (!$node->isChecked()) {
            $node->click();
        }
    }

    /**
     * Clicks course checkbox in the management interface, if checked.
     *
     * @Given /^I unselect course "(?P<name>[^"]*)" in the management interface$/
     * @param string $name
     */
    public function i_unselect_course_in_the_management_interface($name) {
        $node = $this->get_management_course_listing_node_by_name($name);
        $node = $node->findField('bc[]');
        if ($node->isChecked()) {
            $node->click();
        }
    }

    /**
     * Move selected categories to top level in the management interface.
     *
     * @Given /^I move category "(?P<name>[^"]*)" to top level in the management interface$/
     * @param string $name
     * @return Given[]
     */
    public function i_move_category_to_top_level_in_the_management_interface($name) {
        $this->i_select_category_in_the_management_interface($name);
        return array(
            new Given('I select "' .  coursecat::get(0)->get_formatted_name() . '" from "menumovecategoriesto"'),
            new Given('I press "bulkmovecategories"'),
        );
    }

    /**
     * Checks that a category is a subcategory of specific category.
     *
     * @Given /^I should see category "(?P<subcatidnumber>[^"]*)" as subcategory of "(?P<catidnumber>[^"]*)" in the management interface$/
     * @throws ExpectationException
     * @param string $subcatidnumber
     * @param string $catidnumber
     */
    public function i_should_see_category_as_subcategory_of_in_the_management_interface($subcatidnumber, $catidnumber) {
        $categorynodeid = $this->get_category_id($catidnumber);
        $subcategoryid = $this->get_category_id($subcatidnumber);
        $exception = new ExpectationException('The category '.$subcatidnumber.' is not a subcategory of '.$catidnumber, $this->getSession());
        $selector = sprintf('#category-listing .listitem-category[data-id="%d"] .listitem-category[data-id="%d"]', $categorynodeid, $subcategoryid);
        $this->find('css', $selector, $exception);
    }

    /**
     * Checks that a category is not a subcategory of specific category.
     *
     * @Given /^I should not see category "(?P<subcatidnumber>[^"]*)" as subcategory of "(?P<catidnumber>[^"]*)" in the management interface$/
     * @throws ExpectationException
     * @param string $subcatidnumber
     * @param string $catidnumber
     */
    public function i_should_not_see_category_as_subcategory_of_in_the_management_interface($subcatidnumber, $catidnumber) {
        try {
            $this->i_should_see_category_as_subcategory_of_in_the_management_interface($subcatidnumber, $catidnumber);
        } catch (ExpectationException $e) {
            // ExpectedException means that it is not highlighted.
            return;
        }
        throw new ExpectationException('The category '.$subcatidnumber.' is a subcategory of '.$catidnumber, $this->getSession());
    }

    /**
     * Click to expand a category revealing its sub categories within the management UI.
     *
     * @Given /^I click to expand category "(?P<idnumber>[^"]*)" in the management interface$/
     * @param string $idnumber
     */
    public function i_click_to_expand_category_in_the_management_interface($idnumber) {
        $categorynode = $this->get_management_category_listing_node_by_idnumber($idnumber);
        $exception = new ExpectationException('Category "' . $idnumber . '" does not contain an expand or collapse toggle.', $this->getSession());
        $togglenode = $this->find('css', 'a[data-action=collapse],a[data-action=expand]', $exception, $categorynode);
        $togglenode->click();
    }

    /**
     * Checks that a category within the management interface is visible.
     *
     * @Given /^category in management listing should be visible "(?P<idnumber>[^"]*)"$/
     * @param string $idnumber
     */
    public function category_in_management_listing_should_be_visible($idnumber) {
        $id = $this->get_category_id($idnumber);
        $exception = new ExpectationException('The category '.$idnumber.' is not visible.', $this->getSession());
        $selector = sprintf('#category-listing .listitem-category[data-id="%d"][data-visible="1"]', $id);
        $this->find('css', $selector, $exception);
    }

    /**
     * Checks that a category within the management interface is dimmed.
     *
     * @Given /^category in management listing should be dimmed "(?P<idnumber>[^"]*)"$/
     * @param string $idnumber
     */
    public function category_in_management_listing_should_be_dimmed($idnumber) {
        $id = $this->get_category_id($idnumber);
        $selector = sprintf('#category-listing .listitem-category[data-id="%d"][data-visible="0"]', $id);
        $exception = new ExpectationException('The category '.$idnumber.' is visible.', $this->getSession());
        $this->find('css', $selector, $exception);
    }

    /**
     * Checks that a course within the management interface is visible.
     *
     * @Given /^course in management listing should be visible "(?P<idnumber>[^"]*)"$/
     * @param string $idnumber
     */
    public function course_in_management_listing_should_be_visible($idnumber) {
        $id = $this->get_course_id($idnumber);
        $exception = new ExpectationException('The course '.$idnumber.' is not visible.', $this->getSession());
        $selector = sprintf('#course-listing .listitem-course[data-id="%d"][data-visible="1"]', $id);
        $this->find('css', $selector, $exception);
    }

    /**
     * Checks that a course within the management interface is dimmed.
     *
     * @Given /^course in management listing should be dimmed "(?P<idnumber>[^"]*)"$/
     * @param string $idnumber
     */
    public function course_in_management_listing_should_be_dimmed($idnumber) {
        $id = $this->get_course_id($idnumber);
        $exception = new ExpectationException('The course '.$idnumber.' is visible.', $this->getSession());
        $selector = sprintf('#course-listing .listitem-course[data-id="%d"][data-visible="0"]', $id);
        $this->find('css', $selector, $exception);
    }

    /**
     * Toggles the visibility of a course in the management UI.
     *
     * If it was visible it will be hidden. If it is hidden it will be made visible.
     *
     * @Given /^I toggle visibility of course "(?P<idnumber>[^"]*)" in management listing$/
     * @param string $idnumber
     */
    public function i_toggle_visibility_of_course_in_management_listing($idnumber) {
        $id = $this->get_course_id($idnumber);
        $selector = sprintf('#course-listing .listitem-course[data-id="%d"][data-visible]', $id);
        $node = $this->find('css', $selector);
        $exception = new ExpectationException('Course listing "' . $idnumber . '" does not contain a show or hide toggle.', $this->getSession());
        if ($node->getAttribute('data-visible') === '1') {
            $toggle = $this->find('css', '.action-hide', $exception, $node);
        } else {
            $toggle = $this->find('css', '.action-show', $exception, $node);
        }
        $toggle->click();
    }

    /**
     * Toggles the visibility of a category in the management UI.
     *
     * If it was visible it will be hidden. If it is hidden it will be made visible.
     *
     * @Given /^I toggle visibility of category "(?P<idnumber>[^"]*)" in management listing$/
     */
    public function i_toggle_visibility_of_category_in_management_listing($idnumber) {
        $id = $this->get_category_id($idnumber);
        $selector = sprintf('#category-listing .listitem-category[data-id="%d"][data-visible]', $id);
        $node = $this->find('css', $selector);
        $exception = new ExpectationException('Category listing "' . $idnumber . '" does not contain a show or hide toggle.', $this->getSession());
        if ($node->getAttribute('data-visible') === '1') {
            $toggle = $this->find('css', '.action-hide', $exception, $node);
        } else {
            $toggle = $this->find('css', '.action-show', $exception, $node);
        }
        $toggle->click();
    }

    /**
     * Moves a category displayed in the management interface up or down one place.
     *
     * @Given /^I click to move category "(?P<idnumber>[^"]*)" (?P<direction>up|down) one$/
     *
     * @param string $idnumber The category idnumber
     * @param string $direction The direction to move in, either up or down
     */
    public function i_click_to_move_category_by_one($idnumber, $direction) {
        $node = $this->get_management_category_listing_node_by_idnumber($idnumber);
        $this->user_moves_listing_by_one('category', $node, $direction);
    }

    /**
     * Moves a course displayed in the management interface up or down one place.
     *
     * @Given /^I click to move course "(?P<idnumber>[^"]*)" (?P<direction>up|down) one$/
     *
     * @param string $idnumber The course idnumber
     * @param string $direction The direction to move in, either up or down
     */
    public function i_click_to_move_course_by_one($idnumber, $direction) {
        $node = $this->get_management_course_listing_node_by_idnumber($idnumber);
        $this->user_moves_listing_by_one('course', $node, $direction);
    }

    /**
     * Moves a course or category listing within the management interface up or down by one.
     *
     * @param string $listingtype One of course or category
     * @param \Behat\Mink\Element\NodeElement $listingnode
     * @param string $direction One of up or down.
     * @param bool $highlight If set to false we don't check the node has been highlighted.
     */
    protected function user_moves_listing_by_one($listingtype, $listingnode, $direction, $highlight = true) {
        $up = (strtolower($direction) === 'up');
        if ($up) {
            $exception = new ExpectationException($listingtype.' listing does not contain a moveup button.', $this->getSession());
            $button = $this->find('css', 'a.action-moveup', $exception, $listingnode);
        } else {
            $exception = new ExpectationException($listingtype.' listing does not contain a movedown button.', $this->getSession());
            $button = $this->find('css', 'a.action-movedown', $exception, $listingnode);
        }
        $button->click();
        if ($this->running_javascript() && $highlight) {
            $listitem = $listingnode->getParent();
            $exception = new ExpectationException('Nothing was highlighted, ajax didn\'t occur or didn\'t succeed.', $this->getSession());
            $this->spin(array($this, 'listing_is_highlighted'), $listitem->getTagName().'#'.$listitem->getAttribute('id'), 2, $exception, true);
        }
    }

    /**
     * Used by spin to determine the callback has been highlighted.
     *
     * @param behat_course $self A self reference (default first arg from a spin callback)
     * @param \Behat\Mink\Element\NodeElement $selector
     * @return bool
     */
    protected function listing_is_highlighted($self, $selector) {
        $listitem = $this->find('css', $selector);
        return $listitem->hasClass('highlight');
    }

    /**
     * Check that one course appears before another in the course category management listings.
     *
     * @Given /^I should see course listing "(?P<preceedingcourse>[^"]*)" before "(?P<followingcourse>[^"]*)"$/
     *
     * @param string $preceedingcourse The first course to find
     * @param string $followingcourse The second course to find (should be AFTER the first course)
     * @throws ExpectationException
     */
    public function i_should_see_course_listing_before($preceedingcourse, $followingcourse) {
        $xpath = "//div[@id='course-listing']//li[contains(concat(' ', @class, ' '), ' listitem-course ')]//a[text()='{$preceedingcourse}']/ancestor::li[@data-id]//following::a[text()='{$followingcourse}']";
        $msg = "{$preceedingcourse} course does not appear before {$followingcourse} course";
        if (!$this->getSession()->getDriver()->find($xpath)) {
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * Check that one category appears before another in the course category management listings.
     *
     * @Given /^I should see category listing "(?P<preceedingcategory>[^"]*)" before "(?P<followingcategory>[^"]*)"$/
     *
     * @param string $preceedingcategory The first category to find
     * @param string $followingcategory The second category to find (should be after the first category)
     * @throws ExpectationException
     */
    public function i_should_see_category_listing_before($preceedingcategory, $followingcategory) {
        $xpath = "//div[@id='category-listing']//li[contains(concat(' ', @class, ' '), ' listitem-category ')]//a[text()='{$preceedingcategory}']/ancestor::li[@data-id]//following::a[text()='{$followingcategory}']";
        $msg = "{$preceedingcategory} category does not appear before {$followingcategory} category";
        if (!$this->getSession()->getDriver()->find($xpath)) {
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * Checks that we are on the course management page that we expect to be on and that no course has been selected.
     *
     * @Given /^I should see the "(?P<mode>[^"]*)" management page$/
     * @param string $mode The mode to expected. One of 'Courses', 'Course categories' or 'Course categories and courses'
     * @return Given[]
     */
    public function i_should_see_the_courses_management_page($mode) {
        $return = array(
            new Given('I should see "Course and category management" in the "h2" "css_element"')
        );
        switch ($mode) {
            case "Courses":
                $return[] = new Given('"#category-listing" "css_element" should not exists');
                $return[] = new Given('"#course-listing" "css_element" should exists');
                break;
            case "Course categories":
                $return[] = new Given('"#category-listing" "css_element" should exists');
                $return[] = new Given('"#course-listing" "css_element" should not exists');
                break;
            case "Courses categories and courses":
            default:
                $return[] = new Given('"#category-listing" "css_element" should exists');
                $return[] = new Given('"#course-listing" "css_element" should exists');
                break;
        }
        $return[] = new Given('"#course-detail" "css_element" should not exists');
        return $return;
    }

    /**
     * Checks that we are on the course management page that we expect to be on and that a course has been selected.
     *
     * @Given /^I should see the "(?P<mode>[^"]*)" management page with a course selected$/
     * @param string $mode The mode to expected. One of 'Courses', 'Course categories' or 'Course categories and courses'
     * @return Given[]
     */
    public function i_should_see_the_courses_management_page_with_a_course_selected($mode) {
        $return = $this->i_should_see_the_courses_management_page($mode);
        array_pop($return);
        $return[] = new Given('"#course-detail" "css_element" should exists');
        return $return;
    }

    /**
     * Locates a course in the course category management interface and then triggers an action for it.
     *
     * @Given /^I click on "(?P<action>[^"]*)" action for "(?P<name>[^"]*)" in management course listing$/
     *
     * @param string $action The action to take. One of
     * @param string $name The name of the course as it is displayed in the management interface.
     */
    public function i_click_on_action_for_item_in_management_course_listing($action, $name) {
        $node = $this->get_management_course_listing_node_by_name($name);
        $this->user_clicks_on_management_listing_action('course', $node, $action);
    }

    /**
     * Locates a category in the course category management interface and then triggers an action for it.
     *
     * @Given /^I click on "(?P<action>[^"]*)" action for "(?P<name>[^"]*)" in management category listing$/
     *
     * @param string $action The action to take. One of
     * @param string $name The name of the category as it is displayed in the management interface.
     */
    public function i_click_on_action_for_item_in_management_category_listing($action, $name) {
        $node = $this->get_management_category_listing_node_by_name($name);
        $this->user_clicks_on_management_listing_action('category', $node, $action);
    }

    /**
     * Clicks to expand or collapse a category displayed on the frontpage
     *
     * @Given /^I toggle "(?P<categoryname_string>(?:[^"]|\\")*)" category children visibility in frontpage$/
     * @throws ExpectationException
     * @param string $categoryname
     */
    public function i_toggle_category_children_visibility_in_frontpage($categoryname) {

        $headingtags = array();
        for ($i = 1; $i <= 6; $i++) {
            $headingtags[] = 'self::h' . $i;
        }

        $exception = new ExpectationException('"' . $categoryname . '" category can not be found', $this->getSession());
        $categoryliteral = $this->getSession()->getSelectorsHandler()->xpathLiteral($categoryname);
        $xpath = "//div[@class='info']/descendant::*[" . implode(' or ', $headingtags) . "][@class='categoryname'][./descendant::a[.=$categoryliteral]]";
        $node = $this->find('xpath', $xpath, $exception);
        $node->click();

        // Smooth expansion.
        $this->getSession()->wait(1000, false);
    }

    /**
     * Finds the node to use for a management listitem action and clicks it.
     *
     * @param string $listingtype Either course or category.
     * @param \Behat\Mink\Element\NodeElement $listingnode
     * @param string $action The action being taken
     * @throws Behat\Mink\Exception\ExpectationException
     */
    protected function user_clicks_on_management_listing_action($listingtype, $listingnode, $action) {
        $actionsnode = $listingnode->find('xpath', "//*[contains(concat(' ', normalize-space(@class), ' '), '{$listingtype}-item-actions')]");
        if (!$actionsnode) {
            throw new ExpectationException("Could not find the actions for $listingtype", $this->getSession());
        }
        $actionnode = $actionsnode->find('css', '.action-'.$action);
        if (!$actionnode) {
            throw new ExpectationException("Expected action was not available or not found ($action)", $this->getSession());
        }
        if ($this->running_javascript() && !$actionnode->isVisible()) {
            $actionsnode->find('css', 'a.toggle-display')->click();
            $actionnode = $actionsnode->find('css', '.action-'.$action);
        }
        $actionnode->click();
    }

    /**
     * Clicks on a category in the management interface.
     *
     * @Given /^I click on "([^"]*)" category in the management category listing$/
     * @param string $name The name of the category to click.
     */
    public function i_click_on_category_in_the_management_category_listing($name) {
        $node = $this->get_management_category_listing_node_by_name($name);
        $node->find('css', 'a.categoryname')->click();
    }
}
