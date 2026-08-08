using System.Text.Json;
using System.Text.RegularExpressions;
using DayzMapTiler.Pbo;

namespace DayzMapTiler.Source;

/// <summary>
/// Scans every PBO in a DayZ install's Addons folder (same source set as CargoCatalogBuilder)
/// for placeable CfgVehicles entries — buildings, static props, containers, decorations,
/// vehicles — and extracts what config.bin actually carries per class: classname, resolved
/// model path, displayName (when declared), immediate parent, and source PBO. Category is NOT
/// a real DayZ config property (the engine has no "placement category" field) — it's inferred
/// here from classname prefixes and the resolved ancestor chain, so it's a best-effort first
/// pass, not authoritative. Model bounding box and a preview image would require parsing the
/// referenced .p3d files, which nothing in this tool (or its dependencies) does — those fields
/// are intentionally left for a future pass, not guessed here.
/// </summary>
public static class ObjectCatalogBuilder
{
    public sealed class CatalogEntry
    {
        public required string Classname { get; init; }
        public string? DisplayName { get; init; }
        public required string Category { get; init; }
        public string? Model { get; init; }
        public required string ParentClass { get; init; }
        public required string SourcePbo { get; init; }
        public required List<string> Tags { get; init; }
    }

    private const string RootConfigName = "CfgVehicles";
    private static readonly Regex ConfigBinPattern = new(@"(^|/)config\.bin$", RegexOptions.IgnoreCase | RegexOptions.Compiled);

    // Ancestor names that mean "this is not a placeable object" (players, infected, animals,
    // AI/controller scaffolding) — anything whose resolved ancestor chain hits one of these is
    // skipped regardless of how deep the inheritance goes.
    private static readonly string[] ExcludedAncestors =
    {
        "Man", "PlayerBase", "DayZPlayer", "DayZPlayerImplement",
        "ZombieBase", "DayZInfected", "DayZZombieBase", "ZCommon",
        "AnimalBase", "DayZAnimalBase", "DayZCreatureAI", "DZ_LightAndShadow",
        "ParticleSourceScripted", "EffectSound", "EffectParticle", "ThingEffect",
        "Head_Default", "HeadWithVoice",
    };

    // Ordered classname-prefix rules for category inference — first match wins. Checked against
    // the classname itself; falls back to ancestor-chain checks (see ClassifyByAncestors) when
    // no prefix matches.
    private static readonly (string Prefix, string Category)[] PrefixRules =
    {
        ("Land_Mil_", "Military"), ("Land_Camp", "Military"), ("Land_Watchtower", "Military"),
        ("Land_Barracks", "Military"), ("Land_Bunker", "Military"), ("Land_Fort_", "Military"),
        ("Land_Ind_", "Industrial"),
        ("Land_Res_", "Residential"), ("Land_House", "Residential"), ("Land_Church", "Residential"), ("Land_Chapel", "Residential"),
        ("Land_Ruins", "Castle"), ("Castle_", "Castle"), ("Land_Castle", "Castle"),
        ("Land_Bridge", "Bridges"),
        ("Land_Wall", "Walls"), ("CityWall", "Walls"), ("Land_Fence", "Walls"),
        ("Land_Road", "Roads"), ("Land_Pavement", "Roads"), ("Land_Rail", "Roads"), ("Land_Vodnik", "Roads"),
        ("Land_Deco", "Decorations"), ("Land_Misc_Deco", "Decorations"),
    };

    private static readonly (string Needle, string Category)[] ClassnameContainsRules =
    {
        ("Tree", "Nature"), ("Bush", "Nature"), ("Rock", "Nature"), ("Stone", "Nature"), ("Plant", "Nature"),
    };

    private static readonly string[] VehicleAncestors = { "Car", "CarScript", "Ship", "BoatScript", "Boat_Base", "Heli_Base", "Helicopter", "ItemHelicopter", "ItemShip", "NonAIVehicleScript" };
    private static readonly string[] BuildingAncestors = { "House", "HouseNoDestruct" };
    private static readonly string[] NatureAncestors = { "PlantBase", "BushBase", "TreeBase" };

    // DayZ's engine defines literally everything under CfgVehicles, including hand-held/wearable
    // items (weapons, clothing, attachments, food, tools) — those all derive from Inventory_Base
    // (or one of its well-known direct branches) just like real world-placeable containers (a
    // barrel and a jacket share this ancestor). Only the subset that's ALSO a known container
    // (has its own itemsCargoSize, cross-referenced against the cargo-size fixture) belongs in
    // an "objects to place in the world" catalog — everything else under one of these is
    // loot-economy content already covered by ClassnameCatalog/CargoSizeCatalog, not something
    // Object Spawner is for.
    private static readonly string[] ItemAncestors =
    {
        "Inventory_Base", "Clothing", "Clothing_Base", "Weapon_Base", "Weapon_Base_Attachment",
        "Ammunition_Base", "Container_Base", "Magazine_Base", "Edible_Base", "ItemOptics",
    };

    private sealed record ParsedConfig(string FileName, string InternalPath, RapifiedConfig Rap);

    public static int Run(string[] args)
    {
        var addonsDir = args[0];
        var outputPath = args[1];

        var pboFiles = Directory.GetFiles(addonsDir, "*.pbo").OrderBy(f => f).ToList();
        Console.WriteLine($"Found {pboFiles.Count} PBO files in {addonsDir}");

        var parsed = new List<ParsedConfig>();
        var globalIndex = new Dictionary<string, RapifiedConfig.RapClass>(StringComparer.OrdinalIgnoreCase);
        var failed = new List<(string File, string Reason)>();
        var totalParseWarnings = 0;

        foreach (var pboPath in pboFiles)
        {
            var fileName = Path.GetFileName(pboPath);
            try
            {
                using var pbo = PboArchive.Open(pboPath);
                foreach (var configEntry in pbo.FindAllMatching(ConfigBinPattern))
                {
                    RapifiedConfig rap;
                    try
                    {
                        var data = pbo.ReadEntryData(configEntry);
                        rap = RapifiedConfig.Parse(data);
                    }
                    catch (Exception ex)
                    {
                        failed.Add(($"{fileName}:{configEntry.Name}", $"parse error: {ex.Message}"));
                        continue;
                    }

                    totalParseWarnings += rap.Warnings.Count;
                    parsed.Add(new ParsedConfig(fileName, configEntry.Name, rap));
                    IndexAll(rap.Root, globalIndex);
                }
            }
            catch (Exception ex)
            {
                failed.Add((fileName, ex.Message));
            }
        }

        Console.WriteLine($"Parsed {parsed.Count} config.bin files across {pboFiles.Count} PBOs.");
        Console.WriteLine($"Global classname index: {globalIndex.Count} unique names.");

        // Pre-load the cargo-size fixture's capacity-bearing classnames so containers (tents,
        // crates, chests — anything with its own itemsCargoSize) get the "Containers" category
        // even though their classnames don't follow a predictable naming prefix.
        var containerClassnames = LoadContainerClassnames();
        Console.WriteLine($"Loaded {containerClassnames.Count} known container classnames from dayz-cargo-sizes.json for category cross-reference.");

        var byClassname = new Dictionary<string, CatalogEntry>(StringComparer.OrdinalIgnoreCase);
        var all = new List<CatalogEntry>();
        var skippedExcludedAncestor = 0;
        var skippedInventoryItem = 0;
        var skippedNoModel = 0;
        var categoryCounts = new Dictionary<string, int>(StringComparer.OrdinalIgnoreCase);

        foreach (var pc in parsed)
        {
            var rootClass = pc.Rap.Root.Children.Values.FirstOrDefault(c => string.Equals(c.Name, RootConfigName, StringComparison.OrdinalIgnoreCase));
            if (rootClass is null) continue;

            foreach (var cls in rootClass.Children.Values)
            {
                var ancestors = ResolveAncestorChain(globalIndex, cls);
                if (ancestors.Any(a => ExcludedAncestors.Contains(a, StringComparer.OrdinalIgnoreCase)))
                {
                    skippedExcludedAncestor++;
                    continue;
                }
                if (ancestors.Any(a => ItemAncestors.Contains(a, StringComparer.OrdinalIgnoreCase)) && !containerClassnames.Contains(cls.Name))
                {
                    skippedInventoryItem++;
                    continue;
                }

                var model = ResolveStringProperty(globalIndex, cls, "model");
                if (string.IsNullOrWhiteSpace(model))
                {
                    skippedNoModel++;
                    continue;
                }

                // Direct-only (no parent-chain walk): most structures never override displayName
                // and would otherwise all inherit the same generic literal from their base class
                // (e.g. plain "House" on ~half of all buildings) — indistinguishable and worse
                // than no name at all. A directly-declared value is a real per-class name; still
                // often a raw, unresolved "$STR_..." stringtable key (no stringtable parser
                // exists here), which callers should treat as absent, not human-readable.
                var displayName = cls.Values.TryGetValue("displayName", out var dn) && dn is string dnStr && dnStr.Trim().Length > 0 ? dnStr.Trim() : null;
                var category = Classify(cls.Name, ancestors, containerClassnames);
                var tags = BuildTags(cls.Name, ancestors, category);

                var entry = new CatalogEntry
                {
                    Classname = cls.Name,
                    DisplayName = string.IsNullOrWhiteSpace(displayName) ? null : displayName,
                    Category = category,
                    Model = model,
                    ParentClass = cls.Parent,
                    SourcePbo = pc.FileName,
                    Tags = tags,
                };

                if (byClassname.ContainsKey(cls.Name)) continue; // first-seen kept, same as CargoCatalogBuilder

                byClassname[cls.Name] = entry;
                all.Add(entry);
                categoryCounts[category] = categoryCounts.GetValueOrDefault(category) + 1;
            }
        }

        Console.WriteLine($"\nTotal placeable objects catalogued: {all.Count}");
        Console.WriteLine($"Skipped (excluded ancestor — player/infected/animal/effect): {skippedExcludedAncestor}");
        Console.WriteLine($"Skipped (Inventory_Base item, not a known container — loot economy content, not a world object): {skippedInventoryItem}");
        Console.WriteLine($"Skipped (no resolvable model): {skippedNoModel}");
        Console.WriteLine($"Parse warnings across all files: {totalParseWarnings}");
        Console.WriteLine($"Sub-configs that failed to open/parse: {failed.Count}");
        foreach (var (file, reason) in failed) Console.WriteLine($"  FAILED {file}: {reason}");
        Console.WriteLine("\nBy category:");
        foreach (var (category, count) in categoryCounts.OrderByDescending(kv => kv.Value))
        {
            Console.WriteLine($"  {category}: {count}");
        }

        var json = JsonSerializer.Serialize(
            all.OrderBy(e => e.Classname, StringComparer.OrdinalIgnoreCase).Select(e => new
            {
                classname = e.Classname,
                display_name = e.DisplayName,
                category = e.Category,
                model = e.Model,
                parent_class = e.ParentClass,
                source_pbo = e.SourcePbo,
                tags = e.Tags,
            }),
            new JsonSerializerOptions { WriteIndented = true });
        File.WriteAllText(outputPath, json);
        Console.WriteLine($"\nWrote {all.Count} entries to {outputPath}");

        return 0;
    }

    private static HashSet<string> LoadContainerClassnames()
    {
        // Reads the already-built cargo-size fixture rather than re-deriving itemsCargoSize —
        // avoids duplicating CargoCatalogBuilder's resolution logic for the same underlying data.
        var candidates = new[]
        {
            Path.Combine(AppContext.BaseDirectory, "..", "..", "..", "..", "..", "database", "seeders", "fixtures", "dayz-cargo-sizes.json"),
            Path.Combine(Directory.GetCurrentDirectory(), "database", "seeders", "fixtures", "dayz-cargo-sizes.json"),
        };
        var path = candidates.FirstOrDefault(File.Exists);
        if (path is null) return new HashSet<string>(StringComparer.OrdinalIgnoreCase);

        using var doc = JsonDocument.Parse(File.ReadAllText(path));
        var result = new HashSet<string>(StringComparer.OrdinalIgnoreCase);
        foreach (var item in doc.RootElement.EnumerateArray())
        {
            if (item.TryGetProperty("cw", out _) && item.TryGetProperty("classname", out var name))
            {
                result.Add(name.GetString() ?? "");
            }
        }
        return result;
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

    /// <summary>
    /// Walks the Parent chain and returns every ancestor NAME reached (not including cls
    /// itself), root-most last, cut off by the 50-hop guard. Unlike a naive walk, the final
    /// unresolved parent name is still recorded even when its RapClass body can't be found in
    /// the global index — engine-level base classes (Clothing, Weapon_Base, Inventory_Base...)
    /// are sometimes only declared via a bare "class Foo: Bar;" forward reference in the addon
    /// set actually scanned, with their real body living in a core PBO that isn't part of it, so
    /// requiring a resolved body to even see the name would silently truncate chains right where
    /// the useful classification signal is (e.g. "this is Clothing" is exactly what we need to
    /// know to exclude it, even if we can't walk any further above it).
    /// </summary>
    private static List<string> ResolveAncestorChain(Dictionary<string, RapifiedConfig.RapClass> globalIndex, RapifiedConfig.RapClass cls)
    {
        var chain = new List<string>();
        var current = cls;
        var guard = 0;
        while (guard++ < 50 && !string.IsNullOrEmpty(current.Parent))
        {
            chain.Add(current.Parent);
            if (!globalIndex.TryGetValue(current.Parent, out var parent))
            {
                break;
            }
            current = parent;
        }
        return chain;
    }

    private static string? ResolveStringProperty(Dictionary<string, RapifiedConfig.RapClass> globalIndex, RapifiedConfig.RapClass cls, string propertyName)
    {
        RapifiedConfig.RapClass? current = cls;
        var guard = 0;
        while (current is not null && guard++ < 50)
        {
            if (current.Values.TryGetValue(propertyName, out var value) && value is string s && s.Trim().Length > 0)
            {
                return s.Trim();
            }
            if (string.IsNullOrEmpty(current.Parent) || !globalIndex.TryGetValue(current.Parent, out var parent))
            {
                break;
            }
            current = parent;
        }
        return null;
    }

    private static string Classify(string classname, List<string> ancestors, HashSet<string> containerClassnames)
    {
        if (containerClassnames.Contains(classname)) return "Containers";

        foreach (var (prefix, category) in PrefixRules)
        {
            if (classname.StartsWith(prefix, StringComparison.OrdinalIgnoreCase)) return category;
        }
        foreach (var (needle, category) in ClassnameContainsRules)
        {
            if (classname.Contains(needle, StringComparison.OrdinalIgnoreCase)) return category;
        }
        if (ancestors.Any(a => VehicleAncestors.Contains(a, StringComparer.OrdinalIgnoreCase))) return "Vehicles";
        if (ancestors.Any(a => NatureAncestors.Contains(a, StringComparer.OrdinalIgnoreCase))) return "Nature";
        if (ancestors.Any(a => BuildingAncestors.Contains(a, StringComparer.OrdinalIgnoreCase))) return "Buildings";

        return "Misc";
    }

    private static List<string> BuildTags(string classname, List<string> ancestors, string category)
    {
        var tags = new List<string> { category };
        // A handful of coarse, genuinely useful search tags — not an attempt at a full taxonomy.
        if (classname.Contains("Wood", StringComparison.OrdinalIgnoreCase)) tags.Add("wood");
        if (classname.Contains("Metal", StringComparison.OrdinalIgnoreCase) || classname.Contains("Concrete", StringComparison.OrdinalIgnoreCase)) tags.Add("concrete");
        if (ancestors.Contains("House", StringComparer.OrdinalIgnoreCase)) tags.Add("building");
        return tags.Distinct(StringComparer.OrdinalIgnoreCase).ToList();
    }
}
