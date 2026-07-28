import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';
import 'package:google_fonts/google_fonts.dart';
import 'Screens/Purchase List/model/purchase_details_model.dart';

const kMainColor = Color(0xff00987F);
const kMainColorBg = Color(0xffE7F7EF);
const kSubTitleColor = Color(0xffFF8C34);
const kGreyTextColor = Color(0xFF828282);
const kBackgroundColor = Color(0xffF5F3F3);
const kBorderColorTextField = Color(0xFFC2C2C2);
const kDarkWhite = Color(0xFFF1F7F7);
const kWhite = Color(0xFFffffff);
const kOutlineColor = Color(0xffE6E6E9);
const kMainColor100 = Color(0xffE7F7EF);
const kRed100 = Color(0xffFFE2E2);
const kTextFiledBorder = Color(0xffD7D9DE);
const kBorderColor = Color(0xffD8D8D8);
const kPremiumPlanColor = Color(0xFF8752EE);
const kPremiumPlanColor2 = Color(0xFFFF5F00);
const kTitleColor = Color(0xFF000000);
const kNutral700 = Color(0xff404040);
const kNutrals500 = Color(0xff808191);
const kLineColor = Color(0xffE6E6E9);
const kNutral600 = Color(0xff737373);
const kNutral800 = Color(0xff1B1D21);
Color kOutlineBorder = Color(0xff8b8b9e4d).withValues(alpha: 0.3);
const kNutrals900 = Color(0xff131313);
const kTitle3 = Color(0xff464C54);

const inNutral900 = Color(0xff121212);
const inNutral700 = Color(0xff4D4D4D);
const inMainBg = Color(0xffE7F7EF);
const inNutral100 = Color(0xffD8D8D8);
const kSecondaryTextColor = Color(0xFFFFFFFF);
final kTextStyle = GoogleFonts.manrope(
  color: Colors.white,
);
List<String> paymentsTypeList = ['Cash', 'Card', 'Check', 'Mobile Pay', 'Due'];

enum TaxType { Inclusive, Exclusive }

bool isExpiringInFiveDays = false;
bool isExpiringInOneDays = false;

String paypalClientId = '';
String paypalClientSecret = '';
const bool sandbox = true;
String noProductImageUrl = 'images/no_product_image.png';

const String printIcon = 'assets/logo/print.svg';
const String pdfIcon = 'assets/logo/pdf.svg';


const kSplashGradiant = LinearGradient(colors: [
  Color(0xff14B8A6),
  Color(0xff00987F),
], begin: Alignment.topCenter, end: Alignment.bottomCenter);

const kGradiant = LinearGradient(colors: [
  Color(0xff00987F),
  Color(0xff14B8A6),
]);

///---------------update information---------------

//__________Language________________________________
Map<String, String> languageMap = {
  'English': 'en',
  'Afrikaans': 'af',
  'Amharic': 'am',
  'Arabic': 'ar',
  'Assamese': 'as',
  'Azerbaijani': 'az',
  'Belarusian': 'be',
  'Bulgarian': 'bg',
  'Bengali': 'bn',
  'Bosnian': 'bs',
  'Catalan Valencian': 'ca',
  'Czech': 'cs',
  'Welsh': 'cy',
  'Danish': 'da',
  'German': 'de',
  'Modern Greek': 'el',
  'Spanish Castilian': 'es',
  'Estonian': 'et',
  'Basque': 'eu',
  'Persian': 'fa',
  'Finnish': 'fi',
  'Filipino Pilipino': 'fil',
  'French': 'fr',
  'Galician': 'gl',
  'Swiss German Alemannic Alsatian': 'gsw',
  'Gujarati': 'gu',
  'Hebrew': 'he',
  'Hindi': 'hi',
  'Croatian': 'hr',
  'Hungarian': 'hu',
  'Armenian': 'hy',
  'Indonesian': 'id',
  'Icelandic': 'is',
  'Italian': 'it',
  'Japanese': 'ja',
  'Georgian': 'ka',
  'Kazakh': 'kk',
  'Khmer Central Khmer': 'km',
  'Kannada': 'kn',
  'Korean': 'ko',
  'Kirghiz Kyrgyz': 'ky',
  'Lao': 'lo',
  'Lithuanian': 'lt',
  'Latvian': 'lv',
  'Macedonian': 'mk',
  'Malayalam': 'ml',
  'Mongolian': 'mn',
  'Marathi': 'mr',
  'Malay': 'ms',
  'Burmese': 'my',
  'Norwegian Bokmål': 'nb',
  'Nepali': 'ne',
  'Dutch Flemish': 'nl',
  'Norwegian': 'no',
  'Oriya': 'or',
  'Panjabi Punjabi': 'pa',
  'Polish': 'pl',
  'Pushto Pashto': 'ps',
  'Portuguese': 'pt',
  'Romanian Moldavian Moldovan': 'ro',
  'Sinhala Sinhalese': 'si',
  'Slovak': 'sk',
  'Slovenian': 'sl',
  'Albanian': 'sq',
  'Serbian': 'sr',
  'Swedish': 'sv',
  'Swahili': 'sw',
  'Tamil': 'ta',
  'Telugu': 'te',
  'Thai': 'th',
  'Tagalog': 'tl',
  'Turkish': 'tr',
  'Ukrainian': 'uk',
  'Urdu': 'ur',
  'Uzbek': 'uz',
  'Vietnamese': 'vi',
  'Chinese': 'zh',
  'Russian': 'ru',
};

String? selectedLanguage = languageMap['English'];

const kButtonDecoration = BoxDecoration(
  borderRadius: BorderRadius.all(
    Radius.circular(5),
  ),
);

num getPurchasePrice({required Details product}) {
  if (product.product?.taxType?.toLowerCase() == 'inclusive') {
    return product.purchaseWithTax ?? 0;
  }
  return product.purchaseWithoutTax ?? 0;
}

num calculateSubtotal({required PurchaseDetailsData detail}) {
  num subTotal = 0.0;
  for (var detail in detail.details!) {
    subTotal += getPurchasePrice(product: detail) * (detail.quantities ?? 0);
  }
  return subTotal = num.parse(subTotal.toStringAsFixed(2));
}

InputDecoration kInputDecoration(BuildContext context) {
  return InputDecoration(
    hintStyle: Theme.of(context).textTheme.bodyLarge?.copyWith(color: kNutral700),
    labelStyle: Theme.of(context).textTheme.bodyMedium?.copyWith(color: kNutrals900),
    floatingLabelBehavior: FloatingLabelBehavior.always,
    enabledBorder: const OutlineInputBorder(
      borderRadius: BorderRadius.all(Radius.circular(8.0)),
      borderSide: BorderSide(color: kBorderColor, width: 1),
    ),
    focusedBorder: const OutlineInputBorder(
      borderRadius: BorderRadius.all(Radius.circular(6.0)),
      borderSide: BorderSide(color: kBorderColor, width: 1),
    ),
  );
}

final gTextStyle = GoogleFonts.poppins(
  color: Colors.white,
);

OutlineInputBorder outlineInputBorder() {
  return OutlineInputBorder(
    borderRadius: BorderRadius.circular(1.0),
    borderSide: const BorderSide(color: kBorderColorTextField),
  );
}

final otpInputDecoration = InputDecoration(
  contentPadding: const EdgeInsets.symmetric(vertical: 5.0),
  border: outlineInputBorder(),
  focusedBorder: outlineInputBorder(),
  enabledBorder: outlineInputBorder(),
);

// List<String> language = ['English'];

List<String> productCategory = ['Fashion', 'Electronics', 'Computer', 'Gadgets', 'Watches', 'Cloths'];

//______________________________________________Back_Button__________________
class Back extends StatelessWidget {
  final VoidCallback onPressed;

  const Back({super.key, required this.onPressed});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 40,
      width: 40,
      decoration: const BoxDecoration(
        color: Color(0xFFF3F3F3),
        shape: BoxShape.circle,
      ),
      child: IconButton(
        alignment: Alignment.center,
        padding: EdgeInsets.zero,
        tooltip: 'Back',
        splashColor: Colors.transparent,
        highlightColor: Colors.transparent,
        onPressed: onPressed,
        icon: const Icon(
          Icons.arrow_back,
          color: Colors.black,
          size: 24,
        ),
      ),
    );
  }
}

class PrimaryButton extends StatelessWidget {
  final VoidCallback onPressed;
  final String text;

  const PrimaryButton({
    super.key,
    required this.onPressed,
    required this.text,
  });

  @override
  Widget build(BuildContext context) {
    TextTheme textTheme = Theme.of(context).textTheme;
    bool isDark = Theme.of(context).brightness == Brightness.dark;
    return SizedBox(
      height: 48.0,
      width: double.infinity,
      child: ElevatedButton(
        onPressed: onPressed,
        style: const ButtonStyle(backgroundColor: WidgetStatePropertyAll(kMainColor)),
        child: Text(
          text,
          style: textTheme.labelLarge,
        ),
      ),
    );
  }
}

//--------global icon button----------
class GlobalIconButton extends StatelessWidget {
  const GlobalIconButton({super.key, required this.onPressed, required this.icon});
  final VoidCallback onPressed;
  final String icon;

  @override
  Widget build(BuildContext context) {
    return IconButton(
      padding: EdgeInsets.zero,
      visualDensity: VisualDensity(horizontal: -4),
      onPressed: onPressed,
      icon: Container(
        height: 30,
        width: 30,
        padding: EdgeInsets.all(4),
        decoration: BoxDecoration(
          color: Color(0xFFFFFFFF).withValues(alpha: 0.22),
          borderRadius: BorderRadius.circular(4),
        ),
        child: SvgPicture.asset(icon),
      ),
    );
  }
}

//------------Dotted Border-----------------

class GlobalDottedBorder extends StatelessWidget {
  const GlobalDottedBorder({super.key});

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        double availableWidth = constraints.maxWidth;
        double dotSpacing = 6;
        double dotWidth = 1;
        int dotCount = (availableWidth / (dotSpacing + dotWidth)).floor();
        return Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: List.generate(dotCount, (index) {
            return Container(
              height: 1,
              width: 4,
              color: kOutlineBorder,
            );
          }),
        );
      },
    );
  }
}
