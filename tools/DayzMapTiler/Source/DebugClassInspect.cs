using DayzMapTiler.Pbo;

namespace DayzMapTiler.Source;

public static class DebugClassInspect
{
    public static int Run(string[] args)
    {
        var pboPath = args[0];
        var rootName = args[1];
        var className = args[2];

        using var pbo = PboArchive.Open(pboPath);
        var configEntry = pbo.Find("config.bin") ?? throw new FileNotFoundException("No config.bin.");
        var data = pbo.ReadEntryData(configEntry);
        var rap = RapifiedConfig.Parse(data);

        var rootClass = rap.Root.Children.Values.FirstOrDefault(c => string.Equals(c.Name, rootName, StringComparison.OrdinalIgnoreCase));
        if (rootClass is null)
        {
            Console.WriteLine($"Root '{rootName}' not found. Available: {string.Join(", ", rap.Root.Children.Keys)}");
            return 1;
        }

        var target = rootClass.Children.Values.FirstOrDefault(c => string.Equals(c.Name, className, StringComparison.OrdinalIgnoreCase));
        if (target is null)
        {
            Console.WriteLine($"Class '{className}' not found as a direct child of {rootName}. {rootClass.Children.Count} children total.");
            var similar = rootClass.Children.Keys.Where(k => k.Contains(className.Split('_')[0], StringComparison.OrdinalIgnoreCase)).Take(20);
            Console.WriteLine("Similar names: " + string.Join(", ", similar));
            return 1;
        }

        Console.WriteLine($"Found {target.Name}, parent='{target.Parent}', own value keys: {string.Join(",", target.Values.Keys)}, own children: {string.Join(",", target.Children.Keys)}");
        if (target.Values.TryGetValue("itemSize", out var itemSizeVal) && itemSizeVal is List<object> itemSizeArr)
        {
            Console.WriteLine("  itemSize[] = {" + string.Join(",", itemSizeArr) + "}");
        }
        if (target.Values.TryGetValue("itemsCargoSize", out var cargoSizeVal) && cargoSizeVal is List<object> cargoSizeArr)
        {
            Console.WriteLine("  itemsCargoSize[] = {" + string.Join(",", cargoSizeArr) + "}");
        }

        var current = target;
        var chain = new List<string> { target.Name };
        while (!string.IsNullOrEmpty(current.Parent))
        {
            var parent = rootClass.Children.Values.FirstOrDefault(c => string.Equals(c.Name, current.Parent, StringComparison.OrdinalIgnoreCase));
            if (parent is null)
            {
                Console.WriteLine($"Parent '{current.Parent}' of '{current.Name}' NOT FOUND within {rootName} of this PBO (cross-file inheritance, or missing).");
                break;
            }
            chain.Add(parent.Name);
            Console.WriteLine($"  parent '{parent.Name}': value keys={string.Join(",", parent.Values.Keys)}, children={string.Join(",", parent.Children.Keys)}, itself parent='{parent.Parent}'");
            current = parent;
        }
        Console.WriteLine("Chain: " + string.Join(" -> ", chain));

        return 0;
    }
}
