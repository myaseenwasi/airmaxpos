@extends('layouts.app')

@section('title', __('None Added Products'))

@section('content')
    <!-- Global function for button clicks - must be defined before content -->
    <script>
        function handleAddToInventory(productName, productId) {
            console.log('Global function called with product:', productName, 'ID:', productId);
            
            if (confirm('Do you want to go to the Products list page? This will remove the product from the None Added Products list.')) {
                console.log('User confirmed, removing product and redirecting...');
                
                // Remove the product from None Added Products first
                $.ajax({
                    url: "/products/none-added/remove",
                    method: 'POST',
                    data: {
                        product_name: productName,
                        product_id: productId,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Product removed successfully:', response);
                        if (response.success) {
                            // Remove the row from the table
                            var row = $('button[data-product-id="' + productId + '"]').closest('tr');
                            row.fadeOut(300, function() {
                                row.remove();
                                // Update the counter
                                var currentCount = parseInt($('.tw-text-yellow-800 strong').text());
                                $('.tw-text-yellow-800 strong').text(currentCount - 1);
                                
                                // Check if no products left
                                if ($('#none_added_product_table tbody tr').length === 0) {
                                    location.reload(); // Reload to show empty state
                                }
                            });
                            
                            // Redirect to products list page
                            window.location.href = "/products";
                        } else {
                            console.error('Failed to remove product:', response.message);
                            // Still redirect even if removal fails
                            window.location.href = "/products";
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error removing product:', error);
                        // Still redirect even if removal fails
                        window.location.href = "/products";
                    }
                });
            } else {
                console.log('User cancelled');
            }
        }
    </script>

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="row">
            <div class="col-md-6">
                <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-gray-800">
                    <i class="fa fa-exclamation-triangle text-warning"></i>
                    None Added Products
                    <small class="tw-text-sm md:tw-text-base tw-text-gray-600 tw-font-normal tw-block tw-mt-2">
                        Products that haven't been added to inventory yet
                    </small>
                </h1>
            </div>
            <div class="col-md-6">
                <div class="tw-flex tw-justify-end tw-items-center tw-h-full">
                    <div class="tw-bg-yellow-50 tw-border tw-border-yellow-200 tw-rounded-lg tw-p-4 tw-w-full">
                        <div class="tw-flex tw-items-center">
                            <i class="fa fa-info-circle text-warning tw-mr-2"></i>
                            <span class="tw-text-sm tw-text-yellow-800">
                                <strong>{{ $products->count() ?? 0 }}</strong> products pending addition
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-list"></i>
                            Product List
                        </h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                <i class="fa fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover" id="none_added_product_table">
                                <thead class="tw-bg-gray-50">
                                    <tr>
                                        <th class="tw-font-bold tw-text-sm md:tw-text-base text-center" style="width: 60px;">
                                            <i class="fa fa-hashtag"></i>
                                        </th>
                                        <th class="tw-font-bold tw-text-sm md:tw-text-base">
                                            <i class="fa fa-cube"></i> Product Name
                                        </th>
                                        <th class="tw-font-bold tw-text-sm md:tw-text-base">
                                            <i class="fa fa-barcode"></i> SKU
                                        </th>
                                        <th class="tw-font-bold tw-text-sm md:tw-text-base">
                                            <i class="fa fa-tags"></i> Category
                                        </th>
                                        <th class="tw-font-bold tw-text-sm md:tw-text-base text-center" style="width: 120px;">
                                            <i class="fa fa-info-circle"></i> Status
                                        </th>
                                        <th class="tw-font-bold tw-text-sm md:tw-text-base text-center" style="width: 100px;">
                                            <i class="fa fa-cogs"></i> Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($products as $index => $product)
                                        <tr class="tw-hover:bg-gray-50">
                                            <td class="text-center tw-font-semibold tw-text-gray-700">
                                                {{ $index + 1 }}
                                            </td>
                                            <td>
                                                <div class="tw-flex tw-items-center">
                                                    <div class="tw-w-8 tw-h-8 tw-bg-gray-200 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mr-3">
                                                        <i class="fa fa-cube tw-text-gray-500"></i>
                                                    </div>
                                                    <div>
                                                        <div class="tw-font-medium tw-text-gray-900">{{ $product->name }}</div>
                                                        @if(isset($product->description) && $product->description)
                                                            <div class="tw-text-sm tw-text-gray-500">{{ Str::limit($product->description, 50) }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded tw-text-sm tw-font-mono tw-text-gray-700">
                                                    {{ $product->sku }}
                                                </span>
                                            </td>
                                            <td>
                                                @if(isset($product->category_name) && $product->category_name)
                                                    <span class="tw-bg-blue-100 tw-text-blue-800 tw-px-2 tw-py-1 tw-rounded tw-text-sm">
                                                        <i class="fa fa-tag tw-mr-1"></i>
                                                        {{ $product->category_name }}
                                                    </span>
                                                @else
                                                    <span class="tw-text-gray-400 tw-text-sm">No category</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning tw-text-white tw-px-3 tw-py-1">
                                                    <i class="fa fa-clock-o tw-mr-1"></i>
                                                    Not Added
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-xs btn-primary add-to-inventory-btn" 
                                                            data-product-name="{{ $product->name }}"
                                                            data-product-id="{{ $product->id }}"
                                                            onclick="handleAddToInventory('{{ $product->name }}', {{ $product->id }})"
                                                            title="Go to Products List">
                                                        <i class="fa fa-plus"></i> Products
                                                    </button>
                                                    <button type="button" class="btn btn-xs btn-info" title="View Details">
                                                        <i class="fa fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-xs btn-warning" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center tw-py-8">
                                                <div class="tw-flex tw-flex-col tw-items-center tw-justify-center">
                                                    <i class="fa fa-check-circle tw-text-4xl tw-text-green-500 tw-mb-4"></i>
                                                    <h4 class="tw-text-lg tw-font-semibold tw-text-gray-700 tw-mb-2">No Pending Products</h4>
                                                    <p class="tw-text-gray-500">All products have been successfully added to inventory.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($products->count() > 0)
                        <div class="box-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="tw-text-sm tw-text-gray-600">
                                        <i class="fa fa-info-circle tw-mr-1"></i>
                                        Showing {{ $products->count() }} products that need to be added to inventory
                                    </p>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button type="button" class="btn btn-warning">
                                        <i class="fa fa-download"></i> Export List
                                    </button>
                                    <button type="button" class="btn btn-primary">
                                        <i class="fa fa-plus"></i> Add Selected
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@push('js')
<script>
console.log('None Added Products page loaded');

$(document).ready(function() {
    console.log('Document ready - jQuery version:', $.fn.jquery);
    
    // Add CSS styles first
    var css = `
        <style>
            .add-to-inventory-btn {
                background-color: #007bff !important;
                border-color: #007bff !important;
                color: white !important;
                font-weight: 500;
                transition: all 0.3s ease;
                cursor: pointer !important;
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
    
    // Initialize DataTable
    var table;
    if ($('#none_added_product_table').length) {
        table = $('#none_added_product_table').DataTable({
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
            ],
            "drawCallback": function() {
                console.log('DataTable redrawn, buttons count:', $('.add-to-inventory-btn').length);
                // Re-attach event handlers after DataTable redraw
                attachButtonHandlers();
            }
        });
        console.log('DataTable initialized');
    }
    
    // Function to attach button handlers
    function attachButtonHandlers() {
        console.log('Attaching button handlers...');
        
        // Remove any existing handlers first
        $(document).off('click', '.add-to-inventory-btn');
        
        // Attach new handlers using event delegation
        $(document).on('click', '.add-to-inventory-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            console.log('Button clicked!');
            console.log('Event target:', e.target);
            console.log('Current element:', this);
            
            var productName = $(this).data('product-name');
            console.log('Product name:', productName);
            
            // Show confirmation dialog
            if (confirm('Do you want to go to the Products list page?')) {
                console.log('User confirmed, redirecting...');
                window.location.href = "/products";
            } else {
                console.log('User cancelled');
            }
            
            return false;
        });
        
        // Also try direct binding as backup
        $('.add-to-inventory-btn').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Direct binding - Button clicked!');
            
            var productName = $(this).data('product-name');
            console.log('Product name (direct):', productName);
            
            if (confirm('Do you want to go to the Products list page?')) {
                console.log('User confirmed, redirecting...');
                window.location.href = "/products";
            }
            
            return false;
        });
        
        console.log('Button handlers attached');
    }
    
    // Initial attachment of handlers
    attachButtonHandlers();
    
    // Test if buttons are clickable after DataTable initialization
    setTimeout(function() {
        console.log('Final button count:', $('.add-to-inventory-btn').length);
        $('.add-to-inventory-btn').each(function(index) {
            console.log('Button ' + index + ':', $(this).text(), 'Product:', $(this).data('product-name'));
            // Test if button is clickable
            $(this).on('click', function() {
                console.log('Test click on button ' + index);
            });
        });
    }, 1000);
    
    // Additional debugging - log all clicks on the table
    $('#none_added_product_table').on('click', function(e) {
        console.log('Table clicked:', e.target);
        if ($(e.target).hasClass('add-to-inventory-btn')) {
            console.log('Found add-to-inventory-btn in table click');
        }
    });
});
</script>
@endpush
