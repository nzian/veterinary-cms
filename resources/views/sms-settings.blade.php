@extends('AdminBoard')

@section('content')
<div class="min-h-screen">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-[#0f7ea0]">SMS Configuration</h1>
            <div class="flex gap-2">
                <button onclick="testSMS()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Test SMS
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('sms-settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Provider Selection -->
            <select name="provider" id="provider" onchange="updateAPIURL()" 
                class="w-full border border-gray-300 rounded px-3 py-2">
                <option value="philsms" {{ ($settings->sms_provider ?? 'philsms') == 'philsms' ? 'selected' : '' }}>PhilSMS</option>
                <option value="semaphore" {{ ($settings->sms_provider ?? '') == 'semaphore' ? 'selected' : '' }}>Semaphore</option>
                <option value="twilio" {{ ($settings->sms_provider ?? '') == 'twilio' ? 'selected' : '' }}>Twilio</option>
            </select>

            <!-- API Key (for PhilSMS & Semaphore) -->
            <div id="api_key_field">
                <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                <input type="text" name="api_key" value="{{ $settings->sms_api_key ?? '' }}" 
                       class="w-full border border-gray-300 rounded px-3 py-2" 
                       placeholder="Enter your SMS provider API key">
            </div>

              <!-- Twilio SID -->
            <div id="twilio_sid_field" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Twilio Account SID</label>
                <input type="text" name="twilio_sid" value="{{ $settings->sms_api_key ?? '' }}" 
                       class="w-full border border-gray-300 rounded px-3 py-2" 
                       placeholder="Enter your Twilio SID">
            </div>

            <!-- Twilio Auth Token -->
            <div id="twilio_token_field" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Twilio Auth Token</label>
                <input type="text" name="twilio_token" value="{{ $settings->sms_twilio_token ?? '' }}" 
                       class="w-full border border-gray-300 rounded px-3 py-2" 
                       placeholder="Enter your Twilio Auth Token">
            </div>

            <!-- Sender ID -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sender ID</label>
                <input type="text" name="sender_id" value="{{ $settings->sms_sender_id ?? 'YourClinic' }}" 
                       class="w-full border border-gray-300 rounded px-3 py-2" 
                       placeholder="YourClinic" maxlength="11">
                <p class="text-sm text-gray-500 mt-1">⚠️ For Twilio, this must be a Twilio phone number.</p>
            </div>

            <!-- API URL -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">API URL</label>
                <input type="url" name="api_url" id="api_url" value="{{ $settings->sms_api_url ?? 'https://app.philsms.com/api/v3/sms/send' }}" 
                       class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <!-- Current Configuration -->
            <div class="bg-gray-50 p-4 rounded border">
                <h3 class="font-medium text-gray-700 mb-2">Current Configuration</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-600">Provider:</span> <span class="font-medium">{{ $settings->sms_provider ?? 'Not set' }}</span></div>
                    <div><span class="text-gray-600">Sender ID:</span> <span class="font-medium">{{ $settings->sms_sender_id ?? 'Not set' }}</span></div>
                    <div><span class="text-gray-600">API Key / SID:</span> <span class="font-medium">{{ $settings->sms_api_key || $settings->twilio_sid ? '***' : 'Not set' }}</span></div>
                    <div><span class="text-gray-600">Status:</span> <span class="font-medium text-green-600">{{ $settings->sms_is_active ? 'Active' : 'Inactive' }}</span></div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-[#0f7ea0] text-white px-6 py-2 rounded hover:bg-[#0c6a86]">
                    Save Configuration
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Test SMS Modal -->
<div id="testModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg w-96">
        <h3 class="text-lg font-medium mb-4">Test SMS</h3>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Test Phone Number</label>
            <input type="text" id="testNumber" class="w-full border border-gray-300 rounded px-3 py-2" 
                   placeholder="09171234567">
            <p class="text-sm text-gray-500 mt-1">Enter a mobile number to test</p>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeTestModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                Cancel
            </button>
            <button type="button" onclick="sendTestSMS()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                Send Test SMS
            </button>
        </div>
    </div>
</div>

<script>
function testSMS() {
    document.getElementById('testModal').classList.remove('hidden');
    document.getElementById('testModal').classList.add('flex');
}

function closeTestModal() {
    document.getElementById('testModal').classList.add('hidden');
    document.getElementById('testModal').classList.remove('flex');
}

function sendTestSMS() {
    const testNumber = document.getElementById('testNumber').value;
    if (!testNumber) {
        alert('Please enter a test number');
        return;
    }

    const button = event.target;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Sending...';

    fetch('{{ route("sms-settings.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ test_number: testNumber })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Test SMS sent successfully!');
        } else {
            alert('❌ Test SMS failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('❌ Request failed: ' + error.message);
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = originalText;
        closeTestModal();
    });
}
</script>


<script>
function updateAPIURL() {
    const provider = document.getElementById('provider').value;
    const apiUrlField = document.getElementById('api_url');
    const apiKeyField = document.getElementById('api_key_field');
    const sidField = document.getElementById('twilio_sid_field');
    const tokenField = document.getElementById('twilio_token_field');

    if (provider === 'philsms') {
        apiUrlField.value = 'https://app.philsms.com/api/v3/sms/send';
        apiKeyField.classList.remove('hidden');
        sidField.classList.add('hidden');
        tokenField.classList.add('hidden');
    } else if (provider === 'semaphore') {
        apiUrlField.value = 'https://api.semaphore.co/api/v4/messages';
        apiKeyField.classList.remove('hidden');
        sidField.classList.add('hidden');
        tokenField.classList.add('hidden');
    } else if (provider === 'twilio') {
        apiUrlField.value = 'https://api.twilio.com/2010-04-01/Accounts';
        apiKeyField.classList.add('hidden');
        sidField.classList.remove('hidden');
        tokenField.classList.remove('hidden');
    }
}

// run once on load
document.addEventListener('DOMContentLoaded', updateAPIURL);
</script>

@endsection
