<?php
// This file is part of Ranking block for Moodle - http://moodle.org/
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
 * Theme Moove block settings file
 *
 * @package    theme_ivana
 * @copyright  2017 Willian Mano http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

// This is used for performance, we don't need to know about these settings on every page in Moodle, only when
// we are looking at the admin settings pages.
if ($ADMIN->fulltree) {
    // Boost provides a nice setting page which splits settings onto separate tabs. We want to use it here.
    $settings = new theme_boost_admin_settingspage_tabs('themesettingivana', get_string('configtitle', 'theme_ivana'));

    /*
    * ----------------------
    * General settings tab
    * ----------------------
    */
    $page = new admin_settingpage('theme_ivana_general', get_string('generalsettings', 'theme_ivana'));

    // Logo file setting.
    $name = 'theme_ivana/logo';
    $title = get_string('logo', 'theme_ivana');
    $description = get_string('logodesc', 'theme_ivana');
    $opts = ['accepted_types' => ['.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'], 'maxfiles' => 1];
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo', 0, $opts);
    $page->add($setting);

    // Favicon setting.
    $name = 'theme_ivana/favicon';
    $title = get_string('favicon', 'theme_ivana');
    $description = get_string('favicondesc', 'theme_ivana');
    $opts = ['accepted_types' => ['.ico'], 'maxfiles' => 1];
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon', 0, $opts);
    $page->add($setting);

    // Preset.
    $name = 'theme_ivana/preset';
    $title = get_string('preset', 'theme_ivana');
    $description = get_string('preset_desc', 'theme_ivana');
    $default = 'default.scss';

    $context = \core\context\system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_ivana', 'preset', 0, 'itemid, filepath, filename', false);

    $choices = [];
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }
    // These are the built in presets.
    $choices['default.scss'] = 'default.scss';
    $choices['plain.scss'] = 'plain.scss';

    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Preset files setting.
    $name = 'theme_ivana/presetfiles';
    $title = get_string('presetfiles', 'theme_ivana');
    $description = get_string('presetfiles_desc', 'theme_ivana');

    $setting = new admin_setting_configstoredfile(
        $name,
        $title,
        $description,
        'preset',
        0,
        ['maxfiles' => 10, 'accepted_types' => ['.scss']]
    );
    $page->add($setting);

    // Login page background image.
    $name = 'theme_ivana/loginbgimg';
    $title = get_string('loginbgimg', 'theme_ivana');
    $description = get_string('loginbgimg_desc', 'theme_ivana');
    $opts = ['accepted_types' => ['.png', '.jpg', '.svg']];
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginbgimg', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $brand-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_ivana/brandcolor';
    $title = get_string('brandcolor', 'theme_ivana');
    $description = get_string('brandcolor_desc', 'theme_ivana');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#0f47ad');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $navbar-header-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_ivana/secondarymenucolor';
    $title = get_string('secondarymenucolor', 'theme_ivana');
    $description = get_string('secondarymenucolor_desc', 'theme_ivana');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#0f47ad');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $fontsarr = [
        'Moodle' => 'Moodle Font',
        'Roboto' => 'Roboto',
        'Poppins' => 'Poppins',
        'Montserrat' => 'Montserrat',
        'Open Sans' => 'Open Sans',
        'Lato' => 'Lato',
        'Raleway' => 'Raleway',
        'Inter' => 'Inter',
        'Nunito' => 'Nunito',
        'Encode Sans' => 'Encode Sans',
        'Work Sans' => 'Work Sans',
        'Oxygen' => 'Oxygen',
        'Manrope' => 'Manrope',
        'Sora' => 'Sora',
        'Epilogue' => 'Epilogue',
    ];

    $name = 'theme_ivana/fontsite';
    $title = get_string('fontsite', 'theme_ivana');
    $description = get_string('fontsite_desc', 'theme_ivana');
    $setting = new admin_setting_configselect($name, $title, $description, 'Roboto', $fontsarr);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_ivana/enablecourseindex';
    $title = get_string('enablecourseindex', 'theme_ivana');
    $description = get_string('enablecourseindex_desc', 'theme_ivana');
    $default = 1;
    $choices = [0 => get_string('no'), 1 => get_string('yes')];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $page->add($setting);

    $name = 'theme_ivana/enableclassicbreadcrumb';
    $title = get_string('enableclassicbreadcrumb', 'theme_ivana');
    $description = get_string('enableclassicbreadcrumb_desc', 'theme_ivana');
    $default = 0;
    $choices = [0 => get_string('no'), 1 => get_string('yes')];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $page->add($setting);

    // Must add the page after definiting all the settings!
    $settings->add($page);

    /*
    * ----------------------
    * Advanced settings tab
    * ----------------------
    */
    $page = new admin_settingpage('theme_ivana_advanced', get_string('advancedsettings', 'theme_ivana'));

    // Raw SCSS to include before the content.
    $setting = new admin_setting_scsscode(
        'theme_ivana/scsspre',
        get_string('rawscsspre', 'theme_ivana'),
        get_string('rawscsspre_desc', 'theme_ivana'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content.
    $setting = new admin_setting_scsscode(
        'theme_ivana/scss',
        get_string('rawscss', 'theme_ivana'),
        get_string('rawscss_desc', 'theme_ivana'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Google analytics block.
    $name = 'theme_ivana/googleanalytics';
    $title = get_string('googleanalytics', 'theme_ivana');
    $description = get_string('googleanalyticsdesc', 'theme_ivana');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // H5P custom CSS.
    $setting = new admin_setting_configtextarea('theme_ivana/hvpcss', get_string('hvpcss', 'theme_ivana'), get_string('hvpcss_desc', 'theme_ivana'), '');
    $page->add($setting);

    $settings->add($page);

    /*
    * -----------------------
    * Frontpage settings tab
    * -----------------------
    */
    $page = new admin_settingpage('theme_ivana_frontpage', get_string('frontpagesettings', 'theme_ivana'));

    // Disable teachers from cards.
    $name = 'theme_ivana/disableteacherspic';
    $title = get_string('disableteacherspic', 'theme_ivana');
    $description = get_string('disableteacherspicdesc', 'theme_ivana');
    $default = 1;
    $choices = [0 => get_string('no'), 1 => get_string('yes')];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $page->add($setting);

    $settings->add($page);

    /*
    * --------------------
    * Footer settings tab
    * --------------------
    */
    $page = new admin_settingpage('theme_ivana_footer', get_string('footersettings', 'theme_ivana'));

    // Website.
    $name = 'theme_ivana/website';
    $title = get_string('website', 'theme_ivana');
    $description = get_string('websitedesc', 'theme_ivana');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Mobile.
    $name = 'theme_ivana/mobile';
    $title = get_string('mobile', 'theme_ivana');
    $description = get_string('mobiledesc', 'theme_ivana');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Mail.
    $name = 'theme_ivana/mail';
    $title = get_string('mail', 'theme_ivana');
    $description = get_string('maildesc', 'theme_ivana');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Facebook url setting.
    $name = 'theme_ivana/facebook';
    $title = get_string('facebook', 'theme_ivana');
    $description = get_string('facebookdesc', 'theme_ivana');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Twitter url setting.
    $name = 'theme_ivana/twitter';
    $title = get_string('twitter', 'theme_ivana');
    $description = get_string('twitterdesc', 'theme_ivana');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Linkdin url setting.
    $name = 'theme_ivana/linkedin';
    $title = get_string('linkedin', 'theme_ivana');
    $description = get_string('linkedindesc', 'theme_ivana');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Youtube url setting.
    $name = 'theme_ivana/youtube';
    $title = get_string('youtube', 'theme_ivana');
    $description = get_string('youtubedesc', 'theme_ivana');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Instagram url setting.
    $name = 'theme_ivana/instagram';
    $title = get_string('instagram', 'theme_ivana');
    $description = get_string('instagramdesc', 'theme_ivana');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Whatsapp url setting.
    $name = 'theme_ivana/whatsapp';
    $title = get_string('whatsapp', 'theme_ivana');
    $description = get_string('whatsappdesc', 'theme_ivana');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Telegram url setting.
    $name = 'theme_ivana/telegram';
    $title = get_string('telegram', 'theme_ivana');
    $description = get_string('telegramdesc', 'theme_ivana');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    $settings->add($page);
}
