@extends('layouts.main')

@section('title', 'Ticket Refund Others Edit')

@section('header')
    @include('inc.header')
@endsection

@section('sidebar')
    @include('inc.sidebar')
@endsection

@section('content')
    <div class="uk-grid">
        <div class="uk-width-large-10-10">
            <div class="uk-grid uk-grid-medium" data-uk-grid-margin>
                <div class="uk-width-xLarge-10-10 uk-width-large-10-10">
                    <div class="md-card">
                        <div class="user_heading">
                            <div class="user_heading_avatar fileinput fileinput-new" data-provides="fileinput">
                                <div class="fileinput-preview fileinput-exists thumbnail"></div>
                            </div>
                            <div class="user_heading_content">
                                <h2 class="heading_b"><span class="uk-text-truncate">Edit Ticket Refund Others</span></h2>
                                
                            </div>
                        </div>
                        <div class="md-card">
                            {!! Form::open(['url' => route('ticket_refund_others_update' , $refund->id), 'method' => 'POST']) !!}
                            <div class="user_content">
                                <div class="uk-margin-top">

                                    <div class="uk-grid" data-uk-grid-margin>
                                        <div class="uk-width-medium-1-5 uk-vertical-align">
                                            <label class="uk-vertical-align-middle" for="returnflightarrivalDate">Date <span style="color: red">*</span></label>
                                        </div>
                                        <div class="uk-width-medium-2-5">

                                            <label for="returnflightarrivalDate">Date</label>
                                            <input class="md-input" type="text" id="date" name="date" data-uk-datepicker="{format:'YYYY-MM-DD'}" value="{{ $refund['date'] }}" required/>
                                            @if($errors->has('date'))
                                                <div class="uk-text-danger">{{ $errors->first('date') }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="uk-grid" data-uk-grid-margin>

                                        <div class="uk-width-medium-1-5 uk-vertical-align">
                                            <label class="uk-vertical-align-middle" for="contact_id">ADM Fee <span style="color: red">*</span></label>
                                        </div>
                                        <div class="uk-width-medium-2-5">
                                            <label for="adm_fee">ADM Fee</label>
                                            <input class="md-input" type="number" id="adm_fee" name="adm_fee" step="0.01" value="{{ $refund['adm_fee'] }}" oninput="amdAmount();"/>
                                            
                                            @if($errors->has('adm_fee'))
                                                <div class="uk-text-danger">{{ $errors->first('adm_fee') }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="uk-grid" data-uk-grid-margin>

                                        <div class="uk-width-medium-1-5 uk-vertical-align">
                                            <label class="uk-vertical-align-middle" for="contact_id">Difference of Airline Commission <span style="color: red">*</span></label>
                                        </div>
                                        <div class="uk-width-medium-2-5">
                                            <label for="difference_of_airline_commission">Difference of Airline Commission</label>
                                            <input class="md-input" type="number" id="difference_of_airline_commission" name="difference_of_airline_commission" step="0.01" value="{{ $refund['difference_of_airline_commission'] }}" oninput="amount();"/>
                                            
                                            @if($errors->has('difference_of_airline_commission'))
                                                <div class="uk-text-danger">{{ $errors->first('difference_of_airline_commission') }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <br>

                                    @if($errors->has('invoice_rate') ||$errors->has('invoice_qty'))

                                        <span style="font-weight: 400; color:red; position: relative; right:0px">{!! "Invoice field required" !!}</span>

                                    @endif

                                    <div class="uk-grid" >
                                        <div class="uk-width-1-2" >
                                         <div style=" padding:10px;height: 40px; color: white; background-color: maroon">
                                             Invoice <span style="color:gold"> {{ $refund->invoice?'#'.$refund->invoice['invoice_number']:'' }} </span>
                                         </div>

                                       </div>
                                        <div class="uk-width-1-2" style="padding: 10px; height: 40px; position:relative;background: maroon ">
                                           <div id="inv" style="position: absolute; right: 10px; height: 40px; ">
                                               <input type="checkbox"  id="checkbox_invoice" {{ $refund->invoice?'':"name=check_invoice" }}   style=" margin-top: -1px; height: 25px; width: 20px;" />
                                           </div>

                                        </div>
                                    </div>
                                    <div class="uk-grid" style="display: none;" id="invoice_details">
                                        <div class="uk-width-1-1" >

                                            <div class="uk-grid" data-uk-grid-margin>

                                                <div class="uk-width-medium-1-5 uk-vertical-align">
                                                    <label class="uk-vertical-align-middle" for="contact_id">Contact Name <span style="color: red">*</span></label>
                                                </div>
                                                <div class="uk-width-medium-2-5">
                                                    <select {{ $refund->invoice?"disabled":null }} data-md-selectize data-md-selectize-bottom data-uk-tooltip="{pos:'top'}" title="Select Customer" id="contact_id" name="contact_id">
                                                        <option value="" selected>Select Customer</option>
                                                        @foreach($contact as $value)
                                                            @if($refund->invoice)
                                                                @if($refund->invoice['customer_id']==$value['id'])
                                                                <option value=" {{ $value->id }} " selected> {{ $value->display_name }} </option>
                                                                @endif
                                                            @else
                                                                <option value=" {{ $value->id }} " > {{ $value->display_name }} </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    @if($errors->has('contact_id'))
                                                        <div class="uk-text-danger">{{ $errors->first('contact_id') }}</div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="uk-grid">
                                                <div class="uk-width-medium-1-5 uk-vertical-align">
                                                    <label class="uk-vertical-align-middle" for="documentNumber">Select particular <span style="color: red">*</span></label>
                                                </div>
                                                <div class="uk-width-medium-2-5">

                                                    <select {{ $refund->invoice?"disabled":null }}  name="invoice_particular" id="invoice_item" class="md-input" data-md-selectize data-md-selectize-bottom data-uk-tooltip="{pos:'top'}" title="Select Item">
                                                        <option value="" disabled selected hidden>Select...</option>
                                                        @foreach($item as $value)
                                                          @if($refund->invoice)
                                                              @if($refund->invoice['OrderInvoiceEntries']['item_id']==$value['id'])
                                                                <option {{ "selected" }} value="{{ $value->id }}">{{ $value->item_name }}</option>

                                                              @endif

                                                          @endif
                                                          @if(!$refund->invoice &&  $refund->departureSector)
                                                                  <option {{ $refund->departureSector==$value->item_name?"selected":null }} value="{{ $value->id }}">{{ $value->item_name }}</option>
                                                          @endif
                                                            <option  value="{{ $value->id }}">{{ $value->item_name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @if($errors->has('invoice_particular'))
                                                        <div class="uk-text-danger">{{ $errors->first('invoice_particular') }}</div>
                                                    @endif

                                                </div>

                                            </div>

                                            <div class="uk-grid">
                                                <div class="uk-width-medium-1-5 uk-vertical-align">
                                                    <label class="uk-vertical-align-middle" for="Quantity">Quantity <span style="color: red">*</span></label>
                                                </div>
                                                <div class="uk-width-medium-2-5">

                                                    <label for="Quantity">Quantity </label>
                                                    <input {{ $refund->invoice?'readonly':"" }} class="md-input" type="number" id="Quantity" name="invoice_qty" value="{{ $refund->invoice?$refund->invoice['OrderInvoiceEntries']['quantity']:'' }}"/>
                                                    @if($errors->has('invoice_qty'))
                                                        <div class="uk-text-danger">{{ $errors->first('invoice_qty') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="uk-grid">
                                                <div class="uk-width-medium-1-5 uk-vertical-align">
                                                    <label class="uk-vertical-align-middle" for="Rate">Rate <span style="color: red">*</span></label>
                                                </div>
                                                <div class="uk-width-medium-2-5">

                                                    <input {{ $refund->invoice?'readonly':"" }}  class="md-input" type="number" id="Rate" name="invoice_rate" value="{{ $refund->invoice?$refund->invoice['OrderInvoiceEntries']['rate']:'' }}"/>
                                                    @if($errors->has('invoice_rate'))
                                                        <div class="uk-text-danger">{{ $errors->first('invoice_rate') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($errors->has('bill_rate') ||$errors->has('bill_qty'))

                                        <span style="color:red; position: relative; right:0px">{!! "Bill field required" !!}</span>

                                    @endif

                                    <div class="uk-grid" >
                                        <div class="uk-width-1-2" >
                                            <div style=" padding:10px;height: 40px; color: white; background-color: #2D2D2D ">
                                                Bill <span style="color:gold"> {{ $refund->bill?'#'.$refund->bill['bill_number']:'' }} </span>
                                            </div>

                                        </div>
                                        <div class="uk-width-1-2" style="padding: 10px; height: 40px; position:relative;background: #2D2D2D ">
                                            <div id="inv" style="position: absolute; right: 10px; height: 40px; ">
                                                <input type="checkbox"  id="checkbox_bill" {{ $refund->bill?'':"name=check_bill" }}   style=" margin-top: -1px; height: 25px; width: 20px;" />
                                            </div>

                                        </div>
                                    </div>
                                    <div class="uk-grid" style="display: none;" id="bill_details">
                                        <div class="uk-width-1-1" >

                                            <div class="uk-grid" data-uk-grid-margin>

                                                <div class="uk-width-medium-1-5 uk-vertical-align">
                                                    <label class="uk-vertical-align-middle" for="vendor_id">Vendor Name <span style="color: red">*</span></label>
                                                </div>
                                                <div class="uk-width-medium-2-5">
                                                    <select {{ $refund->bill?'disabled':"" }} data-md-selectize data-md-selectize-bottom data-uk-tooltip="{pos:'top'}" title="Select Vendor" id="vendor_id" name="vendor_id">
                                                        <option value="" selected>Select Vendor</option>
                                                        @foreach($contact as $value)
                                                            @if($refund->bill)
                                                                @if($refund->bill['vendor_id']==$value['id'])
                                                                <option value=" {{ $value->id }} " selected> {{ $value->display_name }} </option>
                                                                @endif
                                                            @else
                                                                <option value=" {{ $value->id }} " > {{ $value->display_name }} </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    @if($errors->has('vendor_id'))
                                                        <div class="uk-text-danger">{{ $errors->first('vendor_id') }}</div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="uk-grid">
                                                <div class="uk-width-medium-1-5 uk-vertical-align">
                                                    <label class="uk-vertical-align-middle" for="documentNumber">Select particular <span style="color: red">*</span></label>
                                                </div>
                                                <div class="uk-width-medium-2-5">

                                                    <select {{ $refund->bill?'disabled':"" }}  name="bill_particular" id="bill_item" class="md-input" data-md-selectize data-md-selectize-bottom data-uk-tooltip="{pos:'top'}" title="Select Item">
                                                        <option value="" disabled selected hidden>Select...</option>
                                                        @foreach($item as $value)
                                                            @if($refund->bill)
                                                                @if($refund->bill['OrderbillEntries']['item_id']==$value['id'])
                                                                    <option {{ "selected" }} value="{{ $value->id }}">{{ $value->item_name }}</option>
                                                                @endif
                                                            @endif
                                                                @if(!$refund->bill &&  $refund->departureSector)
                                                                    <option {{ $refund->departureSector==$value->item_name?"selected":null }} value="{{ $value->id }}">{{ $value->item_name }}</option>
                                                                @endif
                                                            <option value="{{ $value->id }}">{{ $value->item_name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @if($errors->has('bill_particular'))
                                                        <div class="uk-text-danger">{{ $errors->first('bill_particular') }}</div>
                                                    @endif
                                                </div>

                                            </div>

                                            <div class="uk-grid">
                                                <div class="uk-width-medium-1-5 uk-vertical-align">

                                                    <label class="uk-vertical-align-middle" for="bill_quantity">Quantity <span style="color: red">*</span></label>
                                                </div>
                                                <div class="uk-width-medium-2-5">

                                                    <label id="lavel_quantity" for="bill_quantity">Quantity</label>
                                                    <input {{ $refund->bill?'readonly':"" }} class="md-input" type="number" id="bill_quantity" name="bill_qty" value="{{ $refund->bill?$refund->bill['OrderbillEntries']['quantity']:'' }}"/>
                                                    @if($errors->has('bill_qty'))
                                                        <div class="uk-text-danger">{{ $errors->first('bill_qty') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="uk-grid">
                                                <div class="uk-width-medium-1-5 uk-vertical-align">
                                                    <label class="uk-vertical-align-middle" for="Rate">Rate <span style="color: red">*</span></label>
                                                </div>
                                                <div class="uk-width-medium-2-5">

                                                    <input step="any" {{ $refund->bill?'readonly':"" }} class="md-input" type="number" id="bill_Rate" name="bill_rate" value="{{ $refund->bill?$refund->bill['OrderbillEntries']['rate']:'' }}"/>
                                                    @if($errors->has('bill_rate'))
                                                        <div class="uk-text-danger">{{ $errors->first('bill_rate') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    

                                    <div class="uk-grid uk-ma" data-uk-grid-margin>
                                        <div class="uk-width-1-1 uk-float-left">
                                            <input type="submit" class="md-btn md-btn-primary" value="confirm" name="confirm" />
                                            
                                        </div>
                                    </div>

                                </div>
                            </div>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
<script>

    $("#checkbox_invoice").on("click",function () {
        $("#invoice_details").toggle(800);
    });

    $("#checkbox_bill").on("click",function () {
        $("#bill_details").toggle(800);

    });

    // function amount(){
    //     var amount = $('#difference_of_airline_commission').val();
    //     var adm_fee = $('#adm_fee').val();

    //     amount = (amount)?amount:0;

    //     $('#bill_Rate').val(parseFloat(adm_fee));
        
    //     if(amount<0){
    //         $('#Rate').val(Math.abs(amount));
    //         $('#bill_Rate').val(parseFloat(adm_fee));
    //     }
    //     else if(amount>=0){
    //         $('#Rate').val('');
    //         $('#bill_Rate').val(parseFloat(adm_fee) + parseFloat(amount));
    //     }
    // }

    // function amdAmount(){
    //     var amount = $('#difference_of_airline_commission').val();
    //     var adm_fee = $('#adm_fee').val();

    //     amount = (amount)?amount:0;

    //     $('#bill_Rate').val(parseFloat(adm_fee));  

    //     if(amount<0){
    //         $('#Rate').val(Math.abs(amount));
    //         $('#bill_Rate').val(parseFloat(adm_fee));
    //     }
    //     else if(amount>=0){
    //         $('#Rate').val('');
    //         $('#bill_Rate').val(parseFloat(adm_fee) + parseFloat(amount));
    //     }
    // }
    
    $('#sidebar_ticket_all_refund').addClass('act_item');
    $('#sidebar_ticketing').addClass('current_section');
    $(window).load(function(){
        $("#tiktok").trigger('click');
    })
</script>


@endsection