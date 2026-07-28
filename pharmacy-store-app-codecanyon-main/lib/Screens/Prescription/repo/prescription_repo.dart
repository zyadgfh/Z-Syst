import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Screens/Prescription/model/prescription_model.dart';
import 'package:mobile_pos/Repository/constant_functions.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import 'package:path/path.dart' as path;

class PrescriptionRepo {
  Future<PrescriptionPaginatedModel?> getPrescriptions({String? search, String? nextPage}) async {
    final uri = Uri.parse('${APIConfig.url}/prescriptions?page=$nextPage&search=$search');
    final token = await getAuthToken();

    final response = await http.get(uri, headers: {
      'Accept': 'application/json',
      'Authorization': token,
    });

    if (response.statusCode == 200) {
      return PrescriptionPaginatedModel.fromJson(jsonDecode(response.body));
    } else {
      throw Exception('Failed to fetch prescriptions');
    }
  }

  Future<Map<String, dynamic>> createPrescription({
    required File image,
    String? partyId,
    String? notes,
  }) async {
    final token = await getAuthToken();
    final uri = Uri.parse('${APIConfig.url}/prescriptions');

    final request = http.MultipartRequest('POST', uri);
    request.headers.addAll({
      'Accept': 'application/json',
      'Authorization': token,
    });

    if (partyId != null && partyId.isNotEmpty) {
      request.fields['party_id'] = partyId;
    }
    if (notes != null && notes.isNotEmpty) {
      request.fields['notes'] = notes;
    }
    request.files.add(await http.MultipartFile.fromPath('image', image.path, filename: path.basename(image.path)));

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);

    if (response.statusCode == 200 || response.statusCode == 201) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to create prescription');
    }
  }

  Future<Map<String, dynamic>> updatePrescription({
    required int id,
    File? image,
    String? partyId,
    String? notes,
  }) async {
    final token = await getAuthToken();
    final uri = Uri.parse('${APIConfig.url}/prescriptions/$id');

    final request = http.MultipartRequest('POST', uri);
    request.headers.addAll({
      'Accept': 'application/json',
      'Authorization': token,
    });
    request.fields['_method'] = 'PUT';

    if (partyId != null && partyId.isNotEmpty) {
      request.fields['party_id'] = partyId;
    }
    if (notes != null && notes.isNotEmpty) {
      request.fields['notes'] = notes;
    }
    if (image != null) {
      request.files.add(await http.MultipartFile.fromPath('image', image.path, filename: path.basename(image.path)));
    }

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to update prescription');
    }
  }

  Future<Map<String, dynamic>> deletePrescription(int id) async {
    final token = await getAuthToken();
    final uri = Uri.parse('${APIConfig.url}/prescriptions/$id');

    final response = await http.delete(uri, headers: {
      'Accept': 'application/json',
      'Authorization': token,
    });

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to delete prescription');
    }
  }

  Future<Map<String, dynamic>> linkToSale({
    required int prescriptionId,
    required int saleId,
  }) async {
    final token = await getAuthToken();
    final uri = Uri.parse('${APIConfig.url}/prescriptions/link-to-sale');

    final response = await http.post(
      uri,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': token,
      },
      body: jsonEncode({
        'prescription_id': prescriptionId,
        'sale_id': saleId,
      }),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to link prescription to sale');
    }
  }
}
