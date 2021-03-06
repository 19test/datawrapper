<?php


function add_header_vars(&$page, $active = null, $page_css = null) {

    if (!function_exists('header_nav_hook')) {
        function header_nav_hook(&$headlinks, $part) {
            $links = DatawrapperHooks::execute('header_nav_' . $part);
            if (!empty($links)) {
                foreach ($links as $link) {
                    $headlinks[] = $link;
                }
            }
        }
    }

    if (!isset($page['page_description'])) {
        $page['page_description'] = __('Datawrapper is an open source tool helping everyone to create simple, correct and embeddable charts in minutes.', 'core');
    }

    // define the header links
    global $app;
    $config = $GLOBALS['dw_config'];
    if (!isset($active)) {
        $active = explode('/', $app->request()->getResourceUri());
        $active = $active[1];
    }

    if (!isset($config['prevent_guest_charts'])) {
        $config['prevent_guest_charts'] = false;
    }
    if (!isset($config['prevent_guest_access'])) {
        $config['prevent_guest_access'] = false;
    }

    $user = DatawrapperSession::getUser();
    $headlinks = array();
    if ($user->isLoggedIn()) {
        $headlinks[] = array(
            'url' => '/chart/create',
            'id' => 'chart',
            'title' => __('New Chart'),
            'icon' => 'fa fa-plus'
        );
    }

    header_nav_hook($headlinks, 'create');

    if (isset($config['navigation'])) foreach ($config['navigation'] as $item) {
        $link = array('url' => str_replace('%lang%', substr(DatawrapperSession::getLanguage(), 0, 2), $item['url']), 'id' => $item['id'], 'title' => __($item['title']));
        if (!empty($item['icon'])) $link['icon'] = $item['icon'];
        $headlinks[] = $link;
    }

    if (!$user->isLoggedIn()) {
        header_nav_hook($headlinks, 'logged_out_nav'); 
    }

    header_nav_hook($headlinks, 'custom_nav');


    if ($user->isLoggedIn()) {


        $username = $user->guessName();
        if ($username == $user->getEmail()) {
            $username = strlen($username) > 18 ? substr($username, 0, 9).'…'.substr($username, strlen($username)-9) : $username;
        } else {
            if (strlen($username) > 18) $username = substr($username, 0, 16).'…';
        }

        $headlinks[] = 'divider';

        $headlinks[] = array(
            'url' => '/account',
            'id' => 'account',
            'title' => '<i class="fa fa-lock"></i> &nbsp;' . __('My Account')
        );


        $headlinks[] = 'divider';

        // the place where settings used to be

        header_nav_hook($headlinks, 'settings');

        if ($user->hasCharts()) {
            // mycharts
            $mycharts = array(
                'url' => '/mycharts/',
                'id' => 'mycharts',
                'title' => __('My Charts'),
                //'justicon' => true,
                'icon' => 'fa fa-bar-chart-o',
                'dropdown' => array()
            );
            foreach ($user->getRecentCharts(9) as $chart) {
                $mycharts['dropdown'][] = array(
                    'url' => '/'.$chart->getNamespace().'/'.$chart->getId().'/visualize#tell-the-story',
                    'title' => '<div style="height:20px; width:30px; position:absolute; left:10px;top:4px;'.
				'background-image:url('.$chart->thumbUrl(true).'); background-size:cover;"></div>'
                        . '<span>' . strip_tags($chart->getTitle()) . '</span>'
                );
            }
            $mycharts['dropdown'][] = 'divider';
            $mycharts['dropdown'][] = array('url' => '/mycharts/', 'title' => __('All charts'));
            $headlinks[] = $mycharts;
        }

        header_nav_hook($headlinks, 'mycharts');



    } else {
        $headlinks[] = array(
            'url' => '#login',
            'id' => 'login',
            'title' => $config['prevent_guest_access'] ? __('Login') : __('Login / Sign Up'),
            'icon' => 'fa fa-sign-in'
        );
    }

    // language dropdown
    if (!empty($config['languages'])) {
        $langDropdown = array(
            'url' => '',
            'id' => 'lang',
            'dropdown' => array(),
            'title' => strtoupper(substr(DatawrapperSession::getLanguage(), 0, 2)),
            'icon' => false,
            'tooltip' => __('Switch language')
        );
        foreach ($config['languages'] as $lang) {
            $langDropdown['dropdown'][] = array(
                'url' => '#lang-'.$lang['id'],
                'title' => $lang['title']
            );
        }
        if (count($langDropdown['dropdown']) > 1) $headlinks[] = $langDropdown;
    }

    header_nav_hook($headlinks, 'languages');



    if ($user->isLoggedIn()) {
        $headlinks[] = array(
            'url' => '#logout',
            'id' => 'signout',
            'icon' => 'fa fa-sign-out',
            'justicon' => true,
            'tooltip' => __('Sign out')
        );
    }

    header_nav_hook($headlinks, 'user');

    // admin link
    if ($user->isLoggedIn() && $user->isAdmin()
        && DatawrapperHooks::hookRegistered(DatawrapperHooks::GET_ADMIN_PAGES)) {
        $headlinks[] = 'divider';
        $headlinks[] = array(
            'url' => '/admin',
            'id' => 'admin',
            'icon' => 'fa fa-gears',
            'justicon' => true,
            'tooltip' => __('Admin')
        );
    }

    header_nav_hook($headlinks, 'admin');

    if (DatawrapperHooks::hookRegistered(DatawrapperHooks::CUSTOM_LOGO)) {
        $logos = DatawrapperHooks::execute(DatawrapperHooks::CUSTOM_LOGO);
        $page['custom_logo'] = $logos[0];
    }

    foreach ($headlinks as $i => $link) {
        if ($link == 'divider') continue;
        $headlinks[$i]['active'] = $headlinks[$i]['id'] == $active;
    }
    $page['headlinks'] = $headlinks;
    $page['user'] = DatawrapperSession::getUser();
    $page['language'] = substr(DatawrapperSession::getLanguage(), 0, 2);
    $page['locale'] = DatawrapperSession::getLanguage();
    $page['DW_DOMAIN'] = $config['domain'];
    $page['DW_VERSION'] = DATAWRAPPER_VERSION;
    $page['ASSET_DOMAIN'] = $config['asset_domain'];
    $page['DW_CHART_CACHE_DOMAIN'] = $config['chart_domain'];
    $page['SUPPORT_EMAIL'] = $config['email']['support'];
    $page['config'] = $config;
    $page['page_css'] = $page_css;
    $page['invert_navbar'] = isset($config['invert_header']) && $config['invert_header'] || substr($config['domain'], -4) == '.pro';
    $page['noSignup'] = $config['prevent_guest_access'];
    $page['alternative_signins'] = DatawrapperHooks::execute(DatawrapperHooks::ALTERNATIVE_SIGNIN);

    if (isset ($config['maintenance']) && $config['maintenance'] == true) {
        $page['maintenance'] = true;
    } 

    $uri = $app->request()->getResourceUri();
    $plugin_assets = DatawrapperHooks::execute(DatawrapperHooks::GET_PLUGIN_ASSETS, $uri);
    if (!empty($plugin_assets)) {
        $plugin_js_files = array();
        $plugin_css_files = array();
        foreach ($plugin_assets as $assets) {
            if (!is_array($assets)) $assets = array($assets);
            foreach ($assets as $asset) {
                $file = $asset[0];
                $plugin = $asset[1];
                if (substr($file, -3) == '.js') $plugin_js_files[] = $file . '?v=' . $plugin->getVersion();
                if (substr($file, -4) == '.css') $plugin_css_files[] = $file . '?v=' . $plugin->getVersion();
            }
        }
        $page['plugin_js'] = $plugin_js_files;
        $page['plugin_css'] = $plugin_css_files;
    }

    if (isset($config['piwik'])) {
        $page['PIWIK_URL'] = $config['piwik']['url'];
        $page['PIWIK_IDSITE'] = $config['piwik']['idSite'];
        if (isset($config['piwik']['idSiteNoCharts'])) {
            $page['PIWIK_IDSITE_NO_CHARTS'] = $config['piwik']['idSiteNoCharts'];
        }
    }

    if ($config['debug']) {
        if (file_exists('../.git')) {
            // parse git branch
            $head = file_get_contents('../.git/HEAD');
            $parts = explode("/", $head);
            $branch = trim($parts[count($parts)-1]);
            $output = array();
            exec('git rev-parse HEAD', $output);
            $commit = $output[0];
            $page['BRANCH'] = ' (<a href="https://github.com/datawrapper/datawrapper/tree/'.$commit.'">'.$branch.'</a>)';
        }
    }
}

