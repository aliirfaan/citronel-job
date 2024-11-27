<?php

namespace aliirfaan\CitronelJob\Traits;

use aliirfaan\CitronelJob\Models\JobPolicy;

trait HasJobPolicy
{
    /**
     * Method getJobPolicy
     *
     * @param string $jobPolicyId
     *
     * @return null | JobPolicy
     */
    public function getJobPolicy($jobPolicyId)
    {
        return JobPolicy::where('id', $jobPolicyId)
        ->where('active', 1)
        ->first();
    }
    
    /**
     * check if isRetry based on extra job data - for external use
     *
     * @param array $jobExtra
     *
     * @return bool
     */
    public function isRetry(array $jobExtra = []): bool
    {
        return $jobExtra['is_retry'] ?? false;
    }
    
    /**
     * get number of attemtps based on extra job data - for external use
     *
     * @param array $jobExtra [explicite description]
     *
     * @return int
     */
    public function attemptsCount(array $jobExtra = []): int
    {
        return intval($jobExtra['attempts']) ?? 0;
    }
    
    /**
     * check if isLastAttempt based on extra job data - for external use
     *
     * @param array $jobExtra [explicite description]
     *
     * @return bool
     */
    public function isLastAttempt(array $jobExtra = []): bool
    {
        return $jobExtra['is_last_attempt'] ?? false;
    }
}
