<?php
declare(strict_types = 1);
namespace Zend\Ftp;

use Zend\Ftp\Exception\FtpException;

/**
 *
 * @author Ventimiglia Samuel
 * @since 2017/08/16
 * @version 0.0.1
 */
final class FtpConnection
{

    /**
     * The connection with the server
     *
     * @var resource
     */
    private $conn = null;

    /**
     *
     * @var \Zend\Ftp\FtpAuthentication
     */
    private $auth;

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
     *
     * @var string
     */
    private $host;

    /**
     * PHP FTP functions wrapper.
     *
     * @var FtpModel
     */
    private $ftp;

    /**
     *
     * @throws FtpException
     */
    public function __construct()
    {
        $extMessage = 'FTP extension is not loaded!, please check it.';
        if (! extension_loaded('ftp')) {
            throw new FtpException($extMessage);
        }
    }

    /**
     *
     * @return the $username
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     *
     * @return the $password
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     *
     * @return the $host
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     *
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     *
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * Opens a FTP connection ssl by default
     *
     * @param string $host
     * @param int $port
     * @param int $timeout
     * @param bool $connSSL
     * @throws FtpException
     * @return resource
     */
    public function connect(int $port = 21, int $timeout = 90, bool $connSSL = true)
    {
        /* check connection */
        $this->checkConnection();
        /* */
        if (null !== $this->conn) {
            error_log('connection already open');
            return $this->conn;
        }
        try {
            /* checkParameters*/
            $this->checkParameters($port,$timeout,$connSSL);

            /* connection to the server */
            if ($connSSL) {
                $this->conn = ftp_ssl_connect($this->getHost(), $port, $timeout);
            } else {
                $this->conn = ftp_connect($this->getHost(), $port, $timeout);
            }

            if ($this->conn) {
                $this->ftp = new FtpModel($this->conn);
                $this->login();
            }
        } catch (\Exception $e) {
            error_log($e->getMessage() . PHP_EOL . $e->getFile() . PHP_EOL);
            throw new FtpException($e->getMessage());
        }
        return $this->conn;
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
        if (empty($this->getHost()) || \is_null($this->getHost())) {
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

    /**
     * Login
     *
     * @throws FtpException
     * @return \Zend\Ftp\FtpAuthentication
     */
    private function login(): FtpAuthentication
    {
        try {
            $this->auth = new FtpAuthentication($this->ftp, $this->conn, $this->getUsername(), $this->getPassword());
            $result = $this->auth->login();

            if ($result === false) {
                throw new FtpException('Login incorrect');
            }
        } catch (\Exception $e) {
            error_log($e->getMessage() . PHP_EOL . $e->getFile() . PHP_EOL);
            throw new FtpException($e->getMessage() . PHP_EOL . $e->getFile() . PHP_EOL);
        }
        return $result;
    }

    /**
     * Get the help information of the remote FTP server.
     *
     * @return array
     */
    public function help(): array
    {
        return $this->ftp->help();
    }

    /**
     * Remove memory
     */
    public function __destruct()
    {
        if ($this->conn) {
            $this->ftp->close();
        }
    }
}