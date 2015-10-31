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
You should ideally have this inserted into the array just after the Illuminate\Database\DatabaseServiceProvider to ensure its boot methods is called after the database is available but before any other Service Providers are booted.

### Laravel 5.1:

```php
LeeMason\Tenantable\TenantableServiceProvider::class,
```

And that's it!

## Compatability

The Tenantable package has been developed with Laravel 5.1, i see no reason why it wouldnt work with 5.0 but it is only tested for 5.1 and above.

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
