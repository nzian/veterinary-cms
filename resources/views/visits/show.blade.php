<div class="card">
    <div class="card-header">
        <h5>Services</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Completed At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($visit->services as $service)
                    <tr>
                        <td>{{ $service->serv_name }}</td>
                        <td>
                            <span class="badge bg-{{ $service->pivot->status === 'completed' ? 'success' : 'warning' }}">
                                {{ ucfirst($service->pivot->status) }}
                            </span>
                        </td>
                        <td>
                            {{ $service->pivot->completed_at ? $service->pivot->completed_at->format('M d, Y H:i') : 'N/A' }}
                        </td>
                        <td>
                            @if($service->pivot->status !== 'completed')
                            <button class="btn btn-sm btn-success complete-service" 
                                    data-visit-id="{{ $visit->visit_id }}"
                                    data-service-id="{{ $service->serv_id }}">
                                Mark as Complete
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
// In resources/views/visits/show.blade.php
$('.complete-service').click(function() {
    const visitId = $(this).data('visit-id');
    const serviceId = $(this).data('service-id');
    const button = $(this);
    const row = button.closest('tr');
    
    // Show loading state
    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Completing...');
    
    $.ajax({
        url: `/visits/${visitId}/services/${serviceId}/complete`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            notes: '' // Add notes if needed
        },
        success: function(response) {
            if (response.success) {
                // Update the row
                const statusBadge = row.find('.badge');
                statusBadge.removeClass('bg-warning').addClass('bg-success')
                    .text('Completed');
                
                // Update completed at
                const now = new Date();
                const formattedDate = now.toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                row.find('td:nth-child(3)').text(formattedDate);
                
                // Remove the action button
                row.find('td:last').empty();
                
                // If all services are completed, show a message
                if (response.all_services_completed) {
                    // Option 1: Show a success message
                    alert('All services have been completed!');
                    // Option 2: Or reload the page to show the completed state
                    // window.location.reload();
                }
            }
        },
        error: function(xhr) {
            button.prop('disabled', false).text('Mark as Complete');
            alert('Failed to update service status. Please try again.');
            console.error('Error:', xhr.responseText);
        }
    });
});
</script>
@endpush