import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:mobile_pos/app_config/api_config.dart';

/// Z-Syst platform services for the Flutter POS app.
///
/// Everything the device app needs beyond the classic CRUD repos:
///  - device enrollment & assignment (printers per device, scanner config)
///  - scan-based stock take sessions
///  - offline sale replay (idempotent by clientUuid)
///  - dose sticker lookup (scan DOSE:{sale}:{detail} → instructions)
class ZSystApi {
  ZSystApi({http.Client? client, this.token}) : _client = client ?? http.Client();

  final http.Client _client;
  final String? token;

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': token,
      };

  Uri _u(String path) => Uri.parse('${APIConfig.url}/$path');

  Future<Map<String, dynamic>?> _send(String method, String path, {Map<String, dynamic>? body}) async {
    final uri = _u(path);
    final req = http.Request(method, uri)..headers.addAll(_headers);
    if (body != null) req.body = json.encode(body);
    final res = await _client.send(req).then(http.Response.fromStream);
    if (res.statusCode >= 200 && res.statusCode < 300) {
      final decoded = json.decode(res.body);
      return decoded is Map<String, dynamic> ? decoded : {'data': decoded};
    }
    return null;
  }

  // ── Devices ─────────────────────────────────────────────────────────

  /// Register this phone/tablet as a POS device; returns uuid + device token
  /// (X-Device-Token) plus the assigned barcode/receipt printers.
  Future<Map<String, dynamic>?> enrollDevice({
    required String name,
    required String bearerToken,
    Map<String, dynamic> peripherals = const {'scanner': true},
  }) async {
    final res = await _client.post(
      _u('devices/enroll'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'Authorization': 'Bearer $bearerToken'},
      body: json.encode({'name': name, 'type': 'pos', 'peripherals': peripherals}),
    );
    if (res.statusCode >= 200 && res.statusCode < 300) {
      return (json.decode(res.body) as Map<String, dynamic>)['data'] as Map<String, dynamic>?;
    }
    return null;
  }

  /// Assigned printers + scanner/print settings for this device.
  Future<Map<String, dynamic>?> deviceMe({required String deviceToken, required String bearerToken}) async {
    final res = await _client.get(
      _u('devices/me'),
      headers: {..._headers, 'Authorization': 'Bearer $bearerToken', 'X-Device-Token': deviceToken},
    );
    if (res.statusCode == 200) return json.decode(res.body) as Map<String, dynamic>;
    return null;
  }

  Future<bool> heartbeat(String uuid, {required String bearerToken}) async {
    final res = await _client.post(
      _u('devices/$uuid/heartbeat'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $bearerToken'},
    );
    return res.statusCode == 200;
  }

  // ── Stock take (الجرد بالمسح) ────────────────────────────────────────

  Future<Map<String, dynamic>?> stockTakeOpen({required String bearerToken, String type = 'spot_check'}) =>
      _send('POST', 'stock-takes', body: {'type': type});

  Future<Map<String, dynamic>?> stockTakeCurrent({required String bearerToken}) =>
      _send('GET', 'stock-takes/current');

  /// Scan a product code/barcode into the open session (beep = counted).
  Future<Map<String, dynamic>?> stockTakeScan(String code, {required String bearerToken, int qty = 1}) =>
      _send('POST', 'stock-takes/scan', body: {'code': code, 'qty': qty});

  // ── Offline sale replay ─────────────────────────────────────────────

  /// Replay locally-queued sales. The server dedupes by client_uuid, so
  /// replaying the same batch twice never double-sells.
  Future<Map<String, dynamic>?> syncOfflineSales(List<Map<String, dynamic>> sales, {required String bearerToken}) =>
      _send('POST', 'pos/sync', body: {'sales': sales});

  // ── Dose stickers (طباعة الجرعات كباركود) ────────────────────────────

  /// Scan a dose sticker payload `DOSE:{saleId}:{detailId}` → instructions.
  Future<Map<String, dynamic>?> doseLookup(String payload, {required String bearerToken}) async {
    final uri = _u('../admin/dose/$payload').resolveUri(_u(''));
    final res = await _client.get(uri, headers: {'Accept': 'application/json', 'Authorization': 'Bearer $bearerToken'});
    if (res.statusCode == 200) {
      final decoded = json.decode(res.body);
      return decoded is Map<String, dynamic> ? decoded : null;
    }
    return null;
  }
}
