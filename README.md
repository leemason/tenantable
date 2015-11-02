## Tenantable
[![Packagist License](https://poser.pugx.org/leemason/tenantable/license.png)](http://choosealicense.com/licenses/mit/)
[![Latest Stable Version](https://poser.pugx.org/leemason/tenantable/version.png)](https://packagist.org/packages/leemason/tenantable)
[![Total Downloads](https://poser.pugx.org/leemason/tenantable/d/total.png)](https://packagist.org/packages/leemason/tenantable)
[![Build Status](https://travis-ci.org/leemason/tenantable.svg?branch=master)](https://travis-ci.org/leemason/tenantable)

The Laravel Tenantable package is designed to enable multi-tenancy based database connections on the fly without having to access the database ```::connection('name')``` in every database call.

## Installation

Just place require new package for your laravel installation via composer.json

```
composer require leemason/tenantable
```

Then hit composer update

After updating composer, add the ServiceProvider to the providers array in config/app.php.
You should ideally have this inserted into the array just after the ```Illuminate\Database\DatabaseServiceProvider::class``` to ensure its boot methods is called after the database is available but before any other Service Providers are booted.

### Laravel 5.1:

```php
LeeMason\Tenantable\TenantableServiceProvider::class,
```

Then in your workflow create tenants the Eloquent way:

```php
$tenant = new \LeeMason\Tenantable\Tenant();
$tenant->domain = 'http://domain.com';
$tenant->driver = 'mysql';
$tenant->host = 'localhost';
$tenant->database = 'domain_com';
$tenant->username = 'root';
$tenant->password = '';
$tenant->prefix = 'prefix';
$tenant->save();
```

And that's it! Whenever your app is visited via http://domain.com the default database connection will be set with the above details.

## Compatibility

The Tenantable package has been developed with Laravel 5.1, i see no reason why it wouldn't work with 5.0 but it is only tested for 5.1 and above.

## Introduction

The package simply resolves the correct connection details via the domain accessed via connection details saved in the database.

Once resolved it sets the default database connection with the saved values.

This prevents the need to keep switching, or programatically accessing the right connection depending on the tenant being accessed.

This means all of your routes, models, etc will run on the active tenant database (unless explicitly stated via ```::connection('name')```)

This is how things work during a HTTP request:

- Tenantable copies the name of the default database connection into the ```tenantable.database.default``` config area.
- Tenantable gets the host string via the ```Http\Request::getHost()``` method.
- Tenantable looks for a tenant in the database that match this host.
- If one isn't found, Tenantable looks in the Domains table to find a match (and if found uses eloquent relationships to return the Tenant model.
- When a match is found, the match is saved as the active tenant, and the database details for the tenant are placed in the ```database.connections.tenant``` config.
- Then the default database connection is changed to 'tenant' and the connection purged (disconnected/reconnected).
- The ```app.url``` config is set the tenants domain.
- If a match isn't found in either tables a TenantNotResolved event is fired and no config changes happen.

This is how it works during an artisan console request:

- Tenantable copies the name of the default database connection into the ```tenantable.database.default``` config area.
- Tenantable registers a console option of ```--tenant``` where you can supply the id,uuid,domain or *,all to run for all tenants.
- Tenantable checks to see if the tenant option is provided, if it isn't no tenant is resolved. The command runs normally.
- If a match is found it resolves the tenant (settings the tenant connection details) before excecuting the command.
- If you provide ```--tenant``` with either a ```*``` or the string ```all``` Tenantable will run the command foreach tenant found in the database, setting the active tenant before running each time.

## The Resolver Class

The ```\LeeMason\Tenantable\Resolver``` class responsible for resolving and managing the active tenant during http and console access.

The ```TenantableServiceProvider``` registers this class as a singleton for use anywhere in your app via method injection, or by using the ```app('LeeMason\Tenantable\Resolver')``` helper function.

This class provides you with methods to access or alter the active tenant:

```php
//fetch the resolver class either via the app() function or by injecting
$resolver = app('LeeMason\Tenantable\Resolver');

//check if a tenant was resolved
$resolver->isResolved(); // returns bool

//get the active tenant model
$tenant = $resolver->getActiveTenant(); // returns instance of \LeeMason\Tenantable\Tenant or null

//set the active tenant
$resolver->setActiveTenant(\LeeMason\Tenantable\Tenant $tenant); // fires a \LeeMason\Tenantable\Events\SetActiveTenantEvent event

//purge tenant connection
$resolver->purgeTenantConnection();

//reconnect tenant connection
$resolver->reconnectTenantConnection();
```

## The Tenant Model

The ```\LeeMason\Tenantable\Tenant``` class is a very simple Eloquent model with some database connection attributes, and a meta attribute which is cast to a ```Illuminate\Support\Collection``` when accessed.

Each attribute (except id,uuid,domain,driver,prefix,meta, and timestamps) are encrypted for security and are decrypted on access, encrypted on save automatically.

Each model instance is assigned a ```uuid``` upon creation, this attribute cannot be set/changed as its a unique id generated for this tenant.

The reason for the uuid is to allow you to use an identifyer for the tenant elsewhere without exposing the tenants id or domain (for example in the filesystem, where you may store tenant specific files in sub folders).

The model can be used in any way other Eloquent models are to create/read/update/delete:

```php
//create by mass assignment
\LeeMason\Tenantable\Tenant::create([
    'domain' => 'http://...'
    ....
]);

//call then save
$tenant = \LeeMason\Tenantable\Tenant();
$tenant->domain = 'http://...';
...
$tenant->save();

//fetch all tenants
$tenant = \LeeMason\Tenantable\Tenant::all();

//fetch by domain
$tenant = \LeeMason\Tenantable\Tenant::where('domain', 'http://..')->first();
```

## Events

The Tenantable packages produces a few events which can be consumed in your application

```\LeeMason\Tenantable\Events\SetActiveTenantEvent(\LeeMason\Tenantable\Tenant $tenant)```

This event is fired when a tenant is set as the active tenant and has a public ```$tenant``` property containing the ```\LeeMason\Tenantable\Tenant``` instance.

*Note* this may not be as a result of the resolver.

```\LeeMason\Tenantable\Events\TenantResolvedEvent(\LeeMason\Tenantable\Tenant $tenant)```

This event is fired when a tenant is resolved by the resolver and has a public ```$tenant``` property containing the ```\LeeMason\Tenantable\Tenant``` instance.

*Note* this is only fired once per request as the resolver is responsible for this event.

```\LeeMason\Tenantable\Events\TenantNotResolvedEvent(\LeeMason\Tenantable\Resolver $resolver)```

This event is fired when by the resolver when it cannot resolve a tenant and has a public ```$resolver``` property containing the ```\LeeMason\Tenantable\Resolver``` instance.

*Note* this is only fired once per request as the resolver is responsible for this event.

#### Notes on using Artisan::call();

Using the ```Artisan``` Facade to run a command provides no access to alter the applications active tenant before running (unlike console artisan access).

Because of this the currently active tenant will be used.

To run the command foreach tenant you will need to fetch all tenants using ```Tenant::all()``` and run the ```Artisan::call()``` method inside a foreach after setting the active tenant like so:

```php
//fetch the resolver class either via the app() function or by injecting
$resolver = app('LeeMason\Tenantable\Resolver');

//store the current tenant
$resolvedTenant = $resolver->getActiveTenant();

//fetch all tenants and loop / call command for each
$tenants = \LeeMason\Tenantable\Tenant::all();
foreach($tenants as $tenant){
    $resolver->setActiveTenant($tenant);
    $result = \Artisan::call('commandname', ['array' => 'of', 'the' => 'arguments']);
}

//restore the correct tenant
$resolver->setActiveTenant($resolvedTenant);
```

If you need to run the Artisan facade on the original default connection (ie not the tenant connection) simply call the ```Resolver::purgeTenantConnection()``` function first:

```php
//fetch the resolver class either via the app() function or by injecting
$resolver = app('LeeMason\Tenantable\Resolver');

//store the current tenant
$resolvedTenant = $resolver->getActiveTenant();

//purge the tenant from the default connection
$resolver->purgeTenantConnection();

//call the command
$result = \Artisan::call('commandname', ['array' => 'of', 'the' => 'arguments']);

//restore the tenant connection as the default
$resolver->reconnectTenantConnection();
```