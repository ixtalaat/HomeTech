<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Admin = 'admin';
    case Technician = 'technician';
    case Manager = 'manager';
}
