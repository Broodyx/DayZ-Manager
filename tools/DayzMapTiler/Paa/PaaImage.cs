namespace DayzMapTiler.Paa;

/// <summary>
/// Decodes a Bohemia PAA texture: a 2-byte format tag, a chain of "TAGG" metadata
/// blocks (skipped — none of them are needed to read pixel data), then a sequence of
/// mip levels (largest first). Only mip 0 (full resolution) is decoded; that is all a
/// map tiler needs.
/// </summary>
public sealed class PaaImage
{
    public required int Width { get; init; }
    public required int Height { get; init; }
    public required byte[] Rgba { get; init; } // width*height*4, row-major, top-to-bottom

    private static readonly byte[] TaggMarker = { (byte)'G', (byte)'G', (byte)'A', (byte)'T' };

    public static PaaImage Decode(byte[] fileBytes)
    {
        using var stream = new MemoryStream(fileBytes);
        using var reader = new BinaryReader(stream);

        var tag = reader.ReadUInt16();
        var format = tag switch
        {
            0xFF01 => PaaFormat.Dxt1,
            0xFF05 => PaaFormat.Dxt5,
            0x4444 => PaaFormat.Argb4444,
            0x1555 => PaaFormat.Argb1555,
            0x8080 => PaaFormat.Ai88,
            _ => throw new NotSupportedException($"Unrecognized PAA format tag 0x{tag:x4}."),
        };

        SkipTaggChain(reader);

        var rawWidth = reader.ReadUInt16();
        var height = reader.ReadUInt16();
        var isLzoCompressed = (rawWidth & 0x8000) != 0;
        var width = rawWidth & 0x7FFF;

        var storedLength = Read24BitLength(reader);
        var stored = reader.ReadBytes(storedLength);
        if (stored.Length != storedLength)
        {
            throw new EndOfStreamException($"Truncated PAA mip 0 data: wanted {storedLength} bytes, got {stored.Length}.");
        }

        var rawSize = RawByteSize(format, width, height);
        var raw = isLzoCompressed ? Lzo1x.Decompress(stored, rawSize) : stored;
        if (raw.Length != rawSize)
        {
            throw new InvalidDataException(
                $"PAA mip 0 raw size mismatch: expected {rawSize} bytes for {width}x{height} {format}, got {raw.Length}.");
        }

        var rgba = BlockDecoders.Decode(format, raw, width, height);
        return new PaaImage { Width = width, Height = height, Rgba = rgba };
    }

    private static void SkipTaggChain(BinaryReader reader)
    {
        while (true)
        {
            var marker = reader.ReadBytes(4);
            if (marker.Length < 4 || !marker.AsSpan().SequenceEqual(TaggMarker))
            {
                reader.BaseStream.Position -= marker.Length;
                break;
            }

            reader.ReadBytes(4); // tag name, not needed
            var length = reader.ReadUInt32();
            reader.BaseStream.Position += length;
        }

        // The tag chain (present or empty) is always followed by a 2-byte 0x0000
        // terminator before the first mip level's own width field.
        var terminator = reader.ReadUInt16();
        if (terminator != 0)
        {
            reader.BaseStream.Position -= 2;
        }
    }

    private static int Read24BitLength(BinaryReader reader)
    {
        var b0 = reader.ReadByte();
        var b1 = reader.ReadByte();
        var b2 = reader.ReadByte();
        return b0 | (b1 << 8) | (b2 << 16);
    }

    private static int RawByteSize(PaaFormat format, int width, int height) => format switch
    {
        PaaFormat.Dxt1 => ((width + 3) / 4) * ((height + 3) / 4) * 8,
        PaaFormat.Dxt5 => ((width + 3) / 4) * ((height + 3) / 4) * 16,
        PaaFormat.Argb4444 or PaaFormat.Argb1555 or PaaFormat.Ai88 => width * height * 2,
        _ => throw new NotSupportedException($"No raw size formula for {format}."),
    };
}
