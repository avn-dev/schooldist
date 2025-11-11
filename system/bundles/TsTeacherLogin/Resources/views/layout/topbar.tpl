<header class="main-header">
    <!-- Logo -->
    <a href="{route name='TsTeacherLogin.teacher'}" class="logo">
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
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </a>

        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
				<li class="system-logo-item">
                    {if !empty($sSystemLogo)}
					    <img src="{route name='TsTeacherLogin.teacher_logo' sFile=$sSystemLogo}" class="system-logo" alt="Logo">
                    {/if}
				</li>

                {assign var=oProfilPicture value=$oTeacher->getProfilePicture()}

                <!-- User Account: style can be found in dropdown.less -->
                <li class="dropdown user user-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        {if $oProfilPicture}
                            <img src="{$oProfilPicture->getPublicUrl(false)}" class="user-image" alt="User Image">
                        {/if}
                        <span class="hidden-xs">{$oAccess->firstname} {$oAccess->lastname}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <!-- User image -->
                        <li class="user-header">
                            {if $oProfilPicture}
                                <img src="{$oProfilPicture->getPublicUrl(false)}" class="img-circle" alt="User Image">
                            {/if}
                            <p>{$oAccess->firstname} {$oAccess->lastname}</p>
                        </li>
                        <!-- Menu Footer-->
                        <li class="user-footer">
                            <div class="pull-left">
                                <a href="{route name='TsTeacherLogin.teacher_data'}" class="btn btn-default btn-flat">{'My data'|L10N}</a>
                            </div>
                            <div class="pull-right">
                                <a href="{route name='TsTeacherLogin.teacher_logout'}" class="btn btn-default btn-flat">{'Logout'|L10N}</a>
                            </div>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</header>