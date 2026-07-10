import 'package:http/http.dart' as http;
import 'package:mobile_pos/app_config/app_config.dart';


class PurchaseModel {
  Future<bool> isActiveBuyer() async {
    try {
      final uri = Uri.parse('https://api.envato.com/v3/market/author/sale?code=${AppConfig.purchaseCode}');
      final headers = {'Authorization': 'Bearer orZoxiU81Ok7kxsE0FvfraaO0vDW5tiz'};

      final response = await http.get(uri, headers: headers);

      return response.statusCode == 200;
    } catch (e) {
      // Handle errors gracefully
      print("Error in isActiveBuyer: $e");
      return false;
    }
  }
}
