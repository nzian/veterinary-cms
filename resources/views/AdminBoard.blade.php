<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1" name="viewport" />
  <title>Multi-Branch VCMS Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      height: 100vh;
    }

    /* Custom scrollbar */
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

    /* Modern glassmorphism effect */
    .glass-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Smooth transitions */
    .smooth-transition {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Modern gradient background */
    .gradient-bg {
  background: linear-gradient(135deg,  #ff8c42 30%, #875e0cff 50%, #f88e28 100%);
}


    /* Hover effects */
    .hover-lift {
      transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .hover-lift:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    /* Modern button style */
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

    /* Custom notification badge */
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
      0% {
        transform: scale(1);
      }

      50% {
        transform: scale(1.1);
      }

      100% {
        transform: scale(1);
      }
    }

    /* Modern dropdown styling */
    .modern-dropdown {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(0, 0, 0, 0.1);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    /* Sidebar modern styling */
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

    /* Search input modern styling */
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
  </style>
</head>

<body class="h-screen flex flex-col bg-gradient-to-br from-gray-50 to-gray-100">
  <!-- HEADER -->
  <header class="flex items-center h-17 gradient-bg text-white shadow-xl relative z-50">

    <!-- Logo Section -->
   <div class="h-full flex items-center w-10 md:w-64 shrink-0" style="background-color: #ff8c42;">
  <img src="{{ asset('images/header5.png') }}" 
       class="h-15 md:h-16 object-contain w-full" 
       alt="Logo" />
</div>

{{-- Show only if user is super admin --}}
@if(auth()->user()->user_role === 'superadmin')
    <!-- Branch Selector -->
    <div class="flex-shrink-0 relative ml-4">
      <button id="branchDropdownBtn"
        class="flex items-center gap-2 px-4 py-2 rounded-lg hover-lift modern-btn bg-white/10 backdrop-blur-sm hover:bg-white/20 smooth-transition">
        <i class="fas fa-code-branch text-lg"></i>
        <span class="hidden md:inline font-medium">
          Branches <i class="fas fa-chevron-down ml-2 text-sm"></i>
        </span>
      </button>

      <!-- Branch Dropdown -->
      <div id="branchDropdownMenu" class="hidden absolute mt-3 modern-dropdown rounded-xl w-48 z-50 overflow-hidden">
        <div class="py-2">
          @foreach($branches as $branch)
            <a href="{{ route('branch.switch', ['id' => $branch->branch_id]) }}" data-branch="{{ $branch->branch_id }}"
              class="branch-link block px-4 py-3 text-sm font-medium text-gray-700 hover:bg-[#f846a4] hover:text-white smooth-transition border-l-4 border-transparent hover:border-[#0f7ea0]">
              <i class="fas fa-building mr-3 text-xs opacity-70"></i>
              {{ $branch->branch_name }}
            </a>
          @endforeach
        </div>
      </div>
    </div>
@endif


   <!-- Global Search Section -->
<form method="GET" action="{{ route('global.search') }}" class="flex-1 mx-6 relative">
  <div class="relative group">
    <input type="text" name="search" value="{{ request('search') }}"
      class="w-full h-11 rounded-xl modern-search px-4 pr-12 text-sm text-gray-700 placeholder:text-gray-400 smooth-transition"
      placeholder="Search pets, owners, appointments, products, services..." />
    <button type="submit"
      class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-[#0f7ea0] smooth-transition">
      <i class="fas fa-search text-lg"></i>
    </button>
    <div
      class="absolute inset-0 rounded-xl bg-gradient-to-r from-[#0f7ea0]/20 to-transparent opacity-0 group-hover:opacity-100 smooth-transition pointer-events-none">
    </div>
  </div>
</form>


   <!-- Notifications -->
<div class="relative mr-4">
  <button
    class="flex items-center justify-center w-11 h-11 rounded-xl bg-white/10 backdrop-blur-sm hover:bg-white/20 smooth-transition hover-lift modern-btn relative"
    onclick="toggleNotificationDropdown()">
    <i class="fas fa-bell text-lg"></i>
    @php
      $totalNotifications = ($unreadNotificationCount ?? 0) + count($lowStockItems ?? []);
    @endphp
    @if($totalNotifications > 0)
      <span class="notification-badge">{{ $totalNotifications }}</span>
    @endif
  </button>

  <div id="notificationDropdown"
    class="hidden absolute right-0 mt-3 w-96 modern-dropdown rounded-xl z-50 overflow-hidden">
    <div class="px-4 py-3 bg-gradient-to-r from-[#ff8c42] to-[#875e0cff] text-white">
      <h3 class="font-semibold text-sm">Notifications</h3>
      <p class="text-xs opacity-90">
        {{ $totalNotifications }} unread
      </p>
    </div>
    
    <div class="max-h-96 overflow-y-auto">
      {{-- System Notifications --}}
      @forelse($notifications ?? [] as $notification)
        <div class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 smooth-transition cursor-pointer {{ !$notification->is_read ? 'bg-blue-50' : '' }}"
             onclick="markAsRead('{{ $notification->id }}', '{{ $notification->type }}')">
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                        {{ $notification->type === 'appointment_arrived' ? 'bg-green-100' : '' }}
                        {{ $notification->type === 'user_login' ? 'bg-blue-100' : '' }}
                        {{ $notification->type === 'referral_received' ? 'bg-purple-100' : '' }}">
              <i class="fas {{ $notification->data['icon'] ?? 'fa-bell' }}
                        {{ $notification->type === 'appointment_arrived' ? 'text-green-500' : '' }}
                        {{ $notification->type === 'user_login' ? 'text-blue-500' : '' }}
                        {{ $notification->type === 'referral_received' ? 'text-purple-500' : '' }} text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-sm text-gray-900">{{ $notification->title }}</p>
              <p class="text-xs text-gray-600 mt-1">{{ $notification->message }}</p>
              <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
            </div>
            @if(!$notification->is_read)
              <div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2"></div>
            @endif
          </div>
        </div>
      @empty
      @endforelse
      
      {{-- Low Stock Alerts --}}
      @forelse($lowStockItems ?? [] as $item)
        <div class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 smooth-transition">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
              <i class="fas fa-exclamation-triangle text-red-500 text-sm"></i>
            </div>
            <div class="flex-1">
              <p class="font-medium text-sm text-gray-900">{{ $item->prod_name }}</p>
              <p class="text-xs text-gray-500">Only {{ $item->prod_stocks }} items left</p>
            </div>
          </div>
        </div>
      @empty
      @endforelse
      
      @if(count($notifications ?? []) === 0 && count($lowStockItems ?? []) === 0)
        <div class="px-4 py-8 text-center text-gray-500">
          <i class="fas fa-check-circle text-2xl mb-2"></i>
          <p class="text-sm">No notifications</p>
        </div>
      @endif
    </div>
    
    @if(count($notifications ?? []) > 0)
      <div class="px-4 py-2 bg-gray-50 border-t border-gray-100">
        <button onclick="markAllAsRead()" class="text-xs text-blue-600 hover:text-blue-700 font-medium">
          Mark all as read
        </button>
      </div>
    @endif
  </div>
</div>
    <!-- POS Button (only visible to receptionist) -->
@auth
  @if(strtolower(trim(auth()->user()->user_role)) === 'receptionist')
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
              {{ auth()->user()->user_role === 'Super Admin' ? 'Welcome Admin' : ucwords(auth()->user()->user_role) }}
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
      
      // Normalize role name (remove extra spaces, convert to lowercase for comparison)
      $normalizedRole = strtolower(trim($userRole));
      
      // Define menu items for each role (using actual database values)
      $menuItems = [
        'superadmin' => [
          ['route' => 'dashboard-index', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
          ['route' => 'branch-management.index', 'icon' => 'fa-building', 'label' => 'Branch Management'],
          ['route' => 'prodservequip.index', 'icon' => 'fa-boxes', 'label' => 'Inventory Management'],
          ['route' => 'pet-management.index', 'icon' => 'fa-paw', 'label' => 'Pet Management'],
          ['route' => 'medical.index', 'icon' => 'fa-stethoscope', 'label' => 'Medical Management'],
          ['route' => 'sales.index', 'icon' => 'fa-cash-register', 'label' => 'Sales Management'],
          ['route' => 'report.index', 'icon' => 'fa-chart-bar', 'label' => 'Reports'],
       
        ],
        'veterinarian' => [
          ['route' => 'dashboard-index', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
          ['route' => 'pet-management.index', 'icon' => 'fa-paw', 'label' => 'Pet Management'],
          ['route' => 'medical.index', 'icon' => 'fa-stethoscope', 'label' => 'Medical Management'],
          ['route' => 'branch-reports.index', 'icon' => 'fa-chart-line', 'label' => 'Branch Reports'],
        ],
        'receptionist' => [
          ['route' => 'dashboard-index', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
          ['route' => 'prodservequip.index', 'icon' => 'fa-boxes', 'label' => 'Inventory Management'],
          ['route' => 'pet-management.index', 'icon' => 'fa-paw', 'label' => 'Pet Management'],
          ['route' => 'medical.index', 'icon' => 'fa-stethoscope', 'label' => 'Medical Management'],
          ['route' => 'sales.index', 'icon' => 'fa-cash-register', 'label' => 'Sales Management'],
          ['route' => 'branch-reports.index', 'icon' => 'fa-chart-line', 'label' => 'Branch Reports'],
        ],
      ];
      
      // Get menu items for current user role
      $currentMenuItems = $menuItems[$normalizedRole] ?? [];
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
        <div>No menu items available for your role.</div>
        <div class="mt-2 text-xs bg-white/10 p-2 rounded">
          Debug: Your role is "<strong>{{ $userRole }}</strong>"<br>
          Normalized: "<strong>{{ $normalizedRole }}</strong>"
        </div>
      </li>
    @endif
  </ul>
</nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto bg-gradient-to-br from-gray-50 to-gray-100 relative">
      <!-- Main content container -->
      <div class="p-6 h-full">
        <!-- Content area with modern card styling -->
        <div>
          @yield('content')
        </div>
      </div>
    </main>
  </div>

<!-- Footer -->
<footer class="bg-gradient-to-l from-slate-800 to-slate-900 text-gray-300 text-sm py-3 px-6 w-full text-center">
    &copy; {{ date('Y') }} TEAM-ORIENTED. All rights reserved.
</footer>



  <script>
    function markAsRead(notificationId, type) {
  fetch(`/notifications/${notificationId}/read`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
  }).then(() => {
    if (type === 'appointment_arrived') {
      window.location.href = `/medical-management?active_tab=appointments`;
    } else if (type === 'referral_received') {
      window.location.href = `/medical-management?active_tab=referrals`;
    } else {
      location.reload();
    }
  });
}

function markAllAsRead() {
  fetch('/notifications/mark-all-read', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
  }).then(() => location.reload());
}
    document.addEventListener('DOMContentLoaded', function () {
      // Branch dropdown functionality
      const btn = document.getElementById('branchDropdownBtn');
      const menu = document.getElementById('branchDropdownMenu');

      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        menu.classList.toggle('hidden');
      });

      // Close dropdowns when clicking outside
      document.addEventListener('click', function (e) {
        if (!e.target.closest('#branchDropdownBtn')) {
          menu.classList.add('hidden');
        }
        if (!e.target.closest('#notificationDropdown') && !e.target.closest('[onclick="toggleNotificationDropdown()"]')) {
          document.getElementById('notificationDropdown').classList.add('hidden');
        }
        if (!e.target.closest('#userDropdown') && !e.target.closest('[onclick="toggleUserDropdown()"]')) {
          document.getElementById('userDropdown').classList.add('hidden');
        }
      });

      // Add loading states for navigation items
      const navLinks = document.querySelectorAll('nav a');
      navLinks.forEach(link => {
        link.addEventListener('click', function () {
          const icon = this.querySelector('i');
          const originalClass = icon.className;
          icon.className = 'fas fa-spinner fa-spin text-lg';

          setTimeout(() => {
            icon.className = originalClass;
          }, 500);
        });
      });
    });

    function toggleNotificationDropdown() {
      document.getElementById('notificationDropdown').classList.toggle('hidden');
    }

    function toggleUserDropdown() {
      document.getElementById('userDropdown').classList.toggle('hidden');
    }

    // Add smooth scroll behavior
    document.documentElement.style.scrollBehavior = 'smooth';
  </script>
</body>

</html>
