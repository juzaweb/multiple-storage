<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/juzacms
 * @author     The Anh Dang
 * @link       https://juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\MultipleStorage\Contracts;

use Illuminate\Support\Collection;

interface StorageManager
{
    public function all(bool $collection = true): Collection|array;
}
