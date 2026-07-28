import 'package:flutter/material.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../constant.dart';
import '../Home/home.dart';
import 'language_provider.dart';

class SelectLanguage extends StatefulWidget {
  const SelectLanguage({super.key, this.alreadySelectedLanguage});

  final String? alreadySelectedLanguage;

  @override
  State<SelectLanguage> createState() => _SelectLanguageState();
}

class _SelectLanguageState extends State<SelectLanguage> {
  Future<void> saveData(String data) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('lang', data);
  }

  Future<void> getInit() async {
    final prefs = await SharedPreferences.getInstance();
    final savedLanguageCode = prefs.getString('lang') ?? 'en'; // Default to English code
    setState(() {
      selectedLanguage = savedLanguageCode;
    });
    context.read<LanguageChangeProvider>().changeLocale(savedLanguageCode);
  }

  @override
  void initState() {
    // TODO: implement initState
    super.initState();
    getInit();
  }

  @override
  Widget build(BuildContext context) {
    print(languageMap.length);
    final theme = Theme.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.transparent,
        leading: GestureDetector(
          onTap: () {
            Navigator.pop(context);
          },
          child: const Icon(
            FeatherIcons.x,
            color: Colors.white,
          ),
        ),
        centerTitle: true,
        title: Text(
          lang.S.of(context).selectLang,
          style: theme.textTheme.titleLarge?.copyWith(color: Colors.white),
        ),
      ),
      body: ListView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        itemCount: languageMap.length,
        shrinkWrap: true,
        physics: AlwaysScrollableScrollPhysics(),
        itemBuilder: (_, index) {
          final entry = languageMap.entries.toList()[index];
          String languageName = entry.key;
          String languageCode = entry.value;
          return StatefulBuilder(
            builder: (_, i) {
              return Padding(
                padding: const EdgeInsets.only(bottom: 10.0),
                child: ListTile(
                  onTap: () {
                    setState(() {
                      selectedLanguage = languageCode;
                    });
                  },
                  contentPadding: const EdgeInsets.only(left: 10, right: 10.0),
                  horizontalTitleGap: 10,
                  title: Text(languageName),
                  trailing: Icon(
                    selectedLanguage == languageCode ? Icons.radio_button_checked_outlined : Icons.circle_outlined,
                    color: selectedLanguage == languageCode ? kMainColor : Colors.grey,
                  ),
                  visualDensity: VisualDensity(horizontal: -4, vertical: -4),
                ),
              );
            },
          );
        },
      ),
      bottomNavigationBar: Padding(
        padding: const EdgeInsets.only(left: 16, right: 16, bottom: 12),
        child: NewPrimaryButton(
          buttonText: lang.S.of(context).save,
          onPressed: () async {
            if (selectedLanguage != null) {
              await saveData(selectedLanguage!);
              context.read<LanguageChangeProvider>().changeLocale(selectedLanguage!);
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (context) => const Home()),
              );
            }
          },
        ),
      ),
    );
  }
}
