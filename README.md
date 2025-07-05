# PHP DI-контейнер

## 📌 Что умеет контейнер

- Регистрация по имени, интерфейсу или реализации
- Рекурсивное разрешение зависимостей через type-hint'ы
- Управление жизненным циклом:
    - singleton
    - scoped (временные контейнеры)
- Поддержка alias'ов (`alias('db', DatabaseConnection::class)`)
- Тегирование и групповая загрузка (`tagged('loggers')`)
- Методы управления: `has()`, `forget()`, `flush()`
- Кэширование ReflectionClass для ускорения

## 📝 Laravel в проекте

Laravel 12 используется в этом проекте исключительно как удобное окружение:

- для быстрой настройки автозагрузки (`composer.json`)
- для готовой структуры папок
- для запуска PHPUnit с минимальной конфигурацией

Сам DI-контейнер написан на **чистом PHP**, без зависимости от Laravel.  
Его можно использовать в любом проекте: Laravel, Symfony, или даже без фреймворка.

## 🔧 Примеры

```php
$container = new Container\Container();

// Регистрация по интерфейсу
$container->bind(LoggerInterface::class, FileLogger::class);

// По имени
$container->bind('mailer', Mailer::class);

// Singleton
$container->singleton(DatabaseConnection::class);

// Alias
$container->alias('logger', LoggerInterface::class);

// Scoped-контейнер (жизненный цикл per-request)
$container->scope(function ($scoped) {
    $tempService = $scoped->make(TempService::class);
});
