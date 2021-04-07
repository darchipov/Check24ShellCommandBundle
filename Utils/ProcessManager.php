<?php

namespace Check24\ShellCommandBundle\Utils;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Shell\Process;

/**
 * @author    Eugen Ganshorn <eugen.ganshorn@check24.de>
 * @author    Silvester Denk <silvester.denk@check24.de>
 * @copyright 2017 CHECK24 Vergleichsportal Shopping GmbH <http://preisvergleich.check24.de>
 */
class ProcessManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Process[]
     */
    protected $processes = [];

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param Process $process
     */
    public function addProcess(Process $process)
    {
        $this->processes[] = $process;
    }

    /**
     * @param Process|NULL $excludeProcess
     */
    public function killAllProcesses(Process $excludeProcess = null): void
    {
        $processes = $this->processes;
        krsort($processes);

        foreach ($processes as $process) {
            if ($process === $excludeProcess) {
                continue;
            }

            $process->kill();
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function waitAllProcesses(): array
    {
        $processes = $this->processes;
        krsort($processes);

        $timeout = -1;
        $results = [];
        foreach ($processes as $process) {
            try {
                $command = $process->getCommand();
                if ($command instanceof ParameterCommand) {
                    $name = $command->getName();
                } else {
                    $name = $command->serialize();
                }

                $process->wait($timeout);
                $timeout = 0;

                $outputHandler = $process->getOutputHandler();

                $results[$name] = [
                    Process::STDOUT => $outputHandler->readStdOut(),
                    Process::STDERR => $outputHandler->readStdErr(),
                ];
            } catch (\Exception $e) {
                $this->killAllProcesses();

                $this->logger->error('Error: ' . $e->getMessage(), ['exception' => $e]);

                throw $e;
            }
        }

        $this->processes = [];

        return $results;
    }
}
