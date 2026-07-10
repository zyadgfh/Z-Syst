String currency = '\$';
String currencyName = 'US Dollar';
List<String> items = ['৳ (Taka)', '\$ (US Dollar)', "₹ (Rupee)", "€ (Euro)", "₽ (Ruble)", "£ (UK Pound)", "R (Rial)", "؋ Af ⁄ Afs"];

// numberToWords
String numberToWords(num inputNumber) {
  if (inputNumber == 0) return 'Zero';

  final List<String> units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];

  final List<String> teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];

  final List<String> tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

  String convertLessThanOneThousand(int number) {
    if (number >= 100) {
      return '${units[number ~/ 100]} Hundred ${convertLessThanOneThousand(number % 100)}'.trim();
    } else if (number >= 10 && number <= 19) {
      return teens[number - 10];
    } else {
      return '${tens[number ~/ 10]} ${units[number % 10]}'.trim();
    }
  }

  String convertIntegerPart(num number) {
    if (number == 0) {
      return '';
    } else if (number < 1000) {
      return convertLessThanOneThousand(number.toInt());
    } else {
      String result = '';
      for (int i = 0; number > 0; i++) {
        if (number % 1000 != 0) {
          result = '${convertLessThanOneThousand((number % 1000).toInt())} ${['', 'Thousand', 'Million', 'Billion'][i]} $result';
        }
        number ~/= 1000;
      }
      return result.trim();
    }
  }

  String convertFractionalPart(String fractionalPart) {
    final List<String> fractionalWords = [];
    for (int i = 0; i < fractionalPart.length; i++) {
      fractionalWords.add(units[int.parse(fractionalPart[i])]);
    }
    return fractionalWords.join(' ');
  }

  final parts = inputNumber.toString().split('.');
  final integerPart = int.parse(parts[0]);
  final fractionalPart = parts.length > 1 ? parts[1] : '';

  String result = convertIntegerPart(integerPart);
  if (fractionalPart.isNotEmpty) {
    result += ' Point ${convertFractionalPart(fractionalPart)}';
  }
  return result.trim();
}
