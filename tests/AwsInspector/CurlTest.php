<?php

namespace AwsInspector\Tests;

class CurlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \AwsInspector\Helper\Curl
     */
    protected $curl;

    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $connectionMock = $this->getMock('\AwsInspector\Ssh\Connection', [], [], '', false);
        $connectionMock->method('exec')->willReturn(
            [
                'returnVar' => 0,
                'output' => [
                    '<HTML><HEAD><meta http-equiv="content-type" content="text/html;charset=utf-8">',
                    '<TITLE>301 Moved</TITLE></HEAD><BODY>',
                    '<H1>301 Moved</H1>',
                    'The document has moved',
                    '<A HREF="http://www.google.com/">here</A>.',
                    '</BODY></HTML>',
                    'HTTP/1.1 301 Moved Permanently',
                    'Location: http://www.google.com/',
                    'Content-Type: text/html; charset=UTF-8'
                ]
            ]
        );
        $this->curl = new \AwsInspector\Helper\Curl('http://google.com', [], $connectionMock);
    }

    public function testSingleHeader()
    {
        $this->assertEquals('http://www.google.com/', $this->curl->getResponseHeader('Location'));
    }

    public function testHeaders()
    {
        $this->assertEquals(
            [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Location' => 'http://www.google.com/',
            ],
            $this->curl->getResponseHeaders()
        );
    }

    public function testResponseStatus()
    {
        $this->assertEquals('HTTP/1.1 301 Moved Permanently', $this->curl->getResponseStatus());
    }

    public function testStatusCode()
    {
        $this->assertEquals('301', $this->curl->getResponseCode());
    }

    public function testBody()
    {
        $this->assertContains('<H1>301 Moved</H1>', $this->curl->getResponseBody());
    }
}
