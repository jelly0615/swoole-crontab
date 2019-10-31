<?php
namespace Cron\Scheduled;

define('FIRST_SECOND', 0);
define('LAST_SECOND', 59);

define('FIRST_MIN', 0);
define('LAST_MIN', 59);
define('FIRST_HOUR', 0);
define('LAST_HOUR', 23);
define('FIRST_DAY', 1);
define('LAST_DAY', 31);
define('FIRST_MONTH', 1);
define('LAST_MONTH', 12);
define('FIRST_WEEK', 0);
define('LAST_WEEK', 6);

use Symfony\Component\Process\Process;

class Task
{
    private $taskString;

    private $second;

    private $min;

    private $hour;

    private $day;

    private $month;

    private $week;

    private $command;

    private $process;

    private $runTime;

    /**
     * @var string $taskString example: 10 * * * * php example.php
     */
    public function __construct(string $taskString)
    {   
	if(!$taskString){
	    return true;
	}
        $this->taskString = $taskString;
        $this->runTime = time();
        $this->initialize();
    }

    /**
     * 初始化任务配置
     */
    private function initialize()
    {
        //过滤多余的空格
        $rule = array_filter(explode("|", $this->taskString), function($value) {
            return $value != "";
        });
        if (count($rule) < 7) {
            throw new \ErrorException("'taskString' parse failed");
        }
//	var_dump($rule);
        $this->second = $this->format($rule[0], 'second');
        $this->min = $this->format($rule[1], 'min');
        $this->hour= $this->format($rule[2], 'hour');
        $this->day = $this->format($rule[3], 'day');
        $this->month = $this->format($rule[4], 'month');
        $this->week= $this->format($rule[5], 'week');
        $this->command = array_slice($rule, 6);
// 	var_dump($this->taskString);
//        var_dump($rule);
//        var_dump($this->second);
//        var_dump($this->min);
//        var_dump($this->hour);
//        var_dump($this->day);
//        var_dump($this->month);
//        var_dump($this->week);
//        var_dump($this->command);
    }

    private function format($value, $field)
    {
        if ($value === '*') {
            return $value;
        }
        if (is_numeric($value)) {
            return [$this->checkFieldRule($value, $field)];
        }
        $steps = explode(',', $value);
        $scope = [];
        foreach ($steps as $step) {
            if (strpos($step, '-') !== false) {
                $range = explode('-', $step);
                $scope = array_merge($scope, range(
                    $this->checkFieldRule($range[0], $field),
                    $this->checkFieldRule($range[1], $field)
                ));
                continue;
            }
            if (strpos($step, '/') !== false) {
                $inter = explode('/', $step);
                $confirmInter = isset($inter[1]) ? $inter[1] : $inter[0];
                if ($confirmInter === '/') {
                    $confirmInter = 1; 
                }
                $scope = array_merge($scope, range(
                    constant('FIRST_' . strtoupper($field)),
                    constant('LAST_' . strtoupper($field)),
                    $confirmInter
                ));
                continue;
            }
            $scope[] = $step;
        }
        return $scope;
    }

    private function checkFieldRule($value, $field)
    {
        $first = constant('FIRST_' . strtoupper($field));
        $last  = constant('LAST_' . strtoupper($field));
        if ($value < $first) {
            return $first;
        }
        if ($value > $last) {
            return $last;
        }
        return (int) $value;
    }

    public function getTimeAttribute($attribute)
    {
        if (!in_array($attribute, ['second', 'min', 'hour', 'day', 'month', 'week', 'runTime'])) return null;
        return $this->{$attribute} ?? null;
    }

    public function setRunTime($time)
    {
        $this->runTime = $time;
    }

    public function run()
    {
        if (null === $this->process) {
            $this->process = new Process(implode(" ", $this->command));
        }
        echo "process statring ".implode(" ", $this->command)."\r\n";
	//异步执行命令
        $this->process->start();
    }
}
