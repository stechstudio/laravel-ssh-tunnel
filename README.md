# Laravel SSH Tunnel
Access a service on a remote host, via an SSH Tunnel! For example, people have been asking how to connect to a MySQL server over SSH in PHP for years.

 - [Connect to a MySQL server over SSH in PHP](http://stackoverflow.com/questions/464317/connect-to-a-mysql-server-over-ssh-in-php)
 - [Connect to a MySQL server over SSH in PHP](http://stackoverflow.com/questions/309615/connect-to-a-mysql-server-over-ssh-in-php)
 - [Connect to a mysql database via SSH through PHP](http://stackoverflow.com/questions/18069658/connect-to-a-mysql-database-via-ssh-through-php)
 - [Connect to remote MySQL database with PHP using SSH](http://stackoverflow.com/questions/4927056/connect-to-remote-mysql-database-with-php-using-ssh)
 - [Laravel MySql DB Connection with SSH](http://stackoverflow.com/questions/25495364/laravel-mysql-db-connection-with-ssh)

We had a similar challenge, specifically accessing a MySQL database over an SSH Tunnel and all of the Questions and Answers were helpful in finding a solution. However, we wanted something that would just plug and play with our Laravel applications and Lumen Services.

So we wrote this package. We hope you enjoy it!

## Installation

```
composer require stechstudio/laravel-ssh-tunnel
```
## Configuration
All configuration can and should be done in your `.env` file.
```env
# Process used to verify connection
# Use bash if your distro uses nmap-ncat (RHEL/CentOS 7.x) 
TUNNELER_VERIFY_PROCESS=nc

# Path to the nc executable
TUNNELER_NC_PATH=/usr/bin/nc
# Path to the bash executable
TUNNELER_BASH_PATH=/usr/bin/bash
# Path to the ssh executable
TUNNELER_SSH_PATH=/usr/bin/ssh
# Path to the nohup executable
TUNNELER_NOHUP_PATH=/usr/bin/nohup

# Log messages for troubleshooting
SSH_VERBOSITY=
NOHUP_LOG=/dev/null

# The identity file you want to use for ssh auth
TUNNELER_IDENTITY_FILE=/home/user/.ssh/id_rsa

# The local address and port for the tunnel
TUNNELER_LOCAL_PORT=13306
TUNNELER_LOCAL_ADDRESS=127.0.0.1

# The remote address and port for the tunnel
TUNNELER_BIND_PORT=3306
TUNNELER_BIND_ADDRESS=127.0.0.1

# The ssh connection: sshuser@sshhost:sshport
TUNNELER_USER=sshuser
TUNNELER_HOSTNAME=sshhost
TUNNELER_PORT=sshport

# How long to wait, in microseconds, before testing to see if the tunnel is created.
# Depending on your network speeds you will want to modify the default of 1 seconds
TUNNELER_CONN_WAIT=1000000

# How often it is checked if the tunnel is created. Useful if the tunnel creation is sometimes slow, 
# and you want to minimize waiting times 
TUNNELER_CONN_TRIES=1

# Do you want to ensure you have the Tunnel in place for each bootstrap of the framework?
TUNNELER_ON_BOOT=false

# Do you want to use additional SSH options when the tunnel is created?
TUNNELER_SSH_OPTIONS="-o StrictHostKeyChecking=no"
```

## Quickstart
The simplest way to use the Tunneler is to set `TUNNELER_ON_BOOT=true` in your `.env` file. This will ensure the tunnel is in place everytime the framework bootstraps.

However, there is minimal performance impact because the tunnel will get reused. You only have to bear the connection costs when the tunnel has been disconnected for some reason.

Then you can just configure your service, which we will demonstrate using a database connection. Add this under `'connections'` in your `config/database.php` file

```php
'mysql_tunnel' => [
    'driver'    => 'mysql',
    'host'      => env('TUNNELER_LOCAL_ADDRESS'),
    'port'      => env('TUNNELER_LOCAL_PORT'),
    'database'  => env('DB_DATABASE'),
    'username'  => env('DB_USERNAME'),
    'password'  => env('DB_PASSWORD'),
    'charset'   => env('DB_CHARSET', 'utf8'),
    'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
    'prefix'    => env('DB_PREFIX', ''),
    'timezone'  => env('DB_TIMEZONE', '+00:00'),
    'strict'    => env('DB_STRICT_MODE', false),
],
```
And there you have it. Go set up your Eloquent models now.

## Artisan Command
```
php artisan tunneler:activate
```

This artisan command will either verify the connection is up, or will create the connection. This probably isn't of great benefit for running manually, apart for testing your configuration.

However, if you would like to ensure that the tunnel is available all the time, and not do the work on bootstrap, you can use the [Laravel Scheduler](https://laravel.com/docs/5.3/scheduling) to schedule the artisan command to run at whatever interval you think is best to maintain your connection. In your `App\Console\Kernel` for example:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('tunneler:activate')->everyFiveMinutes();
}
```

Then, assuming you have properly set up the Scheduler in cron, the artisan command will check the tunnel every five minutes and restart it if it isn't up.

## Dispatch It
Perhaps your application rarely needs to do this, but when it does, you'd like to have an easy way to ensure the tunnel is in place before the connection attempt.

```php
$app->get('/mysql_tunnel', function () use ($app) {
    dispatch(new STS\Tunneler\Jobs\CreateTunnel());

    $users = DB::connection('mysql_tunnel')
            ->table('users')
            ->get();

    dd($users);
});

```

## How Does it Work?
It first uses netcat (`nc`) via `exec` to check the local port to see if the tunnel is open. If the port is there, it does nothing else.

If the port isn't there, it then creates the ssh tunnel connection command and executes that via `exec` after execution we wait the defined `TUNNELER_CONN_WAIT` time before running netcat again to verify that the connection is in place.

That's it. The tunnel will stay up until it times out, if it times out, and depending on the strategy you have chosen to ensure it is up and available when you need it, it will simply be recreated on demand.

