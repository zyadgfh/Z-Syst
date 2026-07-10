<div class="login-button-list">
    <ul>
        <li><a class="theme-btn" href="javascript:void(0)" onclick="fillup('superadmin@superadmin.com','superadmin')">{{ __('Super Admin') }}</a></li>
        <li><a class="theme-btn" href="javascript:void(0)" onclick="fillup('admin@admin.com','admin')">{{ __('Admin') }}</a></li>
        @if (moduleCheck('Business'))
        <li>
            <a class="theme-btn position-relative" href="javascript:void(0)" onclick="fillup('shopowner@acnoo.com', '123456')">
                {{ __('Bussiness') }}
                <div class="sup-addon">{{__('Add-on')}}</div>
            </a>
        </li>
        @else
        <li>
            <a class="theme-btn" href="javascript:void(0)" onclick="fillup('manager@manager.com', 'manager')">{{ __('Manager') }}</a>
        </li>
        @endif
        @if (moduleCheck('MultiBranchAddon') && moduleCheck('Business'))
        <li>
            <a class="theme-btn position-relative" href="javascript:void(0)" onclick="fillup('multibranch@acnoo.com', '123456')">
                {{ __('Multi Branch') }}
                <div class="sup-addon">{{__('Add-on')}}</div>
            </a>
        </li>
        @else
        <li>
            <a class="theme-btn" href="javascript:void(0)" onclick="fillup('manager@manager.com', 'manager')">{{ __('Manager') }}</a>
        </li>
        @endif
    </ul>
</div>
