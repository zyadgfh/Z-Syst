import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';

/// Centralizes AdMob configuration and lifecycle handling for Android.
///
/// Ad unit IDs are public identifiers, not secrets. Keep sensitive credentials
/// out of the repository and out of this class.
class AdMobService {
  AdMobService._();

  static const String bannerAdUnitId = 'ca-app-pub-7182978499975366/8095961836';
  static const String interstitialAdUnitId = 'ca-app-pub-7182978499975366/4311970357';

  static InterstitialAd? _interstitialAd;
  static DateTime? _lastInterstitialShownAt;
  static const Duration _interstitialCooldown = Duration(minutes: 3);

  static Future<void> initialize() async {
    if (defaultTargetPlatform != TargetPlatform.android) {
      return;
    }

    await MobileAds.instance.initialize();
    preloadInterstitial();
  }

  static void preloadInterstitial() {
    if (defaultTargetPlatform != TargetPlatform.android || _interstitialAd != null) {
      return;
    }

    InterstitialAd.load(
      adUnitId: interstitialAdUnitId,
      request: const AdRequest(),
      adLoadCallback: InterstitialAdLoadCallback(
        onAdLoaded: (ad) {
          _interstitialAd = ad;
          ad.fullScreenContentCallback = FullScreenContentCallback(
            onAdDismissedFullScreenContent: (ad) {
              ad.dispose();
              _interstitialAd = null;
              preloadInterstitial();
            },
            onAdFailedToShowFullScreenContent: (ad, _) {
              ad.dispose();
              _interstitialAd = null;
              preloadInterstitial();
            },
          );
        },
        onAdFailedToLoad: (_) {
          _interstitialAd = null;
        },
      ),
    );
  }

  /// Shows an interstitial only at a caller-selected, non-sensitive transition.
  ///
  /// Callers must not invoke this while a user is entering an expense, making a
  /// sale, paying, uploading data, or synchronizing. A cooldown prevents
  /// repeated interruptions when the user browses quickly.
  static void showAfterNonSensitiveTransition() {
    if (defaultTargetPlatform != TargetPlatform.android) {
      return;
    }

    final ad = _interstitialAd;
    if (ad == null) {
      preloadInterstitial();
      return;
    }

    final now = DateTime.now();
    if (_lastInterstitialShownAt != null && now.difference(_lastInterstitialShownAt!) < _interstitialCooldown) {
      return;
    }

    _interstitialAd = null;
    _lastInterstitialShownAt = now;
    ad.show();
  }
}

class AdMobBanner extends StatefulWidget {
  const AdMobBanner({super.key});

  @override
  State<AdMobBanner> createState() => _AdMobBannerState();
}

class _AdMobBannerState extends State<AdMobBanner> {
  BannerAd? _bannerAd;
  bool _isLoaded = false;

  @override
  void initState() {
    super.initState();
    if (defaultTargetPlatform == TargetPlatform.android) {
      _bannerAd = BannerAd(
        adUnitId: AdMobService.bannerAdUnitId,
        request: const AdRequest(),
        size: AdSize.banner,
        listener: BannerAdListener(
          onAdLoaded: (ad) {
            if (mounted) setState(() => _isLoaded = true);
          },
          onAdFailedToLoad: (ad, _) {
            ad.dispose();
            if (mounted) setState(() => _isLoaded = false);
          },
        ),
      )..load();
    }
  }

  @override
  void dispose() {
    _bannerAd?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (!_isLoaded || _bannerAd == null) {
      return const SizedBox.shrink();
    }

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: SizedBox(
        width: _bannerAd!.size.width.toDouble(),
        height: _bannerAd!.size.height.toDouble(),
        child: AdWidget(ad: _bannerAd!),
      ),
    );
  }
}
