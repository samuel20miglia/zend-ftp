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
final class FtpClient extends FtpModel
{

    /**
     *
     * @var \Zend\Ftp\FtpAuthentication
     */
    private $auth;

    /**
     *
     * @throws FtpException
     */
    public function __construct(FtpAuthentication $auth)
    {
        $this->auth = $auth;
        $this->conn = $auth->getConn();

    }

    /**
     * Remove memory
     */
    public function __destruct()
    {
        if ($this->conn) {
            $this->auth->close();
        }
    }
}