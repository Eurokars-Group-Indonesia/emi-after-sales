<div class="sidebar" id="sidebar">
    <h5 class="text-center py-3">AFTER SALES</h5>
    
    <a href="{{ route('atpm.aftersales.home') }}" class="menu-link active">
        <span><i class="bi bi-house-fill"></i>Home</span>
    </a>


    <a href="#" class="menu-toggle" data-target="menu1">
        <span><i class="bi bi-building"></i>Administration</span>
        <i class="bi bi-chevron-down arrow"></i>
    </a>
    <div id="menu1" class="submenu">
        <a href="#" class="menu-toggle" data-target="menu1-1">
            <span><i class="bi bi-people"></i>ATPM</span>
            <i class="bi bi-chevron-down arrow"></i>
        </a>
        <div id="menu1-1" class="submenu">
            <a href="{{ route('atpm.aftersales.atpm_user') }}" class="menu-link">User</a>
        </div>
        {{-- <a href="#" class="menu-link"><i class="bi bi-box"></i>Products</a> --}}
        <a href="{{ route('atpm.aftersales.model_other') }}" class="menu-link">Model Other</a>
    </div>
    {{-- <div id="menu1" class="submenu">
        <a href="{{ route('atpm.report.service-retention') }}" class="menu-link"></a>
        <a href="{{ route('atpm.report.service-retention') }}" class="menu-link">Model Other</a>
        <a href="{{ route('atpm.aftersales.sync') }}" class="menu-link"><i class="bi bi-box"></i>Sync Monitoring</a>
    </div> --}}

{{-- 
    <a href="#" class="menu-toggle" data-target="menu1">
        <span><i class="bi bi-database"></i>Master Data</span>
        <i class="bi bi-chevron-down arrow"></i>
    </a>
    <div id="menu1" class="submenu">
        <a href="#" class="menu-toggle" data-target="menu1-1">
            <span><i class="bi bi-people"></i>Users</span>
            <i class="bi bi-chevron-down arrow"></i>
        </a>
        <div id="menu1-1" class="submenu">
            <a href="#" class="menu-link">List Users</a>
            <a href="#" class="menu-link">Add User</a>
        </div>
        <a href="#" class="menu-link"><i class="bi bi-box"></i>Products</a>
    </div>
 --}}


    <a href="#" class="menu-toggle" data-target="menu2">
        <span><i class="bi bi-bar-chart-fill"></i>Reports</span>
        <i class="bi bi-chevron-down arrow"></i>
    </a>
    <div id="menu2" class="submenu">
        <a href="{{ route('atpm.report.service-retention') }}" class="menu-link">Retention Report</a>
    </div>

    

    {{-- <a href="#" class="menu-toggle" data-target="menu1">
        <span><i class="bi bi-database"></i>Master Data</span>
        <i class="bi bi-chevron-down arrow"></i>
    </a>
    <div id="menu1" class="submenu">
        <a href="#" class="menu-toggle" data-target="menu1-1">
            <span><i class="bi bi-people"></i>Users</span>
            <i class="bi bi-chevron-down arrow"></i>
        </a>
        <div id="menu1-1" class="submenu">
            <a href="#" class="menu-link">List Users</a>
            <a href="#" class="menu-link">Add User</a>
        </div>
        <a href="#" class="menu-link"><i class="bi bi-box"></i>Products</a>
    </div> --}}

    {{-- <a href="#" class="menu-toggle" data-target="menu2">
        <span><i class="bi bi-bar-chart-fill"></i>Reports</span>
        <i class="bi bi-chevron-down arrow"></i>
    </a>
    <div id="menu2" class="submenu">
        
        <a href="{{ route('atpm.report.service-retention') }}" class="menu-link">Retention Report</a>

        <a href="#" class="menu-toggle" data-target="menu2-1">
            <span><i class="bi bi-cash"></i>Sales</span>
            <i class="bi bi-chevron-down arrow"></i>
        </a>
        <div id="menu2-1" class="submenu">
            <a href="#" class="menu-link">Daily</a>
            <a href="#" class="menu-link">Monthly</a>
        </div>
        <a href="#" class="menu-link"><i class="bi bi-graph-up"></i>Analytics</a>
    </div> --}}
</div>
