<div class="modal fade in" tabindex="-1" role="dialog" id="return_payment_modal">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Returning Payment</h4>
			</div>
			<div class="modal-body">
                {{-- @php

                    $transaction = request()->session()->get('transaction');
                    echo '<pre>';
                    print_r($transaction);
                    echo '</pre>';
                @endphp --}}
               
				<div class="box box-solid bg-orange">
                    <div class="box-body">
                        <div class="col-md-3">
                             <hr>
                            <strong>
                                @lang('lang_v1.total_items'):
                            </strong>
                            <br />
                            <span class="lead text-bold total_quantity" id="total_quantity"></span>
                        </div>

                        <div class="col-md-3">
                            <hr>
                            <strong>
                                @lang('sale.tax') (<span id="tax_label_span"></span>):
                            </strong>
                            <br />
                            <span class="lead text-bold total_payable_span" id="tax_span"></span>
                        </div>
                        <div class="col-md-3">
                            <hr>
                            <strong>
                                @lang('sale.total_payable'):
                            </strong>
                            <br />
                            <span class="lead text-bold total_payable_span" id="total_payable_span"></span>
                        </div>

                        <div class="col-md-3">
                            <hr>
                            <strong>
                                @lang('lang_v1.total_paying'):
                            </strong>
                            <br />
                            <span class="lead text-bold total_paying" id="total_paying"></span>
                            <input type="hidden" id="total_paying_input">
                        </div>

                        <div class="col-md-3">
                            <hr>
                            <strong>
                                @lang('lang_v1.change_return'):
                            </strong>
                            <br />
                            <span class="lead text-bold change_return_span text-red" id="change_return_span"></span>
                           
                            
                        </div>

                    </div>
                            <!-- /.box-body -->
                        </div>
			</div>
			<div class="modal-footer">
			    <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->