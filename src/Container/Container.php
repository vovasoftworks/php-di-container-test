<?php

namespace Container;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

class Container
{
    protected array $bindings = [];
    protected array $instances = [];
    protected array $aliases = [];
    protected array $tags = [];
    protected array $reflections = [];

    public function bind(string $abstract, Closure|string|null $concrete = null, bool $singleton = false): void
    {
        $concrete = $concrete ?? $abstract;

        $this->bindings[$abstract] = compact('concrete', 'singleton');
    }

    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function alias(string $alias, string $abstract): void
    {
        $this->aliases[$alias] = $abstract;
    }

    public function tag(string $abstract, string|array $tags): void
    {
        $tags = (array) $tags;
        foreach ($tags as $tag) {
            $this->tags[$tag][] = $abstract;
        }
    }

    public function tagged(string $tag): array
    {
        return array_map(fn($abstract) => $this->make($abstract), $this->tags[$tag] ?? []);
    }

    public function make(string $abstract): mixed
    {
        $abstract = $this->aliases[$abstract] ?? $abstract;

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;
        $singleton = $this->bindings[$abstract]['singleton'] ?? false;

        $object = $this->build($concrete);

        if ($singleton) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    public function scope(Closure $callback): mixed
    {
        $scoped = clone $this;
        $scoped->instances = [];
        return $callback($scoped);
    }

    protected function build(Closure|string $concrete): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        try {
            $reflector = $this->getReflection($concrete);

            if (! $reflector->isInstantiable()) {
                throw new InvalidArgumentException("Class [$concrete] is not instantiable.");
            }

            $constructor = $reflector->getConstructor();

            if (! $constructor) {
                return new $concrete;
            }

            $dependencies = [];

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                if (! $type || $type->isBuiltin()) {
                    throw new InvalidArgumentException("Cannot resolve parameter [\$$parameter->name] in class [$concrete].");
                }

                $dependencies[] = $this->make($type->getName());
            }

            return $reflector->newInstanceArgs($dependencies);
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException("Failed to resolve [$concrete]: " . $e->getMessage());
        }
    }

    /**
     * @throws ReflectionException
     */
    protected function getReflection(string $class): ReflectionClass
    {
        return $this->reflections[$class] ??= new ReflectionClass($class);
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || class_exists($abstract);
    }

    public function forget(string $abstract): void
    {
        unset($this->bindings[$abstract], $this->instances[$abstract]);
    }

    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
        $this->tags = [];
    }
}
