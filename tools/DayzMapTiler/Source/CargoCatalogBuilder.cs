using System.Text.Json;
using System.Text.RegularExpressions;
using DayzMapTiler.Pbo;

namespace DayzMapTiler.Source;

/// <summary>
/// Scans every PBO in a DayZ install's Addons folder — including per-model sub-configs like
/// "AKM\config.bin" inside weapons_firearms.pbo, not just each PBO's top-level config.bin —
/// for two distinct, real DayZ config properties:
///   itemSize[]        — how much space THIS classname itself takes up sitting inside
///                        someone else's inventory grid (used on nearly all lootable items:
///                        weapons, magazines, food, clothing, tools, ...).
///   itemsCargoSize[]   — the size of the storage grid THIS classname itself provides to hold
///                        OTHER things (containers, vehicle trunks, and some clothing with
///                        pockets — a class can have both at once, e.g. a jacket has its own
///                        itemSize when carried AND itemsCargoSize for its pockets).
/// Both are resolved through name-based class inheritance across the ENTIRE scanned file set
/// (a global cross-file classname index), because many items only carry these properties on a
/// shared base class defined in a completely different PBO than the item itself.
/// This is real data extracted from the game's own binarized configs — not a guess.
/// </summary>
public static class CargoCatalogBuilder
{
    public sealed class CatalogEntry
    {
        public required string Classname { get; init; }
        public (int Width, int Height)? Footprint { get; init; }
        public (int Width, int Height)? Capacity { get; init; }
        public required string SourcePbo { get; init; }
    }

    private static readonly string[] RootConfigNames = { "CfgVehicles", "CfgWeapons", "CfgMagazines" };
    private static readonly Regex ConfigBinPattern = new(@"(^|/)config\.bin$", RegexOptions.IgnoreCase | RegexOptions.Compiled);

    private sealed record ParsedConfig(string FileName, string InternalPath, RapifiedConfig Rap);

    public static int Run(string[] args)
    {
        var addonsDir = args[0];
        var outputPath = args[1];

        var pboFiles = Directory.GetFiles(addonsDir, "*.pbo").OrderBy(f => f).ToList();
        Console.WriteLine($"Found {pboFiles.Count} PBO files in {addonsDir}");

        // Pass 1: open every PBO, parse every config.bin found anywhere inside it (root AND
        // per-model subfolders), and build ONE global classname index across the whole set —
        // required because inheritance frequently crosses PBO/subfolder boundaries.
        var parsed = new List<ParsedConfig>();
        var globalIndex = new Dictionary<string, RapifiedConfig.RapClass>(StringComparer.OrdinalIgnoreCase);
        var indexCollisions = 0;
        var failed = new List<(string File, string Reason)>();
        var totalParseWarnings = 0;
        var totalNonHealthWarnings = 0;

        foreach (var pboPath in pboFiles)
        {
            var fileName = Path.GetFileName(pboPath);
            try
            {
                using var pbo = PboArchive.Open(pboPath);
                foreach (var configEntry in pbo.FindAllMatching(ConfigBinPattern))
                {
                    byte[] data;
                    RapifiedConfig rap;
                    try
                    {
                        data = pbo.ReadEntryData(configEntry);
                        rap = RapifiedConfig.Parse(data);
                    }
                    catch (Exception ex)
                    {
                        failed.Add(($"{fileName}:{configEntry.Name}", $"parse error: {ex.Message}"));
                        continue;
                    }

                    totalParseWarnings += rap.Warnings.Count;
                    var nonHealth = rap.Warnings.Count(w => !w.Contains("'Health'"));
                    totalNonHealthWarnings += nonHealth;

                    parsed.Add(new ParsedConfig(fileName, configEntry.Name, rap));
                    indexCollisions += IndexAll(rap.Root, globalIndex);
                }
            }
            catch (Exception ex)
            {
                failed.Add((fileName, ex.Message));
            }
        }

        Console.WriteLine($"Parsed {parsed.Count} config.bin files (root + per-model subfolders) across {pboFiles.Count} PBOs.");
        Console.WriteLine($"Global classname index: {globalIndex.Count} unique names ({indexCollisions} same-name redeclarations skipped, first-seen kept).");

        // Pass 2: for every direct child of a root config section in every parsed file (only
        // direct children are real classnames — deeper nesting like Cargo/Health/DamageSystem
        // is structural sub-config, never something that spawns/loots on its own), resolve its
        // own itemSize and itemsCargoSize by walking the Parent chain through the GLOBAL index.
        var byClassname = new Dictionary<string, CatalogEntry>(StringComparer.OrdinalIgnoreCase);
        var duplicates = new List<string>();
        var all = new List<CatalogEntry>();

        foreach (var pc in parsed)
        {
            foreach (var rootName in RootConfigNames)
            {
                var rootClass = pc.Rap.Root.Children.Values.FirstOrDefault(c => string.Equals(c.Name, rootName, StringComparison.OrdinalIgnoreCase));
                if (rootClass is null) continue;

                foreach (var cls in rootClass.Children.Values)
                {
                    var footprint = ResolveSize(globalIndex, cls, "itemSize", useNestedCargo: false);
                    var capacity = ResolveSize(globalIndex, cls, "itemsCargoSize", useNestedCargo: true);
                    if (footprint is null && capacity is null) continue;

                    var entry = new CatalogEntry
                    {
                        Classname = cls.Name,
                        Footprint = footprint,
                        Capacity = capacity,
                        SourcePbo = pc.FileName,
                    };

                    if (byClassname.TryGetValue(cls.Name, out var existing))
                    {
                        if (existing.Footprint == entry.Footprint && existing.Capacity == entry.Capacity)
                        {
                            continue; // identical duplicate (redeclared/extended elsewhere) — not a conflict
                        }
                        duplicates.Add($"{cls.Name}: {Describe(existing)} ({existing.SourcePbo}) vs {Describe(entry)} ({entry.SourcePbo})");
                        continue; // keep first-seen on a genuine conflict, flagged for manual review
                    }

                    byClassname[cls.Name] = entry;
                    all.Add(entry);
                }
            }
        }

        Console.WriteLine($"\nTotal classes with a resolved footprint and/or capacity: {all.Count}");
        Console.WriteLine($"  with itemSize (own footprint): {all.Count(e => e.Footprint is not null)}");
        Console.WriteLine($"  with itemsCargoSize (own storage capacity): {all.Count(e => e.Capacity is not null)}");
        Console.WriteLine($"Parse warnings across all files: {totalParseWarnings} ({totalNonHealthWarnings} outside the known-benign Health.healthLevels case)");
        Console.WriteLine($"Sub-configs that failed to open/parse: {failed.Count}");
        foreach (var (file, reason) in failed) Console.WriteLine($"  FAILED {file}: {reason}");
        Console.WriteLine($"Classname conflicts across files (kept first-seen, needs review): {duplicates.Count}");
        foreach (var d in duplicates.Take(30)) Console.WriteLine($"  CONFLICT {d}");

        var json = JsonSerializer.Serialize(
            all.OrderBy(e => e.Classname, StringComparer.OrdinalIgnoreCase).Select(e => new
            {
                classname = e.Classname,
                footprint_width = e.Footprint?.Width,
                footprint_height = e.Footprint?.Height,
                capacity_width = e.Capacity?.Width,
                capacity_height = e.Capacity?.Height,
                source_pbo = e.SourcePbo,
            }),
            new JsonSerializerOptions { WriteIndented = true });
        File.WriteAllText(outputPath, json);
        Console.WriteLine($"\nWrote {all.Count} entries to {outputPath}");

        return 0;
    }

    private static string Describe(CatalogEntry e) =>
        $"footprint={(e.Footprint is { } f ? $"{f.Width}x{f.Height}" : "-")} capacity={(e.Capacity is { } c ? $"{c.Width}x{c.Height}" : "-")}";

    /// <returns>0 if the class name was newly added, 1 if a same-named class already existed (skipped, first-seen kept) — used only to report a collision count.</returns>
    private static int IndexAll(RapifiedConfig.RapClass cls, Dictionary<string, RapifiedConfig.RapClass> into)
    {
        var collisions = 0;
        if (!string.IsNullOrEmpty(cls.Name))
        {
            if (!into.ContainsKey(cls.Name)) into[cls.Name] = cls;
            else collisions = 1;
        }
        foreach (var child in cls.Children.Values)
        {
            collisions += IndexAll(child, into);
        }
        return collisions;
    }

    private static (int Width, int Height)? ResolveSize(Dictionary<string, RapifiedConfig.RapClass> globalIndex, RapifiedConfig.RapClass cls, string propertyName, bool useNestedCargo)
    {
        RapifiedConfig.RapClass? current = cls;
        var guard = 0;
        while (current is not null && guard++ < 50)
        {
            if (current.Values.TryGetValue(propertyName, out var direct) && direct is List<object> directArr && directArr.Count > 0)
            {
                return ToSize(directArr);
            }
            if (useNestedCargo && current.Children.TryGetValue("Cargo", out var cargoClass)
                && cargoClass.Values.TryGetValue(propertyName, out var nested) && nested is List<object> nestedArr && nestedArr.Count > 0)
            {
                return ToSize(nestedArr);
            }

            if (string.IsNullOrEmpty(current.Parent)) break;
            if (!globalIndex.TryGetValue(current.Parent, out var parent)) break;
            current = parent;
        }
        return null;
    }

    private static (int Width, int Height) ToSize(List<object> arr)
    {
        var w = ToInt(arr[0]);
        var h = arr.Count > 1 ? ToInt(arr[1]) : w;
        return (w, h);
    }

    private static int ToInt(object v) => v switch
    {
        int i => i,
        float f => (int) f,
        _ => 0,
    };
}
