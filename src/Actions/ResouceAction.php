<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/juzacms
 * @author     The Anh Dang
 * @link       https://juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\MultipleStorage\Actions;

use Juzaweb\CMS\Abstracts\Action;

class ResouceAction extends Action
{
    public function handle(): void
    {
        $this->addAction(Action::BACKEND_INIT, [$this, 'registerResource']);
    }

    public function registerResource(): void
    {
        $this->hookAction->registerAdminPage(
            'storages',
            [
                'title' => 'Storages',
            ]
        );
    }
}
