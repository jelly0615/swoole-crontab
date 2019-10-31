<?php
namespace Cron;

use Cron\Scheduled\Task;

class Cron
{
    private $job = null;

    private $prevTime = null;

    public function __construct(array $tasks = [])
    {
        $this->job = new Job();
        if ($tasks) {
            $this->load($tasks);
        }
    }

    public function load(array $tasks)
    {
	$taskCount = count($tasks);
	echo "loadind ".$taskCount. "tasks" . "\r\n";
        foreach ($tasks as $task) {
            $this->job->addJob(new Task($task));
        }
    }   
    
    final public function tickCallback($timeId, $params = null)
    {
        $current = time();
        //week, month, day, hour, min
        $ref = explode('|', date('w|n|j|G|i|s', $current));
        foreach ($this->job as $k=>$task) {
	    //echo "tickCallback the ".$k. "task" . "\r\n";	
            $ready = 0;
            //$diff = $task->getTimeAttribute('runTime') - $current;
            //对应上面的$ref数组
            foreach (['week', 'month', 'day', 'hour', 'min', 'second'] as $key => $field) {
		$value = $task->getTimeAttribute($field);
		if ($value === '*') {
                    $ready += 1;
                    continue;
                }
                $ready += in_array($ref[$key], $value) ? 1: 0;
            }
            if (6 === $ready) {
		echo "begining the ".$k. " task" . "\r\n";
                //执行任务
                $task->run();
                //更新运行时间
                $task->setRunTime($current);
            }
        }
        $this->prevTime = $current;
        return true;
        //swoole_timer_clear($timeId);
    }


    public function start()
    {
        swoole_timer_tick(1000, [$this, 'tickCallback']);
    }
}
