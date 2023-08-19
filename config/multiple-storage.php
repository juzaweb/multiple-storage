<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/juzacms
 * @author     The Anh Dang
 * @link       https://juzaweb.com
 * @license    GNU V2
 */

return [
    'storages' => [
        'google_drive' => [
            'name' => 'Google Drive',
            'configs' => [
                'site_key' => [
                    'type' => 'text',
                    'label' => 'Site key',
                ],
                'secret_key' => [
                    'type' => 'security',
                    'label' => 'Secret key',
                ]
            ]
        ],
    ]
];
