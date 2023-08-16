<?php

namespace Juzaweb\MultipleStorage\Http\Controllers;

use Juzaweb\CMS\Http\Controllers\BackendController;

class MultipleStorageController extends BackendController
{
    public function index()
    {
        //

        return view(
            'jumu::index',
            [
                'title' => 'Title Page',
            ]
        );
    }
}
