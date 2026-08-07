<?php

namespace App\Support;

/**
 * Static place-name labels for the Chernarus+ map (world X/Z, same convention as
 * everything else the Map editor plots — see map-editor.blade.php's `[worldZ, worldX]`
 * marker coordinates). Not derived from any uploaded config — DayZ's config XMLs don't
 * carry human-readable place names — sourced from the community-maintained coordinate
 * list at https://www.reddit.com/r/dayz/comments/10pe12o/ (u/Old-Toe-1600), with a
 * handful of spelling corrections against the DayZ wiki (Elektrozavodsk, Solnichniy,
 * Kabanino) and de-duplication of near-identical repeated entries (Rogovo, Nadezhda,
 * Gaglovo/Guglovo).
 */
final class ChernarusPlaces
{
    /** @return list<array{name:string,x:float,z:float}> */
    public static function all(): array
    {
        return [
            ['name' => 'Volchiypik', 'x' => 300, 'z' => 2242],
            ['name' => 'Kamenka', 'x' => 1942, 'z' => 2242],
            ['name' => 'Kamenka Military Base', 'x' => 2096, 'z' => 3326],
            ['name' => 'Pavlovo', 'x' => 1702, 'z' => 3825],
            ['name' => 'Bor', 'x' => 3315, 'z' => 3982],
            ['name' => 'Komarovo', 'x' => 3663, 'z' => 2482],
            ['name' => 'Balota', 'x' => 4492, 'z' => 2445],
            ['name' => 'Balota Airfield', 'x' => 4957, 'z' => 2456],
            ['name' => 'Vysotovo', 'x' => 5782, 'z' => 2553],
            ['name' => 'Novoselki', 'x' => 6217, 'z' => 3277],
            ['name' => 'Dubovo', 'x' => 6723, 'z' => 3630],
            ['name' => 'Chernogorsk', 'x' => 6472, 'z' => 2568],
            ['name' => 'Prigorodki', 'x' => 7732, 'z' => 3288],
            ['name' => 'Pusta', 'x' => 9157, 'z' => 3858],
            ['name' => 'Kometa', 'x' => 10338, 'z' => 3562],
            ['name' => 'Elektrozavodsk', 'x' => 10196, 'z' => 2130],
            ['name' => 'Rog', 'x' => 11250, 'z' => 4293],
            ['name' => 'Tulga', 'x' => 12720, 'z' => 4398],
            ['name' => 'Kamyshovo', 'x' => 12067, 'z' => 3517],
            ['name' => 'Voron', 'x' => 13455, 'z' => 3292],
            ['name' => 'Skalisty', 'x' => 13665, 'z' => 3026],
            ['name' => 'Zvir', 'x' => 551, 'z' => 5358],
            ['name' => 'Metalurg', 'x' => 1076, 'z' => 6630],
            ['name' => 'Sosnovka', 'x' => 2523, 'z' => 6375],
            ['name' => 'Zelenogorsk', 'x' => 2752, 'z' => 5276],
            ['name' => 'Zelenogorsk Military Base', 'x' => 2482, 'z' => 5167],
            ['name' => 'Pogorevka', 'x' => 4451, 'z' => 6427],
            ['name' => 'Rogovo', 'x' => 4766, 'z' => 6791],
            ['name' => 'Pulkovo', 'x' => 4942, 'z' => 5696],
            ['name' => 'Kozlovka', 'x' => 4376, 'z' => 4687],
            ['name' => 'Nadezhdino', 'x' => 5853, 'z' => 4803],
            ['name' => 'Vyhnoye', 'x' => 6570, 'z' => 6060],
            ['name' => 'Zub', 'x' => 6547, 'z' => 5583],
            ['name' => 'Nadezhda', 'x' => 7248, 'z' => 7008],
            ['name' => 'Mogilevka', 'x' => 7556, 'z' => 5163],
            ['name' => 'Kumyrna', 'x' => 8396, 'z' => 5985],
            ['name' => 'Simurg', 'x' => 228, 'z' => 7512],
            ['name' => 'Simurg Military Warehouse', 'x' => 975, 'z' => 7638],
            ['name' => 'Simurg Military Barracks', 'x' => 1166, 'z' => 7233],
            ['name' => 'Tri Kresta', 'x' => 333, 'z' => 9367],
            ['name' => 'Galkino', 'x' => 1203, 'z' => 8793],
            ['name' => 'Kroma', 'x' => 1436, 'z' => 9217],
            ['name' => 'Bogatyvka', 'x' => 1563, 'z' => 8959],
            ['name' => 'Myshino', 'x' => 1972, 'z' => 7342],
            ['name' => 'Vybor', 'x' => 3840, 'z' => 8921],
            ['name' => 'Pustoska', 'x' => 3071, 'z' => 7912],
            ['name' => 'Vybor Military Base', 'x' => 4440, 'z' => 8302],
            ['name' => 'Kabanino', 'x' => 5328, 'z' => 8617],
            ['name' => 'Stary Sobor', 'x' => 6075, 'z' => 7758],
            ['name' => 'Gnomovzamok', 'x' => 7391, 'z' => 9078],
            ['name' => 'Novy Sobor', 'x' => 7102, 'z' => 7668],
            ['name' => 'Radio Zenit', 'x' => 8107, 'z' => 9330],
            ['name' => 'Altar', 'x' => 8148, 'z' => 9120],
            ['name' => 'Guglovo', 'x' => 8433, 'z' => 6630],
            ['name' => 'Shakovka', 'x' => 9622, 'z' => 6555],
            ['name' => 'Staroye', 'x' => 10128, 'z' => 5433],
            ['name' => 'Msta', 'x' => 11321, 'z' => 5486],
            ['name' => 'Dolina', 'x' => 11305, 'z' => 6600],
            ['name' => 'Solnichniy', 'x' => 13395, 'z' => 6240],
            ['name' => 'Gorka', 'x' => 9558, 'z' => 8823],
            ['name' => 'Polana', 'x' => 10736, 'z' => 8030],
            ['name' => 'Orlovets', 'x' => 10903, 'z' => 9120],
            ['name' => 'Berezino', 'x' => 12011, 'z' => 9065],
            ['name' => 'Khelm', 'x' => 12947, 'z' => 10117],
            ['name' => 'Krutoy Cap', 'x' => 14275, 'z' => 12956],
            ['name' => 'Rify', 'x' => 14195, 'z' => 13785],
            ['name' => 'Olsha', 'x' => 13830, 'z' => 13310],
            ['name' => 'Svetlojarsk', 'x' => 13448, 'z' => 12731],
            ['name' => 'Krasnostav', 'x' => 12725, 'z' => 9627],
            ['name' => 'Krasnostav Airfield', 'x' => 12142, 'z' => 11228],
            ['name' => 'Black Forest', 'x' => 12317, 'z' => 12467],
            ['name' => 'Black Mountain', 'x' => 11213, 'z' => 12271],
            ['name' => "Devil's Castle", 'x' => 9978, 'z' => 12100],
            ['name' => 'Gvozdno', 'x' => 8802, 'z' => 11654],
            ['name' => 'Grishino', 'x' => 8059, 'z' => 12623],
            ['name' => 'Petrovka', 'x' => 7180, 'z' => 12788],
            ['name' => 'Topolniki', 'x' => 6075, 'z' => 12895],
            ['name' => 'Dubrovka', 'x' => 6170, 'z' => 11220],
            ['name' => 'Severograd', 'x' => 5103, 'z' => 12480],
            ['name' => 'Veresnik', 'x' => 4520, 'z' => 11852],
            ['name' => 'Tisy', 'x' => 3664, 'z' => 13149],
            ['name' => 'Vavilovo', 'x' => 3085, 'z' => 12036],
            ['name' => 'Lopatino', 'x' => 2059, 'z' => 12464],
            ['name' => 'Kamensk', 'x' => 3373, 'z' => 14802],
            ['name' => 'Zaprudnoe', 'x' => 1621, 'z' => 13862],
        ];
    }
}
