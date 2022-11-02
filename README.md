<h1>Делал на php 7.4, dbms - mysql, framework - Yii 2 Advanced</h1>
<p>Endpoint: http://<домен>/frontend/web/api/appTopCategory?date=2022-10-01</p>
<p>Дополнительный функционал добавил (логирование запросов на endpoint и ограничение по ip)</p>
<h2>Установка</h2>
<ol>   
    <li>Склонировать репозиторий</li>
    <li>В корневой папке проекта выполнить: composer install --ignore-platform-reqs (флаг если пишет конфликты)</li>
    <li>В корневой папке проекта выполнить: init (либо php init)
        <ol>
            <li>Which environment do you want the application to be initialized in?: [1] Production </li>
            <li>Initialize the application under 'Production' environment? [yes|no]: yes</li>
            <li>Дальше тут спрашивает перезаписать ли директорию: отвечаем no</li>
        </ol>
    </li>
    <li>Создать базу данных: имя - testapptica, логин:'root', пароль:'' (либо пропишите свой конфиг в 'common/config/main-local.php')</li>
    <li>После этого в консоли выполнить yii migrate</li>
    <li>Все готово, можно заходить на endpoint (я работал на OpenServer и использовал .htaccess в 'frontend/web/', при таком раскладе все пути работают)</li>
</ol>



<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii 2 Advanced Project Template</h1>
    <br>
</p>

Yii 2 Advanced Project Template is a skeleton [Yii 2](http://www.yiiframework.com/) application best for
developing complex Web applications with multiple tiers.

The template includes three tiers: front end, back end, and console, each of which
is a separate Yii application.

The template is designed to work in a team development environment. It supports
deploying the application in different environments.

Documentation is at [docs/guide/README.md](docs/guide/README.md).

[![Latest Stable Version](https://img.shields.io/packagist/v/yiisoft/yii2-app-advanced.svg)](https://packagist.org/packages/yiisoft/yii2-app-advanced)
[![Total Downloads](https://img.shields.io/packagist/dt/yiisoft/yii2-app-advanced.svg)](https://packagist.org/packages/yiisoft/yii2-app-advanced)
[![build](https://github.com/yiisoft/yii2-app-advanced/workflows/build/badge.svg)](https://github.com/yiisoft/yii2-app-advanced/actions?query=workflow%3Abuild)

DIRECTORY STRUCTURE
-------------------

```
common
    config/              contains shared configurations
    mail/                contains view files for e-mails
    models/              contains model classes used in both backend and frontend
    tests/               contains tests for common classes    
console
    config/              contains console configurations
    controllers/         contains console controllers (commands)
    migrations/          contains database migrations
    models/              contains console-specific model classes
    runtime/             contains files generated during runtime
backend
    assets/              contains application assets such as JavaScript and CSS
    config/              contains backend configurations
    controllers/         contains Web controller classes
    models/              contains backend-specific model classes
    runtime/             contains files generated during runtime
    tests/               contains tests for backend application    
    views/               contains view files for the Web application
    web/                 contains the entry script and Web resources
frontend
    assets/              contains application assets such as JavaScript and CSS
    config/              contains frontend configurations
    controllers/         contains Web controller classes
    models/              contains frontend-specific model classes
    runtime/             contains files generated during runtime
    tests/               contains tests for frontend application
    views/               contains view files for the Web application
    web/                 contains the entry script and Web resources
    widgets/             contains frontend widgets
vendor/                  contains dependent 3rd-party packages
environments/            contains environment-based overrides
```
