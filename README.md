# Citronel job

Use jobs by loading job settings from a database table for better configuration management.

## Features
* Migration to create table for storing job configuration.
* Job class that loads a job based on an id found in database table.

## Requirements

* [Composer](https://getcomposer.org/)
* [Laravel](http://laravel.com/)

## Installation

* Install the package using composer:

```bash
 $ composer require aliirfaan/citronel-job
```

## Traits
* HasJobPolicy  
Use this trait to get job policy by id in your service class

## Jobs
* CitronelJob  
Extend this class in your job class

## Usage

## Migration

Run migration to create job_polices table

```bash
 $ php artisan migrate
```
## Seeder

Create a seeder to add rows to the job_polices table. Below is an example seeder run().

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobPolicySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            [
                'id' => 'currency_platform_refresh_currency_rate',
                'title' => 'Get latest currency rate from platform and update local currency table',
                'max_retry_count' => 4,
                'max_exceptions_count' => 4,
                'backoff_period' => '30,180,300',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'fulfill_item',
                'title' => 'Fulfill order item',
                'max_retry_count' => 3,
                'max_exceptions_count' => 3,
                'backoff_period' => '30,180',
                'queue' => 'order_fulfilment',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($rows as $row) {
            DB::table('job_policies')->insert($row);
        }
    }
}

```
Run the seeder

```bash
 $ php artisan db:seed --class=JobPolicySeeder
```

## Example usage: Extend CitronelJob in your job class

```php
<?php

namespace App\Jobs\Api\v1\CurrencyPlatform;

use aliirfaan\CitronelJob\Jobs\CitronelJob;
use App\Services\Api\v1\Currency\CurrencyService;

class RefreshCurrencyRate extends CitronelJob
{
    public $currencyService;

    /**
     * Create a new job instance.
     */
    public function __construct($jobPolicyId)
    {
        parent::__construct($jobPolicyId);

        $this->currencyService = new CurrencyService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        parent::handle();

        // you can pass job data to external service
        $jobExtra = $this->generateJobExtra();

        $correlationToken = $this->helperService->generateCorrelationToken();
        $refreshExchangeRateRespponse = $this->currencyService->refreshExchangeRate($correlationToken, $jobExtra);
        if (!$refreshExchangeRateRespponse['success']) {
            // fail job
            throw new \Exception($refreshExchangeRateRespponse['message']);
        }
    }
}

```
## Example usage: use trait to get job policy

```php
<?php

namespace App\Services\Api\v1\Order;

use App\Traits\Api\v1\HasJobPolicy;

class FulfillmentService
{
    use HasJobPolicy;

    /**
     * Method fulfillItem
     *
     * Fulfill an item
     *
     * @param mixed $item [explicite description]
     * @param array $extra extra passed in job containing keys from generateJobExtra()
     *
     * @return array
     */
    public function fulfillItem($item, $extra = [])
    {
        // example code

        try {

            $isRetry = $this->isRetry($extra);
            $attempts = $this->attemptsCount($extra);

            // get job by policy id
            $jobPolicyId = 'fulfill_item';
            $jobPolicy = $this->getJobPolicy($jobPolicyId);

            /**
             * check if request is the last attempt for the job
             * if retry job is active and if last attempt, order status is set to unfulfilled, else order status is processing_retry
            **/
            $isLastAttempt = $this->isLastAttempt($extra);
            if (!is_null($jobPolicy) && !$isLastAttempt) {
                $orderItemFulfillmentStatus = config('order.order_status.processing_retry.status');
            }

            $this->orderFulfillmentApiCommand::where('id', $item->id)->update(
                [
                    // example - update attemp
                    'attempts' => $attempts,
                ]
            );

        } catch (\Illuminate\Database\QueryException $e) {
            report($e);
        }
        
        return $data;
    }
}

```