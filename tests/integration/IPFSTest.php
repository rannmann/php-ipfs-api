<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use rannmann\PhpIpfsApi\IPFS;

class IPFSTest extends TestCase
{
    /**
     * @var IPFS
     */
    private $ipfs;

    public function setUp()
    {
        parent::setUp();

        $this->ipfs = new IPFS('staging-ipfs-a1.thh.io');
    }

    /**
     * @expectedException           Exception
     * @expectedExceptionMessage    IPFS Error: No Response
     */
    public function test_cannot_connect(): void
    {
        $ipfs = new IPFS('rannmann.com', 1, 2);
        $ipfs->setCurlTimeout(1);
        $ipfs->version();
    }

    public function test_version(): void
    {
        $result = $this->ipfs->version();
        $this->assertTrue(is_string($result), "Version result is a string");
        $this->assertTrue(strpos($result, '.') !== false, "Version has at least once decimal place");
    }

}