<?php

namespace App\Filament\Clusters\Settings\Resources\Banners\Utilities;

use Filament\View\PanelsRenderHook;

class Location
{
    public static function getLabel(?string $location): ?string
    {
        if(!$location) {
            return $location;
        }

        return collect(static::$locations)->flatMap(fn ($group) => $group)->get($location, $location);
    }

    public static array $locations = [
        'Panel' => [
            PanelsRenderHook::BODY_START => 'After <body>',
            PanelsRenderHook::BODY_END => 'Before </body>',
            PanelsRenderHook::CONTENT_BEFORE => 'Before page content',
            PanelsRenderHook::CONTENT_AFTER => 'After page content',
            PanelsRenderHook::CONTENT_START => 'Before page content, inside <main>',
            PanelsRenderHook::CONTENT_END => 'After page content, inside <main>',
            PanelsRenderHook::LAYOUT_START => 'Start of the layout container, also can be scoped to the page class',
            PanelsRenderHook::LAYOUT_END => 'End of the layout container, also can be scoped to the page class',
            PanelsRenderHook::FOOTER => 'Footer of the page',
        ],
        'Auth' => [
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER => 'After login form',
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE => 'Before login form',
            PanelsRenderHook::AUTH_PASSWORD_RESET_REQUEST_FORM_AFTER => 'After password reset request form',
            PanelsRenderHook::AUTH_PASSWORD_RESET_REQUEST_FORM_BEFORE => 'Before password reset request form',
            PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_AFTER => 'After password reset form',
            PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_BEFORE => 'Before password reset form',
//                            PanelsRenderHook::AUTH_REGISTER_FORM_AFTER => 'After register form',
//                            PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE => 'Before register form',
        ],
        'Pages' => [
            PanelsRenderHook::PAGE_START => 'Start of the page content container, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_FOOTER_WIDGETS_BEFORE => 'Before the page footer widgets, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_FOOTER_WIDGETS_AFTER => 'After the page footer widgets, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_FOOTER_WIDGETS_START => 'Start of the page footer widgets, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_FOOTER_WIDGETS_END => 'End of the page footer widgets, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE => 'Before the page header actions, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_HEADER_ACTIONS_AFTER => 'After the page header actions, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_HEADER_WIDGETS_BEFORE => 'Before the page header widgets, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_HEADER_WIDGETS_AFTER => 'After the page header widgets, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_HEADER_WIDGETS_START => 'Start of the page header widgets, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_HEADER_WIDGETS_END => 'End of the page header widgets, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_END => 'End of the page content container, also can be scoped to the page or resource class',
        ],
        'Navigation' => [
            PanelsRenderHook::PAGE_SUB_NAVIGATION_END_AFTER => 'After the page sub navigation “end” sidebar position, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_SUB_NAVIGATION_END_BEFORE => 'Before the page sub navigation “end” sidebar position, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_SUB_NAVIGATION_SIDEBAR_AFTER => 'After the page sub navigation sidebar, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_SUB_NAVIGATION_SIDEBAR_BEFORE => 'Before the page sub navigation sidebar, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_SUB_NAVIGATION_START_AFTER => 'After the page sub navigation “start” sidebar position, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_SUB_NAVIGATION_START_BEFORE => 'Before the page sub navigation “start” sidebar position, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_SUB_NAVIGATION_TOP_AFTER => 'After the page sub navigation “top” tabs position, also can be scoped to the page or resource class',
            PanelsRenderHook::PAGE_SUB_NAVIGATION_TOP_BEFORE => 'Before the page sub navigation “top” tabs position, also can be scoped to the page or resource class',
        ],
        'Resource' => [
            PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER => 'After the resource table, also can be scoped to the page or resource class',
            PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE => 'Before the resource table, also can be scoped to the page or resource class',
            PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABS_END => 'The end of the filter tabs (after the last tab), also can be scoped to the page or resource class',
            PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABS_START => 'The start of the filter tabs (before the first tab), also can be scoped to the page or resource class',
            PanelsRenderHook::RESOURCE_PAGES_MANAGE_RELATED_RECORDS_TABLE_AFTER => 'After the relation manager table, also can be scoped to the page or resource class',
            PanelsRenderHook::RESOURCE_PAGES_MANAGE_RELATED_RECORDS_TABLE_BEFORE => 'Before the relation manager table, also can be scoped to the page or resource class',
            PanelsRenderHook::RESOURCE_RELATION_MANAGER_AFTER => 'After the relation manager table, also can be scoped to the page or relation manager class',
            PanelsRenderHook::RESOURCE_RELATION_MANAGER_BEFORE => 'Before the relation manager table, also can be scoped to the page or relation manager class',
            PanelsRenderHook::RESOURCE_TABS_END => 'The end of the resource tabs (after the last tab), also can be scoped to the page or resource class',
            PanelsRenderHook::RESOURCE_TABS_START => 'The start of the resource tabs (before the first tab), also can be scoped to the page or resource class',
        ],
        'Topbar & Sidebar' => [
            PanelsRenderHook::GLOBAL_SEARCH_AFTER => 'After the global search container, inside the topbar',
            PanelsRenderHook::GLOBAL_SEARCH_BEFORE => 'Before the global search container, inside the topbar',
            PanelsRenderHook::GLOBAL_SEARCH_END => 'The end of the global search container',
            PanelsRenderHook::GLOBAL_SEARCH_START => 'The start of the global search container',
            PanelsRenderHook::SIDEBAR_LOGO_AFTER => 'After the logo in the sidebar',
            PanelsRenderHook::SIDEBAR_LOGO_BEFORE => 'Before the logo in the sidebar',
            PanelsRenderHook::SIDEBAR_NAV_END => 'In the sidebar, before </nav>',
            PanelsRenderHook::SIDEBAR_NAV_START => 'In the sidebar, after <nav>',
            PanelsRenderHook::SIDEBAR_FOOTER => 'Pinned to the bottom of the sidebar, below the content',
            PanelsRenderHook::SIDEBAR_START => 'Start of the sidebar container',
            PanelsRenderHook::TOPBAR_END => 'End of the topbar container',
            PanelsRenderHook::TOPBAR_LOGO_AFTER => 'After the logo in the topbar',
            PanelsRenderHook::TOPBAR_LOGO_BEFORE => 'Before the logo in the topbar',
            PanelsRenderHook::TOPBAR_START => 'Start of the topbar container',
            PanelsRenderHook::USER_MENU_AFTER => 'After the user menu',
            PanelsRenderHook::USER_MENU_BEFORE => 'Before the user menu',
            PanelsRenderHook::USER_MENU_PROFILE_AFTER => 'After the profile item in the user menu',
            PanelsRenderHook::USER_MENU_PROFILE_BEFORE => 'Before the profile item in the user menu',
        ],
    ];
}
