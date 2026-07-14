<?php

// Local landing pages for programmatic SEO. Each renders /web-design-{slug}
// via LocationController + resources/views/locations/show.blade.php.
return [
    'cities' => [
        ['slug' => 'sydney', 'name' => 'Sydney', 'state' => 'New South Wales', 'abbr' => 'NSW',
            'intro' => 'From the Sydney CBD to the Northern Beaches, Inner West and Western Sydney, local businesses compete hard for attention online.'],
        ['slug' => 'melbourne', 'name' => 'Melbourne', 'state' => 'Victoria', 'abbr' => 'VIC',
            'intro' => 'Melbourne is one of Australia\'s most competitive markets — from the CBD and Fitzroy to the bayside and outer suburbs.'],
        ['slug' => 'brisbane', 'name' => 'Brisbane', 'state' => 'Queensland', 'abbr' => 'QLD',
            'intro' => 'Brisbane and South East Queensland are booming, and a fast, well-ranked website is how local businesses win the work.'],
        ['slug' => 'perth', 'name' => 'Perth', 'state' => 'Western Australia', 'abbr' => 'WA',
            'intro' => 'Perth businesses serve a vast, spread-out market — being found on Google is everything.'],
        ['slug' => 'adelaide', 'name' => 'Adelaide', 'state' => 'South Australia', 'abbr' => 'SA',
            'intro' => 'Adelaide\'s tight-knit business community rewards trades and services that show up first in search.'],
        ['slug' => 'canberra', 'name' => 'Canberra', 'state' => 'Australian Capital Territory', 'abbr' => 'ACT',
            'intro' => 'From government contractors to local trades, Canberra businesses need a professional web presence that ranks.'],
        ['slug' => 'gold-coast', 'name' => 'Gold Coast', 'state' => 'Queensland', 'abbr' => 'QLD',
            'intro' => 'The Gold Coast moves fast — tourism, trades and services all live or die by their online visibility.'],
        ['slug' => 'newcastle', 'name' => 'Newcastle', 'state' => 'New South Wales', 'abbr' => 'NSW',
            'intro' => 'Newcastle and the Hunter region are growing quickly, and local search is where the customers are.'],
        ['slug' => 'hobart', 'name' => 'Hobart', 'state' => 'Tasmania', 'abbr' => 'TAS',
            'intro' => 'Hobart businesses punch above their weight — a sharp website and strong SEO keeps them ahead.'],
        ['slug' => 'darwin', 'name' => 'Darwin', 'state' => 'Northern Territory', 'abbr' => 'NT',
            'intro' => 'In Darwin\'s close community market, being the business that shows up first online is a real advantage.'],
    ],
];
