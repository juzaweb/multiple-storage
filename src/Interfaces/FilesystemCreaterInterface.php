<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/juzacms
 * @author     The Anh Dang
 * @link       https://juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\MultipleStorage\Interfaces;

use Illuminate\Contracts\Filesystem\Filesystem;

interface FilesystemCreaterInterface
{
    public function create(array $config): Filesystem;
}
