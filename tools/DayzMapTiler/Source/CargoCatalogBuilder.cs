using System.Text.Json;
using DayzMapTiler.Pbo;

namespace DayzMapTiler.Source;

/// <summary>
/// Scans every PBO in a DayZ install's Addons folder for classes carrying itemsCargoSize[]
/// (item/container inventory footprint), resolving through name-based class inheritance
/// within each file, and writes one merged classname -> {width,height,...} JSON catalog.
/// This is real data extracted from the game's own binarized configs — not a guess.
/// </summary>
public static class CargoCatalogBuilder
{
    public sealed class CatalogEntry
    {
        public required string Classname { get; init; }
        public required int Width { get; init; }
        public required int Height { get; init; }
        public int Slots => Width * Height;
        public string? DisplayName { get; init; }
        public required string SourcePbo { get; init; }
        public required string RootConfig { get; init; }
        public required string ResolvedFrom { get; init; }
    }

    private static readonly string[] RootConfigNames = { "CfgVehicles", "CfgWeapons", "CfgMagazines" };

    public static int Run(string[] args)
    {
        var addonsDir = args[0];
        var outputPath = args[1];

        var pboFiles = Directory.GetFiles(addonsDir, "*.pbo").OrderBy(f => f).ToList();
        Console.WriteLine($"Found {pboFiles.Count} PBO files in {addonsDir}");

        var all = new List<CatalogEntry>();
        var byClassname = new Dictionary<string, CatalogEntry>(StringComparer.OrdinalIgnoreCase);
        var duplicates = new List<string>();
        var failed = new List<(string File, string Reason)>();
        var totalParseWarnings = 0;
        var totalNonHealthWarnings = 0;

        foreach (var pboPath in pboFiles)
        {
            var fileName = Path.GetFileName(pboPath);
            try
            {
                using var pbo = PboArchive.Open(pboPath);
                var configEntry = pbo.Find("config.bin");
                if (configEntry is null) continue;

                var data = pbo.ReadEntryData(configEntry);
                RapifiedConfig rap;
                try
                {
                    rap = RapifiedConfig.Parse(data);
                }
                catch (Exception ex)
                {
                    failed.Add((fileName, $"parse error: {ex.Message}"));
                    continue;
                }

                totalParseWarnings += rap.Warnings.Count;
                var nonHealth = rap.Warnings.Where(w => !w.Contains("'Health'")).ToList();
                totalNonHealthWarnings += nonHealth.Count;
                if (nonHealth.Count > 0)
                {
                    Console.WriteLine($"  {fileName}: {nonHealth.Count} non-Health warnings, e.g. \"{nonHealth[0]}\"");
                }

                var globalIndex = new Dictionary<string, RapifiedConfig.RapClass>(StringComparer.OrdinalIgnoreCase);
                IndexAll(rap.Root, globalIndex);

                foreach (var rootName in RootConfigNames)
                {
                    var rootClass = rap.Root.Children.Values.FirstOrDefault(c => string.Equals(c.Name, rootName, StringComparison.OrdinalIgnoreCase));
                    if (rootClass is null) continue;

                    // Only direct children of the root config are real classnames (weapons,
                    // items, containers, ammo). Deeper nesting (Cargo, Health, DamageSystem,
                    // ...) is structural sub-config, not something that ever spawns/loots.
                    foreach (var cls in rootClass.Children.Values)
                    {
                        var (size, resolvedFrom) = ResolveCargoSize(globalIndex, cls);
                        if (size is null) continue;

                        var displayName = ResolveInherited(globalIndex, cls, "displayName") as string;
                        var entry = new CatalogEntry
                        {
                            Classname = cls.Name,
                            Width = size[0],
                            Height = size.Count > 1 ? size[1] : size[0],
                            DisplayName = displayName,
                            SourcePbo = fileName,
                            RootConfig = rootName,
                            ResolvedFrom = resolvedFrom,
                        };

                        if (byClassname.TryGetValue(cls.Name, out var existing))
                        {
                            if (existing.Width == entry.Width && existing.Height == entry.Height)
                            {
                                continue; // identical duplicate (class re-declared/extended across files) — not a conflict
                            }
                            duplicates.Add($"{cls.Name}: {existing.Width}x{existing.Height} ({existing.SourcePbo}) vs {entry.Width}x{entry.Height} ({entry.SourcePbo})");
                            continue; // keep first-seen on genuine conflict, flagged for manual review
                        }

                        byClassname[cls.Name] = entry;
                        all.Add(entry);
                    }
                }
            }
            catch (Exception ex)
            {
                failed.Add((fileName, ex.Message));
            }
        }

        Console.WriteLine($"\nTotal classes with a resolved itemsCargoSize: {all.Count}");
        Console.WriteLine($"Parse warnings across all files: {totalParseWarnings} ({totalNonHealthWarnings} outside the known-benign Health.healthLevels case)");
        Console.WriteLine($"Files that failed to open/parse: {failed.Count}");
        foreach (var (file, reason) in failed) Console.WriteLine($"  FAILED {file}: {reason}");
        Console.WriteLine($"Classname conflicts across files (kept first-seen, needs review): {duplicates.Count}");
        foreach (var d in duplicates.Take(30)) Console.WriteLine($"  CONFLICT {d}");

        var json = JsonSerializer.Serialize(
            all.OrderBy(e => e.Classname, StringComparer.OrdinalIgnoreCase).Select(e => new
            {
                classname = e.Classname,
                width = e.Width,
                height = e.Height,
                slots = e.Slots,
                display_name = e.DisplayName,
                source_pbo = e.SourcePbo,
                root_config = e.RootConfig,
                resolved_from = e.ResolvedFrom,
            }),
            new JsonSerializerOptions { WriteIndented = true });
        File.WriteAllText(outputPath, json);
        Console.WriteLine($"\nWrote {all.Count} entries to {outputPath}");

        return 0;
    }

    private static void IndexAll(RapifiedConfig.RapClass cls, Dictionary<string, RapifiedConfig.RapClass> into)
    {
        if (!string.IsNullOrEmpty(cls.Name) && !into.ContainsKey(cls.Name))
        {
            into[cls.Name] = cls;
        }
        foreach (var child in cls.Children.Values)
        {
            IndexAll(child, into);
        }
    }

    private static (List<int>? Size, string ResolvedFrom) ResolveCargoSize(Dictionary<string, RapifiedConfig.RapClass> globalIndex, RapifiedConfig.RapClass cls)
    {
        RapifiedConfig.RapClass? current = cls;
        var guard = 0;
        while (current is not null && guard++ < 50)
        {
            if (current.Values.TryGetValue("itemsCargoSize", out var direct) && direct is List<object> directArr && directArr.Count > 0)
            {
                return (directArr.Select(ToInt).ToList(), current == cls ? "itself" : $"inherited from {current.Name}");
            }
            if (current.Children.TryGetValue("Cargo", out var cargoClass) && cargoClass.Values.TryGetValue("itemsCargoSize", out var nested) && nested is List<object> nestedArr && nestedArr.Count > 0)
            {
                return (nestedArr.Select(ToInt).ToList(), current == cls ? "itself (Cargo class)" : $"inherited from {current.Name}'s Cargo class");
            }

            if (string.IsNullOrEmpty(current.Parent)) break;
            if (!globalIndex.TryGetValue(current.Parent, out var parent)) break;
            current = parent;
        }
        return (null, "");
    }

    private static object? ResolveInherited(Dictionary<string, RapifiedConfig.RapClass> globalIndex, RapifiedConfig.RapClass cls, string key)
    {
        RapifiedConfig.RapClass? current = cls;
        var guard = 0;
        while (current is not null && guard++ < 50)
        {
            if (current.Values.TryGetValue(key, out var v)) return v;
            if (string.IsNullOrEmpty(current.Parent)) return null;
            if (!globalIndex.TryGetValue(current.Parent, out var parent)) return null;
            current = parent;
        }
        return null;
    }

    private static int ToInt(object v) => v switch
    {
        int i => i,
        float f => (int) f,
        _ => 0,
    };
}
