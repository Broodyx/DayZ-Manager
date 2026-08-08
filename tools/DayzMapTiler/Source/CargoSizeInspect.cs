using DayzMapTiler.Pbo;

namespace DayzMapTiler.Source;

public static class CargoSizeInspect
{
    public static int Run(string[] args)
    {
        var path = args[0];
        using var pbo = PboArchive.Open(path);
        var configEntry = pbo.Find("config.bin") ?? throw new FileNotFoundException("No config.bin in this PBO.");
        var data = pbo.ReadEntryData(configEntry);
        Console.WriteLine($"config.bin size: {data.Length} bytes");

        var rap = RapifiedConfig.Parse(data);
        var relevantWarnings = rap.Warnings.Where(w => !w.Contains("'Health'")).Distinct().ToList();
        Console.WriteLine($"Warnings during parse: {rap.Warnings.Count} ({relevantWarnings.Count} outside the unrelated Health.healthLevels nested-array property)");
        foreach (var w in relevantWarnings) Console.WriteLine("  WARN: " + w);

        Console.WriteLine("Root-level classes: " + string.Join(", ", rap.Root.Children.Keys));

        var cfgVehicles = FindByName(rap.Root, "CfgVehicles");
        if (cfgVehicles is null)
        {
            Console.WriteLine("No CfgVehicles class found — cannot proceed.");
            return 1;
        }
        Console.WriteLine($"CfgVehicles: {cfgVehicles.Children.Count} direct child classes.");

        // Sanity check: displayName strings should read as plausible English item names —
        // if the binary parse were wrong these would come out as garbage/non-printable.
        Console.WriteLine("\nSample displayName decode sanity check:");
        foreach (var n in new[] { "Barrel_Green", "SeaChest", "WoodenCrate", "AmmoBox" })
        {
            var cls = cfgVehicles.Children.GetValueOrDefault(n);
            var resolved = cls is null ? null : ResolveInherited(cfgVehicles, cls, "displayName");
            Console.WriteLine($"  {n}: displayName={(resolved ?? "<none found in chain>")}");
        }

        // Resolve, for every direct CfgVehicles child, its effective cargo grid — either a
        // directly-assigned itemsCargoSize[] or one on a nested "Cargo" class — walking up
        // the name-based (not nesting-based) parent chain when the class itself has neither.
        var results = new List<(string Name, string? DisplayName, List<int> Size, string ResolvedFrom, List<string> Chain)>();
        foreach (var (name, cls) in cfgVehicles.Children)
        {
            var (size, resolvedFrom, chain) = ResolveCargoSize(cfgVehicles, cls);
            if (size is null) continue;
            var displayName = ResolveInherited(cfgVehicles, cls, "displayName") as string;
            results.Add((name, displayName, size, resolvedFrom, chain));
        }

        Console.WriteLine($"\nContainers with a resolved cargo grid: {results.Count}\n");
        Console.WriteLine($"{"Classname",-30} {"DisplayName",-22} {"Grid",-8} {"Slots",-7} Resolved from (parent chain)");
        foreach (var r in results.OrderByDescending(r => r.Size[0] * r.Size[1]))
        {
            var grid = $"{r.Size[0]}x{r.Size[1]}";
            var slots = r.Size[0] * r.Size[1];
            Console.WriteLine($"{r.Name,-30} {(r.DisplayName ?? ""),-22} {grid,-8} {slots,-7} {r.ResolvedFrom} [{string.Join(" -> ", r.Chain)}]");
        }

        return 0;
    }

    private static (List<int>? Size, string ResolvedFrom, List<string> Chain) ResolveCargoSize(RapifiedConfig.RapClass scope, RapifiedConfig.RapClass cls)
    {
        var chain = new List<string> { string.IsNullOrEmpty(cls.Name) ? "(root)" : cls.Name };
        RapifiedConfig.RapClass? current = cls;
        var guard = 0;
        while (current is not null && guard++ < 50)
        {
            if (current.Values.TryGetValue("itemsCargoSize", out var direct) && direct is List<object> directArr)
            {
                return (directArr.Select(ToInt).ToList(), current == cls ? "itself (direct)" : $"inherited from {current.Name}", chain);
            }
            if (current.Children.TryGetValue("Cargo", out var cargoClass) && cargoClass.Values.TryGetValue("itemsCargoSize", out var nested) && nested is List<object> nestedArr)
            {
                return (nestedArr.Select(ToInt).ToList(), current == cls ? "itself (Cargo class)" : $"inherited from {current.Name}'s Cargo class", chain);
            }

            if (string.IsNullOrEmpty(current.Parent)) break;
            if (!scope.Children.TryGetValue(current.Parent, out var parent)) break;
            chain.Add(parent.Name);
            current = parent;
        }
        return (null, "", chain);
    }

    private static object? ResolveInherited(RapifiedConfig.RapClass scope, RapifiedConfig.RapClass cls, string key)
    {
        RapifiedConfig.RapClass? current = cls;
        var guard = 0;
        while (current is not null && guard++ < 50)
        {
            if (current.Values.TryGetValue(key, out var v)) return v;
            if (string.IsNullOrEmpty(current.Parent)) return null;
            if (!scope.Children.TryGetValue(current.Parent, out var parent)) return null;
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

    private static RapifiedConfig.RapClass? FindByName(RapifiedConfig.RapClass cls, string name)
    {
        if (string.Equals(cls.Name, name, StringComparison.OrdinalIgnoreCase)) return cls;
        foreach (var child in cls.Children.Values)
        {
            var found = FindByName(child, name);
            if (found is not null) return found;
        }
        return null;
    }
}
