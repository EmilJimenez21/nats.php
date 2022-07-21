<?php

declare(strict_types=1);

namespace Basis\Nats\Tests;

class PerformanceTest extends Test
{
    public function testPerformance()
    {
        $client = $this->createClient()->setTimeout(0.1)->setDelay(0);
        $client->setLogger(null);

        $this->logger?->info('start performance test');

        $this->limit = 100_000;
        $this->counter = 0;

        $client->subscribe('hello', function ($n) {
            $this->counter++;
        });

        $publishing = microtime(true);
        foreach (range(1, $this->limit) as $n) {
            $client->publish('hello', 'data-' . $n);
        }
        $publishing = microtime(true) - $publishing;

        $this->logger?->info('publishing', [
            'rps' => floor($this->limit / $publishing),
            'length' => $this->limit,
            'time' => $publishing,
        ]);

        $processing = microtime(true);
        while ($this->counter < $this->limit) {
            $client->process(0);
        }
        $processing = microtime(true) - $processing;

        $this->logger?->info('processing', [
            'rps' => floor($this->limit / $processing),
            'length' => $this->limit,
            'time' => $processing,
        ]);

        // at least 5000rps should be enough for test
        $this->assertGreaterThan(5000, $this->limit / $processing);
    }
}
