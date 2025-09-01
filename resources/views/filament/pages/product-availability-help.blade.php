<div class="space-y-4">
    <div>
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">About Product Availability</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            This page shows real-time product availability based on current and future rental bookings. 
            Data automatically refreshes every 30 seconds to provide the most up-to-date information.
        </p>
    </div>

    <div>
        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Column Explanations</h4>
        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <div><strong>Total Items:</strong> Total number of serial numbered items for this product</div>
            <div><strong>Available Items:</strong> Number of items available during the selected date range</div>
            <div><strong>Current Rentals:</strong> Breakdown of items currently rented by status:
                <ul class="ml-4 mt-1 list-disc">
                    <li><span style="color: orange;">Booking</span> - Items with confirmed bookings</li>
                    <li><span style="color: blue;">Paid</span> - Items that are paid for but not yet picked up</li>
                    <li><span style="color: green;">On Rented</span> - Items currently in use by customers</li>
                </ul>
            </div>
            <div><strong>Next Available:</strong> The earliest date when more items will become available</div>
            <div><strong>Available Serial Numbers:</strong> List of specific serial numbers available during the date range</div>
        </div>
    </div>

    <div>
        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Filters</h4>
        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <div><strong>Date Range:</strong> Select the period to check availability for. Defaults to next 7 days.</div>
            <div><strong>Only Available Products:</strong> Toggle to show only products that have at least some items available in the selected date range.</div>
        </div>
    </div>

    <div>
        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Color Coding</h4>
        <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <div><span class="font-semibold text-green-600">Green:</span> 70%+ items available</div>
            <div><span class="font-semibold text-yellow-600">Yellow:</span> 30-69% items available</div>
            <div><span class="font-semibold text-red-600">Red:</span> Less than 30% items available</div>
            <div><span class="font-semibold text-gray-500">Gray:</span> No items for this product</div>
        </div>
    </div>

    <div>
        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Tips</h4>
        <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <li>Use the search box to quickly find specific products</li>
            <li>Click the refresh button to manually update data</li>
            <li>Adjust the date range to plan for future availability</li>
            <li>Cancelled bookings are automatically excluded from availability calculations</li>
        </div>
    </div>
</div>
