<?php

declare(strict_types=1);

namespace App\Enums;

enum OnlineClassPlatformType: string
{
    case Zoom = 'zoom';
    case GoogleMeet = 'google_meet';
    case Jitsi = 'jitsi';
    case BigBlueButton = 'bigbluebutton';
    case Teams = 'teams';
}
