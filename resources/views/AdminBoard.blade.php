<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no" />
  <title>Multi-Branch VCMS Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="/css/toast.css" rel="stylesheet" />
  <script src="/js/list-filter-new.js"></script>
  
  <style>
    html {
      height: 100%;
      overflow-x: hidden;
    }
    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow-x: hidden;
    }

    ::-webkit-scrollbar {
      width: 6px;
    }

    ::-webkit-scrollbar-track {
      background: #f1f5f9;
    }

    ::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 3px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }

    .glass-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .smooth-transition {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .gradient-bg {
      background: linear-gradient(135deg, #ff8c42 20%, #875e0cff 50%, #ec7c13ff 100%);
    }

    .hover-lift {
      transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .hover-lift:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .modern-btn {
      position: relative;
      overflow: hidden;
    }

    .modern-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s;
    }

    .modern-btn:hover::before {
      left: 100%;
    }

    .notification-badge {
      position: absolute;
      top: -2px;
      right: -2px;
      background: #ef4444;
      color: white;
      border-radius: 50%;
      width: 16px;
      height: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      font-weight: 600;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }

    .modern-dropdown {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(0, 0, 0, 0.1);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .sidebar-item {
      position: relative;
      overflow: hidden;
    }

    .sidebar-item::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 4px;
      background: #ff8c42;
      transform: scaleY(0);
      transition: transform 0.3s ease;
    }

    .sidebar-item.active::before,
    .sidebar-item:hover::before {
      transform: scaleY(1);
    }

    .sidebar-item.active {
      background: linear-gradient(90deg, rgba(246, 207, 68, 0.2), transparent);
    }

    .modern-search {
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(255, 255, 255, 0.3);
      backdrop-filter: blur(10px);
    }

    .modern-search:focus {
      background: rgba(255, 255, 255, 1);
      border-color: rgba(202, 141, 10, 0.5);
      outline: none;
      box-shadow: 0 0 0 3px rgba(15, 126, 160, 0.1);
    }

    .line-clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    @keyframes bellShake {
      0%, 100% { transform: rotate(0deg); }
      10%, 30%, 50%, 70%, 90% { transform: rotate(-10deg); }
      20%, 40%, 60%, 80% { transform: rotate(10deg); }
    }

    .fa-bell:hover {
      animation: bellShake 0.5s ease-in-out;
    }

    #notificationDropdown a:hover {
      transform: translateX(2px);
    }

    /* Branch indicator badge */
    .branch-badge {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
  </style>
</head>

<body class="h-screen flex flex-col bg-gradient-to-br from-gray-50 to-gray-100">
  <!-- HEADER -->
  <header class="flex items-center h-16 md:h-17 gradient-bg text-white shadow-xl relative z-50">
    <!-- Logo Section -->
    <div class="h-full flex items-center w-16 md:w-64 shrink-0" style="background-color: #ff8c42;">
      <img src="{{ asset('images/header5.png') }}" class="h-14 md:h-16 object-contain w-full px-2 md:px-0" alt="Logo" />
    </div>

    @if(auth()->user()->user_role === 'superadmin')
      <!-- Branch Selector -->
      <div class="flex-shrink-0 relative ml-4">
        <button id="branchDropdownBtn"
          class="flex items-center gap-2 px-4 py-2 rounded-lg hover-lift modern-btn bg-white/10 backdrop-blur-sm hover:bg-white/20 smooth-transition">
          <i class="fas fa-code-branch text-lg"></i>
          <span class="hidden md:inline font-medium">
            @if(session('branch_mode') === 'active')
              {{ session('active_branch_name') }}
            @else
              All Branches
            @endif
            <i class="fas fa-chevron-down ml-2 text-sm"></i>
          </span>
        </button>

        <!-- Branch Dropdown -->
        <div id="branchDropdownMenu" class="hidden absolute mt-3 modern-dropdown rounded-xl w-56 z-50 overflow-hidden">
          <div class="py-2">
            @if(session('branch_mode') === 'active')
              <a href="{{ route('branch.clear') }}"
                class="block px-4 py-3 text-sm font-medium text-gray-700 hover:bg-[#ff8c42] hover:text-white smooth-transition border-l-4 border-transparent hover:border-[#875e0cff]">
                <i class="fas fa-globe mr-3 text-xs opacity-70"></i>
                View All Branches
              </a>
              <hr class="my-2 border-gray-200">
            @endif
            
            @foreach($branches as $branch)
              <a href="{{ route('branch.switch', ['id' => $branch->branch_id]) }}"
                class="branch-link block px-4 py-3 text-sm font-medium text-gray-700 hover:bg-[#ff8c42] hover:text-white smooth-transition border-l-4 border-transparent hover:border-[#875e0cff] {{ session('active_branch_id') == $branch->branch_id ? 'bg-green-50 border-l-4 !border-green-500' : '' }}">
                <i class="fas fa-building mr-3 text-xs opacity-70"></i>
                {{ $branch->branch_name }}
                @if(session('active_branch_id') == $branch->branch_id)
                  <i class="fas fa-check text-green-600 float-right mt-1"></i>
                @endif
              </a>
            @endforeach
          </div>
        </div>
      </div>

      <!-- Active Branch Indicator -->
      @if(session('branch_mode') === 'active')
        <div class="ml-3 flex items-center">
          <i class="fas fa-circle text-green-400 text-xs animate-pulse-glow"></i>
        </div>
      @endif
    @endif

    <!-- Global Search (Dashboard only) or layout spacer -->
    @if (Route::currentRouteName() === 'dashboard-index')
      <form method="GET" action="{{ route('global.search') }}" class="flex-1 mx-6 relative">
        <div class="relative group">
          <input type="text" name="search" value="{{ request('search') }}"
            class="w-full h-11 rounded-xl modern-search px-4 pr-12 text-sm text-gray-700 placeholder:text-gray-400 smooth-transition"
            placeholder="Search pets, owners, appointments, products, services..." />
          <button type="submit"
            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-[#0f7ea0] smooth-transition">
            <i class="fas fa-search text-lg"></i>
          </button>
        </div>
      </form>
    @else
      <div class="flex-1 mx-6"></div>
    @endif

    <!-- Notification Bell -->
    <div class="relative mr-4">
      <button
        class="flex items-center justify-center w-11 h-11 rounded-xl bg-white/10 backdrop-blur-sm hover:bg-white/20 smooth-transition hover-lift modern-btn relative"
        onclick="toggleNotificationDropdown()">
        <i class="fas fa-bell text-lg"></i>
        @php
          $notificationService = app(\App\Services\NotificationService::class);
          $unreadCount = $notificationService->getUnreadCount(auth()->user());
        @endphp
        @if($unreadCount > 0)
          <span class="notification-badge">{{ $unreadCount }}</span>
        @endif
      </button>

      <!-- Notification Dropdown -->
      <div id="notificationDropdown" class="hidden absolute right-0 mt-3 w-96 modern-dropdown rounded-xl z-50 overflow-hidden shadow-2xl">
        
        <!-- Header -->
        <div class="px-4 py-3 bg-gradient-to-r from-[#ff8c42] to-[#875e0cff] text-white flex items-center justify-between">
          <div>
            <h3 class="font-semibold text-sm">Notifications</h3>
            <p class="text-xs opacity-90">{{ $unreadCount }} unread</p>
          </div>
          @if($unreadCount > 0)
            <button onclick="markAllAsRead()" 
                    class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1 rounded-lg transition-all">
              Mark all read
            </button>
          @endif
        </div>
        
        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
          @php
            $notifications = $notificationService->getNotifications(auth()->user());
          @endphp
          
          @forelse($notifications as $notification)
            <div onclick="markAsReadAndRedirect('{{ $notification['id'] }}', '{{ $notification['route'] }}')"
               class="block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 smooth-transition cursor-pointer {{ !$notification['is_read'] ? 'bg-blue-50' : '' }}">
              <div class="flex items-start gap-3">
                <!-- Icon -->
                <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0
                            {{ $notification['color'] === 'blue' ? 'bg-blue-100' : '' }}
                            {{ $notification['color'] === 'green' ? 'bg-green-100' : '' }}
                            {{ $notification['color'] === 'red' ? 'bg-red-100' : '' }}
                            {{ $notification['color'] === 'orange' ? 'bg-orange-100' : '' }}
                            {{ $notification['color'] === 'yellow' ? 'bg-yellow-100' : '' }}
                            {{ $notification['color'] === 'purple' ? 'bg-purple-100' : '' }}">
                  <i class="fas {{ $notification['icon'] }}
                            {{ $notification['color'] === 'blue' ? 'text-blue-500' : '' }}
                            {{ $notification['color'] === 'green' ? 'text-green-500' : '' }}
                            {{ $notification['color'] === 'red' ? 'text-red-500' : '' }}
                            {{ $notification['color'] === 'orange' ? 'text-orange-500' : '' }}
                            {{ $notification['color'] === 'yellow' ? 'text-yellow-600' : '' }}
                            {{ $notification['color'] === 'purple' ? 'text-purple-500' : '' }}"></i>
                </div>
                
                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-sm text-gray-900">{{ $notification['title'] }}</p>
                  <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ $notification['message'] }}</p>
                  <p class="text-xs text-gray-400 mt-1">
                    {{ \Carbon\Carbon::parse($notification['timestamp'])->diffForHumans() }}
                  </p>
                </div>
                
                <!-- Unread Indicator -->
                @if(!$notification['is_read'])
                  <div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2"></div>
                @endif
              </div>
            </div>
          @empty
            <div class="px-4 py-12 text-center text-gray-500">
              <i class="fas fa-bell-slash text-3xl mb-3 opacity-50"></i>
              <p class="text-sm font-medium">No notifications</p>
              <p class="text-xs text-gray-400 mt-1">You're all caught up!</p>
            </div>
          @endforelse
        </div>
        
        <!-- Footer (only show if there are notifications) -->
        @if(count($notifications) > 0)
          <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 text-center">
            <button onclick="refreshNotifications()" 
                    class="text-xs text-blue-600 hover:text-blue-700 font-medium">
              <i class="fas fa-sync-alt mr-1"></i> Refresh
            </button>
          </div>
        @endif
      </div>
    </div>


    <!-- POS Button (visible to receptionist and super admin in branch mode) -->
@auth
  @php
    $userRole = strtolower(trim(auth()->user()->user_role));
    $branchMode = session('branch_mode') === 'active';
  @endphp
  @if($userRole === 'receptionist' || ($userRole === 'superadmin' && $branchMode))
    <a href="{{ route('pos') }}" class="mr-4">
      <button
        class="bg-gradient-to-r from-[#8bc34a] to-[#7cb342] text-white font-semibold px-6 py-2.5 rounded-xl hover-lift modern-btn shadow-lg hover:shadow-xl smooth-transition">
        <i class="fas fa-cash-register mr-2"></i>
        <span class="hidden sm:inline">POS</span>
      </button>
    </a>
  @endif
@endauth


    <!-- User Dropdown -->
    <div class="relative mr-4">
      <button
        class="flex items-center gap-3 px-4 py-2 rounded-xl bg-white/10 backdrop-blur-sm hover:bg-white/20 smooth-transition hover-lift modern-btn"
        onclick="toggleUserDropdown()">
        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
          <i class="fas fa-user text-sm"></i>
        </div>
        <div class="hidden md:block text-left">
          <p class="text-xs font-medium">
            @auth
              {{ auth()->user()->user_role === 'superadmin' ? 'Super Admin' : ucwords(auth()->user()->user_role) }}
            @endauth
          </p>
        </div>
        <i class="fas fa-chevron-down text-xs opacity-70"></i>
      </button>

      <div id="userDropdown" class="hidden absolute right-0 mt-3 w-48 modern-dropdown rounded-xl z-50 overflow-hidden">
        <div class="py-2">
          <a href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-red-50 hover:text-red-600 smooth-transition">
            <i class="fas fa-sign-out-alt"></i>
            Logout
          </a>
        </div>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
          @csrf
        </form>
      </div>
    </div>
  </header>

  <!-- SIDEBAR + MAIN WRAPPER -->
  <div class="flex flex-1 overflow-hidden">
    <!-- SIDEBAR -->
    <aside class="bg-gradient-to-b from-slate-800 to-slate-900 w-16 md:w-64 flex flex-col shadow-2xl relative">
      <!-- Navigation -->
      <nav class="flex-1 py-6">
        <ul class="space-y-1 px-3">
          @php
            $userRole = auth()->user()->user_role ?? '';
            $normalizedRole = strtolower(trim($userRole));
            $isSuperAdmin = $normalizedRole === 'superadmin';
            $branchMode = session('branch_mode') === 'active';
            
            // Super Admin menu (when NOT in branch mode)
            $superAdminGlobalMenu = [
              ['route' => 'dashboard-index', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
              ['route' => 'branch-management.index', 'icon' => 'fa-building', 'label' => 'Branch Management'],
              ['route' => 'prodservequip.index', 'icon' => 'fa-boxes', 'label' => 'Inventory Management'],
              ['route' => 'report.index', 'icon' => 'fa-chart-bar', 'label' => 'Reports'],
            ];
            
            // Super Admin menu (when IN branch mode - gets branch-specific features)
            $superAdminBranchMenu = [
              ['route' => 'dashboard-index', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
              ['route' => 'prodservequip.index', 'icon' => 'fa-boxes', 'label' => 'Inventory Management'],
              ['route' => 'pet-management.index', 'icon' => 'fa-paw', 'label' => 'Pet Management'],
              ['route' => 'medical.index', 'icon' => 'fa-stethoscope', 'label' => 'Visit & Service Management'],
              ['route' => 'care-continuity.index', 'icon' => 'fa-heartbeat', 'label' => 'Care Continuity Management'],
              ['route' => 'sales.index', 'icon' => 'fa-cash-register', 'label' => 'Sales Management'],
              ['route' => 'branch-reports.index', 'icon' => 'fa-chart-line', 'label' => 'Branch Reports'],
            ];
            
            // Other role menus
            $menuItems = [
              'veterinarian' => [
                ['route' => 'dashboard-index', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
                ['route' => 'pet-management.index', 'icon' => 'fa-paw', 'label' => 'Pet Management'],
                ['route' => 'medical.index', 'icon' => 'fa-stethoscope', 'label' => 'Visit & Service Management'],
                ['route' => 'care-continuity.index', 'icon' => 'fa-heartbeat', 'label' => 'Care Continuity Management'],
                ['route' => 'branch-reports.index', 'icon' => 'fa-chart-line', 'label' => 'Branch Reports'],
              ],
              'receptionist' => [
                ['route' => 'dashboard-index', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
                ['route' => 'prodservequip.index', 'icon' => 'fa-boxes', 'label' => 'Inventory Management'],
                ['route' => 'pet-management.index', 'icon' => 'fa-paw', 'label' => 'Pet Management'],
                ['route' => 'medical.index', 'icon' => 'fa-stethoscope', 'label' => 'Visit & Service Management'],
                ['route' => 'care-continuity.index', 'icon' => 'fa-heartbeat', 'label' => 'Care Continuity Management'],
                ['route' => 'sales.index', 'icon' => 'fa-cash-register', 'label' => 'Sales Management'],
                ['route' => 'branch-reports.index', 'icon' => 'fa-chart-line', 'label' => 'Branch Reports'],
              ],
            ];
            
            // Determine which menu to show
            if ($isSuperAdmin) {
              $currentMenuItems = $branchMode ? $superAdminBranchMenu : $superAdminGlobalMenu;
            } else {
              $currentMenuItems = $menuItems[$normalizedRole] ?? [];
            }
          @endphp

          @foreach($currentMenuItems as $item)
            <li class="sidebar-item {{ Route::currentRouteName() == $item['route'] ? 'active' : '' }}">
              <a href="{{ route($item['route']) }}"
                class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#875e0cff] smooth-transition">
                  <i class="fas {{ $item['icon'] }} text-lg"></i>
                </div>
                <span class="hidden md:inline font-medium">{{ $item['label'] }}</span>
              </a>
            </li>
          @endforeach

          @if(empty($currentMenuItems))
            <li class="px-4 py-3 text-white/60 text-sm">
              <i class="fas fa-exclamation-triangle mr-2"></i>
              <div>No menu items available.</div>
            </li>
          @endif
        </ul>
      </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto bg-gradient-to-br from-gray-50 to-gray-100 relative">
      @yield('content')
    </main>
  </div>

  <!-- Footer -->
  <footer class="bg-gradient-to-l from-slate-800 to-slate-900 text-gray-300 text-sm py-3 px-6 w-full text-center">
    &copy; {{ date('Y') }} TEAM-ORIENTED. All rights reserved.
  </footer>

  <script>
    // Notification functions
    window.notifications = @json($notifications ?? []);

    function markAsReadAndRedirect(index, link) {
        if(window.notifications[index]) {
            window.notifications[index].is_read = true;
            updateNotificationBadge();

            // Send AJAX to mark as read in backend
            fetch('{{ route("notifications.markAllRead") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ id: window.notifications[index].id })
            }).catch(err => console.error('Error marking notification as read:', err));

            // Redirect to the link
            if(link) {
                window.location.href = link;
            }
        }
    }

    function updateNotificationBadge() {
        const count = window.notifications.filter(n => !n.is_read).length;
        const badge = document.querySelector('.notification-badge');
        if(badge) {
            if(count > 0) {
                badge.textContent = count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    function markAllAsRead() {
        fetch('{{ route("notifications.markAllRead") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        }).then(() => {
            location.reload();
        }).catch(err => {
            console.error('Error marking all as read:', err);
            alert('Failed to mark notifications as read');
        });
    }

    function refreshNotifications() {
        console.log('Refreshing notifications...');
        location.reload();
    }

    // Initialize notification badge on page load
    document.addEventListener('DOMContentLoaded', () => {
        updateNotificationBadge();
    });

    // Branch dropdown toggle
    document.getElementById('branchDropdownBtn')?.addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('branchDropdownMenu').classList.toggle('hidden');
    });

    // User dropdown toggle
    function toggleUserDropdown() {
      document.getElementById('userDropdown').classList.toggle('hidden');
    }

    // Notification dropdown toggle
    function toggleNotificationDropdown() {
      const dropdown = document.getElementById('notificationDropdown');
      dropdown.classList.toggle('hidden');
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
      const branchDropdown = document.getElementById('branchDropdownMenu');
      const userDropdown = document.getElementById('userDropdown');
      const notificationDropdown = document.getElementById('notificationDropdown');
      
      if (branchDropdown && !e.target.closest('#branchDropdownBtn') && !e.target.closest('#branchDropdownMenu')) {
        branchDropdown.classList.add('hidden');
      }
      
      if (userDropdown && !e.target.closest('[onclick="toggleUserDropdown()"]') && !userDropdown.contains(e.target)) {
        userDropdown.classList.add('hidden');
      }
      
      if (notificationDropdown && !e.target.closest('[onclick="toggleNotificationDropdown()"]') && !notificationDropdown.contains(e.target)) {
        notificationDropdown.classList.add('hidden');
      }
    });

    // Smooth scroll
    document.documentElement.style.scrollBehavior = 'smooth';
  </script>
@stack('scripts')
</body>
</html>