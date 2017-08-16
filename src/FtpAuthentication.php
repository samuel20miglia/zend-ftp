<?php
declare(strict_types=1);

namespace Zend\Ftp;

use Zend\Ftp\Exception\FtpAuthenticationException;
use Zend\Ftp\Exception\FtpException;

/**
 * Manage the ftp authentiation and connection
 *
 * @author samuel ventimiglia
 * @since 2017/08/16
 * @version 0.0.1
 */
final class FtpAuthentication
{
    /**
     *
     * @var resource
     */
    private $stream = null;

    /**
     *
     * @var string
     */
    private $username = null;

    /**
     *
     * @var string
     */
    private $password = null;

    /**
     * The connection with the server
     *
     * @var resource
     */
    private $conn;


    /**
     * @return the $conn
     */
    public function getConn()
    {
        return $this->conn;
    }

    /**
     * @param resource $stream
     * @param string $username
     * @param string $password
     * @throws FtpAuthenticationException
     */
    public function __construct()
    {
        $extMessage = 'FTP extension is not loaded!, please check it.';
        if (! extension_loaded('ftp')) {
            throw new FtpException($extMessage);
        }
    }

    /**
     * @return the $stream
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @return the $username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return the $password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param resource $stream
     */
    public function setStream($stream)
    {
        $this->stream = $stream;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Login
     * @throws FtpException
     * @return \Zend\Ftp\FtpAuthentication
     */
    protected function login()
    {
        try {
            $result = ftp_login($this->conn,$this->username, $this->password);

            if ($result === false) {
                throw new FtpAuthenticationException('Login incorrect');
            }

        } catch (\Exception $e) {
            error_log($e->getMessage() . PHP_EOL . $e->getFile(). PHP_EOL);
            throw new FtpAuthenticationException($e->getMessage() . PHP_EOL . $e->getFile(). PHP_EOL);
        }
        return $result;
    }

    /**
     * Opens a FTP connection ssl by default
     *
     * @param int $port
     * @param int $timeout
     * @param bool $connSSL
     * @throws FtpException
     * @return resource
     */
    public function connect(int $port = 21, int $timeout = 90, bool $connSSL = true)
    {

        try {
            /* checkParameters*/
            $this->checkParameters($port,$timeout,$connSSL);

            /* connection to the server */
            if ($connSSL) {
                $this->conn = ftp_ssl_connect($this->stream, $port, $timeout);
            } else {
                $this->conn = ftp_connect($this->stream, $port, $timeout);
            }

            if ($this->conn) {
                $this->login();
            }
        } catch (\Exception $e) {
            error_log($e->getMessage() . PHP_EOL . $e->getFile() . PHP_EOL);
            throw new FtpException($e->getMessage());
        }
    }

    /**
     * checkParameters
     *
     * @param int $port
     * @param int $timeout
     * @param bool $connSSL
     * @throws FtpException
     */
    private function checkParameters(int $port,int $timeout,bool $connSSL)
    {
        /* string $host parameter */
        if (empty($this->stream) || \is_null($this->stream)) {
            throw new FtpException('Missing host?');
        }
        /* int $port parameter */
        if (empty($port) || \is_null($port)) {
            throw new FtpException('Missing port?');
        }
        /* int $timeout parameter */
        if (empty($timeout) || \is_null($timeout)) {
            throw new FtpException('Missing timeout?');
        }
        /* bool $connSSL parameter */
        if (empty($connSSL) || \is_null($connSSL)) {
            throw new FtpException('Missing ssl?');
        }
    }

    public function close(){
        return ftp_close($this->conn);
    }
}
