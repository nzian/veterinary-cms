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
      background: linear-gradient(135deg, #0f7ea0 0%, #0c6b87 50%, #0a5a73 100%);
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
      background: #f846a4;
      transform: scaleY(0);
      transition: transform 0.3s ease;
    }

    .sidebar-item.active::before,
    .sidebar-item:hover::before {
      transform: scaleY(1);
    }

    .sidebar-item.active {
      background: linear-gradient(90deg, rgba(246, 68, 213, 0.2), transparent);
    }

    /* Search input modern styling */
    .modern-search {
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(255, 255, 255, 0.3);
      backdrop-filter: blur(10px);
    }

    .modern-search:focus {
      background: rgba(255, 255, 255, 1);
      border-color: rgba(15, 126, 160, 0.5);
      outline: none;
      box-shadow: 0 0 0 3px rgba(15, 126, 160, 0.1);
    }
  </style>
</head>

<body class="h-screen flex flex-col bg-gradient-to-br from-gray-50 to-gray-100">
  <!-- HEADER -->
  <header class="flex items-center h-16 gradient-bg text-white shadow-xl relative z-50">

    <!-- Logo Section -->
    <div class="h-full flex items-centered
     bg-white w-10
      md:w-64 shrink-0">
      <img src="{{ asset('images/header1.png') }}" class="h-15
       md:h-13
       object-contain w-full" alt="Logo" />
    </div>

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
              class="branch-link block px-4 py-3 text-sm font-medium text-gray-700 hover:bg-[#0f7ea0] hover:text-white smooth-transition border-l-4 border-transparent hover:border-[#0f7ea0]">
              <i class="fas fa-building mr-3 text-xs opacity-70"></i>
              {{ $branch->branch_name }}
            </a>
          @endforeach
        </div>
      </div>
    </div>

    <!-- Search Section -->
    <form method="GET" action="{{ route('pets-index') }}" class="flex-1 mx-6 relative">
      <div class="relative group">
        <input type="text" name="search" value="{{ request('search') }}"
          class="w-full h-11 rounded-xl modern-search px-4 pr-12 text-sm text-gray-700 placeholder:text-gray-400 smooth-transition"
          placeholder="Search pets, owners, appointments..." />
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
        @if(!empty($lowStockItems))
          <span class="notification-badge">{{ count($lowStockItems) }}</span>
        @endif
      </button>

      <div id="notificationDropdown"
        class="hidden absolute right-0 mt-3 w-80 modern-dropdown rounded-xl z-50 overflow-hidden">
        <div class="px-4 py-3 bg-gradient-to-r from-[#0f7ea0] to-[#0c6b87] text-white">
          <h3 class="font-semibold text-sm">Notifications</h3>
          <p class="text-xs opacity-90">Stock alerts and updates</p>
        </div>
        <div class="max-h-64 overflow-y-auto">
          @forelse($lowStockItems as $item)
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
            <div class="px-4 py-8 text-center text-gray-500">
              <i class="fas fa-check-circle text-2xl mb-2"></i>
              <p class="text-sm">No alerts at the moment</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>

    <!-- POS Button -->
    <a href="{{ route('pos') }}" class="mr-4">
      <button
        class="bg-gradient-to-r from-[#8bc34a] to-[#7cb342] text-white font-semibold px-6 py-2.5 rounded-xl hover-lift modern-btn shadow-lg hover:shadow-xl smooth-transition">
        <i class="fas fa-cash-register mr-2"></i>
        <span class="hidden sm:inline">POS</span>
      </button>
    </a>

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
          <!-- Dashboard -->
          <li class="sidebar-item {{ Route::currentRouteName() == 'dashboard-index' ? 'active' : '' }}">
            <a href="{{ route('dashboard-index') }}"
              class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div
                class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-tachometer-alt text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Dashboard</span>
            </a>
          </li>

          <!-- Pet Owners 
          <li class="sidebar-item {{ Route::currentRouteName() == 'owners-index' ? 'active' : '' }}">
            <a href="{{ route('owners-index') }}" class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-user-alt text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Pet Owners</span>
            </a>
          </li>-->

          
          <!-- Branch Management -->
          <li class="sidebar-item {{ Route::currentRouteName() == 'branch-management.index' ? 'active' : '' }}">
            <a href="{{ route('branch-management.index') }}"
              class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div
                class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-building text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Branch Management</span>
            </a>
          </li>

           <!-- Inventory Management -->
          <li class="sidebar-item {{ Route::currentRouteName() == 'prodservequip.index' ? 'active' : '' }}">
            <a href="{{ route('prodservequip.index') }}"
              class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div
                class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-boxes text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Inventory Management</span>
            </a>
          </li>


          <!-- Pet & Owner Management -->
          <li class="sidebar-item {{ Route::currentRouteName() == 'pet-management.index' ? 'active' : '' }}">
            <a href="{{ route('pet-management.index') }}"
              class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div
                class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-paw text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Pet <br>
                Management</span>
            </a>
          </li>

          <!-- Pets 
          <li class="sidebar-item {{ Route::currentRouteName() == 'pets-index' ? 'active' : '' }}">
            <a href="{{ route('pets-index') }}" class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-paw text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Pets</span>
            </a>
          </li>-->

          <!-- Medical Management -->
          <li class="sidebar-item {{ Route::currentRouteName() == 'medical.index' ? 'active' : '' }}">
            <a href="{{ route('medical.index') }}"
              class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div
                class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-stethoscope text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Medical Management</span>
            </a>
          </li>

          <!-- Appointments 
          <li class="sidebar-item {{ Route::currentRouteName() == 'appointments-index' ? 'active' : '' }}">
            <a href="{{ route('appointments-index') }}" class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-calendar-alt text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Appointments</span>
            </a>
          </li>-->

          <!-- Prescriptions 
<li class="sidebar-item {{ Route::currentRouteName() == 'prescriptions.index' ? 'active' : '' }}">
  <a href="{{ route('prescriptions.index') }}" class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
    <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
      <i class="fas fa-notes-medical text-lg"></i>
    </div>
    <span class="hidden md:inline font-medium">Prescriptions</span>
  </a>
</li>-->


          <!-- Referrals 
          <li class="sidebar-item {{ Route::currentRouteName() == 'referral-index' ? 'active' : '' }}">
            <a href="{{ route('referral-index') }}" class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-share text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Referrals</span>
            </a>
          </li> -->

          <!-- Management (Branch & User) 
<li class="sidebar-item {{ in_array(Route::currentRouteName(), ['branch-user-management.index', 'branches.index', 'userManagement.index']) ? 'active' : '' }}">
    <a href="{{ route('branch-user-management.index') }}" class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
        <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
            <i class="fas fa-users-cog text-lg"></i>
        </div>
        <span class="hidden md:inline font-medium">Management</span>
    </a>
</li>-->

          <!-- Products 
          <li class="sidebar-item {{ Route::currentRouteName() == 'product-index' ? 'active' : '' }}">
            <a href="{{ route('product-index') }}" class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-box-open text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Products</span>
            </a>
          </li>-->

          <!-- Services 
          <li class="sidebar-item {{ Route::currentRouteName() == 'services-index' ? 'active' : '' }}">
            <a href="{{ route('services-index') }}" class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-hand-holding-medical text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Services</span>
            </a>
          </li>-->

          <!-- Sales Management -->
<li class="sidebar-item {{ Route::currentRouteName() == 'sales.index' ? 'active' : '' }}">
  <a href="{{ route('sales.index') }}"
    class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
    <div
      class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
      <i class="fas fa-cash-register text-lg"></i>
    </div>
    <span class="hidden md:inline font-medium">Sales Management</span>
  </a>
</li>

          <!-- Billings 
          <li class="sidebar-item {{ Route::currentRouteName() == 'billing-index' ? 'active' : '' }}">
            <a href="{{ route('billing-index') }}"
              class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div
                class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-receipt text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Billings</span>
            </a>
          </li>-->

          <!--
          <li class="sidebar-item {{ Route::currentRouteName() == 'order-index' ? 'active' : '' }}">
            <a href="{{ route('order-index') }}"
              class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div
                class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-clipboard-list text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Orders</span>
            </a>
          </li>-->

          <!-- Branches
          <li class="sidebar-item {{ Route::currentRouteName() == 'branches-index' ? 'active' : '' }}">
            <a href="{{ route('branches-index') }}" class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-code-branch text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Branches</span>
            </a>
          </li>-->

          <!-- Users
          <li class="sidebar-item {{ Route::currentRouteName() == 'userManagement.index' ? 'active' : '' }}">
            <a href="{{ route('userManagement.index') }}" class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-users text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Users</span>
            </a>
          </li>-->

          <!-- Reports -->
          <li class="sidebar-item {{ Route::currentRouteName() == 'report.index' ? 'active' : '' }}">
            <a href="{{ route('report.index') }}"
              class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group">
              <div
                class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
                <i class="fas fa-chart-bar text-lg"></i>
              </div>
              <span class="hidden md:inline font-medium">Reports</span>
            </a>
          </li>
        </ul>
      </nav>
      
      <div class="p-3 border-t border-white/10">
  <a href="{{ route('sms-settings.index') }}"
     class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group w-full hover:bg-white/10 {{ request()->routeIs('sms-settings.*') ? 'bg-white/10' : '' }}">
    <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition {{ request()->routeIs('sms-settings.*') ? 'bg-[#0f7ea0]' : '' }}">
      <i class="fas fa-cog text-lg"></i>
    </div>
    <span class="hidden md:inline font-medium">Settings</span>
  </a>
</div>

      <!-- Settings 
      <div class="p-3 border-t border-white/10">
        <button
          class="flex items-center gap-4 px-4 py-3 text-white hover:text-white smooth-transition rounded-xl group w-full hover:bg-white/10">
          <div
            class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-[#0f7ea0] smooth-transition">
            <i class="fas fa-cog text-lg"></i>
          </div>
          <span class="hidden md:inline font-medium">Settings</span>
        </button>
      </div>-->
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

  <script>
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