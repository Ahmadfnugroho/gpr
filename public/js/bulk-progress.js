/**
 * Bulk Operations Progress Tracker
 * Real-time progress tracking for bulk operations with modern UI
 */

class BulkProgressTracker {
    constructor(options = {}) {
        this.options = {
            updateInterval: 1000, // 1 second
            maxRetries: 3,
            retryDelay: 2000, // 2 seconds
            ...options
        };
        
        this.currentJobId = null;
        this.progressModal = null;
        this.updateTimer = null;
        this.retryCount = 0;
        
        this.init();
    }

    init() {
        this.createProgressModal();
        this.bindEvents();
    }

    createProgressModal() {
        // Create modal HTML
        const modalHTML = `
            <div id="bulkProgressModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="progressTitle">
                                Processing Bulk Operation
                            </h3>
                            <button id="closeProgressModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mb-4">
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-blue-700" id="progressText">Starting...</span>
                                <span class="text-sm font-medium text-blue-700" id="progressPercentage">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                        </div>
                        
                        <div class="text-sm text-gray-600 mb-4">
                            <div class="flex justify-between">
                                <span>Processed:</span>
                                <span id="processedCount">0 / 0</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Estimated Time:</span>
                                <span id="estimatedTime">Calculating...</span>
                            </div>
                        </div>
                        
                        <div id="progressLogs" class="max-h-32 overflow-y-auto text-xs text-gray-500 bg-gray-50 p-2 rounded mb-4 hidden">
                        </div>
                        
                        <div class="flex justify-end space-x-2">
                            <button id="toggleLogs" class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                Show Logs
                            </button>
                            <button id="cancelOperation" class="px-4 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700 hidden">
                                Cancel
                            </button>
                        </div>
                        
                        <div id="completionMessage" class="hidden mt-4 p-3 rounded-md">
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert modal into DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.progressModal = document.getElementById('bulkProgressModal');
    }

    bindEvents() {
        // Close modal
        document.getElementById('closeProgressModal').addEventListener('click', () => {
            this.hideModal();
        });

        // Toggle logs
        document.getElementById('toggleLogs').addEventListener('click', () => {
            this.toggleLogs();
        });

        // Cancel operation (if implemented)
        document.getElementById('cancelOperation').addEventListener('click', () => {
            this.cancelOperation();
        });

        // Close modal when clicking outside
        this.progressModal.addEventListener('click', (e) => {
            if (e.target === this.progressModal) {
                this.hideModal();
            }
        });
    }

    startBulkOperation(formData, options = {}) {
        const url = options.url || '/customers/bulk-action/start';
        
        // Show loading state
        this.showModal();
        this.updateProgress({
            message: 'Initializing bulk operation...',
            percentage: 0,
            processed: 0,
            total: 0
        });

        // Send request to start bulk operation
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.job_id) {
                this.currentJobId = data.job_id;
                
                if (data.use_polling) {
                    // Start polling for progress
                    this.startProgressPolling();
                    
                    // Update with initial info
                    this.updateProgress({
                        message: data.message || 'Operation started in background',
                        percentage: 0,
                        estimated_time: data.estimated_time
                    });
                } else {
                    // Operation completed immediately
                    this.handleCompletion(data);
                }
            } else {
                throw new Error(data.error || 'Failed to start bulk operation');
            }
        })
        .catch(error => {
            this.handleError(error);
        });
    }

    startProgressPolling() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }

        this.updateTimer = setInterval(() => {
            this.fetchProgress();
        }, this.options.updateInterval);
    }

    fetchProgress() {
        if (!this.currentJobId) {
            return;
        }

        fetch(`/customers/bulk-action/progress?job_id=${this.currentJobId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                this.retryCount = 0; // Reset retry count on success
                this.updateProgress(data);
                
                // Check if completed
                if (data.status === 'completed' || data.percentage >= 100) {
                    this.stopProgressPolling();
                    this.handleCompletion(data);
                } else if (data.status === 'failed') {
                    this.stopProgressPolling();
                    this.handleError(new Error(data.error_message || 'Operation failed'));
                }
            })
            .catch(error => {
                this.handleProgressError(error);
            });
    }

    handleProgressError(error) {
        this.retryCount++;
        
        if (this.retryCount >= this.options.maxRetries) {
            this.stopProgressPolling();
            this.handleError(error);
        } else {
            // Retry after delay
            setTimeout(() => {
                this.fetchProgress();
            }, this.options.retryDelay);
            
            this.addLog(`Connection error, retrying... (${this.retryCount}/${this.options.maxRetries})`);
        }
    }

    updateProgress(data) {
        // Update progress bar
        const percentage = data.percentage || 0;
        document.getElementById('progressBar').style.width = percentage + '%';
        document.getElementById('progressPercentage').textContent = percentage.toFixed(1) + '%';
        
        // Update text
        document.getElementById('progressText').textContent = data.message || 'Processing...';
        
        // Update counts
        if (data.processed !== undefined && data.total !== undefined) {
            document.getElementById('processedCount').textContent = `${data.processed} / ${data.total}`;
        }
        
        // Update estimated time
        if (data.estimated_time) {
            const timeText = typeof data.estimated_time === 'object' 
                ? data.estimated_time.formatted 
                : data.estimated_time;
            document.getElementById('estimatedTime').textContent = timeText;
        }
        
        // Add to logs
        if (data.message) {
            this.addLog(`${new Date().toLocaleTimeString()}: ${data.message}`);
        }
    }

    handleCompletion(data) {
        const completionDiv = document.getElementById('completionMessage');
        let message = '';
        let className = '';
        
        if (data.success !== false) {
            message = `✅ Operation completed successfully!`;
            if (data.success_count) {
                message += ` (${data.success_count} records processed)`;
            }
            if (data.execution_time) {
                message += ` in ${data.execution_time}s`;
            }
            className = 'bg-green-100 text-green-800';
        } else {
            message = `❌ Operation failed: ${data.error || 'Unknown error'}`;
            className = 'bg-red-100 text-red-800';
        }
        
        completionDiv.textContent = message;
        completionDiv.className = `p-3 rounded-md ${className}`;
        completionDiv.classList.remove('hidden');
        
        // Auto-close after 5 seconds for success
        if (data.success !== false) {
            setTimeout(() => {
                this.hideModal();
                // Refresh page or update table
                if (window.location.reload) {
                    window.location.reload();
                }
            }, 5000);
        }
    }

    handleError(error) {
        console.error('Bulk operation error:', error);
        
        const completionDiv = document.getElementById('completionMessage');
        completionDiv.textContent = `❌ Error: ${error.message}`;
        completionDiv.className = 'p-3 rounded-md bg-red-100 text-red-800';
        completionDiv.classList.remove('hidden');
        
        // Update progress bar to show error state
        document.getElementById('progressBar').className = 'bg-red-600 h-2.5 rounded-full transition-all duration-300';
        document.getElementById('progressText').textContent = 'Operation failed';
    }

    stopProgressPolling() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
            this.updateTimer = null;
        }
    }

    showModal() {
        this.progressModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    hideModal() {
        this.progressModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        this.stopProgressPolling();
        this.currentJobId = null;
    }

    addLog(message) {
        const logsDiv = document.getElementById('progressLogs');
        const logEntry = document.createElement('div');
        logEntry.textContent = message;
        logsDiv.appendChild(logEntry);
        logsDiv.scrollTop = logsDiv.scrollHeight;
    }

    toggleLogs() {
        const logsDiv = document.getElementById('progressLogs');
        const toggleBtn = document.getElementById('toggleLogs');
        
        if (logsDiv.classList.contains('hidden')) {
            logsDiv.classList.remove('hidden');
            toggleBtn.textContent = 'Hide Logs';
        } else {
            logsDiv.classList.add('hidden');
            toggleBtn.textContent = 'Show Logs';
        }
    }

    cancelOperation() {
        // This would need to be implemented on the backend
        console.log('Cancel operation requested');
        // You can implement cancellation logic here
    }
}

// Initialize global instance
window.bulkProgressTracker = new BulkProgressTracker();

// Helper function for easy integration
window.startBulkOperation = function(formData, options = {}) {
    return window.bulkProgressTracker.startBulkOperation(formData, options);
};
