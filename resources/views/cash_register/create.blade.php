@extends('layouts.app')
@section('title',  __('cash_register.open_cash_register'))

@section('content')
<style type="text/css">



</style>
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('cash_register.open_cash_register')</h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
{!! Form::open(['url' => action([\App\Http\Controllers\CashRegisterController::class, 'store']), 'method' => 'post', 
'id' => 'add_cash_register_form' ]) !!}
  <div class="box box-solid">
    <div class="box-body">
    <br><br><br>
    <input type="hidden" name="sub_type" value="{{$sub_type}}">
      <div class="row">
        @if($business_locations->count() > 0)
        <div class="col-sm-8 col-sm-offset-2">
          <div class="form-group">
            {!! Form::label('amount', __('cash_register.cash_in_hand') . ':*') !!}
            {!! Form::text('amount', null, ['class' => 'form-control input_number',
              'placeholder' => __('cash_register.enter_amount'), 'required']); !!}
          </div>
        </div>
        @if(count($business_locations) > 1)
        <div class="clearfix"></div>
        <div class="col-sm-8 col-sm-offset-2">
          <div class="form-group">
            {!! Form::label('location_id', __('business.business_location') . ':') !!}
              {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
              'placeholder' => __('lang_v1.select_location')]); !!}
          </div>
        </div>
        @else
          {!! Form::hidden('location_id', array_key_first($business_locations->toArray()) ); !!}
        @endif
        <div class="col-sm-8 col-sm-offset-2">
          <div class="form-group">
            @php
              $business_id = request()->session()->get('user.business_id');
              $devices = \App\Device::where('business_id', $business_id)
                ->whereNotIn('id', function($query) {
                    $query->select('device_id')
                        ->from('cash_registers')
                        ->where('status', 'open')
                        ->whereNotNull('device_id');
                })
                ->get();
            @endphp
            {!! Form::label('device_id', __('Station') . ':') !!}
            {!! Form::select('device_id', ['' => __('Select Station')] + $devices->pluck('device_friendly_name', 'id')->toArray(), null, ['class' => 'form-control select2',
            'required']); !!}
            @if($devices->count() == 0 && auth()->user()->can('device.view'))
              <div class="alert">
                @lang('No stations available. Please create a device first.')
                <a href="{{ action([\App\Http\Controllers\DeviceController::class, 'index']) }}" class="btn btn-primary btn-sm">
                  @lang('Create Device')
                </a>
              </div>
            @endif
          </div>
        </div>
        <div class="col-sm-8 col-sm-offset-2">
          <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white pull-right">@lang('cash_register.open_register')</button>
        </div>
        @else
        <div class="col-sm-8 col-sm-offset-2 text-center">
          <h3>@lang('lang_v1.no_location_access_found')</h3>
        </div>
      @endif
      </div>
      <br><br><br>
    </div>
  </div>
  {!! Form::close() !!}
</section>
<!-- /.content -->
@endsection