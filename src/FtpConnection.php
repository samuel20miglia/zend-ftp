<?php

use Zend\Ftp\Exception\FtpException;

/**
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
     */
    public function __construct ()
    {

    }

    /**
     * Remove memory
     */
    function __destruct ()
    {
        $this->conn = null;
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
    public function connect(string $host,
        int $port = 21,
        int $timeout = 90,
        bool $connSSL = true)
    {
        /* check connection */
        $this->checkConnection();

        try {
            /* string $host parameter */
            if (empty($host) || \is_null($host)) {
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

            /* connection to the server */
            if ($connSSL) {
                $this->conn = ftp_ssl_connect($host, $port, $timeout);
            }else {
                $this->conn = ftp_connect($host, $port, $timeout);
            }

        } catch (Exception $e) {
            error_log($e->getMessage() . PHP_EOL . $e->getFile(). PHP_EOL);
            throw new FtpException($e->getMessage());
        }
        return $this->conn;
    }

    /**
     * Check opened connection
     *
     * @return resource
     */
    private function checkConnection(){
        /* */
        if (null !== $this->conn){
            error_log('connection already open');
            return $this->conn;
        }
    }
}