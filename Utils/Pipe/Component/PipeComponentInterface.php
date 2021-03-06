<?php

namespace Check24\ShellCommandBundle\Utils\Pipe\Component;

use Check24\ShellCommandBundle\Utils\Pipe\Resource\ResourceInterface;
use Shell\Process;

/**
 * @author    Eugen Ganshorn <eugen.ganshorn@check24.de>
 * @author    Silvester Denk <silvester.denk@check24.de>
 * @copyright 2017 CHECK24 Vergleichsportal Shopping GmbH <http://preisvergleich.check24.de>
 */
interface PipeComponentInterface
{
    public function exec(): PipeComponentInterface;

    public function passParameters(array $parameters);

    public function setInput(ResourceInterface $resource): PipeComponentInterface;

    public function setOutput(ResourceInterface $resource): PipeComponentInterface;

    public function getInput(): ?ResourceInterface;

    public function getOutput(): ?ResourceInterface;

    public function getStreamProcess(): ?Process;

    public function setStreamProcess(Process $process): PipeComponentInterface;

    public function setExpectedExitCodes(array $exitCodes): PipeComponentInterface;

    public function setLastComponentInPipe(bool $lastComponentInPipe): PipeComponentInterface;
}
