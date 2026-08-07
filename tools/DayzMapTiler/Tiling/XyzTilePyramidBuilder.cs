using SixLabors.ImageSharp;
using SixLabors.ImageSharp.Formats.Png;
using SixLabors.ImageSharp.Formats.Webp;
using SixLabors.ImageSharp.PixelFormats;
using SixLabors.ImageSharp.Processing;

namespace DayzMapTiler.Tiling;

/// <summary>
/// Slices a single square source image (spanning the whole world, X/Z 0..worldSize) into
/// a standard XYZ tile pyramid: <c>tiles/{z}/{x}/{y}.webp</c> (or .png), y=0 at the top of
/// the image (not TMS). At zoom z the world is covered by 2^z tiles per side, so the
/// source is resized to tileSize*2^z square for that zoom before slicing.
///
/// Defaults to WebP: lossless PNG on photographic satellite tiles measured ~450KB/tile,
/// which makes a real tile pyramid impractical to serve — WebP at quality 82 gets the same
/// tile down to the 15-40KB range with no visible loss, which is what actually matters for
/// a map people will pan/zoom in a browser.
/// </summary>
public static class XyzTilePyramidBuilder
{
    public static void Build(Image<Rgba32> source, string outputDir, int tileSize, int minZoom, int maxZoom, string format = "webp")
    {
        for (var z = minZoom; z <= maxZoom; z++)
        {
            var tilesPerSide = 1 << z; // 2^z
            var targetSize = tileSize * tilesPerSide;

            if (targetSize > source.Width)
            {
                Console.WriteLine($"  zoom {z}: upscaling {source.Width}px source to {targetSize}px (beyond native resolution — expected past the source's native detail level).");
            }

            using var resized = source.Clone(ctx => ctx.Resize(new ResizeOptions
            {
                Size = new Size(targetSize, targetSize),
                Mode = ResizeMode.Stretch, // the world is square by definition; the source is expected to already cover it edge-to-edge
                Sampler = KnownResamplers.Bicubic,
            }));

            var zoomDir = Path.Combine(outputDir, z.ToString());
            for (var x = 0; x < tilesPerSide; x++)
            {
                var xDir = Path.Combine(zoomDir, x.ToString());
                Directory.CreateDirectory(xDir);
                for (var y = 0; y < tilesPerSide; y++)
                {
                    using var tile = resized.Clone(ctx => ctx.Crop(new Rectangle(x * tileSize, y * tileSize, tileSize, tileSize)));
                    if (format == "png")
                    {
                        tile.Save(Path.Combine(xDir, $"{y}.png"), new PngEncoder());
                    }
                    else
                    {
                        tile.Save(Path.Combine(xDir, $"{y}.webp"), new WebpEncoder { Quality = 82 });
                    }
                }
            }

            Console.WriteLine($"  zoom {z}: {tilesPerSide * tilesPerSide} dlaždic ({tilesPerSide}x{tilesPerSide}) hotovo.");
        }
    }
}
