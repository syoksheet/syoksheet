<?php

namespace App\Enums;

enum QueueName: string
{
    case Audit = 'audit';
    case Notifications = 'notifications';
    case Default = 'default';
}
