@extends('voyager::master')

@section('page_title', __('voyager.generic.viewing').' '.$dataType->display_name_plural)

@section('page_header')
    <div class="container-fluid">
        <h1 class="page-title">
            <i class="{{ $dataType->icon }}"></i> {{ __('voyager.seeders.menu_items.'.strtolower($dataType->display_name_plural).'') }}
        </h1>
        @can('add',app($dataType->model_name))
            <a href="{{ route('voyager.'.$dataType->slug.'.create') }}" class="btn btn-success btn-add-new">
                <i class="voyager-plus"></i> <span>{{ __('voyager.generic.add_new') }}</span>
            </a>
        @endcan
        @can('delete',app($dataType->model_name))
            @include('voyager::partials.bulk-delete')
        @endcan
        @include('voyager::multilingual.language-selector')
    </div>
@stop

@section('content')
    <div class="page-content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        <table id="dataTable" class="table table-hover">
                            <thead>
                                <tr>
                                    @can('delete',app($dataType->model_name))
                                        <th></th>
                                    @endcan
                                    @foreach($dataType->browseRows as $rows)
                                    <th>{{ __('voyager.seeders.data_rows.'.strtolower($rows->display_name).'') }}</th>
                                    @endforeach
                                    <th class="actions">{{ __('voyager.generic.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dataTypeContent as $data)
                                <tr>
                                    @can('delete',app($dataType->model_name))
                                        <td>
                                            <input type="checkbox" name="row_id" id="checkbox_{{ $data->getKey() }}" value="{{ $data->getKey() }}">
                                        </td>
                                    @endcan
                                    @foreach($dataType->browseRows as $row)
                                        <td>
                                            <?php $options = json_decode($row->details); ?>
                                            @if($row->type == 'image')
                                                <img src="@if( strpos($data->{$row->field}, 'http://') === false && strpos($data->{$row->field}, 'https://') === false){{ Voyager::image( $data->{$row->field} ) }}@else{{ $data->{$row->field} }}@endif" style="width:100px">
                                            @elseif($row->type == 'select_multiple')
                                                @if ($data->{$row->field} && isset($options->relationship))
                                                    {{ $data->{$row->field}->implode($options->relationship->label, ', ') }}
                                                @endif
                                            @else
                                                {{ $data->{$row->field} }}
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="no-sort no-click" id="bread-actions">
                                            @can('delete', $data)
                                                <a href="javascript:;" title="{{ __('voyager.generic.delete') }}" class="btn btn-sm btn-danger pull-right delete" data-id="{{ $data->{$data->getKeyName()} }}" id="delete-{{ $data->{$data->getKeyName()} }}">
                                                    <i class="voyager-trash"></i> <span class="hidden-xs hidden-sm">{{ __('voyager.generic.delete') }}</span>
                                                </a>
                                            @endcan
                                            @can('edit', $data)
                                                <a href="{{ route('voyager.'.$dataType->slug.'.edit', $data->{$data->getKeyName()}) }}" title="{{ __('voyager.generic.edit') }}" class="btn btn-sm btn-primary pull-right edit">
                                                    <i class="voyager-edit"></i> <span class="hidden-xs hidden-sm">{{ __('voyager.generic.edit') }}</span>
                                                </a>
                                            @endcan
                                            @can('read', $data)
                                                <a href="{{ route('voyager.'.$dataType->slug.'.show', $data->{$data->getKeyName()}) }}" title="{{ __('voyager.generic.view') }}" class="btn btn-sm btn-warning pull-right">
                                                    <i class="voyager-eye"></i> <span class="hidden-xs hidden-sm">{{ __('voyager.generic.view') }}</span>
                                                </a>
                                            @endcan
                                        </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @if ($dataType->server_side)
            <div class="row">
                <div class="col-sm-5">
                    <div role="status" aria-live="polite">Showing {{ $dataTypeContent->firstItem() }} to {{ $dataTypeContent->lastItem() }} of {{ $dataTypeContent->total() }} entries</div>
                </div>
                <div class="col-sm-7">
                    {{ $dataTypeContent->links() }}
                </div>
            </div>
        @endif
    </div>

    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager.generic.close') }}"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i> 
                        @if (Session::get('locale') == 'vi')
                            {{ __('voyager.generic.delete_question') }} {{ __('voyager.seeders.menu_items.'.strtolower($dataType->display_name_plural).'') }} này?
                        @else
                            {{ __('voyager.generic.delete_question') }} {{ strtolower($dataType->display_name_singular) }}?
                        @endif
                        </h4>
                </div>
                <div class="modal-footer">
                    <form action="#" id="delete_form" method="POST">
                        {{ method_field("DELETE") }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-danger pull-right delete-confirm"
                                 value="{{ __('voyager.generic.delete_confirm') }}">
                    </form>
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager.generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    </div><!-- /.modal -->
@stop

@section('javascript')
    <!-- DataTables -->
    <script>
        @if (!$dataType->server_side)
            $(document).ready(function () {
                var table = $('#dataTable').DataTable({!! json_encode(
                    array_merge([
                        "order" => [],
                        "language" => __('voyager.datatable'),
                    ],
                    config('voyager.dashboard.data_tables', []))
                , true) !!});
            });
        @endif

        $('td').on('click', '.delete', function (e) {
            var form = $('#delete_form')[0];

            form.action = parseActionUrl(form.action, $(this).data('id'));

            $('#delete_modal').modal('show');
        });

        function parseActionUrl(action, id) {
            return action.match(/\/[0-9]+$/)
                ? action.replace(/([0-9]+$)/, id)
                : action + '/' + id;
        }
    </script>
@stop