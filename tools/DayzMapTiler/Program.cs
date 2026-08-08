using DayzMapTiler.Output;
using DayzMapTiler.Source;
using DayzMapTiler.Tiling;
using SixLabors.ImageSharp;
using SixLabors.ImageSharp.PixelFormats;
using SixLabors.ImageSharp.Processing;

if (args.Length > 0 && args[0] == "inspect-pbo")
{
    return DayzMapTiler.Source.CargoSizeInspect.Run(args[1..]);
}

if (args.Length > 0 && args[0] == "cargo-catalog")
{
    return DayzMapTiler.Source.CargoCatalogBuilder.Run(args[1..]);
}

if (args.Length > 0 && args[0] == "debug-class")
{
    return DayzMapTiler.Source.DebugClassInspect.Run(args[1..]);
}

if (args.Length > 0 && args[0] == "search-class")
{
    return DayzMapTiler.Source.SearchClassInspect.Run(args[1..]);
}

if (args.Length > 0 && args[0] == "fetch")
{
    return await RunFetchAsync(args[1..]);
}

if (args.Length > 0 && args[0] == "crop")
{
    return RunCrop(args[1..]);
}

if (args.Length > 0 && args[0] == "preview")
{
    return RunPreview(args[1..]);
}

if (args.Length > 0 && args[0] == "find-border")
{
    return RunFindBorder(args[1..]);
}

return RunTile(args);

static int RunFindBorder(string[] args)
{
    // args: <sourcePath>
    var src = args[0];
    using var image = Image.Load<Rgba32>(src);
    var w = image.Width;
    var h = image.Height;
    Console.WriteLine($"Image: {w}x{h}");

    // "Border" heuristic: the iZurvive frame/watermark background is a flat mid-dark gray
    // (measured ~34,34,34) — real terrain/sea pixels always have visible R/G/B separation
    // (greens, browns, blues), a flat near-equal-channel gray does not occur in map content.
    bool IsDark(Rgba32 p) => p.R < 45 && p.G < 45 && p.B < 45 && Math.Abs(p.R - p.G) < 6 && Math.Abs(p.G - p.B) < 6;

    image.ProcessPixelRows(accessor =>
    {
        var midY = h / 2;
        var midX = w / 2;
        var rowMid = accessor.GetRowSpan(midY);

        int left = 0;
        while (left < w && IsDark(rowMid[left])) left++;

        int right = w - 1;
        while (right > 0 && IsDark(rowMid[right])) right--;

        int top = 0;
        for (; top < h; top++)
        {
            if (!IsDark(accessor.GetRowSpan(top)[midX])) break;
        }

        int bottom = h - 1;
        for (; bottom > 0; bottom--)
        {
            if (!IsDark(accessor.GetRowSpan(bottom)[midX])) break;
        }

        Console.WriteLine($"Scanning from center row/col: left={left} right={right} top={top} bottom={bottom}");

        // Cross-check at several other lines, in case the midline hit a lake/coast pixel
        // that happened to look border-like (or vice versa).
        foreach (var fraction in new[] { 0.1, 0.25, 0.75, 0.9 })
        {
            var y = (int) (h * fraction);
            var row = accessor.GetRowSpan(y);
            int t = 0; while (t < w && IsDark(row[t])) t++;
            var x = (int) (w * fraction);
            int topAt = 0; for (; topAt < h; topAt++) { if (!IsDark(accessor.GetRowSpan(topAt)[x])) break; }
            int rightAt = w - 1; { var rowAtY = accessor.GetRowSpan((int)(h * fraction)); while (rightAt > 0 && IsDark(rowAtY[rightAt])) rightAt--; }
            Console.WriteLine($"  at fraction={fraction}: topBorderAtX{x}={topAt}, rightBorderAtY{(int)(h*fraction)}={rightAt}");
        }
    });

    // Also print raw pixel samples along the very first/last 60px of each edge at the
    // midline, in case the border isn't pure black (e.g. dark green sea / gradient).
    image.ProcessPixelRows(accessor =>
    {
        var midY = h / 2;
        var row = accessor.GetRowSpan(midY);
        Console.WriteLine("Top-left horizontal samples (x, R,G,B) at midY:");
        for (var x = 0; x < 80; x += 4)
        {
            var p = row[x];
            Console.WriteLine($"  x={x}: {p.R},{p.G},{p.B}");
        }
        Console.WriteLine("Right edge horizontal samples (x from right, R,G,B) at midY:");
        for (var dx = 0; dx < 200; dx += 8)
        {
            var x = w - 1 - dx;
            var p = row[x];
            Console.WriteLine($"  x={x} (w-{dx}): {p.R},{p.G},{p.B}");
        }
    });

    image.ProcessPixelRows(accessor =>
    {
        var midX = w / 2;
        Console.WriteLine("Top edge vertical samples (y, R,G,B) at midX:");
        for (var y = 0; y < 80; y += 4)
        {
            var p = accessor.GetRowSpan(y)[midX];
            Console.WriteLine($"  y={y}: {p.R},{p.G},{p.B}");
        }
        Console.WriteLine("Bottom edge vertical samples (y from bottom, R,G,B) at midX:");
        for (var dy = 0; dy < 200; dy += 8)
        {
            var y = h - 1 - dy;
            var p = accessor.GetRowSpan(y)[midX];
            Console.WriteLine($"  y={y} (h-{dy}): {p.R},{p.G},{p.B}");
        }
    });

    return 0;
}

static int RunPreview(string[] args)
{
    // args: <sourcePath> <size> <outPath>
    var src = args[0];
    var size = int.Parse(args[1]);
    var outPath = args[2];

    using var image = Image.Load<Rgba32>(src);
    using var resized = image.Clone(ctx => ctx.Resize(size, size));
    SaveByExtension(resized, outPath);
    Console.WriteLine($"Preview {size}x{size} -> {outPath}");
    return 0;
}

static void SaveByExtension(Image<Rgba32> image, string outPath)
{
    var ext = Path.GetExtension(outPath).ToLowerInvariant();
    if (ext is ".jpg" or ".jpeg")
    {
        image.SaveAsJpeg(outPath, new SixLabors.ImageSharp.Formats.Jpeg.JpegEncoder { Quality = 85 });
    }
    else
    {
        image.SaveAsPng(outPath);
    }
}

static int RunCrop(string[] args)
{
    // args: <sourcePath> <x> <y> <size> <outPath>
    var src = args[0];
    var x = int.Parse(args[1]);
    var y = int.Parse(args[2]);
    var size = int.Parse(args[3]);
    var outPath = args[4];

    using var image = Image.Load<Rgba32>(src);
    using var crop = image.Clone(ctx => ctx.Crop(new Rectangle(x, y, size, size)));
    SaveByExtension(crop, outPath);
    Console.WriteLine($"Crop {size}x{size} at ({x},{y}) -> {outPath}");
    return 0;
}

static int RunTile(string[] args)
{
    var options = CliOptions.Parse(args);
    if (options is null)
    {
        CliOptions.PrintUsage();
        return 1;
    }

    Console.WriteLine($"DayzMapTiler — {options.Map}");
    Console.WriteLine($"Zdrojový obrázek: {options.SourceImage}");
    Console.WriteLine($"Výstup: {options.OutputDir}");

    Directory.CreateDirectory(options.OutputDir);

    using var source = Image.Load<Rgba32>(options.SourceImage);
    Console.WriteLine($"Načteno {source.Width}x{source.Height}px.");

    if (source.Width != source.Height)
    {
        Console.WriteLine($"POZOR: zdrojový obrázek není čtvercový ({source.Width}x{source.Height}) — svět DayZ je čtvercový (X i Z {options.WorldSize}), výstup bude natažený/zkreslený. Ořízni zdroj na čtverec před spuštěním.");
    }

    var previewSize = Math.Min(1024, source.Width);
    using (var preview = source.Clone(ctx => ctx.Resize(previewSize, previewSize)))
    {
        preview.SaveAsPng(Path.Combine(options.OutputDir, "preview.png"));
    }
    Console.WriteLine("Uložen preview.png.");

    var tilesDir = Path.Combine(options.OutputDir, "tiles");
    Console.WriteLine($"Generuji XYZ dlaždice (zoom {options.MinZoom}..{options.MaxZoom}, tileSize={options.TileSize}, formát={options.Format})...");
    XyzTilePyramidBuilder.Build(source, tilesDir, options.TileSize, options.MinZoom, options.MaxZoom, options.Format);

    TilesJsonWriter.Write(Path.Combine(options.OutputDir, "tiles.json"), new TilesJsonModel
    {
        Map = options.Map,
        WorldSize = options.WorldSize,
        TileSize = options.TileSize,
        MinZoom = options.MinZoom,
        MaxZoom = options.MaxZoom,
        Projection = "dayz",
    });
    Console.WriteLine("Uložen tiles.json.");

    Console.WriteLine("Hotovo.");
    return 0;
}

static async Task<int> RunFetchAsync(string[] args)
{
    var opts = FetchOptions.Parse(args);
    if (opts is null)
    {
        FetchOptions.PrintUsage();
        return 1;
    }

    var (tilesPerSide, sampleUrl) = IzurviveMapDownloader.Plan(opts.Map, opts.Type, opts.Version, opts.Res);
    Console.WriteLine($"Stahuji {opts.Map}/{opts.Type} verze {opts.Version}, rozlišení {opts.Res} ({tilesPerSide}x{tilesPerSide} = {tilesPerSide * tilesPerSide} dlaždic) z maps.izurvive.com...");
    Console.WriteLine($"Příklad URL: {sampleUrl}");

    var progress = new Progress<(int done, int total)>(p =>
    {
        if (p.done % 200 == 0 || p.done == p.total)
        {
            Console.WriteLine($"  {p.done}/{p.total} dlaždic staženo...");
        }
    });

    using var stitched = await IzurviveMapDownloader.DownloadAndStitchAsync(
        opts.Map, opts.Type, opts.Version, opts.Res, maxConcurrency: 24, progress, CancellationToken.None);

    Directory.CreateDirectory(Path.GetDirectoryName(opts.Out)!);
    await stitched.SaveAsJpegAsync(opts.Out);
    Console.WriteLine($"Hotovo: {opts.Out} ({stitched.Width}x{stitched.Height}px).");
    return 0;
}

sealed class CliOptions
{
    public required string SourceImage { get; init; }
    public required string OutputDir { get; init; }
    public string Map { get; init; } = "chernarusplus";
    public int WorldSize { get; init; } = 15360;
    public int TileSize { get; init; } = 512;
    public int MinZoom { get; init; } = 0;
    public int MaxZoom { get; init; } = 8;
    public string Format { get; init; } = "webp";

    public static CliOptions? Parse(string[] args)
    {
        string? sourceImage = null, outputDir = null, map = "chernarusplus", format = "webp";
        int worldSize = 15360, tileSize = 512, minZoom = 0, maxZoom = 8;

        for (var i = 0; i < args.Length; i++)
        {
            switch (args[i])
            {
                case "--source-image": sourceImage = args[++i]; break;
                case "--out": outputDir = args[++i]; break;
                case "--map": map = args[++i]; break;
                case "--world-size": worldSize = int.Parse(args[++i]); break;
                case "--tile-size": tileSize = int.Parse(args[++i]); break;
                case "--min-zoom": minZoom = int.Parse(args[++i]); break;
                case "--max-zoom": maxZoom = int.Parse(args[++i]); break;
                case "--format": format = args[++i]; break;
            }
        }

        if (sourceImage is null || outputDir is null)
        {
            return null;
        }

        return new CliOptions
        {
            SourceImage = sourceImage,
            OutputDir = outputDir,
            Map = map,
            WorldSize = worldSize,
            TileSize = tileSize,
            MinZoom = minZoom,
            MaxZoom = maxZoom,
            Format = format,
        };
    }

    public static void PrintUsage()
    {
        Console.WriteLine("""
            Usage: dotnet run -- --source-image <path> --out <dir> [--map chernarusplus]
                   [--world-size 15360] [--tile-size 512] [--min-zoom 0] [--max-zoom 8]

            Example:
              dotnet run -- --source-image "D:\...\DayZ_1.25.0_chernarus_map_16x16_sat.jpg" --out "D:\...\out" --max-zoom 6

            Subcommand:
              dotnet run -- fetch --map chernarus --type sat --version 1.25.0 --res 6 --out <file.jpg>
            """);
    }
}

sealed class FetchOptions
{
    public required string Out { get; init; }
    public string Map { get; init; } = "chernarus";
    public string Type { get; init; } = "sat";
    public required string Version { get; init; }
    public int Res { get; init; } = 6;

    public static FetchOptions? Parse(string[] args)
    {
        string? outPath = null, version = null, map = "chernarus", type = "sat";
        var res = 6;

        for (var i = 0; i < args.Length; i++)
        {
            switch (args[i])
            {
                case "--out": outPath = args[++i]; break;
                case "--map": map = args[++i]; break;
                case "--type": type = args[++i]; break;
                case "--version": version = args[++i]; break;
                case "--res": res = int.Parse(args[++i]); break;
            }
        }

        if (outPath is null || version is null)
        {
            return null;
        }

        return new FetchOptions { Out = outPath, Map = map, Type = type, Version = version, Res = res };
    }

    public static void PrintUsage()
    {
        Console.WriteLine("""
            Usage: dotnet run -- fetch --version <e.g. 1.25.0> --out <file.jpg> [--map chernarus] [--type sat] [--res 6]
            """);
    }
}
