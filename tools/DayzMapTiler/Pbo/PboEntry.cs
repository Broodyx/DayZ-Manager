namespace DayzMapTiler.Pbo;

/// <summary>One file entry from a PBO's header (name + packing/size metadata), plus where its data starts in the archive.</summary>
public sealed class PboEntry
{
    public required string Name { get; init; }
    public required uint PackingMethod { get; init; }
    public required uint OriginalSize { get; init; }
    public required uint Timestamp { get; init; }
    public required uint DataSize { get; init; }
    public required long DataOffset { get; init; }

    /// <summary>PBO packing method 0 — the only mode official BI-shipped content actually uses for file data.</summary>
    public bool IsUncompressed => PackingMethod == 0;
}
