<?php

namespace SunlightExtend\Devkit\Component;

/**
 * Devkit event logger
 *
 * @author ShiraNai7 <shira.cz>
 */
class EventLogger
{
    /**
     * array(
     *      event => array(
     *          0 => count,
     *          1 => array(
     *              arg1_name => arg1_type,
     *              ...
     *          )
     *      ),
     *      ...
     * )
     *
     * @var array
     */
    private $log = array();

    /**
     * Log extend event
     *
     * @param string $event
     */
    public function log($event)
    {
        $eventArgs = array_slice(func_get_args(), 1);

        if (sizeof($eventArgs) === 1 && is_array($eventArgs[0])) {
            // standard extend arguments
            $eventArgs = $eventArgs[0];
        }

        if (isset($this->log[$event])) {
            ++$this->log[$event][0];
        } else {
            $argsInfo = array();
            foreach ($eventArgs as $argName => $argValue) {
                $argsInfo[$argName] = gettype($argValue);
            }
            $this->log[$event] = array(1, $argsInfo);
        }
    }

    /**
     * Get log
     *
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }
}
