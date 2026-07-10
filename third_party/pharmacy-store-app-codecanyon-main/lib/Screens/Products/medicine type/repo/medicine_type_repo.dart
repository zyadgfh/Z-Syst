// ignore_for_file: file_names, unused_element, unused_local_variable

import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:http/http.dart' as http;

import '../../../../app_config/api_config.dart';
import '../../../../Repository/constant_functions.dart';
import '../model/medicine_type_model.dart';
import '../provider/medicine_type_provider.dart';

class MedicineTypeRepo {
  Future<List<MedicineTypeModel>> fetchAllMedicineType() async {
    final uri = Uri.parse('${APIConfig.url}/medicine-types');

    try {
      final response = await http.get(uri, headers: {
        'Accept': 'application/json',
        'Authorization': await getAuthToken(),
      });

      if (response.statusCode == 200) {
        final parsedData = jsonDecode(response.body) as Map<String, dynamic>;
        final categoryList = parsedData['data'] as List<dynamic>;
        return categoryList.map((unit) => MedicineTypeModel.fromJson(unit)).toList();
      } else {
        throw Exception('Failed to fetch medicine type: ${response.statusCode}');
      }
    } catch (error) {
      rethrow;
    }
  }

  Future<void> addMedicineType({
    required WidgetRef ref,
    required BuildContext context,
    required String medicineType,
    required bool fromDropdown,
  }) async {
    final uri = Uri.parse('${APIConfig.url}/medicine-types');

    try {
      var responseData = await http.post(uri, headers: {
        "Accept": 'application/json',
        'Authorization': await getAuthToken(),
      }, body: {
        'name': medicineType,
      });
      final parsedData = jsonDecode(responseData.body);

      if (responseData.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Added successful!')));

        if (!fromDropdown) ref.refresh(medicineTypeProvider);

        if (parsedData['data'] != null) {
          MedicineTypeModel addedData = MedicineTypeModel.fromJson(parsedData['data']);

          Navigator.pop(context, addedData);
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Medicine type creation failed: ${parsedData['message']}')));
      }
    } catch (error) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('An error occurred: $error')));
    }
  }

  Future<num?> addMedicineTypeForBulk({
    required String name,
  }) async {
    final uri = Uri.parse('${APIConfig.url}/medicine-types');

    try {
      var responseData = await http.post(uri, headers: {
        "Accept": 'application/json',
        'Authorization': await getAuthToken(),
      }, body: {
        'name': name,
      });
      final parsedData = jsonDecode(responseData.body);

      if (responseData.statusCode == 200) {
        return parsedData['data']['id'];
      } else {
        return null;
      }
    } catch (error) {
      return null;
    }
  }

  ///_______Edit_________________________________________
  Future<void> editMedicineType({
    required WidgetRef ref,
    required BuildContext context,
    required num id,
    required String name,
  }) async {
    final uri = Uri.parse('${APIConfig.url}/medicine-types/$id');

    try {
      var responseData = await http.post(uri, headers: {
        "Accept": 'application/json',
        'Authorization': await getAuthToken(),
      }, body: {
        'name': name,
        '_method': 'put',
      });
      final parsedData = jsonDecode(responseData.body);

      if (responseData.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('update successful!')));
        var data1 = ref.refresh(medicineTypeProvider);
        Navigator.pop(context);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Medicine type creation failed: ${parsedData['message']}')));
      }
    } catch (error) {
      // Handle unexpected errors gracefully
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('An error occurred: $error')));
    }
  }

  ///_________delete_medicine_type________________________
  Future<bool> deleteMedicineType({required BuildContext context, required num unitId}) async {
    final String apiUrl = '${APIConfig.url}/medicine-types/$unitId'; // Replace with your API URL

    try {
      final response = await http.delete(
        Uri.parse(apiUrl),
        headers: {
          'Accept': 'application/json',
          'Authorization': await getAuthToken(),
        },
      );

      if (response.statusCode == 200) {
        final responseData = json.decode(response.body);
        final String message = responseData['message'];
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message)),
        );
        return true;
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to delete medicine type.')),
        );
        return false;
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('An error occurred.')),
      );
      return false;
    }
  }
}
