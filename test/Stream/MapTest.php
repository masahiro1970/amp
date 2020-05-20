<?php

namespace Amp\Test\Stream;

use Amp\AsyncGenerator;
use Amp\PHPUnit\AsyncTestCase;
use Amp\PHPUnit\TestException;
use Amp\Stream;
use Amp\StreamSource;

class MapTest extends AsyncTestCase
{
    public function testNoValuesEmitted()
    {
        $source = new StreamSource;

        /** @noinspection PhpUnusedLocalVariableInspection */
        $stream = Stream\map($source->stream(), $this->createCallback(0));

        $source->complete();
    }

    public function testValuesEmitted()
    {
        $count = 0;
        $values = [1, 2, 3];
        $generator = new AsyncGenerator(static function (callable $yield) use ($values) {
            foreach ($values as $value) {
                yield $yield($value);
            }
        });

        $stream = Stream\map($generator, static function ($value) use (&$count) {
            ++$count;

            return $value + 1;
        });

        while ($value = yield $stream->continue()) {
            $this->assertSame(\array_shift($values) + 1, $value->unwrap());
        }

        $this->assertSame(3, $count);
    }

    /**
     * @depends testValuesEmitted
     */
    public function testOnNextCallbackThrows()
    {
        $values = [1, 2, 3];
        $exception = new TestException;

        $generator = new AsyncGenerator(static function (callable $yield) use ($values) {
            foreach ($values as $value) {
                yield $yield($value);
            }
        });

        $stream = Stream\map($generator, static function () use ($exception) {
            throw $exception;
        });

        $this->expectExceptionObject($exception);

        yield $stream->continue();
    }

    public function testStreamFails()
    {
        $exception = new TestException;
        $source = new StreamSource;

        $iterator = Stream\map($source->stream(), $this->createCallback(0));

        $source->fail($exception);

        $this->expectExceptionObject($exception);

        yield $iterator->continue();
    }
}