<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px"
    data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
    <!--begin::Logo-->
    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
        <!--begin::Logo image-->
        <a href="#">
            <img alt="Logo" src="{{ asset('metronic/demo1/assets/media/logos/logo-lrtj.png') }}"
                class="h-45px app-sidebar-logo-default" />
            {{-- <img alt="Logo" src="{{ asset('metronic/demo1/assets/media/logos/default-small.svg') }}" class="h-20px app-sidebar-logo-minimize" /> --}}
        </a>
        <!--end::Sidebar toggle-->
    </div>
    <!--end::Logo-->
    <!--begin::sidebar menu-->
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <!--begin::Menu wrapper-->
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
            <!--begin::Scroll wrapper-->
            <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3" data-kt-scroll="true"
                data-kt-scroll-activate="true" data-kt-scroll-height="auto"
                data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
                data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px"
                data-kt-scroll-save-state="true">
                <!--begin::Menu-->
                <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="#kt_app_sidebar_menu"
                    data-kt-menu="true" data-kt-menu-expand="false">
                    <!--begin:Menu item-->

                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="click"
                        class="menu-item {{ request()->segment(1) == 'dashboard' ? 'show here' : '' }} menu-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-home fs-2">
                                </i>
                            </span>
                            <span class="menu-title">Dashboard</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(1) == 'dashboard' && request()->segment(2) == 'monthly' ? 'active' : '' }} "
                                    href="{{ route('dashboard.monthly') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Monthly</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(1) == 'dashboard' && request()->segment(2) == 'yearly' ? 'active' : '' }} "
                                    href="{{ route('dashboard.yearly') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Yearly</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->

                    <!--end:Menu item-->
                    @canAction('MASTER_DATA','R')
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="click"
                        class="menu-item {{ request()->segment(1) == 'master-data' ? 'show here' : '' }} menu-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-category fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">Master Data</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(2) == 'master-company' ? 'active' : '' }}"
                                    href="{{ route('master.company') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Master Company</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(2) == 'master-location' ? 'active' : '' }}"
                                    href="{{ route('master.location') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Master Location</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(2) == 'master-uom' ? 'active' : '' }}"
                                    href="{{ route('master.uom') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Master UOM</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(2) == 'master-status' ? 'active' : '' }}"
                                    href="{{ route('master.status') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Master Status</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(2) == 'master-asset-class' ? 'active' : '' }}"
                                    href="{{ route('master.asset_class') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Master Asset Class</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(2) == 'master-division' ? 'active' : '' }}"
                                    href="{{ route('master.division') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Master Division</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(2) == 'master-user-code' ? 'active' : '' }}"
                                    href="{{ route('master.user_code') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Master User Code</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(2) == 'master-sumber' ? 'active' : '' }}"
                                    href="{{ route('master.sumber') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Master Sumber</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    @endcanAction
                    @canAction('USER_MGMT','R')
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="click"
                        class="menu-item  {{ request()->segment(1) == 'user-management' ? 'show here' : '' }} menu-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-user fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">User Management</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a href="{{ route('settings.users.index') }}"
                                    class="menu-link  {{ request()->segment(2) == 'users' ? 'active' : '' }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">List User</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a href="{{ route('settings.roles.index') }}"
                                    class="menu-link  {{ request()->segment(2) == 'roles' ? 'active' : '' }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Master Role</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    @endcanAction

                    @canAction('ASSETS','R')
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link {{ request()->segment(1) == 'label-printing' ? 'active' : '' }}"
                            href="{{ route('label.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-printer fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </span>
                            <span class="menu-title">Printing Label</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                    @endcanAction

                    @canAction('ASSETS','R')
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="click"
                        class="menu-item {{ request()->segment(1) == 'assets' ? 'show here' : '' }} menu-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-abstract-26 fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Assets</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(1) == 'assets' && request()->segment(2) != 'bulk-upload' ? 'active' : '' }} "
                                    href="{{ route('assets.index') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Assets</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            @canAction('ASSETS','C')
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(2) == 'bulk-upload' ? 'active' : '' }} "
                                    href="{{ route('assets.upload.bulk') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Bulk Upload</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            @endcanAction
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    @endcanAction

                    @canAction('DEPRECIATION','R')
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link  {{ request()->segment(1) == 'depreciation' ? 'active' : '' }}"
                            href="{{ route('depreciation.period.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-chart-line-down fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Depreciation</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                    @endcanAction

                    @php
                        $u = auth()->user();

                        $canAcq = $u && $u->hasAction('ACQUISITION', 'R');
                        $canMov = $u && $u->hasAction('MOVEMENT', 'R');
                        $canDisp = $u && $u->hasAction('DISPOSAL', 'R');
                        $canRet = $u && $u->hasAction('RETURN', 'R');
                        $canTfReq = $u && $u->hasAction('TRANSFER', 'R');

                        $showTransaction = $canAcq || $canMov || $canDisp || $canRet || $canTfReq;
                        $showTransfer = $canTfReq || $canMov;
                    @endphp

                    @if ($showTransaction)
                        <!--begin:Menu item-->
                        <div data-kt-menu-trigger="click"
                            class="menu-item {{ request()->segment(1) == 'transaction' ? 'show here' : '' }} menu-accordion">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-arrow-right-left fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Transaction</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <div class="menu-sub menu-sub-accordion">
                                @canAction('ACQUISITION','R')
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->segment(2) == 'acquisition' ? 'active' : '' }}"
                                        href="{{ route('transaction.acquisition.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Acquisition</span>
                                    </a>
                                </div>
                                @endcanAction

                                @if ($showTransfer)
                                    <div data-kt-menu-trigger="click"
                                        class="menu-item 
                                {{ request()->segment(1) == 'transaction' && (request()->segment(2) == 'movement' || request()->segment(2) == 'transfer-requests') ? 'show here' : '' }}
                                     menu-accordion">
                                        <!--begin:Menu link-->
                                        <span class="menu-link">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot"></span>
                                            </span>
                                            <span class="menu-title">Transfer Request</span>
                                            <span class="menu-arrow"></span>
                                        </span>
                                        <!--end:Menu link-->
                                        <!--begin:Menu sub-->
                                        <div class="menu-sub menu-sub-accordion">
                                            @canAction('MOVEMENT','R')
                                            <!--begin:Menu item-->
                                            <div class="menu-item">
                                                <!--begin:Menu link-->
                                                <a class="menu-link {{ request()->segment(2) == 'movement' ? 'active' : '' }}"
                                                    href="{{ route('transaction.transfer.index') }}">
                                                    <span class="menu-bullet">
                                                        <span class="bullet bullet-dot"></span>
                                                    </span>
                                                    <span class="menu-title">Movement</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            @endcanAction
                                            @canAction('TRANSFER','R')
                                            <!--begin:Menu item-->
                                            <div class="menu-item">
                                                <!--begin:Menu link-->
                                                <a class="menu-link {{ request()->segment(2) == 'transfer-requests' ? 'active' : '' }}"
                                                    href="{{ route('transaction.transfer-requests.index') }}">
                                                    <span class="menu-bullet">
                                                        <span class="bullet bullet-dot"></span>
                                                    </span>
                                                    <span class="menu-title">Transfer Value</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            @endcanAction
                                        </div>
                                        <!--end:Menu sub-->
                                    </div>
                                @endif

                                {{-- @canAction('TRANSFER','R')
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->segment(2) == 'transfer-requests' ? 'active' : '' }}"
                                        href="{{ route('transaction.transfer-requests.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Transfer Request</span>
                                    </a>
                                </div>
                                @endcanAction

                                @canAction('MOVEMENT','R')
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->segment(2) == 'movement' ? 'active' : '' }}"
                                        href="{{ route('transaction.transfer.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Movement</span>
                                    </a>
                                </div>
                                @endcanAction --}}

                                @canAction('DISPOSAL','R')
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->segment(2) == 'disposal' ? 'active' : '' }}"
                                        href="{{ route('transaction.disposal.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Disposal</span>
                                    </a>
                                </div>
                                @endcanAction

                                @canAction('RETURN','R')
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->segment(2) == 'return' ? 'active' : '' }}"
                                        href="{{ route('transaction.return.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Return</span>
                                    </a>
                                </div>
                                @endcanAction

                            </div>
                        </div>
                        <!--end:Menu item-->
                    @endif

                    @canAction('STOCK_OPN','R')
                    <div data-kt-menu-trigger="click"
                        class="menu-item {{ request()->segment(1) == 'stock-opname' ? 'show here' : '' }} menu-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-notepad-edit fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Stock Opname</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(1) == 'stock-opname' && request()->segment(2) == 'create-projects' ? 'active' : '' }} "
                                    href="{{ route('stockopname.assets.select.index') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Create Projects</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(1) == 'stock-opname' && request()->segment(2) == 'projects' ? 'active' : '' }} "
                                    href="{{ route('stockopname.asset_projects.index') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">List Projects</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link {{ request()->segment(1) == 'stock-opname' && request()->segment(2) == 'correction' ? 'active' : '' }} "
                                    href="{{ route('stockopname.index') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Correction</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    @endcanAction


                    @canAction('REPORTING','R')
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link {{ request()->segment(1) == 'reporting' ? 'active' : '' }}"
                            href="{{ route('reporting.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-document fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Reporting</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                    @endcanAction

                    @canAction('TRASH','R')
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link {{ request()->segment(1) == 'trash' ? 'active' : '' }}"
                            href="{{ route('trash.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-trash fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </span>
                            <span class="menu-title">Trash</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                    @endcanAction
                </div>
                <!--end::Menu-->
            </div>
            <!--end::Scroll wrapper-->
        </div>
        <!--end::Menu wrapper-->
    </div>
    <!--end::sidebar menu-->
    <!--begin::Footer-->
    <div class="app-sidebar-footer flex-column-auto pt-2 pb-6 px-6" id="kt_app_sidebar_footer">
        <a href="#"
            class="btn btn-flex flex-center btn-custom btn-danger overflow-hidden text-nowrap px-0 h-40px w-100"
            data-bs-dismiss-="click">
            <span class="btn-label">User Guide & Manuals</span>
            <i class="ki-duotone ki-document btn-icon fs-2 m-0">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
        </a>
    </div>
    <!--end::Footer-->
</div>
