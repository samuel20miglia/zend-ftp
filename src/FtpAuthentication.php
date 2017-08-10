<?php
declare(strict_types=1);

namespace Zend\Ftp;

/**
 * @author samuel ventimiglia
 *
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

    public function __construct(string $stream, string $username, string $password)
    {

    }
}

