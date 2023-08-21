@extends('cms::layouts.backend')

@section('content')
    @component('cms::components.form_resource', [
        'model' => $model
    ])

        <div class="row">
            <div class="col-md-8">

                {{ Field::text($model, 'name', ['label' => trans('multi_storage::content.name'), 'required' => true]) }}

                {{ Field::text(
                            $model,
                            'total_size',
                              [
                                  'label' => trans('multi_storage::content.total_size'),
                                  'required' => true,
                                  'type' => 'number',
                                  'prefix' => 'MB',
                                  'value' => $model->total_size / 1024 / 1024,
                              ]
                        )
                        }}

                {{ Field::select(
                    $model,
                    'type',
                    [
                        'label' => trans('multi_storage::content.filesystem'),
                        'required' => true,
                        'readonly' => $model->id,
                        'options' => $storagesOptions
                    ])
                }}

                @foreach($storages as $storageKey => $storage)
                    <div id="form-{{ $storageKey }}"
                         class="@if($model->type != $storageKey) box-hidden @endif form-configs">
                        @foreach($storage['configs'] as $key => $config)
                            @php $config['name'] = "configs[{$storageKey}][{$key}]" @endphp
                            @php $config['data']['value'] = $model->configs[$key] ?? null @endphp
                            {{ Field::fieldByType($config) }}
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="col-md-4">
                {{ Field::checkbox($model, 'active', ['label' => trans('multi_storage::content.active'), 'checked' => $model->active]) }}
            </div>
        </div>

        <script type="text/javascript">
            $('select[name=type]').on('change', function () {
                let type = $(this).val();
                $('.form-configs').hide('slow');
                $('#form-' + type).show('slow');
            });
        </script>

    @endcomponent
@endsection
