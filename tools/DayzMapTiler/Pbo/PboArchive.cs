using System.Text;

namespace DayzMapTiler.Pbo;

/// <summary>
/// Reads a Bohemia Interactive PBO archive: a header listing every entry (name +
/// packing method / original size / reserved / timestamp / data size), terminated by
/// an all-zero boundary entry, followed by the entries' raw data laid back-to-back in
/// the same order. The very first header entry is conventionally a "Vers" marker
/// (packing method 0x56657273, spelled "sreV" when read as 4 LE bytes) carrying a
/// product/prefix/version property list instead of real file data — not a real file.
///
/// Official BI-shipped content only ever uses packing method 0 (uncompressed) for real
/// file entries; a nonzero method there is treated as unsupported rather than guessed at.
/// </summary>
public sealed class PboArchive : IDisposable
{
    private const uint VersionMarker = 0x56657273; // "sreV" read as a little-endian uint32

    private readonly FileStream _stream;

    public IReadOnlyList<PboEntry> Entries { get; }
    public IReadOnlyDictionary<string, string> Properties { get; }
    public string Path { get; }

    private PboArchive(string path, FileStream stream, List<PboEntry> entries, Dictionary<string, string> properties)
    {
        Path = path;
        _stream = stream;
        Entries = entries;
        Properties = properties;
    }

    public static PboArchive Open(string path)
    {
        var stream = new FileStream(path, FileMode.Open, FileAccess.Read, FileShare.Read);
        try
        {
            var reader = new BinaryReader(stream, Encoding.UTF8, leaveOpen: true);
            var properties = new Dictionary<string, string>();
            var header = new List<(string Name, uint Packing, uint Original, uint Reserved, uint Timestamp, uint DataSize)>();

            var first = true;
            while (true)
            {
                var name = ReadCString(reader);
                var packing = reader.ReadUInt32();
                var original = reader.ReadUInt32();
                var reserved = reader.ReadUInt32();
                var timestamp = reader.ReadUInt32();
                var dataSize = reader.ReadUInt32();

                if (first && packing == VersionMarker)
                {
                    // Property list: key/value C-string pairs, terminated by an empty key.
                    while (true)
                    {
                        var key = ReadCString(reader);
                        if (key.Length == 0)
                        {
                            break;
                        }
                        var value = ReadCString(reader);
                        properties[key] = value;
                    }
                    first = false;
                    continue;
                }
                first = false;

                var isBoundary = name.Length == 0 && packing == 0 && original == 0 && dataSize == 0;
                if (isBoundary)
                {
                    break;
                }

                header.Add((name, packing, original, reserved, timestamp, dataSize));
            }

            var dataSectionStart = stream.Position;
            var entries = new List<PboEntry>(header.Count);
            var offset = dataSectionStart;
            foreach (var e in header)
            {
                entries.Add(new PboEntry
                {
                    Name = e.Name,
                    PackingMethod = e.Packing,
                    OriginalSize = e.Original,
                    Timestamp = e.Timestamp,
                    DataSize = e.DataSize,
                    DataOffset = offset,
                });
                offset += e.DataSize;
            }

            return new PboArchive(path, stream, entries, properties);
        }
        catch
        {
            stream.Dispose();
            throw;
        }
    }

    /// <summary>Finds an entry by its PBO-internal path (backslash or forward-slash, case-insensitive).</summary>
    public PboEntry? Find(string internalPath)
    {
        var normalized = Normalize(internalPath);
        return Entries.FirstOrDefault(e => Normalize(e.Name) == normalized);
    }

    public IEnumerable<PboEntry> FindAllMatching(System.Text.RegularExpressions.Regex pattern)
        => Entries.Where(e => pattern.IsMatch(Normalize(e.Name)));

    public byte[] ReadEntryData(PboEntry entry)
    {
        if (!entry.IsUncompressed)
        {
            throw new NotSupportedException(
                $"Entry '{entry.Name}' uses PBO packing method 0x{entry.PackingMethod:x8}, which this reader does not " +
                "implement (official BI-shipped content only ever stores files uncompressed).");
        }

        var buffer = new byte[entry.DataSize];
        _stream.Seek(entry.DataOffset, SeekOrigin.Begin);
        var read = _stream.Read(buffer, 0, buffer.Length);
        if (read != buffer.Length)
        {
            throw new EndOfStreamException($"Expected {buffer.Length} bytes for entry '{entry.Name}', got {read}.");
        }

        return buffer;
    }

    /// <summary>Extracts every entry (or every entry matching <paramref name="filter"/>) to <paramref name="destinationDir"/>, preserving the internal path.</summary>
    public void ExtractAll(string destinationDir, Func<PboEntry, bool>? filter = null)
    {
        foreach (var entry in Entries)
        {
            if (filter is not null && !filter(entry))
            {
                continue;
            }

            var relative = Normalize(entry.Name).Replace('/', System.IO.Path.DirectorySeparatorChar);
            var destination = System.IO.Path.Combine(destinationDir, relative);
            Directory.CreateDirectory(System.IO.Path.GetDirectoryName(destination)!);
            File.WriteAllBytes(destination, ReadEntryData(entry));
        }
    }

    private static string Normalize(string internalPath) => internalPath.Replace('\\', '/').ToLowerInvariant();

    private static string ReadCString(BinaryReader reader)
    {
        var bytes = new List<byte>();
        while (true)
        {
            var b = reader.ReadByte();
            if (b == 0)
            {
                break;
            }
            bytes.Add(b);
        }

        return Encoding.UTF8.GetString(bytes.ToArray());
    }

    public void Dispose() => _stream.Dispose();
}
