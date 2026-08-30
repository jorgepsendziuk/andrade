<?php

use Adianti\Registry\TSession;

class BuilderTemplateParser
{
    /**
     * Parse template and replace basic system variables
     * @param $content raw template
     */
    public static function parse($content, $theme = 'theme3')
    {
        $ini = AdiantiApplicationConfig::get();
        $preferences = SystemPreferenceService::getPreferences();

        if ((TSession::getValue('login') == 'admin') && !empty($ini['general']['token']) && file_exists("app/templates/{$theme}/builder-menu.html"))
        {
            $builder_menu = file_get_contents("app/templates/{$theme}/builder-menu.html");
            $content = str_replace('<!--{BUILDER-MENU}-->', $builder_menu, $content);
        }
        else
        {
            $content  = self::removeContentBeetwenTag($content, '<!--[IFADMIN]-->', '<!--[/IFADMIN]-->');
            $content  = self::removeContentBeetwenTag($content, '<!--[IFADMIN-LEFT-MENU]-->', '<!--[/IFADMIN-LEFT-MENU]-->');
        }
        
        if (!isset($ini['permission']['user_register']) OR $ini['permission']['user_register'] !== '1')
        {
            $content  = self::removeContentBeetwenTag($content, '<!--[CREATE-ACCOUNT]-->', '<!--[/CREATE-ACCOUNT]-->');
        }
        
        if (!isset($ini['permission']['reset_password']) OR $ini['permission']['reset_password'] !== '1')
        {
            $content  = self::removeContentBeetwenTag($content, '<!--[RESET-PASSWORD]-->', '<!--[/RESET-PASSWORD]-->');
        }
        
        $use_tabs = $ini['general']['use_tabs'] ?? 0;
        $store_tabs = $ini['general']['store_tabs'] ?? 0;
        $use_mdi_windows = $ini['general']['use_mdi_windows'] ?? 0;
        $store_mdi_windows = $ini['general']['store_mdi_windows'] ?? 0;

        if ($use_mdi_windows) {
            $use_tabs = 1;
        }

        if ($store_mdi_windows) {
            $store_tabs = 1;
        }

        $has_left_menu = false;
        $has_top_menu = false;
        $top_menu_var = 'false';
        $top_menu = '';

        if ( (!isset($ini['general']['left_menu']) || $ini['general']['left_menu'] == '0') && (!isset($ini['general']['top_menu']) || $ini['general']['top_menu'] == '0') )
        {
            $content = str_replace(['<!--[IF-LEFT-MENU]-->', '<!--[/IF-LEFT-MENU]-->'], ['', ''], $content);
            $has_left_menu = true;
        }
        elseif (!isset($ini['general']['left_menu']) || $ini['general']['left_menu'] == '0')
        {
            $content  = self::removeContentBeetwenTag($content, '<!--[IF-LEFT-MENU]-->', '<!--[/IF-LEFT-MENU]-->');
            $content  = self::removeContentBeetwenTag($content, '<!--[IFADMIN-LEFT-MENU]-->', '<!--[/IFADMIN-LEFT-MENU]-->');
        }
        elseif(isset($ini['general']['left_menu']) && $ini['general']['left_menu'] == '1')
        {
            $content = str_replace(['<!--[IF-LEFT-MENU]-->', '<!--[/IF-LEFT-MENU]-->'], ['', ''], $content);
            $has_left_menu = true;
        }

        if (isset($ini['general']['top_menu']) && $ini['general']['top_menu'] == '1')
        {
            $content = str_replace(['<!--[IF-TOP-MENU]-->', '<!--[/IF-TOP-MENU]-->'], ['', ''], $content);
            $has_top_menu = true;
            $top_menu_var = 'true';
        }
        else
        {
            $content  = self::removeContentBeetwenTag($content, '<!--[IF-TOP-MENU]-->', '<!--[/IF-TOP-MENU]-->');
        }

        if(!$has_left_menu)
        {
            $content = str_replace('{builder_top_menu}', 'top-menu-only', $content);
            $content = str_replace('{top_menu_only}', 'true', $content);
        }
        else
        {
            $content = str_replace('{builder_top_menu}', '', $content);
            $content = str_replace('{top_menu_only}', 'false', $content);
        }
        
        $menu = 'menu.xml';
        if (isset($ini['general']['public_view']) && $ini['general']['public_view'] == '1' && empty(TSession::getValue('logged')))
        {
            $menu = 'menu-public.xml';
        }

        $themeMenuFactory = BuilderMenuFactory::getInstance($theme, $menu);
        $menu = $themeMenuFactory->getMenu();
        $modulemenu = $themeMenuFactory->getModuleMenu();
        $dropdownMenu = $themeMenuFactory->getDropdownNavbarMenu();
        $top_menu_module = $has_top_menu ? $themeMenuFactory->getTopModuleMenu() : '';
        $top_menu = $has_top_menu ? $themeMenuFactory->getTopMenu() : '';

        $content = str_replace('{TOP-MENU-BUILDER}', $top_menu, $content);
        $content = str_replace('{TOP-MODULE-MENU-BUILDER}', $top_menu_module, $content);
        $content = str_replace('{MODULE_MENU}', $modulemenu, $content);
        $content = str_replace('{DROPDOWN_MENU}', $dropdownMenu, $content);
        $content = str_replace('{MENU}', $menu, $content);

        $use_tabs = $ini['general']['use_tabs'] ?? 0;
        $store_tabs = $ini['general']['store_tabs'] ?? 0;
        $use_mdi_windows = $ini['general']['use_mdi_windows'] ?? 0;
        $store_mdi_windows = $ini['general']['store_mdi_windows'] ?? 0;
        $dialog_box_type = $ini['general']['dialog_box_type'] ?? 'bootstrap';
        $multiunit = $ini['general']['multiunit'] ?? 0;
        $change_unit = $ini['general']['change_unit'] ?? 0;
        $single_tab_mode = !empty($preferences['single_tab_mode']) && $preferences['single_tab_mode'] == 'T' ? "true" : "false";

        if ($use_mdi_windows) {
            $use_tabs = 1;
        }

        if ($store_mdi_windows) {
            $store_tabs = 1;
        }

        $class     = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';

        $libraries_user = file_get_contents("app/templates/{$theme}/libraries_user.html");
        $libraries_builder = file_get_contents("app/templates/{$theme}/libraries_builder.html");
        $libraries_theme = file_get_contents("app/templates/{$theme}/libraries_theme.html");
        $libraries = file_get_contents("app/templates/{$theme}/libraries.html");
        $user_theme = BuilderService::getTheme(TSession::getValue('userid'));

        $content   = str_replace('{LIBRARIES}', $libraries, $content);
        $content   = str_replace('{class}',     $class, $content);
        $content   = str_replace('{template}',  $theme, $content);
        $content   = str_replace('{lang}',      AdiantiCoreTranslator::getLanguage(), $content);
        $content   = str_replace('{debug}',     isset($ini['general']['debug']) ? $ini['general']['debug'] : '1', $content);
        $content   = str_replace('{login}',     (string) TSession::getValue('login'), $content);
        $content   = str_replace('{title}',     isset($ini['general']['title']) ? $ini['general']['title'] : '', $content);
        $content   = str_replace('{username}',  (string) TSession::getValue('username'), $content);
        $content   = str_replace('{usermail}',  (string) TSession::getValue('usermail'), $content);
        $content   = str_replace('{frontpage}', (string) TSession::getValue('frontpage'), $content);
        $content   = str_replace('{userunitid}', (string) TSession::getValue('userunitid'), $content);
        $content   = str_replace('{userunitname}', (string) TSession::getValue('userunitname'), $content);
        $content   = str_replace('{query_string}', $_SERVER["QUERY_STRING"] ?? '', $content);
        $content   = str_replace('{use_tabs}', $use_tabs, $content);
        $content   = str_replace('{store_tabs}', $store_tabs, $content);
        $content   = str_replace('{use_mdi_windows}', $use_mdi_windows, $content);
        $content   = str_replace('{application}', $ini['general']['application'], $content);
        $content   = str_replace('{user_theme}', $user_theme, $content);
        $content   = str_replace('{dialog_box_type}', $dialog_box_type, $content);

        if($multiunit && $change_unit && is_array(TSession::getValue('userunitids')) && count(TSession::getValue('userunitids')) > 1)
        {
            $content = str_replace(['<!--[CHANGE-UNIT]-->', '<!--[/CHANGE-UNIT]-->'], ['', ''], $content);
        }
        else
        {
            $content  = self::removeContentBeetwenTag($content, '<!--[CHANGE-UNIT]-->', '<!--[/CHANGE-UNIT]-->');
        }
        
        $css       = TPage::getLoadedCSS();
        $js        = TPage::getLoadedJS();
        $content   = str_replace('{HEAD}', $css.$js, $content);
        
        $content = str_replace('{LIBRARIES_USER}', $libraries_user, $content);
        $content = str_replace('{LIBRARIES_BUILDER}', $libraries_builder, $content);
        $content = str_replace('{LIBRARIES_THEME}', $libraries_theme, $content);
        $content = str_replace('{template}', $theme, $content);
        $content = str_replace('{top_menu_var}', $top_menu_var, $content);
        $content = str_replace('{lang}', AdiantiCoreTranslator::getLanguage(), $content);
        $content = str_replace('{debug}', isset($ini['general']['debug']) ? $ini['general']['debug'] : '1', $content);
        $content = str_replace('{verify_messages_menu}', isset($ini['general']['verify_messages_menu']) ? $ini['general']['verify_messages_menu'] : 'false', $content);
        $content = str_replace('{verify_notifications_menu}', isset($ini['general']['verify_notifications_menu']) ? $ini['general']['verify_notifications_menu'] : 'false', $content);
        $content = str_replace('{use_tabs}', $use_tabs, $content);
        $content = str_replace('{store_tabs}', $store_tabs, $content);
        $content = str_replace('{use_mdi_windows}', $use_mdi_windows, $content);
        $content = str_replace('{application}', $ini['general']['application'], $content);
        $content = str_replace('{single_tab_mode}', $single_tab_mode, $content);

        if(TSession::getValue('logged') && SystemChatService::isEnabled())
        {
            TTransaction::open('permission');
            $users = json_encode(SystemChatService::getUserItems());
            TTransaction::close();
            $content = str_replace('{firebase_token}', BuilderFirebaseService::createUserToken(), $content);
            $content = str_replace('{firebase_config}', SystemPreferenceService::getFirebaseConfig(), $content);
            $content = str_replace('{user_id}', TSession::getValue('userid'), $content);
            $content = str_replace('{login}', TSession::getValue('login'), $content);
            $content = str_replace('{users}', $users, $content);
            $content = str_replace('{chat_enabled}', 'true', $content);

            $content = str_replace(['<!--[IF-CHAT]-->', '<!--[/IF-CHAT]-->'], ['', ''], $content);
        }
        elseif(TSession::getValue('logged') && SystemPreferenceService::hasFirebaseConfigured())
        {
            $content = str_replace('{firebase_config}', SystemPreferenceService::getFirebaseConfig(), $content);
            $content = str_replace('{firebase_token}', BuilderFirebaseService::createUserToken(), $content);
            $content = str_replace('{user_id}', TSession::getValue('userid'), $content);
            $content = str_replace('{login}', TSession::getValue('login'), $content);
            $content = str_replace('{users}', 'null', $content);
            $content = str_replace('{chat_enabled}', 'false', $content);

            $content  = self::removeContentBeetwenTag($content, '<!--[IF-CHAT]-->', '<!--[/IF-CHAT]-->');
        }
        else
        {
            $content  = self::removeContentBeetwenTag($content, '<!--[IF-CHAT]-->', '<!--[/IF-CHAT]-->');
            $content = str_replace('{firebase_token}', 'false', $content);
            $content = str_replace('{user_id}', 'false', $content);
            $content = str_replace('{login}', 'false', $content);
            $content = str_replace('{users}', 'null', $content);
            $content = str_replace('{chat_enabled}', 'false', $content);
            $content = str_replace('{firebase_config}', 'false', $content);
        }

        $mad_debug_console = 'false';
        if(MadLogService::isDebugConsoleEnabled()){
            $mad_debug_console = 'true';
        }
    
        $content = str_replace('{mad_debug_console}', $mad_debug_console, $content);

        // Remove all comments
        $content = preg_replace('/<!--.*?-->/s', '', $content);
        
        return $content;
    }

    public static function init($layoutName)
    {
        ob_start();
        $ini        = AdiantiApplicationConfig::get();
        $theme      = $ini['general']['theme'];
        $publicName = 'public';
        $loginName  = 'login';

        if (isset($_REQUEST['token_mobile']) || (!isset($_REQUEST['token_mobile']) && TSession::getValue('logged_mobile')))
        {
            $layoutName = 'layout-mobile';
            $publicName = 'public-mobile';
            $loginName = 'login-mobile';
            $theme  = $ini['general']['theme_mobile'];

            try
            {
                if (isset($_REQUEST['token_mobile']) && empty($_REQUEST['token_mobile']))
                {
                    TSession::freeSession();
                }
                else if (! empty($_REQUEST['token_mobile']))
                {
                    BuilderMobileService::initSessionFromToken($_REQUEST['token_mobile']);
                }
            }
            catch (Exception $e)
            {
                new TMessage('erro', $e->getMessage());
                return;
            }
        }


        if ( TSession::getValue('logged') )
        {
            if (isset($_REQUEST['template']) AND $_REQUEST['template'] == 'iframe')
            {
                $content = file_get_contents("app/templates/{$theme}/iframe.html");
            }
            else
            {
                $content = file_get_contents("app/templates/{$theme}/{$layoutName}.html");
            }
        }
        else
        {
            if ((isset($ini['general']['public_view']) && $ini['general']['public_view'] == '1') || $layoutName == 'public')
            {
                $content = file_get_contents("app/templates/{$theme}/{$publicName}.html");
            }
            elseif (is_file("app/templates/{$theme}/{$layoutName}.html") && $layoutName != 'layout')
            {
                $content = file_get_contents("app/templates/{$theme}/{$layoutName}.html");
            }
            else
            {
                $content = file_get_contents("app/templates/{$theme}/{$loginName}.html");
            }
        }

        $content = self::parse($content, $theme);
        $content .= ob_get_clean();

        return $content;
    }

    public static function removeContentBeetwenTag($content, $tag1, $tag2)
    {
        // Escapa caracteres especiais das tags para uso em regex
        $tag1 = preg_quote($tag1, '/');
        $tag2 = preg_quote($tag2, '/');
        
        // Cria o padrão de regex para encontrar o conteúdo entre as tags (incluindo as tags)
        $pattern = '/' . $tag1 . '.*?' . $tag2 . '/s';
        
        // Remove todo o conteúdo entre as tags (incluindo as tags)
        return preg_replace($pattern, '', $content);
    }

}
