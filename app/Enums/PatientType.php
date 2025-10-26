<?php
namespace App\Enums;

enum PatientType: string
{
    case Outpatient = 'Outpatient';
    case Inpatient = 'Inpatient';
    case Emergency = 'Emergency';
}
