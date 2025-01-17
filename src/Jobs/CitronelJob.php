<?php

namespace aliirfaan\CitronelJob\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use aliirfaan\CitronelJob\Traits\HasJobPolicy;
use InvalidArgumentException;

/**
 * CitronelJob
 * A job class that takes job settings from job policy table
 * Other jobs that use settings from job policy table can extend this job
 */
class CitronelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, HasJobPolicy;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions;
    
    /**
     * The job policy key in job_policies table
     *
     * @var string
     */
    public $jobPolicyId;

    /**
     * jobPolicy object
     *
     * @var mixed
     */
    public $jobPolicy;
    
    /**
     * isLastAttempt
     *
     * @var bool
     */
    public $isLastAttempt;

    /**
     * Create a new job instance.
     *
     * @param string $jobPolicyId job policy key in job_policies table
     *
     * @return void
     */
    public function __construct($jobPolicyId)
    {
        $this->jobPolicyId = $jobPolicyId;
        $this->jobPolicy = $this->getJobPolicy($this->jobPolicyId);

        $this->isLastAttempt = false;
 
        // $this->jobPolicy may return null if job is not active or not found
        if(!is_null($this->jobPolicy)) {
            $this->tries = $this->jobPolicy->max_retry_count;
            $this->backoff =  $this->generateJobBackoff($this->jobPolicy->backoff_period);
            $this->maxExceptions = $this->jobPolicy->max_exceptions_count;
            $this->onQueue($this->jobPolicy->queue);
            $this->onConnection($this->jobPolicy->connection);
        }
    }

    /**
     * job backOff period
     *
     * @param int|string $backOff period to wait before attempting a job, can be an int or string.
     * If delimited string return array, used for exponential backoff
     * @param string $delimiter
     *
     * @return void
     */
    public function generateJobBackoff($backOff, $delimiter = ',')
    {
        if (str_contains($backOff, $delimiter)) {
            return explode($delimiter, $backOff);
        }

       return intval($backOff);
    }
    
    /**
     * Job data for external use
     *
     * @return array
     */
    public function generateJobExtra()
    {
        $isRetry = $this->attempts() > 1 ? true : false;

        return [
            'attempts' => $this->attempts(),
            'is_last_attempt' => $this->isLastAttempt,
            'is_retry' => $isRetry,
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if(is_null($this->jobPolicy)) {
            throw new InvalidArgumentException('Job not found.');
        }
        
        $attempts = $this->attempts();
        if ($attempts == $this->job->maxTries()) {
            $this->isLastAttempt = true;
        }
    }
}
