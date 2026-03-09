<?php

namespace Tests;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Redis;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected $seeders = [];

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->clearCache(); // Testing doesn't work properly with cached stuff.

        return $app;
    }

    /**
     * Clears Laravel Cache.
     */
    protected function clearCache()
    {
        $commands = ['clear-compiled', 'cache:clear', 'view:clear', 'config:clear', 'route:clear'];
        foreach ($commands as $command) {
            Artisan::call($command);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->initDatabase();
    }

    protected function initDatabase(): void
    {
        Artisan::call('migrate');

        foreach ($this->seeders as $seeder) {
            $this->seed($seeder);
        }
    }

    protected function tearDown(): void
    {
        /**
         * 使用 paratest 執行並行測試時，
         * 因同時跑數個 runner，有機會導致檔案讀寫衝突，
         * 故須在每個 test case 執行後立刻重置資料，
         * 而非等整個 Class 執行完後 RefreshDatabase
         */
//         $this->resetDatabase();
        // 並行測試要注意干擾問題
        Redis::flushall();
    }

    protected function resetDatabase(): void
    {
        Artisan::call('migrate:reset');
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        gc_collect_cycles();
    }

    /**
     * 凍結時間
     *
     * @return void
     */
    protected function freezeTime($date)
    {
        $mockNow = Carbon::parse($date);
        Carbon::setTestNow($mockNow);
    }

    /**
     * 解凍時間
     *
     * @return void
     */
    protected function unFreezeTime()
    {
        Carbon::setTestNow();
    }
}
