<?php

/*
|--------------------------------------------------------------------------
| Pengaturan Kasir POS
|--------------------------------------------------------------------------
*/

return [

    /*
    | Batas diskon (dalam persen dari subtotal) yang boleh diberikan kasir TANPA persetujuan supervisor.
    | Diskon di atas batas ini wajib disetujui dengan email + PIN supervisor.
    | Bisa diubah lewat .env: POS_MAX_DISCOUNT_PERCENT_WITHOUT_APPROVAL=15
    */
    'max_discount_percent_without_approval' => (float) env('POS_MAX_DISCOUNT_PERCENT_WITHOUT_APPROVAL', 10),

];
