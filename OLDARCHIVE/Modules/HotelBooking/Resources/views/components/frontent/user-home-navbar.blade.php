<ul class="nav nav-tabs">
    <li class="nav-item">
        <a class="nav-link @if(request()->routeIs('tenant.user.dashboard.user.reservation')) active @endif" aria-current="page" href="{{route('tenant.user.dashboard.user.reservation')}}">All Reservation</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(request()->routeIs('tenant.user.dashboard.pending.reservation')) active @endif" href="{{route('tenant.user.dashboard.pending.reservation')}}">Pending Reservation</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(request()->routeIs('tenant.user.dashboard.approved.reservation')) active @endif" href="{{route('tenant.user.dashboard.approved.reservation')}}">Approved Reservation</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(request()->routeIs('tenant.user.dashboard.canceled.reservation')) active @endif" href="{{route('tenant.user.dashboard.canceled.reservation')}}">Canceled Reservation</a>
    </li>
</ul>
