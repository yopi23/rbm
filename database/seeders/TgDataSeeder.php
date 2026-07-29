<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;
use App\Models\ScreenSize;
use App\Models\CameraPosition;
use App\Models\HpData;
use Illuminate\Support\Facades\DB;

class TGDataSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks & truncate tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        HpData::truncate();
        Brand::truncate();
        ScreenSize::truncate();
        CameraPosition::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $dataGroups = [
        [
            'kode' => 'E01',
            'summary' => 'iphone 6/iPhone 7/iPhone 8',
            'compat' => 'iphone 6, iPhone 7, iPhone 8',
            'size' => '4.7 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E02',
            'summary' => 'iphone 6plus/iPhone 8 Plus/iPhone 7 Plus',
            'compat' => 'iphone 6plus, iPhone 8 Plus, iPhone 7 Plus',
            'size' => '5.5 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E03',
            'summary' => 'iPhone X/iPhone XS/iPhone 11 Pro',
            'compat' => 'iPhone X, iPhone XS, iPhone 11 Pro',
            'size' => '5.8 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E04',
            'summary' => 'iPhone XR/iPhone 11',
            'compat' => 'iPhone XR, iPhone 11',
            'size' => '6.1 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E05',
            'summary' => 'iPhone XS Max/iPhone 11 Pro Max',
            'compat' => 'iPhone XS Max, iPhone 11 Pro Max',
            'size' => '6.5 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E06',
            'summary' => 'iPhone 12mini',
            'compat' => 'iPhone 12mini',
            'size' => '5.4 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E07',
            'summary' => 'iPhone 12/iPhone 12 Pro',
            'compat' => 'iPhone 12, iPhone 12 Pro',
            'size' => '6.1 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E08',
            'summary' => 'iPhone 12 Pro Max',
            'compat' => 'iPhone 12 Pro Max',
            'size' => '6.7 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E09',
            'summary' => 'iPhone 13mini',
            'compat' => 'iPhone 13mini',
            'size' => '5.4 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E10',
            'summary' => 'iPhone 13/iPhone 13 Pro/iPhone 14/iPhone 16E/iPhone SE4',
            'compat' => 'iPhone 13, iPhone 13 Pro, iPhone 14, iPhone 16E, iPhone SE4',
            'size' => '6.1 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E11',
            'summary' => 'iPhone 13 Pro Max/iPhone 14 Plus',
            'compat' => 'iPhone 13 Pro Max, iPhone 14 Plus',
            'size' => '6.7 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E12',
            'summary' => 'iPhone 14 Pro/iPhone 15/iPhone 16',
            'compat' => 'iPhone 14 Pro, iPhone 15, iPhone 16',
            'size' => '6.1 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E13',
            'summary' => 'iPhone 14 Pro Max/iPhone 15 Plus/iPhone 16 Plus',
            'compat' => 'iPhone 14 Pro Max, iPhone 15 Plus, iPhone 16 Plus',
            'size' => '6.7 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E14',
            'summary' => 'iPhone 15 Pro',
            'compat' => 'iPhone 15 Pro',
            'size' => '6.1 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E15',
            'summary' => 'iPhone 15 Pro Max',
            'compat' => 'iPhone 15 Pro Max',
            'size' => '6.7 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E16',
            'summary' => 'iPhone 16 Pro/IPHONE 17',
            'compat' => 'iPhone 16 Pro, IPHONE 17',
            'size' => '6.3 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E17',
            'summary' => 'iPhone 16 Pro Max',
            'compat' => 'iPhone 16 Pro Max',
            'size' => '6.9 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E18',
            'summary' => 'iPhone 17 Pro',
            'compat' => 'iPhone 17 Pro',
            'size' => '6.3 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E19',
            'summary' => 'iPhone 17 Pro Max',
            'compat' => 'iPhone 17 Pro Max',
            'size' => '6.9 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E20',
            'summary' => 'iPhone 17 Air',
            'compat' => 'iPhone 17 Air',
            'size' => '6.6 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E21',
            'summary' => 'A3S/OP A3/Y81S/Y81/Y83/Y85/F7/R15/X21/Realme2/C1/V9/REDMI NOTE6/小米8Lite/V9youth/A12E/1+6/Y83PRO',
            'compat' => 'A3S, OP A3, Y81S, Y81, Y83, Y85, F7, R15, X21, Realme2, C1, V9, REDMI NOTE6, 小米8Lite, V9youth, A12E, 1+6, Y83PRO',
            'size' => '6.2 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E22',
            'summary' => 'R17/M20/R15X/RenoA/1+6T/R17PRO/K1/F9/M20S/A7X/1+7/Realme2pro/realme 3pro/realme 5pro/realme X2/realme XT/redmi NOTE8/redmi note8T/Y8P/Psmart PLUS/Tecno pop5/P50E/P50/LT P20/HONOR 20LITE(国内版）/honor 30i',
            'compat' => 'R17, M20, R15X, RenoA, 1+6T, R17PRO, K1, F9, M20S, A7X, 1+7, Realme2pro, realme 3pro, realme 5pro, realme X2, realme XT, redmi NOTE8, redmi note8T, Y8P, Psmart PLUS, Tecno pop5, P50E, P50, LT P20, HONOR 20LITE(国内版）, honor 30i',
            'size' => '6.3 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E23',
            'summary' => 'A7/Y95/Y93/Y91/redmi 7/Realme 3/redmi Y3/A5S/OPPO A12/A10/A10S/A11K/vivoY1S/M01S/REDMI 8/REDMI 8A',
            'compat' => 'A7, Y95, Y93, Y91, redmi 7, Realme 3, redmi Y3, A5S, OPPO A12, A10, A10S, A11K, vivoY1S, M01S, REDMI 8, REDMI 8A',
            'size' => '6.2 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E24',
            'summary' => 'A9/F11/REDMI NOTE8PRO/1+7T/Realme X2pro/1+ACE/Y19/Y5S/REDMI 9/pocoM2/NOVA8SE/poco M3/REDMI 9POWER/REDMI 9T/Y73(2021)/POCO M2/V23e/V23e（5G)/VIVO T1/VIVO Y75 4G/VIVO T1 44W/VIVO Y55 4G/M34/M15 5G',
            'compat' => 'A9, F11, REDMI NOTE8PRO, 1+7T, Realme X2pro, 1+ACE, Y19, Y5S, REDMI 9, pocoM2, NOVA8SE, poco M3, REDMI 9POWER, REDMI 9T, Y73(2021), POCO M2, V23e, V23e（5G), VIVO T1, VIVO Y75 4G, VIVO T1 44W, VIVO Y55 4G, M34, M15 5G',
            'size' => '6.53 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E25',
            'summary' => 'Realme5/REALME 5T/REALME C3/REALME 5S/REALME 5i/REALME 6i/REALME narzo10A/REALME C3i/REALME C11/REALME C15/REALME narzo/REALME narzo10/REALME NARZO 20/REALME NARZO 20A/OP A9(2020)/OP A5(2020)/OP A11X/OPPOA31/A20S/MI10lite/REDMI 10X(5G)/REDMI 10Xpro(5G)/REDMI 9A/VIVO Y20/REDMI 9C/Y20i/OP A15/VIVO Y20S/VIVO Y12(2020)/POCO C3/REDMI 9i/VIVO Y51(2020.12）/SAM A12/Y52S/OPPO A15S/VIVO Y20A/Y21S/SAMA32/(5G)/OPPO A55/VIVO Y31 2020/Y31 2021/SAM A12(2021)/SAM A02S欧版/A02S 欧版2021/A02/SAM M02/M02S/Realme C20/RealmeV11/Realme C21/RealmeNARZO 30A/SAM M12/Realme C25//Y31S/VIVO Y72/SamF12 /Sam F02S/OPPO A53S(5G)/Realme C25S/Y52A/Y51A(2021)/Y53S/OPPO A16/Y20G/Y72(印度版）/Y52S/Sam A03S/Y21/Y33S/Realme C21Y/Realme C25Y/oppo A55(5G)/REALME Narzo 50i/POCO C31/REAME Narzo 50A/Y12A/Y15S/OP A16K/VIVO Y15A/OPA56(5G)/Y20T/A03CORE/Sam A13(5G)/Realme C35/Spark7/VIVO T1(5G)/Vivo Y75(5g)/Y33T/SamA23/Sam A12/SAM A13 4G/SAM F23/M23/M33(5G)/Realme9（4G）/REDMI Note11E/VIVO Y01/OP A57 5G/OP A77/REALME C30/OP K10 5G国际版/VIVO Y77国际版/OP A97 5G/VIVO Y20S/VIVO Y16/Y35/Y22S/Y22/HONOR X8 5G/OP A77S/SAM A04S/MOTO E7POWER/REDMI A1/OP A17/MOTO G50/HONOR X6/A04CORE/SAM A04/IQ00Z6 5G/Y30 5G/MT E22 4G/REALME NARZO50iPRIME/REDMI10 5G/POCOM5/OP A17K/REALME10 5G/A04E/OP A78/HOT30i/SPARK10 4G/REDMIA2/HONORX5/REDMIA2+HOT12PRO/NOVAY61/LTEI S23/POP6PRO/OP A38/HONORX6A/HONORX5PLUS/VIVO Y02T/REDMINOTE11R/OP A97 5G/OP A59 5G/Y18/Y28/Y28S/HONOR X5B',
            'compat' => 'Realme5, REALME 5T, REALME C3, REALME 5S, REALME 5i, REALME 6i, REALME narzo10A, REALME C3i, REALME C11, REALME C15, REALME narzo, REALME narzo10, REALME NARZO 20, REALME NARZO 20A, OP A9(2020), OP A5(2020), OP A11X, OPPOA31, A20S, MI10lite, REDMI 10X(5G), REDMI 10Xpro(5G), REDMI 9A, VIVO Y20, REDMI 9C, Y20i, OP A15, VIVO Y20S, VIVO Y12(2020), POCO C3, REDMI 9i, VIVO Y51(2020.12）, SAM A12, Y52S, OPPO A15S, VIVO Y20A, Y21S, SAMA32, (5G), OPPO A55, VIVO Y31 2020, Y31 2021, SAM A12(2021), SAM A02S欧版, A02S 欧版2021, A02, SAM M02, M02S, Realme C20, RealmeV11, Realme C21, RealmeNARZO 30A, SAM M12, Realme C25, Y31S, VIVO Y72, SamF12 , Sam F02S, OPPO A53S(5G), Realme C25S, Y52A, Y51A(2021), Y53S, OPPO A16, Y20G, Y72(印度版）, Y52S, Sam A03S, Y21, Y33S, Realme C21Y, Realme C25Y, oppo A55(5G), REALME Narzo 50i, POCO C31, REAME Narzo 50A, Y12A, Y15S, OP A16K, VIVO Y15A, OPA56(5G), Y20T, A03CORE, Sam A13(5G), Realme C35, Spark7, VIVO T1(5G), Vivo Y75(5g), Y33T, SamA23, Sam A12, SAM A13 4G, SAM F23, M23, M33(5G), Realme9（4G）, REDMI Note11E, VIVO Y01, OP A57 5G, OP A77, REALME C30, OP K10 5G国际版, VIVO Y77国际版, OP A97 5G, VIVO Y20S, VIVO Y16, Y35, Y22S, Y22, HONOR X8 5G, OP A77S, SAM A04S, MOTO E7POWER, REDMI A1, OP A17, MOTO G50, HONOR X6, A04CORE, SAM A04, IQ00Z6 5G, Y30 5G, MT E22 4G, REALME NARZO50iPRIME, REDMI10 5G, POCOM5, OP A17K, REALME10 5G, A04E, OP A78, HOT30i, SPARK10 4G, REDMIA2, HONORX5, REDMIA2+HOT12PRO, NOVAY61, LTEI S23, POP6PRO, OP A38, HONORX6A, HONORX5PLUS, VIVO Y02T, REDMINOTE11R, OP A97 5G, OP A59 5G, Y18, Y28, Y28S, HONOR X5B',
            'size' => '6.5 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E26',
            'summary' => 'REALME6/REALME 6S/REALME 7/OP A52/OP A92/OPPO A72/1+NORE N10/REALME V15 5G/REALMEX7 INDIA/REALME NARZO30PRO 5G/REALME8/REALME8PRO/OPPO A74 4G/5G/XM11LITE4G/5G/OPPO A54 5G/OPPO A93 5G/REALME8 5G/1+NORDN10 5G/REALMENARZ30 4G/5G/REALME9i/REALME9PRO/REALME9 5G/RENO7LITE/RENO6LITE/RENO8Z/RENO8LITE/REALME10 4G/RENO8T/REALME11/LT9701/LT M50',
            'compat' => 'REALME6, REALME 6S, REALME 7, OP A52, OP A92, OPPO A72, 1+NORE N10, REALME V15 5G, REALMEX7 INDIA, REALME NARZO30PRO 5G, REALME8, REALME8PRO, OPPO A74 4G, 5G, XM11LITE4G, 5G, OPPO A54 5G, OPPO A93 5G, REALME8 5G, 1+NORDN10 5G, REALMENARZ30 4G, 5G, REALME9i, REALME9PRO, REALME9 5G, RENO7LITE, RENO6LITE, RENO8Z, RENO8LITE, REALME10 4G, RENO8T, REALME11, LT9701, LT M50',
            'size' => '6.5 inch',
            'camera' => 'Punch Hole (Kiri)'
        ],
        [
            'kode' => 'E27',
            'summary' => 'OPPO A91/RENO 3/F15/F17/V20SE/V20/A73(2020)/Sam M32/Sam F22/Sam A33/V21E',
            'compat' => 'OPPO A91, RENO 3, F15, F17, V20SE, V20, A73(2020), Sam M32, Sam F22, Sam A33, V21E',
            'size' => '6.4 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E28',
            'summary' => 'Reno4/F17PRO/A93(2020)/Reno4 Lite/RENO5LITE',
            'compat' => 'Reno4, F17PRO, A93(2020), Reno4 Lite, RENO5LITE',
            'size' => '6.4 inch',
            'camera' => 'Punch Hole (Kiri)'
        ],
        [
            'kode' => 'E29',
            'summary' => 'Reno5/oppo A94(4G/5G)/F19PRO/F19PRO(5G)/Realme GT/A95/Reno 5F/Reno6（5G)/Realme X7 max/1+NORD CE(5g)/Reno6 4G/5G/1+Nord2(5g)/RealmeGT master(5g)/OPPO F19S/Reno 6（4g）/Reno 7（5G）/1+NORD2T 5G',
            'compat' => 'Reno5, oppo A94(4G, 5G), F19PRO, F19PRO(5G), Realme GT, A95, Reno 5F, Reno6（5G), Realme X7 max, 1+NORD CE(5g), Reno6 4G, 5G, 1+Nord2(5g), RealmeGT master(5g), OPPO F19S, Reno 6（4g）, Reno 7（5G）, 1+NORD2T 5G',
            'size' => '6.4 inch',
            'camera' => 'Punch Hole (Kiri)'
        ],
        [
            'kode' => 'E30',
            'summary' => 'Z5X/Z1PRO/REDMI NOTE9（5G)/Y30/Y50/REDMI 10X(4G)/Y70S/VIVO Y51S/REDMI NOTE9T',
            'compat' => 'Z5X, Z1PRO, REDMI NOTE9（5G), Y30, Y50, REDMI 10X(4G), Y70S, VIVO Y51S, REDMI NOTE9T',
            'size' => '6.53 inch',
            'camera' => 'Punch Hole (Kiri)'
        ],
        [
            'kode' => 'E31',
            'summary' => 'V15/F11PRO/RealmeX/Reno6.6/K3',
            'compat' => 'V15, F11PRO, RealmeX, Reno6.6, K3',
            'size' => '6.53 inch',
            'camera' => 'Full Layar'
        ],
        [
            'kode' => 'E32',
            'summary' => 'SAM A50/X23/SAM M30/SAM A30/OPPOY3/Y17/Y12/Y15/S1/Y7S/XIAOMI9X/A20/A40S/VIVO Y9S/U10/M31/M21/M50S/VIVO S6/A50S/A30S/M30S/M21S/F41/VIVO Y51(2020.9月）/SAM A32(4G)/Y3S/Sam A22ZW/ITEL A04/CAMON12PRO',
            'compat' => 'SAM A50, X23, SAM M30, SAM A30, OPPOY3, Y17, Y12, Y15, S1, Y7S, XIAOMI9X, A20, A40S, VIVO Y9S, U10, M31, M21, M50S, VIVO S6, A50S, A30S, M30S, M21S, F41, VIVO Y51(2020.9月）, SAM A32(4G), Y3S, Sam A22ZW, ITEL A04, CAMON12PRO',
            'size' => '6.4 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E33',
            'summary' => 'J4core/J6PLUS/J4PLUS/A7(2018)/A8PLUS/A6PLUS/J8PLUS/REDMI note5PRO/REDMI 5PLUS/A750/J8',
            'compat' => 'J4core, J6PLUS, J4PLUS, A7(2018), A8PLUS, A6PLUS, J8PLUS, REDMI note5PRO, REDMI 5PLUS, A750, J8',
            'size' => '6 inch',
            'camera' => 'Waterdrop'
        ],
        [
            'kode' => 'E34',
            'summary' => 'Sam A71/S10lite(2020)/NOTE10lite/REDMI note9pro（4G/5G)/REDMI note9promax/REDMI NOTE9S/SAM A90/pocoM2PRO/M51/POCO X3NFC/小米10T lite / Y7A(2020)/PSMART 2021/MI10i/HONORX10Lite/SAM A72(5G)/A72(4G)/REDMI K40/SAM M62/REDMI NOTE 10PRO(4g/5g)/REDMI NOTE10PRO MAX/F62/POCO X3PRO/POCO F3/MI 11X/Sam Note20(5g)/REDMI 10i/IQ007/Sam A42/Sam M42/Poco F3GT/MI 11i/POCO X3GT/MI11T/11Tpro/M52(5g)/Redminote11pro（国内，国际，国际5g）/Redminote11国际5g/Redmi note11pro+/Poco M4pro（5g)/POCO M2PRO/Redminote10Lite/Redminote11T（5g）/VIVO T1/Sam note20/红米K40S/Poco X4pro/Redmi K50/Redmi K50 pro/Redmi K50(5g)电竞版/Sam A73(5g）/Redmi note11S(5g)/HONORX8/POCO F4/POCOX4GT/VIVO Y77国内版/POCOF4GT/M53 5G/1+10R/IQ00NE6/REALMEGT NRO3/POCOF4/POCOX4GT/K40S/REDMINOTE11TPRO/       MOTO E32/ NOVA Y90/RENO8PRO/MOTO G22/MI12T/12TPRO/K50UITRA/REDMINOTE12PRO/REALME10PRO/NOVA10SE/POCOX5PRO/S16E/V27E 4G/REDMINOTE12PRO 5G/REDMINOTE12PRO+ 5G/REDMINOTE12TURBO/POCOF5 5G/POCOF5PRO/K60 5G/CAMON20/CAMON20PRO/INF NOTE30PRO/REALMEGT3/REDMINOTE12TURBO/1+NORD3 5G/MOTOE13/HONOR90LITE/INF GT10PRO/REDMINOTE13 5G/REDMIK60UITRA/MI13T/MI13TPRO/Y200/V29E/SPARKGO 2024/SAM M54 5G/F54 5G/G04/G24/SPARK20C/SPARK20/RENO11F/F25PRO/REALME 12+/Y100印尼/Y200E/SAM M55 5G/SAM C55/1+NORD CE4LITE 5G/RENO12F 5G/1+NORD 4/NOVA 12I/REDMI NOTE 14 5G/POCO M7PRO 5G/V50LITE/REDMI NOTE14S/1+13R/1+ALE5/LTEL A80/POP 10C/1+NORD CE5',
            'compat' => 'Sam A71, S10lite(2020), NOTE10lite, REDMI note9pro（4G, 5G), REDMI note9promax, REDMI NOTE9S, SAM A90, pocoM2PRO, M51, POCO X3NFC, 小米10T lite ,  Y7A(2020), PSMART 2021, MI10i, HONORX10Lite, SAM A72(5G), A72(4G), REDMI K40, SAM M62, REDMI NOTE 10PRO(4g, 5g), REDMI NOTE10PRO MAX, F62, POCO X3PRO, POCO F3, MI 11X, Sam Note20(5g), REDMI 10i, IQ007, Sam A42, Sam M42, Poco F3GT, MI 11i, POCO X3GT, MI11T, 11Tpro, M52(5g), Redminote11pro（国内，国际，国际5g）, Redminote11国际5g, Redmi note11pro+, Poco M4pro（5g), POCO M2PRO, Redminote10Lite, Redminote11T（5g）, VIVO T1, Sam note20, 红米K40S, Poco X4pro, Redmi K50, Redmi K50 pro, Redmi K50(5g)电竞版, Sam A73(5g）, Redmi note11S(5g), HONORX8, POCO F4, POCOX4GT, VIVO Y77国内版, POCOF4GT, M53 5G, 1+10R, IQ00NE6, REALMEGT NRO3, POCOF4, POCOX4GT, K40S, REDMINOTE11TPRO,        MOTO E32,  NOVA Y90, RENO8PRO, MOTO G22, MI12T, 12TPRO, K50UITRA, REDMINOTE12PRO, REALME10PRO, NOVA10SE, POCOX5PRO, S16E, V27E 4G, REDMINOTE12PRO 5G, REDMINOTE12PRO+ 5G, REDMINOTE12TURBO, POCOF5 5G, POCOF5PRO, K60 5G, CAMON20, CAMON20PRO, INF NOTE30PRO, REALMEGT3, REDMINOTE12TURBO, 1+NORD3 5G, MOTOE13, HONOR90LITE, INF GT10PRO, REDMINOTE13 5G, REDMIK60UITRA, MI13T, MI13TPRO, Y200, V29E, SPARKGO 2024, SAM M54 5G, F54 5G, G04, G24, SPARK20C, SPARK20, RENO11F, F25PRO, REALME 12+, Y100印尼, Y200E, SAM M55 5G, SAM C55, 1+NORD CE4LITE 5G, RENO12F 5G, 1+NORD 4, NOVA 12I, REDMI NOTE 14 5G, POCO M7PRO 5G, V50LITE, REDMI NOTE14S, 1+13R, 1+ALE5, LTEL A80, POP 10C, 1+NORD CE5',
            'size' => '6.7 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E35',
            'summary' => 'SAM A70/X650/Realme C20A/Realme C11(2021)/Y76S/M32(5G)/Redmi 10A/INFINIX NOTE12/IN NOTE12 G96/SAM F13/REDMIA2/REDMIA2+/VIVO Y35M+/Y27/NOVA Y60/VIVO Y27 5G/LT6217/LTM30/ITEL A05S/POVA4PRO',
            'compat' => 'SAM A70, X650, Realme C20A, Realme C11(2021), Y76S, M32(5G), Redmi 10A, INFINIX NOTE12, IN NOTE12 G96, SAM F13, REDMIA2, REDMIA2+, VIVO Y35M+, Y27, NOVA Y60, VIVO Y27 5G, LT6217, LTM30, ITEL A05S, POVA4PRO',
            'size' => '6.7 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E36',
            'summary' => 'Sam A51/A51(5G)/SAM A31/S20FE/M31S/A52(4G)/A52(5G)/REDMI NOTE 10(4G)//REDMI NOTE10S/VIVO X60/Vivo V21(4g/5g)/V21E/SAMA22(4G)/SAMA52S(5g)/Redminote 11S/SamA53(5G)/Redminote11/（国际版4G）/Poco M4PRO(4g）/Sam A53/MOTO G52/G82/VIVO T1PRO/POCOM5S/A31 5G/REDMINOTE12S/Y100',
            'compat' => 'Sam A51, A51(5G), SAM A31, S20FE, M31S, A52(4G), A52(5G), REDMI NOTE 10(4G), REDMI NOTE10S, VIVO X60, Vivo V21(4g, 5g), V21E, SAMA22(4G), SAMA52S(5g), Redminote 11S, SamA53(5G), Redminote11, （国际版4G）, Poco M4PRO(4g）, Sam A53, MOTO G52, G82, VIVO T1PRO, POCOM5S, A31 5G, REDMINOTE12S, Y100',
            'size' => '6.5 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E37',
            'summary' => 'SAM A21S/A21/OPPO A53（4G/5G)/OPPOA32/A33(2020)/Realme C17/1+NORD N100/OPPO A53S/OPPO A54(4G)/InfinixHOT 9/Realme 7i/Realme C17/OPPOA55(4G)/Realme GT NEO2/1+9RT/Realme Narzo50/8i/RealmeGT2pro/OP A96(4g）国际版/REALME GT2/REALMEGT NEO3T/SPARK5',
            'compat' => 'SAM A21S, A21, OPPO A53（4G, 5G), OPPOA32, A33(2020), Realme C17, 1+NORD N100, OPPO A53S, OPPO A54(4G), InfinixHOT 9, Realme 7i, Realme C17, OPPOA55(4G), Realme GT NEO2, 1+9RT, Realme Narzo50, 8i, RealmeGT2pro, OP A96(4g）国际版, REALME GT2, REALMEGT NEO3T, SPARK5',
            'size' => '6.5 inch',
            'camera' => 'Punch Hole (Kiri)'
        ],
        [
            'kode' => 'E38',
            'summary' => 'A22(5G)/A14 5G/F42(5g)/Infinix Smart6/HONOR30P/Realme C31/MOTO G10/G20/G30/G9PLAY/E7PLUS/G8POWER LITE/ONE FUSION/E20/MOTO G50 5G/SAM M14 5G',
            'compat' => 'A22(5G), A14 5G, F42(5g), Infinix Smart6, HONOR30P, Realme C31, MOTO G10, G20, G30, G9PLAY, E7PLUS, G8POWER LITE, ONE FUSION, E20, MOTO G50 5G, SAM M14 5G',
            'size' => '6.5 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E39',
            'summary' => 'SAM S22/S23',
            'compat' => 'SAM S22, S23',
            'size' => '6.8 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E40',
            'summary' => 'SAM S21+ /SAM A24/SAM M34 5G/REDMINOTE13PRO/REDMIK70/K70PRO',
            'compat' => 'SAM S21+ , SAM A24, SAM M34 5G, REDMINOTE13PRO, REDMIK70, K70PRO',
            'size' => '6.2 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E41',
            'summary' => 'SAM A54/S23FE/S23+/RENO 13/HONOR400/RENO14/RENO 14F',
            'compat' => 'SAM A54, S23FE, S23+, RENO 13, HONOR400, RENO14, RENO 14F',
            'size' => '6.4 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E42',
            'summary' => 'SAM A34/SAM A35/SAM A55/SAM S24+/SAM F15 5G',
            'compat' => 'SAM A34, SAM A35, SAM A55, SAM S24+, SAM F15 5G',
            'size' => '6.6 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E43',
            'summary' => 'SAM A16/SAM A26/M16/SAM A17/SAMF36/M36',
            'compat' => 'SAM A16, SAM A26, M16, SAM A17, SAMF36, M36',
            'size' => '6.7 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E44',
            'summary' => 'SAM A36/SAM A56/POCO F7/NOTHING PHONE 3A/MI 15T/MI 15TPRO/HONOR X70/S25FE/MOTO G67/POCO X8PROMAX/MOTO G67',
            'compat' => 'SAM A36, SAM A56, POCO F7, NOTHING PHONE 3A, MI 15T, MI 15TPRO, HONOR X70, S25FE, MOTO G67, POCO X8PROMAX, MOTO G67',
            'size' => '6.7 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E45',
            'summary' => 'SAM A05/A05S/REDMI13C/SAM M14 4G/SAM A06/REALME NOTE 60/poco m6 5G/M06/REALME NOTE 60X/REALMENOTE60X/SAM M05/SAM A07/HONOR X5C/POP 20/SPARK GO 3/Y11D/Y05/Y19S 5G/ITEL city 100/itel A100',
            'compat' => 'SAM A05, A05S, REDMI13C, SAM M14 4G, SAM A06, REALME NOTE 60, poco m6 5G, M06, REALME NOTE 60X, REALMENOTE60X, SAM M05, SAM A07, HONOR X5C, POP 20, SPARK GO 3, Y11D, Y05, Y19S 5G, ITEL city 100, itel A100',
            'size' => '6.7 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E46',
            'summary' => 'MI 13/HONOR 10LITE/MOTO E6S/E6PLUS/MI 14/A1K/REALME C2',
            'compat' => 'MI 13, HONOR 10LITE, MOTO E6S, E6PLUS, MI 14, A1K, REALME C2',
            'size' => '6.1 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E47',
            'summary' => 'NOVA9SE /MOTO E30/E40/REDMINOTE12 5G/POCOX5 5G/REALMEC55/REDMINOTE12 4G/1+NORDCE3LITE 5G/NOVA11i/麦芒20/OP F23 5G/A98 5G/VIVO Y78/Y36/OP A58 4G/IQ0011S/REALME11 5G/REALME11X 5G/Y27S/SMART 10PLUS/POCO 10/REALME C67/REALME 12/OP A60/REALME 12X/SMART10/SMART 10PLUS/SPARK40/SMART 10 HD/SPARK GO2/HOT60/HOT60I/Y100T/Y77T/Y38/Y200I/CAMON 30 5G/POVA6PRO/REALME C65/POVA6/CAMON 30PRO/OP A3PRO 5G/OP A3X/Y28 4G/OP A79/spark GO 1/HOT50I/SMART 9/POP9/REALME 13/VI Y19S/HONT50PRO/SAPRK 30C/REALME C75/HOT 50 5G/Y29/REALME 14X/VIVOY29/CAMON40/INF GT20PRO/INF NOTE50/MOTO E15/INF NOTE50PRO+/INF NOTE5X/MOTOG05/MOTOG15/VIVOY39 5G/MOTOG75/INFNOTE50PRO/SPARK30 5G/OP A5PRO 5G/SPARK40L/REALME C71/OP A5/Y21D/Y31/MOTO G57/MOTO G35',
            'compat' => 'NOVA9SE , MOTO E30, E40, REDMINOTE12 5G, POCOX5 5G, REALMEC55, REDMINOTE12 4G, 1+NORDCE3LITE 5G, NOVA11i, 麦芒20, OP F23 5G, A98 5G, VIVO Y78, Y36, OP A58 4G, IQ0011S, REALME11 5G, REALME11X 5G, Y27S, SMART 10PLUS, POCO 10, REALME C67, REALME 12, OP A60, REALME 12X, SMART10, SMART 10PLUS, SPARK40, SMART 10 HD, SPARK GO2, HOT60, HOT60I, Y100T, Y77T, Y38, Y200I, CAMON 30 5G, POVA6PRO, REALME C65, POVA6, CAMON 30PRO, OP A3PRO 5G, OP A3X, Y28 4G, OP A79, spark GO 1, HOT50I, SMART 9, POP9, REALME 13, VI Y19S, HONT50PRO, SAPRK 30C, REALME C75, HOT 50 5G, Y29, REALME 14X, VIVOY29, CAMON40, INF GT20PRO, INF NOTE50, MOTO E15, INF NOTE50PRO+, INF NOTE5X, MOTOG05, MOTOG15, VIVOY39 5G, MOTOG75, INFNOTE50PRO, SPARK30 5G, OP A5PRO 5G, SPARK40L, REALME C71, OP A5, Y21D, Y31, MOTO G57, MOTO G35',
            'size' => '6.72 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E48',
            'summary' => 'REDMI note7/REDMI note7pro/畅享10e/畅享9A/Y6P(2020)/畅享Z/SamA22PlusZW/Y6(2020)/POP6',
            'compat' => 'REDMI note7, REDMI note7pro, 畅享10e, 畅享9A, Y6P(2020), 畅享Z, SamA22PlusZW, Y6(2020), POP6',
            'size' => '6.3 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E49',
            'summary' => 'POCO M3PRO /REDMI NOTE 10(5G)/REDMI Note10T(5G)/REDMI 10/REDMI 10Prime/SAM M22/MI12LITE/VIVO V25/V25E/REDMI NOTE11SE 5G/MOTO G71 5G/G62 5G/REDMINOTE12S/MOTO G73 5G/MOTOG13/MOTOG53/MOTO G14',
            'compat' => 'POCO M3PRO , REDMI NOTE 10(5G), REDMI Note10T(5G), REDMI 10, REDMI 10Prime, SAM M22, MI12LITE, VIVO V25, V25E, REDMI NOTE11SE 5G, MOTO G71 5G, G62 5G, REDMINOTE12S, MOTO G73 5G, MOTOG13, MOTOG53, MOTO G14',
            'size' => '6.5 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E50',
            'summary' => 'REDMI 10C/REDMI10POWER/NOVA Y70/HONORX7A/REDMI12C/POCO C40/NOVAY70PLUS/REALMEC53/NOVA Y71/REALMEC51/LT2003/POCO C65/REDMI A3/NOVA Y71/REALME C63/REALME C61/REDMI A3X',
            'compat' => 'REDMI 10C, REDMI10POWER, NOVA Y70, HONORX7A, REDMI12C, POCO C40, NOVAY70PLUS, REALMEC53, NOVA Y71, REALMEC51, LT2003, POCO C65, REDMI A3, NOVA Y71, REALME C63, REALME C61, REDMI A3X',
            'size' => '6.71 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E51',
            'summary' => 'REDMI 14C/REDMI A4/POCO C75 4G/POCO C75 5G/REDMI 14R/REDMI A5/REDMI A3PRO/POCO M7',
            'compat' => 'REDMI 14C, REDMI A4, POCO C75 4G, POCO C75 5G, REDMI 14R, REDMI A5, REDMI A3PRO, POCO M7',
            'size' => '6.88 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E52',
            'summary' => 'LT6509/SMART8 /SMART8PRO/SPARKGO 2024/POP8/MOTOG23/G34/HONOR90LITE/POCO X6 5G/POCO X6PRO//POCO M6PRO/POCO F6PRO/POCO F6/MI14T/MI14TPRO/NOVA 12/NOVA 11/POCO X7PRO/V40LITE/V40LITE/POCOX7PRO/MOTOG55/POCO F7PRO/HONOR X6C',
            'compat' => 'LT6509, SMART8 , SMART8PRO, SPARKGO 2024, POP8, MOTOG23, G34, HONOR90LITE, POCO X6 5G, POCO X6PRO, POCO M6PRO, POCO F6PRO, POCO F6, MI14T, MI14TPRO, NOVA 12, NOVA 11, POCO X7PRO, V40LITE, V40LITE, POCOX7PRO, MOTOG55, POCO F7PRO, HONOR X6C',
            'size' => '6.6 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E53',
            'summary' => 'Hot 8/X650B/HOT 8Lite/HOT 10Lite/Note11/S16/SPARK8T/SPARK9/SPARK9PRO/KC8/HOT20i/HOT20i/SPARK9T/SPARK8/SPARK10 4G/ITEL P40/NOTE30/NOTE30PRO/X657/POP6PRO/NOTE12PRO/KC8/SMART 5/Spark4/Spark GO/Spark 8P/HOT 11/Spark8C/Hot10i/S17/ITEL A58/Pop 5p/Camon 12/Pop 3+ /P36/P37/Spark6Go 2021/Smart 5pro/POP 5pro/spark9t/SMART7/LTEI A60/SPARK GO 2023/POP7PRO',
            'compat' => 'Hot 8, X650B, HOT 8Lite, HOT 10Lite, Note11, S16, SPARK8T, SPARK9, SPARK9PRO, KC8, HOT20i, HOT20i, SPARK9T, SPARK8, SPARK10 4G, ITEL P40, NOTE30, NOTE30PRO, X657, POP6PRO, NOTE12PRO, KC8, SMART 5, Spark4, Spark GO, Spark 8P, HOT 11, Spark8C, Hot10i, S17, ITEL A58, Pop 5p, Camon 12, Pop 3+ , P36, P37, Spark6Go 2021, Smart 5pro, POP 5pro, spark9t, SMART7, LTEI A60, SPARK GO 2023, POP7PRO',
            'size' => '6.52 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E54',
            'summary' => 'HOT 9play/Smart 5(印度版）//HOT 10play/HOT 10S/HOT 10T/HOT 11Play/Spark7P/SMART6PLUS',
            'compat' => 'HOT 9play, Smart 5(印度版）, HOT 10play, HOT 10S, HOT 10T, HOT 11Play, Spark7P, SMART6PLUS',
            'size' => '6.82 inch',
            'camera' => 'Poni / Waterdrop'
        ],
        [
            'kode' => 'E55',
            'summary' => 'HOT11S/HOT40/HOT40PRO/REDMI13/REDMI 12/HONOR X7C/REDMI NOTE13R/HOT 50 4G/REDMINOTE13R/SPARK30  4G/REDMI13X/SPARK 30PRO/POVANEO 3/POVA6 NEO/HONOR 400SMART/HONOR X7D',
            'compat' => 'HOT11S, HOT40, HOT40PRO, REDMI13, REDMI 12, HONOR X7C, REDMI NOTE13R, HOT 50 4G, REDMINOTE13R, SPARK30  4G, REDMI13X, SPARK 30PRO, POVANEO 3, POVA6 NEO, HONOR 400SMART, HONOR X7D',
            'size' => '6.78 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E56',
            'summary' => 'HOT12 /G51 5G/G60/HOT12PLAY',
            'compat' => 'HOT12 , G51 5G, G60, HOT12PLAY',
            'size' => '6.82 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E57',
            'summary' => 'Infinix note 10pro/note 10/LG STYLO6',
            'compat' => 'Infinix note 10pro, note 10, LG STYLO6',
            'size' => '6.95 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E63',
            'summary' => 'Hot 40i',
            'compat' => 'Hot 40i',
            'size' => '6.56 inch',
            'camera' => 'Punch Hole (Tengah)'
        ],
        [
            'kode' => 'E75',
            'summary' => 'Reno6Z/Reno7Z/Oneplus nord ce2(5g）/Realme 9pro+ /OP F21pro4g/5g/RENO5Z/RENO8 5G/OP A78 4G',
            'compat' => 'Reno6Z, Reno7Z, Oneplus nord ce2(5g）, Realme 9pro+ , OP F21pro4g, 5g, RENO5Z, RENO8 5G, OP A78 4G',
            'size' => '6.43 inch',
            'camera' => 'Punch Hole (Kiri)'
        ]
        ];

        $count = 0;

        foreach ($dataGroups as $group) {
            $kode = $group['kode'];
            $sizeStr = $group['size'];
            $camStr = $group['camera'];
            $compatStr = $group['compat'];

            // Resolve ScreenSize & CameraPosition
            $screenSize = ScreenSize::firstOrCreate(['size' => $sizeStr]);
            $cameraPos = CameraPosition::firstOrCreate(
                ['position' => $camStr],
                ['group' => 'A']
            );

            // Split compatible phone models by comma or slash
            $models = array_filter(array_map('trim', explode(',', str_replace('/', ',', $compatStr))));

            foreach ($models as $modelName) {
                if (empty($modelName)) continue;

                // Detect Brand
                $brandName = 'Umum';
                $mLow = strtolower($modelName);
                if (str_contains($mLow, 'iphone') || str_contains($mLow, 'ipad')) {
                    $brandName = 'Apple';
                } elseif (str_contains($mLow, 'sam') || str_contains($mLow, 'samsung')) {
                    $brandName = 'Samsung';
                } elseif (str_contains($mLow, 'redmi') || str_contains($mLow, 'xiaomi') || str_contains($mLow, 'mi') || str_contains($mLow, 'poco')) {
                    $brandName = 'Xiaomi';
                } elseif (str_contains($mLow, 'vivo') || str_contains($mLow, 'v20') || str_contains($mLow, 'y20')) {
                    $brandName = 'Vivo';
                } elseif (str_contains($mLow, 'oppo') || str_contains($mLow, 'reno') || str_contains($mLow, 'op ')) {
                    $brandName = 'Oppo';
                } elseif (str_contains($mLow, 'realme')) {
                    $brandName = 'Realme';
                } elseif (str_contains($mLow, 'infinix') || str_contains($mLow, 'hot') || str_contains($mLow, 'spark')) {
                    $brandName = 'Infinix';
                } elseif (str_contains($mLow, 'honor')) {
                    $brandName = 'Honor';
                }

                $brand = Brand::firstOrCreate(['name' => $brandName]);

                HpData::firstOrCreate(
                    [
                        'type' => $modelName,
                        'brand_id' => $brand->id,
                    ],
                    [
                        'code_tg' => $kode,
                        'screen_size_id' => $screenSize->id,
                        'camera_position_id' => $cameraPos->id,
                    ]
                );
                $count++;
            }
        }

        $this->command->info("Berhasil merombak dan mengisi {$count} data HP valid ke database!");
    }
}
