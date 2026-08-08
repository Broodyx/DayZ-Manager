using System.Text;

namespace DayzMapTiler.Source;

/// <summary>
/// Parses a Bohemia Interactive "rapified" binary config (config.bin), the format produced
/// by binarizing a config.cpp. Format (cross-referenced against the independently-maintained
/// open-source derapifiers armake and HEMTT, which agree on this layout):
///
///   Header: char[4] "\0raP", uint32 always0, uint32 enumOffset, uint32 always0.
///   Root class body follows immediately (no parent-name field, unlike nested classes).
///
///   Class body:  cstring parentName (nested classes only, root has none);
///                compressedInt entryCount; entryCount x Entry.
///   Entry: byte type
///     0 = nested class:      cstring name; uint32 bodyOffset (body parsed independently later)
///     1 = value:              byte valueType; cstring name; value (valueType: 0=string,1=float,2=long)
///     2 = array:               cstring name; compressedInt count; count x (byte valueType; value)
///     3 = extern class decl:  cstring name  (forward declaration, no body — "class Foo;")
///     4 = deleted class decl: cstring name  ("delete Foo;")
///     5 = array with +=:      cstring name; byte unknownFlag; compressedInt count; count x (byte valueType; value)
///
///   Enum section (at enumOffset, independent of the class tree — used here purely as a
///   structural cross-check, not as the primary parse path): uint32 count; count x (uint32
///   bodyOffset; cstring className).
/// </summary>
public sealed class RapifiedConfig
{
    public sealed class RapClass
    {
        public required string Name;
        public required string Parent;
        public required long BodyOffset;
        public readonly Dictionary<string, object> Values = new(StringComparer.OrdinalIgnoreCase);
        public readonly Dictionary<string, RapClass> Children = new(StringComparer.OrdinalIgnoreCase);
    }

    public RapClass Root { get; }
    public IReadOnlyList<(uint Offset, string Name)> EnumEntries { get; }
    public List<string> Warnings { get; } = new();

    private readonly byte[] _data;

    private RapifiedConfig(byte[] data, RapClass root, List<(uint, string)> enumEntries)
    {
        _data = data;
        Root = root;
        EnumEntries = enumEntries;
    }

    public static RapifiedConfig Parse(byte[] data)
    {
        if (data.Length < 16 || data[0] != 0 || data[1] != (byte) 'r' || data[2] != (byte) 'a' || data[3] != (byte) 'P')
        {
            throw new InvalidDataException("Not a rapified config (missing \\0raP magic) — this file is plain-text config.cpp, not config.bin.");
        }

        var always0 = ReadUInt32(data, 4);
        var version = ReadUInt32(data, 8);
        var enumOffset = ReadUInt32(data, 12);

        var self = new RapifiedConfigBuilder(data);
        var warnings = self.Warnings;
        if (always0 != 0)
        {
            warnings.Add($"Header field at offset 4 was {always0}, not 0 — format assumption may be off.");
        }

        var root = self.ParseClassBody(16, "", isRoot: true);

        var enumEntries = new List<(uint, string)>();
        var pos = (long) enumOffset;
        var count = ReadUInt32(data, pos);
        pos += 4;
        for (var i = 0; i < count; i++)
        {
            var offset = ReadUInt32(data, pos);
            pos += 4;
            var name = ReadCString(data, ref pos);
            enumEntries.Add((offset, name));
        }

        var result = new RapifiedConfig(data, root, enumEntries)
        {
        };
        result.Warnings.AddRange(warnings);
        return result;
    }

    private static uint ReadUInt32(byte[] data, long pos) =>
        (uint) (data[pos] | (data[pos + 1] << 8) | (data[pos + 2] << 16) | (data[pos + 3] << 24));

    private static float ReadFloat(byte[] data, long pos) => BitConverter.ToSingle(data, (int) pos);

    private static int ReadInt32(byte[] data, long pos) =>
        data[pos] | (data[pos + 1] << 8) | (data[pos + 2] << 16) | (data[pos + 3] << 24);

    private static string ReadCString(byte[] data, ref long pos)
    {
        var start = pos;
        while (data[pos] != 0) pos++;
        var s = Encoding.UTF8.GetString(data, (int) start, (int) (pos - start));
        pos++; // skip null terminator
        return s;
    }

    private static uint ReadCompressedInt(byte[] data, ref long pos)
    {
        uint result = 0;
        var shift = 0;
        byte b;
        do
        {
            b = data[pos++];
            result |= (uint) (b & 0x7f) << shift;
            shift += 7;
        } while ((b & 0x80) != 0);
        return result;
    }

    private sealed class RapifiedConfigBuilder
    {
        private readonly byte[] _data;
        public readonly List<string> Warnings = new();

        public RapifiedConfigBuilder(byte[] data) => _data = data;

        private string _currentClassNameForWarnings = "";

        public RapClass ParseClassBody(long offset, string name, bool isRoot)
        {
            var savedName = _currentClassNameForWarnings;
            _currentClassNameForWarnings = name;
            try
            {
                return ParseClassBodyInner(offset, name, isRoot);
            }
            finally
            {
                _currentClassNameForWarnings = savedName;
            }
        }

        private RapClass ParseClassBodyInner(long offset, string name, bool isRoot)
        {
            var pos = offset;
            var parent = ReadCString(_data, ref pos); // root has an empty parent cstring too — just a lone null byte
            var cls = new RapClass { Name = name, Parent = parent, BodyOffset = offset };

            var entryCount = ReadCompressedInt(_data, ref pos);
            for (var i = 0; i < entryCount; i++)
            {
                var type = _data[pos++];
                switch (type)
                {
                    case 0: // nested class
                    {
                        var childName = ReadCString(_data, ref pos);
                        var bodyOffset = ReadUInt32(_data, pos);
                        pos += 4;
                        // Parse eagerly (depth-first) — bodies can appear anywhere in the file.
                        var child = ParseClassBody(bodyOffset, childName, isRoot: false);
                        cls.Children[childName] = child;
                        break;
                    }
                    case 1: // value
                    {
                        var valueType = _data[pos++];
                        var varName = ReadCString(_data, ref pos);
                        object value;
                        if (valueType == 0) value = ReadCString(_data, ref pos);
                        else if (valueType == 1) { value = ReadFloat(_data, pos); pos += 4; }
                        else if (valueType == 2) { value = ReadInt32(_data, pos); pos += 4; }
                        else value = WarnUnknownValueType(valueType, varName);
                        cls.Values[varName] = value;
                        break;
                    }
                    case 2: // array
                    {
                        var varName = ReadCString(_data, ref pos);
                        cls.Values[varName] = ReadArrayElements(ref pos, varName);
                        break;
                    }
                    case 3: // extern class declaration ("class Foo;") — no body
                    {
                        ReadCString(_data, ref pos);
                        break;
                    }
                    case 4: // deleted class declaration ("delete Foo;")
                    {
                        ReadCString(_data, ref pos);
                        break;
                    }
                    case 5: // array with += (expansion)
                    {
                        var varName = ReadCString(_data, ref pos);
                        pos += 4; // unknown flag / code, observed as a fixed-size field before the count
                        cls.Values[varName] = ReadArrayElements(ref pos, varName);
                        break;
                    }
                    default:
                        Warnings.Add($"Class '{name}': unknown entry type {type} at offset {pos - 1} — remaining entries in this class may be misparsed.");
                        goto doneEntries;
                }
            }
            doneEntries:
            return cls;
        }

        private List<object> ReadArrayElements(ref long pos, string varName)
        {
            var count = ReadCompressedInt(_data, ref pos);
            var values = new List<object>((int) count);
            for (var i = 0; i < count; i++)
            {
                var elemType = _data[pos++];
                object v;
                if (elemType == 0) v = ReadCString(_data, ref pos);
                else if (elemType == 1) { v = ReadFloat(_data, pos); pos += 4; }
                else if (elemType == 2) { v = ReadInt32(_data, pos); pos += 4; }
                else if (elemType == 3) v = ReadArrayElements(ref pos, varName); // nested sub-array (e.g. curve points, health level tuples)
                else { v = WarnUnknownValueType(elemType, varName); break; }
                values.Add(v);
            }
            return values;
        }

        private object WarnUnknownValueType(byte valueType, string varName)
        {
            Warnings.Add($"Class '{_currentClassNameForWarnings}': unknown value type {valueType} for '{varName}' — this property (and possibly following entries in this class) may be misparsed.");
            return "<unknown>";
        }
    }
}
