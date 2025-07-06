// None Added Products JavaScript
$(document).ready(function() {
    console.log('None Added Products JS loaded');
    
    // Add CSS styles
    var css = `
        <style>
            .table-hover tbody tr:hover {
                background-color: #f8f9fa !important;
            }
            
            .badge {
                font-size: 0.75rem;
                font-weight: 500;
            }
            
            .btn-group .btn {
                margin-right: 2px;
            }
            
            .btn-group .btn:last-child {
                margin-right: 0;
            }
            
            .box-warning {
                border-top-color: #f39c12;
            }
            
            .box-warning > .box-header {
                background-color: #f39c12;
                color: white;
            }
            
            .table-responsive {
                border-radius: 4px;
                overflow: hidden;
            }
            
            .table thead th {
                border-bottom: 2px solid #dee2e6;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.5px;
            }
            
            .table tbody td {
                vertical-align: middle;
                border-top: 1px solid #dee2e6;
            }
            
            .hover-row {
                background-color: #f8f9fa !important;
                transition: background-color 0.2s ease;
            }
            
            /* Enhanced styling for the Products button */
            .add-to-inventory-btn {
                background-color: #007bff !important;
                border-color: #007bff !important;
                color: white !important;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            
            .add-to-inventory-btn:hover {
                background-color: #0056b3 !important;
                border-color: #0056b3 !important;
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .add-to-inventory-btn:active {
                transform: translateY(0);
                box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }
            
            .add-to-inventory-btn i {
                margin-right: 3px;
            }
        </style>
    `;
    $('head').append(css);
    
    // Simple test function - defined at the top to avoid undefined errors
    window.testButtonClick = function(productName) {
        console.log('Inline onclick works! Product:', productName);
        // Don't show alert here to avoid double alerts
    };
    
    // Initialize DataTable
    if ($('#none_added_product_table').length) {
        $('#none_added_product_table').DataTable({
            "pageLength": 25,
            "order": [[ 0, "asc" ]],
            "language": {
                "search": "Search products:",
                "lengthMenu": "Show _MENU_ products per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ products",
                "emptyTable": "No products found",
                "zeroRecords": "No matching products found"
            },
            "columnDefs": [
                { "orderable": false, "targets": [5] } // Disable sorting for actions column
            ]
        });
        
        console.log('DataTable initialized');
    }
    
    // Add hover effects
    $('.table tbody tr').hover(
        function() {
            $(this).addClass('hover-row');
        },
        function() {
            $(this).removeClass('hover-row');
        }
    );
    
    // Note: The click handler is now handled by the inline onclick function in the view
    // This prevents conflicts between the inline function and the document.on('click') handler
    console.log('Event handlers attached - inline onclick functions are used for button clicks');
    
    console.log('Event handlers attached');
}); 