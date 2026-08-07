using System.Text.Json;
using System.Text.Json.Serialization;

namespace DayzMapTiler.Output;

/// <summary>Matches the exact tiles.json schema requested — no extra fields.</summary>
public sealed class TilesJsonModel
{
    [JsonPropertyName("map")] public required string Map { get; init; }
    [JsonPropertyName("worldSize")] public required int WorldSize { get; init; }
    [JsonPropertyName("tileSize")] public required int TileSize { get; init; }
    [JsonPropertyName("minZoom")] public required int MinZoom { get; init; }
    [JsonPropertyName("maxZoom")] public required int MaxZoom { get; init; }
    [JsonPropertyName("projection")] public required string Projection { get; init; }
}

public static class TilesJsonWriter
{
    public static void Write(string path, TilesJsonModel model)
    {
        var json = JsonSerializer.Serialize(model, new JsonSerializerOptions { WriteIndented = true });
        File.WriteAllText(path, json);
    }
}
