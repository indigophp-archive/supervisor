<?php

namespace Indigo\Supervisor\EventListener;

use Indigo\Supervisor\Supervisor;
use Indigo\Supervisor\Process;
use Indigo\Supervisor\Event\EventInterface;
use Symfony\Component\Process\Process as SymfonyProcess;
use Psr\Log\NullLogger;

class MemmonEventListener extends AbstractEventListener
{
    protected $supervisor;
    protected $program = array();
    protected $group = array();
    protected $any;
    protected $uptime;
    protected $name = null;

    public function __construct(
        Supervisor $supervisor,
        array $program = array(),
        array $group = array(),
        $any,
        $uptime = 60,
        $name = null
    ) {
        $this->supervisor = $supervisor;
        $this->program    = $program;
        $this->group      = $group;
        $this->any        = intval($any);
        $this->uptime     = $uptime;
        $this->name       = $name;
        $this->logger     = new NullLogger;
    }

    protected function doListen(EventInterface $event)
    {
        if (strpos($event->getHeader('eventname', ''), 'TICK') === false) {
            return 0;
        }

        $processes = $this->supervisor->getAllProcess();

        foreach ($processes as $process) {
            if (!$this->checkProcess($process)) {
                continue;
            }

            $mem = $process->getMemUsage();
            $max = $this->getMaxMemory($process);

            if ($max > 0 and $mem > $max) {
                $this->restart($process, $mem);
            }
        }

        return 0;
    }

    protected function restart(Process $process, $mem)
    {
        try {
            $result = $process->restart();
        } catch (\Exception $e) {
            $result = false;
        }

        $message = $result ? '[Success]' : '[Failure]';
        $message .= '(' . ($this->name ? $this->name . '/' : '') . $process['name'] . ') ';
        $context = array('subject' => $message);
        $message .= 'Process restart at ' . $mem . ' bytes';

        $this->logger->info($message, $process, $context);

        return $result;
    }

    protected function checkProcess(Process $process)
    {
        if (!$process->isRunning()) {
            return false;
        } elseif ($process['now'] - $process['start'] < $this->uptime) {
            return false;
        }

        return true;
    }

    protected function getMaxMemory(Process $process)
    {
        $pname = $process['group'] . ':' . $process['name'];

        $mem = array(
            $this->hasProgram($process['name']),
            $this->hasProgram($pname),
            $this->hasGroup($process['group']),
            $this->any,
        );

        return abs(max($mem));
    }

    protected function hasProgram($program)
    {
        return array_key_exists($program, $this->program) ? $this->program[$program] : false;
    }

    protected function hasGroup($group)
    {
        return array_key_exists($group, $this->group) ? $this->group[$group] : false;
    }
}
