using DayzMapTiler.Pbo;

namespace DayzMapTiler.Source;

/// <summary>Finds which PBO(s) + root config define a given classname as a direct child, and dumps its own properties.</summary>
public static class SearchClassInspect
{
    public static int Run(string[] args)
    {
        var addonsDir = args[0];
        var className = args[1];
        var partial = args.Length > 2 && args[2] == "--partial";
        var rootNames = new[] { "CfgVehicles", "CfgWeapons", "CfgMagazines" };

        foreach (var pboPath in Directory.GetFiles(addonsDir, "*.pbo").OrderBy(f => f))
        {
            try
            {
                using var pbo = PboArchive.Open(pboPath);
                var configEntry = pbo.Find("config.bin");
                if (configEntry is null) continue;
                var data = pbo.ReadEntryData(configEntry);
                RapifiedConfig rap;
                try { rap = RapifiedConfig.Parse(data); } catch { continue; }

                foreach (var rootName in rootNames)
                {
                    var rootClass = rap.Root.Children.Values.FirstOrDefault(c => string.Equals(c.Name, rootName, StringComparison.OrdinalIgnoreCase));
                    if (rootClass is null) continue;
                    if (partial)
                    {
                        var matches = rootClass.Children.Keys.Where(k => k.Contains(className, StringComparison.OrdinalIgnoreCase)).ToList();
                        if (matches.Count > 0) Console.WriteLine($"{Path.GetFileName(pboPath)} :: {rootName} :: {string.Join(", ", matches)}");
                        continue;
                    }
                    var target = rootClass.Children.Values.FirstOrDefault(c => string.Equals(c.Name, className, StringComparison.OrdinalIgnoreCase));
                    if (target is null) continue;

                    Console.WriteLine($"{Path.GetFileName(pboPath)} :: {rootName} :: {target.Name} (parent={target.Parent})");
                    Console.WriteLine("  values: " + string.Join(",", target.Values.Keys));
                    Console.WriteLine("  children: " + string.Join(",", target.Children.Keys));
                    foreach (var key in new[] { "itemSize", "itemsCargoSize" })
                    {
                        if (target.Values.TryGetValue(key, out var v) && v is List<object> arr)
                        {
                            Console.WriteLine($"  {key}[] = {{{string.Join(",", arr)}}}");
                        }
                    }
                }
            }
            catch
            {
                // ignore unreadable pbo for this search
            }
        }

        return 0;
    }
}
