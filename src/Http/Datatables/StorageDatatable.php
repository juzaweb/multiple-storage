<?php

namespace Juzaweb\MultipleStorage\Http\Datatables;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Juzaweb\CMS\Abstracts\DataTable;
use Juzaweb\CMS\Repositories\Criterias\FilterCriteria;
use Juzaweb\CMS\Repositories\Criterias\SearchCriteria;
use Juzaweb\CMS\Repositories\Criterias\SortCriteria;
use Juzaweb\MultipleStorage\Repositories\StorageRepository;

class StorageDatatable extends DataTable
{
    /**
     * Columns datatable
     *
     * @return array
     */
    public function columns(): array
    {
        return [
            'name' => [
                'label' => trans('multi_storage::content.name'),
                'formatter' => [$this, 'rowActionsFormatter'],
            ],
            'type' => [
                'label' => trans('multi_storage::content.type'),
            ],
            'configs' => [
                'label' => trans('multi_storage::content.configs'),
            ],
            'created_at' => [
                'label' => trans('cms::app.created_at'),
                'width' => '15%',
                'align' => 'center',
                'formatter' => function ($value, $row, $index) {
                    return jw_date_format($row->created_at);
                }
            ]
        ];
    }

    /**
     * Query data datatable
     *
     * @param  array  $data
     * @return Builder
     * @throws BindingResolutionException
     */
    public function query(array $data): Builder
    {
        return app()->make(StorageRepository::class)
            ->pushCriteria(new SearchCriteria($data))
            ->pushCriteria(new FilterCriteria($data))
            ->pushCriteria(new SortCriteria($data))
            ->getQuery();
    }

    public function bulkActions($action, $ids): void
    {
        switch ($action) {
            case 'delete':
                foreach ($ids as $id) {
                    app(StorageRepository::class)->delete($id);
                }
                break;
        }
    }
}
