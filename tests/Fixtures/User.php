<?php

namespace Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Whilesmart\ModelConfiguration\Traits\Configurable;
use Whilesmart\Notifications\Traits\ReceivesNotifications;

class User extends Authenticatable
{
    use Configurable, ReceivesNotifications;

    protected $table = 'users';

    protected $guarded = ['id'];
}
