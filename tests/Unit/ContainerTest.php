<?php

namespace Tests\Unit;

use Container\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function test_singleton_binding()
    {
        $container = new Container();

        $container->singleton(FooInterface::class, Foo::class);

        $a = $container->make(FooInterface::class);
        $b = $container->make(FooInterface::class);

        $this->assertInstanceOf(Foo::class, $a);
        $this->assertSame($a, $b);
    }

    public function test_closure_binding()
    {
        $container = new Container();

        $container->bind('random', fn () => uniqid());

        $a = $container->make('random');
        $b = $container->make('random');

        $this->assertNotEquals($a, $b);
    }

    public function test_alias_support()
    {
        $container = new Container();

        $container->bind(FooInterface::class, Foo::class);
        $container->alias('foo', FooInterface::class);

        $instance = $container->make('foo');

        $this->assertInstanceOf(Foo::class, $instance);
    }

    public function test_scope_creates_isolated_instances()
    {
        $container = new Container();

        $container->bind(FooInterface::class, Foo::class);

        $container->singleton(Service::class);

        $a = $container->make(Service::class);

        $container->scope(function (Container $scoped) use ($a) {
            $b = $scoped->make(Service::class);
            $this->assertNotSame($a, $b);
        });

        $c = $container->make(Service::class);
        $this->assertSame($a, $c);
    }


    public function test_has_and_forget()
    {
        $container = new Container();
        $container->bind(FooInterface::class, Foo::class);

        $this->assertTrue($container->has(FooInterface::class));

        $container->forget(FooInterface::class);
        $this->assertFalse($container->has(FooInterface::class));
    }

    public function test_tagging_and_tagged_resolution()
    {
        $container = new Container();
        $container->bind(LoggerInterface::class, FileLogger::class);
        $container->bind('logger.db', DatabaseLogger::class);

        $container->tag(LoggerInterface::class, 'loggers');
        $container->tag('logger.db', 'loggers');

        $loggers = $container->tagged('loggers');

        $this->assertCount(2, $loggers);
        $this->assertInstanceOf(LoggerInterface::class, $loggers[0]);
        $this->assertInstanceOf(DatabaseLogger::class, $loggers[1]);
    }

    public function test_nested_dependencies()
    {
        $container = new Container();

        $container->bind(NestedService::class);
        $container->bind(Service::class);
        $container->bind(FooInterface::class, Foo::class);

        $instance = $container->make(NestedService::class);

        $this->assertInstanceOf(NestedService::class, $instance);
        $this->assertInstanceOf(Service::class, $instance->service);
        $this->assertInstanceOf(Foo::class, $instance->service->foo);
    }
}

interface FooInterface {}
class Foo implements FooInterface {}

class Service
{
    public function __construct(public FooInterface $foo) {}
}

class NestedService
{
    public function __construct(public Service $service) {}
}

interface LoggerInterface {}
class FileLogger implements LoggerInterface {}
class DatabaseLogger implements LoggerInterface {}
