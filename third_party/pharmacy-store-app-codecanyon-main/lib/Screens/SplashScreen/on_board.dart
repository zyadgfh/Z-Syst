import 'package:flutter/material.dart';
import 'package:mobile_pos/Screens/Authentication/Welcome%20Screen/welcome_screen.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import '../../app_config/app_config.dart';
import '../../constant.dart';
import '../Authentication/Sign In/sign_in_screen.dart';

class OnBoard extends StatefulWidget {
  const OnBoard({super.key});

  @override
  OnBoardState createState() => OnBoardState();
}

class OnBoardState extends State<OnBoard> {
  PageController pageController = PageController(initialPage: 0);
  int currentIndexPage = 0;
  List<Map<String, dynamic>> getSlider({required BuildContext context}) {
    List<Map<String, dynamic>> sliderList = [
      {
        "icon": AppConfig.onboard1,
        "title": lang.S.of(context).easyToUseThePos,
        "description": lang.S.of(context).easytheusedesciption,
      },
      {
        "icon": AppConfig.onboard2,
        "title": lang.S.of(context).choseYourFeature,
        "description": lang.S.of(context).choseyourfeatureDesciption,
      },
      {
        "icon": AppConfig.onboard3,
        "title": lang.S.of(context).allBusinessSolutions,
        "description": lang.S.of(context).allBusinessolutionDescrip,
      },
    ];
    return sliderList;
  }

  List<Map<String, dynamic>> sliderList = [];

  @override
  Widget build(BuildContext context) {
    sliderList = getSlider(context: context);
    double screenHeight = MediaQuery.of(context).size.height;
    final theme = Theme.of(context);
    return Scaffold(
      backgroundColor: kWhite,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        actions: [
          TextButton(
            style: ButtonStyle(padding: WidgetStatePropertyAll(EdgeInsets.all(0))),
            onPressed: () {
              Navigator.pushReplacement(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const SignIn(),
                  ));
            },
            child: Text(
              lang.S.of(context).skip,
              style: theme.textTheme.bodyLarge?.copyWith(fontSize: 18, color: kNutrals500),
            ),
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Expanded(
              child: PageView.builder(
                itemCount: sliderList.length,
                controller: pageController,
                onPageChanged: (int index) => setState(() => currentIndexPage = index),
                itemBuilder: (_, index) {
                  return Column(
                    children: [
                      const SizedBox(
                        height: 10,
                      ),
                      Expanded(
                        flex: 3,
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          child: AspectRatio(
                            aspectRatio: 16 / 9, // Adjusts according to screen size
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(10),
                              child: Image.asset(
                                height: 407,
                                sliderList[index]['icon'],
                                fit: BoxFit.cover,
                              ),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 30),
                      Text(
                        sliderList[index]['title'].toString(),
                        style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700, fontSize: 22),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: 10),
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 20.0),
                        child: Text(
                          sliderList[index]['description'].toString(),
                          textAlign: TextAlign.center,
                          maxLines: 5,
                          style: theme.textTheme.bodyLarge?.copyWith(color: kNutral700),
                        ),
                      ),
                    ],
                  );
                },
              ),
            ),
            const SizedBox(
              height: 50,
            ),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(
                sliderList.length,
                (dotIndex) => AnimatedContainer(
                  duration: const Duration(milliseconds: 250),
                  margin: const EdgeInsets.symmetric(horizontal: 5),
                  height: 8.0,
                  width: currentIndexPage == dotIndex ? 25.0 : 10.0,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(5),
                    color: Theme.of(context).colorScheme.primary.withValues(alpha: currentIndexPage == dotIndex ? 1.0 : 0.4),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 50),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0),
              child: NewPrimaryButton(
                onPressed: () {
                  setState(
                    () {
                      currentIndexPage < 2
                          ? pageController.nextPage(
                              duration: const Duration(milliseconds: 500),
                              curve: Curves.easeInOut,
                            )
                          : Navigator.pushReplacement(
                              context,
                              MaterialPageRoute(
                                builder: (context) => const WelcomeScreen(),
                              ),
                            );
                    },
                  );
                },
                buttonText: currentIndexPage < 2 ? lang.S.of(context).next : '${lang.S.of(context).useFree} ${AppConfig.appName}',
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }
}
