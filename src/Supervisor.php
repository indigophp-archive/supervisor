<?php

/*
 * This file is part of the Indigo Supervisor package.
 *
 * (c) Indigo Development Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indigo\Supervisor;

use Indigo\Supervisor\Connector\ConnectorInterface;

/**
 * Manage supervisor instance
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class Supervisor
{
    /**
     * Service states
     */
    const SHUTDOWN   = -1;
    const RESTARTING = 0;
    const RUNNING    = 1;
    const FATAL      = 2;

    /**
     * Connector object
     *
     * @var ConnectorInterface
     */
    protected $connector;

    /**
     * Creates new Supervisor instance
     *
     * @param ConnectorInterface $connector
     */
    public function __construct(ConnectorInterface $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Returns connector object
     *
     * @return ConnectorInterface
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * Sets connector
     *
     * @param ConnectorInterface $connector
     *
     * @return this
     */
    public function setConnector(ConnectorInterface $connector)
    {
        $this->connector = $connector;

        return $this;
    }

    /**
     * Checks whether connecting to a local Supervisor instance
     *
     * @return boolean
     */
    public function isLocal()
    {
        return $this->connector->isLocal();
    }

    /**
     * Calls a method
     *
     * @param string $namespace Namespace of method
     * @param string $method    Method name
     * @param array  $arguments Argument list
     *
     * @return mixed
     */
    public function call($namespace, $method, array $arguments = array())
    {
        return $this->connector->call($namespace, $method, $arguments);
    }

    /**
     * Magic __call method
     *
     * Handles all calls to supervisor namespace
     */
    public function __call($method, $arguments)
    {
        $process = reset($arguments);

        if ($process instanceof Process) {
            array_shift($arguments);

            return $process->call('supervisor', $method, $arguments);
        }

        return $this->call('supervisor', $method, $arguments);
    }

    /**
     * Status and control
     */

    /**
     * Is service running?
     *
     * @return boolean
     */
    public function isRunning()
    {
        return $this->isState();
    }

    /**
     * Checks if supervisord is in given state
     *
     * @param integer $isState
     *
     * @return boolean
     */
    public function isState($isState = self::RUNNING)
    {
        $state = $this->getState();

        return $state['statecode'] == $isState;
    }

    /**
     * Process control
     */

    /**
     * Returns all processes as Process objects
     *
     * @return array Array of Process objects
     *
     * @codeCoverageIgnore
     */
    public function getAllProcesses()
    {
        $processes = $this->getAllProcessInfo();

        foreach ($processes as $key => $processInfo) {
            $processes[$key] = new Process($processInfo, $this->connector);
        }

        return $processes;
    }

    /**
     * Returns a specific Process
     *
     * @param string $name Process name or 'group:name'
     *
     * @return Process
     */
    public function getProcess($name)
    {
        return new Process($name, $this->connector);
    }

    /**
     * Return the version of the RPC API used by supervisord
     *
     * @return string version version id
     */
    public function getAPIVersion()
    {
        return $this->connector->call('supervisor', 'getAPIVersion');
    }

    /**
     * Return the version of the supervisor package in use by supervisord
     *
     * @return string version version id
     */
    public function getSupervisorVersion()
    {
        return $this->connector->call('supervisor', 'getSupervisorVersion');
    }

    /**
     * Return identifiying string of supervisord
     *
     * @return string identifier identifying string
     */
    public function getIdentification()
    {
        return $this->connector->call('supervisor', 'getIdentification');
    }

    /**
     * Return the PID of supervisord
     *
     * @return integer PID
     */
    public function getPID()
    {
        return $this->connector->call('supervisor', 'getPID');
    }

    /**
     * Read length bytes from the main log starting at offset
     *
     * @param integer $offset offset to start reading from
     * @param integer $length length number of bytes to read from the log
     *
     * @return string result Bytes of log
     */
    public function readLog($offset, $length)
    {
        return $this->connector->call('supervisor', 'readLog', array($offset, $length));
    }

    /**
     * Clear the main log.
     *
     * @return boolean result always returns True unless error
     */
    public function clearLog()
    {
        return $this->connector->call('supervisor', 'clearLog');
    }

    /**
     * Start all processes listed in the configuration file
     *
     * @param boolean $wait Wait for each process to be fully started
     *
     * @return array result An array containing start statuses
     */
    public function startAllProcesses($wait = true)
    {
        return $this->connector->call('supervisor', 'startAllProcesses', array($wait));
    }

    /**
     * Stop all processes listed in the configuration file
     *
     * @param boolean $wait Wait for each process to be fully stoped
     *
     * @return array result An array containing start statuses
     */
    public function stopAllProcesses($wait = true)
    {
        return $this->connector->call('supervisor', 'stopAllProcesses', array($wait));
    }

    /**
     * Send an event that will be received by event listener subprocesses subscribing to the RemoteCommunicationEvent.
     *
     * @param string $type String for the “type” key in the event header
     * @param string $data Data for the event body
     *
     * @return boolean Always return True
     */
    public function sendRemoteCommEvent($type, $data)
    {
        return $this->connector->call('supervisor', 'sendRemoteCommEvent', array($type, $data));
    }

    /**
     * Clear all process log files
     *
     * @return boolean result Always return true
     */
    public function clearAllProcessLogs()
    {
        return $this->connector->call('supervisor', 'clearAllProcessLogs');
    }
}
