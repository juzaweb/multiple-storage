<?php

namespace Juzaweb\MultipleStorage\Http\Controllers\Backend;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Juzaweb\Backend\Http\Controllers\Backend\PageController;
use Juzaweb\CMS\Abstracts\DataTable;
use Juzaweb\CMS\Traits\ResourceController;
use Juzaweb\MultipleStorage\Contracts\StorageManager;
use Juzaweb\MultipleStorage\Http\Datatables\StorageDatatable;
use Juzaweb\MultipleStorage\Models\Storage;

class StorageController extends PageController
{
    use ResourceController {
        getDataForForm as DataForForm;
    }

    protected string $viewPrefix = 'multi_storage::backend.storage';

    public function __construct(protected StorageManager $storageManager)
    {
    }

    protected function getDataTable(...$params): DataTable
    {
        return new StorageDatatable();
    }

    public function getDataForForm($model, ...$params): array
    {
        $data = $this->DataForForm($model, ...$params);
        $data['storages'] = $this->storageManager->all();
        $data['storagesOptions'] = ['' => '-----'];
        $data['storagesOptions'] += collect($data['storages'])->map(fn ($item) => $item['name'])->toArray();
        return $data;
    }

    protected function beforeSave(&$data, &$model, ...$params): void
    {
        $model->total_size = Arr::get($data, 'total_size', 0) * 1024 * 1024;
        $model->active = filter_var(Arr::get($data, 'active', false), FILTER_VALIDATE_BOOLEAN);
        $model->configs = array_merge($model->configs ?? [], $data['configs'][$data['type']] ?? []);
    }

    protected function validator(array $attributes, ...$params): ValidatorContract
    {
        return Validator::make(
            $attributes,
            [
                'name' => ['required'],
                'type' => ['required'],
                'total_size' => ['required', 'integer', 'min:1'],
                'configs.*' => ['required', 'array'],
            ]
        );
    }

    protected function getModel(...$params): string
    {
        return Storage::class;
    }

    protected function getTitle(...$params): string
    {
        return trans('multi_storage::content.storage');
    }
}
