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

And that's it!

## Compatibility

The Tenantable package has been developed with Laravel 5.1, i see no reason why it wouldn't work with 5.0 but it is only tested for 5.1 and above.

## Introduction

The package simply resolves the correct connection details via the domain accessed via connection details saved in the database.

Once resolved it sets the default database connection with the saved values.

This prevents the need to keep switching, or programatically accessing the right connection depending on the tenant being viewed.

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

### Notes on using Artisan::call();

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

//purge and set the default connection
$resolver->purgeTenantConnection();

//call the command
$result = \Artisan::call('commandname', ['array' => 'of', 'the' => 'arguments']);

//restore the tenant connection
$resolver->reconnectTenantConnection();
```