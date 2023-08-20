<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/juzacms
 * @author     The Anh Dang
 * @link       https://juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\MultipleStorage\Support\Adapters;

use Exception;
use Google\Http\MediaFileUpload;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\FileList;
use Google\Service\Drive\Permission;
use Illuminate\Contracts\Filesystem\Filesystem;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use Google\Exception as GoogleException;
use Symfony\Component\Mime\MimeTypes;

class GoogleDriveAdapter implements FilesystemAdapter
{
    /**
     * Fetch fields setting for get
     *
     * @var string
     */
    public const FETCHFIELDS_GET = 'id,name,mimeType,modifiedTime,parents,permissions,size,webContentLink,webViewLink';

    /**
     * Fetch fields setting for list
     *
     * @var string
     */
    public const FETCHFIELDS_LIST = 'files(FETCHFIELDS_GET),nextPageToken';

    /**
     * MIME tyoe of directory
     *
     * @var string
     */
    public const DIRMIME = 'application/vnd.google-apps.folder';

    /**
     * Drive instance
     *
     * @var Drive
     */
    protected Drive $service;

    /**
     * @var string|null path prefix
     */
    protected ?string $pathPrefix;

    /**
     * @var string
     */
    protected string $pathSeparator = '/';

    /**
     * Default options
     *
     * @var array
     */
    protected static array $defaultOptions = [
        'spaces' => 'drive',
        'useHasDir' => false,
        'additionalFetchField' => '',
        'publishPermission' => [
            'type' => 'anyone',
            'role' => 'reader',
            'withLink' => true
        ],
        'appsExportMap' => [
            'application/vnd.google-apps.document'
                => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.google-apps.spreadsheet'
                => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.google-apps.drawing'
                => 'application/pdf',
            'application/vnd.google-apps.presentation'
                => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.google-apps.script' => 'application/vnd.google-apps.script+json',
            'default' => 'application/pdf'
        ],
        // Default parameters for each command
        // see https://developers.google.com/drive/v3/reference/files
        // ex. 'defaultParams' => ['files.list' => ['includeTeamDriveItems' => true]]
        'defaultParams' => [],
        // Team Drive Id
        'teamDriveId' => null,
        // Corpora value for files.list with the Team Drive
        'corpora' => 'teamDrive',
        // Delete action 'trash' (Into trash) or 'delete' (Permanently delete)
        'deleteAction' => 'trash'
    ];

    /**
     * A comma-separated list of spaces to query
     * Supported values are 'drive', 'appDataFolder' and 'photos'
     *
     * @var string
     */
    protected mixed $spaces;

    /**
     * Permission array as published item
     *
     * @var array
     */
    protected mixed $publishPermission;

    /**
     * Cache of file objects
     *
     * @var array
     */
    private array $cacheFileObjects = [];

    /**
     * Cache of file objects by ParentId/Name based
     *
     * @var array
     */
    private array $cacheFileObjectsByName = [];

    /**
     * Cache of hasDir
     *
     * @var array
     */
    private array $cacheHasDirs = [];

    /**
     * Use hasDir function
     *
     * @var bool
     */
    private bool $useHasDir = false;

    /**
     * List of fetch field for get
     *
     * @var string
     */
    private string $fetchfieldsGet = '';

    /**
     * List of fetch field for lest
     *
     * @var string
     */
    private string $fetchfieldsList = '';

    /**
     * Additional fetch fields array
     *
     * @var array
     */
    private array $additionalFields = [];

    /**
     * Options array
     *
     * @var array
     */
    private array $options = [];

    /**
     * Default parameters of each commands
     *
     * @var array
     */
    private mixed $defaultParams = [];

    /**
     * @var ?string
     */
    private ?string $root;

    public function __construct(Drive $service, ?string $root = null, array $options = [])
    {
        if (!$root) {
            $root = 'root';
        }

        $this->service = $service;
        $this->setPathPrefix($root);
        $this->root = $root;

        $this->options = array_replace_recursive(static::$defaultOptions, $options);

        $this->spaces = $this->options['spaces'];
        $this->useHasDir = $this->options['useHasDir'];
        $this->publishPermission = $this->options['publishPermission'];

        $this->fetchfieldsGet = self::FETCHFIELDS_GET;
        if ($this->options['additionalFetchField']) {
            $this->fetchfieldsGet .= ','.$this->options['additionalFetchField'];
            $this->additionalFields = explode(',', $this->options['additionalFetchField']);
        }
        $this->fetchfieldsList = str_replace('FETCHFIELDS_GET', $this->fetchfieldsGet, self::FETCHFIELDS_LIST);
        if (isset($this->options['defaultParams']) && is_array($this->options['defaultParams'])) {
            $this->defaultParams = $this->options['defaultParams'];
        }

        if ($this->options['teamDriveId']) {
            $this->setTeamDriveId($this->options['teamDriveId'], $this->options['corpora']);
        }
    }

    /**
     * Gets the service (Drive)
     *
     * @return object  Drive
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Write a new file.
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  Config  $config
     *            Config object
     *
     * @return void false on failure file meta data on success
     */
    public function write($path, $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    /**
     * Write a new file using a stream.
     *
     * @param  string  $path
     * @param  resource  $contents
     * @param  Config  $config
     *            Config object
     *
     * @return void false on failure file meta data on success
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, $contents, $config);
    }

    /**
     * Update a file.
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  Config  $config
     *            Config object
     *
     * @return void false on failure file meta data on success
     */
    public function update(string $path, string $contents, Config $config): void
    {
        $this->write($path, $contents, $config);
    }

    /**
     * Rename a file.
     *
     * @param  string  $path
     * @param  string  $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath): bool
    {
        [$oldParent, $fileId] = $this->splitPath($path);
        [$newParent, $newName] = $this->splitPath($newpath);

        $file = new DriveFile();
        $file->setName($newName);
        $opts = [
            'fields' => $this->fetchfieldsGet
        ];
        if ($newParent !== $oldParent) {
            $opts['addParents'] = $newParent;
            $opts['removeParents'] = $oldParent;
        }

        $updatedFile = $this->service->files->update($fileId, $file, $this->applyDefaultParams($opts, 'files.update'));

        if ($updatedFile) {
            $this->cacheFileObjects[$updatedFile->getId()] = $updatedFile;
            $this->cacheFileObjectsByName[$newParent.'/'.$newName] = $updatedFile;
            return true;
        }

        return false;
    }

    /**
     * Copy a file.
     *
     * @param  string  $path
     * @param  string  $newpath
     *
     * @return bool
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        [, $srcId] = $this->splitPath($source);
        [$newParentId, $fileName] = $this->splitPath($destination);

        $file = new DriveFile();
        $file->setName($fileName);
        $file->setParents(
            [
                $newParentId
            ]
        );

        $newFile = $this->service->files->copy(
            $srcId,
            $file,
            $this->applyDefaultParams(
                [
                'fields' => $this->fetchfieldsGet
                ],
                'files.copy'
            )
        );

        if ($newFile instanceof DriveFile) {
            $this->cacheFileObjects[$newFile->getId()] = $newFile;
            $this->cacheFileObjectsByName[$newParentId.'/'.$fileName] = $newFile;
            [$newDir] = $this->splitPath($destination);

            $newpath = (($newDir === $this->root) ? '' : ($newDir.'/')).$newFile->getId();
            if ($this->getRawVisibility($source) === Filesystem::VISIBILITY_PUBLIC) {
                $this->publish($newpath);
            } else {
                $this->unPublish($newpath);
            }
        }
    }

    /**
     * Delete a file.
     *
     * @param  string  $path
     *
     * @return bool
     */
    public function delete(string $path): void
    {
        if ($file = $this->getFileObject($path)) {
            $name = $file->getName();
            [$parentId, $id] = $this->splitPath($path);

            if ($parents = $file->getParents()) {
                $file = new DriveFile();
                $opts = [];
                $res = false;
                if (count($parents) > 1) {
                    $opts['removeParents'] = $parentId;
                } else {
                    if ($this->options['deleteAction'] === 'delete') {
                        try {
                            $this->service->files->delete($id);
                        } catch (GoogleException $e) {
                            throw $e;
                        }
                        $res = true;
                    } else {
                        $file->setTrashed(true);
                    }
                }
                if (!$res) {
                    try {
                        $this->service->files->update($id, $file, $this->applyDefaultParams($opts, 'files.update'));
                    } catch (GoogleException $e) {
                        throw $e;
                    }
                }

                unset(
                    $this->cacheFileObjects[$id],
                    $this->cacheHasDirs[$id],
                    $this->cacheFileObjectsByName[$parentId.'/'.$name]
                );
            }
        }
    }

    /**
     * Create a directory.
     *
     * @param  string  $dirname
     *            directory name
     * @param  Config  $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        list ($pdirId, $name) = $this->splitPath($dirname);

        $folder = $this->createDirectory($name, $pdirId);
        if ($folder) {
            $itemId = $folder->getId();
            // for confirmation by getMetaData() oe has() while in this connection
            $this->cacheFileObjectsByName[$pdirId.'/'.$name] = $folder;
            $this->cacheFileObjects[$itemId] = $folder;
            $this->cacheHasDirs[$itemId] = false;
            $path_parts = $this->splitFileExtension($name);
            return [
                'path' => dirname($dirname).'/'.$itemId,
                'filename' => $path_parts['filename'],
                'extension' => $path_parts['extension']
            ];
        }

        return false;
    }

    /**
     * Check whether a file exists.
     *
     * @param  string  $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        return ($this->getFileObject($path, true) instanceof DriveFile);
    }

    /**
     * Read a file.
     *
     * @param  string  $path
     *
     * @return array|false
     */
    public function read(string $path): string
    {
        [, $fileId] = $this->splitPath($path);

        if ($response = $this->service->files->get(
            $fileId,
            $this->applyDefaultParams(
                [
                    'alt' => 'media'
                ],
                'files.get'
            )
        )
        ) {
            return (string) $response->getBody();
        }

        throw new \RuntimeException('Could not read file');
    }

    /**
     * Read a file as a stream.
     *
     * @param  string  $path
     *
     * @return array|false
     */
    public function readStream($path)
    {
        $redirect = [];
        if (func_num_args() > 1) {
            $redirect = func_get_arg(1);
        }
        if (!$redirect) {
            $redirect = [
                'cnt' => 0,
                'url' => '',
                'token' => '',
                'cookies' => []
            ];
            if ($file = $this->getFileObject($path)) {
                $dlurl = $this->getDownloadUrl($file);
                $client = $this->service->getClient();
                if ($client->isUsingApplicationDefaultCredentials()) {
                    $token = $client->fetchAccessTokenWithAssertion();
                } else {
                    $token = $client->getAccessToken();
                }
                $access_token = '';
                if (is_array($token)) {
                    if (empty($token['access_token']) && !empty($token['refresh_token'])) {
                        $token = $client->fetchAccessTokenWithRefreshToken();
                    }
                    $access_token = $token['access_token'];
                } else {
                    if ($token = @json_decode($client->getAccessToken())) {
                        $access_token = $token->access_token;
                    }
                }
                $redirect = [
                    'cnt' => 0,
                    'url' => '',
                    'token' => $access_token,
                    'cookies' => []
                ];
            }
        } else {
            if ($redirect['cnt'] > 5) {
                return false;
            }
            $dlurl = $redirect['url'];
            $redirect['url'] = '';
            $access_token = $redirect['token'];
        }

        if ($dlurl) {
            $url = parse_url($dlurl);
            $cookies = [];
            if ($redirect['cookies']) {
                foreach ($redirect['cookies'] as $d => $c) {
                    if (strpos($url['host'], $d) !== false) {
                        $cookies[] = $c;
                    }
                }
            }
            if ($access_token) {
                $query = isset($url['query']) ? '?'.$url['query'] : '';
                $stream = stream_socket_client('ssl://'.$url['host'].':443');
                stream_set_timeout($stream, 300);
                fputs($stream, "GET {$url['path']}{$query} HTTP/1.1\r\n");
                fputs($stream, "Host: {$url['host']}\r\n");
                fputs($stream, "Authorization: Bearer {$access_token}\r\n");
                fputs($stream, "Connection: Close\r\n");
                if ($cookies) {
                    fputs($stream, "Cookie: ".join('; ', $cookies)."\r\n");
                }
                fputs($stream, "\r\n");
                while (($res = trim(fgets($stream))) !== '') {
                    // find redirect
                    if (preg_match('/^Location: (.+)$/', $res, $m)) {
                        $redirect['url'] = $m[1];
                    }
                    // fetch cookie
                    if (strpos($res, 'Set-Cookie:') === 0) {
                        $domain = $url['host'];
                        if (preg_match('/^Set-Cookie:(.+)(?:domain=\s*([^ ;]+))?/i', $res, $c1)) {
                            if (!empty($c1[2])) {
                                $domain = trim($c1[2]);
                            }
                            if (preg_match('/([^ ]+=[^;]+)/', $c1[1], $c2)) {
                                $redirect['cookies'][$domain] = $c2[1];
                            }
                        }
                    }
                }
                if ($redirect['url']) {
                    $redirect['cnt']++;
                    fclose($stream);
                    return $this->readStream($path, $redirect);
                }
                return compact('stream');
            }
        }
        return false;
    }

    /**
     * List contents of a directory.
     *
     * @param  string  $dirname
     * @param  bool  $recursive
     *
     * @return array
     */
    public function listContents($dirname = '', $recursive = false): iterable
    {
        return $this->getItems($dirname, $recursive);
    }

    /**
     * Get all the metadata of a file or directory.
     *
     * @param  string  $path
     *
     * @return array|false
     */
    public function getMetadata(string $path): bool|array
    {
        if ($obj = $this->getFileObject($path, true)) {
            if ($obj instanceof DriveFile) {
                return $this->normaliseObject($obj, dirname($path));
            }
        }
        return false;
    }

    /**
     * Get all the metadata of a file or directory.
     *
     * @param  string  $path
     *
     * @return array|false
     */
    public function getSize($path): bool|array
    {
        $meta = $this->getMetadata($path);
        return ($meta && isset($meta['size'])) ? $meta : false;
    }

    /**
     * Get the mimetype of a file.
     *
     * @param  string  $path
     *
     * @return array|false
     */
    public function getMimetype($path): bool|array
    {
        $meta = $this->getMetadata($path);
        return ($meta && isset($meta['mimetype'])) ? $meta : false;
    }

    /**
     * Get the timestamp of a file.
     *
     * @param  string  $path
     *
     * @return array|false
     */
    public function getTimestamp($path): bool|array
    {
        $meta = $this->getMetadata($path);
        return ($meta && isset($meta['timestamp'])) ? $meta : false;
    }

    /**
     * Set the visibility for a file.
     *
     * @param  string  $path
     * @param  string  $visibility
     *
     * @return void file meta data
     */
    public function setVisibility(string $path, string $visibility): void
    {
        ($visibility === Filesystem::VISIBILITY_PUBLIC) ? $this->publish($path) : $this->unPublish($path);
    }

    /**
     * Get the visibility of a file.
     *
     * @param  string  $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {
        return [
            'visibility' => $this->getRawVisibility($path)
        ];
    }

    public function createDirectory(string $path, Config $config): void
    {
        $file = new DriveFile();
        $file->setName($path);
        $file->setParents([$config->get('parent_id')]);
        $file->setMimeType(self::DIRMIME);

        $this->service->files->create(
            $file,
            $this->applyDefaultParams(
                [
                    'fields' => $this->fetchfieldsGet
                ],
                'files.create'
            )
        );
    }

    // /////////////////- ORIGINAL METHODS -///////////////////

    /**
     * Get contents parmanent URL
     *
     * @param  string  $path
     *            itemId path
     *
     * @return string|false
     */
    public function getUrl($path): bool|string
    {
        if ($this->publish($path)) {
            $obj = $this->getFileObject($path);
            if ($url = $obj->getWebContentLink()) {
                return str_replace('export=download', 'export=media', $url);
            }
            if ($url = $obj->getWebViewLink()) {
                return $url;
            }
        }
        return false;
    }

    /**
     * Has child directory
     *
     * @param  string  $path
     *            itemId path
     *
     * @return array
     */
    public function hasDir($path): array|bool
    {
        $meta = $this->getMetadata($path);
        return ($meta && isset($meta['hasdir'])) ? $meta : [
            'hasdir' => true
        ];
    }

    /**
     * Do cache cacheHasDirs
     *
     * @param  array  $targets
     *            [[path => id],...]
     * @param $object
     * @return array
     */
    protected function setHasDir($targets, $object): array
    {
        $service = $this->service;
        $client = $service->getClient();
        $gFiles = $service->files;
        $opts = [
            'pageSize' => 1
        ];
        $paths = [];
        $results = [];
        $i = 0;
        foreach ($targets as $id) {
            $opts['q'] = sprintf('trashed = false and "%s" in parents and mimeType = "%s"', $id, self::DIRMIME);
            $request = $gFiles->listFiles($this->applyDefaultParams($opts, 'files.list'));
            $key = (string)++$i;
            $results[$key] = $request;
            $paths[$key] = $id;
        }
        foreach ($results as $key => $result) {
            if ($result instanceof FileList) {
                $object[$paths[$key]]['hasdir'] = $this->cacheHasDirs[$paths[$key]] = (bool)$result->getFiles();
            }
        }
        return $object;
    }

    /**
     * Get the object permissions presented as a visibility.
     *
     * @param  string  $path
     *            itemId path
     *
     * @return string
     */
    protected function getRawVisibility(string $path): string
    {
        $file = $this->getFileObject($path);
        $permissions = $file->getPermissions();
        $visibility = Filesystem::VISIBILITY_PRIVATE;
        foreach ($permissions as $permission) {
            if ($permission->type === $this->publishPermission['type']
                && $permission->role === $this->publishPermission['role']
            ) {
                $visibility = Filesystem::VISIBILITY_PUBLIC;
                break;
            }
        }
        return $visibility;
    }

    /**
     * Publish specified path item
     *
     * @param  string  $path
     *            itemId path
     *
     * @return bool
     */
    protected function publish(string $path): bool
    {
        if (($file = $this->getFileObject($path))) {
            if ($this->getRawVisibility($path) === Filesystem::VISIBILITY_PUBLIC) {
                return true;
            }
            try {
                $permission = new Permission($this->publishPermission);
                if ($this->service->permissions->create($file->getId(), $permission)) {
                    return true;
                }
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Un-publish specified path item
     *
     * @param  string  $path
     *            itemId path
     *
     * @return bool
     */
    protected function unPublish(string $path)
    {
        if (($file = $this->getFileObject($path))) {
            $permissions = $file->getPermissions();
            try {
                foreach ($permissions as $permission) {
                    if ($permission->type === 'anyone' && $permission->role === 'reader') {
                        $this->service->permissions->delete($file->getId(), $permission->getId());
                    }
                }
                return true;
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Path splits to dirId, fileId or newName
     *
     * @param  string  $path
     * @param  bool  $getParentId
     * @return array [ $dirId , $fileId|newName ]
     */
    protected function splitPath(string $path, bool $getParentId = true): array
    {
        $useSlashSub = defined('EXT_FLYSYSTEM_SLASH_SUBSTITUTE');
        if ($path === '' || $path === '/') {
            $fileName = $this->root;
            $dirName = '';
        } else {
            if ($useSlashSub) {
                $path = str_replace(EXT_FLYSYSTEM_SLASH_SUBSTITUTE, chr(7), $path);
            }
            $paths = explode('/', $path);
            $fileName = array_pop($paths);
            if ($getParentId) {
                $dirName = $paths ? array_pop($paths) : '';
            } else {
                $dirName = join('/', $paths);
            }
            if ($dirName === '') {
                $dirName = $this->root;
            }
        }
        return [
            $dirName,
            $useSlashSub ? str_replace(chr(7), '/', $fileName) : $fileName
        ];
    }

    /**
     * Item name splits to filename and extension
     * This function supported include '/' in item name
     *
     * @param  string  $name
     *
     * @return array [ 'filename' => $filename , 'extension' => $extension ]
     */
    protected function splitFileExtension(string $name): array
    {
        $extension = '';
        $name_parts = explode('.', $name);
        if (isset($name_parts[1])) {
            $extension = array_pop($name_parts);
        }
        $filename = join('.', $name_parts);
        return compact('filename', 'extension');
    }

    /**
     * Get normalised files array from DriveFile
     *
     * @param  DriveFile  $object
     * @param  string  $dirname
     *            Parent directory itemId path
     *
     * @return array Normalised files array
     */
    protected function normaliseObject(DriveFile $object, string $dirname): array
    {
        $id = $object->getId();
        $path_parts = $this->splitFileExtension($object->getName());
        $result = ['name' => $object->getName()];
        $result['type'] = $object->mimeType === self::DIRMIME ? 'dir' : 'file';
        $result['path'] = ($dirname ? ($dirname.'/') : '').$id;
        $result['id'] = $id;
        $result['filename'] = $path_parts['filename'];
        $result['extension'] = $path_parts['extension'];
        $result['timestamp'] = strtotime($object->getModifiedTime());
        if ($result['type'] === 'file') {
            $result['mimetype'] = $object->mimeType;
            $result['size'] = (int)$object->getSize();
        }
        if ($result['type'] === 'dir') {
            $result['size'] = 0;
            if ($this->useHasDir) {
                $result['hasdir'] = $this->cacheHasDirs[$id] ?? false;
            }
        }

        // attach additional fields
        if ($this->additionalFields) {
            foreach ($this->additionalFields as $field) {
                if (property_exists($object, $field)) {
                    $result[$field] = $object->$field;
                }
            }
        }
        return $result;
    }

    /**
     * Get items array of target dirctory
     *
     * @param  string  $dirname
     *            itemId path
     * @param  bool  $recursive
     * @param  number  $maxResults
     * @param  string  $query
     *
     * @return array Items array
     */
    protected function getItems($dirname, $recursive = false, $maxResults = 0, $query = ''): array
    {
        [, $itemId] = $this->splitPath($dirname);

        $maxResults = min($maxResults, 1000);
        $results = [];
        $parameters = [
            'pageSize' => $maxResults ?: 1000,
            'fields' => $this->fetchfieldsList,
            'spaces' => $this->spaces,
            'q' => sprintf('trashed = false and "%s" in parents', $itemId)
        ];
        if ($query) {
            $parameters['q'] .= ' and ('.$query.')';
        }
        $parameters = $this->applyDefaultParams($parameters, 'files.list');
        $pageToken = null;
        $gFiles = $this->service->files;
        $this->cacheHasDirs[$itemId] = false;
        $setHasDir = [];

        do {
            try {
                if ($pageToken) {
                    $parameters['pageToken'] = $pageToken;
                }
                $fileObjs = $gFiles->listFiles($parameters);
                if ($fileObjs instanceof FileList) {
                    foreach ($fileObjs as $obj) {
                        $id = $obj->getId();
                        $this->cacheFileObjects[$id] = $obj;
                        $result = $this->normaliseObject($obj, $dirname);
                        $results[$id] = $result;
                        if ($result['type'] === 'dir') {
                            if ($this->useHasDir) {
                                $setHasDir[$id] = $id;
                            }
                            if ($this->cacheHasDirs[$itemId] === false) {
                                $this->cacheHasDirs[$itemId] = true;
                                unset($setHasDir[$itemId]);
                            }
                            if ($recursive) {
                                $results = array_merge(
                                    $results,
                                    $this->getItems($result['path'], true, $maxResults, $query)
                                );
                            }
                        }
                    }
                    $pageToken = $fileObjs->getNextPageToken();
                } else {
                    $pageToken = null;
                }
            } catch (Exception $e) {
                $pageToken = null;
            }
        } while ($pageToken && $maxResults === 0);

        if ($setHasDir) {
            $results = $this->setHasDir($setHasDir, $results);
        }
        return array_values($results);
    }

    /**
     * Get file oblect DriveFile
     *
     * @param  string  $path
     *            itemId path
     * @param  string  $checkDir
     *            do check hasdir
     *
     * @return DriveFile|null
     */
    protected function getFileObject(string $path, $checkDir = false): ?DriveFile
    {
        [$parentId, $itemId] = $this->splitPath($path, true);
        if (isset($this->cacheFileObjects[$itemId])) {
            return $this->cacheFileObjects[$itemId];
        } else {
            if (isset($this->cacheFileObjectsByName[$parentId.'/'.$itemId])) {
                return $this->cacheFileObjectsByName[$parentId.'/'.$itemId];
            }
        }

        $service = $this->service;
        $client = $service->getClient();

        $fileObj = $hasdir = null;

        $opts = [
            'fields' => $this->fetchfieldsGet
        ];

        try {
            $fileObj = $this->service->files->get($itemId, $this->applyDefaultParams($opts, 'files.get'));
            if ($checkDir && $this->useHasDir) {
                $hasdir = $service->files->listFiles(
                    $this->applyDefaultParams(
                        [
                        'pageSize' => 1,
                        'q' => sprintf(
                            'trashed = false and "%s" in parents and mimeType = "%s"',
                            $itemId,
                            self::DIRMIME
                        )
                        ],
                        'files.list'
                    )
                );
            }
        } catch (\Google_Service_Exception $e) {
            if (!$fileObj) {
                if (intVal($e->getCode()) != 404) {
                    return null;
                }
            }
        }

        if ($fileObj instanceof DriveFile) {
            if ($hasdir && $fileObj->mimeType === self::DIRMIME) {
                if ($hasdir instanceof FileList) {
                    $this->cacheHasDirs[$fileObj->getId()] = (bool)$hasdir->getFiles();
                }
            }
        } else {
            $fileObj = null;
        }
        $this->cacheFileObjects[$itemId] = $fileObj;

        return $fileObj;
    }

    /**
     * Get download url
     *
     * @param  DriveFile  $file
     *
     * @return string|false
     */
    protected function getDownloadUrl(DriveFile $file): bool|string
    {
        if (!str_starts_with($file->mimeType, 'application/vnd.google-apps')) {
            return 'https://www.googleapis.com/drive/v3/files/'.$file->getId().'?alt=media';
        }

        $mimeMap = $this->options['appsExportMap'];
        $mime = $mimeMap[$file->getMimeType()] ?? $mimeMap['default'];
        $mime = rawurlencode($mime);

        return 'https://www.googleapis.com/drive/v3/files/'.$file->getId().'/export?mimeType='.$mime;
    }

    /**
     * Create dirctory
     *
     * @param  string  $name
     * @param  string  $parentId
     *
     * @return DriveFile|NULL
     */
    protected function createDirectory2($name, $parentId): void
    {


        //return ($obj instanceof DriveFile) ? $obj : false;
    }

    /**
     * Upload|Update item
     *
     * @param  string  $path
     * @param  string|resource  $contents
     * @param  Config  $config
     *
     * @return array|false item info array
     */
    protected function upload(string $path, $contents, Config $config): bool|array
    {
        [$parentId, $fileName] = $this->splitPath($path);
        $srcDriveFile = $this->getFileObject($path);
        if (is_resource($contents)) {
            $uploadedDriveFile = $this->uploadResourceToGoogleDrive(
                $contents,
                $parentId,
                $fileName,
                $srcDriveFile,
                $config->get('mimetype')
            );
        } else {
            $uploadedDriveFile = $this->uploadStringToGoogleDrive(
                $contents,
                $parentId,
                $fileName,
                $srcDriveFile,
                $config->get('mimetype')
            );
        }

        return $this->normaliseUploadedFile($uploadedDriveFile, $path, $config->get('visibility'));
    }

    /**
     * Detect the largest chunk size that can be used for uploading a file
     *
     * @return int
     */
    protected function detectChunkSizeBytes(): int
    {
        // Max and default chunk size of 100MB
        $chunkSizeBytes = 100 * 1024 * 1024;
        $memoryLimit = $this->getIniBytes('memory_limit');
        if ($memoryLimit > 0) {
            $availableMemory = $memoryLimit - $this->getMemoryUsedBytes();
            /*
             * We need some breathing room, so we only take 1/4th
             *  of the available memory for use in chunking (the divide by 4 does this).
             * The chunk size must be a multiple of 256KB(262144).
             * An example of why we need the breathing room is detecting the mime type for a file that is
             *  just small enough to fit into one chunk.
             * In this scenario, we send the entire file off as a string to have the mime type detected.
             * Unfortunately, this leads to the entire
             * file being loaded into memory again, separately from the copy we're holding.
             */
            $chunkSizeBytes = max(262144, min($chunkSizeBytes, floor($availableMemory / 4 / 262144) * 262144));
        }

        return (int)$chunkSizeBytes;
    }

    /**
     * Normalise a Drive File that has been created
     *
     * @param  DriveFile  $uploadedFile
     * @param  string  $localPath
     * @param  string  $visibility
     * @return array|bool
     */
    protected function normaliseUploadedFile($uploadedFile, $localPath, $visibility): bool|array
    {
        list ($parentId, $fileName) = $this->splitPath($localPath);

        if (!($uploadedFile instanceof DriveFile)) {
            return false;
        }

        $this->cacheFileObjects[$uploadedFile->getId()] = $uploadedFile;
        if (!$this->getFileObject($localPath)) {
            $this->cacheFileObjectsByName[$parentId.'/'.$fileName] = $uploadedFile;
        }
        $result = $this->normaliseObject($uploadedFile, dirname($localPath));

        if ($visibility && $this->setVisibility($localPath, $visibility)) {
            $result['visibility'] = $visibility;
        }

        return $result;
    }

    /**
     * Upload a PHP resource stream to Google Drive
     *
     * @param  resource  $resource
     * @param  string  $parentId
     * @param  string  $fileName
     * @param $srcDriveFile
     * @param  string  $mime
     * @return bool|DriveFile
     */
    protected function uploadResourceToGoogleDrive(
        $resource,
        $parentId,
        $fileName,
        $srcDriveFile,
        $mime
    ): DriveFile|bool {
        $chunkSizeBytes = $this->detectChunkSizeBytes();
        $fileSize = $this->getFileSizeBytes($resource);

        if ($fileSize <= $chunkSizeBytes) {
            // If the resource fits in a single chunk, we'll just upload it in a single request
            return $this->uploadStringToGoogleDrive(
                stream_get_contents($resource),
                $parentId,
                $fileName,
                $srcDriveFile,
                $mime
            );
        }

        $client = $this->service->getClient();
        // Call the API with the media upload, defer so it doesn't immediately return.
        $client->setDefer(true);

        if (!$mime) {
            $mime = MimeTypes::getDefault()->guessMimeType($resource);
        }

        $request = $this->ensureDriveFileExists('', $parentId, $fileName, $srcDriveFile, $mime);
        $client->setDefer(false);
        $media = $this->getMediaFileUpload($client, $request, $mime, $chunkSizeBytes);
        $media->setFileSize($fileSize);

        // Upload chunks until we run out of file to upload; $status will be false until the process is complete.
        $status = false;
        while (!$status && !feof($resource)) {
            $chunk = $this->readFileChunk($resource, $chunkSizeBytes);
            $status = $media->nextChunk($chunk);
        }

        // The final value of $status will be the data from the API for the object that has been uploaded.
        return $status;
    }

    /**
     * Upload a string to Google Drive
     *
     * @param  string  $contents
     * @param  string  $parentId
     * @param  string  $fileName
     * @param  string  $mime
     * @return DriveFile
     */
    protected function uploadStringToGoogleDrive($contents, $parentId, $fileName, $srcDriveFile, $mime): DriveFile
    {
        return $this->ensureDriveFileExists($contents, $parentId, $fileName, $srcDriveFile, $mime);
    }

    /**
     * Ensure that a file exists on Google Drive by creating it if it doesn't exist or updating it if it does
     *
     * @param  string  $contents
     * @param  string  $parentId
     * @param  string  $fileName
     * @param  string  $mime
     * @return DriveFile
     */
    protected function ensureDriveFileExists($contents, $parentId, $fileName, $srcDriveFile, $mime): DriveFile
    {
        if (!$mime) {
            $mime = MimeTypes::getDefault()->guessMimeType($contents);
        }

        $driveFile = new DriveFile();

        $mode = 'update';
        if (!$srcDriveFile) {
            $mode = 'insert';
            $driveFile->setName($fileName);
            $driveFile->setParents([$parentId]);
        }

        $driveFile->setMimeType($mime);

        $params = ['fields' => $this->fetchfieldsGet];

        if ($contents) {
            $params['data'] = $contents;
            $params['uploadType'] = 'media';
        }

        if ($mode === 'insert') {
            $retrievedDriveFile = $this->service->files->create(
                $driveFile,
                $this->applyDefaultParams($params, 'files.create')
            );
        } else {
            $retrievedDriveFile = $this->service->files->update(
                $srcDriveFile->getId(),
                $driveFile,
                $this->applyDefaultParams($params, 'files.update')
            );
        }

        return $retrievedDriveFile;
    }

    /**
     * Read file chunk
     *
     * @param  resource  $handle
     * @param  int  $chunkSize
     *
     * @return string
     */
    protected function readFileChunk($handle, $chunkSize)
    {
        $byteCount = 0;
        $giantChunk = '';
        while (!feof($handle)) {
            // fread will never return more than 8192 bytes if the stream is read buffered,
            // and it does not represent a plain file
            // An example of a read buffered file is when reading from a URL
            $chunk = fread($handle, 8192);
            $byteCount += strlen($chunk);
            $giantChunk .= $chunk;
            if ($byteCount >= $chunkSize) {
                return $giantChunk;
            }
        }
        return $giantChunk;
    }

    /**
     * Return bytes from php.ini value
     *
     * @param  string  $iniName
     * @param  string  $val
     * @return number
     */
    protected function getIniBytes($iniName = '', $val = '')
    {
        if ($iniName !== '') {
            $val = ini_get($iniName);
            if ($val === false) {
                return 0;
            }
        }
        $val = trim($val, "bB \t\n\r\0\x0B");
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int) $val;

        switch ($last) {
            case 't':
                $val *= 1024;
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $val;
    }

    /**
     * Return the number of memory bytes allocated to PHP
     *
     * @return int
     */
    protected function getMemoryUsedBytes()
    {
        return memory_get_usage(true);
    }

    /**
     * Get the size of a file resource
     *
     * @param $resource
     *
     * @return int
     */
    protected function getFileSizeBytes($resource)
    {
        return fstat($resource)['size'];
    }

    /**
     * Get a MediaFileUpload
     *
     * @param $client
     * @param $request
     * @param $mime
     * @param $chunkSizeBytes
     *
     * @return MediaFileUpload
     */
    protected function getMediaFileUpload($client, $request, $mime, $chunkSizeBytes)
    {
        return new MediaFileUpload($client, $request, $mime, null, true, $chunkSizeBytes);
    }

    /**
     * Apply optional parameters for each command
     *
     * @param  array  $params  The parameters
     * @param  string  $cmdName  The command name
     *
     * @return array
     *
     * @see https://developers.google.com/drive/v3/reference/files
     * @see \Drive_Resource_Files
     */
    protected function applyDefaultParams($params, $cmdName)
    {
        if (isset($this->defaultParams[$cmdName]) && is_array($this->defaultParams[$cmdName])) {
            return array_replace($this->defaultParams[$cmdName], $params);
        }

        return $params;
    }

    /**
     * Enables Team Drive support by changing default parameters
     *
     * @return void
     *
     * @see https://developers.google.com/drive/v3/reference/files
     * @see \Drive_Resource_Files
     */
    public function enableTeamDriveSupport(): void
    {
        $this->defaultParams = array_merge_recursive(
            array_fill_keys(
                [
                'files.copy',
                'files.create',
                'files.delete',
                'files.trash',
                'files.get',
                'files.list',
                'files.update',
                'files.watch'
                ],
                ['supportsTeamDrives' => true]
            ),
            $this->defaultParams
        );
    }

    /**
     * Selects Team Drive to operate by changing default parameters
     *
     * @param  string  $teamDriveId  Team Drive id
     * @param  string  $corpora  Corpora value for files.list
     *
     * @return void
     *
     * @see https://developers.google.com/drive/v3/reference/files
     * @see https://developers.google.com/drive/v3/reference/files/list
     * @see \Drive_Resource_Files
     */
    public function setTeamDriveId(string $teamDriveId, string $corpora = 'teamDrive'): void
    {
        $this->enableTeamDriveSupport();
        $this->defaultParams = array_merge_recursive(
            $this->defaultParams,
            [
                'files.list' => [
                    'corpora' => $corpora,
                    'includeTeamDriveItems' => true,
                    'teamDriveId' => $teamDriveId
                ]
            ]
        );

        if ($this->root === 'root') {
            $this->setPathPrefix($teamDriveId);
            $this->root = $teamDriveId;
        }
    }

    public function fileExists(string $path): bool
    {
        if ($this->getFileObject($path)) {
            return true;
        }

        return false;
    }

    public function directoryExists(string $path): bool
    {
        // TODO: Implement directoryExists() method.
    }

    public function deleteDirectory(string $path): void
    {
        $this->delete($path);
    }

    public function visibility(string $path): FileAttributes
    {
        // TODO: Implement visibility() method.
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->getMimetype($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        // TODO: Implement lastModified() method.
    }

    public function fileSize(string $path): FileAttributes
    {
        return new FileAttributes(
            $path,
            $this->getSize($path),
            $this->getVisibility($path)
        );
    }

    public function move(string $source, string $destination, Config $config): void
    {
        // TODO: Implement move() method.
    }

    /**
     * Set the path prefix.
     *
     * @param  string  $prefix
     *
     * @return void
     */
    public function setPathPrefix(string $prefix): void
    {
        if ($prefix === '') {
            $this->pathPrefix = null;

            return;
        }

        $this->pathPrefix = rtrim($prefix, '\\/') . $this->pathSeparator;
    }

    /**
     * Get the path prefix.
     *
     * @return string|null path prefix or null if pathPrefix is empty
     */
    public function getPathPrefix(): ?string
    {
        return $this->pathPrefix;
    }

    /**
     * Prefix a path.
     *
     * @param  string  $path
     *
     * @return string prefixed path
     */
    public function applyPathPrefix(string $path): string
    {
        return $this->getPathPrefix() . ltrim($path, '\\/');
    }

    /**
     * Remove a path prefix.
     *
     * @param  string  $path
     *
     * @return string path without the prefix
     */
    public function removePathPrefix(string $path): string
    {
        return substr($path, strlen((string) $this->getPathPrefix()));
    }
}
