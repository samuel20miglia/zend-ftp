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
     * @var string
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
     * @param string $stream
     * @param string $username
     * @param string $password
     * @throws FtpAuthenticationException
     */
    public function __construct(string $stream, string $username, string $password)
    {
        $extMessage = 'FTP extension is not loaded!, please check it.';
        if (!extension_loaded('ftp')) {
            throw new FtpException($extMessage);
        }

        if (empty($username) || \is_null($username)) {
            throw new FtpAuthenticationException('Missing username.');
        }

        if (empty($password) || \is_null($password)) {
            throw new FtpAuthenticationException('Missing password.');
        }
    }

    /**
     *
     * @throws FtpException
     * @return \Zend\Ftp\FtpAuthentication
     */
    private function login()
    {
        try {
            $result = $this->ftp->login($this->username, $this->password);

            if ($result === false) {
                throw new FtpException('Login incorrect');
            }

        } catch (\Exception $e) {
            error_log($e->getMessage() . PHP_EOL . $e->getFile(). PHP_EOL);
        }
        return $this;
    }
}
