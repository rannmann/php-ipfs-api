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

        $this->ipfs = new IPFS();
    }

    /**
     * @expectedException           Exception
     * @expectedExceptionMessage    IPFS Error: No Response
     */
    public function test_cannotConnect(): void
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

    public function test_add(): void
    {
        $hash = $this->ipfs->add($this->getTestString(), ['pin' => false]);

        $this->assertEquals(
            $this->getTestFileHash(),
            $hash,
            "Uploaded string hash is expected value.  Possibly using another algorithm?"
        );
    }

    public function test_addFromPath(): void
    {
        $hash = $this->ipfs->addFromPath($this->getTestFile(), ['pin' => false]);
        $this->assertEquals(
            $this->getTestFileHash(),
            $hash,
            "Uploaded file hash is expected value.  Possibly using another algorithm?"
        );
    }

    public function test_addFromUrl(): void
    {
        $hash = $this->ipfs->addFromUrl('https://i.imgur.com/QjXJYZk.png');
        $this->assertTrue(is_string($hash), "Adding image from URL resulted in a string hash");
        $this->assertTrue(strlen($hash) >= 10, "Adding image from URL resulted in hash with at least 10 length");
    }

    public function test_get(): void
    {
        $hash = $this->ipfs->add($this->getTestString(), ['pin' => false]);
        $result = $this->ipfs->get($hash);

        $this->assertEquals($this->getTestString(), $result);
    }

    public function test_ls_string(): void
    {
        $hash = $this->ipfs->add($this->getTestString(), ['pin' => false]);
        $result = $this->ipfs->ls($hash);

        $this->assertTrue(is_array($result), "Result is array");
        // should be empty because we uploaded a string, not a directory
        $this->assertTrue(empty($result), "Result array is empty");
    }

    public function test_ls_directory(): void
    {
        // TODO Try an "ls" test again but with some expected results
    }

    public function test_size(): void
    {
        $hash = $this->ipfs->add($this->getTestString(), ['pin' => false]);
        $result = $this->ipfs->size($hash);
        $this->assertEquals(25, $result);
    }

    public function test_pinAdd(): void
    {
        $hash = $this->ipfs->add($this->getTestString(), ['pin' => false]);
        $result = $this->ipfs->pinAdd($hash);

        $this->assertCount(1, $result, "Pinning one item results in an array of length 1");
        $this->assertEquals($hash, $result[0], "Returned pin hash is equal to input hash");
    }

    public function test_pinRm(): void
    {
        $hash = $this->ipfs->add($this->getTestString()); // Default pins
        $result = $this->ipfs->pinRm($hash);

        $this->assertCount(1, $result, "Unpinning one item results in an array of length 1");
        $this->assertEquals($hash, $result[0], "Returned unpin hash is equal to input hash");
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage IPFS Error 0: not pinned or pinned indirectly
     */
    public function test_pinRm_notPinned(): void
    {
        $hash = $this->ipfs->add($this->getTestString(), ['pin' => false]);
        $this->ipfs->pinRm($hash);
    }

    public function test_id(): void
    {
        $result = $this->ipfs->id();
        $this->assertTrue(is_array($result), "Node identifier information is an array");
        $this->assertTrue(array_key_exists('ID', $result), "Node identifier has ID key");
        $this->assertTrue(array_key_exists('PublicKey', $result), "Node identifier has PublicKey key");
        $this->assertTrue(array_key_exists('Addresses', $result), "Node identifier has Addresses key");
        $this->assertTrue(array_key_exists('AgentVersion', $result), "Node identifier has AgentVersion key");
        $this->assertTrue(array_key_exists('ProtocolVersion', $result), "Node identifier has ProtocolVersion key");
    }



    /**
     * Returns the test file's location which contains a copy of the test string
     *
     * @return string
     */
    private function getTestFile(): string
    {
        return 'testfile.txt';
    }

    /**
     * @return string
     */
    private function getTestFileHash(): string
    {
        return "QmSeDEQu6Py1RitQztLCMo2udw5soky3ZbC2QRpXhiC6dz";
    }

    /**
     * Same string that exists in the test file, which should result in the same hash when uploaded
     *
     * @return string
     */
    private function getTestString(): string
    {
        return "IPFS PHPUnit Test";
    }

}