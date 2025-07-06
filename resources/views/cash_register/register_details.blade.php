<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    <div class="modal-header mini_print">
      <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h3 class="modal-title">@lang( 'cash_register.register_details' ) ( {{ \Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->format('jS M, Y h:i A') }} -  {{\Carbon::createFromFormat('Y-m-d H:i:s', $close_time)->format('jS M, Y h:i A')}} )</h3>
    </div>

    <div class="modal-body">
      @include('cash_register.payment_details')
      <hr>
      @if(!empty($register_details->denominations))
        @php
          $total = 0;
        @endphp
        <div class="row">
          <div class="col-md-8 col-sm-12">
            <h3>@lang( 'lang_v1.cash_denominations' )</h3>
            <table class="table table-slim">
              <thead>
                <tr>
                  <th width="20%" class="text-right">@lang('lang_v1.denomination')</th>
                  <th width="20%">&nbsp;</th>
                  <th width="20%" class="text-center">@lang('lang_v1.count')</th>
                  <th width="20%">&nbsp;</th>
                  <th width="20%" class="text-left">@lang('sale.subtotal')</th>
                </tr>
              </thead>
              <tbody>
                @foreach($register_details->denominations as $key => $value)
                <tr>
                  <td class="text-right">{{$key}}</td>
                  <td class="text-center">X</td>
                  <td class="text-center">{{$value ?? 0}}</td>
                  <td class="text-center">=</td>
                  <td class="text-left">
                    @format_currency($key * $value)
                  </td>
                </tr>
                @php
                  $total += ($key * $value);
                @endphp
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="4" class="text-center">@lang('sale.total')</th>
                  <td>@format_currency($total)</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      @endif
      
      <div class="row">
        <div class="col-xs-6">
          <b>@lang('report.user'):</b> {{ $register_details->user_name}}<br>
          <b>@lang('business.email'):</b> {{ $register_details->email}}<br>
          <b>@lang('business.business_location'):</b> {{ $register_details->location_name}}<br>
          <b>@lang('lang_v1.device_id'):</b> {{ $register_details->device_serial_number ?? 'Not Assigned'}}<br>
          <b>@lang('lang_v1.station_name'):</b> {{ $register_details->station_name ?? 'Not Assigned'}}<br>
        </div>
        @if(!empty($register_details->closing_note))
          <div class="col-xs-6">
            <strong>@lang('cash_register.closing_note'):</strong><br>
            {{$register_details->closing_note}}
          </div>
        @endif
      </div>
    </div>

    <div class="modal-footer">
  <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print print-mini-button" 
          aria-label="Print">
      <i class="fa fa-print"></i> @lang('messages.print_mini')
  </button>
      <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print" 
        aria-label="Print" 
          onclick="$(this).closest('div.modal').printThis();">
        <i class="fa fa-print"></i> @lang( 'messages.print_detailed' )
      </button>

      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" 
        data-dismiss="modal">@lang( 'messages.cancel' )
      </button>
    </div>

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
<style type="text/css">
  @media print {
    .modal {
        position: absolute;
        left: 0;
        top: 0;
        margin: 0;
        padding: 0;
        overflow: visible!important;
    }
}
</style>
<script>
  $(document).ready(function () {
      $(document).on('click', '.print-mini-button', function () {
          // Create a temporary container for mini print
          var miniPrintContainer = $('<div>').addClass('mini_print_temp');
          
          // Clone only the header (modal title)
          var headerContent = $('.modal-header.mini_print').clone();
          miniPrintContainer.append(headerContent);
          
          // Add payment details (without duplicating)
          var paymentDetails = $('.modal-body .mini_print').clone();
          miniPrintContainer.append(paymentDetails);
          
          // Add user information to the mini print
          var userInfo = $('<div>').addClass('row').html(`
            <div class="col-xs-12">
              @if(!empty($register_details->user_name))
                <strong>@lang('report.user'):</strong> {{$register_details->user_name}}<br>
              @endif
              @if(!empty($register_details->email))
                <strong>@lang('business.email'):</strong> {{$register_details->email}}<br>
              @endif
              @if(!empty($register_details->location_name))
                <strong>@lang('business.business_location'):</strong> {{$register_details->location_name}}<br>
              @endif
              @if(!empty($register_details->device_serial_number))
                <strong>@lang('lang_v1.device_id'):</strong> {{$register_details->device_serial_number}}<br>
              @endif
              @if(!empty($register_details->station_name))
                <strong>@lang('lang_v1.station_name'):</strong> {{$register_details->station_name}}<br>
              @endif
            </div>
          `);
          
          miniPrintContainer.append(userInfo);
          
          // Print the temporary container
          miniPrintContainer.printThis();
          
          // Remove the temporary container
          miniPrintContainer.remove();
      });
  });
</script>