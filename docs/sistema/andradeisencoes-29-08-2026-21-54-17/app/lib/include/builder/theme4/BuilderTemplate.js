window.BuilderTemplate = ( function() {

    var resizeTimeout;

    const resizeBuilderMenu = function() {

        clearTimeout(resizeTimeout);

        resizeTimeout = setTimeout(function(){
            
            if ($(document).innerWidth() <= 950) {
                $('.builder-menu-arrow-menus-scroll').removeClass("show-arrows");
                return;
            }
            
            var topMenu = $('.builder-menu');
            var size = $(document).width() - ( $('.navbar-brand').innerWidth() + $('.navbar-collapse:not(.builder-menu)').innerWidth() + 170 );

            if (topMenu[0].scrollWidth > topMenu[0].clientWidth) 
            {
                topMenu.css('width', size + 'px');
                $('.builder-menu-arrow-menus-scroll').addClass("show-arrows");
            } 
            else 
            {
                $('.builder-menu-arrow-menus-scroll').removeClass("show-arrows");
            }
        }, 250);
    }
    
    const clickBuilderMenuOption = function(element) 
    {
        let left = $(this).offset().left;
        $(this).closest('li').find('ul:first').css('left', left  + 'px');
    }
    
    const initTopMenu = function()
    {
        $(document).ready(function () {
            BuilderTemplate.resizeBuilderMenu();
            $(window).resize(BuilderTemplate.resizeBuilderMenu);
            $('.builder-menu>ul>li.dropdown').click(BuilderTemplate.clickBuilderMenuOption);
            $('.sidebar-toggle').click(BuilderTemplate.resizeBuilderMenu);

            $('.builder-menu .dropdown-menu,.builder-menu .dropdown-submenu a.dropdown-toggle').click(function (event) {
                event.stopPropagation();
            });

            $('.builder-menu-arrow-menus-scroll:last').click(function (e) {
                $('.builder-menu')[0].scrollLeft += 75;
            });

            $('.builder-menu-arrow-menus-scroll:first').click(function (e) {
                $('.builder-menu')[0].scrollLeft -= 75
            });

        });
    }
    
    const updateMessagesMenu = function() {
        $.get('engine.php?class=MessageList&theme=theme4', function(data) {
            $('#envelope_messages').html(data);
        });
    }
    
    const updateNotificationsMenu = function() {
        $.get('engine.php?class=NotificationList&theme=theme4', function(data) {
            $('#envelope_notifications').html(data);
        });
    }

    const initFirebase = function()
    {
        if(BuilderTemplate.firebase_config)
        {
            window.addEventListener('firebaseLoaded', async function() {

                await FirebaseService.init({
                    token: BuilderTemplate.firebase_token,
                    config: BuilderTemplate.firebase_config
                }, function(){
                    
                    setTimeout(function() { if(BuilderTemplate.chat_enabled)
                        {
                            ChatApp.init();
                        }
                        else
                        {
                            ChatApp.disable();
                        } }, 1000);
                    

                    $(document).ajaxSend(function(event, jqXHR, ajaxOptions) {
                        const params = new URLSearchParams(ajaxOptions.url.split('?')[1]);
                        const result = `${params.get('class')}::${params.get('method')}`;

                        FirebaseService.setUserAttribute('last_action', result);
                    });
                });
            });
        }
    }

    const init = function(options)
    {
        if(options.mad_debug_console)
        {
            initDebugConsole();            
        }
        
        if(options.top_menu)
        {
            BuilderTemplate.initTopMenu();
        }
        
        BuilderTemplate.users = options.users;
        BuilderTemplate.chat_enabled = options.chat_enabled;
        BuilderTemplate.firebase_token = options.firebase_token;
        BuilderTemplate.single_tab_mode = options.single_tab_mode;
        BuilderTemplate.firebase_config = options.firebase_config;
        BuilderTemplate.application_name = options.application_name;

        if(BuilderTemplate.single_tab_mode == true)
        {
            setTimeout(function() { System.checkMultipleTabs(options.application_name); });
        }

        initFirebase();

        if(options.public_layout == false)
        {
            BuilderTemplate.loadSearchBar();
            BuilderTemplate.updateMessagesMenu();
            BuilderTemplate.updateNotificationsMenu();
            
            if(options.verify_messages_menu)
            {
                if(options.verify_messages_menu < 5000)
                {
                    options.verify_messages_menu = 5000;
                }
                BuilderTemplate.intervalUpdateMessagesMenu = setInterval( BuilderTemplate.updateMessagesMenu, options.verify_messages_menu);
            }
            
            if(options.verify_notifications_menu)
            {
                if(options.verify_notifications_menu < 5000)
                {
                    options.verify_notifications_menu = 5000;
                }
                BuilderTemplate.intervalUpdateNotificationsMenu = setInterval( BuilderTemplate.updateNotificationsMenu, options.verify_notifications_menu);
            }
        }

        __adianti_set_name(options.application_name);
        __adianti_init_tabs(options.use_tabs, options.store_tabs, options.use_mdi_windows);
        __adianti_set_language(options.language);
        __adianti_set_debug(options.debug);
    }

    const loadSearchBar = function()
    {
        $.get('engine.php?class=SearchInputBox', function (data) {
            $('#envelope_search').html(data);
        });
    }

    const initDebugConsole = function() {
        System.initDebugConsole();
    }

    return {
        updateMessagesMenu: updateMessagesMenu,
        updateNotificationsMenu: updateNotificationsMenu,
        loadSearchBar: loadSearchBar,
        init: init,
        resizeBuilderMenu: resizeBuilderMenu,
        clickBuilderMenuOption: clickBuilderMenuOption,
        initTopMenu: initTopMenu
    };

})();
