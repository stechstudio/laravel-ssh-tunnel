<?php
return [
    'nc_path' => env('TUNNELER_NC_PATH', 'nc'),
    'ssh_path' => env('TUNNELER_SSH_PATH', 'ssh'),
    'nohup_path' => env('TUNNELER_NOHUP_PATH', 'nohup'),

    'local_address' => env('TUNNELER_LOCAL_ADDRESS', '127.0.0.1'),
    'local_port' => env('TUNNELER_LOCAL_PORT'),
    'identity_file' => env('TUNNELER_IDENTITY_FILE'),

    'bind_address' => env('TUNNELER_BIND_ADDRESS', '127.0.0.1'),
    'bind_port' => env('TUNNELER_BIND_PORT'),

    'user' => env('TUNNELER_USER'),
    'hostname' => env('TUNNELER_HOSTNAME'),
    'port' => env('TUNNELER_PORT'),
    'wait' => env('TUNNELER_CONN_WAIT', '500000'),

    'on_boot' => filter_var(env('TUNNELER_ON_BOOT', false), FILTER_VALIDATE_BOOLEAN)
];