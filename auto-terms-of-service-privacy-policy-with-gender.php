<?php
/*
  Plugin Name: Auto Terms of Service and Privacy Policy
  Plugin URI: http://wordpress.org/extend/plugins/auto-terms-of-service-and-privacy-policy/
  Description: Puts your own information into a version of Automattic's <a href="http://en.wordpress.com/tos/">Terms of Service</a> and <a href="http://automattic.com/privacy/">Privacy Policy</a>, both available under the <a href="http://creativecommons.org/licenses/by-sa/3.0/">Creative Commons Sharealike</a> license, that have been modified to exclude specifics to Automattic (like mentions of "JetPack", "WordPress.com", and "VIP") and have more generic language that can apply to most any site or service provider, including single sites, subscription sites, blog networks, and others. <strong>Edit plugin's settings, then use one or more of the 3 available shortcodes: [my_terms_of_service_and_privacy_policy], [my_terms_of_service], and/or [my_privacy_policy]
  Version: 1.4.3
  Author: TourKick (Clifford P)
  Author URI: http://twitter.com/TourKick
  License: GPL2 - http://codex.wordpress.org/Writing_a_Plugin#License
 */
/*
  WARNING: I'm human. I'm not perfect. Neither are you. Neither is this...
  But hopefully it's more good than bad.
 */
/*
  DISCLAIMER: I am not an attorney. I am not liable for any content, code, or other errors or omissions or inaccuracies. This plugin provides no warranties or guarantees. Do not rely on me to update the plugin ever. USE AT YOUR OWN RISK. I am not liable if Automattic removes their permission to use and adapt their work or if this plugin blows you or your website up or does anything negative. Finally, it needs to be said: READ YOUR OWN TERMS OF SERVICE AND PRIVACY POLICY BEFORE PUBLISHING. If you do not like Automattic's version, simply replacing their info with yours, maybe this plugin is not for you.
  -Clifford Paulick and/or TourKick LLC
 */
/*
  To-Do List:
  1) Should it be more MultiSite friendly so each site can have their own information? It doesn't currently break MultiSite, but it's not quite a MultiSite plugin.
  2) Add smartquotes / wptexturize? I tried but didn't get it to work.
 */

// Setting up gender strings and constant
define("TCPP_MALE", 0);
define("TCPP_FEMALE", 1);
define("TCPP_NEUTRAL", 2);
define("TCPP_TOS", 0);
define("TCPP_PP", 1);

class ATOSPP_Gender_Constants {

    public static $tcpp_posessive_gender = array("his", "her", "our");
    public static $tcpp_objective_gender = array("him", "her", "us");
    public static $tcpp_subjective_gender = array("he", "she", "we");

}

// plugin folder path
if (!defined('TCPP_PLUGIN_DIR')) {
    define('TCPP_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
// plugin folder url
// Do not add closing PHP tag
if (!defined('TCPP_PLUGIN_URL')) {
    define('TCPP_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Add settings link on plugin page from http://bavotasan.com/2009/a-settings-link-for-your-wordpress-plugins/
function atospp_plugin_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=auto-terms-of-service-and-privacy-policy/auto-terms-of-service-privacy-policy.php">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'atospp_plugin_settings_link');



/* --------------------- OPTIONS PAGE ------------------ */

class ATOSPP_Options {

    public $options;

    public function __construct() {
//delete_option('atospp_plugin_options');
        $this->options = get_option('atospp_plugin_options');
        $this->register_settings_and_fields();
    }

    public static function add_menu_page() {
        add_options_page('Auto TOS & PP Options', 'Auto TOS & PP', 'administrator', __FILE__, array('ATOSPP_Options', 'display_options_page'));
    }

    public static function display_options_page() {
        ?>

        <div class="wrap">

            <h2>Auto Terms of Service and Privacy Policy Options</h2>

            <form action="options.php" method="post" enctype="multipart/form-data">
                <?php settings_fields('atospp_plugin_options'); ?>

        <?php do_settings_sections(__FILE__); ?>

                <p class="submit">
                    <input name="submit" type="submit" class="button-primary" value="Save Changes" />
                </p>
            </form>

        </div>

        <?php
    }

    public function register_settings_and_fields() {

        register_setting('atospp_plugin_options', 'atospp_plugin_options');
        add_settings_section('atospp_section', 'Settings<br/><br/><hr/><span style="font-size: 80%;">Available shortcodes:<ul><li>[my_terms_of_service_and_privacy_policy]</li><li> [my_terms_of_service]</li><li>[my_privacy_policy]</li></ul></span><hr/>', array($this, 'atospp_section_cb'), __FILE__);

        add_settings_field('atospp_onoff', 'On/Off:<br/><small><span style="color:darkred;">Enter all your info below, then Turn On so shortcodes can work.</span><br/><span style="color:red;">Will not allow you to Turn On until you enter all required <span style="color:red;">(*)</span> fields.</span></small>', array($this, 'atospp_onoff_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_tos_heading', '<span style="color:red;">(*)</span> TOS Heading:<br/><small>e.g. Terms of Service, Terms of Use</small>', array($this, 'atospp_tos_heading_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_pp_heading', '<span style="color:red;">(*)</span> PP Heading:<br/><small>e.g. Privacy Policy</small>', array($this, 'atospp_pp_heading_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_namefull', '<span style="color:red;">(*)</span> Full Name:<br/><small>e.g. Automattic Inc.</small>', array($this, 'atospp_namefull_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_name', '<span style="color:red;">(*)</span> Name:<br/><small>e.g. Automattic</small>', array($this, 'atospp_name_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_namepossessive', '<span style="color:red;">(*)</span> Possessive Name:<br/><small>e.g. Automattic\'s</small>', array($this, 'atospp_namepossessive_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_domainname', '<span style="color:red;">(*)</span> Domain Name:<br/><small>e.g. Automattic.com</small>', array($this, 'atospp_domainname_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_websiteurl', '<span style="color:red;">(*)</span> Official Website URL:<br/><small>e.g. http://www.wordpress.com/</small>', array($this, 'atospp_websiteurl_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_minage', '<span style="color:red;">(*)</span> Minimum Age:<br/><small>e.g. 13</small>', array($this, 'atospp_minage_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_time_feesnotifications', '<span style="color:red;">(*)</span> Time Period for changing fees and for notifications:<br/><small>e.g. thirty (30) days</small>', array($this, 'atospp_time_feesnotifications_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_time_replytopriorityemail', '<span style="color:red;">(*)</span> Time Period for replying to priority email:<br/><small>e.g. one business day</small>', array($this, 'atospp_time_replytopriorityemail_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_time_determiningmaxdamages', '<span style="color:red;">(*)</span> Time Period for determining maximum damages:<br/><small>e.g. twelve (12) month<br/><span style="color:darkred;">Notice no "S" on "month"</span></small>', array($this, 'atospp_time_determiningmaxdamages_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_dmcanoticeurl', 'DMCA Notice URL:<br/><small>e.g. http://automattic.com/dmca-notice/<br/><span style="color:darkred;">If left blank, sentence about reporting DMCA violations will be shown but not hyperlinked.</span></small>', array($this, 'atospp_dmcanoticeurl_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_venue', '<span style="color:red;">(*)</span> Venue:<br/><small>e.g. state of California, U.S.A.</small>', array($this, 'atospp_venue_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_courtlocation', '<span style="color:red;">(*)</span> Court Location:<br/><small>e.g. San Francisco County, California</small>', array($this, 'atospp_courtlocation_setting'), __FILE__, 'atospp_section');
        add_settings_field('atospp_arbitrationlocation', '<span style="color:red;">(*)</span> Arbitration Location:<br/><small>e.g. San Francisco, California</small>', array($this, 'atospp_arbitrationlocation_setting'), __FILE__, 'atospp_section');

// Added for gender check
        add_settings_field(
                'atospp_gender', 'Gender', array($this, 'atospp_gender_setting'), __FILE__, 'atospp_section'
        );
    }

    public function atospp_section_cb() {
//Optional
    }

    /*
      INPUTS
     */

    public function atospp_tos_heading_setting() {
        $tos_heading = "Terms of Service";
        if (!empty($this->options['atospp_tos_heading'])) {
            $tos_heading = $this->options['atospp_tos_heading'];
        }
        echo "<input name='atospp_plugin_options[atospp_tos_heading]' type='text' value='{$tos_heading}' />";
    }

    public function atospp_pp_heading_setting() {
        $pp_heading = "Privacy Policy";
        if (!empty($this->options['atospp_pp_heading'])) {
            $pp_heading = $this->options['atospp_pp_heading'];
        }
        echo "<input name='atospp_plugin_options[atospp_pp_heading]' type='text' value='{$pp_heading}' />";
    }

    public function atospp_namefull_setting() {
        $namefull = "";
        if (!empty($this->options['atospp_namefull'])) {
            $namefull = $this->options['atospp_namefull'];
        }
        echo "<input name='atospp_plugin_options[atospp_namefull]' type='text' value='{$namefull}' />";
    }

    public function atospp_name_setting() {
        $name = "";
        if (!empty($this->options['atospp_name'])) {
            $name = $this->options['atospp_name'];
        }
        echo "<input name='atospp_plugin_options[atospp_name]' type='text' value='{$name}' />";
    }

    public function atospp_namepossessive_setting() {
        $namepossessive = "";
        if (!empty($this->options['atospp_namepossessive'])) {
            $namepossessive = $this->options['atospp_namepossessive'];
        }
//" instead of ' because of apostrophe in possessive
        echo '<input name="atospp_plugin_options[atospp_namepossessive]" type="text" value="' . $namepossessive . '" />';
    }

    public function atospp_domainname_setting() {
        $domainname = "";
        if (!empty($this->options['atospp_domainname'])) {
            $domainname = $this->options['atospp_domainname'];
        }
        echo "<input name='atospp_plugin_options[atospp_domainname]' type='text' value='{$domainname}' />";
    }

    public function atospp_websiteurl_setting() {
        $websiteurl = get_site_url();
        if (!empty($this->options['atospp_websiteurl'])) {
            $websiteurl = $this->options['atospp_websiteurl'];
        }
        echo "<input name='atospp_plugin_options[atospp_websiteurl]' type='text' value='{$websiteurl}' />";
    }

    public function atospp_minage_setting() {
        $minage = "13";
        if (!empty($this->options['atospp_minage'])) {
            $minage = $this->options['atospp_minage'];
        }
        echo "<input name='atospp_plugin_options[atospp_minage]' type='text' value='{$minage}' />";
    }

    public function atospp_time_feesnotifications_setting() {
        $timefeesnotifications = "thirty (30) days";
        if (!empty($this->options['atospp_time_feesnotifications'])) {
            $timefeesnotifications = $this->options['atospp_time_feesnotifications'];
        }
        echo "<input name='atospp_plugin_options[atospp_time_feesnotifications]' type='text' value='{$timefeesnotifications}' />";
    }

    public function atospp_time_replytopriorityemail_setting() {
        $timereplytopriorityemail = "one business day";
        if (!empty($this->options['atospp_time_replytopriorityemail'])) {
            $timereplytopriorityemail = $this->options['atospp_time_replytopriorityemail'];
        }
        echo "<input name='atospp_plugin_options[atospp_time_replytopriorityemail]' type='text' value='{$timereplytopriorityemail}' />";
    }

    public function atospp_time_determiningmaxdamages_setting() {
        $timedeterminingmaxdamages = "twelve (12) month";
        if (!empty($this->options['atospp_time_determiningmaxdamages'])) {
            $timedeterminingmaxdamages = $this->options['atospp_time_determiningmaxdamages'];
        }
        echo "<input name='atospp_plugin_options[atospp_time_determiningmaxdamages]' type='text' value='{$timedeterminingmaxdamages}' />";
    }

    public function atospp_dmcanoticeurl_setting() {
        $dmcanoticeurl = "";
        if (!empty($this->options['atospp_dmcanoticeurl'])) {
            $dmcanoticeurl = $this->options['atospp_dmcanoticeurl'];
        }
        echo "<input name='atospp_plugin_options[atospp_dmcanoticeurl]' type='text' value='{$dmcanoticeurl}' />";
    }

    public function atospp_venue_setting() {
        $venue = "";
        if (!empty($this->options['atospp_venue'])) {
            $venue = $this->options['atospp_venue'];
        }
        echo "<input name='atospp_plugin_options[atospp_venue]' type='text' value='{$venue}' />";
    }

    public function atospp_courtlocation_setting() {
        $courtlocation = "";
        if (!empty($this->options['atospp_courtlocation'])) {
            $courtlocation = $this->options['atospp_courtlocation'];
        }
        echo "<input name='atospp_plugin_options[atospp_courtlocation]' type='text' value='{$courtlocation}' />";
    }

    public function atospp_arbitrationlocation_setting() {
        $arbitrationlocation = "";
        if (!empty($this->options['atospp_arbitrationlocation'])) {
            $arbitrationlocation = $this->options['atospp_arbitrationlocation'];
        }
        echo "<input name='atospp_plugin_options[atospp_arbitrationlocation]' type='text' value='{$arbitrationlocation}' />";
    }

// Setting the gender, uses a radio button that defaults to "Neutral."
    public function atospp_gender_setting() {

        if (empty($this->options['atospp_gender'])) {
            $this->options['atospp_gender'] = TCPP_NEUTRAL;
        }

        // echo $this->options['atospp_gender'] . '<br>';

        $html = '<input type="radio" id="atospp_gender_male" name="atospp_plugin_options[atospp_gender]" value="' . TCPP_MALE . '"' . checked(1, $this->options['atospp_gender'] == TCPP_MALE, false) . '/>';
        $html .= '<label for="atospp_gender_male">Male</label>';

        $html .= '<input type="radio" id="atospp_gender_female" name="atospp_plugin_options[atospp_gender]" value="' . TCPP_FEMALE . '"' . checked(1, $this->options['atospp_gender'] == TCPP_FEMALE, false) . '/>';
        $html .= '<label for="atospp_gender_female">Female</label>';

        $html .= '<input type="radio" id="atospp_gender_neutral" name="atospp_plugin_options[atospp_gender]" value="' . TCPP_NEUTRAL . '"' . checked(1, $this->options['atospp_gender'] == TCPP_NEUTRAL, false) . '/>';
        $html .= '<label for="atospp_gender_neutral">Neutral</label>';

        echo $html;
    }

// last so it can check required fields!
    public function atospp_onoff_setting() {
        $onoff = 'atospp_off';
        if (
                !empty($this->options['atospp_onoff']) && !empty($this->options['atospp_tos_heading']) && !empty($this->options['atospp_pp_heading']) && !empty($this->options['atospp_namefull']) && !empty($this->options['atospp_name']) && !empty($this->options['atospp_namepossessive']) && !empty($this->options['atospp_domainname']) && !empty($this->options['atospp_websiteurl']) && !empty($this->options['atospp_minage']) && !empty($this->options['atospp_time_feesnotifications']) && !empty($this->options['atospp_time_replytopriorityemail']) && !empty($this->options['atospp_time_determiningmaxdamages']) && !empty($this->options['atospp_venue']) && !empty($this->options['atospp_courtlocation']) && !empty($this->options['atospp_arbitrationlocation'])
        ) {
            $onoff = $this->options['atospp_onoff'];
        }

        $off = "";
        if ($onoff == 'atospp_off') {
            $off = "selected='selected'";
        }

        $on = "";
        if ($onoff == 'atospp_on') {
            $on = "selected='selected'";
        }

        echo "<select name='atospp_plugin_options[atospp_onoff]'>";
        echo "<option value='atospp_off' $off>Off / Coming Soon</option>";
        echo "<option value='atospp_on' $on>On / Displaying</option>";
        echo "</select>";
    }

}

add_action('admin_menu', 'initOptionsATOSPP');

function initOptionsATOSPP() {
    ATOSPP_Options::add_menu_page();
}

add_action('admin_init', 'initAdminATOSPP');

function initAdminATOSPP() {
    new ATOSPP_Options();
}

/* --------------------- END OPTIONS PAGE ------------------ */

/* --------------------- GET TEXT FUNCTIONS ------------------ */

function get_atospp_legal_text($which_text) {

    $options = get_option('atospp_plugin_options');

    if (
            empty($options['atospp_onoff']) || empty($options['atospp_tos_heading']) || empty($options['atospp_pp_heading']) || empty($options['atospp_namefull']) || empty($options['atospp_name']) || empty($options['atospp_namepossessive']) || empty($options['atospp_domainname']) || empty($options['atospp_websiteurl']) || empty($options['atospp_minage']) || empty($options['atospp_time_feesnotifications']) || empty($options['atospp_time_replytopriorityemail']) || empty($options['atospp_time_determiningmaxdamages']) || empty($options['atospp_venue']) || empty($options['atospp_courtlocation']) || empty($options['atospp_arbitrationlocation'])
    ) {
        $tcpp_publish = 'atospp_off';
    } else {
        $tcpp_publish = $options['atospp_onoff'];
    }

    $tcpp_termsheading = $options['atospp_tos_heading'];
    $tcpp_privacypolicyheading = $options['atospp_pp_heading'];
    $tcpp_biznamefull = $options['atospp_namefull'];
    $tcpp_bizname = $options['atospp_name'];
    $tcpp_biznamepossessive = $options['atospp_namepossessive'];
    $tcpp_domainname = $options['atospp_domainname'];
    $tcpp_websiteurl = $options['atospp_websiteurl'];
    $tcpp_minimumage = $options['atospp_minage'];
    $tcpp_timeperiodforchangingfeesandfornotifications = $options['atospp_time_feesnotifications'];
    $tcpp_timeperiodtoreplytopriorityemail = $options['atospp_time_replytopriorityemail'];
    $tcpp_timeperiodfordeterminingmaximumdamages = $options['atospp_time_determiningmaxdamages'];

    $tcpp_dmcanoticeurl = $options['atospp_dmcanoticeurl'];

    if (!empty($tcpp_dmcanoticeurl)) {
        $tcpp_dmcaoutput = "$tcpp_bizname in accordance with <a href=\"$tcpp_dmcanoticeurl\">$tcpp_biznamepossessive Digital Millennium Copyright Act (&quot;DMCA&quot;) Policy</a>";
    } else {
        $tcpp_dmcaoutput = "$tcpp_bizname in accordance with $tcpp_biznamepossessive Digital Millennium Copyright Act (&quot;DMCA&quot;) Policy";
    }

    $tcpp_venue = $options['atospp_venue'];
    $tcpp_courtlocation = $options['atospp_courtlocation'];
    $tcpp_arbitrationlocation = $options['atospp_arbitrationlocation'];

    // Setting up gender strings
    $tcpp_posessive_gender = ATOSPP_Gender_Constants::$tcpp_posessive_gender[$options['atospp_gender']];
    $tcpp_objective_gender = ATOSPP_Gender_Constants::$tcpp_objective_gender[$options['atospp_gender']];
    $tcpp_subjective_gender = ATOSPP_Gender_Constants::$tcpp_subjective_gender[$options['atospp_gender']];

    if ($options['atospp_gender'] == TCPP_NEUTRAL)
    {
        $tcpp_haveorhas = "have";
        $tcpp_endorseorendorses = "endorse";
        $tcpp_respectorrespects = "respect"; 
        $tcpp_askorasks = "ask";
        $tcpp_reserveorreserves = "reserve";
        $tcpp_considerorconsiders = "consider";
    }
    else
    {
        $tcpp_haveorhas = "has";
        $tcpp_endorseorendorses = "endorses";
        $tcpp_respectorrespects = "respects";
        $tcpp_askorasks = "asks";
        $tcpp_reserveorreserves = "reserves";
        $tcpp_considerorconsiders = "considers";
    }
    

   

    $tcpp_tcond = "<h3 class='auto-tos-pp tosheading'>$tcpp_termsheading:</h3>
<p><a class='auto-tos-pp' href='#atospp'>Back to top</a></p>

<p>The following terms and conditions govern all use of the $tcpp_domainname website and all content, services and products available at or through the website (taken together, the Website). The Website is owned and operated by $tcpp_biznamefull (&quot;$tcpp_bizname&quot;). The Website is offered subject to your acceptance without modification of all of the terms and conditions contained herein and all other operating rules, policies (including, without limitation, $tcpp_biznamepossessive $tcpp_privacypolicyheading) and procedures that may be published from time to time on this Site by $tcpp_bizname (collectively, the &quot;Agreement&quot;).</p>
<p>Please read this Agreement carefully before accessing or using the Website. By accessing or using any part of the web site, you agree to become bound by the terms and conditions of this agreement. If you do not agree to all the terms and conditions of this agreement, then you may not access the Website or use any services. If these terms and conditions are considered an offer by $tcpp_bizname, acceptance is expressly limited to these terms. The Website is available only to individuals who are at least $tcpp_minimumage years old.</p>
<ol>
<li><strong>Your $tcpp_domainname Account and Site.</strong> If you create a blog/site on the Website, you are responsible for maintaining the security of your account and blog, and you are fully responsible for all activities that occur under the account and any other actions taken in connection with the blog. You must not describe or assign keywords to your blog in a misleading or unlawful manner, including in a manner intended to trade on the name or reputation of others, and $tcpp_bizname may change or remove any description or keyword that $tcpp_subjective_gender $tcpp_considerorconsiders inappropriate or unlawful, or otherwise likely to cause $tcpp_bizname liability. You must immediately notify $tcpp_bizname of any unauthorized uses of your blog, your account or any other breaches of security. $tcpp_bizname will not be liable for any acts or omissions by You, including any damages of any kind incurred as a result of such acts or omissions.</li>
<li><strong>Responsibility of Contributors.</strong> If you operate a blog, comment on a blog, post material to the Website, post links on the Website, or otherwise make (or allow any third party to make) material available by means of the Website (any such material, &quot;Content&quot;), You are entirely responsible for the content of, and any harm resulting from, that Content. That is the case regardless of whether the Content in question constitutes text, graphics, an audio file, or computer software. By making Content available, you represent and warrant that:
<ul>
<li>the downloading, copying and use of the Content will not infringe the proprietary rights, including but not limited to the copyright, patent, trademark or trade secret rights, of any third party;</li>
<li>if your employer has rights to intellectual property you create, you have either (i) received permission from your employer to post or make available the Content, including but not limited to any software, or (ii) secured from your employer a waiver as to all rights in or to the Content;</li>
<li>you have fully complied with any third-party licenses relating to the Content, and have done all things necessary to successfully pass through to end users any required terms;</li>
<li>the Content does not contain or install any viruses, worms, malware, Trojan horses or other harmful or destructive content;</li>
<li class=&quot;important&quot;>the Content is not spam, is not machine- or randomly-generated, and does not contain unethical or unwanted commercial content designed to drive traffic to third party sites or boost the search engine rankings of third party sites, or to further unlawful acts (such as phishing) or mislead recipients as to the source of the material (such as spoofing);</li>
<li>the Content is not pornographic, does not contain threats or incite violence towards individuals or entities, and does not violate the privacy or publicity rights of any third party;</li>
<li>your blog is not getting advertised via unwanted electronic messages such as spam links on newsgroups, email lists, other blogs and web sites, and similar unsolicited promotional methods;</li>
<li>your blog is not named in a manner that misleads your readers into thinking that you are another person or company. For example, your blog's URL or name is not the name of a person other than yourself or company other than your own; and</li>
<li>you have, in the case of Content that includes computer code, accurately categorized and/or described the type, nature, uses and effects of the materials, whether requested to do so by $tcpp_bizname or otherwise.</li>
</ul>
<p>By submitting Content to $tcpp_bizname for inclusion on your Website, you grant $tcpp_bizname a world-wide, royalty-free, and non-exclusive license to reproduce, modify, adapt and publish the Content solely for the purpose of displaying, distributing and promoting your blog. If you delete Content, $tcpp_bizname will use reasonable efforts to remove it from the Website, but you acknowledge that caching or references to the Content may not be made immediately unavailable.</p>
<p>Without limiting any of those representations or warranties, $tcpp_bizname has the right (though not the obligation) to, in $tcpp_biznamepossessive sole discretion (i) refuse or remove any content that, in $tcpp_biznamepossessive reasonable opinion, violates any $tcpp_bizname policy or is in any way harmful or objectionable, or (ii) terminate or deny access to and use of the Website to any individual or entity for any reason, in $tcpp_biznamepossessive sole discretion. $tcpp_bizname will have no obligation to provide a refund of any amounts previously paid.</li>
<li><strong>Payment and Renewal.</strong>
<ul>
<li><strong>General Terms.</strong><br />
By selecting a product or service, you agree to pay $tcpp_bizname the one-time and/or monthly or annual subscription fees indicated (additional payment terms may be included in other communications). Subscription payments will be charged on a pre-pay basis on the day you sign up for an Upgrade and will cover the use of that service for a monthly or annual subscription period as indicated. Payments are not refundable.</li>
<li><strong>Automatic Renewal. </strong><br />
Unless you notify $tcpp_bizname before the end of the applicable subscription period that you want to cancel a subscription, your subscription will automatically renew and you authorize $tcpp_objective_gender to collect the then-applicable annual or monthly subscription fee for such subscription (as well as any taxes) using any credit card or other payment mechanism $tcpp_subjective_gender $tcpp_haveorhas on record for you. Upgrades can be canceled at any time by submitting your request to $tcpp_bizname in writing.</li>
</ul>
</li>
<li><strong>Services.</strong></li>
<ul>
<li><strong>Fees; Payment. </strong>By signing up for a Services account you agree to pay $tcpp_bizname the applicable setup fees and recurring fees. Applicable fees will be invoiced starting from the day your services are established and in advance of using such services. $tcpp_bizname reserves the right to change the payment terms and fees upon $tcpp_timeperiodforchangingfeesandfornotifications prior written notice to you. Services can be canceled by you at anytime on $tcpp_timeperiodforchangingfeesandfornotifications written notice to $tcpp_bizname.</li>
<li><strong>Support.</strong> If your service includes access to priority email support. &quot;Email support&quot; means the ability to make requests for technical support assistance by email at any time (with reasonable efforts by $tcpp_bizname to respond within $tcpp_timeperiodtoreplytopriorityemail) concerning the use of the VIP Services. &quot;Priority&quot; means that support takes priority over support for users of the standard or free $tcpp_domainname services. All support will be provided in accordance with $tcpp_bizname standard services practices, procedures and policies.</li>
</ul>
<li><strong>Responsibility of Website Visitors.</strong> $tcpp_bizname has not reviewed, and cannot review, all of the material, including computer software, posted to the Website, and cannot therefore be responsible for that material's content, use or effects. By operating the Website, $tcpp_bizname does not represent or imply that $tcpp_subjective_gender $tcpp_endorseorendorses the material there posted, or that $tcpp_subjective_gender believes such material to be accurate, useful or non-harmful. You are responsible for taking precautions as necessary to protect yourself and your computer systems from viruses, worms, Trojan horses, and other harmful or destructive content. The Website may contain content that is offensive, indecent, or otherwise objectionable, as well as content containing technical inaccuracies, typographical mistakes, and other errors. The Website may also contain material that violates the privacy or publicity rights, or infringes the intellectual property and other proprietary rights, of third parties, or the downloading, copying or use of which is subject to additional terms and conditions, stated or unstated. $tcpp_bizname disclaims any responsibility for any harm resulting from the use by visitors of the Website, or from any downloading by those visitors of content there posted.</li>
<li><strong>Content Posted on Other Websites.</strong> $tcpp_bizname has not reviewed, and cannot review, all of the material, including computer software, made available through the websites and webpages to which $tcpp_domainname links, and that link to $tcpp_domainname. $tcpp_bizname does not have any control over those non-$tcpp_bizname websites and webpages, and is not responsible for their contents or their use. By linking to a non-$tcpp_bizname website or webpage, $tcpp_bizname does not represent or imply that $tcpp_subjective_gender $tcpp_endorseorendorses such website or webpage. You are responsible for taking precautions as necessary to protect yourself and your computer systems from viruses, worms, Trojan horses, and other harmful or destructive content. $tcpp_bizname disclaims any responsibility for any harm resulting from your use of non-$tcpp_bizname websites and webpages.</li>
<li><strong>Copyright Infringement and DMCA Policy.</strong> As $tcpp_bizname asks others to respect $tcpp_posessive_gender intellectual property rights, $tcpp_subjective_gender $tcpp_respectorrespects the intellectual property rights of others. If you believe that material located on or linked to by $tcpp_domainname violates your copyright, you are encouraged to notify $tcpp_dmcaoutput. $tcpp_bizname will respond to all such notices, including as required or appropriate by removing the infringing material or disabling all links to the infringing material. $tcpp_bizname will terminate a visitor's access to and use of the Website if, under appropriate circumstances, the visitor is determined to be a repeat infringer of the copyrights or other intellectual property rights of $tcpp_bizname or others. In the case of such termination, $tcpp_bizname will have no obligation to provide a refund of any amounts previously paid to $tcpp_bizname.</li>
<li><strong>Intellectual Property.</strong> This Agreement does not transfer from $tcpp_bizname to you any $tcpp_bizname or third party intellectual property, and all right, title and interest in and to such property will remain (as between the parties) solely with $tcpp_bizname. $tcpp_bizname, $tcpp_domainname, the $tcpp_domainname logo, and all other trademarks, service marks, graphics and logos used in connection with $tcpp_domainname, or the Website are trademarks or registered trademarks of $tcpp_bizname or $tcpp_biznamepossessive licensors. Other trademarks, service marks, graphics and logos used in connection with the Website may be the trademarks of other third parties. Your use of the Website grants you no right or license to reproduce or otherwise use any $tcpp_bizname or third-party trademarks.</li>
<li><strong>Advertisements.</strong> $tcpp_bizname reserves the right to display advertisements on your blog unless you have purchased an ad-free account.</li>
<li><strong>Attribution.</strong> $tcpp_bizname reserves the right to display attribution links such as 'Blog at $tcpp_domainname,' theme author, and font attribution in your blog footer or toolbar.</li>
<li><strong>Partner Products.</strong> By activating a partner product (e.g. theme) from one of $tcpp_biznamepossessive partners, you agree to that partner's terms of service. You can opt out of their terms of service at any time by de-activating the partner product.</li>
<li><strong>Domain Names.</strong> If you are registering a domain name, using or transferring a previously registered domain name, you acknowledge and agree that use of the domain name is also subject to the policies of the Internet Corporation for Assigned Names and Numbers (&quot;ICANN&quot;), including their <a href=\"http://www.icann.org/en/registrars/registrant-rights-responsibilities-en.htm\">Registration Rights and Responsibilities</a>.</li>
<li><strong>Changes. </strong>$tcpp_bizname reserves the right, at $tcpp_posessive_gender sole discretion, to modify or replace any part of this Agreement. It is your responsibility to check this Agreement periodically for changes. Your continued use of or access to the Website following the posting of any changes to this Agreement constitutes acceptance of those changes. $tcpp_bizname may also, in the future, offer new services and/or features through the Website (including, the release of new tools and resources). Such new features and/or services shall be subject to the terms and conditions of this Agreement. <strong><br />
</strong></li>
<li><strong>Termination. </strong>$tcpp_bizname may terminate your access to all or any part of the Website at any time, with or without cause, with or without notice, effective immediately. If you wish to terminate this Agreement or your $tcpp_domainname account (if you have one), you may simply discontinue using the Website. Notwithstanding the foregoing, if you have a paid services account, such account can only be terminated by $tcpp_bizname if you materially breach this Agreement and fail to cure such breach within $tcpp_timeperiodforchangingfeesandfornotifications from $tcpp_biznamepossessive notice to you thereof; provided that, $tcpp_bizname can terminate the Website immediately as part of a general shut down of $tcpp_objective_gender service. All provisions of this Agreement which by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity and limitations of liability. <strong><br />
</strong></li>
<li class=&quot;important&quot;><strong>Disclaimer of Warranties.</strong> The Website is provided &quot;as is&quot;. $tcpp_bizname and $tcpp_posessive_gender suppliers and licensors hereby disclaim all warranties of any kind, express or implied, including, without limitation, the warranties of merchantability, fitness for a particular purpose and non-infringement. Neither $tcpp_bizname nor $tcpp_posessive_gender suppliers and licensors, makes any warranty that the Website will be error free or that access thereto will be continuous or uninterrupted. You understand that you download from, or otherwise obtain content or services through, the Website at your own discretion and risk.</li>
<li class=&quot;important&quot;><strong>Limitation of Liability.</strong> In no event will $tcpp_bizname, or $tcpp_posessive_gender suppliers or licensors, be liable with respect to any subject matter of this agreement under any contract, negligence, strict liability or other legal or equitable theory for: (i) any special, incidental or consequential damages; (ii) the cost of procurement for substitute products or services; (iii) for interruption of use or loss or corruption of data; or (iv) for any amounts that exceed the fees paid by you to $tcpp_bizname under this agreement during the $tcpp_timeperiodfordeterminingmaximumdamages period prior to the cause of action. $tcpp_bizname shall have no liability for any failure or delay due to matters beyond $tcpp_posessive_gender reasonable control. The foregoing shall not apply to the extent prohibited by applicable law.</li>
<li><strong>General Representation and Warranty.</strong> You represent and warrant that (i) your use of the Website will be in strict accordance with the $tcpp_bizname $tcpp_privacypolicyheading, with this Agreement and with all applicable laws and regulations (including without limitation any local laws or regulations in your country, state, city, or other governmental area, regarding online conduct and acceptable content, and including all applicable laws regarding the transmission of technical data exported from the United States or the country in which you reside) and (ii) your use of the Website will not infringe or misappropriate the intellectual property rights of any third party.</li>
<li><strong>Indemnification.</strong> You agree to indemnify and hold harmless $tcpp_bizname, $tcpp_posessive_gender contractors, and $tcpp_posessive_gender licensors, and their respective directors, officers, employees and agents from and against any and all claims and expenses, including attorneys' fees, arising out of your use of the Website, including but not limited to your violation of this Agreement.</li>
<li><strong>Miscellaneous.</strong> This Agreement constitutes the entire agreement between $tcpp_bizname and you concerning the subject matter hereof, and it may only be modified by a written amendment signed by an authorized executive of $tcpp_bizname, or by the posting by $tcpp_bizname of a revised version. Except to the extent applicable law, if any, provides otherwise, this Agreement, any access to or use of the Website will be governed by the laws of the $tcpp_venue, excluding its conflict of law provisions, and the proper venue for any disputes arising out of or relating to any of the same will be the state and federal courts located in $tcpp_courtlocation. Except for claims for injunctive or equitable relief or claims regarding intellectual property rights (which may be brought in any competent court without the posting of a bond), any dispute arising under this Agreement shall be finally settled in accordance with the Comprehensive Arbitration Rules of the Judicial Arbitration and Mediation Service, Inc. (&quot;JAMS&quot;) by three arbitrators appointed in accordance with such Rules. The arbitration shall take place in $tcpp_arbitrationlocation, in the English language and the arbitral decision may be enforced in any court. The prevailing party in any action or proceeding to enforce this Agreement shall be entitled to costs and attorneys' fees. If any part of this Agreement is held invalid or unenforceable, that part will be construed to reflect the parties' original intent, and the remaining portions will remain in full force and effect. A waiver by either party of any term or condition of this Agreement or any breach thereof, in any one instance, will not waive such term or condition or any subsequent breach thereof. You may assign your rights under this Agreement to any party that consents to, and agrees to be bound by, its terms and conditions; $tcpp_bizname may assign $tcpp_posessive_gender rights under this Agreement without condition. This Agreement will be binding upon and will inure to the benefit of the parties, their successors and permitted assigns.</li>
</ol>";
    
    $tcpp_privacypolicy = "<h3 class='auto-tos-pp ppheading'>$tcpp_privacypolicyheading:</h3>
<p><a class='auto-tos-pp' href='#atospp'>Back to top</a></p>
<p>$tcpp_biznamefull (&quot;<strong>$tcpp_bizname</strong>&quot;) operates $tcpp_domainname and may operate other websites. It is $tcpp_biznamepossessive policy to respect your privacy regarding any information $tcpp_subjective_gender may collect while operating $tcpp_posessive_gender websites.</p>
<h3>Website Visitors</h3>
<p>Like most website operators, $tcpp_bizname collects non-personally-identifying information of the sort that web browsers and servers typically make available, such as the browser type, language preference, referring site, and the date and time of each visitor request. $tcpp_biznamepossessive purpose in collecting non-personally identifying information is to better understand how $tcpp_biznamepossessive visitors use $tcpp_posessive_gender website. From time to time, $tcpp_bizname may release non-personally-identifying information in the aggregate, e.g., by publishing a report on trends in the usage of $tcpp_posessive_gender website.</p>
<p>$tcpp_bizname also collects potentially personally-identifying information like Internet Protocol (IP) addresses for logged in users and for users leaving comments on $tcpp_domainname blogs/sites. $tcpp_bizname only discloses logged in user and commenter IP addresses under the same circumstances that $tcpp_subjective_gender uses and discloses personally-identifying information as described below, except that commenter IP addresses and email addresses are visible and disclosed to the administrators of the blog/site where the comment was left.</p>
<h3>Gathering of Personally-Identifying Information</h3>
<p>Certain visitors to $tcpp_biznamepossessive websites choose to interact with $tcpp_bizname in ways that require $tcpp_bizname to gather personally-identifying information. The amount and type of information that $tcpp_bizname gathers depends on the nature of the interaction. For example, $tcpp_subjective_gender $tcpp_askorasks visitors who sign up at <a href=\"$tcpp_websiteurl\">$tcpp_domainname</a> to provide a username and email address. Those who engage in transactions with $tcpp_bizname are asked to provide additional information, including as necessary the personal and financial information required to process those transactions. In each case, $tcpp_bizname collects such information only insofar as is necessary or appropriate to fulfill the purpose of the visitor's interaction with $tcpp_bizname. $tcpp_bizname does not disclose personally-identifying information other than as described below. And visitors can always refuse to supply personally-identifying information, with the caveat that it may prevent them from engaging in certain website-related activities.</p>
<h3>Aggregated Statistics</h3>
<p>$tcpp_bizname may collect statistics about the behavior of visitors to $tcpp_posessive_gender websites. $tcpp_bizname may display this information publicly or provide it to others. However, $tcpp_bizname does not disclose personally-identifying information other than as described below.</p>
<h3>Protection of Certain Personally-Identifying Information</h3>
<p>$tcpp_bizname discloses potentially personally-identifying and personally-identifying information only to those of $tcpp_posessive_gender employees, contractors and affiliated organizations that (i) need to know that information in order to process it on $tcpp_biznamepossessive behalf or to provide services available at $tcpp_biznamepossessive websites, and (ii) that have agreed not to disclose it to others. Some of those employees, contractors and affiliated organizations may be located outside of your home country; by using $tcpp_biznamepossessive websites, you consent to the transfer of such information to them. $tcpp_bizname will not rent or sell potentially personally-identifying and personally-identifying information to anyone. Other than to $tcpp_posessive_gender employees, contractors and affiliated organizations, as described above, $tcpp_bizname discloses potentially personally-identifying and personally-identifying information only in response to a subpoena, court order or other governmental request, or when $tcpp_bizname believes in good faith that disclosure is reasonably necessary to protect the property or rights of $tcpp_bizname, third parties or the public at large. If you are a registered user of an $tcpp_bizname website and have supplied your email address, $tcpp_bizname may occasionally send you an email to tell you about new features, solicit your feedback, or just keep you up to date with what's going on with $tcpp_bizname and $tcpp_posessive_gender products. If you send $tcpp_objective_gender a request (for example via email or via one of our feedback mechanisms), $tcpp_subjective_gender $tcpp_reserveorreserves the right to publish it in order to help $tcpp_objective_gender clarify or respond to your request or to help $tcpp_objective_gender support other users. $tcpp_bizname takes all measures reasonably necessary to protect against the unauthorized access, use, alteration or destruction of potentially personally-identifying and personally-identifying information.</p>
<h3>Cookies</h3>
<p>A cookie is a string of information that a website stores on a visitor's computer, and that the visitor's browser provides to the website each time the visitor returns. $tcpp_bizname uses cookies to help $tcpp_bizname identify and track visitors, their usage of $tcpp_bizname website, and their website access preferences. $tcpp_bizname visitors who do not wish to have cookies placed on their computers should set their browsers to refuse cookies before using $tcpp_biznamepossessive websites, with the drawback that certain features of $tcpp_biznamepossessive websites may not function properly without the aid of cookies.</p>
<h3>Business Transfers</h3>
<p>If $tcpp_bizname, or substantially all of $tcpp_posessive_gender assets, were acquired, or in the unlikely event that $tcpp_bizname goes out of business or enters bankruptcy, user information would be one of the assets that is transferred or acquired by a third party. You acknowledge that such transfers may occur, and that any acquirer of $tcpp_bizname may continue to use your personal information as set forth in this policy.</p>
<h3>Ads</h3>
<p>Ads appearing on any of $tcpp_posessive_gender websites may be delivered to users by advertising partners, who may set cookies. These cookies allow the ad server to recognize your computer each time they send you an online advertisement to compile information about you or others who use your computer. This information allows ad networks to, among other things, deliver targeted advertisements that they believe will be of most interest to you. This Privacy Policy covers the use of cookies by $tcpp_bizname and does not cover the use of cookies by any advertisers.</p>
<h3>$tcpp_privacypolicyheading Changes</h3>
<p>Although most changes are likely to be minor, $tcpp_bizname may change $tcpp_posessive_gender $tcpp_privacypolicyheading from time to time, and in $tcpp_biznamepossessive sole discretion. $tcpp_bizname encourages visitors to frequently check this page for any changes to $tcpp_posessive_gender $tcpp_privacypolicyheading. If you have a $tcpp_domainname account, you might also receive an alert informing you of these changes. Your continued use of this site after any change in this $tcpp_privacypolicyheading will constitute your acceptance of such change.</p>";


    if( $which_text == TCPP_TOS)
        return $tcpp_tcond;
    else
        return $tcpp_privacypolicy;
}

/* --------------------- SHORTCODES ------------------ */

// shortcode [my_terms_of_service_and_privacy_policy]
function my_terms_of_service_and_privacy_policy_func() {
    
        $options = get_option('atospp_plugin_options');

    if (
            empty($options['atospp_onoff']) || empty($options['atospp_tos_heading']) || empty($options['atospp_pp_heading']) || empty($options['atospp_namefull']) || empty($options['atospp_name']) || empty($options['atospp_namepossessive']) || empty($options['atospp_domainname']) || empty($options['atospp_websiteurl']) || empty($options['atospp_minage']) || empty($options['atospp_time_feesnotifications']) || empty($options['atospp_time_replytopriorityemail']) || empty($options['atospp_time_determiningmaxdamages']) || empty($options['atospp_venue']) || empty($options['atospp_courtlocation']) || empty($options['atospp_arbitrationlocation'])
    ) {
        $tcpp_publish = 'atospp_off';
    } else {
        $tcpp_publish = $options['atospp_onoff'];
    }
    
        $tcpp_termsheading = $options['atospp_tos_heading'];
    $tcpp_privacypolicyheading = $options['atospp_pp_heading'];

    $tcpp_tcond = get_atospp_legal_text(TCPP_TOS);
    $tcpp_privacypolicy = get_atospp_legal_text(TCPP_PP);
    
    $tcpp_combinedtermsandprivacy = "<a name='atospp'></a><h3 class='auto-tos-pp tospptocheading'>Contents:</h3>
<ol class='auto-tos-pp tospptoc'>
<li><a href=#terms>$tcpp_termsheading</a></li>
<li><a href=#privacy>$tcpp_privacypolicyheading</a></li>
</ol>
<a name=\"terms\"></a>

<hr class='auto-tos-pp tosppbeforetos' />
$tcpp_tcond
<a name=\"privacy\"></a>

<hr class='auto-tos-pp tosppbeforepp' />
$tcpp_privacypolicy";



    $settingspage = admin_url('options-general.php?page=auto-terms-of-service-and-privacy-policy/auto-terms-of-service-privacy-policy.php');

    $a = "";
    if (!empty($tcpp_combinedtermsandprivacy) && $tcpp_publish == 'atospp_on') {
        $a .= $tcpp_combinedtermsandprivacy;
    } elseif (current_user_can('edit_plugins')) {
        $a .= "Terms and Privacy Policy are coming soon. <a href='$settingspage'>Configure this plugin's settings.</a><br/>";
    } else {
        $a .= "Terms and Privacy Policy are coming soon.<br/>";
    }

    return $a;
}

add_shortcode('my_terms_of_service_and_privacy_policy', 'my_terms_of_service_and_privacy_policy_func');

// shortcode [my_terms_of_service]
function my_terms_of_service_func() {

    $options = get_option('atospp_plugin_options');

    if (
            empty($options['atospp_onoff']) || empty($options['atospp_tos_heading']) || empty($options['atospp_pp_heading']) || empty($options['atospp_namefull']) || empty($options['atospp_name']) || empty($options['atospp_namepossessive']) || empty($options['atospp_domainname']) || empty($options['atospp_websiteurl']) || empty($options['atospp_minage']) || empty($options['atospp_time_feesnotifications']) || empty($options['atospp_time_replytopriorityemail']) || empty($options['atospp_time_determiningmaxdamages']) || empty($options['atospp_venue']) || empty($options['atospp_courtlocation']) || empty($options['atospp_arbitrationlocation'])
    ) {
        $tcpp_publish = 'atospp_off';
    } else {
        $tcpp_publish = $options['atospp_onoff'];
    }

    $tcpp_tcond = get_atospp_legal_text(TCPP_TOS);

    $settingspage = admin_url('options-general.php?page=auto-terms-of-service-and-privacy-policy/auto-terms-of-service-privacy-policy.php');

    $b = "";
    if (!empty($tcpp_tcond) && $tcpp_publish == 'atospp_on') {
        $b .= $tcpp_tcond;
    } elseif (current_user_can('edit_plugins')) {
        $b .= "Terms are coming soon. <a href='$settingspage'>Configure this plugin's settings.</a><br/>";
    } else {
        $b .= "Terms are coming soon.<br/>";
    }

    return $b;
}

add_shortcode('my_terms_of_service', 'my_terms_of_service_func');

// shortcode [my_privacy_policy]
function my_privacy_policy_func() {


    $options = get_option('atospp_plugin_options');

    if (
            empty($options['atospp_onoff']) || empty($options['atospp_tos_heading']) || empty($options['atospp_pp_heading']) || empty($options['atospp_namefull']) || empty($options['atospp_name']) || empty($options['atospp_namepossessive']) || empty($options['atospp_domainname']) || empty($options['atospp_websiteurl']) || empty($options['atospp_minage']) || empty($options['atospp_time_feesnotifications']) || empty($options['atospp_time_replytopriorityemail']) || empty($options['atospp_time_determiningmaxdamages']) || empty($options['atospp_venue']) || empty($options['atospp_courtlocation']) || empty($options['atospp_arbitrationlocation'])
    ) {
        $tcpp_publish = 'atospp_off';
    } else {
        $tcpp_publish = $options['atospp_onoff'];
    }

    $tcpp_privacypolicy = get_atospp_legal_text(TCPP_PP);

    $settingspage = admin_url('options-general.php?page=auto-terms-of-service-and-privacy-policy/auto-terms-of-service-privacy-policy.php');

    $c = "";
    if (!empty($tcpp_privacypolicy) && $tcpp_publish == 'atospp_on') {
        $c .= $tcpp_privacypolicy;
    } elseif (current_user_can('edit_plugins')) {
        $c .= "Privacy Policy is coming soon. <a href='$settingspage'>Configure this plugin's settings.</a><br />";
    } else {
        $c .= "Privacy Policy is coming soon.<br />";
    }

    return $c;
}

add_shortcode('my_privacy_policy', 'my_privacy_policy_func');



/* --------------------- END OF SHORTCODE ------------------ */


// End of plugin
