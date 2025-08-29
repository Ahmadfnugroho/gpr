<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>WhatsApp Management Dashboard - Global Photo Rental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-connected { background-color: #10B981; }
        .status-connecting { background-color: #F59E0B; animation: pulse 2s infinite; }
        .status-disconnected { background-color: #EF4444; }
        .status-unknown { background-color: #6B7280; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">
                        📱 WhatsApp Management Dashboard
                    </h1>
                    <p class="text-gray-600">Global Photo Rental - WhatsApp API Management</p>
                </div>
                <form method="POST" action="{{ route('whatsapp.auth.logout') }}">
                    @csrf
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm">
                        🔐 Logout
                    </button>
                </form>
            </div>
        </div>

        @if(isset($error))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <strong>Error:</strong> {{ $error }}
        </div>
        @endif

        <!-- Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Connection Status -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-800">Connection Status</h3>
                        <div id="connection-status" class="mt-2">
                            <span class="status-indicator status-unknown"></span>
                            <span class="text-gray-600">Checking...</span>
                        </div>
                    </div>
                    <div class="text-3xl">🔗</div>
                </div>
                <div class="mt-4">
                    <button id="refresh-status" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm">
                        Refresh Status
                    </button>
                </div>
            </div>

            <!-- Server Info -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-800">Server Info</h3>
                        <div id="server-info" class="mt-2 text-sm text-gray-600">
                            <div>Server: whatsapp.globalphotorental.com</div>
                            <div id="version-info">Version: Loading...</div>
                        </div>
                    </div>
                    <div class="text-3xl">🖥️</div>
                </div>
            </div>

            <!-- Connected Phone -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-800">Connected Phone</h3>
                        <div id="phone-info" class="mt-2 text-sm text-gray-600">
                            Not connected
                        </div>
                    </div>
                    <div class="text-3xl">📱</div>
                </div>
            </div>
        </div>

        <!-- QR Code Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📷 QR Code for WhatsApp Connection</h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <div id="qr-code-container" class="text-center">
                        <div class="bg-gray-200 rounded-lg p-8 mb-4">
                            <div id="qr-code-display">
                                <div class="text-gray-500">Click "Get QR Code" to display</div>
                            </div>
                        </div>
                        <button id="get-qr-code" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg">
                            Get QR Code
                        </button>
                        <button id="restart-session" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded ml-2">
                            Restart Session
                        </button>
                        <button id="logout-session" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded ml-2">
                            End Session
                        </button>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800 mb-3">📋 Instructions:</h4>
                    <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600">
                        <li>Click "Get QR Code" button</li>
                        <li>Open WhatsApp on your phone</li>
                        <li>Go to Settings > Linked Devices</li>
                        <li>Tap "Link a Device"</li>
                        <li>Scan the QR code displayed</li>
                        <li>Wait for connection to establish</li>
                    </ol>
                    <div class="mt-4 p-3 bg-blue-50 rounded">
                        <p class="text-sm text-blue-700">
                            <strong>💡 Tip:</strong> Make sure your phone has stable internet connection during scanning.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Message Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📨 Test Message</h3>
            <form id="test-message-form">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="text" id="test-phone" class="w-full p-3 border border-gray-300 rounded-lg" 
                               placeholder="6281234567890" value="6281117095956">
                        <p class="text-xs text-gray-500 mt-1">Format: 628xxxxxxxxx (international format)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <textarea id="test-message" rows="3" class="w-full p-3 border border-gray-300 rounded-lg" 
                                  placeholder="Enter your test message...">🧪 Test message from Global Photo Rental WhatsApp Dashboard!

✅ System is working properly.
📱 WhatsApp API is connected and ready to send notifications.

Time: {{ date('d M Y H:i:s') }}</textarea>
                    </div>
                </div>
                <button type="submit" id="send-test" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg">
                    Send Test Message
                </button>
            </form>
        </div>

        <!-- Logs Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📋 Recent Logs</h3>
            <div class="bg-gray-50 rounded-lg p-4 max-h-64 overflow-y-auto">
                <div id="logs-container" class="text-sm text-gray-700 font-mono">
                    <div class="text-gray-500">Loading logs...</div>
                </div>
            </div>
            <div class="mt-4">
                <button id="refresh-logs" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
                    Refresh Logs
                </button>
            </div>
        </div>
    </div>

    <script>
        // Set CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Auto-refresh status every 30 seconds
        let statusInterval = setInterval(checkStatus, 30000);

        // Initial load
        $(document).ready(function() {
            checkStatus();
            loadLogs();
            loadVersion();
        });

        // Check connection status
        function checkStatus() {
            $.get('{{ route("whatsapp.status") }}')
                .done(function(data) {
                    if (data.success) {
                        let statusClass = 'status-unknown';
                        let statusText = data.status;
                        
                        if (data.connected) {
                            statusClass = 'status-connected';
                            statusText = 'Connected ✅';
                        } else if (data.status === 'SCAN_QR_CODE') {
                            statusClass = 'status-connecting';
                            statusText = 'Scan QR Code 📱';
                        } else {
                            statusClass = 'status-disconnected';
                            statusText = data.status;
                        }
                        
                        $('#connection-status').html(
                            '<span class="status-indicator ' + statusClass + '"></span>' +
                            '<span>' + statusText + '</span>'
                        );

                        // Update phone info
                        if (data.me) {
                            $('#phone-info').html(
                                '<div>Phone: ' + (data.me.pushname || 'N/A') + '</div>' +
                                '<div>Number: ' + (data.me.id || 'N/A').replace('@c.us', '') + '</div>'
                            );
                        } else {
                            $('#phone-info').html('Not connected');
                        }
                    } else {
                        $('#connection-status').html(
                            '<span class="status-indicator status-disconnected"></span>' +
                            '<span>Error: ' + data.message + '</span>'
                        );
                    }
                })
                .fail(function() {
                    $('#connection-status').html(
                        '<span class="status-indicator status-disconnected"></span>' +
                        '<span>Connection Error</span>'
                    );
                });
        }

        // Get QR Code
        $('#get-qr-code').click(function() {
            let $btn = $(this);
            $btn.prop('disabled', true).text('Getting QR Code...');
            
            $.get('{{ route("whatsapp.qr") }}')
                .done(function(data) {
                    if (data.success && data.qr_code) {
                        $('#qr-code-display').html('<img src="' + data.qr_code + '" alt="QR Code" class="mx-auto max-w-xs">');
                    } else {
                        $('#qr-code-display').html('<div class="text-red-500">Failed to get QR Code: ' + data.message + '</div>');
                    }
                })
                .fail(function() {
                    $('#qr-code-display').html('<div class="text-red-500">Error loading QR Code</div>');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('Get QR Code');
                });
        });

        // Restart session
        $('#restart-session').click(function() {
            if (!confirm('Are you sure you want to restart the session?')) return;
            
            let $btn = $(this);
            $btn.prop('disabled', true).text('Restarting...');
            
            $.post('{{ route("whatsapp.restart") }}')
                .done(function(data) {
                    if (data.success) {
                        alert('Session restarted successfully!');
                        $('#qr-code-display').html('<div class="text-gray-500">Click "Get QR Code" to display new QR</div>');
                        checkStatus();
                    } else {
                        alert('Failed to restart session: ' + data.message);
                    }
                })
                .fail(function() {
                    alert('Error restarting session');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('Restart Session');
                });
        });

        // End/Logout session
        $('#logout-session').click(function() {
            if (!confirm('Are you sure you want to end the WhatsApp session? This will log out the connected device.')) return;
            
            let $btn = $(this);
            $btn.prop('disabled', true).text('Ending Session...');
            
            $.post('{{ route("whatsapp.logout") }}')
                .done(function(data) {
                    if (data.success) {
                        alert('Session ended successfully! WhatsApp has been logged out.');
                        $('#qr-code-display').html('<div class="text-gray-500">Session ended. Click "Get QR Code" to start new session.</div>');
                        $('#phone-info').html('Not connected');
                        checkStatus();
                    } else {
                        alert('Failed to end session: ' + data.message);
                    }
                })
                .fail(function() {
                    alert('Error ending session');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('End Session');
                });
        });

        // Send test message
        $('#test-message-form').submit(function(e) {
            e.preventDefault();
            
            let phone = $('#test-phone').val();
            let message = $('#test-message').val();
            
            if (!phone || !message) {
                alert('Please fill in both phone number and message');
                return;
            }
            
            let $btn = $('#send-test');
            $btn.prop('disabled', true).text('Sending...');
            
            $.post('{{ route("whatsapp.test") }}', {
                phone_number: phone,
                message: message
            })
            .done(function(data) {
                if (data.success) {
                    alert('Test message sent successfully!');
                } else {
                    alert('Failed to send message: ' + data.message);
                }
            })
            .fail(function(xhr) {
                let errorMsg = 'Error sending message';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg += ': ' + xhr.responseJSON.message;
                }
                alert(errorMsg);
            })
            .always(function() {
                $btn.prop('disabled', false).text('Send Test Message');
            });
        });

        // Refresh status button
        $('#refresh-status').click(checkStatus);

        // Load version info
        function loadVersion() {
            // This will be loaded via the controller
        }

        // Load logs
        function loadLogs() {
            $.get('{{ route("whatsapp.logs") }}')
                .done(function(data) {
                    if (data.success) {
                        let logsHtml = '';
                        if (data.logs && data.logs.length > 0) {
                            data.logs.forEach(function(log) {
                                logsHtml += '<div class="mb-1">' + log + '</div>';
                            });
                        } else {
                            logsHtml = '<div class="text-gray-500">No recent WhatsApp logs found</div>';
                        }
                        $('#logs-container').html(logsHtml);
                    } else {
                        $('#logs-container').html('<div class="text-red-500">Failed to load logs</div>');
                    }
                })
                .fail(function() {
                    $('#logs-container').html('<div class="text-red-500">Error loading logs</div>');
                });
        }

        // Refresh logs button
        $('#refresh-logs').click(loadLogs);
    </script>
</body>
</html>
