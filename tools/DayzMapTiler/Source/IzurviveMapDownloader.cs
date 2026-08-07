using SixLabors.ImageSharp;
using SixLabors.ImageSharp.PixelFormats;
using SixLabors.ImageSharp.Processing;

namespace DayzMapTiler.Source;

/// <summary>
/// Downloads and stitches a Chernarus+/Livonia map straight from iZurvive's public tile
/// server — the same source the community "DayZ-Map-DL" script uses
/// (https://maps.izurvive.com/maps/{Map}-{Type}/{version}/tiles/{res}/{x}/{y}.jpg), just
/// implemented directly in .NET so it doesn't need aria2c/ImageMagick/g++ (none of which
/// are available on this machine). Reused as this tool's actual satellite source since
/// the game's own PBOs don't ship one (see chat).
/// </summary>
public static class IzurviveMapDownloader
{
    private static readonly HttpClient Http = new() { Timeout = TimeSpan.FromSeconds(30) };

    public static (int TilesPerSide, string Url) Plan(string map, string type, string version, int res)
    {
        var mapFolder = (map, type) switch
        {
            ("chernarus", "sat") => "ChernarusPlus-Sat",
            ("chernarus", "top") => "ChernarusPlus-Top",
            ("livonia", "sat") => "Livonia-Sat",
            ("livonia", "top") => "Livonia-Top",
            _ => throw new ArgumentException($"Unknown map/type combination: {map}/{type}"),
        };
        var tilesPerSide = 1 << res; // 2^res, matches getmap.sh's SIZE+1
        var sampleUrl = $"https://maps.izurvive.com/maps/{mapFolder}/{version}/tiles/{res}/0/0.jpg";
        return (tilesPerSide, sampleUrl);
    }

    public static async Task<Image<Rgba32>> DownloadAndStitchAsync(
        string map, string type, string version, int res,
        int maxConcurrency, IProgress<(int done, int total)>? progress, CancellationToken ct)
    {
        var mapFolder = (map, type) switch
        {
            ("chernarus", "sat") => "ChernarusPlus-Sat",
            ("chernarus", "top") => "ChernarusPlus-Top",
            ("livonia", "sat") => "Livonia-Sat",
            ("livonia", "top") => "Livonia-Top",
            _ => throw new ArgumentException($"Unknown map/type combination: {map}/{type}"),
        };
        var tilesPerSide = 1 << res;

        // Probe tile (0,0) first to learn the per-tile pixel size and fail fast with a
        // clear error if this version/resolution isn't actually hosted.
        var probeBytes = await DownloadTileAsync(mapFolder, version, res, 0, 0, ct)
            ?? throw new InvalidOperationException(
                $"Dlaždice (0,0) pro {mapFolder}/{version}/{res} se nepodařilo stáhnout — zkontroluj přesné číslo verze hry (musí sedět přesně, např. '1.25.0').");
        using var probeImage = Image.Load<Rgba32>(probeBytes);
        var tileImgSize = probeImage.Width;

        var canvas = new Image<Rgba32>(tileImgSize * tilesPerSide, tileImgSize * tilesPerSide);
        canvas.Mutate(ctx => ctx.DrawImage(probeImage, new Point(0, 0), 1f));

        var total = tilesPerSide * tilesPerSide;
        var done = 1;
        progress?.Report((done, total));

        var coords = new List<(int x, int y)>();
        for (var y = 0; y < tilesPerSide; y++)
        {
            for (var x = 0; x < tilesPerSide; x++)
            {
                if (x == 0 && y == 0) continue;
                coords.Add((x, y));
            }
        }

        using var gate = new SemaphoreSlim(maxConcurrency);
        var canvasLock = new object();
        var failures = new List<(int x, int y)>();

        var tasks = coords.Select(async coord =>
        {
            await gate.WaitAsync(ct);
            try
            {
                var bytes = await DownloadTileAsync(mapFolder, version, res, coord.x, coord.y, ct);
                if (bytes is null)
                {
                    lock (canvasLock) { failures.Add(coord); }
                    return;
                }

                using var tile = Image.Load<Rgba32>(bytes);
                lock (canvasLock)
                {
                    canvas.Mutate(ctx => ctx.DrawImage(tile, new Point(coord.x * tileImgSize, coord.y * tileImgSize), 1f));
                    done++;
                    progress?.Report((done, total));
                }
            }
            finally
            {
                gate.Release();
            }
        });

        await Task.WhenAll(tasks);

        if (failures.Count > 0)
        {
            Console.WriteLine($"POZOR: {failures.Count} z {total} dlaždic se nestáhlo (zůstanou černé/průhledné na výsledném obrázku): "
                + string.Join(", ", failures.Take(20).Select(f => $"({f.x},{f.y})")) + (failures.Count > 20 ? ", ..." : ""));
        }

        return canvas;
    }

    private static async Task<byte[]?> DownloadTileAsync(string mapFolder, string version, int res, int x, int y, CancellationToken ct)
    {
        var url = $"https://maps.izurvive.com/maps/{mapFolder}/{version}/tiles/{res}/{x}/{y}.jpg";
        try
        {
            using var response = await Http.GetAsync(url, ct);
            if (!response.IsSuccessStatusCode)
            {
                return null;
            }
            return await response.Content.ReadAsByteArrayAsync(ct);
        }
        catch (Exception)
        {
            return null;
        }
    }
}
