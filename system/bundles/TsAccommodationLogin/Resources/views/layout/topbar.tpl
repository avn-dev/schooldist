<header class="main-header">
    <!-- Logo -->
    <a href="{route name='TsAccommodationLogin.accommodation'}" class="logo">
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <span class="logo-mini">
            <img src="{$aLogos['framework_logo_small']}" alt="Logo">
        </span>
        <!-- logo for regular state and mobile devices -->
        <span class="logo-lg">
            <img src="{$aLogos['framework_logo']}" alt="Logo">
        </span>
    </a>
    <!-- Header Navbar: style can be found in header.less -->
    <nav class="navbar navbar-static-top">
        <!-- Sidebar toggle button-->
        <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>

        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
				<li class="system-logo-item">
                    {if !empty($sSystemLogo)}
					    <img src="{route name='TsAccommodationLogin.accommodation_logo' sFile=$sSystemLogo}" class="system-logo" alt="Logo">
                    {/if}
				</li>
                <!-- User Account: style can be found in dropdown.less -->
				{if $accommodation}
                <li class="dropdown user user-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <img src="{$accommodation->getGravatar(160)}" class="user-image" alt="User Image">
                        <span class="hidden-xs">{$accommodation->firstname} {$accommodation->lastname}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <!-- User image -->
                        <li class="user-header">
                            <img src="{$accommodation->getGravatar(160)}" class="img-circle" alt="User Image">
                            <p>{$accommodation->firstname} {$accommodation->lastname}</p>
                        </li>
                        <!-- Menu Footer-->
                        <li class="user-footer">
                            <div class="pull-left">
                                <a href="{route name='TsAccommodationLogin.accommodation_data'}" class="btn btn-default btn-flat">{'My data'|L10N}</a>
                            </div>
                            <div class="pull-right">
                                <a href="{route name='TsAccommodationLogin.accommodation_logout'}" class="btn btn-default btn-flat">{'Logout'|L10N}</a>
                            </div>
                        </li>
                    </ul>
                </li>
				{/if}
            </ul>
        </div>
    </nav>
</header>