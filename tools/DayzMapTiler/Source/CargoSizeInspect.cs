using DayzMapTiler.Pbo;

namespace DayzMapTiler.Source;

public static class CargoSizeInspect
{
    public static int Run(string[] args)
    {
        var path = args[0];
        var subConfig = args.Length > 1 ? args[1] : null;
        using var pbo = PboArchive.Open(path);
        var entry = subConfig is null
            ? pbo.Find("config.bin")
            : pbo.Find(subConfig);
        if (entry is null) { Console.WriteLine("not found"); return 1; }
        var data = pbo.ReadEntryData(entry);
        Console.WriteLine($"config.bin size: {data.Length} bytes");
        var rap = RapifiedConfig.Parse(data);
        Console.WriteLine("Root classes: " + string.Join(", ", rap.Root.Children.Keys));
        foreach (var rootName in new[] { "CfgVehicles", "CfgWeapons", "CfgMagazines" })
        {
            var root = rap.Root.Children.Values.FirstOrDefault(c => string.Equals(c.Name, rootName, StringComparison.OrdinalIgnoreCase));
            if (root is null) continue;
            Console.WriteLine($"{rootName}: {string.Join(", ", root.Children.Keys)}");
            foreach (var child in root.Children.Values)
            {
                if (child.Values.TryGetValue("itemsCargoSize", out var v1) && v1 is List<object> a1)
                    Console.WriteLine($"  {child.Name} itemsCargoSize=[{string.Join(",", a1)}]");
                if (child.Values.TryGetValue("itemSize", out var v2) && v2 is List<object> a2)
                    Console.WriteLine($"  {child.Name} itemSize=[{string.Join(",", a2)}]");
            }
        }
        return 0;
    }
}
