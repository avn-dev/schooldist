{extends file="system/bundles/Admin/Resources/views/system.tpl"}

{block name="system_head"}

    <style>
        {\System::getSystemColorStyles()}
    </style>

{/block}

{block name="system_header_navbar" prepend}

    {if $oAccess->hasRight('core_communication')}
        <li>
            <a href="javascript:void(0);" onclick="loadContentByUrl('tc-communication', '{'Kommunikation'|L10N:'Framework'}', '/admin/extensions/tc/communication.html');" title="{'Kommunikation'|L10N:'Framework'}"><i class="fa fa-envelope"></i></a>
        </li>
    {/if}
    {if $oAccess->hasRight('core_zendesk')}
        <li>
            <a href="/zendesk/sso" target="_blank" title="{'Unterstützung'|L10N:'Framework'}"><i class="fa fa-life-ring"></i></a>
        </li>
    {/if}
    {if $oAccess->hasRight('thebing_welcome_wishlist')}
        <li>
            <a href="javascript:void(0);" onclick="loadContentByUrl('tc-wishlist', '{'Wunschliste'|L10N:'Framework'}', '/wishlist');" title="{'Wunschzettel'|L10N:'Framework'}"><i class="fa fa-commenting"></i></a>
        </li>
    {/if}
    {if 1 || $oAccess->hasRight('thebing_chat')}
        {if Ext_TC_Util::isDevSystem() == false}
            <li>
                <a href="javascript:void(0);" id="hf-chat" title="Chat"><i class="fa fa-comments"></i></a>
            </li>
        {/if}
    {/if}

{/block}

{block name="system_footer_js" append}

    {if Ext_TC_Util::isDevSystem() == false}

        <script>

			var sChatName = '{Access::getInstance()->firstname|escape} {Access::getInstance()->lastname|escape}';
			var sChatEmail = '{Access::getInstance()->email|escape}';
            var sError = '{'Momentan sind alle Agents offline'|L10N:'Framework'|trim}';

        </script>

        <script src="https://update.fidelo.com/fidelo_software_chat.js"></script>

    {/if}

{/block}