import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:material_design_icons_flutter/material_design_icons_flutter.dart';
import 'package:mobile_pos/Screens/Authentication/Sign%20Up/repo/sign_up_repo.dart';
import 'package:mobile_pos/Screens/Authentication/Sign%20Up/verify_email.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import '../../../GlobalComponents/button_global.dart';
import '../../../constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;

import '../widgets/check_email_for_otp_popup.dart';

class SignUpScreen extends StatefulWidget {
  const SignUpScreen({super.key});

  @override
  State<SignUpScreen> createState() => _SignUpScreenState();
}

class _SignUpScreenState extends State<SignUpScreen> {
  ///__________Variables________________________________
  bool showPassword = true;
  bool isClicked = false;

  ///________Key_______________________________________
  GlobalKey<FormState> key = GlobalKey<FormState>();

  ///___________Controllers______________________________
  TextEditingController nameTextController = TextEditingController();
  TextEditingController passwordTextController = TextEditingController();
  TextEditingController emailTextController = TextEditingController();

  ///________Dispose____________________________________
  @override
  void dispose() {
    super.dispose();
    nameTextController.dispose();
    passwordTextController.dispose();
    emailTextController.dispose();
  }

  @override
  Widget build(BuildContext context) {
    TextTheme textTheme = Theme.of(context).textTheme;
    return AcnooScafoldWidget(
      // backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        centerTitle: true,
        // surfaceTintColor: kWhite,
        iconTheme: const IconThemeData(color: kWhite),
        title: Text(
          lang.S.of(context).register,
          style: textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600, color: kWhite, fontSize: 20),
        ),
      ),
      body: SingleChildScrollView(
        padding: EdgeInsets.symmetric(horizontal: 16),
        child: Form(
          key: key,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 40),
                child: const NameWithLogo(),
              ),

              ///____________Name______________________________________________
              TextFormField(
                controller: nameTextController,
                keyboardType: TextInputType.name,
                decoration: InputDecoration(
                  //labelText: 'Full Name',
                  labelText: lang.S.of(context).fullName,
                  //hintText: 'Enter your full name',
                  hintText: lang.S.of(context).enterYourFullName,
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    //return 'name can\'n be empty';
                    return lang.S.of(context).nameCanNotBeEmpty;
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20.0),

              ///__________Email______________________________________________
              TextFormField(
                controller: emailTextController,
                keyboardType: TextInputType.emailAddress,
                decoration: InputDecoration(
                  // border: OutlineInputBorder(),
                  // labelText: 'email',
                  labelText: lang.S.of(context).lableEmail,
                  //hintText: 'Enter email address',
                  hintText: lang.S.of(context).hintEmail,
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    //return 'Email can\'n be empty';
                    return lang.S.of(context).emailCannotBeEmpty;
                  } else if (!value.contains('@')) {
                    //return 'Please enter a valid email';
                    return lang.S.of(context).pleaseEnterAValidEmail;
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20.0),

              ///___________Password_____________________________________________
              TextFormField(
                controller: passwordTextController,
                keyboardType: TextInputType.text,
                obscureText: showPassword,
                decoration: InputDecoration(
                  //labelText: 'Password',
                  labelText: lang.S.of(context).lablePassword,
                  // hintText: 'Enter password',
                  hintText: lang.S.of(context).hintPassword,
                  suffixIcon: IconButton(
                    onPressed: () {
                      setState(() {
                        showPassword = !showPassword;
                      });
                    },
                    icon: Icon(
                      showPassword ? MdiIcons.eyeOff : MdiIcons.eye,
                      color: kNutrals500,
                    ),
                  ),
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    //return 'Password can\'t be empty';
                    return lang.S.of(context).passwordCannotBeEmpty;
                  } else if (value.length < 6) {
                    //return 'Please enter a bigger password';
                    return lang.S.of(context).pleaseEnterABiggerPassword;
                  }
                  return null;
                },
              ),
              const SizedBox(height: 30.0),

              ///________Button___________________________________________________
              NewPrimaryButton(
                onPressed: () async {
                  if (isClicked) {
                    return;
                  }
                  if (key.currentState?.validate() ?? false) {
                    isClicked = true;
                    EasyLoading.show();
                    SignUpRepo repo = SignUpRepo();
                    if (await repo.signUp(name: nameTextController.text, email: emailTextController.text, password: passwordTextController.text, context: context)) {
                      if (await checkEmailForCodePupUp(email: emailTextController.text, context: context, textTheme: textTheme)) {
                        Navigator.pushReplacement(
                          context,
                          MaterialPageRoute(
                            builder: (context) => VerifyEmail(
                              email: emailTextController.text,
                              isFormForgotPass: false,
                            ),
                          ),
                        );
                      }
                    } else {
                      isClicked = false;
                    }
                  }
                },
                buttonText: lang.S.of(context).signUps,
                //'Sign Up',
              ),
              const SizedBox(
                height: 20,
              ),
              RichText(
                text: TextSpan(
                  text: lang.S.of(context).alreadyHaveAnAccount,
                  //'Already have an account? ',
                  style: textTheme.bodyMedium?.copyWith(color: kGreyTextColor),
                  children: [
                    TextSpan(
                      text: lang.S.of(context).signIn,
                      recognizer: TapGestureRecognizer()..onTap = () => Navigator.pop(context),
                      //'Sign In',
                      style: textTheme.bodyMedium?.copyWith(color: kMainColor, fontWeight: FontWeight.bold),
                    )
                  ],
                ),
              )
            ],
          ),
        ),
      ),
    );
  }
}
