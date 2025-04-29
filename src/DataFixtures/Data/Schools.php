<?php

namespace App\DataFixtures\Data;

class Schools
{
    public static function getSchoolTypes(): array
    {
        return [
            'Osnovna škola',
            'Srednja stručna škola',
            'Gimnazija',
            'Univerzitet',
        ];
    }

    public static function getSchoolsMap(): array
    {
        return [
            'Beograd - Voždovac' => [
                'Osnovna škola Bora Stanković',
                'Osnovna muzička škola Petar Konjović',
                'Gimnazija Veljko Petrović',
                'Druga ekonomska škola',
                'Saobraćajni fakultet',
            ],
            'Beograd - Vračar' => [
                'Osnovna škola Vladislav Ribnikar',
                'Osnovna škola Kralj Petar II Karađorđević',
                'Treća beogradska gimnazija',
                'Muzička škola Josip Slavenski',
            ],
            'Novi Sad' => [
                'Osnovna škola Đura Jakšić',
                'Osnovna škola Vasa Stajić',
                'Gimnazija Jovan Jovanović Zmaj',
                'Medicinska škola',
            ],
            'Niš - Medijana' => [
                'Osnovna škola Dušan Radović',
                'Osnovna škola Učitelj Tasa',
                'Gimnazija Bora Stanković',
                'Ekonomska škola',
            ],
        ];
    }
}
