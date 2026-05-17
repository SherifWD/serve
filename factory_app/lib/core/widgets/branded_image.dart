import 'package:flutter/material.dart';

enum BrandedImageKind { venue, dish, hero }

class BrandedImage extends StatelessWidget {
  const BrandedImage({
    required this.label,
    this.imageUrl,
    this.kind = BrandedImageKind.dish,
    this.fit = BoxFit.cover,
    this.overlay = const LinearGradient(
      colors: [Color(0x22000000), Color(0x66000000)],
      begin: Alignment.topCenter,
      end: Alignment.bottomCenter,
    ),
    super.key,
  });

  final String label;
  final String? imageUrl;
  final BrandedImageKind kind;
  final BoxFit fit;
  final Gradient overlay;

  @override
  Widget build(BuildContext context) {
    final hasImage = imageUrl != null && imageUrl!.trim().isNotEmpty;

    return DecoratedBox(
      decoration: const BoxDecoration(color: Color(0x14000000)),
      child: Stack(
        fit: StackFit.expand,
        children: [
          if (hasImage)
            Image.network(
              imageUrl!,
              fit: fit,
              errorBuilder: (context, error, stackTrace) => _FallbackArtwork(
                label: label,
                kind: kind,
              ),
              loadingBuilder: (context, child, progress) {
                if (progress == null) return child;
                return _FallbackArtwork(label: label, kind: kind);
              },
            )
          else
            _FallbackArtwork(label: label, kind: kind),
          DecoratedBox(decoration: BoxDecoration(gradient: overlay)),
        ],
      ),
    );
  }
}

class _FallbackArtwork extends StatelessWidget {
  const _FallbackArtwork({
    required this.label,
    required this.kind,
  });

  final String label;
  final BrandedImageKind kind;

  @override
  Widget build(BuildContext context) {
    final palette = _paletteFor(label, kind);

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: palette,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Stack(
        fit: StackFit.expand,
        children: [
          Positioned(
            top: 0,
            right: 0,
            child: Container(
              width: 120,
              height: 42,
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.12),
                borderRadius: const BorderRadius.only(
                  bottomLeft: Radius.circular(42),
                ),
              ),
            ),
          ),
          Positioned(
            bottom: 0,
            left: 0,
            child: Container(
              width: 150,
              height: 36,
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.08),
                borderRadius: const BorderRadius.only(
                  topRight: Radius.circular(36),
                ),
              ),
            ),
          ),
          Positioned(
            top: 22,
            left: 22,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.18),
                borderRadius: BorderRadius.circular(999),
              ),
              child: Icon(
                _iconFor(kind, label),
                color: Colors.white,
                size: kind == BrandedImageKind.hero ? 22 : 18,
              ),
            ),
          ),
          Align(
            alignment: Alignment.center,
            child: Icon(
              _iconFor(kind, label),
              size: kind == BrandedImageKind.hero ? 76 : 54,
              color: Colors.white.withValues(alpha: 0.42),
            ),
          ),
        ],
      ),
    );
  }

  List<Color> _paletteFor(String seed, BrandedImageKind kind) {
    final sets = switch (kind) {
      BrandedImageKind.venue => const [
          [Color(0xFFF97316), Color(0xFFEA580C), Color(0xFFF59E0B)],
          [Color(0xFF2563EB), Color(0xFF0F766E), Color(0xFF38BDF8)],
          [Color(0xFF7C3AED), Color(0xFFDB2777), Color(0xFFF43F5E)],
          [Color(0xFF059669), Color(0xFF16A34A), Color(0xFF84CC16)],
        ],
      BrandedImageKind.hero => const [
          [Color(0xFFF97316), Color(0xFFFB7185), Color(0xFFFACC15)],
          [Color(0xFF0F766E), Color(0xFF2563EB), Color(0xFF38BDF8)],
          [Color(0xFF7C3AED), Color(0xFF3B82F6), Color(0xFF22C55E)],
          [Color(0xFFDC2626), Color(0xFFEA580C), Color(0xFFF59E0B)],
        ],
      BrandedImageKind.dish => const [
          [Color(0xFFB45309), Color(0xFFF97316), Color(0xFFF59E0B)],
          [Color(0xFF7C2D12), Color(0xFFEA580C), Color(0xFFF43F5E)],
          [Color(0xFF14532D), Color(0xFF16A34A), Color(0xFF4ADE80)],
          [Color(0xFF0F172A), Color(0xFF334155), Color(0xFF64748B)],
        ],
    };

    final index =
        seed.codeUnits.fold<int>(0, (sum, unit) => sum + unit) % sets.length;
    return sets[index];
  }

  IconData _iconFor(BrandedImageKind kind, String seed) {
    if (kind == BrandedImageKind.venue) {
      return seed.toLowerCase().contains('cafe')
          ? Icons.local_cafe_rounded
          : Icons.storefront_rounded;
    }

    if (kind == BrandedImageKind.hero) {
      return Icons.local_offer_rounded;
    }

    final lower = seed.toLowerCase();
    if (lower.contains('coffee') ||
        lower.contains('latte') ||
        lower.contains('white')) {
      return Icons.coffee_rounded;
    }
    if (lower.contains('cake') ||
        lower.contains('brownie') ||
        lower.contains('dessert')) {
      return Icons.cake_rounded;
    }
    if (lower.contains('wrap') ||
        lower.contains('burger') ||
        lower.contains('kebab')) {
      return Icons.lunch_dining_rounded;
    }

    return Icons.restaurant_menu_rounded;
  }
}
