@extends('layouts.main')

@section('title', 'Inventory Category')

@section('header')
    @include('inc.header')
@endsection

@section('sidebar')
    @include('inc.sidebar')
@endsection
@section('top_bar')
<div id="top_bar">
    <div class="md-top-bar">
        <ul id="menu_top" class="uk-clearfix">
            <li data-uk-dropdown class="uk-hidden-small">
                <a href="#"><i class="material-icons">&#xE02E;</i><span>@lang('trans.inventory')</span></a>
                <div class="uk-dropdown">
                    <ul class="uk-nav uk-nav-dropdown">
                        <li><a href="{{route('inventory_create')}}">@lang('trans.create_inventory')</a></li>
                        <li><a href="{{route('inventory')}}">@lang('trans.all_inventory')</a></li>
                    </ul>
                </div>
            </li>

            <li data-uk-dropdown class="uk-hidden-small">
                <a href="#"><i class="material-icons">&#xE02E;</i><span>@lang('trans.category')</span></a>
                <div class="uk-dropdown uk-dropdown-scrollable">
                    <ul class="uk-nav uk-nav-dropdown">
                        <li><a href="{{route('inventory_category')}}">@lang('trans.all_category')</a></li>
                    </ul>
                </div>
            </li>
            <li data-uk-dropdown class="uk-hidden-small">
                <a href="#"><i class="material-icons">&#xE02E;</i><span>@lang('trans.manage_sub_category')</span></a>
                <div class="uk-dropdown uk-dropdown-scrollable">
                    <ul class="uk-nav uk-nav-dropdown">
                        <li><a href="{{ route('inventory_sub_category') }}">@lang('trans.all_sub_category')</a></li>
                    </ul>
                </div>
            </li>

            <li data-uk-dropdown class="uk-hidden-small">
                <a href="{{route('stock_create')}}"><i class="material-icons">&#xE02E;</i><span>@lang('trans.add_stock')</span></a>
            </li>
        </ul>
    </div>
</div>
@endsection
@section('content')
    <div class="uk-grid" data-uk-grid-margin data-uk-grid-match id="user_profile">
        <div class="uk-width-large-10-10">
            <div class="uk-grid" data-uk-grid-margin>
                <div class="uk-width-large-10-10">
                    <div class="md-card">
                        <div class="user_heading" data-uk-sticky="{ top: 48, media: 960 }">
                            <div class="user_heading_avatar fileinput fileinput-new" data-provides="fileinput">
                                <div class="fileinput-preview fileinput-exists thumbnail"></div>
                            </div>
                            <div class="user_heading_content">
                                <h2 class="heading_b"><span class="uk-text-truncate">@lang('trans.update_sub_category')</span></h2>
                            </div>
                        </div>
                        <div class="user_content">
                            <div class="uk-margin-top">
                                {!! Form::open(['url' => route('inventory_sub_category_update', ['id' => $categoryById->id]), 'method' => 'POST', 'name' => 'editSubCategoryForm' ]) !!}
                                <div class="uk-grid" data-uk-grid-margin>
                                    <div class="uk-width-medium-1-5 uk-vertical-align">
                                        <label for="item_category_name" class="uk-vertical-align-middle">@lang('trans.category_name')</label>
                                    </div>
                                    <div class="uk-width-medium-2-5">
                                        <select id="item_category_id" name="item_category_id" data-md-selectize required>
                                            <option value="">@lang('trans.select_category_name')</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}"> @if(Session::get('locale') == 'bn') {{ $category->item_category_name }} @else {{ $category->item_category_name }} @endif </option>
                                            @endforeach
                                        </select>
                                        @if($errors->first('item_category_id'))
                                            <div class="uk-text-danger uk-margin-top">Category is required.</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="uk-grid" data-uk-grid-margin>
                                    <div class="uk-width-medium-1-5 uk-vertical-align">
                                        <label for="item_category_name" class="uk-vertical-align-middle">@lang('trans.name')(En)</label>
                                    </div>
                                    <div class="uk-width-medium-2-5">
                                        <label for="item_sub_category_name">@lang('trans.sub_category_eng')</label>
                                        <input class="md-input" type="text" id="item_sub_category_name" name="item_sub_category_name" value="{{ $categoryById->item_sub_category_name }}" required/>
                                        @if($errors->first('item_sub_category_name'))
                                            <div class="uk-text-danger">Category name is required.</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="uk-grid">
                                    <div class="uk-width-medium-1-5 uk-vertical-align">
                                        <label for="item_sub_category_description" class="uk-vertical-align-middle">@lang('trans.description')(En)</label>
                                    </div>
                                    <div class="uk-width-medium-4-5">
                                        <label for="item_sub_category_description">@lang('trans.description_eng')</label>
                                        <textarea class="md-input" name="item_sub_category_description" id="item_sub_category_description" cols="30" rows="4" required>{{ $categoryById->item_sub_category_description }}</textarea>
                                    </div>
                                </div>
                                
                                    <div class="uk-grid">
                                        <div class="uk-width-1-1 uk-float-right">
                                            <button type="submit" class="md-btn md-btn-primary" >@lang('trans.submit')</button>
                                            <button type="button" class="md-btn md-btn-flat uk-modal-close">@lang('trans.close')</button>
                                        </div>
                                    </div>
                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script type="text/javascript">

        document.forms['editSubCategoryForm'].elements['item_category_id'].value = '{{ $categoryById->item_category_id }}';

        $('#sidebar_main_account').addClass('current_section');
        $('#sidebar_inventory_inventory').addClass('act_item');
        $(window).load(function(){
            $("#tiktok_account").trigger('click');
        })
    </script>
@endsection