<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action([\App\Http\Controllers\ProductController::class, 'saveQuickProduct']), 'method' => 'post', 'id' => 'quick_add_product_form' ]) !!}

    <div class="modal-header">
	    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	      <h4 class="modal-title" id="modalTitle">@lang( 'product.add_new_product' )</h4>
    </div>
    <div class="modal-body">
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
             {!! Form::label('name', __('product.product_name') . ':') !!}
              {!! Form::text('name', $product_name, ['class' => 'form-control',
              'placeholder' => __('product.product_name')]); !!}
          </div>
        </div>

        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('sku', __('product.sku') . ':') !!} @show_tooltip(__('tooltip.sku'))
            {!! Form::text('sku', null, ['class' => 'form-control',
              'placeholder' => __('product.sku')]); !!}
          </div>
        </div>
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('unit_id', __('product.unit') . ':') !!}
              {!! Form::select('unit_id', $units, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
          </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-8">
          <div class="form-group">
            {!! Form::label('product_description', __('lang_v1.product_description') . ':') !!}
              {!! Form::textarea('product_description', null, ['class' => 'form-control']); !!}
          </div>
        </div>
        <div class="clearfix"></div>
      </div>
      <div class="row">
        <div class="form-group col-sm-11 col-sm-offset-1">
          @include('product.partials.single_product_form_part', ['profit_percent' => $default_profit_percent, 'quick_add' => true, 'only_default_selling_price' => true ])
        </div>
      </div>
    </div>
    <div class="form-group" style="margin-left: 20px;">
      <div class="checkbox">
        <label>
          {!! Form::checkbox('add_to_product_list', 1, false, ['id' => 'add_to_product_list']) !!} @lang('Add to Product List')
        </label>
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="submit_quick_product">@lang( 'messages.save' )</button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script type="text/javascript">
  $(document).ready(function(){

     // Focus on Exc. Tax field when modal is shown
    $('.quick_add_product_modal').on('shown.bs.modal', function () {
      // Set default value for Exc. Tax field
      $('#single_dsp').val('0.00');
      
      // Focus on the field and position cursor at the end
      $('#single_dsp').focus();
      
      // Position cursor at the end of the value
      var input = $('#single_dsp')[0];
      input.setSelectionRange(input.value.length, input.value.length);
    });
    
    // Handle cursor positioning when user clicks on the field
    $('#single_dsp').on('click', function() {
      var input = this;
      // Position cursor at the end
      input.setSelectionRange(input.value.length, input.value.length);
    });
    
    // Handle cursor positioning when user starts typing
    $('#single_dsp').on('input', function() {
      var input = this;
      var cursorPos = input.selectionStart;
      var currentValue = input.value;
      
      // If user starts typing and the value is "0.00", clear it and start fresh
      if (currentValue === '0.00' && cursorPos === 0) {
        input.value = '';
        input.setSelectionRange(0, 0);
        return;
      }
      
      // Convert input to decimal format
      var numericValue = currentValue.replace(/[^\d]/g, ''); // Remove non-digits
      
      if (numericValue.length > 0) {
        // Convert to decimal: "5" -> "0.05", "59" -> "0.59", "123" -> "1.23"
        var decimalValue = (parseInt(numericValue) / 100).toFixed(2);
        input.value = decimalValue;
        
        // Position cursor at the end
        setTimeout(function() {
          input.setSelectionRange(input.value.length, input.value.length);
        }, 0);
      }
    });
    
    // Handle keydown to ensure proper decimal conversion and Enter submission
    $('#single_dsp').on('keydown', function(e) {
      var input = this;
      
      // Handle Enter key - submit the form
      if (e.keyCode === 13) {
        e.preventDefault();
        $('#quick_add_product_form').submit();
        return false;
      }
      
      // Allow navigation keys (arrows, home, end, etc.)
      if ([37, 38, 39, 40, 35, 36, 8, 46].indexOf(e.keyCode) !== -1) {
        return true;
      }
      
      // Allow only numbers and decimal point
      if (e.keyCode >= 48 && e.keyCode <= 57 || e.keyCode === 190 || e.keyCode === 110) {
        // Let the input event handle the conversion
        return true;
      }
      
      // Block other keys
      return false;
    });

    $("form#quick_add_product_form").validate({
      rules: {
        unit_id: {
              required: false // Make unit optional
          },
          sku: {
              remote: {
                  url: "/products/check_product_sku",
                  type: "post",
                  data: {
                      sku: function() {
                          return $( "#sku" ).val();
                      },
                      product_id: function() {
                          if($('#product_id').length > 0 ){
                              return $('#product_id').val();
                          } else {
                              return '';
                          }
                      },
                  }
              }
          }
      },
      messages: {
          sku: {
              remote: LANG.sku_already_exists
          }
      },
      submitHandler: function (form) {
        var form = $("form#quick_add_product_form");
        var addToProductList = $('#add_to_product_list').is(':checked');
        if (addToProductList) {
          var url = form.attr('action');
          form.find('button[type="submit"]').attr('disabled', true);
          $.ajax({
              method: "POST",
              url: url,
              dataType: 'json',
              data: $(form).serialize(),
              success: function(data){
                  $('.quick_add_product_modal').modal('hide');
                  if( data.success){
                      toastr.success(data.msg);
                      if (typeof get_purchase_entry_row !== 'undefined') {
                        var selected_location = $('#location_id').val();
                        var location_check = true;
                        if (data.locations && selected_location && data.locations.indexOf(selected_location) == -1) {
                          location_check = false;
                        }
                        if (location_check) {
                          get_purchase_entry_row( data.product.id, 0 );
                        }
                      }
                      // This triggers the product to be added to POS UI
                      $(document).trigger({type: "quickProductAdded", 'product': data.product, 'variation': data.variation });
                  } else {
                      toastr.error(data.msg);
                  }
              }
          });
        } else {
          // If not checked, first get or create the 'Not Added Products' category, then save the product with that category
          var formData = form.serializeArray();
          // Step 1: Get or create the category
          $.ajax({
            method: "POST",
            url: "/products/get_or_create_not_added_category",
            dataType: 'json',
            data: {},
            success: function(catResp) {
              if (catResp.success && catResp.category_id) {
                // Step 2: Add category_id to form data
                formData.push({name: 'category_id', value: catResp.category_id});
                // Step 3: Save the product
                var url = form.attr('action');
                form.find('button[type="submit"]').attr('disabled', true);
                $.ajax({
                  method: "POST",
                  url: url,
                  dataType: 'json',
                  data: formData,
                  success: function(data){
                    $('.quick_add_product_modal').modal('hide');
                    if( data.success){
                        toastr.success(data.msg);
                        if (typeof get_purchase_entry_row !== 'undefined') {
                          var selected_location = $('#location_id').val();
                          var location_check = true;
                          if (data.locations && selected_location && data.locations.indexOf(selected_location) == -1) {
                            location_check = false;
                          }
                          if (location_check) {
                            get_purchase_entry_row( data.product.id, 0 );
                          }
                        }
                        // This triggers the product to be added to POS UI
                        $(document).trigger({type: "quickProductAdded", 'product': data.product, 'variation': data.variation });
                    } else {
                        toastr.error(data.msg);
                    }
                  }
                });
              } else {
                toastr.error('Could not create or find the Not Added Products category.');
              }
            },
            error: function() {
              toastr.error('Could not create or find the Not Added Products category.');
            }
          });
        }
        return false;
      }
    });
  });
</script>
</script>