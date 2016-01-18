<?php

// No direct access
defined('IN_GS') or die('Cannot load plugin directly.');

$thisfile = basename(__FILE__, ".php");

// Register the plugin
register_plugin(
    $thisfile,
    'Referrer Blocker',
    '1.0.0',
    'Helge Sverre',
    'https://helgesverre.com/',
    'Blocks access for common referrer spam sites, allows you to whitelist IP\'s',
    'plugins',
    'referrer_blocker_show'
);


// Run this hook everywhere before anything else is loaded in.
add_action('common', 'referrer_blocker');

// Create the plugin sidebar entry
add_action('plugins-sidebar', 'createSideMenu', array($thisfile, 'Referrer Blocker'));

// Hook into the settings page, inject an option and hook into it being saved
add_action('settings-website-extras', 'referrer_block_donation_settings_form');
add_action('settings-website', 'referrer_block_donation_settings');


/**
 * Runs when the site settings are being saved,
 * changes value in donation.txt according to user selection.
 */
function referrer_block_donation_settings()
{
    if (isset($_POST["hide_donation"])) {

        // If the user want to hide the donate button, put 0 in the donate.txt file
        if ((bool)$_POST["hide_donation"]) {
            file_put_contents(GSPLUGINPATH . "/referrer_blocker/donate.txt", "0");
        } else {
            file_put_contents(GSPLUGINPATH . "/referrer_blocker/donate.txt", "1");
        }
    }
}

/**
 * Displays the "hide donation button" form in the site settings page
 */
function referrer_block_donation_settings_form()
{
    $checked = (showDonateButton() ? "" : "checked");
    ?>

    <p class="inline" style="margin-top: 20px;">
        <input name="hide_donation" id="hide_donation" type="checkbox" value="1" <?= $checked ?> >&nbsp;
        <label for="hide_donation"><?php i18n("referrer_blocker/DONATE_SETTINGS"); ?></label>
    </p>

    <?php
}


/**
 * Initializes the plugin, merges language files
 */
function referrer_blocker_init()
{
    // Merge together the language files
    i18n_merge('referrer_blocker') || i18n_merge('referrer_blocker', "en_US");
}


/**
 * Main routine, it essentially just does a check if your referrer is
 * in the list of spammy referrers, it ignores you ig you are whitelisted.
 */
function referrer_blocker_main()
{
    // Initialize language files
    referrer_blocker_init();


    if (isset($_SERVER["HTTP_REFERER"]) && !empty($_SERVER["HTTP_REFERER"])) {


        $whitelist = explode("\n", file_get_contents(
            GSPLUGINPATH . "/referrer_blocker/whitelist.txt",
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        ));

        if (!in_array($_SERVER["REMOTE_ADDR"], $whitelist)) {

            // Trim the referrer from the client
            $clientRef = trim($_SERVER["HTTP_REFERER"]);

            // Load the list of spam referrers
            $referrers = explode("\n", file_get_contents(
                GSPLUGINPATH . "/referrer_blocker/referrers.txt",
                FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
            ));

            // Loop through the spam referrer list
            foreach ($referrers as $referrer) {

                // Check if the client referrer contains a spam referrer
                if (strpos($clientRef, trim($referrer)) !== FALSE) {
                    header('HTTP/1.0 404 Not Found');
                    echo "<h1>404 Not Found</h1>";
                    echo "The page that you have requested could not be found.";
                    exit;
                }
            }
        }
    }
}

/**
 * Function that displays the administrative settings and processess the saved values when they are changed
 */
function referrer_blocker_show()
{
    // Load in the whitelist
    $whitelist = file_get_contents(
        GSPLUGINPATH . "/referrer_blocker/whitelist.txt",
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );

    // Load in the referrer spammer list
    $referrers = file_get_contents(
        GSPLUGINPATH . "/referrer_blocker/referrers.txt",
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );


    // Check if any action is specified
    if (isset($_POST["action"])) {

        // Neatly remove any trailing and leading whitespace from each line and insert only newline
        $whitelist = implode("\n", array_map('trim', explode("\n", $_POST["whitelist"])));
        $referrers = implode("\n", array_map('trim', explode("\n", $_POST["referrers"])));


        // Save to file
        if (
            file_put_contents(GSPLUGINPATH . "/referrer_blocker/whitelist.txt", $whitelist) !== false &&
            file_put_contents(GSPLUGINPATH . "/referrer_blocker/referrers.txt", $referrers) !== false
        ) {
            // Success
            ?>
            <script>
                $(function () {
                    $('div.bodycontent').before('<div class="updated" style="display:block;"><?php i18n("referrer_blocker/SAVE_SUCCESS"); ?></div>');
                    $('.updated, .error').fadeOut(300).fadeIn(500);
                });
            </script>
            <?php
        } else {
            // Failure
            ?>
            <script>
                $(function () {
                    $('div.bodycontent').before('<div class="error" style="display:block;"><?php i18n("referrer_blocker/SAVE_ERROR"); ?></div>');
                    $('.updated, .error').fadeOut(300).fadeIn(500);
                });
            </script>
            <?php
        }
    }
    ?>

    <h3 class="floated"><?php i18n("referrer_blocker/PLUGIN_NAME"); ?></h3>

    <div class="edit-nav clearfix">
        <a href="#" id="fetchListBtn"
           title="<?php i18n("referrer_blocker/FETCH_LIST_TITLE"); ?>"><?php i18n("referrer_blocker/FETCH_LIST"); ?></a>

        <?php if (showDonateButton()): ?>
            <a href="https://paypal.me/helgesverre/10usd" target="_blank"
               title="<?php i18n("referrer_blocker/DONATE_TITLE"); ?>"><?php i18n("referrer_blocker/DONATE"); ?></a>
        <?php endif; ?>
    </div>


    <form class="manyinputs" method="post">
        <label for="whitelist"><?php i18n("referrer_blocker/WHITELIST"); ?></label>
        <p style="margin-bottom: 0;"><?php i18n("referrer_blocker/WHITELIST_DESC"); ?></p>
        <textarea name="whitelist" id="whitelist"><?= $whitelist ?></textarea>


        <br><br>


        <label for="referrers"><?php i18n("referrer_blocker/REFERRER_LIST"); ?></label>
        <p style="margin-bottom: 0;"><?php i18n("referrer_blocker/REFERRER_LIST_DESC"); ?></p>
        <textarea name="referrers" id="referrers"><?= $referrers ?></textarea>

        <input type="hidden" name="action" value="save">

        <p id="submit_line">
            <span>
                <input type="submit" class="submit" name="submitted" id="button"
                       value="<?php i18n("referrer_blocker/SAVE_SETTINGS"); ?>">
            </span>
        </p>

    </form>

    <script>
        // When clicking the fetch button, send an AJAX request to GitHub and fetch a list of spam referrers
        document.getElementById("fetchListBtn").addEventListener("click", function grabReferrerList() {
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {
                if (xhttp.readyState == 4 && xhttp.status == 200) {
                    // Must assign it to the value, if it is the innerhtml or innertext,
                    // it will not repopulate after removing it from the textarea manually
                    document.getElementById("referrers").value = xhttp.responseText;
                }
            };
            xhttp.open("GET", "https://raw.githubusercontent.com/piwik/referrer-spam-blacklist/master/spammers.txt", true);
            xhttp.send();
        });
    </script>
    <?php
}

/**
 * Check if the donate button should be displayed or not, grabs the value from donate.txt in the plugin folder
 * @return bool whether or not to show the donate button
 */
function showDonateButton()
{

    $file_path = GSPLUGINPATH . "/referrer_blocker/donate.txt";

    // If this file exists
    if (file_exists($file_path)) {

        // Load the value from it
        $tmp = file_get_contents($file_path);
    } else {
        // If it doesnt exist, default it to true and create  the file
        file_put_contents(GSPLUGINPATH . "/referrer_blocker/donate.txt", "1");
        $tmp = true;
    }

    // Return the value in the file
    return (bool)$tmp;
}