@extends('layouts.main')

@section('title', 'Inventory')

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
    <?php $helper = new \App\Lib\Helpers ?>
    <div class="uk-grid" data-uk-grid-margin data-uk-grid-match id="user_profile">
        <div class="uk-width-large-10-10">
            <div class="uk-grid" data-uk-grid-margin>
                <div class="uk-width-large-10-10">
                    <div class="md-card">
                        <div class="user_heading">
                            <div class="user_heading_avatar fileinput fileinput-new" data-provides="fileinput">
                                <div class="fileinput-preview fileinput-exists thumbnail"></div>
                            </div>
                            <div class="user_heading_content">
                                <h2 class="heading_b"><span class="uk-text-truncate">@lang('trans.item_sub_category_list')</span></h2>
                            </div>
                        </div>
                        <div class="user_content">
                            <div class="uk-overflow-container uk-margin-bottom">
                                <div style="padding: 5px;margin-bottom: 10px;" class="dt_colVis_buttons"></div>
                                <table class="uk-table" cellspacing="0" width="100%" id="data_table" >
                                    <thead>
                                    <tr>
                                        <th>@lang('trans.serial')</th>
                                        <th>@lang('trans.category_name')</th>
                                        <th>@lang('trans.title')</th>
                                        <th>@lang('trans.description')</th>
                                        <th>@lang('trans.updated_by')</th>
                                        <th>@lang('trans.updated_at')</th>
                                        <th class="uk-text-center">@lang('trans.action')</th>
                                    </tr>
                                    </thead>

                                    <tfoot>
                                    <tr>
                                        <th>@lang('trans.serial')</th>
                                        <th>@lang('trans.category_name')</th>
                                        <th>@lang('trans.title')</th>
                                        <th>@lang('trans.description')</th>
                                        <th>@lang('trans.updated_by')</th>
                                        <th>@lang('trans.updated_at')</th>
                                        <th class="uk-text-center">@lang('trans.action')</th>
                                    </tr>
                                    </tfoot>

                                    <tbody>
                                    <?php $count = 1; $c = 0; ?>
                                    @foreach($item_sub_categories as $category)
                                        <tr>
                                            <td>
                                                @if(Session::get('locale') == 'bn')
                                                    {{ $helper->bn2enNumber($count++) }}
                                                @else
                                                    {{$count++ }}
                                                @endif
                                            </td>
                                            <td>
                                                @if(Session::get('locale') == 'bn')
                                                    {{ $category_name[$c]['item_category_name'] }}
                                                @else
                                                    {{ $category_name[$c]['item_category_name'] }}
                                                @endif
                                            </td>
                                            <td>
                                                @if(Session::get('locale') == 'bn')
                                                    {{ $category->item_sub_category_name }}
                                                @else
                                                    {{ $category->item_sub_category_name }}
                                                @endif
                                            </td>
                                            <td>
                                                @if(Session::get('locale') == 'bn')
                                                    {{ substr($category->item_sub_category_description, 0, 50) }}
                                                @else
                                                    {{ substr($category->item_sub_category_description, 0, 50) }}
                                                @endif
                                            </td>
                                            <td>{{ $category->updatedBy->name }}</td>
                                            <td>{{ $category->updated_at }}</td>
                                            <td class="uk-text-center">
                                                <a href="{{ route('inventory_sub_category_edit',['id' => $category->id]) }}"><i data-uk-tooltip="{pos:'top'}" title="@lang('trans.edit')" class="md-icon material-icons">&#xE254;</i></a>
                                                <a class="delete_btn"><i data-uk-tooltip="{pos:'top'}" title="@lang('trans.delete')" class="md-icon material-icons">&#xE872;</i></a>
                                                <input class="sub_category_id" type="hidden" value="{{ $category->id }}">
                                            </td>
                                        </tr>
                                        <?php $c++; ?>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <!-- Add branch plus sign -->

                            <div class="md-fab-wrapper branch-create">
                                <a id="add_branch_button" href="{{ route('inventory_sub_category_add') }}" class="md-fab md-fab-accent branch-create">
                                    <i class="material-icons">&#xE145;</i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $('.delete_btn').click(function () {
            var id = $(this).next('.sub_category_id').val();
            swal({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then(function () {
                window.location.href = "/inventory/subcategory/delete/"+id;
            })
        })
    </script>
     <script type="text/javascript">
        $('#sidebar_main_account').addClass('current_section');
        $('#sidebar_inventory_inventory').addClass('act_item');
        $(window).load(function(){
            $("#tiktok_account").trigger('click');
        })
    </script>
@endsection
