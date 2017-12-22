<?php

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Shell\Commands\Command;
use Shell\Output\EchoOutputHandler;
use Shell\Process;
use Shopping\ShellCommandBundle\Utils\ProcessManager;

/**
 * @author    Eugen Ganshorn <eugen.ganshorn@check24.de>
 * @copyright 2017 CHECK24 Vergleichsportal Shopping GmbH <http://preisvergleich.check24.de>
 */
class ProcessManagerTest extends TestCase
{
    /** @var  ProcessManager */
    protected $sut;

    public function setUp()
    {
        $this->sut = new ProcessManager();
    }

    public function testWaitAllProcesses()
    {
        $command = $this->createMock(Command::class);

        $command
            ->expects($this->once())
            ->method('serialize')
        ;

        $outputHandler = $this->createMock(EchoOutputHandler::class);

        $outputHandler
            ->expects($this->once())
            ->method('readStdOut')
        ;

        $outputHandler
            ->expects($this->once())
            ->method('readStdErr')
        ;

        $process = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([$command, null, $outputHandler])
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        $process
            ->expects($this->once())
            ->method('wait')
        ;

        $process
            ->expects($this->once())
            ->method('getCommand')
            ->willReturn($command)
        ;

        $process
            ->expects($this->once())
            ->method('getOutputHandler')
            ->willReturn($outputHandler)
        ;


        $logger = $this->createMock(Logger::class);

        $logger
            ->expects($this->never())
            ->method('error')
        ;

        $this->sut->setLogger($logger);

        $this->sut->addProcess($process);
        $results = $this->sut->waitAllProcesses();

        $expected = [
            '' => [
                1 => null,
                2 => null,
            ],
        ];

        $this->assertSame($expected, $results);
    }
}