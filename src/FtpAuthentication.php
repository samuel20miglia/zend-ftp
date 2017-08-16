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
     *
     * @var FtpModel
     */
    private $ftp;

    /**
     * @param FtpModel $ftp
     * @param resource $stream
     * @param string $username
     * @param string $password
     * @throws FtpAuthenticationException
     */
    public function __construct(FtpModel $ftp, $stream, string $username, string $password)
    {

         if (empty($stream) || \is_null($stream)) {
             throw new FtpAuthenticationException('Missing stream.');
         }

        if (empty($username) || \is_null($username)) {
            throw new FtpAuthenticationException('Missing username.');
        }

        if (empty($password) || \is_null($password)) {
            throw new FtpAuthenticationException('Missing password.');
        }

        $this->stream = $stream;
        $this->username = $username;
        $this->password = $password;
        $this->ftp = $ftp;
    }

    /**
     * Login
     * @throws FtpException
     * @return \Zend\Ftp\FtpAuthentication
     */
    public function login()
    {
        try {
            $result = $this->ftp->login($this->stream,$this->username, $this->password);

            if ($result === false) {
                throw new FtpAuthenticationException('Login incorrect');
            }

        } catch (\Exception $e) {
            error_log($e->getMessage() . PHP_EOL . $e->getFile(). PHP_EOL);
            throw new FtpAuthenticationException($e->getMessage() . PHP_EOL . $e->getFile(). PHP_EOL);
        }
        return $this;
    }
}
