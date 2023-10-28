<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\MultipleStorage\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Juzaweb\Backend\Events\UploadFileSuccess;
use Juzaweb\MultipleStorage\Listeners\UploadToStorageOnUploadFileSuccess;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UploadFileSuccess::class => [
            UploadToStorageOnUploadFileSuccess::class
        ]
    ];
}
