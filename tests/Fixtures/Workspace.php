<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Notifications\Traits\HasNotifications;

class Workspace extends Model
{
    use HasNotifications;

    protected $table = 'workspaces';

    protected $guarded = ['id'];
}
