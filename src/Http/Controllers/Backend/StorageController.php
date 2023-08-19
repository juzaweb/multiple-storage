<?php

namespace Juzaweb\MultipleStorage\Http\Controllers\Backend;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Juzaweb\Backend\Http\Controllers\Backend\PageController;
use Juzaweb\CMS\Abstracts\DataTable;
use Juzaweb\CMS\Traits\ResourceController;
use Juzaweb\MultipleStorage\Http\Datatables\StorageDatatable;
use Juzaweb\MultipleStorage\Models\Storage;

class StorageController extends PageController
{
    use ResourceController {
        getDataForForm as DataForForm;
        parseDataForSave as DataForSave;
    }

    protected string $viewPrefix = 'multi_storage::backend.storage';

    protected function getDataTable(...$params): DataTable
    {
        return new StorageDatatable();
    }

    public function getDataForForm($model, ...$params): array
    {
        $data = $this->DataForForm($model, ...$params);
        $data['storages'] = config("multiple-storage.storages");
        return $data;
    }

    protected function parseDataForSave(array $attributes, ...$params): array
    {
        $attributes['configs'] = $attributes['configs'][$attributes['type']] ?? [];

        return $attributes;
    }

    protected function validator(array $attributes, ...$params): ValidatorContract
    {
        return Validator::make(
            $attributes,
            [
                // Rules
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
