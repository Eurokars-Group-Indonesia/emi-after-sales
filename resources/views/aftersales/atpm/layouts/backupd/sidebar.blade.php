<div class="sidebar" id="sidebar">
    <h5 class="text-center py-3">AFTER SALES | ATPM</h5>
    
    <a href="{{ route('aftersales.atpm.home') }}" class="menu-link active">
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
            <a href="{{ route('aftersales.atpmatpm_user') }}" class="menu-link">User</a>
        </div>
        {{-- <a href="#" class="menu-link"><i class="bi bi-box"></i>Products</a> --}}
        <a href="{{ route('aftersales.atpm.model_other') }}" class="menu-link">Model Other</a>
        <a href="{{ route('atpm.utility.sync_index') }}" class="menu-link">Sync Monitoring</a>
    </div>
   


    <a href="#" class="menu-toggle" data-target="menu2">
        <span><i class="bi bi-bar-chart-fill"></i>Reports</span>
        <i class="bi bi-chevron-down arrow"></i>
    </a>
    <div id="menu2" class="submenu">
        <a href="{{ route('atpm.report.service-retention') }}" class="menu-link">Retention Report</a>
    </div>

    

</div>
