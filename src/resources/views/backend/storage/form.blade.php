@extends('cms::layouts.backend')

@section('content')
    @component('cms::components.form_resource', [
        'model' => $model
    ])

        <div class="row">
            <div class="col-md-12">

                {{ Field::text($model, 'name', ['label' => trans('multi_storage::content.name'), 'required' => true]) }}

                {{ Field::select(
                    $model,
                    'type',
                    [
                        'label' => trans('multi_storage::content.type'),
                        'required' => true,
                        'options' => [
                            '' => '--- Select ---',
                            'google_drive' => trans('multi_storage::content.google_drive'),
                        ]
                    ])
                }}

                @foreach($storages as $storageKey => $storage)
                    <div id="form-{{ $storageKey }}" class="@if($model->type != $storageKey) box-hidden @endif form-configs">
                        @foreach($storage['configs'] as $key => $config)
                            @php $config['name'] = "configs[{$storageKey}][{$key}]" @endphp
                            @php $config['data']['value'] = $model->configs[$key] ?? null @endphp
                            {{ Field::fieldByType($config) }}
                        @endforeach
                    </div>
                @endforeach
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
