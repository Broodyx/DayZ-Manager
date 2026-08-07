namespace DayzMapTiler.Paa;

/// <summary>Decodes a PAA mip level's raw pixel bytes (already LZO-decompressed if needed) into RGBA32.</summary>
public static class BlockDecoders
{
    public static byte[] Decode(PaaFormat format, byte[] data, int width, int height)
    {
        return format switch
        {
            PaaFormat.Dxt1 => DecodeDxt(data, width, height, isDxt5: false),
            PaaFormat.Dxt5 => DecodeDxt(data, width, height, isDxt5: true),
            PaaFormat.Argb4444 => DecodeArgb4444(data, width, height),
            PaaFormat.Argb1555 => DecodeArgb1555(data, width, height),
            PaaFormat.Ai88 => DecodeAi88(data, width, height),
            _ => throw new NotSupportedException($"PAA format {format} has no pixel decoder."),
        };
    }

    private static byte[] DecodeDxt(byte[] data, int width, int height, bool isDxt5)
    {
        var rgba = new byte[width * height * 4];
        var blocksX = (width + 3) / 4;
        var blocksY = (height + 3) / 4;
        var blockSize = isDxt5 ? 16 : 8;
        var offset = 0;

        for (var by = 0; by < blocksY; by++)
        {
            for (var bx = 0; bx < blocksX; bx++)
            {
                byte[] alphaIndices = Array.Empty<byte>();
                byte alpha0 = 255, alpha1 = 255;
                var colorOffset = offset;

                if (isDxt5)
                {
                    alpha0 = data[offset];
                    alpha1 = data[offset + 1];
                    alphaIndices = ExpandAlphaIndices(data, offset + 2);
                    colorOffset = offset + 8;
                }

                var color0 = (ushort)(data[colorOffset] | (data[colorOffset + 1] << 8));
                var color1 = (ushort)(data[colorOffset + 2] | (data[colorOffset + 3] << 8));
                var indices = (uint)(data[colorOffset + 4] | (data[colorOffset + 5] << 8) | (data[colorOffset + 6] << 16) | (data[colorOffset + 7] << 24));

                var (r0, g0, b0) = Rgb565(color0);
                var (r1, g1, b1) = Rgb565(color1);

                var paletteR = new byte[4];
                var paletteG = new byte[4];
                var paletteB = new byte[4];
                var paletteA = new byte[4];
                paletteR[0] = r0; paletteG[0] = g0; paletteB[0] = b0; paletteA[0] = 255;
                paletteR[1] = r1; paletteG[1] = g1; paletteB[1] = b1; paletteA[1] = 255;
                if (!isDxt5 && color0 <= color1)
                {
                    paletteR[2] = (byte)((r0 + r1) / 2); paletteG[2] = (byte)((g0 + g1) / 2); paletteB[2] = (byte)((b0 + b1) / 2); paletteA[2] = 255;
                    paletteR[3] = 0; paletteG[3] = 0; paletteB[3] = 0; paletteA[3] = 0;
                }
                else
                {
                    paletteR[2] = (byte)((2 * r0 + r1) / 3); paletteG[2] = (byte)((2 * g0 + g1) / 3); paletteB[2] = (byte)((2 * b0 + b1) / 3); paletteA[2] = 255;
                    paletteR[3] = (byte)((r0 + 2 * r1) / 3); paletteG[3] = (byte)((g0 + 2 * g1) / 3); paletteB[3] = (byte)((b0 + 2 * b1) / 3); paletteA[3] = 255;
                }

                for (var py = 0; py < 4; py++)
                {
                    var y = by * 4 + py;
                    if (y >= height)
                    {
                        continue;
                    }
                    for (var px = 0; px < 4; px++)
                    {
                        var x = bx * 4 + px;
                        if (x >= width)
                        {
                            continue;
                        }
                        var pixelIndex = py * 4 + px;
                        var colorSelector = (int)((indices >> (pixelIndex * 2)) & 0x3);
                        var r = paletteR[colorSelector];
                        var g = paletteG[colorSelector];
                        var b = paletteB[colorSelector];
                        var a = paletteA[colorSelector];
                        if (isDxt5)
                        {
                            a = InterpolatedAlpha(alpha0, alpha1, alphaIndices[pixelIndex]);
                        }

                        var pixelOffset = (y * width + x) * 4;
                        rgba[pixelOffset] = r;
                        rgba[pixelOffset + 1] = g;
                        rgba[pixelOffset + 2] = b;
                        rgba[pixelOffset + 3] = a;
                    }
                }

                offset += blockSize;
            }
        }

        return rgba;
    }

    private static byte[] ExpandAlphaIndices(byte[] data, int offset)
    {
        // 16 pixels × 3 bits packed into 6 bytes (48 bits), read as two 24-bit little-endian groups of 8 indices each.
        var indices = new byte[16];
        ulong bits = 0;
        for (var i = 0; i < 6; i++)
        {
            bits |= (ulong)data[offset + i] << (8 * i);
        }
        for (var i = 0; i < 16; i++)
        {
            indices[i] = (byte)((bits >> (i * 3)) & 0x7);
        }
        return indices;
    }

    private static byte InterpolatedAlpha(byte a0, byte a1, byte index)
    {
        if (index == 0) return a0;
        if (index == 1) return a1;
        if (a0 > a1)
        {
            return (byte)(((7 - index) * a0 + (index - 1) * a1) / 6);
        }
        return index switch
        {
            6 => 0,
            7 => 255,
            _ => (byte)(((5 - index) * a0 + (index - 1) * a1) / 4),
        };
    }

    private static (byte r, byte g, byte b) Rgb565(ushort color)
    {
        var r5 = (color >> 11) & 0x1F;
        var g6 = (color >> 5) & 0x3F;
        var b5 = color & 0x1F;
        var r = (byte)((r5 << 3) | (r5 >> 2));
        var g = (byte)((g6 << 2) | (g6 >> 4));
        var b = (byte)((b5 << 3) | (b5 >> 2));
        return (r, g, b);
    }

    private static byte[] DecodeArgb4444(byte[] data, int width, int height)
    {
        var rgba = new byte[width * height * 4];
        for (var i = 0; i < width * height; i++)
        {
            var value = (ushort)(data[i * 2] | (data[i * 2 + 1] << 8));
            var a4 = (value >> 12) & 0xF;
            var r4 = (value >> 8) & 0xF;
            var g4 = (value >> 4) & 0xF;
            var b4 = value & 0xF;
            rgba[i * 4] = (byte)((r4 << 4) | r4);
            rgba[i * 4 + 1] = (byte)((g4 << 4) | g4);
            rgba[i * 4 + 2] = (byte)((b4 << 4) | b4);
            rgba[i * 4 + 3] = (byte)((a4 << 4) | a4);
        }
        return rgba;
    }

    private static byte[] DecodeArgb1555(byte[] data, int width, int height)
    {
        var rgba = new byte[width * height * 4];
        for (var i = 0; i < width * height; i++)
        {
            var value = (ushort)(data[i * 2] | (data[i * 2 + 1] << 8));
            var a1 = (value >> 15) & 0x1;
            var r5 = (value >> 10) & 0x1F;
            var g5 = (value >> 5) & 0x1F;
            var b5 = value & 0x1F;
            rgba[i * 4] = (byte)((r5 << 3) | (r5 >> 2));
            rgba[i * 4 + 1] = (byte)((g5 << 3) | (g5 >> 2));
            rgba[i * 4 + 2] = (byte)((b5 << 3) | (b5 >> 2));
            rgba[i * 4 + 3] = (byte)(a1 == 1 ? 255 : 0);
        }
        return rgba;
    }

    /// <summary>
    /// Grayscale+alpha (2 bytes/pixel). Byte order is a best-effort assumption
    /// (intensity, then alpha) — this format is not expected to occur in the
    /// satellite/mask layer tiles this tool actually needs to decode.
    /// </summary>
    private static byte[] DecodeAi88(byte[] data, int width, int height)
    {
        var rgba = new byte[width * height * 4];
        for (var i = 0; i < width * height; i++)
        {
            var intensity = data[i * 2];
            var alpha = data[i * 2 + 1];
            rgba[i * 4] = intensity;
            rgba[i * 4 + 1] = intensity;
            rgba[i * 4 + 2] = intensity;
            rgba[i * 4 + 3] = alpha;
        }
        return rgba;
    }
}
