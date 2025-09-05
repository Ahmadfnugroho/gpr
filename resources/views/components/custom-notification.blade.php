@php
    $notification = App\Services\CustomNotificationService::getCurrentNotification();
@endphp

@if($notification)
<div id="customNotification" class="fixed top-4 right-4 z-50 max-w-md w-full">
    <div class="bg-white rounded-lg border shadow-lg overflow-hidden
        @if($notification['type'] === 'success') border-green-400
        @elseif($notification['type'] === 'error') border-red-400
        @elseif($notification['type'] === 'warning') border-yellow-400
        @endif">
        
        <!-- Header -->
        <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
            <div class="flex items-center">
                @if($notification['type'] === 'success')
                    <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                @elseif($notification['type'] === 'error')
                    <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                @elseif($notification['type'] === 'warning')
                    <svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                @endif
                <h3 class="text-sm font-medium text-gray-900">{{ $notification['title'] }}</h3>
            </div>
            <button onclick="closeNotification()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <!-- Content -->
        <div class="p-4">
            <p class="text-sm text-gray-700 mb-3">{{ $notification['message'] }}</p>
            
            @if(!empty($notification['errors']))
                <div class="bg-gray-50 rounded p-3 mb-3">
                    <p class="text-xs font-medium text-gray-600 mb-2">Errors Detail:</p>
                    <div class="max-h-32 overflow-y-auto">
                        @foreach(array_slice($notification['errors'], 0, 5) as $error)
                            <p class="text-xs text-red-600 mb-1">• {{ $error }}</p>
                        @endforeach
                        @if(count($notification['errors']) > 5)
                            <p class="text-xs text-gray-500 italic">dan {{ count($notification['errors']) - 5 }} error lainnya...</p>
                        @endif
                    </div>
                </div>
            @endif
            
            <!-- Action Buttons -->
            <div class="flex justify-end space-x-2">
                @if($notification['show_download'])
                    <button onclick="downloadFailedImport()" 
                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Download Error Report
                    </button>
                @endif
                
                <button onclick="closeNotification()" 
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function downloadFailedImport() {
        window.open('/failed-import/download', '_blank');
    }
    
    function closeNotification() {
        document.getElementById('customNotification').style.display = 'none';
        
        // Clear notification from session
        fetch('/failed-import/clear', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        });
    }
    
    // Auto close after 10 seconds for success notifications
    @if($notification['type'] === 'success')
        setTimeout(function() {
            closeNotification();
        }, 10000);
    @endif
</script>
@endif
