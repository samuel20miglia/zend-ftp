<?php
declare(strict_types=1);

namespace Zend\Ftp;

use Zend\Ftp\Exception\FtpException;

/**
 * @author samuel ventimiglia
 *
 */
class FtpModel implements \Countable
{

    /**
     * PHP FTP functions wrapper.
     *
     * @var FtpModel
     */
    protected $ftp;

    /**
     * The connection with the server
     *
     * @var resource
     */
    protected $conn;


    /**
     * Forward the method call to FTP functions
     *
     * @param  string       $function
     * @param  array        $arguments
     * @return mixed
     * @throws FtpException When the function is not valid
     */
    public function __call($function, array $arguments)
    {
        $function = 'ftp_' . $function;
        if (function_exists($function)) {
            //array_unshift($arguments, $this->conn);
            return call_user_func_array($function, $arguments);
        }
        throw new FtpException("{$function} is not a valid FTP function");
    }

    /**
     * Overwrites the PHP limit
     *
     * @param  string|null $memory            The memory limit, if null is not modified
     * @param  int         $time_limit        The max execution time, unlimited by default
     * @param  bool        $ignore_user_abort Ignore user abort, true by default
     * @return FtpClient
     */
    public function setPhpLimit($memory = null, $time_limit = 0, $ignore_user_abort = true)
    {
        if (null !== $memory) {
            ini_set('memory_limit', $memory);
        }
        ignore_user_abort(true);
        set_time_limit($time_limit);
        return $this;
    }

    /**
     * Get the help information of the remote FTP server.
     *
     * @return array
     */
    public function help()
    {
        return $this->raw($this->conn,'help');
    }

    /**
     * Returns the last modified time of the given file.
     * Return -1 on error
     *
     * @param string $remoteFile
     * @param string|null $format
     *
     * @return int
     */
    public function modifiedTime($remoteFile, $format = null)
    {
        $time = $this->mdtm($remoteFile);
        if ($time !== -1 && $format !== null) {
            return date($format, $time);
        }
        return $time;
    }
    /**
     * Changes to the parent directory.
     *
     * @throws FtpException
     * @return FtpClient
     */
    public function up()
    {
        $result = @$this->cdup();
        if ($result === false) {
            throw new FtpException('Unable to get parent folder');
        }
        return $this;
    }
    /**
     * Returns a list of files in the given directory.
     *
     * @param string   $directory The directory, by default is "." the current directory
     * @param bool     $recursive
     * @param callable $filter    A callable to filter the result, by default is asort() PHP function.
     *                            The result is passed in array argument,
     *                            must take the argument by reference !
     *                            The callable should proceed with the reference array
     *                            because is the behavior of several PHP sorting
     *                            functions (by reference ensure directly the compatibility
     *                            with all PHP sorting functions).
     *
     * @return array
     * @throws FtpException If unable to list the directory
     */
    public function nlist($directory = '.', $recursive = false, $filter = 'sort')
    {
        if (!$this->isDir($directory)) {
            throw new FtpException('"'.$directory.'" is not a directory');
        }
        $files = $this->nlist($directory);
        if ($files === false) {
            throw new FtpException('Unable to list directory');
        }
        $result  = array();
        $dir_len = strlen($directory);
        // if it's the current
        if (false !== ($kdot = array_search('.', $files))) {
            unset($files[$kdot]);
        }
        // if it's the parent
        if(false !== ($kdot = array_search('..', $files))) {
            unset($files[$kdot]);
        }
        if (!$recursive) {
            foreach ($files as $file) {
                $result[] = $directory.'/'.$file;
            }
            // working with the reference (behavior of several PHP sorting functions)
            $filter($result);
            return $result;
        }
        // utils for recursion
        $flatten = function (array $arr) use (&$flatten) {
            $flat = [];
            foreach ($arr as $k => $v) {
                if (is_array($v)) {
                    $flat = array_merge($flat, $flatten($v));
                } else {
                    $flat[] = $v;
                }
            }
            return $flat;
        };
        foreach ($files as $file) {
            $file = $directory.'/'.$file;
            // if contains the root path (behavior of the recursivity)
            if (0 === strpos($file, $directory, $dir_len)) {
                $file = substr($file, $dir_len);
            }
            if ($this->isDir($file)) {
                $result[] = $file;
                $items    = $flatten($this->nlist($file, true, $filter));
                foreach ($items as $item) {
                    $result[] = $item;
                }
            } else {
                $result[] = $file;
            }
        }
        $result = array_unique($result);
        $filter($result);
        return $result;
    }
    /**
     * Creates a directory.
     *
     * @see FtpClient::rmdir()
     * @see FtpClient::remove()
     * @see FtpClient::put()
     * @see FtpClient::putAll()
     *
     * @param  string $directory The directory
     * @param  bool   $recursive
     * @return array
     */
    public function mkdir($directory, $recursive = false)
    {
        if (!$recursive or $this->isDir($directory)) {
            return $this->mkdir($directory);
        }
        $result = false;
        $pwd    = $this->pwd();
        $parts  = explode('/', $directory);
        foreach ($parts as $part) {
            if ($part == '') {
                continue;
            }
            if (!@$this->chdir($part)) {
                $result = $this->mkdir($part);
                $this->chdir($part);
            }
        }
        $this->chdir($pwd);
        return $result;
    }
    /**
     * Remove a directory.
     *
     * @see FtpClient::mkdir()
     * @see FtpClient::cleanDir()
     * @see FtpClient::remove()
     * @see FtpClient::delete()
     * @param  string       $directory
     * @param  bool         $recursive Forces deletion if the directory is not empty
     * @return bool
     * @throws FtpException If unable to list the directory to remove
     */
    public function rmdir($directory, $recursive = true)
    {
        if ($recursive) {
            $files = $this->nlist($directory, false, 'rsort');
            // remove children
            foreach ($files as $file) {
                $this->remove($file, true);
            }
        }
        // remove the directory
        return $this->ftp->rmdir($directory);
    }
    /**
     * Empty directory.
     *
     * @see FtpClient::remove()
     * @see FtpClient::delete()
     * @see FtpClient::rmdir()
     *
     * @param  string $directory
     * @return bool
     */
    public function cleanDir($directory)
    {
        if(!$files = $this->nlist($directory)) {
            return $this->isEmpty($directory);
        }
        // remove children
        foreach ($files as $file) {
            $this->remove($file, true);
        }
        return $this->isEmpty($directory);
    }
    /**
     * Remove a file or a directory.
     *
     * @see FtpClient::rmdir()
     * @see FtpClient::cleanDir()
     * @see FtpClient::delete()
     * @param  string $path      The path of the file or directory to remove
     * @param  bool   $recursive Is effective only if $path is a directory, {@see FtpClient::rmdir()}
     * @return bool
     */
    public function remove($path, $recursive = false)
    {
        try {
            if (@$this->delete($path)
                or ($this->isDir($path) and @$this->rmdir($path, $recursive))) {
                    return true;
                }
                return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * Check if a directory exist.
     *
     * @param string $directory
     * @return bool
     * @throws FtpException
     */
    public function isDir($directory)
    {
        $pwd = $this->pwd();
        if ($pwd === false) {
            throw new FtpException('Unable to resolve the current directory');
        }
        if (@$this->chdir($directory)) {
            $this->chdir($pwd);
            return true;
        }
        $this->chdir($pwd);
        return false;
    }
    /**
     * Check if a directory is empty.
     *
     * @param  string $directory
     * @return bool
     */
    public function isEmpty($directory)
    {
        return $this->count($directory, null, false) === 0 ? true : false;
    }
    /**
     * Scan a directory and returns the details of each item.
     *
     * @see FtpClient::nlist()
     * @see FtpClient::rawlist()
     * @see FtpClient::parseRawList()
     * @see FtpClient::dirSize()
     * @param  string $directory
     * @param  bool   $recursive
     * @return array
     */
    public function scanDir($directory = '.', $recursive = false)
    {
        return $this->parseRawList($this->rawlist($directory, $recursive));
    }
    /**
     * Returns the total size of the given directory in bytes.
     *
     * @param  string $directory The directory, by default is the current directory.
     * @param  bool   $recursive true by default
     * @return int    The size in bytes.
     */
    public function dirSize($directory = '.', $recursive = true)
    {
        $items = $this->scanDir($directory, $recursive);
        $size  = 0;
        foreach ($items as $item) {
            $size += (int) $item['size'];
        }
        return $size;
    }
    /**
     * Count the items (file, directory, link, unknown).
     *
     * @param  string      $directory The directory, by default is the current directory.
     * @param  string|null $type      The type of item to count (file, directory, link, unknown)
     * @param  bool        $recursive true by default
     * @return int
     */
    public function count($directory = '.', $type = null, $recursive = true)
    {
        $items  = (null === $type ? $this->nlist($directory, $recursive)
            : $this->scanDir($directory, $recursive));
        $count = 0;
        foreach ($items as $item) {
            if (null === $type or $item['type'] == $type) {
                $count++;
            }
        }
        return $count;
    }
    /**
     * Uploads a file to the server from a string.
     *
     * @param  string       $remote_file
     * @param  string       $content
     * @return FtpClient
     * @throws FtpException When the transfer fails
     */
    public function putFromString($remote_file, $content)
    {
        $handle = fopen('php://temp', 'w');
        fwrite($handle, $content);
        rewind($handle);
        if ($this->fput($remote_file, $handle, FTP_BINARY)) {
            return $this;
        }
        throw new FtpException('Unable to put the file "'.$remote_file.'"');
    }
    /**
     * Uploads a file to the server.
     *
     * @param  string       $local_file
     * @return FtpClient
     * @throws FtpException When the transfer fails
     */
    public function putFromPath($local_file)
    {
        $remote_file = basename($local_file);
        $handle      = fopen($local_file, 'r');
        if ($this->fput($remote_file, $handle, FTP_BINARY)) {
            rewind($handle);
            return $this;
        }
        throw new FtpException(
            'Unable to put the remote file from the local file "'.$local_file.'"'
            );
    }
    /**
     * Upload files.
     *
     * @param  string    $source_directory
     * @param  string    $target_directory
     * @param  int       $mode
     * @return FtpClient
     */
    public function putAll($source_directory, $target_directory, $mode = FTP_BINARY)
    {
        $d = dir($source_directory);
        // do this for each file in the directory
        while ($file = $d->read()) {
            // to prevent an infinite loop
            if ($file != "." && $file != "..") {
                // do the following if it is a directory
                if (is_dir($source_directory.'/'.$file)) {
                    if (!$this->isDir($target_directory.'/'.$file)) {
                        // create directories that do not yet exist
                        $this->mkdir($target_directory.'/'.$file);
                    }
                    // recursive part
                    $this->putAll(
                        $source_directory.'/'.$file, $target_directory.'/'.$file,
                        $mode
                        );
                } else {
                    // put the files
                    $this->put(
                        $target_directory.'/'.$file, $source_directory.'/'.$file,
                        $mode
                        );
                }
            }
        }
        return $this;
    }
    /**
     * Returns a detailed list of files in the given directory.
     *
     * @see FtpClient::nlist()
     * @see FtpClient::scanDir()
     * @see FtpClient::dirSize()
     * @param  string       $directory The directory, by default is the current directory
     * @param  bool         $recursive
     * @return array
     * @throws FtpException
     */
    public function rawlist($directory = '.', $recursive = false)
    {
        if (!$this->isDir($directory)) {
            throw new FtpException('"'.$directory.'" is not a directory.');
        }
        $list  = $this->rawlist($directory);
        $items = array();
        if (!$list) {
            return $items;
        }
        if (false == $recursive) {
            foreach ($list as $path => $item) {
                $chunks = preg_split("/\s+/", $item);
                // if not "name"
                if (empty($chunks[8]) || $chunks[8] == '.' || $chunks[8] == '..') {
                    continue;
                }
                $path = $directory.'/'.$chunks[8];
                if (isset($chunks[9])) {
                    $nbChunks = count($chunks);
                    for ($i = 9; $i < $nbChunks; $i++) {
                        $path .= ' '.$chunks[$i];
                    }
                }
                if (substr($path, 0, 2) == './') {
                    $path = substr($path, 2);
                }
                $items[ $this->rawToType($item).'#'.$path ] = $item;
            }
            return $items;
        }
        $path = '';
        foreach ($list as $item) {
            $len = strlen($item);
            if (!$len
                // "."
                || ($item[$len-1] == '.' && $item[$len-2] == ' '
                    // ".."
                    or $item[$len-1] == '.' && $item[$len-2] == '.' && $item[$len-3] == ' ')
                ){
                    continue;
            }
            $chunks = preg_split("/\s+/", $item);
            // if not "name"
            if (empty($chunks[8]) || $chunks[8] == '.' || $chunks[8] == '..') {
                continue;
            }
            $path = $directory.'/'.$chunks[8];
            if (isset($chunks[9])) {
                $nbChunks = count($chunks);
                for ($i = 9; $i < $nbChunks; $i++) {
                    $path .= ' '.$chunks[$i];
                }
            }
            if (substr($path, 0, 2) == './') {
                $path = substr($path, 2);
            }
            $items[$this->rawToType($item).'#'.$path] = $item;
            if ($item[0] == 'd') {
                $sublist = $this->rawlist($path, true);
                foreach ($sublist as $subpath => $subitem) {
                    $items[$subpath] = $subitem;
                }
            }
        }
        return $items;
    }
    /**
     * Parse raw list.
     *
     * @see FtpClient::rawlist()
     * @see FtpClient::scanDir()
     * @see FtpClient::dirSize()
     * @param  array $rawlist
     * @return array
     */
    public function parseRawList(array $rawlist)
    {
        $items = array();
        $path  = '';
        foreach ($rawlist as $key => $child) {
            $chunks = preg_split("/\s+/", $child, 9);
            if (isset($chunks[8]) && ($chunks[8] == '.' or $chunks[8] == '..')) {
                continue;
            }
            if (count($chunks) === 1) {
                $len = strlen($chunks[0]);
                if ($len && $chunks[0][$len-1] == ':') {
                    $path = substr($chunks[0], 0, -1);
                }
                continue;
            }
            $item = [
                'permissions' => $chunks[0],
                'number'      => $chunks[1],
                'owner'       => $chunks[2],
                'group'       => $chunks[3],
                'size'        => $chunks[4],
                'month'       => $chunks[5],
                'day'         => $chunks[6],
                'time'        => $chunks[7],
                'name'        => $chunks[8],
                'type'        => $this->rawToType($chunks[0]),
            ];
            if ($item['type'] == 'link') {
                $item['target'] = $chunks[10]; // 9 is "->"
            }
            // if the key is not the path, behavior of ftp_rawlist() PHP function
            if (is_int($key) || false === strpos($key, $item['name'])) {
                array_splice($chunks, 0, 8);
                $key = $item['type'].'#'
                    .($path ? $path.'/' : '')
                    .implode(" ", $chunks);
                    if ($item['type'] == 'link') {
                        // get the first part of 'link#the-link.ext -> /path/of/the/source.ext'
                        $exp = explode(' ->', $key);
                        $key = rtrim($exp[0]);
                    }
                    $items[$key] = $item;
            } else {
                // the key is the path, behavior of FtpClient::rawlist() method()
                $items[$key] = $item;
            }
        }
        return $items;
    }
    /**
     * Convert raw info (drwx---r-x ...) to type (file, directory, link, unknown).
     * Only the first char is used for resolving.
     *
     * @param  string $permission Example : drwx---r-x
     *
     * @return string The file type (file, directory, link, unknown)
     * @throws FtpException
     */
    public function rawToType($permission)
    {
        if (!is_string($permission)) {
            throw new FtpException('The "$permission" argument must be a string, "'
                .gettype($permission).'" given.');
        }
        if (empty($permission[0])) {
            return 'unknown';
        }
        switch ($permission[0]) {
            case '-':
                return 'file';
            case 'd':
                return 'directory';
            case 'l':
                return 'link';
            default:
                return 'unknown';
        }
    }
}
