namespace DayzMapTiler.Paa;

/// <summary>
/// LZO1X decompressor — a direct translation of the canonical miniLZO
/// <c>lzo1x_decompress</c> reference algorithm (the same one BI's tools use to pack
/// PAA mip levels flagged with the 0x8000 "compressed" width bit). The control flow
/// intentionally mirrors the reference implementation's goto-based state machine
/// (flat, not nested in loops — C# forbids jumping into a block from outside it, so
/// this stays a single flat sequence of labels exactly like the original C) instead of
/// a "cleaned up" rewrite, since a subtle reordering here would silently corrupt pixel
/// data rather than throw.
/// </summary>
public static class Lzo1x
{
    public static byte[] Decompress(byte[] input, int expectedOutputLength)
    {
        var output = new byte[expectedOutputLength];
        var ip = 0;
        var op = 0;
        int t;
        int mPos;

        if (input[ip] > 17)
        {
            t = input[ip++] - 17;
            CopyLiterals(input, output, ref ip, ref op, t);
            goto FirstLiteralRun;
        }

    LiteralLengthLoop:
        t = input[ip++];
        if (t >= 16)
        {
            goto Match;
        }

        if (t == 0)
        {
            while (input[ip] == 0)
            {
                t += 255;
                ip++;
            }
            t += 15 + input[ip++];
        }

        CopyLiterals(input, output, ref ip, ref op, t + 3);

    FirstLiteralRun:
        t = input[ip++];
        if (t >= 16)
        {
            goto Match;
        }

        // A literal run that ended here is always followed by a match encoded with
        // 2 distance bytes and a reused/decremented distance ("M1" in the reference).
        mPos = op - 1 - (t >> 2) - (input[ip++] << 2);
        output[op++] = output[mPos++];
        output[op++] = output[mPos];
        goto MatchDone;

    Match:
        if (t >= 64)
        {
            // M2: 3-bit length, 3-bit low distance, 1 distance byte.
            mPos = op - 1 - ((t >> 2) & 7) - (input[ip++] << 3);
            t = (t >> 5) - 1;
        }
        else if (t >= 32)
        {
            // M3: 5-bit length (extensible), 2 distance bytes.
            t &= 31;
            if (t == 0)
            {
                while (input[ip] == 0)
                {
                    t += 255;
                    ip++;
                }
                t += 31 + input[ip++];
            }
            mPos = op - 1 - (ReadUInt16(input, ip) >> 2);
            ip += 2;
        }
        else
        {
            // M4: 3-bit length (extensible), high distance bit folded into the
            // opcode, 2 distance bytes; distance 0 here means end-of-stream.
            mPos = op - ((t & 8) << 11);
            t &= 7;
            if (t == 0)
            {
                while (input[ip] == 0)
                {
                    t += 255;
                    ip++;
                }
                t += 7 + input[ip++];
            }
            mPos -= ReadUInt16(input, ip) >> 2;
            ip += 2;
            if (mPos == op)
            {
                // End-of-stream marker. Output should already be exactly full.
                return output;
            }
            mPos -= 0x4000;
        }

        CopyMatch(output, mPos, ref op, t + 2);

    MatchDone:
        t = input[ip - 2] & 3;
        if (t == 0)
        {
            goto LiteralLengthLoop;
        }

        CopyLiterals(input, output, ref ip, ref op, t);
        goto FirstLiteralRun;
    }

    private static ushort ReadUInt16(byte[] data, int offset) => (ushort)(data[offset] | (data[offset + 1] << 8));

    private static void CopyLiterals(byte[] input, byte[] output, ref int ip, ref int op, int count)
    {
        Buffer.BlockCopy(input, ip, output, op, count);
        ip += count;
        op += count;
    }

    private static void CopyMatch(byte[] output, int matchPos, ref int op, int count)
    {
        // Overlapping copies (matchPos within `count` bytes of op) are the whole point
        // of LZ77-style back-references — must copy byte-by-byte, not via Buffer.BlockCopy.
        for (var i = 0; i < count; i++)
        {
            output[op + i] = output[matchPos + i];
        }
        op += count;
    }
}
