<?php

return [
    /*
     * PlayStation vanilla inventory reference.
     *
     * These values describe the container grid known to the PS vanilla
     * configuration. Item dimensions are intentionally not guessed here;
     * unknown items are reported as "unverified" by the preset editor.
     */
    'containers' => [
        'AssaultBag_Black' => ['width' => 6, 'height' => 7],
        'AssaultBag_Green' => ['width' => 6, 'height' => 7],
        'AssaultBag_Ttsko' => ['width' => 6, 'height' => 7],
        'AssaultBag_Winter' => ['width' => 6, 'height' => 7],
        'PlateCarrierVest' => ['width' => 4, 'height' => 3],
        'PlateCarrierVest_Black' => ['width' => 4, 'height' => 3],
        'PlateCarrierVest_Camo' => ['width' => 4, 'height' => 3],
        'PlateCarrierVest_Desert' => ['width' => 4, 'height' => 3],
        'PlateCarrierVest_Green' => ['width' => 4, 'height' => 3],
        'PlateCarrierVest_Winter' => ['width' => 4, 'height' => 3],
        'PlateCarrierPouches' => ['width' => 3, 'height' => 2],
        'PlateCarrierHolster' => ['width' => 2, 'height' => 2],
        'MilitaryBelt' => ['width' => 4, 'height' => 1],
        'TTsKOJacket_Camo' => ['width' => 6, 'height' => 4],
        'TTsKOPants' => ['width' => 4, 'height' => 3],
    ],

    'slots' => [
        'Headgear' => 'Hlava',
        'Mask' => 'Maska',
        'Eyewear' => 'Brýle',
        'Body' => 'Tělo / bunda',
        'Vest' => 'Vesta',
        'Back' => 'Batoh',
        'Legs' => 'Kalhoty',
        'Feet' => 'Boty',
        'Gloves' => 'Rukavice',
        'Hips' => 'Pás / opasek',
        'shoulderL' => 'Levé rameno',
        'shoulderR' => 'Pravé rameno',
    ],
];
