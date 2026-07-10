import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Products/unit/repo/unit_repo.dart';
import 'package:excel/excel.dart' as e;
import '../Model/product_model.dart';
import '../box/model/box_model.dart';
import '../box/repo/box_repo.dart';
import '../category/model/category_model.dart';
import '../category/repo/category_repo.dart';
import '../manufacturer/model/manufacturer_model.dart';
import '../manufacturer/repo/manufacturer_repo.dart';
import '../medicine type/model/medicine_type_model.dart';
import '../medicine type/repo/medicine_type_repo.dart';
import '../unit/model/unit_model.dart';

Future<ProductModel?> createProductModelFromExcelData({
  required List<e.Data?> row,
  required WidgetRef ref,
}) async {
  // Helper function to get category ID or add a new category
  Future<num?> getCategoryFromDatabase({
    required WidgetRef ref,
    required String givenCategoryName,
  }) async {
    CategoryRepo repo = CategoryRepo();
    num? categoryId;
    List<ProductCategoryModel> categories = await repo.fetchAllCategory();
    for (var element in categories) {
      print('Exiting Category name and id = ${element.categoryName} and Id: ${element.id}');
      if (element.categoryName?.toLowerCase().trim() == givenCategoryName.toLowerCase().trim()) {
        print('this is the id number: ${element.id} ');
        categoryId = element.id;
        break;
      }
    }

    categoryId ??= await repo.addCategoryForBulk(
      name: givenCategoryName,
    );

    return categoryId;
  }

  // Helper function to get category ID or add a new category
  Future<num?> getUnitFromDatabase({
    required WidgetRef ref,
    required String givenUnitName,
  }) async {
    UnitsRepo repo = UnitsRepo();
    Future<num?> addUnit({required String unitName}) async {
      return await repo.addUnitForBulk(
        name: unitName,
      );
    }

    num? unitId;

    List<UnitModel> units = await repo.fetchAllUnits();

    for (var element in units) {
      if (element.unitName?.toLowerCase().trim() == givenUnitName.toLowerCase().trim()) {
        unitId = element.id;
        break;
      }
    }
    unitId ??= await addUnit(unitName: givenUnitName);

    return unitId;
  }

  Future<num?> getBoxSizeFromDatabase({
    required WidgetRef ref,
    required String givenBoxSize,
  }) async {
    Future<num?> addBoxSize({required String boxSize}) async {
      return await BoxRepo().addBoxSizeForBulk(
        name: boxSize,
      );
    }

    final List<BoxSizeModel> boxSizes = await BoxRepo().fetchAllBox();
    num? id;
    for (var element in boxSizes) {
      if (element.name?.toLowerCase().trim() == givenBoxSize.toLowerCase().trim()) {
        id = element.id;
        break;
      }
    }
    id ??= await addBoxSize(boxSize: givenBoxSize);

    return id;
  }

  Future<num?> getMedicineTypeFromDatabase({
    required WidgetRef ref,
    required String type,
  }) async {
    MedicineTypeRepo repo = MedicineTypeRepo();
    Future<num?> addType({required String type}) async {
      return await repo.addMedicineTypeForBulk(
        name: type,
      );
    }

    num? id;

    final List<MedicineTypeModel> data = await repo.fetchAllMedicineType();
    for (var element in data) {
      if (element.name?.toLowerCase().trim() == type.toLowerCase().trim()) {
        id = element.id;
        break;
      }
    }
    id ??= await addType(type: type);
    return id;
  }

  Future<num?> getManufacturerFromDatabase({
    required WidgetRef ref,
    required String givenManufacturerName,
  }) async {
    ManufacturerRepo repo = ManufacturerRepo();
    Future<num?> addManufacturer({required String name}) async {
      return await repo.addManufacturerForBulk(
        name: name,
      );
    }

    num? id;
    final List<ManufacturerModel> manufacturers = await repo.fetchAllManufacturers();
    for (var element in manufacturers) {
      if (element.name?.toLowerCase().trim() == givenManufacturerName.toLowerCase().trim()) {
        id = element.id;
        break;
      }
    }
    id ??= await addManufacturer(name: givenManufacturerName);

    return id;
  }

  ProductModel productModel = ProductModel();
  Meta meta = Meta();
  Stocks stocks = Stocks();
  for (var element in row) {
    if (element?.rowIndex == 0) {
      // Skip header row
      return null;
    }

    switch (element?.columnIndex) {
      /// Product name_________________________________
      case 1:
        print('row: ${element?.rowIndex} + name ${element?.value}');
        if (element?.value == null) return null;
        productModel.productName = element?.value.toString();
        break;

      /// Product code______________________________
      case 2:
        print('row: ${element?.rowIndex} + code ${element?.value}');
        if (element?.value == null) return null;
        productModel.productCode = element?.value.toString();
        break;

      /// Product stock______________________________
      case 3:
        print('row: ${element?.rowIndex} + stock ${element?.value}');
        if (element?.value == null || num.tryParse(element?.value.toString() ?? '') == null) {
          return null;
        }
        productModel.stockSum = num.tryParse(element?.value.toString() ?? '') ?? 0;
        break;

      /// Purchase price_____________________________
      case 4:
        print('row: ${element?.rowIndex} + purchase price ${element?.value}');
        if (element?.value == null || num.tryParse(element?.value.toString() ?? '') == null) {
          return null;
        }
        productModel.productPurchasePriceWithoutTax = num.tryParse(element?.value.toString() ?? '') ?? 0;
        break;

      /// Sales price___________________________________
      case 5:
        print('row: ${element?.rowIndex} + sales ${element?.value}');
        if (element?.value == null || num.tryParse(element?.value.toString() ?? '') == null) {
          return null;
        }
        productModel.productSalePrice = num.tryParse(element?.value.toString() ?? '') ?? 0;
        break;

      /// Wholesale price (optional)___________________
      case 6:
        if (element?.value != null) {
          productModel.productWholeSalePrice = num.tryParse(element?.value.toString() ?? '') ?? 0;
        }
        break;

      ///______Dealer price (optional)_________________________
      case 7:
        if (element?.value != null) {
          productModel.productDealerPrice = num.tryParse(element?.value.toString() ?? '') ?? 0;
        }
        break;

      ///_______Category (required)_______________________________
      case 8:
        if (element?.value == null) return null;
        num? categoryId = await getCategoryFromDatabase(ref: ref, givenCategoryName: element?.value.toString() ?? '');
        print('Category id for bulk ---------------------------> = $categoryId');
        if (categoryId == null) return null;
        productModel.categoryId = categoryId;
        break;

      ///________Box_size_______________________________
      case 9:
        num? id = await getBoxSizeFromDatabase(ref: ref, givenBoxSize: element?.value.toString() ?? '');
        print('Box id for bulk ---------------------------> = $id');
        productModel.boxSizeId = id;
        break;

      ///________Manufacturer_______________________________
      case 10: // brand
        if (element?.value != null) {
          num? id = await getManufacturerFromDatabase(ref: ref, givenManufacturerName: element?.value.toString() ?? '');
          print('manufacturer id for bulk ---------------------------> = $id');
          productModel.manufacturerId = id;
        }

        break;

      ///________Unit_______________________________
      case 11:
        if (element?.value != null) {
          num? id = await getUnitFromDatabase(ref: ref, givenUnitName: element?.value.toString() ?? '');
          print('Unit id for bulk ---------------------------> = $id');
          productModel.unitId = id;
        }
        break;

      ///________Type_______________________________
      case 12:
        if (element?.value != null) {
          num? id = await getMedicineTypeFromDatabase(ref: ref, type: element?.value.toString() ?? '');
          print('Type id for bulk ---------------------------> = $id');
          productModel.typeId = id;
        }
        break;

      ///____Expire_Date_________________________________
      case 13:
        if (element?.value != null) {
          productModel.stocks?.first.expireDate = DateTime.tryParse(element!.value.toString()).toString();
        }
        break;

      ///____strength_________________________________
      case 14:
        if (element?.value != null) {
          meta.strength = element!.value.toString();
        }
        break;

      ///____Generic Name_________________________________
      case 15:
        if (element?.value != null) {
          meta.genericName = element!.value.toString();
        }
        break;

      ///____shelf________________________________
      case 16:
        if (element?.value != null) {
          meta.shelf = element!.value.toString();
        }
        break;

      ///____batch No________________________________
      case 17:
        if (element?.value != null) {
          stocks.batchNo = element!.value.toString();
        }
        break;

      ///____Medicine Details________________________________
      case 18:
        if (element?.value != null) {
          meta.medicineDetails = element!.value.toString();
        }
        break;
    }
  }

  // Return null if any of the required fields are missing
  if (productModel.productName == null ||
      productModel.productCode == null ||
      productModel.stockSum == null ||
      productModel.productPurchasePriceWithoutTax == null ||
      productModel.productSalePrice == null ||
      productModel.categoryId == null) {
    return null;
  }
  productModel.meta = meta;

  return productModel;
}
