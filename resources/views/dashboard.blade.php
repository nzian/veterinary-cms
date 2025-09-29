@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gray-50">
  <div class="max-w-7xl mx-auto p-6">

    {{-- Branch Header --}}
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-3xl font-bold text-[#0f7ea0]">{{ $branchName }}</h1>
        <p class="text-gray-600 mt-1">Dashboard Overview</p>
      </div>
      <div class="text-sm text-gray-500">
        {{ date('l, F j, Y') }}
      </div>
    </div>

    {{-- Key Metrics - Modern Cards (4 Only) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      @php
        $keyMetrics = [
          [
            'label' => 'Total Appointments', 
            'value' => $totalAppointments,
            'icon' => '📅',
            'color' => 'from-blue-500 to-blue-600',
            'change' => '+12%'
          ],
          [
            'label' => "Today's Appointments", 
            'value' => $todaysAppointments,
            'icon' => '🕒',
            'color' => 'from-emerald-500 to-emerald-600',
            'change' => '+8%'
          ],
          [
            'label' => 'Total Pet Owners', 
            'value' => $totalOwners,
            'icon' => '👥',
            'color' => 'from-purple-500 to-purple-600',
            'change' => '+5%'
          ],
          [
            'label' => 'Daily Revenue', 
            'value' => '₱' . number_format($dailySales, 2),
            'icon' => '💰',
            'color' => 'from-amber-500 to-amber-600',
            'change' => '+15%'
          ],
        ];
      @endphp

      @foreach ($keyMetrics as $metric)
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 group">
          <div class="absolute inset-0 bg-gradient-to-br {{ $metric['color'] }} opacity-0 group-hover:opacity-5 transition-opacity"></div>
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="text-2xl">{{ $metric['icon'] }}</div>
              <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded-full">
                {{ $metric['change'] }}
              </span>
            </div>
            <div class="space-y-1">
              <p class="text-sm font-medium text-gray-600">{{ $metric['label'] }}</p>
              <p class="text-2xl font-bold text-gray-900">{{ $metric['value'] }}</p>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Calendar Section - Now Full Width --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-8">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-900">Appointment Calendar</h2>
          <div class="flex items-center space-x-2">
            
            <button id="monthlyBtn" class="px-3 py-1.5 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-[#0f7ea0 transition-colors">Monthly</button>
            <button id="weeklyBtn" class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Weekly</button>
            <button id="todayBtn" class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Today</button>
            <div class="flex space-x-1 ml-2">
              <button id="prevBtn" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">←</button>
              <button id="nextBtn" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">→</button>
            </div>
          </div>
        </div>
        <div id="calendar">
          <div id="calendarHeader" class="text-lg font-semibold text-gray-900 text-center mb-4"></div>
          <div class="overflow-hidden rounded-lg border border-gray-200">
            
            <table class="w-full">
              <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                  @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                    <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center">{{ $day }}</th>
                  @endforeach
                </tr>
              </thead>
              <tbody id="calendarBody" class="divide-y divide-gray-200"></tbody>
            </table>
          </div>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-4 text-sm">
  <div class="flex items-center gap-2">
    <span class="inline-block w-4 h-4 bg-green-100 border border-green-300 rounded"></span> Arrived / Completed
  </div>
  <div class="flex items-center gap-2">
    <span class="inline-block w-4 h-4 bg-yellow-100 border border-yellow-300 rounded"></span> Pending / Approved
  </div>
  <div class="flex items-center gap-2">
    <span class="inline-block w-4 h-4 bg-orange-100 border border-orange-300 rounded"></span> Rescheduled
  </div>
  <div class="flex items-center gap-2">
    <span class="inline-block w-4 h-4 bg-red-100 border border-red-300 rounded"></span> Missed (Did not arrive)
  </div>
</div>

      </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      {{-- Daily Revenue Chart --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold text-gray-900">Daily Revenue</h3>
          <span class="text-sm text-gray-500">Last 7 days</span>
        </div>
        <div class="h-64">
          <canvas id="dailyOrdersChart"></canvas>
        </div>
      </div>
      
      {{-- Monthly Overview --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold text-gray-900">Monthly Overview</h3>
          <span class="text-sm text-gray-500">This year</span>
        </div>
        <div class="h-64">
          <canvas id="monthlyOrdersChart"></canvas>
        </div>
      </div>
    </div>

    {{-- Recent Activity Tables --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      {{-- Recent Appointments --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Recent Appointments</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              @foreach($recentAppointments->take(5) as $appointment)
                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4 text-sm text-gray-900">{{ $appointment->appoint_date }}</td>
                  <td class="px-6 py-4 text-sm text-gray-600">{{ $appointment->pet?->pet_name ?? 'N/A' }}</td>
                  <td class="px-6 py-4">
                    @php
                      $statusColors = [
                        'arrived' => 'bg-green-100 text-green-800',
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'rescheduled' => 'bg-blue-100 text-blue-800'
                      ];
                      $status = strtolower($appointment->appoint_status);
                      $colorClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $colorClass }}">
                      {{ ucfirst($appointment->appoint_status) }}
                    </span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      {{-- Recent Referrals --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Recent Referrals</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              @foreach ($recentReferrals->take(5) as $ref)
                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4 text-sm text-gray-900">{{ $ref->ref_date }}</td>
                  <td class="px-6 py-4 text-sm text-gray-600">{{ Str::limit($ref->ref_description, 30) }}</td>
                  <td class="px-6 py-4">
                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                      {{ ucfirst($ref->ref_status ?? 'Pending') }}
                    </span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

{{-- Appointment Modal --}}
<div id="appointmentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4">
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Appointment Details</h3>
        <button id="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <div id="appointmentDetails" class="space-y-3 mb-6">
        <!-- Appointment details will be populated here -->
      </div>
      
      <div class="flex space-x-3">
        <button id="viewProfileBtn" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
          View Profile
        </button>
        <button id="closeModalBtn" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
          Close
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Visit Update Modal --}}
<div id="visitModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full mx-4">
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Update Pet Visit</h3>
        <button id="closeVisitModal" class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <div id="petProfile" class="space-y-4 mb-6">
        <!-- Pet profile details will be populated here -->
      </div>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Visit Status</label>
          <select id="visitStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="pending">Pending</option>
            <option value="arrived">Arrived</option>
            <option value="completed">Completed</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Visit Notes</label>
          <textarea id="visitNotes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Add visit notes..."></textarea>
        </div>
        
        <div class="flex space-x-3">
          <button id="updateVisitBtn" class="flex-1 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
            Update Visit
          </button>
          <button id="closeVisitModalBtn" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  const appointments = {!! $appointments->toJson() !!};
  let viewDate = new Date();
  let currentView = 'monthly';
  let currentAppointment = null;

  const calendarHeader = document.getElementById('calendarHeader');
  const calendarBody = document.getElementById('calendarBody');
  const appointmentModal = document.getElementById('appointmentModal');
  const visitModal = document.getElementById('visitModal');

  function formatDate(date) {
    return date.toISOString().split('T')[0];
  }

  function getStatusColor(status) {
    const colors = {
      'arrive': 'bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-green-200',
      'arrived': 'bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-green-200',
      'completed': 'bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-green-200',
      'pending': 'bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-yellow-200',
      'approved': 'bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-yellow-200',
      'rescheduled': 'bg-orange-100 text-orange-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-orange-200'
    };
    return colors[(status || 'pending').toLowerCase()] || 'bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-yellow-200';
  }

  function updateViewButtons(activeView) {
    const buttons = ['monthlyBtn', 'weeklyBtn', 'todayBtn'];
    buttons.forEach(id => {
      const btn = document.getElementById(id);
      if (id.replace('Btn', '') === activeView) {
        btn.className = 'px-3 py-1.5 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors';
      } else {
        btn.className = 'px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors';
      }
    });
  }

  function renderCalendar(view) {
    calendarBody.innerHTML = '';
    currentView = view;
    updateViewButtons(view);

    if (view === 'monthly') {
      calendarHeader.textContent = viewDate.toLocaleString('default', { month: 'long', year: 'numeric' });
      const firstDay = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
      const lastDay = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 0);
      const startDay = firstDay.getDay();
      const totalDays = lastDay.getDate();

      let day = 1;
      for (let row = 0; row < 6; row++) {
        let html = '<tr>';
        for (let i = 0; i < 7; i++) {
          if ((row === 0 && i < startDay) || day > totalDays) {
            html += `<td class="p-3 h-24 align-top border-t border-gray-200"></td>`;
          } else {
            const dateObj = new Date(viewDate.getFullYear(), viewDate.getMonth(), day);
            const dateStr = formatDate(dateObj);
            const events = appointments[dateStr] || [];

            let eventsHTML = '';
            events.slice(0, 3).forEach(event => {
  const today = new Date();
  const eventDate = new Date(event.date);

  // Check if missed: event date is past AND status is not 'arrived' or 'completed'
  let colorClass = getStatusColor(event.status);
  if (eventDate < today && !['arrived','completed'].includes((event.status || '').toLowerCase())) {
    colorClass = 'bg-red-100 text-red-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-red-200';
    event.status = 'missed'; // Optional: mark as missed in JS for reference
  }

  const petName = event.pet_name || 'Unknown Pet';
  eventsHTML += `<div class="mb-1">
    <span class="${colorClass}" onclick="openAppointmentModal(${JSON.stringify(event).replace(/"/g, '&quot;')})">
      ${petName}
    </span>
  </div>`;
});

            if (events.length > 3) {
              eventsHTML += `<div class="text-xs text-gray-500">+${events.length - 3} more</div>`;
            }

            const isToday = formatDate(new Date()) === dateStr;
            const bgClass = isToday ? 'bg-blue-50' : '';

            html += `<td class="p-3 h-32 align-top border-t border-gray-200 ${bgClass} hover:bg-gray-50 transition-colors">
                      <div class="font-medium text-sm mb-1 ${isToday ? 'text-blue-600' : 'text-gray-900'}">${day}</div>
                      <div>${eventsHTML}</div>
                    </td>`;
            day++;
          }
        }
        html += '</tr>';
        calendarBody.innerHTML += html;
        if (day > totalDays) break;
      }
    }

    else if (view === 'weekly') {
      calendarHeader.textContent = 'Week of ' + viewDate.toLocaleDateString();
      const weekStart = new Date(viewDate);
      weekStart.setDate(viewDate.getDate() - viewDate.getDay());

      let row = '<tr>';
      for (let i = 0; i < 7; i++) {
        const date = new Date(weekStart);
        date.setDate(weekStart.getDate() + i);
        const dateStr = formatDate(date);
        const events = appointments[dateStr] || [];

        let eventsHTML = '';
        events.forEach(event => {
          const colorClass = getStatusColor(event.status);
          const petName = event.pet_name || 'Unknown Pet';
          eventsHTML += `<div class="mb-1">
            <span class="${colorClass}" onclick="openAppointmentModal(${JSON.stringify(event).replace(/"/g, '&quot;')})">
              ${petName}
            </span>
          </div>`;
        });

        const isToday = formatDate(new Date()) === dateStr;
        const bgClass = isToday ? 'bg-blue-50' : '';

        row += `<td class="p-4 h-40 align-top border-t border-gray-200 ${bgClass} hover:bg-gray-50 transition-colors">
                  <div class="font-medium mb-2 ${isToday ? 'text-blue-600' : 'text-gray-900'}">${date.getDate()}</div>
                  <div>${eventsHTML}</div>
                </td>`;
      }
      row += '</tr>';
      calendarBody.innerHTML = row;
    }

    else if (view === 'today') {
      calendarHeader.textContent = viewDate.toDateString();
      const todayStr = formatDate(viewDate);
      const events = appointments[todayStr] || [];

      let html = `<tr><td class="p-6 border-t border-gray-200" colspan="7">`;
      if (events.length) {
        html += '<div class="space-y-3">';
        events.forEach(event => {
          const colorClass = getStatusColor(event.status);
          const petName = event.pet_name || 'Unknown Pet';
          html += `<div class="flex items-center space-x-3">
                    <span class="${colorClass}" onclick="openAppointmentModal(${JSON.stringify(event).replace(/"/g, '&quot;')})">
                      ${petName} - ${event.time || 'No time set'}
                    </span>
                  </div>`;
        });
        html += '</div>';
      } else {
        html += '<div class="text-center text-gray-500 py-8">No appointments today</div>';
      }
      html += `</td></tr>`;
      calendarBody.innerHTML = html;
    }
  }

  // Event Listeners
  document.getElementById('monthlyBtn').onclick = () => renderCalendar('monthly');
  document.getElementById('weeklyBtn').onclick = () => renderCalendar('weekly');
  document.getElementById('todayBtn').onclick = () => renderCalendar('today');
  
  document.getElementById('prevBtn').onclick = () => {
    if (currentView === 'monthly') viewDate.setMonth(viewDate.getMonth() - 1);
    if (currentView === 'weekly') viewDate.setDate(viewDate.getDate() - 7);
    if (currentView === 'today') viewDate.setDate(viewDate.getDate() - 1);
    renderCalendar(currentView);
  };
  
  document.getElementById('nextBtn').onclick = () => {
    if (currentView === 'monthly') viewDate.setMonth(viewDate.getMonth() + 1);
    if (currentView === 'weekly') viewDate.setDate(viewDate.getDate() + 7);
    if (currentView === 'today') viewDate.setDate(viewDate.getDate() + 1);
    renderCalendar(currentView);
  };

  // Initialize calendar
  renderCalendar('monthly');

  // Modal Functions
  function openAppointmentModal(appointment) {
    currentAppointment = appointment;
    const modal = document.getElementById('appointmentModal');
    const details = document.getElementById('appointmentDetails');
    
    details.innerHTML = `
      <div class="grid grid-cols-2 gap-4">
        <div>
          <span class="text-sm text-gray-600">Pet Name:</span>
          <p class="font-medium">${appointment.pet_name || 'Unknown Pet'}</p>
        </div>
        <div>
          <span class="text-sm text-gray-600">Owner:</span>
          <p class="font-medium">${appointment.owner_name || 'Unknown Owner'}</p>
        </div>
        <div>
          <span class="text-sm text-gray-600">Date:</span>
          <p class="font-medium">${appointment.date}</p>
        </div>
        <div>
          <span class="text-sm text-gray-600">Time:</span>
          <p class="font-medium">${appointment.time || 'No time set'}</p>
        </div>
        <div>
          <span class="text-sm text-gray-600">Appointment Type:</span>
          <p class="font-medium">${appointment.type || 'Checkup'}</p>
        </div>
        <div>
          <span class="text-sm text-gray-600">Status:</span>
          <span class="${getStatusColor(appointment.status)}">${appointment.status || 'Pending'}</span>
        </div>
        ${appointment.notes ? `
        <div class="col-span-2">
          <span class="text-sm text-gray-600">Notes:</span>
          <p class="font-medium">${appointment.notes}</p>
        </div>
        ` : ''}
      </div>
    `;
    
    modal.classList.remove('hidden');
  }

  function openVisitModal() {
    if (!currentAppointment) return;
    
    appointmentModal.classList.add('hidden');
    const modal = document.getElementById('visitModal');
    const profile = document.getElementById('petProfile');
    const statusSelect = document.getElementById('visitStatus');
    const notesTextarea = document.getElementById('visitNotes');
    
    profile.innerHTML = `
      <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="font-semibold text-gray-900 mb-3">${currentAppointment.pet_name || 'Unknown Pet'}</h4>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div>
            <span class="text-gray-600">Owner:</span>
            <span class="font-medium">${currentAppointment.owner_name || 'Unknown Owner'}</span>
          </div>
          <div>
            <span class="text-gray-600">Pet Breed:</span>
            <span class="font-medium">${currentAppointment.pet_breed || 'N/A'}</span>
          </div>
          <div>
            <span class="text-gray-600">Pet Age:</span>
            <span class="font-medium">${currentAppointment.pet_age || 'N/A'}</span>
          </div>
          <div>
            <span class="text-gray-600">Gender:</span>
            <span class="font-medium">${currentAppointment.pet_gender || 'N/A'}</span>
          </div>
          <div>
            <span class="text-gray-600">Date:</span>
            <span class="font-medium">${currentAppointment.date}</span>
          </div>
          <div>
            <span class="text-gray-600">Time:</span>
            <span class="font-medium">${currentAppointment.time || 'No time set'}</span>
          </div>
          <div class="col-span-2">
            <span class="text-gray-600">Current Status:</span>
            <span class="${getStatusColor(currentAppointment.status)}">${currentAppointment.status || 'Pending'}</span>
          </div>
        </div>
      </div>
    `;
    
    // Set current status
    statusSelect.value = currentAppointment.status || 'pending';
    notesTextarea.value = currentAppointment.notes || '';
    
    modal.classList.remove('hidden');
  }

  function updateVisit() {
    if (!currentAppointment) return;
    
    const statusSelect = document.getElementById('visitStatus');
    const notesTextarea = document.getElementById('visitNotes');
    const newStatus = statusSelect.value;
    const newNotes = notesTextarea.value;
    
    // Update the appointment object
    currentAppointment.status = newStatus;
    currentAppointment.notes = newNotes;
    
    // Update in the appointments data structure
    const dateStr = currentAppointment.date;
    if (appointments[dateStr]) {
      const appointmentIndex = appointments[dateStr].findIndex(apt => 
        apt.id === currentAppointment.id || 
        (apt.pet_name === currentAppointment.pet_name && apt.date === currentAppointment.date)
      );
      
      if (appointmentIndex !== -1) {
        appointments[dateStr][appointmentIndex] = { ...currentAppointment };
      }
    }
    
    // Here you would typically make an AJAX call to update the backend
    // For now, we'll simulate a successful update
    console.log('Visit updated:', { 
      appointment_id: currentAppointment.id,
      status: newStatus,
      notes: newNotes 
    });
    
    // Close modal and refresh calendar - status will now be green
    document.getElementById('visitModal').classList.add('hidden');
    renderCalendar(currentView);
  }

  // Modal Event Listeners
  document.getElementById('closeModal').onclick = () => appointmentModal.classList.add('hidden');
  document.getElementById('closeModalBtn').onclick = () => appointmentModal.classList.add('hidden');
  document.getElementById('viewProfileBtn').onclick = openVisitModal;
  
  document.getElementById('closeVisitModal').onclick = () => visitModal.classList.add('hidden');
  document.getElementById('closeVisitModalBtn').onclick = () => visitModal.classList.add('hidden');
  document.getElementById('updateVisitBtn').onclick = updateVisit;

  // Close modals when clicking outside
  appointmentModal.onclick = (e) => {
    if (e.target === appointmentModal) {
      appointmentModal.classList.add('hidden');
    }
  };
  
  visitModal.onclick = (e) => {
    if (e.target === visitModal) {
      visitModal.classList.add('hidden');
    }
  };

  // Make functions globally accessible
  window.openAppointmentModal = openAppointmentModal;

  // Modern Charts with better styling
  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false }
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { color: '#6B7280' }
      },
      y: {
        beginAtZero: true,
        grid: { color: '#F3F4F6' },
        ticks: { 
          color: '#6B7280',
          callback: value => '₱' + value.toLocaleString()
        }
      }
    }
  };

  // Daily Orders Chart
  new Chart(document.getElementById('dailyOrdersChart'), {
    type: 'bar',
    data: {
      labels: {!! json_encode($orderDates) !!},
      datasets: [{
        label: 'Revenue (₱)',
        data: {!! json_encode($orderTotals) !!},
        backgroundColor: '#3B82F6',
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: chartOptions
  });

  // Monthly Orders Chart
  new Chart(document.getElementById('monthlyOrdersChart'), {
    type: 'line',
    data: {
      labels: {!! json_encode($months) !!},
      datasets: [{
        label: 'Monthly Revenue (₱)',
        data: {!! json_encode($monthlySalesTotals) !!},
        borderColor: '#10B981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointBackgroundColor: '#10B981',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 6
      }]
    },
    options: {
      ...chartOptions,
      plugins: {
        ...chartOptions.plugins,
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          titleColor: '#fff',
          bodyColor: '#fff',
          borderColor: '#10B981',
          borderWidth: 1
        }
      }
    }
  });
</script>
@endsection