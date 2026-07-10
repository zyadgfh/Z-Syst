<nav class="side-bar">
    <div class="side-bar-logo">
        <a href="{{ route('business.dashboard.index') }}">
            <img src="{{ asset(get_option('general')['admin_logo'] ?? 'assets/images/logo/backend_logo.png') }}" alt="Logo">
        </a>
        <button class="close-btn"><i class="fal fa-times"></i></button>
    </div>
    <div class="side-bar-manu">
        <ul>
            @usercan('dashboard.read')
            <li class="{{ Request::routeIs('business.dashboard.index') ? 'active' : '' }}">
                <a href="{{ route('business.dashboard.index') }}" class="active">
                    <span class="sidebar-icon">
                        <img src="{{ asset('assets/images/sidebar/dashborad.svg') }}">
                    </span>
                    {{ __('Dashboard') }}
                </a>
            </li>
            @endusercan

            @usercanany(['sales.read', 'sales.create'])
            <li class="dropdown {{ Request::routeIs('business.sales.index', 'business.sales.create', 'business.sales.edit', 'business.sale-returns.create', 'business.sale-returns.index','business.sales.inventory') ? 'active' : '' }}">
                <a href="#">
                    <span class="sidebar-icon">
                        <img src="{{ asset('assets/images/sidebar/sales.svg') }}">
                    </span>
                    {{ __('Sales') }}</a>
                <ul>
                    @usercan('sales.create')
                    <li>
                        <a class="{{ Request::routeIs('business.sales.create') ? 'active' : '' }}" href="{{ route('business.sales.create') }}">
                            {{ __('POS') }}
                        </a>
                    </li>
                    @endusercan

                    @usercan('inventory.create')
                    <li>
                        <a class="{{ Request::routeIs('business.sales.inventory') ? 'active' : '' }}" href="{{ route('business.sales.inventory') }}">
                            {{ __('Inventory') }}
                        </a>
                    </li>
                    @endusercan

                    @usercan('sales.read')
                    <li><a class="{{ Request::routeIs('business.sales.index', 'business.sale-returns.create') ? 'active' : '' }}" href="{{ route('business.sales.index') }}">{{ __('Sales List') }}</a></li>
                    @endusercan

                    @usercan('sale-returns.read')
                    <li><a class="{{ Request::routeIs('business.sale-returns.index') ? 'active' : '' }}" href="{{ route('business.sale-returns.index') }}">{{ __('Sales Return') }}</a></li>
                    @endusercan
                </ul>
            </li>
            @endusercanany

            @usercanany(['purchases.read', 'purchase-returns.read'])
                <li class="dropdown {{ Request::routeIs('business.purchases.index', 'business.purchases.create', 'business.purchases.edit', 'business.purchase-returns.create', 'business.purchase-returns.index') ? 'active' : '' }}">
                    <a href="#">
                        <span class="sidebar-icon">
                            <img src="{{ asset('assets/images/sidebar/Purchase.svg') }}">
                        </span>
                        {{ __('Purchases') }}</a>
                    <ul>
                        @usercan('purchases.create')
                        <li>
                            <a class="{{ Request::routeIs('business.purchases.create') ? 'active' : '' }}" href="{{ route('business.purchases.create') }}">{{ __('Add Purchase')}}</a>
                        </li>
                        @endusercan

                        @usercan('purchases.read')
                        <li><a class="{{ Request::routeIs('business.purchases.index',  'business.purchase-returns.create') ? 'active' : '' }}"
                                href="{{ route('business.purchases.index') }}">{{ __('Purchase List') }}</a></li>
                        @endusercan

                        @usercan('purchase-returns.read')
                        <li><a class="{{ Request::routeIs('business.purchase-returns.index') ? 'active' : '' }}"
                                href="{{ route('business.purchase-returns.index') }}">{{ __('Returns List') }}</a></li>
                        @endusercan

                    </ul>
                </li>
            @endusercanany

            @usercanany(['products.read', 'bulk-uploads.read', 'categories.read', 'brands.read', 'units.read', 'product-models.read'])
                <li class="dropdown {{ Request::routeIs('business.products.index', 'business.products.create', 'business.products.edit', 'business.products.expired', 'business.categories.index', 'business.brands.index', 'business.units.index', 'business.barcodes.index', 'business.bulk-uploads.index', 'business.variations.index', 'business.product-models.index','business.racks.index', 'business.shelfs.index', 'business.combo-products.index') ? 'active' : '' }}">
                    <a href="#">
                        <span class="sidebar-icon">
                            <img src="{{ asset('assets/images/sidebar/product.svg') }}">

                        </span>
                        {{ __('Products') }}</a>
                    <ul>
                        @usercan('products.read')
                        <li><a class="{{ Request::routeIs('business.products.index') ? 'active' : '' }}"
                                href="{{ route('business.products.index') }}">{{ __('All Product') }}</a>
                        </li>
                        @endusercan

                        @usercan('products.create')
                        <li>
                            <a class="{{ Request::routeIs('business.products.create') ? 'active' : '' }}" href="{{ route('business.products.create') }}">{{ __('Add Product') }}</a>
                        </li>
                        @endusercan

                        @usercan('products.read')
                        <li><a class="{{ Request::routeIs('business.combo-products.index') ? 'active' : '' }}"
                                href="{{ route('business.combo-products.index') }}">{{ __('Combo Products') }}</a>
                        </li>
                        @endusercan

                        @usercan('products-expired.read')
                         <li><a class="{{ Request::routeIs('business.products.expired') ? 'active' : '' }}" href="{{ route('business.products.expired') }}">{{ __('Expired Products') }}</a></li>
                        @endusercan

                        @usercan('barcodes.read')
                        <li>
                            <a class="{{ Request::routeIs('business.barcodes.index') ? 'active' : '' }}"
                               href="{{ route('business.barcodes.index') }}">{{ __('Print Labels') }}</a>
                        </li>
                        @endusercan

                        @usercan('bulk-uploads.read')
                        <li>
                            <a class="{{ Request::routeIs('business.bulk-uploads.index') ? 'active' : '' }}"
                               href="{{ route('business.bulk-uploads.index') }}">{{ __('Bulk Upload') }}</a>
                        </li>
                        @endusercan

                        @usercan('categories.read')
                        <li>
                            <a class="{{ Request::routeIs('business.categories.index') ? 'active' : '' }}"
                                href="{{ route('business.categories.index') }}">{{ __('Category') }}</a>
                        </li>
                        @endusercan

                        @usercan('brands.read')
                        <li>
                            <a class="{{ Request::routeIs('business.brands.index') ? 'active' : '' }}"
                                href="{{ route('business.brands.index') }}">{{ __('Brand') }}</a>
                        </li>
                        @endusercan

                        @usercan('product-models.read')
                        <li>
                            <a class="{{ Request::routeIs('business.product-models.index') ? 'active' : '' }}"
                                href="{{ route('business.product-models.index') }}">{{ __('Model') }}</a>
                        </li>
                        @endusercan

                        @usercan('variations.read')
                        <li>
                            <a class="{{ Request::routeIs('business.variations.index') ? 'active' : '' }}" href="{{ route('business.variations.index') }}">{{ __('Variation') }}</a>
                        </li>
                        @endusercan

                        @usercan('units.read')
                        <li>
                            <a class="{{ Request::routeIs('business.units.index') ? 'active' : '' }}"
                                href="{{ route('business.units.index') }}">{{ __('Unit') }}</a>
                        </li>
                        @endusercan

                       @usercan('racks.read')
                        <li>
                            <a class="{{ Request::routeIs('business.racks.index') ? 'active' : '' }}" href="{{ route('business.racks.index') }}">{{ __('Racks') }}</a>
                        </li>
                        @endusercan

                        @usercan('shelfs.read')
                        <li>
                            <a class="{{ Request::routeIs('business.shelfs.index') ? 'active' : '' }}" href="{{ route('business.shelfs.index') }}">{{ __('Shelfs') }}</a>
                        </li>
                        @endusercan

                    </ul>
                </li>
            @endusercanany

            @if (moduleCheck('WarehouseAddon'))
             @usercan('warehouses.read')
                <li class="dropdown {{ Request::routeIs('warehouse.warehouses.index','warehouse.warehouses.product') ? 'active' : '' }}">
                    <a class="position-relative" href="#">
                        <span class="sidebar-icon">
                            <img src="{{ asset('assets/images/sidebar/hrm.svg') }}">
                        </span>
                        {{ __('Warehouse') }}
                        @if (env('DEMO_MODE'))
                         <sup class="badge bg-warning position-absolute side-bar-addon">{{__('Add-On')}}</sup>
                        @endif
                    </a>

                    @usercan('warehouses.read')
                    <ul>
                        <li>
                            <a class="{{ Request::routeIs('warehouse.warehouses.index') ? 'active' : '' }}" href="{{ route('warehouse.warehouses.index') }}">{{ __('Warehouse') }}</a>
                        </li>
                    </ul>
                    @endusercan

                    @usercan('warehouses.read')
                    <ul>
                        <li>
                            <a class="{{ Request::routeIs('warehouse.warehouses.product') ? 'active' : '' }}" href="{{ route('warehouse.warehouses.product') }}">{{ __('Products') }}</a>
                        </li>
                    </ul>
                    @endusercan

                </li>
             @endusercan
            @endif

           @if ((moduleCheck('MultiBranchAddon') && ((plan_data()['allow_multibranch'] ?? false)) || moduleCheck('WarehouseAddon')))
           @usercan('transfers.read')
            <li class="{{ Request::routeIs('business.transfers.index','business.transfers.create','business.transfers.edit') ? 'active' : '' }}">
                <a href="{{ route('business.transfers.index') }}" class="active">
                    <span class="sidebar-icon">
                        <img src="{{ asset('assets/images/sidebar/transfer.svg') }}">
                    </span>
                    {{ __('Transfer') }}
                </a>
            </li>
            @endusercan
            @endif

            @if (moduleCheck('MultiBranchAddon') && (plan_data()['allow_multibranch'] ?? false))
            @usercan('branches.read')
            <li class="dropdown {{ Request::routeIs('multibranch.branches.index', 'multibranch.branches.overview', 'business.roles.index', 'business.roles.edit', 'business.roles.create') ? 'active' : '' }}">
                <a href="#">
                    <span class="sidebar-icon">
                        <img src="{{ asset('assets/images/sidebar/branch.svg') }}">
                    </span>
                    {{ __('Branch') }}
                    @if (env('DEMO_MODE'))
                    <sup class="badge bg-warning position-absolute side-bar-addon-2">{{__('Add-On')}}</sup>
                    @endif
                </a>
                <ul>
                    <li>
                        <a class="{{ Request::routeIs('multibranch.branches.overview') ? 'active' : '' }}" href="{{ route('multibranch.branches.overview') }}">{{ __('Overview') }}</a>
                    </li>
                    <li>
                        <a class="{{ Request::routeIs('multibranch.branches.index') ? 'active' : '' }}" href="{{ route('multibranch.branches.index') }}">{{ __('Branch List') }}</a>
                    </li>
                    <li>
                        <a class="{{ Request::routeIs('business.roles.index', 'business.roles.edit', 'business.roles.create') ? 'active' : '' }}" href="{{ route('business.roles.index') }}">{{ __('Role & permissions') }}</a>
                    </li>
                </ul>
            </li>
            @endusercan
            @endif

            @usercanany(['stocks.read', 'expired-products.read'])
            <li class="dropdown {{ Request::routeIs('business.stocks.index','business.expired-products.index') ? 'active' : '' }}">
                <a href="#">
                    <span class="sidebar-icon">
                        <img src="{{ asset('assets/images/sidebar/stocklist.svg') }}">
                    </span>
                    {{ __('Stock List') }}
                </a>
                <ul>
                    @usercan('stocks.read')
                    <li>
                        <a class="{{ Request::routeIs('business.stocks.index') && !request('alert_qty')  ? 'active' : '' }}" href="{{ route('business.stocks.index') }}">{{ __('All Stock') }}</a>
                    </li>
                    @endusercan
                    @usercan('stocks.read')
                    <li>
                        <a class="{{ Request::routeIs('business.stocks.index') && request('alert_qty') ? 'active' : '' }}" href="{{ route('business.stocks.index', ['alert_qty' => true]) }}">{{ __('Low Stock') }}</a>
                    </li>
                    @endusercan
                    @usercan('expired-products.read')
                    <li>
                        <a class="{{ Request::routeIs('business.expired-products.index') ? 'active' : '' }}" href="{{ route('business.expired-products.index') }}">{{ __('Expired Products') }}</a>
                    </li>
                    @endusercan
                </ul>
            </li>
            @endusercanany

            @usercanany(['parties.read', 'parties.create'])
            <li class="dropdown {{ (Request::routeIs('business.parties.index') && request('type') == 'Customer') || (Request::routeIs('business.parties.create') && request('type') == 'Customer') || (Request::routeIs('business.parties.edit') && request('type') == 'Customer') ? 'active' : '' }}">
                <a href="#">
                    <span class="sidebar-icon">
                        <img src="{{ asset('assets/images/sidebar/customer.svg') }}">

                    </span>
                    {{ __('Customers') }}
                </a>
                <ul>
                    @usercan('parties.read')
                    <li><a class="{{ Request::routeIs('business.parties.index') && request('type') == 'Customer' ? 'active' : '' }}" href="{{ route('business.parties.index', ['type' => 'Customer']) }}">{{ __('All Customers') }}</a>
                    </li>
                    @endusercan
                    @usercan('parties.create')
                    <li><a class="{{ Request::routeIs('business.parties.create') && request('type') == 'Customer' ? 'active' : '' }}" href="{{ route('business.parties.create', ['type' => 'Customer']) }}">{{ __('Add Customer') }}</a>
                    </li>
                    @endusercan
                </ul>
            </li>
            @endusercanany

            @usercanany(['parties.read', 'parties.create'])
            <li class="dropdown {{ (Request::routeIs('business.parties.index') && request('type') == 'Supplier') || (Request::routeIs('business.parties.create') && request('type') == 'Supplier') || (Request::routeIs('business.parties.edit') && request('type') == 'Supplier') ? 'active' : '' }}">
                <a href="#">
                    <span class="sidebar-icon">
                        <img src="{{ asset('assets/images/sidebar/supplier.svg') }}">

                    </span>
                    {{ __('Suppliers') }}
                </a>
                <ul>
                    @usercan('parties.read')
                    <li>
                        <a class="{{ Request::routeIs('business.parties.index') && request('type') == 'Supplier' ? 'active' : '' }}" href="{{ route('business.parties.index', ['type' => 'Supplier']) }}">{{ __('All Suppliers') }}</a>
                    </li>
                    @endusercan
                    @usercan('parties.create')
                    <li>
                        <a class="{{ Request::routeIs('business.parties.create') && request('type') == 'Supplier' ? 'active' : '' }}" href="{{ route('business.parties.create', ['type' => 'Supplier']) }}">{{ __('Add Supplier') }}</a>
                    </li>
                    @endusercan
                </ul>
            </li>
            @endusercanany

            @usercan('vats.read')
            <li class="{{ Request::routeIs('business.vats.index') ? 'active' : '' }}">
                <a href="{{ route('business.vats.index') }}" class="active">
                    <span class="sidebar-icon">
                        <img src="{{ asset('assets/images/sidebar/subscription.svg') }}">
                    </span>
                    {{ __('Tax Setting') }}
                </a>
            </li>
            @endusercan

            @usercan('dues.read')
                <li class="dropdown {{ Request::routeIs('business.dues.index','business.walk-dues.index','business.collect.walk.dues','business.collect.dues', 'business.party.dues') ? 'active' : '' }}">
                    <a href="#">
                        <span class="sidebar-icon">
                            <img src="{{ asset('assets/images/sidebar/duelist.svg') }}">
                        </span>
                        {{ __('Due List') }}
                    </a>
                    <ul>
                        <li>
                            <a class="{{ Request::routeIs('business.dues.index') ? 'active' : '' }}" href="{{ route('business.dues.index') }}">{{ __('All Due') }}</a>
                        </li>

                        <li>
                            <a class="{{ Request::routeIs('business.walk-dues.index','business.collect.walk.dues') ? 'active' : '' }}" href="{{ route('business.walk-dues.index') }}">{{ __('Guest Due') }}</a>
                        </li>

                        <li class="{{ (Request::routeIs('business.party.dues') && request('type') == 'Retailer') ? 'active' : '' }}">
                            <a href="{{ route('business.party.dues', ['type' => 'Retailer']) }}">
                                {{ __('Customer Due') }}
                            </a>
                        </li>

                        <li class="{{ (Request::routeIs('business.party.dues') && request('type') == 'Dealer') ? 'active' : '' }}">
                            <a href="{{ route('business.party.dues', ['type' => 'Dealer']) }}">
                                {{ __('Dealer Due') }}
                            </a>
                        </li>

                        <li class="{{ (Request::routeIs('business.party.dues') && request('type') == 'Wholesaler') ? 'active' : '' }}">
                            <a href="{{ route('business.party.dues', ['type' => 'Wholesaler']) }}">
                                {{ __('Wholesaler Due') }}
                            </a>
                        </li>

                        <li class="{{ (Request::routeIs('business.party.dues') && request('type') == 'Supplier') ? 'active' : '' }}">
                            <a href="{{ route('business.party.dues', ['type' => 'Supplier']) }}">
                                {{ __('Supplier Due') }}
                            </a>
                        </li>

                    </ul>
                </li>
            @endusercan

            <li class="dropdown {{ Request::routeIs('business.banks.index', 'business.banks.create','business.cashes.index', 'business.cheques.index', 'business.bank-transactions.index', 'business.loss-profit-history.index', 'business.transactions.index', 'business.incomes.index', 'business.income-categories.index', 'business.expenses.index', 'business.expense-categories.index', 'business.day-book-reports.index', 'business.cash-flow-reports.index','business.balance-sheet.index') ? 'active' : '' }}">
                <a href="#">
                    <span class="sidebar-icon">
                        <img src="{{ asset('assets/images/sidebar/cash_and_bank.svg') }}">
                    </span>
                    {{ __('Finance & Accounts') }}</a>
                <ul>
                    @usercan('banks.read')
                    <li><a class="{{ Request::routeIs('business.banks.index') ? 'active' : '' }}" href="{{ route('business.banks.index') }}">{{ __('Bank Account') }}</a></li>
                    @endusercan
                    @usercan('cashes.read')
                    <li><a class="{{ Request::routeIs('business.cashes.index') ? 'active' : '' }}" href="{{ route('business.cashes.index') }}">{{ __('Cash In Hand') }}</a></li>
                    @endusercan
                    @usercan('cheques.read')
                    <li><a class="{{ Request::routeIs('business.cheques.index') ? 'active' : '' }}" href="{{ route('business.cheques.index') }}">{{ __('Cheques') }}</a></li>
                    @endusercan
                    @usercan('loss-profit-history.read')
                    <li><a class="{{ Request::routeIs('business.loss-profit-history.index') ? 'active' : '' }}" href="{{ route('business.loss-profit-history.index') }}">{{ __('Profit & Loss') }}</a></li>
                    @endusercan
                    @usercan('transactions.read')
                    <li><a class="{{ Request::routeIs('business.transactions.index') ? 'active' : '' }}" href="{{ route('business.transactions.index') }}">{{ __('Transactions') }}</a></li>
                    @endusercan
                    @usercan('day-book-reports.read')
                    <li><a class="{{ Request::routeIs('business.day-book-reports.index') ? 'active' : '' }}" href="{{ route('business.day-book-reports.index') }}">{{ __('Day Book') }}</a></li>
                    @endusercan
                    @usercan('cash-flow-reports.read')
                    <li><a class="{{ Request::routeIs('business.cash-flow-reports.index') ? 'active' : '' }}" href="{{ route('business.cash-flow-reports.index') }}">{{ __('Cash Flow') }}</a></li>
                    @endusercan
                    <li><a class="{{ Request::routeIs('business.balance-sheet.index') ? 'active' : '' }}" href="{{ route('business.balance-sheet.index') }}">{{ __('Balance Sheet') }}</a></li>
                    @usercan('incomes.read')
                    <li><a class="{{ Request::routeIs('business.incomes.index') ? 'active' : '' }}" href="{{ route('business.incomes.index') }}">{{ __('Income') }}</a></li>
                    @endusercan
                    @usercan('income-categories.read')
                    <li><a class="{{ Request::routeIs('business.income-categories.index') ? 'active' : '' }}" href="{{ route('business.income-categories.index') }}">{{ __('Income Category') }}</a></li>
                    @endusercan
                    @usercan('expenses.read')
                    <li><a class="{{ Request::routeIs('business.expenses.index') ? 'active' : '' }}" href="{{ route('business.expenses.index') }}">{{ __('Expenses') }}</a></li>
                    @endusercan
                    @usercan('expense-categories.read')
                    <li><a class="{{ Request::routeIs('business.expense-categories.index') ? 'active' : '' }}" href="{{ route('business.expense-categories.index') }}">{{ __('Expense Category') }}</a></li>
                    @endusercan
                </ul>
            </li>

            @usercan('subscriptions.read')
            <li class="{{ Request::routeIs('business.subscriptions.index') ? 'active' : '' }}">
                <a href="{{ route('business.subscriptions.index') }}" class="active">
                    <span class="sidebar-icon">
                        <img src="{{ asset('assets/images/sidebar/subscription.svg') }}">
                    </span>
                    {{ __('Subscriptions') }}
                </a>
            </li>
            @endusercan

            {{-- <li class="dropdown {{ Request::routeIs('business.commissions.index','business.sale-commissions.index') ? 'active' : '' }}">
                <a href="#">
                    <span class="sidebar-icon">
                        <img src="{{ asset('assets/images/sidebar/cash_and_bank.svg') }}">
                    </span>
                    {{ __('Sale Commission') }}</a>
                <ul>
                    @usercan('commissions.read')
                    <li><a class="{{ Request::routeIs('business.commissions.index') ? 'active' : '' }}" href="{{ route('business.commissions.index') }}">{{ __('Set Commissions') }}</a></li>
                    @endusercan
                    @usercan('sale-commissions.read')
                    <li><a class="{{ Request::routeIs('business.sale-commissions.index') ? 'active' : '' }}" href="{{ route('business.sale-commissions.index') }}">{{ __('Sale Commission') }}</a></li>
                    @endusercan
                </ul>
            </li> --}}

            @if (moduleCheck('HrmAddon'))
              @usercanany(['department.read', 'designations.read', 'shifts.read', 'employees.read', 'leave-types.read', 'leaves.read', 'holidays.read', 'attendances.read', 'payrolls.read', 'attendance-reports.read', 'payroll-reports.read', 'leave-reports.read'])
                <li class="dropdown {{ Request::routeIs('hrm.department.index', 'hrm.designations.index', 'hrm.shifts.index', 'hrm.employees.index', 'hrm.employees.create', 'hrm.employees.edit', 'hrm.leave-types.index', 'hrm.leaves.index', 'hrm.holidays.index', 'hrm.attendances.index', 'hrm.payrolls.index', 'hrm.attendance-reports.index','hrm.leave-reports.index','hrm.payroll-reports.index') ? 'active' : '' }}">
                    <a class="position-relative" href="#">
                        <span class="sidebar-icon">
                            <img src="{{ asset('assets/images/sidebar/hrm.svg') }}">
                        </span>
                        {{ __('HRM') }}
                        @if (env('DEMO_MODE'))
                        <sup class="badge bg-warning position-absolute side-bar-addon-3">{{__('Add-On')}}</sup>
                        @endif
                    </a>
                    @usercan('department.read')
                    <ul>
                        <li>
                            <a class="{{ Request::routeIs('hrm.department.index') ? 'active' : '' }}"
                                href="{{ route('hrm.department.index') }}">{{ __('Department') }}</a>
                        </li>
                    </ul>
                    @endusercan
                    @usercan('designations.read')
                    <ul>
                        <li>
                            <a class="{{ Request::routeIs('hrm.designations.index') ? 'active' : '' }}"
                                href="{{ route('hrm.designations.index') }}">{{ __('Designation') }}</a>
                        </li>
                    </ul>
                    @endusercan
                    @usercan('shifts.read')
                    <ul>
                        <li>
                            <a class="{{ Request::routeIs('hrm.shifts.index') ? 'active' : '' }}"
                                href="{{ route('hrm.shifts.index') }}">{{ __('Shift') }}</a>
                        </li>
                    </ul>
                    @endusercan
                    @usercan('employees.read')
                    <ul>
                        <li>
                            <a class="{{ Request::routeIs('hrm.employees.index', 'hrm.employees.create', 'hrm.employees.edit') ? 'active' : '' }}"
                                href="{{ route('hrm.employees.index') }}">{{ __('Employee') }}</a>
                        </li>
                    </ul>
                    @endusercan
                    @usercanany(['leave-types.read', 'leaves.read'])
                    <ul>
                        <li class="dropdown {{ Request::routeIs('hrm.leave-types.index', 'hrm.leaves.index') ? 'active' : '' }}">
                            <a href="">{{ __('Leave Request') }}</a>
                            <ul>
                                @usercan('leave-types.read')
                                <li>
                                    <a class="{{ Request::routeIs('hrm.leave-types.index') ? 'active' : '' }}"
                                        href="{{ route('hrm.leave-types.index') }}">{{ __('Leave Type') }}</a>
                                </li>
                                @endusercan
                                @usercan('leaves.read')
                                <li>
                                    <a class="{{ Request::routeIs('hrm.leaves.index') ? 'active' : '' }}"
                                        href="{{ route('hrm.leaves.index') }}">{{ __('Leave') }}</a>
                                </li>
                                @endusercan
                            </ul>
                        </li>
                    </ul>
                    @endusercanany

                    @usercan('holidays.read')
                    <ul>
                        <li>
                            <a class="{{ Request::routeIs('hrm.holidays.index') ? 'active' : '' }}"
                                href="{{ route('hrm.holidays.index') }}">{{ __('Holiday') }}</a>
                        </li>
                    </ul>
                    @endusercan
                    @usercan('attendances.read')
                    <ul>
                        <li>
                            <a class="{{ Request::routeIs('hrm.attendances.index') ? 'active' : '' }}"
                                href="{{ route('hrm.attendances.index') }}">{{ __('Attendance') }}</a>
                        </li>
                    </ul>
                    @endusercan
                    @usercan('payrolls.read')
                    <ul>
                        <li>
                            <a class="{{ Request::routeIs('hrm.payrolls.index') ? 'active' : '' }}"
                                href="{{ route('hrm.payrolls.index') }}">{{ __('Payroll') }}</a>
                        </li>
                    </ul>
                    @endusercan

                    @usercanany(['attendance-reports.read', 'payroll-reports.read', 'leave-reports.read'])
                    <ul>
                        <li class="dropdown {{ Request::routeIs('hrm.attendance-reports.index', 'hrm.payroll-reports.index','hrm.leave-reports.index') ? 'active' : '' }}">
                            <a href="">{{ __('Reports') }}</a>
                            <ul>
                                @usercan('attendance-reports.read')
                                <li>
                                    <a class="{{ Request::routeIs('hrm.attendance-reports.index') ? 'active' : '' }}"
                                        href="{{ route('hrm.attendance-reports.index') }}">{{ __('Attendance') }}</a>
                                </li>
                                @endusercan
                                @usercan('payroll-reports.read')
                                <li>
                                    <a class="{{ Request::routeIs('hrm.payroll-reports.index') ? 'active' : '' }}"
                                        href="{{ route('hrm.payroll-reports.index') }}">{{ __('Payroll') }}</a>
                                </li>
                                @endusercan
                                @usercan('leave-reports.read')
                                <li>
                                    <a class="{{ Request::routeIs('hrm.leave-reports.index') ? 'active' : '' }}" href="{{ route('hrm.leave-reports.index') }}">{{ __('Leave') }}</a>
                                </li>
                                @endusercan
                            </ul>
                        </li>
                    </ul>
                    @endusercanany
                </li>
                @endusercanany
            @endif

            @usercanany(['sale-reports.read', 'sale-return-reports.read', 'purchase-reports.read', 'purchase-return-reports.read', 'vat-reports.read', 'income-reports.read', 'expense-reports.read', 'loss-profits-details.read', 'stock-reports.read', 'due-reports.read', 'supplier-due-reports.read', 'loss-profit-reports.read', 'transaction-history-reports.read', 'subscription-reports.read', 'expired-product-reports.read'])
                <li class="dropdown {{ Request::routeIs('business.income-reports.index', 'business.expense-reports.index', 'business.stock-reports.index', 'business.sale-reports.index', 'business.purchase-reports.index', 'business.due-reports.index', 'business.sale-return-reports.index', 'business.purchase-return-reports.index', 'business.supplier-due-reports.index', 'business.transaction-history-reports.index', 'business.subscription-reports.index', 'business.expired-product-reports.index','business.vat-reports.index', 'business.loss-profit-reports.details', 'business.custom-reports.show', 'business.top-product-reports.index', 'business.product-loss-profit-reports.index', 'business.discount-product-reports.index', 'business.combo-product-reports.index', 'business.product-purchase-reports.index', 'business.product-sale-reports.index', 'business.bill-wise-profits.index','business.loss-profit-history-reports.index', 'business.product-sale-history-reports.index', 'business.product-sale-history-reports.show', 'business.top-customer-reports.index', 'business.top-supplier-reports.index', 'business.product-purchase-history-reports.index', 'business.product-purchase-history-reports.show', 'hrm.attendance-report-history.index', 'hrm.payroll-report-history.index', 'hrm.leave-report-history.index') ? 'active' : '' }}">
                    <a href="#">
                        <span class="sidebar-icon">
                            <img src="{{ asset('assets/images/sidebar/Report.svg') }}">
                        </span>
                        {{ __('Reports') }}</a>
                    <ul>
                        @usercan('sale-reports.read')
                        <li><a class="{{ Request::routeIs('business.sale-reports.index') ? 'active' : '' }}"
                                href="{{ route('business.sale-reports.index') }}">{{ __('Sale') }}</a></li>
                        @endusercan

                        @usercan('sale-return-reports.read')
                        <li><a class="{{ Request::routeIs('business.sale-return-reports.index') ? 'active' : '' }}"
                                href="{{ route('business.sale-return-reports.index') }}">{{ __('Sale Return') }}</a>
                        </li>
                        @endusercan

                        @usercan('purchase-reports.read')
                        <li><a class="{{ Request::routeIs('business.purchase-reports.index') ? 'active' : '' }}"
                                href="{{ route('business.purchase-reports.index') }}">{{ __('Purchase') }}</a>
                        </li>
                        @endusercan

                        @usercan('purchase-return-reports.read')
                        <li><a class="{{ Request::routeIs('business.purchase-return-reports.index') ? 'active' : '' }}"
                                href="{{ route('business.purchase-return-reports.index') }}">{{ __('Purchase Return') }}</a>
                        </li>
                        @endusercan

                        @usercan('vat-reports.read')
                        <li><a class="{{ Request::routeIs('business.vat-reports.index') ? 'active' : '' }}"
                            href="{{ route('business.vat-reports.index') }}">{{ __('Tax Report') }}</a>
                        </li>
                        @endusercan

                        @usercan('income-reports.read')
                        <li><a class="{{ Request::routeIs('business.income-reports.index') ? 'active' : '' }}"
                                href="{{ route('business.income-reports.index') }}">{{ __('Income') }}</a></li>
                        @endusercan

                        @usercan('expense-reports.read')
                        <li><a class="{{ Request::routeIs('business.expense-reports.index') ? 'active' : '' }}"
                                href="{{ route('business.expense-reports.index') }}">{{ __('Expense') }}</a>
                        </li>
                        @endusercan


                        @usercan('stock-reports.read')
                        <li><a class="{{ Request::routeIs('business.stock-reports.index') ? 'active' : '' }}"
                                href="{{ route('business.stock-reports.index') }}">{{ __('Current Stock') }}</a>
                        </li>
                        @endusercan

                        @usercan('due-reports.read')
                        <li><a class="{{ Request::routeIs('business.due-reports.index') ? 'active' : '' }}"
                                href="{{ route('business.due-reports.index') }}">{{ __('Customer Due') }}</a></li>
                        @endusercan

                        @usercan('supplier-due-reports.read')
                        <li><a class="{{ Request::routeIs('business.supplier-due-reports.index') ? 'active' : '' }}"
                                href="{{ route('business.supplier-due-reports.index') }}">{{ __('Supplier Due') }}</a>
                        </li>
                        @endusercan

                        @usercan('bill-wise-profits.read')
                        <li>
                            <a class="{{ Request::routeIs('business.bill-wise-profits.index') ? 'active' : '' }}"
                                href="{{ route('business.bill-wise-profits.index') }}">{{ __('Bill Wise Profit & Loss') }}</a>
                        </li>
                        @endusercan

                        @usercan('product-loss-profit-reports.read')
                        <li><a class="{{ Request::routeIs('business.product-loss-profit-reports.index') ? 'active' : '' }}"
                            href="{{ route('business.product-loss-profit-reports.index') }}">{{ __('Product Wise Profit & Loss') }}</a>
                        </li>
                        @endusercan

                        @usercan('transaction-history-reports.read')
                        <li><a class="{{ Request::routeIs('business.transaction-history-reports.index') ? 'active' : '' }}"
                                href="{{ route('business.transaction-history-reports.index') }}">{{ __('Due Transaction') }}</a>
                        </li>
                        @endusercan

                        @usercan('subscription-reports.read')
                        <li><a class="{{ Request::routeIs('business.subscription-reports.index') ? 'active' : '' }}"
                            href="{{ route('business.subscription-reports.index') }}">{{ __('Subscription Report') }}</a>
                        </li>
                        @endusercan

                        @usercan('top-customers-reports.read')
                        <li>
                            <a class="{{ Request::routeIs('business.top-customer-reports.index') ? 'active' : '' }}"
                                href="{{ route('business.top-customer-reports.index') }}">{{ __('Top 5 Customer') }}</a>
                        </li>
                        @endusercan

                        @usercan('top-suppliers-reports.read')
                        <li>
                            <a class="{{ Request::routeIs('business.top-supplier-reports.index') ? 'active' : '' }}"
                                href="{{ route('business.top-supplier-reports.index') }}">{{ __('Top 5 Supplier') }}</a>
                        </li>
                        @endusercan

                        @usercan('top-product-reports.read')
                        <li>
                            <a class="{{ Request::routeIs('business.top-product-reports.index') ? 'active' : '' }}"
                            href="{{ route('business.top-product-reports.index') }}">{{ __('Top 5 Product') }}</a>
                        </li>
                        @endusercan

                        @usercan('combo-product-reports.read')
                        <li><a class="{{ Request::routeIs('business.combo-product-reports.index') ? 'active' : '' }}"
                            href="{{ route('business.combo-product-reports.index') }}">{{ __('Combo Product') }}</a>
                        </li>
                        @endusercan

                        @usercan('discount-product-reports.read')
                        <li><a class="{{ Request::routeIs('business.discount-product-reports.index') ? 'active' : '' }}"
                            href="{{ route('business.discount-product-reports.index') }}">{{ __('Discount Product') }}</a>
                        </li>
                        @endusercan

                        @usercan('product-purchase-reports.read')
                        <li><a class="{{ Request::routeIs('business.product-purchase-reports.index') ? 'active' : '' }}"
                            href="{{ route('business.product-purchase-reports.index') }}">{{ __('Product Wise Purchase') }}</a>
                        </li>
                        @endusercan

                        @usercan('product-sale-reports.read')
                        <li><a class="{{ Request::routeIs('business.product-sale-reports.index') ? 'active' : '' }}"
                            href="{{ route('business.product-sale-reports.index') }}">{{ __('Product Wise Sale') }}</a>
                        </li>
                        @endusercan

                        @usercan('expired-product-reports.read')
                        <li><a class="{{ Request::routeIs('business.expired-product-reports.index') ? 'active' : '' }}"
                            href="{{ route('business.expired-product-reports.index') }}">{{ __('Expired Product') }}</a>
                        </li>
                        @endusercan

                        @usercan('loss-profit-history-reports.read')
                        <li><a class="{{ Request::routeIs('business.loss-profit-history-reports.index') ? 'active' : '' }}"
                            href="{{ route('business.loss-profit-history-reports.index') }}">{{ __('Loss Profit History') }}</a>
                        </li>
                        @endusercan

                        @usercan('product-sale-history-reports.read')
                        <li><a class="{{ Request::routeIs('business.product-sale-history-reports.index', 'business.product-sale-history-reports.show') ? 'active' : '' }}"
                            href="{{ route('business.product-sale-history-reports.index') }}">{{ __('Product Sale History') }}</a>
                        </li>
                        @endusercan

                        @usercan('product-purchase-history-reports.read')
                        <li><a class="{{ Request::routeIs('business.product-purchase-history-reports.index', 'business.product-purchase-history-reports.show') ? 'active' : '' }}"
                            href="{{ route('business.product-purchase-history-reports.index') }}">{{ __('Product Purchase History') }}</a>
                        </li>
                        @endusercan

                        @if (moduleCheck('HrmAddon'))
                            @usercan('attendance-reports.read')
                            <li>
                                <a class="{{ Request::routeIs('hrm.attendance-report-history.index') ? 'active' : '' }}"
                                    href="{{ route('hrm.attendance-report-history.index') }}">{{ __('Attendance') }}</a>
                            </li>
                            @endusercan
                            @usercan('payroll-reports.read')
                            <li>
                                <a class="{{ Request::routeIs('hrm.payroll-report-history.index') ? 'active' : '' }}"
                                    href="{{ route('hrm.payroll-report-history.index') }}">{{ __('Payroll') }}</a>
                            </li>
                            @endusercan
                            @usercan('leave-reports.read')
                            <li>
                                <a class="{{ Request::routeIs('hrm.leave-report-history.index') ? 'active' : '' }}"
                                    href="{{ route('hrm.leave-report-history.index') }}">{{ __('Leave') }}</a>
                            </li>
                            @endusercan
                        @endif

                        @foreach (custom_reports() as $custom_report)
                        <li>
                            <a class="{{ $custom_report->slug == request()->route('custom_report') ? 'active' : '' }}" href="{{ route('business.custom-reports.show', $custom_report->slug) }}">
                                {{ $custom_report->name }}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </li>
            @endusercanany

            <li class="dropdown {{ Request::routeIs('business.customer-ledger.index', 'business.supplier-ledger.index', 'business.top-customers.index', 'business.top-suppliers.index', 'business.party-loss-profit.index', 'business.customer-ledger.show', 'business.supplier-ledger.show') ? 'active' : '' }}">
                    <a href="#">
                        <span class="sidebar-icon">
                            <img src="{{ asset('assets/images/icons/party-report.png') }}">
                        </span>
                        {{ __('Party Reports') }}</a>
                    <ul>
                        @usercan('customer-ledger.read')
                        <li>
                            <a class="{{ Request::routeIs('business.customer-ledger.index','business.customer-ledger.show') ? 'active' : '' }}"
                                href="{{ route('business.customer-ledger.index') }}">{{ __('Customer Ledger') }}
                            </a>
                        </li>
                        @endusercan

                        @usercan('supplier-ledger.read')
                        <li>
                            <a class="{{ Request::routeIs('business.supplier-ledger.index', 'business.supplier-ledger.show') ? 'active' : '' }}"
                                href="{{ route('business.supplier-ledger.index') }}">{{ __('Supplier Ledger') }}
                            </a>
                        </li>
                        @endusercan

                        @usercan('party-loss-profit.read')
                        <li>
                            <a class="{{ Request::routeIs('business.party-loss-profit.index') ? 'active' : '' }}"
                                href="{{ route('business.party-loss-profit.index') }}">{{ __('Party Profit & Loss') }}
                            </a>
                        </li>
                        @endusercan

                        @usercan('top-customers-reports.read')
                        <li>
                            <a class="{{ Request::routeIs('business.top-customers.index') ? 'active' : '' }}"
                                href="{{ route('business.top-customers.index') }}">{{ __('Top 5 Customer') }}
                            </a>
                        </li>
                        @endusercan

                        @usercan('top-suppliers-reports.read')
                        <li>
                            <a class="{{ Request::routeIs('business.top-suppliers.index') ? 'active' : '' }}"
                                href="{{ route('business.top-suppliers.index') }}">{{ __('Top 5 Supplier') }}
                            </a>
                        </li>
                        @endusercan
                    </ul>
                </li>

            {{-- @if (moduleCheck('CustomReportsAddon'))
                @usercanany(['custom-reports.read', 'custom-reports.create'])
                    <li class="dropdown {{ Request::routeIs('business.custom-reports.index', 'business.custom-reports.create', 'business.custom-reports.edit') ? 'active' : '' }}">
                        <a href="#">
                            <span class="sidebar-icon">
                                <img src="{{ asset('assets/images/sidebar/custom_report.svg') }}">
                            </span>
                            {{ __('Custom Reports') }}</a>
                        <ul>
                            @usercan('custom-reports.create')
                            <li>
                                <a class="{{ Request::routeIs('business.custom-reports.create') ? 'active' : '' }}" href="{{ route('business.custom-reports.create') }}">{{ __('Add New') }}</a>
                            </li>
                            @endusercan
                            @usercan('custom-reports.read')
                            <li>
                                <a class="{{ Request::routeIs('business.custom-reports.index') ? 'active' : '' }}" href="{{ route('business.custom-reports.index') }}">{{ __('View List') }}</a>
                            </li>
                            @endusercan
                        </ul>
                    </li>
                @endusercanany
            @endif --}}

            @if (moduleCheck('CustomDomainAddon'))
             @usercanany(['domains.read', 'domains.read'])
                <li class="{{ Request::routeIs('business.domains.index') ? 'active' : '' }}">
                    <a href="{{ route('business.domains.index') }}" class="active">
                        <span class="sidebar-icon">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18.0343 6.47825H17.183C15.8567 3.78589 13.1445 2.04377 10.1354 1.98438H9.87802C6.8689 2.04377 4.15674 3.76609 2.83035 6.47825H1.97909C1.306 6.47825 0.771484 7.01277 0.771484 7.68586V12.2985C0.771484 12.9716 1.306 13.5061 1.97909 13.5061H2.83035C4.15674 16.1985 6.8689 17.9406 9.87802 18H10.1552C13.1643 17.9406 15.8765 16.2183 17.2028 13.5061H18.0541C18.7272 13.5061 19.2617 12.9716 19.2617 12.2985V7.70566C19.2617 7.03256 18.7074 6.49805 18.0343 6.47825ZM6.45317 6.47825C7.10647 4.35999 8.31407 2.93462 9.66026 2.71686V6.47825H6.45317ZM10.3531 2.71686C11.6795 2.93462 12.8871 4.35999 13.5602 6.47825H10.3531V2.71686ZM14.2729 6.47825C13.8572 5.05288 13.2237 3.88487 12.4516 3.11279C14.1195 3.69185 15.5746 4.91925 16.411 6.47825H14.2729ZM7.56179 3.11279C6.78972 3.88487 6.15622 5.05288 5.74049 6.47825H3.58263C4.45369 4.9143 5.85927 3.7265 7.56179 3.11279ZM3.58263 13.5061H5.72069C6.13642 14.9513 6.76992 16.0995 7.542 16.8716C5.85927 16.2777 4.45369 15.0899 3.58263 13.5061ZM10.3531 17.2873V13.5259H13.5602C12.8871 15.6442 11.6795 17.0696 10.3531 17.2873ZM9.66026 17.2873C8.33387 17.0696 7.12626 15.6442 6.45317 13.5259H9.66026V17.2873ZM16.1536 13.9219C15.2677 15.2779 13.9562 16.3717 12.412 16.8914C13.1841 16.1193 13.8176 14.9513 14.2333 13.5259H16.3714C16.3714 13.5259 16.2476 13.7734 16.1536 13.9219ZM18.5688 12.2985C18.5688 12.5757 18.3313 12.8132 18.0541 12.8132H1.95929C1.68214 12.8132 1.44458 12.5757 1.44458 12.2985V7.70566C1.44458 7.4285 1.68214 7.19094 1.95929 7.19094H18.0541C18.3313 7.19094 18.5688 7.4285 18.5688 7.70566V12.2985Z" fill="white"/>
                                <path d="M6.65198 8.47784C6.47381 8.39865 6.27584 8.47784 6.19665 8.65601L5.44437 10.2793L4.92966 8.97276C4.87027 8.83418 4.75149 8.75499 4.61291 8.75499C4.47433 8.75499 4.33575 8.83418 4.29616 8.97276L3.78144 10.2793L3.04896 8.65601C2.96977 8.47784 2.7718 8.39865 2.59363 8.47784C2.41546 8.55703 2.33627 8.75499 2.41546 8.93317L3.48449 11.3088C3.54388 11.4276 3.66266 11.5068 3.80124 11.5068C3.93982 11.5068 4.0586 11.4078 4.11799 11.289L4.61291 10.0418L5.10783 11.289C5.16722 11.4276 5.286 11.5068 5.42458 11.5068C5.56316 11.5068 5.68194 11.4276 5.74133 11.3088L6.81035 8.93317C6.88954 8.77479 6.83015 8.55703 6.65198 8.47784ZM12.0565 8.47784C11.8783 8.39865 11.6804 8.47784 11.6012 8.65601L10.8687 10.2793L10.354 8.97276C10.2946 8.83418 10.1758 8.75499 10.0372 8.75499C9.89866 8.75499 9.76008 8.83418 9.72049 8.97276L9.20577 10.2793L8.47329 8.65601C8.3941 8.47784 8.19613 8.39865 8.01796 8.47784C7.83979 8.55703 7.7606 8.75499 7.83979 8.93317L8.90882 11.3088C8.96821 11.4276 9.08699 11.5068 9.22557 11.5068C9.36414 11.5068 9.48292 11.4078 9.54232 11.289L10.0372 10.0418L10.5322 11.289C10.5915 11.4276 10.7103 11.5068 10.8489 11.5068C10.9875 11.5068 11.1063 11.4276 11.1657 11.3088L12.2347 8.93317C12.3139 8.77479 12.2347 8.55703 12.0565 8.47784ZM17.4808 8.47784C17.3027 8.39865 17.1047 8.47784 17.0255 8.65601L16.293 10.2793L15.7783 8.97276C15.7189 8.83418 15.6001 8.75499 15.4616 8.75499C15.323 8.75499 15.1844 8.83418 15.1448 8.97276L14.6301 10.2793L13.8976 8.65601C13.8184 8.47784 13.6205 8.39865 13.4423 8.47784C13.2641 8.55703 13.1849 8.75499 13.2641 8.93317L14.3331 11.3088C14.3925 11.4276 14.5113 11.5068 14.6499 11.5068C14.7885 11.5068 14.9073 11.4078 14.9666 11.289L15.4616 10.0418L15.9565 11.289C16.0159 11.4276 16.1347 11.5068 16.2732 11.5068C16.4118 11.5068 16.5306 11.4276 16.59 11.3088L17.659 8.93317C17.7382 8.77479 17.659 8.55703 17.4808 8.47784Z" fill="white"/>
                            </svg>
                        </span>
                        {{ __('My Domains') }}
                    </a>
                </li>
             @endusercanany
            @endif

            @if (moduleCheck('MarketingAddon'))
            <li class="dropdown {{ Request::routeIs('business.sms-templates.index', 'business.sms-gateways.index', 'business.sms-gateways.create', 'business.sms-gateways.edit', 'business.devices.index', 'business.devices.create', 'business.devices.edit') ? 'active' : '' }}">
                <a href="#">
                    <span class="sidebar-icon">
                        <img src="{{ asset('assets/images/sidebar/stocklist.svg') }}">
                    </span>
                    {{ __('SMS Marketing') }}
                </a>
                <ul>
                    <li>
                        <a class="{{ Request::routeIs('business.sms-templates.index') ? 'active' : '' }}" href="{{ route('business.sms-templates.index') }}">{{ __('SMS Template') }}</a>
                    </li>
                    <li>
                        <a class="{{ Request::routeIs('business.sms-gateways.index', 'business.sms-gateways.create', 'business.sms-gateways.edit') ? 'active' : '' }}" href="{{ route('business.sms-gateways.index') }}">{{ __('API Gateway') }}</a>
                    </li>
                    <li>
                        <a class="{{ Request::routeIs('business.devices.index', 'business.devices.create', 'business.devices.edit') ? 'active' : '' }}" href="{{ route('business.devices.index') }}">{{ __('Android Gateway') }}</a>
                    </li>
                </ul>
            </li>
            @endif

            @usercan('manage-settings.read')
            <li class="{{ Request::routeIs('business.manage-settings.index', 'business.currencies.index', 'business.currencies.create', 'business.currencies.edit', 'business.notifications.index','business.settings.index', 'business.sms-gateway-settings.index') ? 'active' : '' }}">
                <a href="{{ route('business.manage-settings.index') }}" class="active">
                    <span class="sidebar-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 6.24997C7.93205 6.24997 6.25005 7.93197 6.25005 9.99997C6.25005 12.068 7.93205 13.75 10 13.75C12.068 13.75 13.75 12.068 13.75 9.99997C13.75 7.93197 12.068 6.24997 10 6.24997ZM10 12.25C8.75905 12.25 7.75005 11.241 7.75005 9.99997C7.75005 8.75897 8.75905 7.74997 10 7.74997C11.241 7.74997 12.25 8.75897 12.25 9.99997C12.25 11.241 11.241 12.25 10 12.25ZM19.2081 11.953C18.5141 11.551 18.082 10.803 18.081 9.99997C18.08 9.19897 18.5091 8.45198 19.2121 8.04498C19.7271 7.74598 19.9031 7.08296 19.6051 6.56696L17.9331 3.68097C17.6351 3.16597 16.972 2.98898 16.456 3.28598C15.757 3.68898 14.8881 3.68898 14.1871 3.28198C13.4961 2.88098 13.0661 2.13598 13.0661 1.33698C13.0661 0.737975 12.578 0.250977 11.979 0.250977H8.02403C7.42403 0.250977 6.93706 0.737975 6.93706 1.33698C6.93706 2.13598 6.50704 2.88097 5.81404 3.28397C5.11504 3.68897 4.24705 3.68996 3.54805 3.28696C3.03105 2.98896 2.36906 3.16698 2.07106 3.68198L0.397049 6.57098C0.0990486 7.08598 0.276035 7.74796 0.796035 8.04996C1.48904 8.45096 1.92105 9.19796 1.92305 9.99896C1.92505 10.801 1.49504 11.55 0.793045 11.957C0.543045 12.102 0.363047 12.335 0.289047 12.615C0.215047 12.894 0.253056 13.185 0.398056 13.436L2.06905 16.32C2.36705 16.836 3.03005 17.015 3.54805 16.716C4.24705 16.313 5.11405 16.314 5.80305 16.713L5.80504 16.714C5.80804 16.716 5.81105 16.718 5.81505 16.72C6.50605 17.121 6.93504 17.866 6.93404 18.666C6.93404 19.265 7.42103 19.752 8.02003 19.752H11.979C12.578 19.752 13.065 19.265 13.065 18.667C13.065 17.867 13.495 17.122 14.189 16.719C14.887 16.314 15.755 16.312 16.455 16.716C16.971 17.014 17.6331 16.837 17.9321 16.322L19.606 13.433C19.903 12.916 19.7261 12.253 19.2081 11.953ZM16.831 15.227C15.741 14.752 14.476 14.817 13.434 15.42C12.401 16.019 11.7191 17.078 11.5871 18.25H8.41005C8.28005 17.078 7.59603 16.017 6.56303 15.419C5.52303 14.816 4.25605 14.752 3.16905 15.227L1.89305 13.024C2.84805 12.321 3.42504 11.193 3.42104 9.99298C3.41804 8.80098 2.84204 7.68097 1.89204 6.97797L3.16905 4.77396C4.25705 5.24796 5.52405 5.18396 6.56605 4.57996C7.59805 3.98196 8.28003 2.92198 8.41203 1.75098H11.5871C11.7181 2.92298 12.4011 3.98197 13.4361 4.58197C14.475 5.18497 15.742 5.24896 16.831 4.77496L18.108 6.97797C17.155 7.67997 16.579 8.80597 16.581 10.004C16.582 11.198 17.1581 12.32 18.1091 13.025L16.831 15.227Z" fill="white" />
                        </svg>
                    </span>
                    {{ __('Settings') }}
                </a>
            </li>
            @endusercan

            @usercan('download-apk.read')
            <li>
                <a href="{{ get_option('general')['app_link'] ?? '' }}" target="_blank" class="active">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 640 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                            <path
                                d="M537.6 226.6c4.1-10.7 6.4-22.4 6.4-34.6 0-53-43-96-96-96-19.7 0-38.1 6-53.3 16.2C367 64.2 315.3 32 256 32c-88.4 0-160 71.6-160 160 0 2.7 .1 5.4 .2 8.1C40.2 219.8 0 273.2 0 336c0 79.5 64.5 144 144 144h368c70.7 0 128-57.3 128-128 0-61.9-44-113.6-102.4-125.4zm-132.9 88.7L299.3 420.7c-6.2 6.2-16.4 6.2-22.6 0L171.3 315.3c-10.1-10.1-2.9-27.3 11.3-27.3H248V176c0-8.8 7.2-16 16-16h48c8.8 0 16 7.2 16 16v112h65.4c14.2 0 21.4 17.2 11.3 27.3z" />
                        </svg>
                    </span>
                    {{ __('Download Apk') }}
                </a>
            </li>
            @endusercan

            @usercan('subscriptions.read')
            <li>
                <div class="sub-plan">
                    <img src="{{ asset('assets/images/sidebar/plan-icon.svg') }}">
                </div>
            </li>
            @endusercan

            @usercan('subscriptions.read')
            <li>
                <div class="lg-sub-plan">
                    <div id="sidebar_plan" class=" sidebar-free-plan d-flex align-items-center justify-content-between p-3 flex-column">
                        <div class="text-center">
                            @if (plan_data() ?? false)

                                <h3>
                                    {{ plan_data()['plan']['subscriptionName'] ?? '' }}
                                </h3>
                                <h5>
                                    {{ __('Expired') }}: {{ formatted_date(plan_data()['will_expire'] ?? '') }}
                                </h5>
                                @else
                                <h3>{{ __('No Active Plan') }}</h3>
                                <h5>{{ __('Please subscribe to a plan') }}</h5>
                            @endif

                        </div>
                        <a href="{{ route('business.subscriptions.index') }}" class="btn upgrate-btn fw-bold">{{ __('Upgrade Now') }}</a>
                    </div>
                </div>
            </li>
            @endusercan

        </ul>
    </div>
</nav>
